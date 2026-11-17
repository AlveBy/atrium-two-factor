<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Http\Controllers;

use Atrium\Admin\Auth\SignIn;
use Atrium\TwoFactor\Guards;
use Atrium\TwoFactor\Models\Factor as FactorModel;
use Atrium\TwoFactor\Services\Audit;
use Atrium\TwoFactor\Services\Challenge;
use Atrium\TwoFactor\Services\Codes;
use Atrium\TwoFactor\Services\Lockout;
use Atrium\TwoFactor\Services\Passkeys;
use Atrium\TwoFactor\Services\RecoveryCodes;
use Atrium\TwoFactor\Services\TrustedDevices;
use Atrium\TwoFactor\Support\Person;
use Atrium\TwoFactor\Support\Redirects;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

/**
 * The page that asks for the second factor, and its answers.
 */
final class ChallengeController extends Controller
{
    public function __construct(
        Guards $guards,
        private readonly Challenge $challenge,
        private readonly Lockout $lockout,
        private readonly RecoveryCodes $recovery,
        private readonly TrustedDevices $devices,
        private readonly Passkeys $passkeys,
        private readonly Codes $codes,
        private readonly Audit $audit,
    ) {
        parent::__construct($guards);
    }

    public function show(Request $request): Response
    {
        $guard = $this->guard($request);
        $user = $this->challenge->user($request, $guard);

        if ($user === null) {
            return redirect()->to($this->guards->loginUrl($guard));
        }

        $factor = $this->challenge->current($request, $guard, $user);
        $target = match ($factor?->key()) {
            'email' => Person::email($user),
            'sms' => FactorModel::query()->of($guard, $user->getAuthIdentifier())->confirmed()->where('type', 'sms')->latest('id')->firstOrFail()->secret,
            default => '',
        };

        return response($this->view($request, 'challenge', [
            'factor' => $factor,
            'factors' => $this->challenge->factors($request, $guard, $user),
            'destination' => match ($factor?->key()) {
                'email' => Person::maskEmail($target),
                'sms' => Person::maskPhone($target),
                default => null,
            },
            'recovery' => $request->boolean('recovery') || $factor === null,
            'hasRecovery' => $this->recovery->remaining($guard, $user->getAuthIdentifier()) > 0,
            'trustedDays' => $this->guards->trustedDays($guard),
            'resendIn' => $target !== '' ? $this->codes->retryAfter($guard, Codes::TWO_FACTOR, $target) : 0,
        ]));
    }

    public function verify(Request $request): Response
    {
        $guard = $this->guard($request);
        $user = $this->challenge->user($request, $guard);
        $pending = $this->challenge->pending($request, $guard);

        if ($user === null || $pending === null) {
            return $this->expired($request, $guard);
        }

        $id = $user->getAuthIdentifier();
        $this->guardAgainstFlood($guard, $id, $request);

        $recoveryCode = trim((string) $request->input('recovery_code', ''));
        $factor = $this->challenge->current($request, $guard, $user);

        if ($recoveryCode !== '') {
            $passed = $this->recovery->use($guard, $id, $recoveryCode);
            $method = 'recovery';
        } else {
            $request->validate(
                $factor?->input() === 'code' ? ['code' => ['required', 'string', 'max:20']] : [],
                ['code.required' => __('atrium-2fa::two-factor.challenge.code_required')],
            );
            $passed = $factor !== null && $factor->verify($user, $guard, $request);
            $method = (string) $factor?->key();
        }

        if (! $passed) {
            return $this->failed($request, $guard, $user, $method);
        }

        $this->lockout->clear($guard, $id, $request->ip());
        $this->challenge->forget($request, $guard);

        if ($method === 'recovery') {
            $this->audit->record($guard, $user, Audit::RECOVERY_USED, 'recovery', ['left' => $this->recovery->remaining($guard, $id)]);
        }

        $response = app(SignIn::class)->finish($request, $user, $guard, $pending['method'], $pending['remember']);

        if ($request->boolean('trust') && $this->guards->trustedDays($guard) > 0) {
            $response->headers->setCookie($this->devices->trust($request, $guard, $id));
            $this->audit->record($guard, $user, Audit::DEVICE_TRUSTED, $method);
        }

        if ($method === 'recovery' && $response instanceof RedirectResponse) {
            $response->with('atrium-2fa.status', __('atrium-2fa::two-factor.challenge.recovery_left', ['count' => $this->recovery->remaining($guard, $id)]));
        }

        return $request->expectsJson() ? Redirects::forScript($response) : $response;
    }

    /**
     * Switch to another factor the person has (the phone is not at hand).
     */
    public function switch(Request $request): RedirectResponse
    {
        $guard = $this->guard($request);
        $user = $this->challenge->user($request, $guard);

        if ($user === null) {
            return redirect()->to($this->guards->loginUrl($guard));
        }

        foreach ($this->challenge->factors($request, $guard, $user) as $factor) {
            if ($factor->key() === $request->input('factor')) {
                $this->challenge->use($request, $guard, $factor->key());
                $this->challenge->prepare($request, $guard, $user, $factor);
            }
        }

        return redirect()->to($this->guards->route($guard, 'challenge'));
    }

    /**
     * Send the code again.
     */
    public function resend(Request $request): RedirectResponse
    {
        $guard = $this->guard($request);
        $user = $this->challenge->user($request, $guard);
        $factor = $user !== null ? $this->challenge->current($request, $guard, $user) : null;

        if ($user === null || $factor === null) {
            return redirect()->to($this->guards->loginUrl($guard));
        }

        $back = redirect()->to($this->guards->route($guard, 'challenge'));

        if ($this->challenge->prepare($request, $guard, $user, $factor)) {
            return $back->with('atrium-2fa.status', __('atrium-2fa::two-factor.challenge.resent'));
        }

        return $request->session()->has('atrium-2fa.error')
            ? $back
            : $back->withErrors(['code' => __('atrium-2fa::two-factor.challenge.resend_too_soon')]);
    }

    /**
     * What the browser needs to sign the challenge with a passkey.
     */
    public function options(Request $request): JsonResponse
    {
        $guard = $this->guard($request);
        $user = $this->challenge->user($request, $guard);
        abort_if($user === null, 419);

        return new JsonResponse($this->passkeys->assertionOptions($request, $guard, Passkeys::CHALLENGE, $user));
    }

    public function cancel(Request $request): RedirectResponse
    {
        $guard = $this->guard($request);
        $this->challenge->forget($request, $guard);

        return redirect()->to($this->guards->loginUrl($guard));
    }

    private function guardAgainstFlood(string $guard, int|string $id, Request $request): void
    {
        $wait = $this->lockout->wait($guard, $id, $request->ip());

        if ($wait > 0) {
            throw ValidationException::withMessages([
                'code' => __('atrium-2fa::two-factor.challenge.throttle', ['seconds' => $wait]),
            ]);
        }
    }

    private function failed(Request $request, string $guard, Authenticatable $user, string $method): Response
    {
        $locked = $this->lockout->hit($guard, $user->getAuthIdentifier(), $request->ip());

        $this->audit->record($guard, $user, Audit::FAILED, $method);

        if ($locked) {
            $this->audit->record($guard, $user, Audit::LOCKED, $method);
        }

        $message = __($method === 'recovery' ? 'atrium-2fa::two-factor.challenge.recovery_invalid' : 'atrium-2fa::two-factor.challenge.code_invalid');

        if ($request->expectsJson()) {
            return new JsonResponse(['message' => $message, 'errors' => ['code' => [$message]]], 422);
        }

        return redirect()->to($this->guards->route($guard, 'challenge', $method === 'recovery' ? ['recovery' => 1] : []))
            ->withErrors(['code' => $message]);
    }

    private function expired(Request $request, string $guard): Response
    {
        $message = __('atrium-2fa::two-factor.challenge.expired');

        if ($request->expectsJson()) {
            return new JsonResponse(['message' => $message, 'redirect' => $this->guards->loginUrl($guard)], 419);
        }

        return redirect()->to($this->guards->loginUrl($guard))->withErrors(['email' => $message]);
    }
}

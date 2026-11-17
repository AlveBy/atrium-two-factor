<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Http\Controllers;

use Atrium\Admin\Auth\SignIn;
use Atrium\TwoFactor\Guards;
use Atrium\TwoFactor\Mail\CodeMail;
use Atrium\TwoFactor\Services\Audit;
use Atrium\TwoFactor\Services\Codes;
use Atrium\TwoFactor\Support\Person;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

use function Illuminate\Support\defer;

/**
 * Signing in with a six-digit code sent to the e-mail, no password.
 *
 * The answer is the same whether the address belongs to anybody: the same
 * page, the same words, and the same time, since the mail goes out after the
 * response and the code is hashed either way.
 */
final class MagicCodeController extends Controller
{
    public function __construct(
        Guards $guards,
        private readonly Codes $codes,
        private readonly Audit $audit,
    ) {
        parent::__construct($guards);
    }

    public function create(Request $request): Response
    {
        $this->enabledOrFail($request);

        return response($this->view($request, 'magic-email'));
    }

    public function store(Request $request): RedirectResponse
    {
        $guard = $this->enabledOrFail($request);
        $email = mb_strtolower((string) $request->validate(
            ['email' => ['required', 'string', 'email', 'max:255']],
            [
                'email.required' => __('atrium-2fa::two-factor.magic.email_required'),
                'email.email' => __('atrium-2fa::two-factor.magic.email_invalid'),
            ],
        )['email']);

        $this->throttle($request, "atrium-2fa-magic-send|{$guard}|{$request->ip()}", 10, 'email');

        $user = $this->guards->findByEmail($guard, $email);
        $request->session()->put($this->key($guard), $email);

        // An unknown address gets a code too, mailed to nobody: the time, the
        // pause before a new one and the answers to wrong, late or too many
        // tries are then the same as for a real address.
        $code = $this->codes->issue($guard, Codes::MAGIC, $email, $user?->getAuthIdentifier());

        if ($code !== null && $user !== null) {
            $ttl = $this->guards->codeTtl($guard);
            $site = Person::siteName();
            $locale = app()->getLocale();

            defer(function () use ($email, $code, $ttl, $site, $locale): void {
                try {
                    Mail::to($email)->locale($locale)->send(new CodeMail($code, $ttl, Codes::MAGIC, $site));
                } catch (Throwable $e) {
                    Log::error('Could not send a sign-in code', ['exception' => $e]);
                }
            });

            $this->audit->record($guard, $user, Audit::CODE_SENT, 'code');
        }

        return $this->toCode($guard);
    }

    public function edit(Request $request): Response
    {
        $guard = $this->enabledOrFail($request);
        $email = $request->session()->get($this->key($guard));

        if (! is_string($email)) {
            return redirect()->to($this->guards->route($guard, 'magic'));
        }

        return response($this->view($request, 'magic-code', ['email' => $email]));
    }

    public function update(Request $request): Response
    {
        $guard = $this->enabledOrFail($request);
        $email = $request->session()->get($this->key($guard));

        if (! is_string($email)) {
            return redirect()->to($this->guards->route($guard, 'magic'));
        }

        $request->validate(
            ['code' => ['required', 'string', 'max:20']],
            ['code.required' => __('atrium-2fa::two-factor.challenge.code_required')],
        );
        $this->throttle($request, "atrium-2fa-magic-check|{$guard}|{$request->ip()}", 20, 'code');

        $user = $this->guards->findByEmail($guard, $email);
        $result = $this->codes->verify($guard, Codes::MAGIC, $email, (string) $request->input('code'));

        if ($user === null || $result !== Codes::OK) {
            if ($user !== null) {
                $this->audit->record($guard, $user, Audit::FAILED, 'code', ['reason' => $result]);
            }

            throw ValidationException::withMessages(['code' => __(match ($result) {
                Codes::EXPIRED => 'atrium-2fa::two-factor.magic.expired',
                Codes::TOO_MANY => 'atrium-2fa::two-factor.magic.too_many',
                default => 'atrium-2fa::two-factor.challenge.code_invalid',
            })]);
        }

        $request->session()->forget($this->key($guard));

        return app(SignIn::class)->start($request, $user, $guard, 'code', false);
    }

    private function enabledOrFail(Request $request): string
    {
        $guard = $this->guard($request);
        abort_unless($this->guards->magicCode($guard), 404);

        return $guard;
    }

    private function throttle(Request $request, string $key, int $perMinute, string $field): void
    {
        if (RateLimiter::tooManyAttempts($key, $perMinute)) {
            throw ValidationException::withMessages([
                $field => __('atrium-2fa::two-factor.challenge.throttle', ['seconds' => RateLimiter::availableIn($key)]),
            ]);
        }

        RateLimiter::hit($key, 60);
    }

    private function toCode(string $guard): RedirectResponse
    {
        return redirect()->to($this->guards->route($guard, 'magic.code'))
            ->with('atrium-2fa.status', __('atrium-2fa::two-factor.magic.sent'));
    }

    private function key(string $guard): string
    {
        return "atrium-2fa.magic.{$guard}";
    }
}

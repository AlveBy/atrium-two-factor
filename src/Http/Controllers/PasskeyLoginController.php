<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Http\Controllers;

use Atrium\Admin\Auth\SignIn;
use Atrium\TwoFactor\Guards;
use Atrium\TwoFactor\Services\Audit;
use Atrium\TwoFactor\Services\Passkeys;
use Atrium\TwoFactor\Support\Redirects;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signing in with a passkey alone: the device checks the fingerprint, the
 * face or the PIN, so it is two factors in one and no password is asked.
 */
final class PasskeyLoginController extends Controller
{
    public function __construct(
        Guards $guards,
        private readonly Passkeys $passkeys,
        private readonly Audit $audit,
    ) {
        parent::__construct($guards);
    }

    public function show(Request $request): Response
    {
        $this->enabledOrFail($request);

        return response($this->view($request, 'passkey-login'));
    }

    public function options(Request $request): JsonResponse
    {
        $guard = $this->enabledOrFail($request);

        return new JsonResponse($this->passkeys->assertionOptions($request, $guard, Passkeys::PASSWORDLESS));
    }

    public function store(Request $request): Response
    {
        $guard = $this->enabledOrFail($request);
        $key = "atrium-2fa-passkey|{$guard}|{$request->ip()}";

        if (RateLimiter::tooManyAttempts($key, 10)) {
            return $this->refused(__('atrium-2fa::two-factor.challenge.throttle', ['seconds' => RateLimiter::availableIn($key)]), 429);
        }

        $factor = $this->passkeys->verifyAssertion($request, $guard, Passkeys::PASSWORDLESS);
        $user = $factor !== null ? $this->guards->find($guard, $factor->user_id) : null;

        if ($user === null) {
            RateLimiter::hit($key, 60);

            if ($factor !== null) {
                $this->audit->record($guard, $factor->user_id, Audit::FAILED, 'passkey', ['reason' => 'inactive']);
            }

            return $this->refused(__('atrium-2fa::two-factor.passkey.failed'), 422);
        }

        RateLimiter::clear($key);
        $response = app(SignIn::class)->start($request, $user, $guard, 'passkey', false);

        return Redirects::forScript($response);
    }

    private function enabledOrFail(Request $request): string
    {
        $guard = $this->guard($request);
        abort_unless($this->guards->passwordless($guard), 404);

        return $guard;
    }

    private function refused(string $message, int $status): JsonResponse
    {
        return new JsonResponse(['message' => $message], $status);
    }
}

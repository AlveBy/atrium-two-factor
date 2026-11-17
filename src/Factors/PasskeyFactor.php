<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Factors;

use Atrium\TwoFactor\Contracts\Factor;
use Atrium\TwoFactor\Models\Factor as FactorModel;
use Atrium\TwoFactor\Services\Passkeys;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;

/**
 * A passkey: the phone's or the computer's fingerprint or face check, or a
 * security key. The browser does the work (WebAuthn); nothing to type.
 */
final class PasskeyFactor implements Factor
{
    public function __construct(private readonly Passkeys $passkeys) {}

    public function key(): string
    {
        return 'passkey';
    }

    public function label(): string
    {
        return __('atrium-2fa::two-factor.factors.passkey');
    }

    public function input(): string
    {
        return 'webauthn';
    }

    public function enrolled(Authenticatable $user, string $guard): bool
    {
        return FactorModel::query()->of($guard, $user->getAuthIdentifier())->confirmed()->where('type', 'passkey')->exists();
    }

    public function prepare(Authenticatable $user, string $guard): bool
    {
        return true;
    }

    public function verify(Authenticatable $user, string $guard, Request $request): bool
    {
        return $this->passkeys->verifyAssertion($request, $guard, Passkeys::CHALLENGE, $user) !== null;
    }
}

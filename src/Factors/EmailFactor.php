<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Factors;

use Atrium\TwoFactor\Contracts\Factor;
use Atrium\TwoFactor\Guards;
use Atrium\TwoFactor\Mail\CodeMail;
use Atrium\TwoFactor\Models\Factor as FactorModel;
use Atrium\TwoFactor\Services\Codes;
use Atrium\TwoFactor\Support\Person;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/**
 * A six-digit code sent to the person's e-mail: the panel's original second
 * step. Needs nothing set up, so it is also the fallback for a person who
 * must pass a second step and has no other factor yet.
 */
final class EmailFactor implements Factor
{
    public function __construct(
        private readonly Codes $codes,
        private readonly Guards $guards,
    ) {}

    public function key(): string
    {
        return 'email';
    }

    public function label(): string
    {
        return __('atrium-2fa::two-factor.factors.email');
    }

    public function input(): string
    {
        return 'code';
    }

    public function enrolled(Authenticatable $user, string $guard): bool
    {
        return FactorModel::query()->of($guard, $user->getAuthIdentifier())->confirmed()->where('type', 'email')->exists();
    }

    public function prepare(Authenticatable $user, string $guard): bool
    {
        $email = Person::email($user);
        $code = $this->codes->issue($guard, Codes::TWO_FACTOR, $email, $user->getAuthIdentifier());

        if ($code === null) {
            return false;
        }

        Mail::to($email)->send(new CodeMail($code, $this->guards->codeTtl($guard), Codes::TWO_FACTOR, Person::siteName()));

        return true;
    }

    public function verify(Authenticatable $user, string $guard, Request $request): bool
    {
        return $this->codes->verify($guard, Codes::TWO_FACTOR, Person::email($user), (string) $request->input('code', '')) === Codes::OK;
    }
}

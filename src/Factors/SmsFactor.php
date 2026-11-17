<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Factors;

use Atrium\TwoFactor\Contracts\Factor;
use Atrium\TwoFactor\Contracts\SmsDriver;
use Atrium\TwoFactor\Models\Factor as FactorModel;
use Atrium\TwoFactor\Services\Codes;
use Atrium\TwoFactor\Support\Person;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;

/**
 * A six-digit code by SMS to the phone the person confirmed. The phone is
 * kept encrypted; the SMS goes out through the SmsDriver (the log by default).
 */
final class SmsFactor implements Factor
{
    public function __construct(
        private readonly Codes $codes,
        private readonly SmsDriver $sms,
    ) {}

    public function key(): string
    {
        return 'sms';
    }

    public function label(): string
    {
        return __('atrium-2fa::two-factor.factors.sms');
    }

    public function input(): string
    {
        return 'code';
    }

    public function enrolled(Authenticatable $user, string $guard): bool
    {
        return $this->phone($user, $guard) !== null;
    }

    public function prepare(Authenticatable $user, string $guard): bool
    {
        return $this->send($guard, Codes::TWO_FACTOR, (string) $this->phone($user, $guard), $user->getAuthIdentifier());
    }

    public function verify(Authenticatable $user, string $guard, Request $request): bool
    {
        $phone = $this->phone($user, $guard);

        return $phone !== null
            && $this->codes->verify($guard, Codes::TWO_FACTOR, $phone, (string) $request->input('code', '')) === Codes::OK;
    }

    /**
     * Issue a code for a phone and text it.
     */
    public function send(string $guard, string $purpose, string $phone, int|string $userId): bool
    {
        $code = $this->codes->issue($guard, $purpose, $phone, $userId);

        if ($code === null) {
            return false;
        }

        $this->sms->send($phone, __('atrium-2fa::two-factor.sms', ['code' => $code, 'site' => Person::siteName()]));

        return true;
    }

    private function phone(Authenticatable $user, string $guard): ?string
    {
        return FactorModel::query()->of($guard, $user->getAuthIdentifier())->confirmed()->where('type', 'sms')->latest('id')->first()?->secret;
    }
}

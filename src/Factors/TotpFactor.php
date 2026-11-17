<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Factors;

use Atrium\TwoFactor\Contracts\Factor;
use Atrium\TwoFactor\Models\Factor as FactorModel;
use Atrium\TwoFactor\Support\Totp;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * An authenticator app (Google Authenticator, Microsoft Authenticator,
 * Aegis, 1Password…): six digits that change every 30 seconds.
 */
final class TotpFactor implements Factor
{
    public function key(): string
    {
        return 'totp';
    }

    public function label(): string
    {
        return __('atrium-2fa::two-factor.factors.totp');
    }

    public function input(): string
    {
        return 'code';
    }

    public function enrolled(Authenticatable $user, string $guard): bool
    {
        return FactorModel::query()->of($guard, $user->getAuthIdentifier())->confirmed()->where('type', 'totp')->exists();
    }

    public function prepare(Authenticatable $user, string $guard): bool
    {
        return true;
    }

    public function verify(Authenticatable $user, string $guard, Request $request): bool
    {
        $code = (string) $request->input('code', '');

        foreach (FactorModel::query()->of($guard, $user->getAuthIdentifier())->confirmed()->where('type', 'totp')->get() as $factor) {
            if (self::accept($factor, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check a code against one app and use its time step up. The step is
     * written only if it is still newer than the stored one, so two requests
     * with the same code cannot both pass.
     */
    private static function accept(FactorModel $factor, string $code): bool
    {
        $step = Totp::fromConfig()->verify((string) $factor->secret, $code, $factor->counter, (int) config('atrium-2fa.totp.window', 1));

        if ($step === null) {
            return false;
        }

        return FactorModel::query()->whereKey($factor->id)->where('counter', '<', $step)
            ->update(['counter' => $step, 'last_used_at' => Carbon::now()]) === 1;
    }
}

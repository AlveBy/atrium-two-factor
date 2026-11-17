<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Services;

use Atrium\TwoFactor\Models\RecoveryCode;
use Illuminate\Support\Carbon;
use Random\Randomizer;

/**
 * One-time codes for the day the phone is lost. Shown once, when made; the
 * table keeps their keyed hashes, so a code can be checked but never read
 * back. Making new ones voids the old.
 */
final class RecoveryCodes
{
    /**
     * Make a new set, voiding the old one.
     *
     * @return list<string> the codes, to be shown once
     */
    public function generate(string $guard, int|string $userId): array
    {
        RecoveryCode::query()->where('guard', $guard)->where('user_id', $userId)->delete();

        $codes = [];

        for ($i = 0; $i < config('atrium-2fa.recovery_codes', 10); $i++) {
            $code = self::make();
            $codes[] = $code;

            RecoveryCode::query()->create(['guard' => $guard, 'user_id' => $userId, 'code_hash' => self::hash($code)]);
        }

        return $codes;
    }

    /**
     * Use a code: true once, false ever after and for any code that is not one.
     */
    public function use(string $guard, int|string $userId, string $code): bool
    {
        $used = RecoveryCode::query()
            ->where('guard', $guard)
            ->where('user_id', $userId)
            ->where('code_hash', self::hash($code))
            ->whereNull('used_at')
            ->update(['used_at' => Carbon::now()]);

        return $used === 1;
    }

    public function remaining(string $guard, int|string $userId): int
    {
        return RecoveryCode::query()->where('guard', $guard)->where('user_id', $userId)->whereNull('used_at')->count();
    }

    public function forget(string $guard, int|string $userId): void
    {
        RecoveryCode::query()->where('guard', $guard)->where('user_id', $userId)->delete();
    }

    /**
     * "abcde-12345": ten characters from an alphabet without look-alikes.
     */
    private static function make(): string
    {
        $code = (new Randomizer)->getBytesFromString('abcdefghjkmnpqrstuvwxyz23456789', 10);

        return substr($code, 0, 5).'-'.substr($code, 5);
    }

    private static function hash(string $code): string
    {
        $normal = strtolower(preg_replace('/[^a-z0-9]/i', '', $code) ?? '');

        return hash_hmac('sha256', 'recovery|'.$normal, (string) config('app.key'));
    }
}

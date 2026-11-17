<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Services;

use Atrium\TwoFactor\Guards;
use Atrium\TwoFactor\Models\Code;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Random\Randomizer;

/**
 * Six-digit codes sent by e-mail or SMS: the second step, and signing in
 * without a password.
 *
 * Only a password hash of the code is stored and only a keyed hash of where
 * it went, so the table tells nobody the code or the address. A code lives a
 * few minutes, takes a few wrong tries and is gone after the right one.
 */
final class Codes
{
    public const TWO_FACTOR = 'two_factor';

    public const MAGIC = 'magic';

    public const SETUP = 'setup';

    public const OK = 'ok';

    public const WRONG = 'wrong';

    public const EXPIRED = 'expired';

    public const TOO_MANY = 'too_many';

    public function __construct(private readonly Guards $guards) {}

    /**
     * A new code for the target, or null when one went there a moment ago.
     * The caller delivers it.
     */
    public function issue(string $guard, string $purpose, string $target, int|string|null $userId = null): ?string
    {
        if ($this->retryAfter($guard, $purpose, $target) > 0) {
            return null;
        }

        $code = (new Randomizer)->getBytesFromString('0123456789', 6);

        $this->query($guard, $purpose, $target)->delete();

        Code::query()->create([
            'guard' => $guard,
            'purpose' => $purpose,
            'target_hash' => self::targetHash($target),
            'user_id' => $userId,
            'code' => Hash::make($code),
            'expires_at' => Carbon::now()->addMinutes($this->guards->codeTtl($guard)),
        ]);

        return $code;
    }

    /**
     * Seconds until another code may go to the target (0: now).
     */
    public function retryAfter(string $guard, string $purpose, string $target): int
    {
        $last = $this->query($guard, $purpose, $target)->latest('id')->first();

        if ($last === null) {
            return 0;
        }

        $passed = (int) $last->created_at->diffInSeconds(Carbon::now(), true);

        return max(0, $this->guards->resendAfter($guard) - $passed);
    }

    /**
     * Check a code typed in. The right one is used up; a wrong one counts
     * against the code, which is gone after too many.
     *
     * @return self::OK|self::WRONG|self::EXPIRED|self::TOO_MANY
     */
    public function verify(string $guard, string $purpose, string $target, string $code): string
    {
        $stored = $this->query($guard, $purpose, $target)->latest('id')->first();

        if ($stored === null || $stored->expires_at->isPast()) {
            return self::EXPIRED;
        }

        if ($stored->attempts >= config('atrium-2fa.codes.max_attempts', 5)) {
            $stored->delete();

            return self::TOO_MANY;
        }

        if (! Hash::check(preg_replace('/\D/', '', $code) ?? '', $stored->code)) {
            $stored->increment('attempts');

            return self::WRONG;
        }

        $stored->delete();

        return self::OK;
    }

    /**
     * An address or a phone as the table keeps it: lowercased, trimmed and
     * hashed with the application's key.
     */
    public static function targetHash(string $target): string
    {
        return hash_hmac('sha256', mb_strtolower(trim($target)), (string) config('app.key'));
    }

    /**
     * @return Builder<Code>
     */
    private function query(string $guard, string $purpose, string $target): Builder
    {
        return Code::query()->where('guard', $guard)->where('purpose', $purpose)->where('target_hash', self::targetHash($target));
    }
}

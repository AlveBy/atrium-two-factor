<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Services;

use Illuminate\Support\Facades\RateLimiter;

/**
 * Wrong answers at the second step. A few a minute from one address, then a
 * wait; many in a row from anywhere, then the person is locked out for a
 * while, so spreading guesses over many addresses does not help either.
 */
final class Lockout
{
    /**
     * Seconds to wait, or 0 when an answer may be checked now.
     */
    public function wait(string $guard, int|string $userId, ?string $ip): int
    {
        foreach ([$this->lockKey($guard, $userId) => $this->lockoutAfter(), $this->minuteKey($guard, $userId, $ip) => $this->perMinute()] as $key => $max) {
            if (RateLimiter::tooManyAttempts($key, $max)) {
                return max(1, RateLimiter::availableIn($key));
            }
        }

        return 0;
    }

    /**
     * Count a wrong answer.
     *
     * @return bool true when this one locked the person out
     */
    public function hit(string $guard, int|string $userId, ?string $ip): bool
    {
        RateLimiter::hit($this->minuteKey($guard, $userId, $ip), 60);
        RateLimiter::hit($this->lockKey($guard, $userId), $this->lockoutMinutes() * 60);

        return RateLimiter::attempts($this->lockKey($guard, $userId)) === $this->lockoutAfter();
    }

    public function clear(string $guard, int|string $userId, ?string $ip): void
    {
        RateLimiter::clear($this->minuteKey($guard, $userId, $ip));
        RateLimiter::clear($this->lockKey($guard, $userId));
    }

    private function perMinute(): int
    {
        return (int) config('atrium-2fa.limits.per_minute', 5);
    }

    private function lockoutAfter(): int
    {
        return (int) config('atrium-2fa.limits.lockout_after', 10);
    }

    private function lockoutMinutes(): int
    {
        return (int) config('atrium-2fa.limits.lockout_minutes', 15);
    }

    private function minuteKey(string $guard, int|string $userId, ?string $ip): string
    {
        return "atrium-2fa|{$guard}|{$userId}|{$ip}";
    }

    private function lockKey(string $guard, int|string $userId): string
    {
        return "atrium-2fa-lock|{$guard}|{$userId}";
    }
}

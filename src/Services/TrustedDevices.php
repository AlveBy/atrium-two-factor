<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Services;

use Atrium\TwoFactor\Guards;
use Atrium\TwoFactor\Models\TrustedDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie as HttpCookie;

/**
 * "Trust this device": a random token in a cookie, its hash in the table.
 * Until it expires the browser skips the second step for that one person;
 * revoked from the security page, or all at once by a reset.
 */
final class TrustedDevices
{
    public function __construct(private readonly Guards $guards) {}

    public function trust(Request $request, string $guard, int|string $userId): HttpCookie
    {
        $days = $this->guards->trustedDays($guard);
        $token = Str::random(64);

        TrustedDevice::query()->create([
            'guard' => $guard,
            'user_id' => $userId,
            'token_hash' => hash('sha256', $token),
            'name' => Str::limit((string) $request->userAgent(), 250, ''),
            'ip' => $request->ip(),
            'expires_at' => Carbon::now()->addDays($days),
        ]);

        return Cookie::make(self::cookie($guard, $userId), $token, $days * 24 * 60, secure: $request->isSecure(), httpOnly: true, sameSite: 'lax');
    }

    public function trusted(Request $request, string $guard, int|string $userId): bool
    {
        $token = $request->cookie(self::cookie($guard, $userId));

        if (! is_string($token) || $token === '' || $this->guards->trustedDays($guard) === 0) {
            return false;
        }

        $device = TrustedDevice::query()
            ->where('guard', $guard)
            ->where('user_id', $userId)
            ->where('token_hash', hash('sha256', $token))
            ->where('expires_at', '>', Carbon::now())
            ->first();

        $device?->forceFill(['last_used_at' => Carbon::now()])->save();

        return $device !== null;
    }

    public function revokeAll(string $guard, int|string $userId): int
    {
        return TrustedDevice::query()->where('guard', $guard)->where('user_id', $userId)->delete();
    }

    public static function cookie(string $guard, int|string $userId): string
    {
        return 'atrium_2fa_'.$guard.'_'.substr(hash('sha256', $guard.'|'.$userId), 0, 16);
    }
}

<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Support;

use Atrium\Admin\Support\Sites;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Str;

/**
 * What this package reads from a guard's user model: an id, an e-mail and a
 * name. Anything Authenticatable with an `email` attribute will do.
 */
final class Person
{
    public static function email(Authenticatable $user): string
    {
        return (string) data_get($user, 'email', '');
    }

    public static function name(Authenticatable $user): string
    {
        return (string) (data_get($user, 'name') ?: self::email($user));
    }

    /**
     * "jo****@example.com": shows where to look, but not whose mailbox it is.
     */
    public static function maskEmail(string $email): string
    {
        [$name, $domain] = explode('@', $email, 2) + [1 => ''];
        $visible = Str::substr($name, 0, min(2, Str::length($name)));

        return $visible.str_repeat('*', max(1, Str::length($name) - Str::length($visible))).($domain === '' ? '' : '@'.$domain);
    }

    /**
     * "+3*******67": the phone with all but its first and last two digits hidden.
     */
    public static function maskPhone(string $phone): string
    {
        return Str::substr($phone, 0, 2).str_repeat('*', max(1, Str::length($phone) - 4)).Str::substr($phone, -2);
    }

    public static function siteName(): string
    {
        return (string) (app(Sites::class)->current()->name ?: config('app.name'));
    }
}

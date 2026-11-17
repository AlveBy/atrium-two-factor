<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Support;

use Illuminate\Support\Str;

/**
 * How a person signed in, in words: "password", "Google", "authenticator app".
 */
final class Methods
{
    public static function label(string $method): string
    {
        if (str_starts_with($method, 'social:')) {
            return Str::headline(substr($method, 7));
        }

        $key = 'atrium-2fa::two-factor.methods.'.$method;
        $label = __($key);

        return is_string($label) && $label !== $key ? $label : $method;
    }
}

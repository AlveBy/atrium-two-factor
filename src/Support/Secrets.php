<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Support;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

/**
 * Secrets at rest: TOTP keys, phones for SMS, passkey data.
 *
 * Encrypted with the application's key (AES-256 with a MAC) and bound to
 * where they are kept: the context (table.column) is sealed inside, and a
 * value read from another place is refused. Unlike personal data, secrets are
 * encrypted always, whether or not the panel's data encryption is switched on.
 */
final class Secrets
{
    public static function seal(string $plain, string $context): string
    {
        return Crypt::encryptString($context."\0".$plain);
    }

    public static function reveal(string $sealed, string $context): string
    {
        $plain = Crypt::decryptString($sealed);

        if (! str_starts_with($plain, $context."\0")) {
            throw new DecryptException("The secret does not belong to {$context}.");
        }

        return substr($plain, strlen($context) + 1);
    }
}

<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);
use Atrium\TwoFactor\Sms\LogSmsDriver;

/*
 * The second sign-in step, for every guard that has people signing in.
 *
 * Each guard below is switched on by itself. What is set here is the
 * default: the panel (Modules → Two-factor sign-in) can change the mode, the
 * factors, the roles, the passwordless ways and the trusted days of each
 * guard without a deployment. docs/two-factor.md explains every key.
 */
return [
    'guards' => [
        // The administrators of the panel.
        'admin' => [
            'enabled' => true,
            // Where the pages of this package live for the guard, and what
            // wraps them.
            'prefix' => 'admin/auth',
            'middleware' => ['web', 'atrium.site', 'atrium.inertia'],
            // Registered for every content language (/de/account/auth), as the site's pages are.
            'localized' => false,
            'auth_middleware' => 'admin.auth',
            'layout' => 'atrium-2fa::layouts.admin',
            'login_route' => 'admin.login',
            // The column that says the account may sign in at all.
            'active_column' => 'is_active',
            // Who must pass a second factor:
            //   all      — everybody;
            //   roles    — superadmins and the roles in required_roles (role ids);
            //   optional — whoever has set up a factor themselves.
            // null keeps the old switch: atrium.auth.two_factor.enabled
            // (ATRIUM_2FA_ENABLED) true means "all", false means "optional".
            'required' => null,
            'required_roles' => [],
            // The factors people of this guard may use.
            'factors' => ['totp', 'passkey', 'email', 'sms'],
            // A passkey alone signs a person in (it is two factors in one).
            'passwordless' => false,
            // A six-digit code by e-mail signs a person in without a password.
            'magic_code' => false,
            // "Trust this device" skips the second step for this many days; 0 hides it.
            'trusted_days' => 30,
        ],

        // The people of the personal account (alveby/atrium-account).
        'account' => [
            'enabled' => true,
            'prefix' => 'account/auth',
            'middleware' => ['web'],
            'localized' => true,
            'auth_middleware' => 'account.auth',
            'layout' => 'atrium-2fa::layouts.site',
            'login_route' => 'atrium.account.login',
            'active_column' => 'active',
            'required' => 'optional',
            'required_roles' => [],
            'factors' => ['totp', 'passkey', 'email', 'sms'],
            'passwordless' => true,
            'magic_code' => true,
            'trusted_days' => 30,
        ],
    ],

    // Codes sent by e-mail or SMS, for the second step and for signing in
    // without a password.
    'codes' => [
        // Minutes a code lives. For the admin, atrium.auth.two_factor.ttl wins.
        'ttl' => 10,
        // Seconds before another code may be sent.
        'resend_after' => 60,
        // Wrong tries a code takes before it is gone.
        'max_attempts' => 5,
    ],

    // Wrong second-step answers: this many a minute, then a wait; this many
    // in a row, then the account is locked for lockout_minutes.
    'limits' => [
        'per_minute' => 5,
        'lockout_after' => 10,
        'lockout_minutes' => 15,
    ],

    // Authenticator apps (RFC 6238). The window is how many 30-second steps
    // before and after now still count, for a phone whose clock is off.
    'totp' => [
        'issuer' => env('ATRIUM_2FA_ISSUER'),
        'window' => 1,
        'digits' => 6,
        'period' => 30,
    ],

    // Passkeys (WebAuthn). The relying party id is the site's host by
    // default; set it to share passkeys between subdomains.
    'webauthn' => [
        'rp_id' => env('ATRIUM_WEBAUTHN_RP_ID'),
        'rp_name' => env('ATRIUM_WEBAUTHN_RP_NAME'),
        'timeout' => 60,
    ],

    'recovery_codes' => 10,

    // How SMS go out. A class implementing Atrium\TwoFactor\Contracts\SmsDriver;
    // the default writes the message to the log. docs/two-factor.md shows how
    // to plug in a provider.
    'sms' => [
        'driver' => LogSmsDriver::class,
    ],
];

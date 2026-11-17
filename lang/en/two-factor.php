<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

/*
 * The second sign-in step, signing in without a password, the security page
 * and the sign-in log.
 */
return [
    'module' => 'Two-factor sign-in',
    'back_to_login' => 'Back to signing in',
    'back_to_panel' => 'Back to the panel',

    'guards' => [
        'admin' => 'Admin panel',
        'account' => 'Personal account',
    ],

    'factors' => [
        'totp' => 'Authenticator app',
        'passkey' => 'Passkey',
        'email' => 'Code by email',
        'sms' => 'Code by SMS',
    ],

    'methods' => [
        'password' => 'Password',
        'code' => 'Code by email',
        'passkey' => 'Passkey',
        'register' => 'Registration',
        'totp' => 'Authenticator app',
        'email' => 'Code by email',
        'sms' => 'Code by SMS',
        'recovery' => 'Recovery code',
    ],

    'login' => [
        'passkey' => 'Sign in with a passkey',
        'code' => 'Get a code by email',
    ],

    'challenge' => [
        'title' => 'Confirm sign-in',
        'sent_to' => 'We sent a code to :destination',
        'totp_hint' => 'Open your authenticator app and enter the six-digit code it shows for this site.',
        'passkey_hint' => 'Use the passkey on this device or a security key: your fingerprint, face or PIN.',
        'use_passkey' => 'Use a passkey',
        'code_label' => [
            'totp' => 'Code from the app',
            'email' => 'Code from the email',
            'sms' => 'Code from the SMS',
        ],
        'submit' => 'Confirm',
        'resend' => 'Send the code again',
        'resend_in' => 'Send again in :seconds s',
        'resent' => 'The code has been sent again.',
        'resend_too_soon' => 'The code has already been sent. You can request a new one in a minute.',
        'send_failed' => 'Could not send the code: mail or SMS is unavailable. Try another way or contact the administrator.',
        'code_required' => 'Enter the code.',
        'code_invalid' => 'The code is wrong or has expired. Check it or request a new one.',
        'throttle' => 'Too many attempts. Try again in :seconds s.',
        'expired' => 'The sign-in has expired. Sign in again.',
        'trust' => 'Do not ask on this device for :days day|Do not ask on this device for :days days',
        'other_ways' => 'Other ways to confirm',
        'use_recovery' => 'Use a recovery code',
        'recovery_hint' => 'Enter one of the recovery codes you saved when you set up two-factor sign-in. Each code works once.',
        'recovery_code' => 'Recovery code',
        'recovery_invalid' => 'This recovery code is wrong or has already been used.',
        'recovery_left' => 'You signed in with a recovery code. Codes left: :count. Make new ones on the security page.',
        'back_to_factor' => 'Back',
        'cancel' => 'Sign in as another user',
    ],

    'magic' => [
        'title' => 'Sign in with a code',
        'hint' => 'Enter your email and we will send you a six-digit code. No password needed.',
        'email' => 'Email',
        'send' => 'Send the code',
        'sent' => 'If an account with this address exists, a code is on its way.',
        'check_mail' => 'If :email belongs to an account, we sent a code there. It works once and for a few minutes.',
        'other_email' => 'Use another email',
        'email_required' => 'Enter your email.',
        'email_invalid' => 'Enter a valid email.',
        'expired' => 'The code has expired. Request a new one.',
        'too_many' => 'Too many wrong codes. Request a new one.',
    ],

    'passkey' => [
        'title' => 'Sign in with a passkey',
        'hint' => 'Your device will ask for your fingerprint, face or PIN. No password needed.',
        'button' => 'Continue with a passkey',
        'failed' => 'This passkey did not work. Try again or sign in another way.',
        'unsupported' => 'This browser does not support passkeys.',
        'cancelled' => 'The passkey check was cancelled or timed out.',
    ],

    'security' => [
        'title' => 'Sign-in security',
        'required' => 'A second step is required for your account: keep at least one way to confirm signing in.',
        'optional' => 'Add a second step to protect your account even if someone learns your password.',
        'must_enrol' => 'Set up a second step before you continue: it is required for your account.',
        'factors' => 'Your ways to confirm',
        'none' => 'Nothing is set up yet.',
        'none_short' => 'none',
        'added_at' => 'added :date',
        'used_at' => 'last used :date',
        'remove' => 'Remove',
        'removed' => 'Removed.',
        'added' => 'Added. It will be asked for when you sign in.',
        'last_factor' => 'This is your last way to confirm sign-in, and one is required. Add another first.',
        'name' => 'Name (optional)',
        'name_totp' => 'Phone',
        'name_passkey' => 'Laptop',
        'confirm' => 'Confirm',
        'totp_hint' => 'Google Authenticator, Microsoft Authenticator, 1Password, Aegis or any other app that shows six-digit codes.',
        'totp_add' => 'Set up an app',
        'totp_another' => 'Add another app',
        'totp_manual' => 'Scan the QR code with the app, or type this key:',
        'qr' => 'QR code for the authenticator app',
        'passkey_hint' => 'A fingerprint, face or PIN on this device, or a security key, instead of typing a code.',
        'passkey_hint_passwordless' => 'A fingerprint, face or PIN on this device, or a security key. A passkey also signs you in without a password.',
        'passkey_add' => 'Add a passkey',
        'passkey_failed' => 'The passkey was not saved. Try again.',
        'phone' => 'Phone number with the country code',
        'phone_invalid' => 'Enter the phone number with the country code, for example +1 555 0100.',
        'sms_add' => 'Send a code',
        'sms_sent' => 'Code sent to :phone',
        'email_hint' => 'A code sent to :email each time you sign in.',
        'email_add' => 'Use codes by email',
        'recovery' => 'Recovery codes',
        'recovery_left' => 'One unused code left.|:count unused codes left.',
        'recovery_regenerate' => 'Make new codes',
        'recovery_new' => 'Your recovery codes',
        'recovery_once' => 'Save them somewhere safe now: they are shown only once. Each code lets you in once if you lose your phone or key. Old codes no longer work.',
        'devices' => 'Trusted devices',
        'devices_none' => 'No devices skip the second step.',
        'devices_revoke' => 'Forget all devices',
        'devices_revoked' => 'All devices will be asked for the second step again.',
        'until' => 'until :date',
        'events' => 'Recent activity',
    ],

    'events' => [
        'signed_in' => 'Signed in',
        'challenged' => 'Second step asked',
        'failed' => 'Wrong answer',
        'locked' => 'Locked for a while',
        'trusted_device' => 'Trusted device, step skipped',
        'code_sent' => 'Code sent',
        'factor_added' => 'Way to confirm added',
        'factor_removed' => 'Way to confirm removed',
        'recovery_used' => 'Recovery code used',
        'recovery_generated' => 'New recovery codes',
        'device_trusted' => 'Device trusted',
        'device_revoked' => 'Devices forgotten',
        'reset' => 'Reset by an administrator',
        'social_linked' => 'Social account linked',
        'social_unlinked' => 'Social account unlinked',
        'social_refused' => 'Social sign-in refused',
    ],

    'log' => [
        'when' => 'When',
        'who' => 'Who',
        'what' => 'Event',
        'how' => 'Method',
        'where' => 'IP address',
        'guard' => 'Where',
        'all' => 'All',
        'filter' => 'Show',
        'empty' => 'Nothing yet.',
        'by_admin' => 'administrator #:id',
        'pages' => 'Pages',
        'newer' => 'Newer',
        'older' => 'Older',
    ],

    'manage' => [
        'hint' => 'If someone lost their phone or key, reset their second step: they sign in with the password and set it up again.',
        'search' => 'Email',
        'email' => 'Email',
        'reset' => 'Reset',
        'confirm' => 'Remove every way to confirm sign-in, the recovery codes and the trusted devices of :email?',
        'reset_done' => 'Reset. The person will set up the second step again.',
    ],

    'menu' => [
        'title' => 'Sign-in',
        'security' => 'My sign-in security',
        'log' => 'Sign-in log',
        'people' => 'Two-factor resets',
    ],

    'settings' => [
        'description' => 'An empty field keeps the value from config/atrium-2fa.php.',
        'from_config' => 'Empty: as in the config file.',
        'required' => ':who: who must pass a second step',
        'modes' => [
            'all' => 'Everybody',
            'roles' => 'Superadmins and the chosen roles',
            'optional' => 'Only those who set it up',
        ],
        'roles' => ':who: roles that must pass it',
        'factors' => ':who: allowed ways to confirm',
        'passwordless' => ':who: sign in with a passkey alone',
        'magic_code' => ':who: sign in with a code by email',
        'trusted_days' => ':who: days a trusted device skips the step',
        'trusted_days_hint' => '0 turns "trust this device" off.',
        'yes' => 'Yes',
        'no' => 'No',
    ],

    'sms' => ':code is your sign-in code for :site. Do not share it.',

    'mail' => [
        'subject' => ':code is your sign-in code for :site',
        'title' => [
            'two_factor' => 'Sign-in confirmation code',
            'magic' => 'Sign-in code',
        ],
        'heading' => [
            'two_factor' => 'Confirming sign-in to :site',
            'magic' => 'Signing in to :site',
        ],
        'code_label' => 'Your code:',
        'valid_for' => 'The code is valid for :count minute.|The code is valid for :count minutes.',
        'not_you' => 'If you did not try to sign in, do not share this code with anyone. If it came as a second step, someone knows your password: change it.',
    ],
];

<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

use Atrium\TwoFactor\Http\Controllers\ChallengeController;
use Atrium\TwoFactor\Http\Controllers\MagicCodeController;
use Atrium\TwoFactor\Http\Controllers\ManageController;
use Atrium\TwoFactor\Http\Controllers\PasskeyLoginController;
use Atrium\TwoFactor\Http\Controllers\SecurityController;
use Atrium\TwoFactor\Http\Middleware\LockInDemo;
use Atrium\TwoFactor\Http\Middleware\ReloadInertiaVisits;
use Illuminate\Support\Facades\Route;

/*
 * Registered once per guard by TwoFactorServiceProvider, inside a group that
 * carries the guard's prefix, middleware and name. $authMiddleware and
 * $isAdmin come from there.
 *
 * @var string $authMiddleware
 * @var bool $isAdmin
 */

/*
 * Before anybody is signed in: plain HTML pages. The panel's sign-in form is
 * an Inertia page, and the redirect it gets after the password is an Inertia
 * visit, which expects a page object: such a visit is told to load the
 * address as a whole page instead.
 */
Route::middleware(ReloadInertiaVisits::class)->group(function (): void {
    // The second step: a person who passed the first factor, not signed in yet.
    Route::get('challenge', [ChallengeController::class, 'show'])->name('challenge');
    Route::post('challenge', [ChallengeController::class, 'verify'])->name('challenge.verify');
    Route::post('challenge/factor', [ChallengeController::class, 'switch'])->name('challenge.switch');
    Route::post('challenge/resend', [ChallengeController::class, 'resend'])->name('challenge.resend');
    Route::post('challenge/options', [ChallengeController::class, 'options'])->name('challenge.options');
    Route::post('challenge/cancel', [ChallengeController::class, 'cancel'])->name('challenge.cancel');

    // Signing in without a password: a code by e-mail, or a passkey alone.
    Route::get('code', [MagicCodeController::class, 'create'])->name('magic');
    Route::post('code', [MagicCodeController::class, 'store'])->name('magic.send');
    Route::get('code/enter', [MagicCodeController::class, 'edit'])->name('magic.code');
    Route::post('code/enter', [MagicCodeController::class, 'update'])->name('magic.verify');
    Route::get('passkey', [PasskeyLoginController::class, 'show'])->name('passkey');
    Route::post('passkey/options', [PasskeyLoginController::class, 'options'])->name('passkey.options');
    Route::post('passkey', [PasskeyLoginController::class, 'store'])->name('passkey.verify');
});

// The person's own security page. Whatever changes a factor, a recovery code
// or a trusted device is locked in a public demo (LockInDemo).
Route::middleware($authMiddleware)->group(function () use ($isAdmin): void {
    Route::get('security', [SecurityController::class, 'index'])->name('security');

    Route::middleware(LockInDemo::class)->group(function () use ($isAdmin): void {
        Route::post('security/totp', [SecurityController::class, 'startTotp'])->name('totp.start');
        Route::post('security/totp/confirm', [SecurityController::class, 'confirmTotp'])->name('totp.confirm');
        Route::post('security/passkeys/options', [SecurityController::class, 'passkeyOptions'])->name('passkeys.options');
        Route::post('security/passkeys', [SecurityController::class, 'storePasskey'])->name('passkeys.store');
        Route::post('security/sms', [SecurityController::class, 'startSms'])->name('sms.start');
        Route::post('security/sms/confirm', [SecurityController::class, 'confirmSms'])->name('sms.confirm');
        Route::post('security/email', [SecurityController::class, 'enableEmail'])->name('email.enable');
        Route::delete('security/factors/{factor}', [SecurityController::class, 'destroy'])->whereNumber('factor')->name('factors.destroy');
        Route::post('security/recovery', [SecurityController::class, 'regenerateRecovery'])->name('recovery.regenerate');
        Route::delete('security/devices', [SecurityController::class, 'revokeDevices'])->name('devices.revoke');

        if ($isAdmin) {
            Route::post('people/reset', [ManageController::class, 'reset'])->name('reset');
        }
    });

    // The sign-in log and resets, in the panel only.
    if ($isAdmin) {
        Route::get('log', [ManageController::class, 'log'])->name('log');
        Route::get('people', [ManageController::class, 'users'])->name('users');
    }
});

<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Every table names its guard and the person's id in it, so one set of
 * tables serves the administrators and the people of the account alike.
 */
return new class extends Migration
{
    public function up(): void
    {
        // The factors a person has set up: an authenticator app, a passkey,
        // a phone for SMS, the e-mail. The secret is encrypted.
        Schema::create('auth_factors', function (Blueprint $table): void {
            $table->id();
            $table->string('guard', 40);
            $table->unsignedBigInteger('user_id');
            $table->string('type', 20);
            $table->string('name')->nullable();
            $table->text('secret')->nullable();
            // A passkey's credential id, hashed: how a passwordless sign-in
            // finds its owner.
            $table->string('credential_hash', 64)->nullable()->unique();
            $table->text('data')->nullable();
            // TOTP: the last time step used, so a code cannot be replayed.
            $table->unsignedBigInteger('counter')->default(0);
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index(['guard', 'user_id']);
        });

        // One-time recovery codes, only their keyed hashes.
        Schema::create('auth_recovery_codes', function (Blueprint $table): void {
            $table->id();
            $table->string('guard', 40);
            $table->unsignedBigInteger('user_id');
            $table->string('code_hash', 64)->unique();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->index(['guard', 'user_id']);
        });

        // Browsers that may skip the second step until expires_at.
        Schema::create('auth_trusted_devices', function (Blueprint $table): void {
            $table->id();
            $table->string('guard', 40);
            $table->unsignedBigInteger('user_id');
            $table->string('token_hash', 64)->unique();
            $table->string('name')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index(['guard', 'user_id']);
        });

        // Six-digit codes by e-mail or SMS. The target (an address or a
        // phone) is kept as a keyed hash, the code as a password hash.
        Schema::create('auth_codes', function (Blueprint $table): void {
            $table->id();
            $table->string('guard', 40);
            $table->string('purpose', 20);
            $table->string('target_hash', 64);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('code');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['guard', 'purpose', 'target_hash']);
        });

        // The sign-in log: who signed in how, every refusal, every change of
        // a factor and who made it.
        Schema::create('auth_events', function (Blueprint $table): void {
            $table->id();
            $table->string('guard', 40);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('event', 40);
            $table->string('method', 40)->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('actor_guard', 40)->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['guard', 'user_id']);
            $table->index('event');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_events');
        Schema::dropIfExists('auth_codes');
        Schema::dropIfExists('auth_trusted_devices');
        Schema::dropIfExists('auth_recovery_codes');
        Schema::dropIfExists('auth_factors');
    }
};

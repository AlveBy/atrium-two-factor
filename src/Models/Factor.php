<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Models;

use Atrium\TwoFactor\Casts\Secret;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A factor a person has set up: an authenticator app (totp), a passkey, a
 * phone for SMS or the e-mail. Unconfirmed ones are a setup in progress.
 *
 * @property int $id
 * @property string $guard
 * @property int $user_id
 * @property string $type
 * @property string|null $name
 * @property string|null $secret
 * @property string|null $credential_hash
 * @property array<string,mixed>|null $data
 * @property int $counter
 * @property Carbon|null $confirmed_at
 * @property Carbon|null $last_used_at
 * @property Carbon $created_at
 */
final class Factor extends Model
{
    protected $table = 'auth_factors';

    protected $guarded = ['id'];

    protected $hidden = ['secret', 'data'];

    protected function casts(): array
    {
        return [
            'secret' => Secret::class,
            'data' => 'encrypted:array',
            'counter' => 'integer',
            'confirmed_at' => 'datetime',
            'last_used_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    protected function scopeOf(Builder $query, string $guard, int|string $userId): Builder
    {
        return $query->where('guard', $guard)->where('user_id', $userId);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    protected function scopeConfirmed(Builder $query): Builder
    {
        return $query->whereNotNull('confirmed_at');
    }
}

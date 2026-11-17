<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $guard
 * @property int $user_id
 * @property string $token_hash
 * @property string|null $name
 * @property string|null $ip
 * @property Carbon $expires_at
 * @property Carbon|null $last_used_at
 * @property Carbon $created_at
 */
final class TrustedDevice extends Model
{
    protected $table = 'auth_trusted_devices';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'last_used_at' => 'datetime'];
    }
}

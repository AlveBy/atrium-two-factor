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
 * @property string $purpose
 * @property string $target_hash
 * @property int|null $user_id
 * @property string $code
 * @property int $attempts
 * @property Carbon $expires_at
 * @property Carbon $created_at
 */
final class Code extends Model
{
    protected $table = 'auth_codes';

    protected $guarded = ['id'];

    protected $hidden = ['code'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'attempts' => 'integer'];
    }
}

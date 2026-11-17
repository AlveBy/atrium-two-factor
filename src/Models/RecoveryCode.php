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
 * @property string $code_hash
 * @property Carbon|null $used_at
 */
final class RecoveryCode extends Model
{
    protected $table = 'auth_recovery_codes';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['used_at' => 'datetime'];
    }
}

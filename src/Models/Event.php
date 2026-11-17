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
 * A line of the sign-in log.
 *
 * @property int $id
 * @property string $guard
 * @property int|null $user_id
 * @property string $event
 * @property string|null $method
 * @property string|null $ip
 * @property string|null $user_agent
 * @property string|null $actor_guard
 * @property int|null $actor_id
 * @property array<string,mixed>|null $meta
 * @property Carbon $created_at
 */
final class Event extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'auth_events';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['meta' => 'array', 'created_at' => 'datetime'];
    }
}

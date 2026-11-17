<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Tests\Support;

use Illuminate\Foundation\Auth\User;

/**
 * Somebody of a guard that is not the admin panel's.
 *
 * @property int $id
 * @property string $email
 * @property bool $active
 */
final class Person extends User
{
    protected $table = 'test_people';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'active' => 'boolean'];
    }
}

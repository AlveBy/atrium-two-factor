<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Casts;

use Atrium\TwoFactor\Support\Secrets;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * A column that holds a secret: encrypted on the way to the database, plain
 * in the model. The table and column are bound into the ciphertext, so a
 * value copied into another column does not decrypt.
 *
 * @implements CastsAttributes<string|null,string|null>
 */
final class Secret implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value === null ? null : Secrets::reveal((string) $value, $model->getTable().'.'.$key);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value === null ? null : Secrets::seal((string) $value, $model->getTable().'.'.$key);
    }
}

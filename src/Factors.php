<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor;

use Atrium\TwoFactor\Contracts\Factor;

/**
 * The second factors there are, by key. Swap or add one with extend().
 */
final class Factors
{
    /** @var array<string,Factor> */
    private array $factors = [];

    public function extend(Factor $factor): void
    {
        $this->factors[$factor->key()] = $factor;
    }

    public function get(string $key): ?Factor
    {
        return $this->factors[$key] ?? null;
    }
}

<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Support;

use Atrium\TwoFactor\Guards;

/**
 * The URLs of this package for one guard, for the views: $routes->url('challenge').
 */
final class GuardRoutes
{
    public function __construct(
        private readonly Guards $guards,
        public readonly string $guard,
    ) {}

    /**
     * @param  array<string,mixed>  $parameters
     */
    public function url(string $name, array $parameters = []): string
    {
        return $this->guards->route($this->guard, $name, $parameters);
    }

    public function login(): string
    {
        return $this->guards->loginUrl($this->guard);
    }
}

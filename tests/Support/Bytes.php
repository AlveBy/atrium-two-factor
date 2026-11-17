<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Tests\Support;

/**
 * A CBOR byte string, as opposed to a text string.
 */
final class Bytes
{
    public function __construct(public readonly string $value) {}
}

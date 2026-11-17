<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Contracts;

/**
 * Sends a text message. Name your class in atrium-2fa.sms.driver (or bind
 * this interface in a service provider); docs/two-factor.md has an example.
 * Throw when the provider refuses: the person is told the SMS could not go.
 */
interface SmsDriver
{
    public function send(string $phone, string $message): void;
}

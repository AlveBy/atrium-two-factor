<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Sms;

use Atrium\TwoFactor\Contracts\SmsDriver;
use Illuminate\Support\Facades\Log;

/**
 * The SMS goes to the log: for development, and until a provider is set.
 * The number is shown with all but its last digits hidden.
 */
final class LogSmsDriver implements SmsDriver
{
    public function send(string $phone, string $message): void
    {
        Log::info('SMS to '.str_repeat('*', max(0, strlen($phone) - 2)).substr($phone, -2).': '.$message);
    }
}

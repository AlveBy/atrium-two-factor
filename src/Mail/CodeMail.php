<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * The e-mail with a sign-in code: the second step, or signing in without a
 * password. The code goes into the subject: it is visible in the inbox list
 * without opening the message, and it is typed by hand anyway.
 */
final class CodeMail extends Mailable
{
    use Queueable;

    public function __construct(
        public string $code,
        public int $ttlMinutes,
        public string $purpose,
        public string $siteName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('atrium-2fa::two-factor.mail.subject', ['code' => $this->code, 'site' => $this->siteName]));
    }

    public function content(): Content
    {
        return new Content(view: 'atrium-2fa::mail.code');
    }
}

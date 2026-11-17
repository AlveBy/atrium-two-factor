<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Support;

/**
 * Line icons for the sign-in buttons, drawn in currentColor so they take the
 * text colour of the theme. They are decoration: every button has its words.
 */
final class Icons
{
    public const PASSKEY = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="9" cy="7" r="4"/><path d="M2 21v-1a6 6 0 0 1 9.5-4.9"/><circle cx="17.5" cy="14.5" r="2.5"/><path d="M17.5 17v4.5l1.5-1.2M17.5 19.5h1.5"/></svg>';

    public const MAIL = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>';
}

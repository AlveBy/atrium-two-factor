<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * In a public demo (docs/demo.md) everybody signs in as the same person: a
 * second factor added, removed or reset there, recovery codes made anew or a
 * device trusted would lock the next visitor out. Every route that changes
 * any of it answers 403 while the demo is on.
 */
final class LockInDemo
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_if((bool) config('atrium.demo.enabled'), 403, __('atrium-2fa::two-factor.security.demo_locked'));

        return $next($request);
    }
}

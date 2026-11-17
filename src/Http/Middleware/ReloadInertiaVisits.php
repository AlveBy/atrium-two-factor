<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * The pages of this package are plain HTML. The panel's menu opens links as
 * Inertia visits, which expect a page object: such a visit is told to load
 * the address as a whole page instead.
 */
final class ReloadInertiaVisits
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') && $request->header('X-Inertia')) {
            return Inertia::location($request->fullUrl());
        }

        return $next($request);
    }
}

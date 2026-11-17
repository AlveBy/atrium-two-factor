<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Http\Middleware;

use Atrium\TwoFactor\Guards;
use Atrium\TwoFactor\Services\Challenge;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * A person who must pass a second step but has no factor yet was let in
 * once: until they set one up, every page sends them to the security page.
 * Signing out and this package's own pages stay open.
 */
final class EnforceEnrolment
{
    public function __construct(
        private readonly Guards $guards,
        private readonly Challenge $challenge,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        foreach ($this->guards->names() as $guard) {
            if (! $request->session()->get($this->challenge->enrolKey($guard))) {
                continue;
            }

            if (! Auth::guard($guard)->check()) {
                $request->session()->forget($this->challenge->enrolKey($guard));

                continue;
            }

            if ($this->open($request, $guard)) {
                continue;
            }

            $url = $this->guards->route($guard, 'security');

            return $request->header('X-Inertia')
                ? Inertia::location($url)
                : redirect()->to($url);
        }

        return $next($request);
    }

    private function open(Request $request, string $guard): bool
    {
        $name = (string) $request->route()?->getName();
        $own = $this->guards->routeName($guard, '');

        return str_starts_with($name, $own)
            || str_starts_with($name, 'localized.'.$own)
            || str_ends_with($name, 'logout')
            || ! $request->isMethod('GET');
    }
}

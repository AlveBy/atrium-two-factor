<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Http\Controllers;

use Atrium\TwoFactor\Guards;
use Atrium\TwoFactor\Support\GuardRoutes;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * The routes of this package are registered once per guard, with the guard
 * as a route default: every controller reads it from there.
 */
abstract class Controller
{
    public function __construct(protected readonly Guards $guards) {}

    protected function guard(Request $request): string
    {
        return (string) $request->route('guard');
    }

    protected function user(Request $request): Authenticatable
    {
        $user = Auth::guard($this->guard($request))->user();
        abort_if($user === null, 403);

        return $user;
    }

    /**
     * A page of this package inside the guard's layout.
     *
     * @param  array<string,mixed>  $data
     */
    protected function view(Request $request, string $view, array $data = []): View
    {
        $guard = $this->guard($request);

        return view("atrium-2fa::{$view}", $data + [
            'guard' => $guard,
            'layout' => $this->guards->config($guard, 'layout', 'atrium-2fa::layouts.site'),
            'routes' => new GuardRoutes($this->guards, $guard),
        ]);
    }
}

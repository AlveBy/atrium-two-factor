<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Http\Controllers;

use Atrium\TwoFactor\Guards;
use Atrium\TwoFactor\Models\Event;
use Atrium\TwoFactor\Support\GuardRoutes;
use Atrium\TwoFactor\Support\Methods;
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

    /**
     * The panel's own guard: its pages are Inertia pages in the panel's
     * layout, the other guards' are Blade pages in the site's.
     */
    protected function inPanel(Request $request): bool
    {
        return $this->guard($request) === $this->guards->adminGuard();
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

    /**
     * The sign-in log as the panel's tables show it: every cell already
     * worded in the panel's language. $people is the e-mails by "guard:id";
     * null leaves the "who" column out.
     *
     * @param  iterable<Event>  $events
     * @param  array<string,string>|null  $people
     * @return list<array{id:int,when_iso:string,when:string,who:string|null,what:string,by:string|null,how:string,ip:string|null}>
     */
    protected function eventRows(iterable $events, ?array $people): array
    {
        $rows = [];

        foreach ($events as $event) {
            $rows[] = [
                'id' => $event->id,
                'when_iso' => $event->created_at->toIso8601String(),
                'when' => $event->created_at->isoFormat('lll'),
                'who' => $people === null ? null : __('atrium-2fa::two-factor.guards.'.$event->guard).': '.($people[$event->guard.':'.$event->user_id] ?? ($event->user_id ? '#'.$event->user_id : '—')),
                'what' => __('atrium-2fa::two-factor.events.'.$event->event),
                'by' => $event->actor_id ? __('atrium-2fa::two-factor.log.by_admin', ['id' => $event->actor_id]) : null,
                'how' => $event->method ? Methods::label($event->method) : '—',
                'ip' => $event->ip,
            ];
        }

        return $rows;
    }
}

<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Http\Controllers;

use Atrium\Admin\Auth\Permissions;
use Atrium\Admin\Models\AdminUser;
use Atrium\TwoFactor\Guards;
use Atrium\TwoFactor\Models\Event;
use Atrium\TwoFactor\Models\Factor;
use Atrium\TwoFactor\Models\TrustedDevice;
use Atrium\TwoFactor\Services\Audit;
use Atrium\TwoFactor\Services\RecoveryCodes;
use Atrium\TwoFactor\Support\Person;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * For whoever may set up the two-factor module: the sign-in log of every
 * guard, and a reset for a person who lost their phone.
 */
final class ManageController extends Controller
{
    public function __construct(
        Guards $guards,
        private readonly Permissions $permissions,
        private readonly RecoveryCodes $recovery,
        private readonly Audit $audit,
    ) {
        parent::__construct($guards);
    }

    public function log(Request $request): Response
    {
        $this->authorize($request);

        $filters = $request->validate([
            'guard' => ['nullable', 'string', 'max:40'],
            'event' => ['nullable', 'string', 'max:40'],
            'user' => ['nullable', 'integer'],
        ]);

        $events = Event::query()
            ->when($filters['guard'] ?? null, fn ($q, $v) => $q->where('guard', $v))
            ->when($filters['event'] ?? null, fn ($q, $v) => $q->where('event', $v))
            ->when($filters['user'] ?? null, fn ($q, $v) => $q->where('user_id', $v))
            ->latest('id')
            ->paginate(50)
            ->withQueryString();

        $people = $this->people($events->getCollection()->map(fn (Event $e): array => [$e->guard, $e->user_id])->all());

        return Inertia::render('Admin/TwoFactor/Log', [
            't' => trans('atrium-2fa::two-factor'),
            'url' => $request->url(),
            'events' => $this->eventRows($events->getCollection(), $people),
            'newer' => $events->previousPageUrl(),
            'older' => $events->nextPageUrl(),
            'filters' => ['guard' => $filters['guard'] ?? '', 'event' => $filters['event'] ?? ''],
            'guards' => $this->guards->names(),
            'eventNames' => [
                Audit::SIGNED_IN, Audit::CHALLENGED, Audit::FAILED, Audit::LOCKED, Audit::TRUSTED_DEVICE,
                Audit::CODE_SENT, Audit::FACTOR_ADDED, Audit::FACTOR_REMOVED, Audit::RECOVERY_USED,
                Audit::RECOVERY_GENERATED, Audit::DEVICE_TRUSTED, Audit::DEVICE_REVOKED, Audit::RESET,
            ],
        ]);
    }

    public function users(Request $request): Response
    {
        $this->authorize($request);

        $guard = $request->query('guard', $this->guards->adminGuard());
        abort_unless($this->guards->has($guard), 404);

        $search = trim((string) $request->query('q', ''));
        $provider = Auth::createUserProvider(config("auth.guards.{$guard}.provider"));
        abort_if(! $provider instanceof EloquentUserProvider, 404);

        $people = $provider->createModel()->newQuery()
            ->when($search !== '', fn ($q) => $q->where('email', 'like', '%'.addcslashes($search, '%_\\').'%'))
            ->orderBy('id')
            ->paginate(30)
            ->withQueryString();

        $factors = Factor::query()->where('guard', $guard)->whereIn('user_id', $people->getCollection()->modelKeys())->confirmed()
            ->get()->groupBy('user_id');

        return Inertia::render('Admin/TwoFactor/People', [
            't' => trans('atrium-2fa::two-factor'),
            'url' => $request->url(),
            'status' => $request->session()->get('atrium-2fa.status'),
            'people' => $people->getCollection()->map(fn (Model $person): array => [
                'id' => $person->getKey(),
                'email' => $person->getAttribute('email'),
                'factors' => $factors->get($person->getKey(), collect())->map(fn (Factor $f): string => __('atrium-2fa::two-factor.factors.'.$f->type))->values()->all(),
            ])->all(),
            'newer' => $people->previousPageUrl(),
            'older' => $people->nextPageUrl(),
            'selected' => $guard,
            'guards' => $this->guards->names(),
            'search' => $search,
            'resetUrl' => route('admin.two-factor.reset'),
        ]);
    }

    /**
     * Takes away every factor, recovery code and trusted device of a person.
     * They sign in with the password again and, where it is required, set a
     * factor up anew.
     */
    public function reset(Request $request): RedirectResponse
    {
        $admin = $this->authorize($request);
        $data = $request->validate(['guard' => ['required', 'string'], 'user' => ['required', 'integer']]);
        $guard = $data['guard'];
        abort_unless($this->guards->has($guard), 404);

        $id = $data['user'];
        $removed = Factor::query()->of($guard, $id)->delete();
        $this->recovery->forget($guard, $id);
        TrustedDevice::query()->where('guard', $guard)->where('user_id', $id)->delete();

        $this->audit->record($guard, $id, Audit::RESET, null, ['factors' => $removed], $admin, $this->guards->adminGuard());

        return redirect()->back()->with('atrium-2fa.status', __('atrium-2fa::two-factor.manage.reset_done'));
    }

    private function authorize(Request $request): AdminUser
    {
        $admin = $this->user($request);
        abort_unless($admin instanceof AdminUser && $this->permissions->canSetUp($admin, 'two_factor'), 403, __('atrium::permissions.forbidden'));

        return $admin;
    }

    /**
     * E-mails of the people in the log, looked up once per guard.
     *
     * @param  list<array{0:string,1:int|null}>  $pairs
     * @return array<string,string> "guard:id" => e-mail
     */
    private function people(array $pairs): array
    {
        $byGuard = [];

        foreach ($pairs as [$guard, $id]) {
            if ($id !== null) {
                $byGuard[$guard][] = $id;
            }
        }

        $emails = [];

        foreach ($byGuard as $guard => $ids) {
            $provider = config("auth.guards.{$guard}") !== null ? Auth::createUserProvider(config("auth.guards.{$guard}.provider")) : null;

            if (! $provider instanceof EloquentUserProvider) {
                continue;
            }

            /** @var Authenticatable&Model $person */
            foreach ($provider->createModel()->newQuery()->whereKey($ids)->get() as $person) {
                $emails[$guard.':'.$person->getKey()] = Person::email($person);
            }
        }

        return $emails;
    }
}

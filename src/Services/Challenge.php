<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Services;

use Atrium\TwoFactor\Contracts\Factor;
use Atrium\TwoFactor\Guards;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * The second step, between the first factor and the session.
 *
 * Registered with SignIn::challengeUsing(): when a person who has to pass
 * a second factor comes through any first factor, they are remembered in the
 * session as pending (nobody is signed in yet) and sent to the page that asks
 * for it. A passkey is already two factors; a person who has only just
 * registered has nothing set up; both go straight in.
 */
final class Challenge
{
    /** First factors that need no second one. */
    public const SKIP = ['passkey', 'register', 'two_factor'];

    public function __construct(
        private readonly Guards $guards,
        private readonly Policy $policy,
        private readonly TrustedDevices $devices,
        private readonly Audit $audit,
    ) {}

    public function __invoke(Request $request, Authenticatable $user, string $guard, string $method, bool $remember): ?Response
    {
        if (! $this->guards->has($guard) || in_array($method, self::SKIP, true)) {
            return null;
        }

        $factors = $this->policy->factors($guard, $user, $this->usedBy($method));

        if ($factors === []) {
            // Nothing to ask for. Whoever has to pass a second step anyway is
            // let in and sent to set a factor up first.
            if ($this->policy->mustEnrol($guard, $user)) {
                $request->session()->put($this->enrolKey($guard), true);
            }

            return null;
        }

        if ($this->devices->trusted($request, $guard, $user->getAuthIdentifier())) {
            $this->audit->record($guard, $user, Audit::TRUSTED_DEVICE, $method);

            return null;
        }

        $request->session()->put($this->key($guard), [
            'id' => $user->getAuthIdentifier(),
            'remember' => $remember,
            'method' => $method,
            'factor' => $factors[0]->key(),
            'except' => $this->usedBy($method),
        ]);

        $this->prepare($request, $guard, $user, $factors[0]);
        $this->audit->record($guard, $user, Audit::CHALLENGED, $method, ['factor' => $factors[0]->key()]);

        return redirect()->to($this->guards->route($guard, 'challenge'));
    }

    /**
     * The person waiting at the second step, and how they came.
     *
     * @return array{id:int|string,remember:bool,method:string,factor:string,except:list<string>}|null
     */
    public function pending(Request $request, string $guard): ?array
    {
        $pending = $request->session()->get($this->key($guard));

        return is_array($pending) && isset($pending['id'], $pending['factor']) ? $pending : null;
    }

    public function user(Request $request, string $guard): ?Authenticatable
    {
        return $this->guards->find($guard, $this->pending($request, $guard)['id'] ?? null);
    }

    /**
     * Switches the pending sign-in to another of the person's factors.
     */
    public function use(Request $request, string $guard, string $factor): void
    {
        $request->session()->put($this->key($guard).'.factor', $factor);
    }

    public function forget(Request $request, string $guard): void
    {
        $request->session()->forget($this->key($guard));
    }

    /**
     * Sends the code of a factor that sends one. A mail server that is down
     * is reported to the person instead of a server error.
     */
    public function prepare(Request $request, string $guard, Authenticatable $user, Factor $factor): bool
    {
        try {
            $sent = $factor->prepare($user, $guard);
        } catch (Throwable $e) {
            Log::error('Could not send a sign-in code', ['guard' => $guard, 'factor' => $factor->key(), 'exception' => $e]);
            $request->session()->flash('atrium-2fa.error', __('atrium-2fa::two-factor.challenge.send_failed'));

            return false;
        }

        if ($sent && $factor->input() === 'code' && $factor->key() !== 'totp') {
            $this->audit->record($guard, $user, Audit::CODE_SENT, $factor->key());
        }

        return $sent;
    }

    /**
     * The factors the pending sign-in may switch between.
     *
     * @return list<Factor>
     */
    public function factors(Request $request, string $guard, Authenticatable $user): array
    {
        return $this->policy->factors($guard, $user, $this->pending($request, $guard)['except'] ?? []);
    }

    public function current(Request $request, string $guard, Authenticatable $user): ?Factor
    {
        $key = $this->pending($request, $guard)['factor'] ?? null;

        foreach ($this->factors($request, $guard, $user) as $factor) {
            if ($factor->key() === $key) {
                return $factor;
            }
        }

        return null;
    }

    /**
     * Has to set a factor up before using the site (see EnforceEnrolment).
     */
    public function enrolKey(string $guard): string
    {
        return "atrium-2fa.enrol.{$guard}";
    }

    private function key(string $guard): string
    {
        return "atrium-2fa.pending.{$guard}";
    }

    /**
     * A code by e-mail as the first factor has already proved the mailbox:
     * the second step asks for something else.
     *
     * @return list<string>
     */
    private function usedBy(string $method): array
    {
        return $method === 'code' ? ['email'] : [];
    }
}

<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Services;

use Atrium\Admin\Facades\Hook;
use Atrium\TwoFactor\Contracts\Factor;
use Atrium\TwoFactor\Factors;
use Atrium\TwoFactor\Guards;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Who has to pass a second step and with what.
 *
 * Required: everybody (all), superadmins and the listed roles (roles), or
 * nobody but those who set a factor up themselves (optional). Other modules
 * may decide too, with the filter `atrium.two_factor.required`
 * (bool $required, Authenticatable $user, string $guard): the account module
 * uses it for the mode of an account type.
 *
 * The factors offered are the ones the person set up and the guard allows.
 * The e-mailed code needs no setup: it is offered to whoever must pass a
 * second step and has nothing else, which is how the admin panel always
 * worked.
 */
final class Policy
{
    public function __construct(
        private readonly Guards $guards,
        private readonly Factors $factors,
    ) {}

    public function required(string $guard, Authenticatable $user): bool
    {
        $required = match ($this->guards->required($guard)) {
            Guards::ALL => true,
            Guards::ROLES => $this->hasRequiredRole($guard, $user),
            default => false,
        };

        return (bool) Hook::apply('atrium.two_factor.required', $required, $user, $guard);
    }

    /**
     * The factors the person has set up, in the order of preference.
     *
     * @return list<Factor>
     */
    public function enrolled(string $guard, Authenticatable $user): array
    {
        return array_values(array_filter(
            $this->allowed($guard),
            fn (Factor $factor): bool => $factor->enrolled($user, $guard),
        ));
    }

    /**
     * What the second step may ask for. Empty: no second step.
     *
     * @param  list<string>  $except  factors the first step has already used (a code by e-mail)
     * @return list<Factor>
     */
    public function factors(string $guard, Authenticatable $user, array $except = []): array
    {
        $enrolled = array_values(array_filter(
            $this->enrolled($guard, $user),
            fn (Factor $factor): bool => ! in_array($factor->key(), $except, true),
        ));

        if ($enrolled !== [] || ! $this->required($guard, $user)) {
            return $enrolled;
        }

        $email = $this->factors->get('email');

        return $email !== null && $this->guards->allows($guard, 'email') && ! in_array('email', $except, true)
            ? [$email]
            : [];
    }

    /**
     * Must pass a second step but has nothing that can ask for one: the
     * person is let in and sent to set a factor up first.
     */
    public function mustEnrol(string $guard, Authenticatable $user): bool
    {
        return $this->required($guard, $user) && $this->factors($guard, $user) === [];
    }

    /**
     * @return list<Factor> the factors the guard allows, registered ones only
     */
    public function allowed(string $guard): array
    {
        $allowed = [];

        foreach ($this->guards->factors($guard) as $key) {
            $factor = $this->factors->get($key);

            if ($factor !== null) {
                $allowed[] = $factor;
            }
        }

        return $allowed;
    }

    private function hasRequiredRole(string $guard, Authenticatable $user): bool
    {
        if (method_exists($user, 'isSuperadmin') && $user->isSuperadmin()) {
            return true;
        }

        $roles = $this->guards->requiredRoles($guard);

        if ($roles === [] || ! method_exists($user, 'roles')) {
            return false;
        }

        $relation = $user->roles();

        return $relation->whereIn($relation->getRelated()->getQualifiedKeyName(), $roles)->exists();
    }
}

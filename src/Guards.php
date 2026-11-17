<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor;

use Atrium\Admin\Facades\Setting;
use Atrium\Admin\Support\ContentLanguages;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;

/**
 * The guards this package serves and how each is set up: the config of
 * atrium-2fa.guards, overridden by what the panel saved (Modules → Two-factor
 * sign-in). A guard that Laravel does not know (the account module is not
 * installed) is skipped.
 */
final class Guards
{
    public const ALL = 'all';

    public const ROLES = 'roles';

    public const OPTIONAL = 'optional';

    /**
     * @return list<string>
     */
    public function names(): array
    {
        $names = [];

        foreach ((array) config('atrium-2fa.guards', []) as $name => $guard) {
            if (($guard['enabled'] ?? false) && config("auth.guards.{$name}") !== null) {
                $names[] = (string) $name;
            }
        }

        return $names;
    }

    public function has(string $guard): bool
    {
        return in_array($guard, $this->names(), true);
    }

    /**
     * A key of the guard's config, as written in config/atrium-2fa.php.
     */
    public function config(string $guard, string $key, mixed $default = null): mixed
    {
        return config("atrium-2fa.guards.{$guard}.{$key}", $default);
    }

    /**
     * Who must pass a second factor: all, roles or optional.
     */
    public function required(string $guard): string
    {
        $mode = $this->setting($guard, 'required') ?? $this->config($guard, 'required');

        if ($mode === null && $guard === $this->adminGuard()) {
            // The switch of the old e-mailed code keeps its meaning.
            $mode = config('atrium.auth.two_factor.enabled', true) ? self::ALL : self::OPTIONAL;
        }

        return in_array($mode, [self::ALL, self::ROLES], true) ? $mode : self::OPTIONAL;
    }

    /**
     * @return list<string> the ids of the roles that must pass it in the "roles" mode
     */
    public function requiredRoles(string $guard): array
    {
        return array_values(array_map('strval', (array) ($this->setting($guard, 'roles') ?? $this->config($guard, 'required_roles', []))));
    }

    /**
     * @return list<string> the factors people of the guard may use
     */
    public function factors(string $guard): array
    {
        $factors = array_values(array_map('strval', (array) ($this->setting($guard, 'factors') ?? $this->config($guard, 'factors', []))));

        if ($guard === $this->adminGuard() && ! config('atrium.auth.two_factor.enabled', true)) {
            // Browser tests switch the e-mailed code off: there is no mail.
            $factors = array_values(array_diff($factors, ['email']));
        }

        return $factors;
    }

    public function allows(string $guard, string $factor): bool
    {
        return in_array($factor, $this->factors($guard), true);
    }

    public function passwordless(string $guard): bool
    {
        return $this->allows($guard, 'passkey') && (bool) ($this->setting($guard, 'passwordless') ?? $this->config($guard, 'passwordless', false));
    }

    public function magicCode(string $guard): bool
    {
        return (bool) ($this->setting($guard, 'magic_code') ?? $this->config($guard, 'magic_code', false));
    }

    public function trustedDays(string $guard): int
    {
        return max(0, (int) ($this->setting($guard, 'trusted_days') ?? $this->config($guard, 'trusted_days')));
    }

    /**
     * Minutes a code lives: the admin keeps atrium.auth.two_factor.ttl.
     */
    public function codeTtl(string $guard): int
    {
        return (int) ($guard === $this->adminGuard()
            ? config('atrium.auth.two_factor.ttl', config('atrium-2fa.codes.ttl', 10))
            : config('atrium-2fa.codes.ttl', 10));
    }

    public function resendAfter(string $guard): int
    {
        return (int) ($guard === $this->adminGuard()
            ? config('atrium.auth.two_factor.resend_after', config('atrium-2fa.codes.resend_after', 60))
            : config('atrium-2fa.codes.resend_after', 60));
    }

    /**
     * The name of one of this package's routes for the guard.
     */
    public function routeName(string $guard, string $name): string
    {
        return ($guard === $this->adminGuard() ? 'admin.two-factor.' : "atrium-2fa.{$guard}.").$name;
    }

    /**
     * @param  array<string,mixed>  $parameters
     */
    public function route(string $guard, string $name, array $parameters = []): string
    {
        return $this->url($guard, $this->routeName($guard, $name), $parameters);
    }

    public function loginUrl(string $guard): string
    {
        return $this->url($guard, (string) $this->config($guard, 'login_route'));
    }

    /**
     * A route of a guard whose pages live on the multilingual site is built
     * in the language the visitor reads.
     *
     * @param  array<string,mixed>  $parameters
     */
    public function url(string $guard, string $name, array $parameters = []): string
    {
        return $this->config($guard, 'localized')
            ? app(ContentLanguages::class)->route($name, $parameters)
            : route($name, $parameters);
    }

    /**
     * A person of the guard by id, or null when there is none or they may
     * not sign in (deactivated, blocked).
     */
    public function find(string $guard, int|string|null $id): ?Authenticatable
    {
        $user = $id === null ? null : Auth::createUserProvider(config("auth.guards.{$guard}.provider"))?->retrieveById($id);

        return $user !== null && $this->active($guard, $user) ? $user : null;
    }

    /**
     * A person of the guard by e-mail, active ones only.
     */
    public function findByEmail(string $guard, string $email): ?Authenticatable
    {
        $user = Auth::createUserProvider(config("auth.guards.{$guard}.provider"))
            ?->retrieveByCredentials(['email' => mb_strtolower(trim($email))]);

        return $user !== null && $this->active($guard, $user) ? $user : null;
    }

    public function active(string $guard, Authenticatable $user): bool
    {
        $column = $this->config($guard, 'active_column');

        return $column === null || data_get($user, (string) $column);
    }

    public function adminGuard(): string
    {
        return (string) config('atrium.auth.guard', 'admin');
    }

    private function setting(string $guard, string $key): mixed
    {
        $value = Setting::get("two_factor.{$guard}_{$key}");

        return $value === '' || $value === [] ? null : $value;
    }
}

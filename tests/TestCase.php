<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Tests;

use Atrium\Admin\AtriumServiceProvider;
use Atrium\Admin\Auth\SignIn;
use Atrium\Admin\Models\AdminUser;
use Atrium\TwoFactor\Models\Factor;
use Atrium\TwoFactor\Support\Totp;
use Atrium\TwoFactor\Tests\Support\Person;
use Atrium\TwoFactor\TwoFactorServiceProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Testing\TestResponse;
use Inertia\ServiceProvider as InertiaServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Tighten\Ziggy\ZiggyServiceProvider;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    /** A TOTP key whose codes the tests compute themselves. */
    protected const SECRET = 'JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP';

    protected function getPackageProviders($app): array
    {
        return [
            InertiaServiceProvider::class,
            ZiggyServiceProvider::class,
            AtriumServiceProvider::class,
            TwoFactorServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $config = $app['config'];
        $config->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $config->set('app.name', 'Testing site');
        $target = env('DB_CONNECTION');
        $config->set('database.default', in_array($target, ['mysql', 'pgsql'], true) ? $target : 'testing');
        $config->set('atrium.demo', false);
        $config->set('atrium.modules', ['alveby/atrium-2fa']);
        $config->set('atrium.auth.two_factor.enabled', true);
        $config->set('atrium.dashboard.timings.enabled', false);
        $config->set('mail.default', 'array');

        // A second guard, like the personal account's, for the tests that
        // check the package is not tied to the admin panel.
        $config->set('auth.guards.people', ['driver' => 'session', 'provider' => 'people']);
        $config->set('auth.providers.people', ['driver' => 'eloquent', 'model' => Person::class]);
        $config->set('atrium-2fa.guards.people', [
            'enabled' => true,
            'prefix' => 'people/auth',
            'middleware' => ['web'],
            'auth_middleware' => 'auth:people',
            'layout' => 'atrium-2fa::layouts.admin',
            'login_route' => 'people.login',
            'active_column' => 'active',
            'required' => 'optional',
            'factors' => ['totp', 'passkey', 'email', 'sms'],
            'passwordless' => true,
            'magic_code' => true,
            'trusted_days' => 30,
        ]);
    }

    protected function defineRoutes($router): void
    {
        $router->middleware('web')->get('people/login', fn () => 'people login')->name('people.login');
        $router->middleware(['web', 'auth:people'])->get('people/home', fn () => 'people home')->name('people.home');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        // The owner of the "people" guard opens its sessions its own way.
        $this->app->make(SignIn::class)->finishUsing('people', function ($request, $user, bool $remember) {
            Auth::guard('people')->login($user, $remember);
            $request->session()->regenerate();

            return redirect()->intended(route('people.home'));
        });
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadMigrationsFrom(__DIR__.'/database');
    }

    /**
     * @param  array<string,mixed>  $attributes
     */
    protected function admin(array $attributes = [], bool $superadmin = true): AdminUser
    {
        $admin = AdminUser::query()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'secret-password',
            'is_active' => true,
            ...$attributes,
        ]);

        $admin->forceFill(['is_superadmin' => $superadmin])->save();

        return $admin;
    }

    /**
     * @param  array<string,mixed>  $attributes
     */
    protected function person(array $attributes = []): Person
    {
        return Person::query()->create([
            'name' => 'Olga',
            'email' => 'olga@example.org',
            'password' => 'person-password',
            ...$attributes,
        ]);
    }

    protected function totp(Authenticatable $user, string $guard = 'admin', string $secret = self::SECRET): Factor
    {
        return Factor::query()->create([
            'guard' => $guard,
            'user_id' => $user->getAuthIdentifier(),
            'type' => 'totp',
            'secret' => $secret,
            'confirmed_at' => Carbon::now(),
        ]);
    }

    /**
     * The code an authenticator app would show, steps from now.
     */
    protected function code(int $offset = 0, string $secret = self::SECRET): string
    {
        $totp = Totp::fromConfig();

        return $totp->at($secret, $totp->step() + $offset);
    }

    protected function signInWithPassword(string $email = 'admin@example.com', string $password = 'secret-password', bool $remember = false): TestResponse
    {
        return $this->post('/admin/login', ['email' => $email, 'password' => $password, 'remember' => $remember]);
    }
}

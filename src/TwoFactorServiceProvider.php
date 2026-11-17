<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor;

use Atrium\Admin\Auth\Permissions;
use Atrium\Admin\Auth\SignIn;
use Atrium\Admin\Facades\Hook;
use Atrium\Admin\Models\AdminUser;
use Atrium\Admin\Modules\Modules;
use Atrium\TwoFactor\Contracts\SmsDriver;
use Atrium\TwoFactor\Factors\EmailFactor;
use Atrium\TwoFactor\Factors\PasskeyFactor;
use Atrium\TwoFactor\Factors\SmsFactor;
use Atrium\TwoFactor\Factors\TotpFactor;
use Atrium\TwoFactor\Http\Middleware\EnforceEnrolment;
use Atrium\TwoFactor\Http\Middleware\ReloadInertiaVisits;
use Atrium\TwoFactor\Services\Audit;
use Atrium\TwoFactor\Services\Challenge;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class TwoFactorServiceProvider extends ServiceProvider
{
    public const PACKAGE = 'alveby/atrium-2fa';

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/atrium-2fa.php', 'atrium-2fa');

        $this->app->singleton(Guards::class);
        $this->app->singleton(Factors::class);
        $this->app->bind(SmsDriver::class, fn ($app) => $app->make((string) config('atrium-2fa.sms.driver', Sms\LogSmsDriver::class)));
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'atrium-2fa');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'atrium-2fa');

        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__.'/../config/atrium-2fa.php' => config_path('atrium-2fa.php')], 'atrium-2fa-config');
            $this->publishes([__DIR__.'/../resources/views' => resource_path('views/vendor/atrium-2fa')], 'atrium-2fa-views');
            $this->publishes([__DIR__.'/../lang' => $this->app->langPath('vendor/atrium-2fa')], 'atrium-2fa-lang');
        }

        // Installed with `atrium:module:install alveby/atrium-2fa`: its tables
        // exist from then on, and only then does it take part in signing in.
        if (! $this->app->make(Modules::class)->isInstalled(self::PACKAGE)) {
            return;
        }

        $this->registerFactors();

        if (! $this->app->routesAreCached()) {
            $this->registerRoutes();
        }

        $this->registerHooks();

        $this->app->make(SignIn::class)->challengeUsing(fn (...$args) => $this->app->make(Challenge::class)(...$args));
        $this->app->make(HttpKernel::class)->appendMiddlewareToGroup('web', EnforceEnrolment::class);
    }

    private function registerFactors(): void
    {
        $this->app->afterResolving(Factors::class, function (Factors $factors): void {
            foreach ([TotpFactor::class, PasskeyFactor::class, SmsFactor::class, EmailFactor::class] as $class) {
                $factors->extend($this->app->make($class));
            }
        });
    }

    private function registerRoutes(): void
    {
        $guards = $this->app->make(Guards::class);

        foreach ($guards->names() as $guard) {
            $isAdmin = $guard === $guards->adminGuard();
            $authMiddleware = $guards->config($guard, 'auth_middleware', 'auth:'.$guard);
            $group = function () use ($guard, $guards, $isAdmin, $authMiddleware): void {
                Route::prefix((string) $guards->config($guard, 'prefix', $guard.'/auth'))
                    ->middleware([...(array) $guards->config($guard, 'middleware', ['web']), ReloadInertiaVisits::class])
                    ->name($guards->routeName($guard, ''))
                    ->group(function () use ($isAdmin, $authMiddleware): void {
                        require __DIR__.'/../routes/web.php';
                    });
            };

            if ($guards->config($guard, 'localized', false)) {
                Route::localized($group);
            } else {
                $group();
            }

            // The guard's routes know which guard they serve.
            foreach (Route::getRoutes()->getRoutes() as $route) {
                $name = (string) $route->getName();

                if (str_starts_with($name, $guards->routeName($guard, '')) || str_starts_with($name, 'localized.'.$guards->routeName($guard, ''))) {
                    $route->defaults('guard', $guard);
                }
            }
        }
    }

    private function registerHooks(): void
    {
        // Extra buttons on the login screens.
        Hook::filter('atrium.auth.methods', function (array $methods, string $guard): array {
            $guards = $this->app->make(Guards::class);

            if (! $guards->has($guard)) {
                return $methods;
            }

            if ($guards->passwordless($guard)) {
                $methods[] = [
                    'key' => 'passkey',
                    'label' => __('atrium-2fa::two-factor.login.passkey'),
                    'url' => $guards->route($guard, 'passkey'),
                    'icon' => Support\Icons::PASSKEY,
                    'kind' => 'link',
                ];
            }

            if ($guards->magicCode($guard)) {
                $methods[] = [
                    'key' => 'code',
                    'label' => __('atrium-2fa::two-factor.login.code'),
                    'url' => $guards->route($guard, 'magic'),
                    'icon' => Support\Icons::MAIL,
                    'kind' => 'link',
                ];
            }

            return $methods;
        });

        // Ways in besides the password that this package gives a person:
        // asked before the last social account may be unlinked.
        Hook::filter('atrium.auth.other_ways_in', function (int $ways, Authenticatable $user, string $guard): int {
            $guards = $this->app->make(Guards::class);

            if (! $guards->has($guard)) {
                return $ways;
            }

            if ($guards->magicCode($guard)) {
                $ways++;
            }

            if ($guards->passwordless($guard)
                && Models\Factor::query()->of($guard, $user->getAuthIdentifier())->confirmed()->where('type', 'passkey')->exists()) {
                $ways++;
            }

            return $ways;
        });

        // The panel's menu: one's own security for everybody, the log and
        // the resets for whoever may set the module up.
        Hook::filter('atrium.admin.menu', function (array $menu, ?AdminUser $user = null): array {
            $guards = $this->app->make(Guards::class);

            if ($user === null || ! $guards->has($guards->adminGuard())) {
                return $menu;
            }

            $children = [['label' => 'atrium-2fa::two-factor.menu.security', 'route' => 'admin.two-factor.security', 'icon' => 'shield']];

            if ($this->app->make(Permissions::class)->canSetUp($user, 'two_factor')) {
                $children[] = ['label' => 'atrium-2fa::two-factor.menu.log', 'route' => 'admin.two-factor.log', 'icon' => 'shield'];
                $children[] = ['label' => 'atrium-2fa::two-factor.menu.people', 'route' => 'admin.two-factor.users', 'icon' => 'users'];
            }

            $menu[] = ['label' => 'atrium-2fa::two-factor.menu.title', 'icon' => 'shield', 'children' => $children];

            return $menu;
        });

        // Every way in is logged, whichever package let the person in.
        Hook::on('atrium.auth.signed_in', function (Authenticatable $user, string $guard, string $method): void {
            if ($this->app->make(Guards::class)->has($guard)) {
                $this->app->make(Audit::class)->record($guard, $user, Audit::SIGNED_IN, $method);
            }
        });

        // Other sign-in packages (social accounts) write to the same log.
        Hook::on('atrium.auth.event', function (string $guard, Authenticatable|int|string|null $user, string $event, ?string $method = null, array $meta = []): void {
            $this->app->make(Audit::class)->record($guard, $user, $event, $method, $meta);
        });
    }
}

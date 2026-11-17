<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Tests\Feature;

use Atrium\Admin\Facades\Hook;
use Atrium\TwoFactor\Factors;
use Atrium\TwoFactor\Guards;
use Atrium\TwoFactor\Http\Middleware\ReloadInertiaVisits;
use Atrium\TwoFactor\Models\Event;
use Atrium\TwoFactor\Models\Factor;
use Atrium\TwoFactor\Support\Icons;
use Atrium\TwoFactor\Tests\TestCase;
use Atrium\TwoFactor\TwoFactorServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * What the package adds to the application once it is installed: shared
 * services, files to publish, routes per guard, buttons and menu entries.
 */
final class ProviderTest extends TestCase
{
    public function test_the_guards_and_the_factors_are_shared_and_every_factor_is_registered(): void
    {
        $this->assertSame($this->app->make(Guards::class), $this->app->make(Guards::class));
        $this->assertSame($this->app->make(Factors::class), $this->app->make(Factors::class));

        foreach (['totp', 'passkey', 'sms', 'email'] as $key) {
            $this->assertSame($key, $this->app->make(Factors::class)->get($key)?->key());
        }
    }

    /**
     * @return array<string,array{string,string,string}>
     */
    public static function publishable(): array
    {
        return [
            'config' => ['atrium-2fa-config', '/config/atrium-2fa.php', 'config/atrium-2fa.php'],
            'views' => ['atrium-2fa-views', '/resources/views', 'resources/views/vendor/atrium-2fa'],
            'translations' => ['atrium-2fa-lang', '/lang', 'lang/vendor/atrium-2fa'],
        ];
    }

    #[DataProvider('publishable')]
    public function test_the_config_views_and_translations_can_be_published(string $tag, string $from, string $to): void
    {
        $paths = ServiceProvider::pathsToPublish(TwoFactorServiceProvider::class, $tag);

        $this->assertCount(1, $paths);
        $this->assertSame(realpath(__DIR__.'/../..'.$from), realpath((string) array_key_first($paths)));
        $this->assertSame($this->app->basePath($to), reset($paths));
    }

    public function test_every_route_of_a_guard_knows_its_guard_and_lives_under_its_prefix(): void
    {
        $people = Route::getRoutes()->getByName('atrium-2fa.people.challenge');
        $admin = Route::getRoutes()->getByName('admin.two-factor.challenge');

        $this->assertSame('people/auth/challenge', $people?->uri());
        $this->assertSame('people', $people->defaults['guard'] ?? null);
        $this->assertSame('admin/auth/challenge', $admin?->uri());
        $this->assertSame('admin', $admin->defaults['guard'] ?? null);
        $this->assertContains('web', $people->gatherMiddleware());
        $this->assertContains(ReloadInertiaVisits::class, $people->gatherMiddleware());
        $this->assertContains('auth:people', Route::getRoutes()->getByName('atrium-2fa.people.security')?->gatherMiddleware() ?? []);
        $this->assertFalse(Route::has('atrium-2fa.people.log'), 'the log and resets live in the panel only');
        $this->assertTrue(Route::has('admin.two-factor.log'));
        $this->assertArrayNotHasKey('guard', Route::getRoutes()->getByName('people.home')?->defaults ?? []);
    }

    public function test_the_login_screen_gets_a_button_for_each_way_in_that_is_on(): void
    {
        $this->assertSame([
            ['key' => 'x'],
            [
                'key' => 'passkey',
                'label' => __('atrium-2fa::two-factor.login.passkey'),
                'url' => route('atrium-2fa.people.passkey'),
                'icon' => Icons::PASSKEY,
                'kind' => 'link',
            ],
            [
                'key' => 'code',
                'label' => __('atrium-2fa::two-factor.login.code'),
                'url' => route('atrium-2fa.people.magic'),
                'icon' => Icons::MAIL,
                'kind' => 'link',
            ],
        ], Hook::apply('atrium.auth.methods', [['key' => 'x']], 'people'));

        config(['atrium-2fa.guards.people.passwordless' => false]);
        $this->assertSame(['code'], array_column(Hook::apply('atrium.auth.methods', [], 'people'), 'key'));

        config(['atrium-2fa.guards.people.passwordless' => true, 'atrium-2fa.guards.people.magic_code' => false]);
        $this->assertSame(['passkey'], array_column(Hook::apply('atrium.auth.methods', [], 'people'), 'key'));
    }

    public function test_the_panel_menu_gets_the_section_after_what_was_there(): void
    {
        $admin = $this->admin();

        $menu = Hook::apply('atrium.admin.menu', [['label' => 'Pages']], $admin);

        $this->assertSame([
            ['label' => 'Pages'],
            ['label' => 'atrium-2fa::two-factor.menu.title', 'icon' => 'shield', 'children' => [
                ['label' => 'atrium-2fa::two-factor.menu.security', 'route' => 'admin.two-factor.security', 'icon' => 'shield'],
                ['label' => 'atrium-2fa::two-factor.menu.log', 'route' => 'admin.two-factor.log', 'icon' => 'shield'],
                ['label' => 'atrium-2fa::two-factor.menu.people', 'route' => 'admin.two-factor.users', 'icon' => 'users'],
            ]],
        ], $menu);
    }

    public function test_the_panel_menu_is_left_alone_when_the_panel_is_not_served(): void
    {
        config(['atrium-2fa.guards.admin.enabled' => false]);

        $this->assertSame([['label' => 'Pages']], Hook::apply('atrium.admin.menu', [['label' => 'Pages']], $this->admin()));
    }

    public function test_the_panel_menu_is_left_whole_for_nobody_signed_in(): void
    {
        $menu = [['label' => 'Pages'], ['label' => 'Products']];

        $this->assertSame($menu, Hook::apply('atrium.admin.menu', $menu));
        $this->assertSame($menu, Hook::apply('atrium.admin.menu', $menu, null));
    }

    public function test_the_login_screen_of_a_guard_switched_off_gets_no_buttons_even_if_its_ways_in_are_on(): void
    {
        config(['atrium-2fa.guards.people.enabled' => false]);

        $this->assertSame([['key' => 'a']], Hook::apply('atrium.auth.methods', [['key' => 'a']], 'people'));
    }

    public function test_every_sign_in_of_a_served_guard_is_logged_whoever_let_the_person_in(): void
    {
        $admin = $this->admin();

        Hook::fire('atrium.auth.signed_in', $admin, 'admin', 'social:github');
        Hook::fire('atrium.auth.signed_in', $admin, 'ghost', 'password');

        $event = Event::query()->sole();
        $this->assertSame(['admin', $admin->id, 'signed_in', 'social:github'], [$event->guard, $event->user_id, $event->event, $event->method]);
    }

    public function test_the_login_screen_of_a_guard_the_package_does_not_serve_is_left_alone(): void
    {
        $this->assertSame([['key' => 'a'], ['key' => 'b']], Hook::apply('atrium.auth.methods', [['key' => 'a'], ['key' => 'b']], 'ghost'));
    }

    /**
     * @return array<string,array{bool,bool,?string,int}>
     */
    public static function otherWaysIn(): array
    {
        return [
            'nothing on' => [false, false, 'confirmed', 0],
            'mail code only' => [true, false, null, 1],
            'mail code, passkey sign-in on but no passkey' => [true, true, null, 1],
            'passkey sign-in with a passkey' => [false, true, 'confirmed', 1],
            'both' => [true, true, 'confirmed', 2],
            'a passkey still being set up' => [false, true, 'pending', 0],
            'a passkey while passkey sign-in is off' => [false, false, 'confirmed', 0],
            'an app is not a passkey' => [false, true, 'totp', 0],
            'someone else\'s passkey' => [false, true, 'someone else', 0],
            'a passkey for the panel' => [false, true, 'other guard', 0],
        ];
    }

    #[DataProvider('otherWaysIn')]
    public function test_the_ways_in_besides_the_password_are_counted_for_a_served_guard(bool $magic, bool $passwordless, ?string $factor, int $added): void
    {
        config(['atrium-2fa.guards.people.magic_code' => $magic, 'atrium-2fa.guards.people.passwordless' => $passwordless]);
        $person = $this->person();
        $other = $this->person(['email' => 'ivan@example.org']);

        if ($factor !== null) {
            Factor::query()->create([
                'guard' => $factor === 'other guard' ? 'admin' : 'people',
                'user_id' => $factor === 'someone else' ? $other->id : $person->id,
                'type' => $factor === 'totp' ? 'totp' : 'passkey',
                'confirmed_at' => $factor === 'pending' ? null : now(),
            ]);
        }

        $this->assertSame($added, Hook::apply('atrium.auth.other_ways_in', 0, $person, 'people'));
        $this->assertSame(3 + $added, Hook::apply('atrium.auth.other_ways_in', 3, $person, 'people'));
    }

    public function test_the_ways_in_of_a_guard_the_package_does_not_serve_are_left_alone(): void
    {
        $person = $this->person();
        Factor::query()->create(['guard' => 'ghost', 'user_id' => $person->id, 'type' => 'passkey', 'confirmed_at' => now()]);
        config(['atrium-2fa.guards.ghost' => ['enabled' => true, 'magic_code' => true, 'passwordless' => true, 'factors' => ['passkey']]]);

        $this->assertSame(2, Hook::apply('atrium.auth.other_ways_in', 2, $person, 'ghost'));
    }
}

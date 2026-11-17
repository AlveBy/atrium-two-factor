<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Tests\Feature;

use Atrium\TwoFactor\Http\Middleware\ReloadInertiaVisits;
use Atrium\TwoFactor\Tests\TestCase;
use Illuminate\Support\Facades\Route;

/**
 * A guard switched on with nothing else said gets its pages under
 * "{guard}/auth", in the web group, behind Laravel's own auth middleware; and
 * what is said is taken in the shapes people write it in.
 */
final class GuardDefaultsTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $config = $app['config'];
        $config->set('auth.guards.staff', ['driver' => 'session', 'provider' => 'people']);
        $config->set('atrium-2fa.guards.staff', ['enabled' => true]);
        $config->set('auth.guards.crew', ['driver' => 'session', 'provider' => 'people']);
        $config->set('atrium-2fa.guards.crew', ['enabled' => true, 'middleware' => ['web', 'throttle:60,1']]);
        $config->set('atrium-2fa.guards.people.prefix', '/people/auth/');
        $config->set('atrium-2fa.guards.people.middleware', 'web');
        $config->set('atrium-2fa.guards.people.auth_middleware', ['auth:people', 'throttle:60,1']);
    }

    public function test_a_guard_that_says_nothing_lives_under_its_name_in_the_web_group_behind_auth(): void
    {
        $challenge = Route::getRoutes()->getByName('atrium-2fa.staff.challenge');
        $security = Route::getRoutes()->getByName('atrium-2fa.staff.security');

        $this->assertSame('staff/auth/challenge', $challenge?->uri());
        $this->assertSame('staff', $challenge->defaults['guard'] ?? null);
        $this->assertSame(['web', ReloadInertiaVisits::class], $challenge->gatherMiddleware());
        $this->assertSame(['web', ReloadInertiaVisits::class, 'auth:staff'], $security?->gatherMiddleware());
    }

    public function test_slashes_around_the_prefix_a_single_middleware_and_a_list_for_auth_are_all_understood(): void
    {
        $this->assertSame('people/auth/challenge', Route::getRoutes()->getByName('atrium-2fa.people.challenge')?->uri());
        $this->assertSame(['web', ReloadInertiaVisits::class], Route::getRoutes()->getByName('atrium-2fa.people.challenge')->gatherMiddleware());
        $this->assertSame(
            ['web', ReloadInertiaVisits::class, 'auth:people', 'throttle:60,1'],
            Route::getRoutes()->getByName('atrium-2fa.people.security')?->gatherMiddleware(),
        );
    }

    public function test_the_pages_of_a_guard_without_a_layout_are_shown_in_the_site_one_and_know_their_guard(): void
    {
        $people = config('atrium-2fa.guards.people');
        unset($people['layout']);
        // The site's own layout comes from the theme, which is not installed here.
        config(['atrium-2fa.guards.people' => $people, 'atrium-account.layout' => 'atrium-2fa::layouts.admin']);

        $page = $this->get(route('atrium-2fa.people.magic'))->assertOk();

        $this->assertSame('atrium-2fa::layouts.site', $page->viewData('layout'));
        $this->assertSame('people', $page->viewData('guard'));
    }

    public function test_every_middleware_of_a_list_is_kept_not_only_the_first(): void
    {
        $this->assertSame(
            ['web', 'throttle:60,1', ReloadInertiaVisits::class],
            Route::getRoutes()->getByName('atrium-2fa.crew.challenge')?->gatherMiddleware(),
        );
    }
}

<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Tests\Feature;

use Atrium\TwoFactor\Guards;
use Atrium\TwoFactor\Tests\TestCase;
use Illuminate\Support\Facades\Route;

/**
 * A guard whose pages live on the multilingual site gets every route twice,
 * like the site's own pages, and each knows its guard.
 */
final class WiringTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app['config']->set('atrium-2fa.guards.people.localized', true);
        $app['config']->set('atrium-2fa.guards.admin.localized', true);
    }

    public function test_a_localized_guard_has_routes_in_every_language(): void
    {
        $this->assertTrue(Route::has('atrium-2fa.people.magic'));
        $this->assertTrue(Route::has('localized.atrium-2fa.people.magic'));
        $this->assertSame('people', Route::getRoutes()->getByName('localized.atrium-2fa.people.magic')?->defaults['guard'] ?? null);
        $this->assertStringEndsWith('/people/auth/code', $this->app->make(Guards::class)->route('people', 'magic'));
    }

    public function test_the_localized_routes_of_one_guard_are_not_claimed_by_the_next(): void
    {
        $this->assertSame('admin', Route::getRoutes()->getByName('localized.admin.two-factor.challenge')?->defaults['guard'] ?? null);
        $this->assertSame('people', Route::getRoutes()->getByName('localized.atrium-2fa.people.challenge')?->defaults['guard'] ?? null);
    }
}

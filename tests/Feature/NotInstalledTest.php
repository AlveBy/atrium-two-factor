<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Tests\Feature;

use Atrium\Admin\Facades\Hook;
use Atrium\TwoFactor\Tests\TestCase;
use Illuminate\Support\Facades\Route;

/**
 * Before `atrium:module:install alveby/atrium-2fa` the package takes no
 * part in signing in: no routes, no buttons, no second step.
 */
final class NotInstalledTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app['config']->set('atrium.modules', []);
    }

    public function test_nothing_is_wired_until_the_module_is_installed(): void
    {
        $this->assertFalse(Route::has('admin.two-factor.challenge'));
        $this->assertSame([], Hook::apply('atrium.auth.methods', [], 'people'));

        $this->admin();
        $this->signInWithPassword()->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticated('admin');
    }
}

<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Tests\Feature;

use Atrium\TwoFactor\Tests\Support\SoftAuthenticator;
use Atrium\TwoFactor\Tests\TestCase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Testing\TestResponse;

/**
 * Signing in with a passkey alone, in detail: the limit per visitor and per
 * guard, what a script is told and what a sign-in leaves behind.
 */
final class PasskeyLoginDetailsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::createFromTimestamp(1_800_000_015));
    }

    /**
     * @param  array<string,mixed>  $body
     */
    private function attempt(array $body = [], string $ip = '203.0.113.1', string $route = 'atrium-2fa.people'): TestResponse
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])->postJson(route("{$route}.passkey.verify"), $body);
    }

    public function test_a_failed_passkey_is_told_why(): void
    {
        $this->attempt()->assertStatus(422)->assertExactJson(['message' => __('atrium-2fa::two-factor.passkey.failed')]);
    }

    public function test_ten_tries_a_minute_for_a_visitor_and_another_visitor_or_guard_is_not_held_up(): void
    {
        config(['atrium-2fa.guards.admin.passwordless' => true]);

        foreach (range(1, 10) as $ignored) {
            $this->attempt()->assertStatus(422);
        }
        $this->attempt()->assertStatus(429)->assertExactJson(['message' => __('atrium-2fa::two-factor.challenge.throttle', ['seconds' => 60])]);

        $this->attempt(ip: '203.0.113.2')->assertStatus(422);
        $this->attempt(route: 'admin.two-factor')->assertStatus(422);

        $this->travel(59)->seconds();
        $this->attempt()->assertStatus(429);
        $this->travel(1)->seconds();
        $this->attempt()->assertStatus(422);
    }

    public function test_a_passkey_that_signs_in_wipes_the_failures_and_the_browser_is_not_remembered(): void
    {
        $person = $this->person();
        $key = new SoftAuthenticator;
        $this->actingAs($person, 'people');
        $options = $this->postJson(route('atrium-2fa.people.passkeys.options'))->json();
        $this->postJson(route('atrium-2fa.people.passkeys.store'), $key->create($options))->assertOk();
        $this->app['auth']->guard('people')->logout();
        $this->app['auth']->forgetGuards();

        foreach (range(1, 9) as $ignored) {
            $this->attempt()->assertStatus(422);
        }
        $options = $this->postJson(route('atrium-2fa.people.passkey.options'))->json();
        $this->attempt($key->get($options))->assertOk()->assertCookieMissing(Auth::guard('people')->getRecallerName());
        $this->assertAuthenticatedAs($person, 'people');

        $this->app['auth']->guard('people')->logout();
        $this->app['auth']->forgetGuards();
        $this->attempt()->assertStatus(422);
        $this->attempt()->assertStatus(422);
    }
}

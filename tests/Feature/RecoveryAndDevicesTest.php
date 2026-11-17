<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Tests\Feature;

use Atrium\TwoFactor\Models\Event;
use Atrium\TwoFactor\Models\RecoveryCode;
use Atrium\TwoFactor\Models\TrustedDevice;
use Atrium\TwoFactor\Services\RecoveryCodes;
use Atrium\TwoFactor\Services\TrustedDevices;
use Atrium\TwoFactor\Tests\TestCase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

final class RecoveryAndDevicesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->travelTo(Carbon::createFromTimestamp(1_800_000_015));
    }

    public function test_a_recovery_code_lets_in_once_and_only_once(): void
    {
        $admin = $this->admin();
        $this->totp($admin);
        $codes = app(RecoveryCodes::class)->generate('admin', $admin->id);

        $this->signInWithPassword();
        $this->get(route('admin.two-factor.challenge', ['recovery' => 1]))->assertOk()->assertSee('name="recovery_code"', false);

        // Typed in capitals, without the dash: still the same code.
        $this->post(route('admin.two-factor.challenge.verify'), ['recovery_code' => strtoupper(str_replace('-', '', $codes[0]))])
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('atrium-2fa.status', __('atrium-2fa::two-factor.challenge.recovery_left', ['count' => 9]));
        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertSame(1, Event::query()->where('event', 'recovery_used')->count());

        $this->post(route('admin.logout'));
        $this->app['auth']->forgetGuards();

        $this->signInWithPassword();
        $this->post(route('admin.two-factor.challenge.verify'), ['recovery_code' => $codes[0]])
            ->assertRedirect(route('admin.two-factor.challenge', ['recovery' => 1]))
            ->assertSessionHasErrors(['code' => __('atrium-2fa::two-factor.challenge.recovery_invalid')]);
        $this->assertGuest('admin');

        // Another person's code is no way in either.
        $other = $this->admin(['email' => 'other@example.com']);
        $foreign = app(RecoveryCodes::class)->generate('admin', $other->id);
        $this->post(route('admin.two-factor.challenge.verify'), ['recovery_code' => $foreign[0]])->assertSessionHasErrors('code');
        $this->assertGuest('admin');
    }

    public function test_recovery_codes_are_stored_hashed_never_as_typed(): void
    {
        $admin = $this->admin();
        $codes = app(RecoveryCodes::class)->generate('admin', $admin->id);

        $this->assertCount(10, $codes);
        $this->assertCount(10, array_unique($codes));
        $this->assertMatchesRegularExpression('/^[a-z2-9]{5}-[a-z2-9]{5}$/', $codes[0]);

        foreach (RecoveryCode::query()->pluck('code_hash') as $hash) {
            $this->assertNotContains($hash, $codes);
            $this->assertSame(64, strlen($hash));
        }
    }

    public function test_wrong_recovery_codes_count_towards_the_lockout(): void
    {
        $admin = $this->admin();
        $this->totp($admin);
        app(RecoveryCodes::class)->generate('admin', $admin->id);
        $this->signInWithPassword();

        foreach (range(1, 5) as $ignored) {
            $this->post(route('admin.two-factor.challenge.verify'), ['recovery_code' => 'aaaaa-bbbbb']);
        }

        $this->post(route('admin.two-factor.challenge.verify'), ['code' => $this->code()])->assertSessionHasErrors('code');
        $this->assertGuest('admin');
        $this->assertSame(5, Event::query()->where('event', 'failed')->count());
    }

    public function test_a_trusted_browser_skips_the_second_step_until_revoked(): void
    {
        $admin = $this->admin();
        $this->totp($admin);
        $this->signInWithPassword();

        $response = $this->post(route('admin.two-factor.challenge.verify'), ['code' => $this->code(), 'trust' => '1']);
        $cookie = collect($response->headers->getCookies())->first(fn ($c) => $c->getName() === TrustedDevices::cookie('admin', $admin->id));
        $this->assertNotNull($cookie);
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame(1, TrustedDevice::query()->count());
        $this->assertNotSame($cookie->getValue(), TrustedDevice::query()->value('token_hash'));

        $this->post(route('admin.logout'));
        $this->app['auth']->forgetGuards();

        $this->withUnencryptedCookie($cookie->getName(), $cookie->getValue());
        $this->signInWithPassword()->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertSame(1, Event::query()->where('event', 'trusted_device')->count());

        $this->delete(route('admin.two-factor.devices.revoke'))->assertRedirect(route('admin.two-factor.security').'#devices');
        $this->assertSame(0, TrustedDevice::query()->count());
        $this->post(route('admin.logout'));
        $this->app['auth']->forgetGuards();

        $this->signInWithPassword()->assertRedirect(route('admin.two-factor.challenge'));
        $this->assertGuest('admin');
    }

    public function test_a_trusted_browser_stops_being_trusted_when_the_days_run_out(): void
    {
        config(['atrium-2fa.guards.admin.trusted_days' => 3]);
        $admin = $this->admin();
        $this->totp($admin);
        $this->signInWithPassword();
        $response = $this->post(route('admin.two-factor.challenge.verify'), ['code' => $this->code(), 'trust' => '1']);
        $cookie = collect($response->headers->getCookies())->first(fn ($c) => str_starts_with($c->getName(), 'atrium_2fa_admin_'));
        $this->post(route('admin.logout'));
        $this->app['auth']->forgetGuards();

        $this->travel(4)->days();
        $this->withUnencryptedCookie($cookie->getName(), $cookie->getValue());
        $this->signInWithPassword()->assertRedirect(route('admin.two-factor.challenge'));
    }

    public function test_trust_is_not_offered_when_the_days_are_zero(): void
    {
        config(['atrium-2fa.guards.admin.trusted_days' => 0]);
        $admin = $this->admin();
        $this->totp($admin);
        $this->signInWithPassword();

        $this->get(route('admin.two-factor.challenge'))->assertDontSee('name="trust"', false);
        $response = $this->post(route('admin.two-factor.challenge.verify'), ['code' => $this->code(), 'trust' => '1']);

        $this->assertSame(0, TrustedDevice::query()->count());
        $this->assertFalse(collect($response->headers->getCookies())->contains(fn ($c) => str_starts_with($c->getName(), 'atrium_2fa_')));
    }

    public function test_a_made_up_or_another_persons_trust_cookie_does_not_skip_anything(): void
    {
        $admin = $this->admin();
        $this->totp($admin);

        $this->withUnencryptedCookie(TrustedDevices::cookie('admin', $admin->id), str_repeat('x', 64));
        $this->signInWithPassword()->assertRedirect(route('admin.two-factor.challenge'));
    }
}

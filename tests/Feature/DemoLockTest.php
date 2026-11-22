<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Tests\Feature;

use Atrium\Admin\Facades\Setting;
use Atrium\Admin\Models\AdminUser;
use Atrium\TwoFactor\Models\Factor;
use Atrium\TwoFactor\Models\RecoveryCode;
use Atrium\TwoFactor\Models\TrustedDevice;
use Atrium\TwoFactor\Services\Policy;
use Atrium\TwoFactor\Services\RecoveryCodes;
use Atrium\TwoFactor\Tests\TestCase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * A public demo (docs/demo.md): everybody is the same person there, so a
 * second factor, a recovery code or a trusted device left by one visitor would
 * lock the next one out. Nothing of the kind can be changed while it is on.
 */
final class DemoLockTest extends TestCase
{
    private function demo(bool $on = true): void
    {
        config(['atrium.demo' => ['enabled' => $on]]);
    }

    /** The visitor of a demo: not a superadmin, and with a factor of their own so every route has something to touch. */
    private function visitor(bool $superadmin = false): AdminUser
    {
        $visitor = $this->admin(superadmin: $superadmin);
        $this->totp($visitor);
        app(RecoveryCodes::class)->generate('admin', $visitor->id);
        TrustedDevice::query()->create(['guard' => 'admin', 'user_id' => $visitor->id, 'token_hash' => str_repeat('a', 64), 'expires_at' => Carbon::now()->addDay()]);

        return $visitor;
    }

    /**
     * @return array<string, array{string, string, array<string,mixed>}> every route that changes a factor, a recovery code or a device
     */
    public static function routes(): array
    {
        return [
            'start an authenticator app' => ['POST', 'totp.start', []],
            'confirm an authenticator app' => ['POST', 'totp.confirm', ['code' => '000000']],
            'ask for passkey options' => ['POST', 'passkeys.options', []],
            'store a passkey' => ['POST', 'passkeys.store', []],
            'start SMS' => ['POST', 'sms.start', ['phone' => '+375291234567']],
            'confirm SMS' => ['POST', 'sms.confirm', ['code' => '000000']],
            'switch on the e-mailed code' => ['POST', 'email.enable', []],
            'remove a factor' => ['DELETE', 'factors.destroy', ['factor' => 1]],
            'make recovery codes anew' => ['POST', 'recovery.regenerate', []],
            'forget trusted devices' => ['DELETE', 'devices.revoke', []],
            'reset a person' => ['POST', 'reset', ['guard' => 'admin', 'user' => 1]],
        ];
    }

    /**
     * @param  array<string,mixed>  $parameters
     */
    #[DataProvider('routes')]
    public function test_every_route_that_changes_a_factor_is_refused_in_a_demo(string $method, string $name, array $parameters): void
    {
        $this->demo();
        $visitor = $this->visitor();
        $before = [Factor::query()->count(), RecoveryCode::query()->count(), TrustedDevice::query()->count()];
        $url = isset($parameters['factor']) ? route("admin.two-factor.{$name}", ['factor' => 1]) : route("admin.two-factor.{$name}");

        $this->actingAs($visitor, 'admin')
            ->json($method, $url, $parameters)
            ->assertForbidden()
            ->assertJsonPath('message', __('atrium-2fa::two-factor.security.demo_locked'));

        $this->assertSame($before, [Factor::query()->count(), RecoveryCode::query()->count(), TrustedDevice::query()->count()]);
    }

    /**
     * @param  array<string,mixed>  $parameters
     */
    #[DataProvider('routes')]
    public function test_the_same_routes_answer_as_usual_on_a_site_that_is_not_a_demo(string $method, string $name, array $parameters): void
    {
        $this->demo(false);
        $this->actingAs($this->visitor(superadmin: true), 'admin');
        $url = isset($parameters['factor']) ? route("admin.two-factor.{$name}", ['factor' => 1]) : route("admin.two-factor.{$name}");

        $this->assertNotSame(403, $this->json($method, $url, $parameters)->status());
    }

    public function test_a_superadmin_of_a_demo_cannot_reset_a_person_either(): void
    {
        $this->demo();
        $admin = $this->admin();
        $this->totp($admin);
        $this->actingAs($admin, 'admin');

        $this->post(route('admin.two-factor.reset'), ['guard' => 'admin', 'user' => $admin->id])->assertForbidden();

        $this->assertSame(1, Factor::query()->count());
    }

    public function test_another_guard_s_security_routes_are_locked_too(): void
    {
        $this->demo();
        $person = $this->person();
        $this->actingAs($person, 'people');

        $this->post(route('atrium-2fa.people.totp.start'))->assertForbidden();
        $this->post(route('atrium-2fa.people.email.enable'))->assertForbidden();
        $this->assertSame(0, Factor::query()->count());
    }

    public function test_the_security_page_of_a_demo_says_so_and_has_no_forms(): void
    {
        $this->demo();
        $this->actingAs($this->visitor(), 'admin');

        $this->get(route('admin.two-factor.security'))
            ->assertOk()
            ->assertSee('dusk="a2f-demo-locked"', false)
            ->assertSee(__('atrium-2fa::two-factor.security.demo_locked'))
            ->assertDontSee('<form', false)
            ->assertDontSee('a2f-totp-start')
            ->assertDontSee('a2f-recovery-regenerate')
            ->assertDontSee('a2f-devices-revoke');
    }

    public function test_the_security_page_of_a_site_that_is_not_a_demo_has_its_forms(): void
    {
        $this->demo(false);
        $this->actingAs($this->visitor(), 'admin');

        $this->get(route('admin.two-factor.security'))
            ->assertOk()
            ->assertSee('dusk="a2f-totp-start"', false)
            ->assertSee('dusk="a2f-recovery-regenerate"', false)
            ->assertDontSee('dusk="a2f-demo-locked"', false);
    }

    public function test_the_security_page_of_a_demo_is_for_people_who_are_signed_in(): void
    {
        $this->demo();

        $this->get(route('admin.two-factor.security'))->assertRedirect(route('admin.login'));
    }

    public function test_the_settings_of_the_module_cannot_be_saved_in_a_demo(): void
    {
        $this->demo();
        $this->actingAs($this->admin(), 'admin');

        $this->put('/admin/modules/two_factor/settings', ['admin_required' => 'all'])->assertForbidden();

        $this->assertNull(Setting::get('two_factor.admin_required'));
    }

    public function test_a_demo_never_asks_anybody_to_set_a_factor_up(): void
    {
        config(['atrium-2fa.guards.admin.required' => 'all', 'atrium-2fa.guards.admin.factors' => ['totp'], 'atrium-2fa.guards.people.required' => 'all']);
        $admin = $this->admin();
        $policy = $this->app->make(Policy::class);

        $this->assertTrue($policy->required('admin', $admin));
        $this->assertTrue($policy->mustEnrol('admin', $admin));

        $this->demo();

        $this->assertFalse($policy->required('admin', $admin));
        $this->assertFalse($policy->required('people', $this->person()));
        $this->assertFalse($policy->mustEnrol('admin', $admin));
        $this->assertSame([], $policy->factors('admin', $admin));
    }

    public function test_the_visitor_of_a_demo_signs_in_with_the_password_alone_even_where_a_second_step_is_required(): void
    {
        config(['atrium-2fa.guards.admin.required' => 'all']);
        $this->demo();
        $admin = $this->admin();

        $this->signInWithPassword()->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin, 'admin');
        $this->get(route('admin.dashboard'))->assertOk();
    }
}

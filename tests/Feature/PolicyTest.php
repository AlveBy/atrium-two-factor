<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Tests\Feature;

use Atrium\Admin\Facades\Hook;
use Atrium\Admin\Facades\Setting;
use Atrium\Admin\Models\AdminRole;
use Atrium\TwoFactor\Contracts\SmsDriver;
use Atrium\TwoFactor\Mail\CodeMail;
use Atrium\TwoFactor\Models\Factor;
use Atrium\TwoFactor\Services\Policy;
use Atrium\TwoFactor\Tests\TestCase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Who must pass a second step, and what happens to someone who must but
 * has nothing set up yet.
 */
final class PolicyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->travelTo(Carbon::createFromTimestamp(1_800_000_015));
    }

    public function test_optional_means_the_password_alone_for_people_without_a_factor(): void
    {
        config(['atrium-2fa.guards.admin.required' => 'optional']);
        $admin = $this->admin();

        $this->signInWithPassword()->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin, 'admin');
        Mail::assertNothingSent();
    }

    public function test_optional_still_asks_those_who_set_a_factor_up(): void
    {
        config(['atrium-2fa.guards.admin.required' => 'optional']);
        $this->totp($this->admin());

        $this->signInWithPassword()->assertRedirect(route('admin.two-factor.challenge'));
    }

    /**
     * @return array<string, array{bool, bool, bool}>
     */
    public static function roles(): array
    {
        return [
            'superadmin is always asked' => [true, false, true],
            'a listed role is asked' => [false, true, true],
            'any other role is not' => [false, false, false],
        ];
    }

    #[DataProvider('roles')]
    public function test_roles_mode_asks_only_the_listed_roles(bool $superadmin, bool $listed, bool $asked): void
    {
        $editors = AdminRole::query()->create(['name' => 'Editors', 'permissions' => []]);
        $authors = AdminRole::query()->create(['name' => 'Authors', 'permissions' => []]);
        config(['atrium-2fa.guards.admin.required' => 'roles', 'atrium-2fa.guards.admin.required_roles' => [$editors->id]]);

        $admin = $this->admin(superadmin: $superadmin);
        $admin->roles()->attach($listed ? $editors : $authors);

        $this->signInWithPassword()->assertRedirect($asked ? route('admin.two-factor.challenge') : route('admin.dashboard'));
    }

    public function test_roles_mode_with_no_roles_listed_asks_only_superadmins(): void
    {
        config(['atrium-2fa.guards.admin.required' => 'roles', 'atrium-2fa.guards.admin.required_roles' => []]);
        $this->admin(superadmin: false);

        $this->signInWithPassword()->assertRedirect(route('admin.dashboard'));
    }

    public function test_roles_mode_asks_nobody_on_a_guard_whose_people_have_no_roles(): void
    {
        config(['atrium-2fa.guards.people.required' => 'roles', 'atrium-2fa.guards.people.required_roles' => ['1']]);

        $this->assertFalse($this->app->make(Policy::class)->required('people', $this->person()));
    }

    public function test_a_hook_can_make_the_second_step_required(): void
    {
        config(['atrium-2fa.guards.admin.required' => 'optional']);
        Hook::filter('atrium.two_factor.required', fn (bool $required, $user) => $required || str_ends_with($user->email, '@example.com'));
        $this->admin();

        $this->signInWithPassword()->assertRedirect(route('admin.two-factor.challenge'));
        Mail::assertSent(CodeMail::class);
    }

    public function test_the_admin_settings_win_over_the_config_and_an_empty_setting_keeps_it(): void
    {
        $admin = $this->admin();

        Setting::save('two_factor', ['admin_required' => 'optional']);
        $this->signInWithPassword()->assertRedirect(route('admin.dashboard'));
        $this->post(route('admin.logout'));
        $this->app['auth']->forgetGuards();

        Setting::save('two_factor', ['admin_required' => null]);
        $this->signInWithPassword()->assertRedirect(route('admin.two-factor.challenge'));
        $this->assertGuest('admin');
        $this->assertNotNull($admin);
    }

    public function test_settings_can_switch_the_code_sign_in_and_the_trusted_days(): void
    {
        $this->get(route('admin.two-factor.magic'))->assertNotFound();
        Setting::save('two_factor', ['admin_magic_code' => '1', 'admin_trusted_days' => 0]);
        $this->get(route('admin.two-factor.magic'))->assertOk();

        $this->totp($this->admin());
        $this->signInWithPassword();
        $this->get(route('admin.two-factor.challenge'))->assertDontSee('name="trust"', false);
    }

    public function test_someone_who_must_but_cannot_pass_a_step_is_let_in_once_and_sent_to_set_one_up(): void
    {
        config(['atrium-2fa.guards.admin.required' => 'all', 'atrium-2fa.guards.admin.factors' => ['totp', 'passkey']]);
        $admin = $this->admin();

        $this->signInWithPassword()->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin, 'admin');

        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.two-factor.security'));
        $this->get(route('admin.dashboard'), ['X-Inertia' => 'true'])->assertStatus(409)->assertHeader('X-Inertia-Location', route('admin.two-factor.security'));
        $this->get(route('admin.two-factor.security'))->assertOk()->assertSee(__('atrium-2fa::two-factor.security.must_enrol'));

        $this->post(route('admin.two-factor.totp.start'));
        $secret = (string) Factor::query()->sole()->secret;
        $this->post(route('admin.two-factor.totp.confirm'), ['code' => $this->code(0, $secret)]);

        $this->get(route('admin.dashboard'))->assertOk();
    }

    public function test_signing_out_is_never_blocked_and_clears_the_reminder(): void
    {
        config(['atrium-2fa.guards.admin.required' => 'all', 'atrium-2fa.guards.admin.factors' => ['totp']]);
        $this->admin();
        $this->signInWithPassword();

        $this->post(route('admin.logout'))->assertRedirect();
        $this->app['auth']->forgetGuards();
        $this->get(route('admin.login'))->assertOk();
        $this->assertFalse(session()->has('atrium-2fa.enrol.admin'));
    }

    public function test_an_app_and_email_let_the_person_switch_and_resend(): void
    {
        $admin = $this->admin();
        $this->totp($admin);
        $this->actingAs($admin, 'admin')->post(route('admin.two-factor.email.enable'));
        $this->app['auth']->forgetGuards();
        $this->flushSession();
        Mail::fake();

        $this->signInWithPassword();
        Mail::assertNothingSent();
        $this->get(route('admin.two-factor.challenge'))->assertSee('dusk="a2f-switch-email"', false);

        $this->post(route('admin.two-factor.challenge.switch'), ['factor' => 'email'])->assertRedirect(route('admin.two-factor.challenge'));
        Mail::assertSentCount(1);
        $this->get(route('admin.two-factor.challenge'))->assertSee('ad***@example.com');

        $this->post(route('admin.two-factor.challenge.resend'))->assertSessionHasErrors(['code' => __('atrium-2fa::two-factor.challenge.resend_too_soon')]);
        Mail::assertSentCount(1);

        $this->travel(61)->seconds();
        $this->post(route('admin.two-factor.challenge.resend'))->assertSessionHas('atrium-2fa.status', __('atrium-2fa::two-factor.challenge.resent'));
        Mail::assertSentCount(2);

        // A factor the person does not have cannot be switched to.
        $this->post(route('admin.two-factor.challenge.switch'), ['factor' => 'sms']);
        $this->get(route('admin.two-factor.challenge'))->assertSee('ad***@example.com');
    }

    public function test_switching_and_resending_without_a_pending_sign_in_lead_to_the_login(): void
    {
        $this->post(route('admin.two-factor.challenge.switch'), ['factor' => 'email'])->assertRedirect(route('admin.login'));
        $this->post(route('admin.two-factor.challenge.resend'))->assertRedirect(route('admin.login'));
        $this->postJson(route('admin.two-factor.challenge.options'))->assertStatus(419);
    }

    public function test_a_code_that_cannot_be_mailed_says_so_instead_of_failing(): void
    {
        $this->admin();
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP is down'));

        $this->signInWithPassword()->assertRedirect(route('admin.two-factor.challenge'));
        $this->get(route('admin.two-factor.challenge'))->assertOk()->assertSee(__('atrium-2fa::two-factor.challenge.send_failed'));
    }

    public function test_a_texted_code_is_a_second_step(): void
    {
        $sent = [];
        $this->app->instance(SmsDriver::class, new class($sent) implements SmsDriver
        {
            /** @param list<array{string,string}> $sent */
            public function __construct(private array &$sent) {}

            public function send(string $phone, string $message): void
            {
                $this->sent[] = [$phone, $message];
            }
        });
        $admin = $this->admin();
        Factor::query()->create(['guard' => 'admin', 'user_id' => $admin->id, 'type' => 'sms', 'secret' => '+375291234567', 'confirmed_at' => now()]);

        $this->signInWithPassword()->assertRedirect(route('admin.two-factor.challenge'));
        $this->assertCount(1, $sent);
        $this->get(route('admin.two-factor.challenge'))->assertSee('+3*********67')->assertDontSee('+375291234567');

        preg_match('/\d{6}/', $sent[0][1], $m);
        $this->post(route('admin.two-factor.challenge.verify'), ['code' => $m[0]])->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin, 'admin');
    }

    public function test_an_inertia_visit_to_a_two_factor_page_becomes_a_full_page_load(): void
    {
        $this->totp($this->admin());
        $this->signInWithPassword();

        $this->get(route('admin.two-factor.challenge'), ['X-Inertia' => 'true'])
            ->assertStatus(409)
            ->assertHeader('X-Inertia-Location', route('admin.two-factor.challenge'));
    }

    public function test_the_login_form_leads_to_the_challenge_for_inertia_too(): void
    {
        $this->totp($this->admin());

        // Inertia follows the redirect with an X-Inertia GET, which the
        // challenge page answers with a full page load (see above).
        $this->post('/admin/login', ['email' => 'admin@example.com', 'password' => 'secret-password'], ['X-Inertia' => 'true'])
            ->assertRedirect(route('admin.two-factor.challenge'));
    }
}

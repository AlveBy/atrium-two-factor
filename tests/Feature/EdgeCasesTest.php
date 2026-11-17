<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Tests\Feature;

use Atrium\Admin\Facades\Hook;
use Atrium\TwoFactor\Models\Event;
use Atrium\TwoFactor\Tests\TestCase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * The branches the main scenarios pass by: a mail server that is down, a
 * factor that stops being allowed halfway, a session left over after
 * signing out, and the hooks other packages use.
 */
final class EdgeCasesTest extends TestCase
{
    public function test_a_resend_that_cannot_be_mailed_shows_why_and_not_too_soon(): void
    {
        $this->admin();
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP is down'));
        Log::shouldReceive('error')->twice()->withArgs(fn (string $message, array $context): bool => $message === 'Could not send a sign-in code'
            && $context['guard'] === 'admin' && $context['factor'] === 'email' && $context['exception'] instanceof \RuntimeException);
        $this->signInWithPassword()->assertRedirect(route('admin.two-factor.challenge'));

        $this->travel(61)->seconds();
        $this->post(route('admin.two-factor.challenge.resend'))
            ->assertRedirect(route('admin.two-factor.challenge'))
            ->assertSessionDoesntHaveErrors('code')
            ->assertSessionMissing('atrium-2fa.status')
            ->assertSessionHas('atrium-2fa.error', __('atrium-2fa::two-factor.challenge.send_failed'));
    }

    public function test_a_sign_in_code_that_cannot_be_mailed_is_logged_and_the_answer_stays_the_same(): void
    {
        $this->person();
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP is down'));
        Log::shouldReceive('error')->once()->withArgs(fn (string $message, array $context): bool => $message === 'Could not send a sign-in code'
            && $context['exception'] instanceof \RuntimeException && $context['exception']->getMessage() === 'SMTP is down');

        $this->post(route('atrium-2fa.people.magic.send'), ['email' => 'olga@example.org'])
            ->assertRedirect(route('atrium-2fa.people.magic.code'));
    }

    public function test_a_factor_taken_off_the_allowed_list_halfway_sends_back_to_the_login(): void
    {
        $admin = $this->admin();
        $this->totp($admin);
        $this->signInWithPassword()->assertRedirect(route('admin.two-factor.challenge'));

        config(['atrium-2fa.guards.admin.factors' => ['passkey']]);

        $this->post(route('admin.two-factor.challenge.resend'))->assertRedirect(route('admin.login'));
        $this->assertGuest('admin');
    }

    public function test_a_person_who_need_not_have_a_factor_may_remove_the_only_one(): void
    {
        config(['atrium-2fa.guards.admin.required' => 'optional', 'atrium-2fa.guards.admin.factors' => ['totp']]);
        $admin = $this->admin();
        $app = $this->totp($admin);

        $this->actingAs($admin, 'admin')
            ->delete(route('admin.two-factor.factors.destroy', $app->id))
            ->assertSessionHasNoErrors();
        $this->assertNull($app->fresh());
    }

    public function test_an_enrolment_left_in_the_session_after_signing_out_is_forgotten(): void
    {
        $this->withSession(['atrium-2fa.enrol.admin' => true])
            ->get(route('admin.login'))
            ->assertOk();

        $this->assertFalse(session()->has('atrium-2fa.enrol.admin'));
    }

    public function test_the_log_of_a_guard_without_people_behind_it_still_opens(): void
    {
        $admin = $this->admin();
        Event::query()->create(['guard' => 'ghost', 'user_id' => 7, 'event' => 'signed_in', 'method' => 'password', 'meta' => []]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.two-factor.log'))
            ->assertOk()
            ->assertSee('ghost');
    }

    public function test_other_packages_write_to_the_same_log(): void
    {
        $admin = $this->admin();

        Hook::fire('atrium.auth.event', 'admin', $admin, 'social_linked', 'social:github', ['by' => 'profile']);

        $event = Event::query()->sole();
        $this->assertSame(['admin', $admin->id, 'social_linked', 'social:github', ['by' => 'profile']], [$event->guard, $event->user_id, $event->event, $event->method, $event->meta]);
    }

    public function test_a_guard_the_package_does_not_serve_gets_no_extra_buttons(): void
    {
        $this->assertSame([['key' => 'x']], Hook::apply('atrium.auth.methods', [['key' => 'x']], 'ghost'));
        $this->assertNotSame([], Hook::apply('atrium.auth.methods', [], 'people'));
    }
}

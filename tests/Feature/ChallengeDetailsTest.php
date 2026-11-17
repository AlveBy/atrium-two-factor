<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Tests\Feature;

use Atrium\TwoFactor\Factors\SmsFactor;
use Atrium\TwoFactor\Models\Event;
use Atrium\TwoFactor\Models\Factor;
use Atrium\TwoFactor\Services\RecoveryCodes;
use Atrium\TwoFactor\Tests\TestCase;
use Illuminate\Foundation\Http\Middleware\TrimStrings;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

/**
 * The second-step page in detail: what it shows, what each answer leaves
 * behind, and the answers given to a script.
 */
final class ChallengeDetailsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->travelTo(Carbon::createFromTimestamp(1_800_000_015));
    }

    public function test_an_app_needs_no_waiting_for_a_resend_and_without_recovery_codes_none_are_offered(): void
    {
        $admin = $this->admin();
        $this->totp($admin);
        $this->signInWithPassword();

        $page = $this->get(route('admin.two-factor.challenge'))->assertOk();

        $this->assertSame('totp', $page->viewData('factor')?->key());
        $this->assertNull($page->viewData('destination'));
        $this->assertSame(0, $page->viewData('resendIn'));
        $this->assertFalse($page->viewData('hasRecovery'));
        $this->assertFalse($page->viewData('recovery'));
        $this->assertSame(30, $page->viewData('trustedDays'));

        app(RecoveryCodes::class)->generate('admin', $admin->id);
        $this->assertTrue($this->get(route('admin.two-factor.challenge'))->viewData('hasRecovery'));
        $this->assertTrue($this->get(route('admin.two-factor.challenge', ['recovery' => 1]))->viewData('recovery'));
    }

    public function test_a_mailed_code_shows_where_it_went_and_how_long_to_wait_for_another(): void
    {
        $this->admin();
        $this->signInWithPassword();
        $this->travel(20)->seconds();

        $page = $this->get(route('admin.two-factor.challenge'))->assertOk();

        $this->assertSame('email', $page->viewData('factor')?->key());
        $this->assertSame('ad***@example.com', $page->viewData('destination'));
        $this->assertSame(40, $page->viewData('resendIn'));
    }

    public function test_an_empty_code_is_asked_for_and_a_long_one_refused(): void
    {
        $admin = $this->admin();
        $this->totp($admin);
        $this->signInWithPassword();

        $this->post(route('admin.two-factor.challenge.verify'), ['code' => ''])
            ->assertSessionHasErrors(['code' => __('atrium-2fa::two-factor.challenge.code_required')]);
        $this->post(route('admin.two-factor.challenge.verify'), ['code' => str_repeat('1', 21)])->assertSessionHasErrors('code');

        $this->assertGuest('admin');
        $this->assertSame(0, Event::query()->where('event', 'failed')->count(), 'not even checked');
    }

    public function test_an_answer_for_a_factor_taken_off_the_allowed_list_halfway_fails_without_an_error(): void
    {
        $admin = $this->admin();
        $this->totp($admin);
        $this->signInWithPassword();
        config(['atrium-2fa.guards.admin.factors' => ['passkey']]);

        $this->post(route('admin.two-factor.challenge.verify'), ['code' => $this->code()])
            ->assertRedirect(route('admin.two-factor.challenge'))
            ->assertSessionHasErrors(['code' => __('atrium-2fa::two-factor.challenge.code_invalid')]);

        $this->assertGuest('admin');
        $this->assertSame('', Event::query()->where('event', 'failed')->sole()->method ?? '');
    }

    public function test_a_right_answer_clears_the_wrong_ones_before_it_and_the_pending_sign_in(): void
    {
        $admin = $this->admin();
        $this->totp($admin);

        $this->signInWithPassword();
        foreach (range(1, 4) as $ignored) {
            $this->post(route('admin.two-factor.challenge.verify'), ['code' => '000000']);
        }
        $this->post(route('admin.two-factor.challenge.verify'), ['code' => $this->code()])->assertRedirect(route('admin.dashboard'));
        $this->assertFalse(session()->has('atrium-2fa.pending.admin'));

        $this->post(route('admin.logout'));
        $this->app['auth']->forgetGuards();
        $this->travel(30)->seconds();

        // Four more wrong ones would be eight in the minute had the first four been kept.
        $this->signInWithPassword();
        foreach (range(1, 4) as $ignored) {
            $this->post(route('admin.two-factor.challenge.verify'), ['code' => '000000']);
        }
        $this->post(route('admin.two-factor.challenge.verify'), ['code' => $this->code()])->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin, 'admin');
    }

    public function test_a_recovery_code_is_logged_with_how_many_are_left_and_spaces_around_it_do_not_matter(): void
    {
        $admin = $this->admin();
        $this->totp($admin);
        $codes = app(RecoveryCodes::class)->generate('admin', $admin->id);
        $this->signInWithPassword();

        $this->post(route('admin.two-factor.challenge.verify'), ['recovery_code' => '  '.$codes[3].'  '])->assertRedirect(route('admin.dashboard'));

        $this->assertSame(['left' => 9], Event::query()->where('event', 'recovery_used')->sole()->meta);
    }

    public function test_trusting_the_browser_is_logged_with_the_factor_that_passed(): void
    {
        $admin = $this->admin();
        $this->totp($admin);
        $this->signInWithPassword();

        $this->post(route('admin.two-factor.challenge.verify'), ['code' => $this->code(), 'trust' => '1']);

        $this->assertSame('totp', Event::query()->where('event', 'device_trusted')->sole()->method);
    }

    public function test_a_script_is_told_why_an_answer_failed(): void
    {
        $admin = $this->admin();
        $this->totp($admin);
        $this->signInWithPassword();
        $message = __('atrium-2fa::two-factor.challenge.code_invalid');

        $this->postJson(route('admin.two-factor.challenge.verify'), ['code' => '000000'])
            ->assertStatus(422)
            ->assertExactJson(['message' => $message, 'errors' => ['code' => [$message]]]);
    }

    public function test_a_script_and_a_page_are_sent_back_to_the_login_when_the_sign_in_expired(): void
    {
        $message = __('atrium-2fa::two-factor.challenge.expired');

        $this->postJson(route('admin.two-factor.challenge.verify'), ['code' => '123456'])
            ->assertStatus(419)
            ->assertExactJson(['message' => $message, 'redirect' => route('admin.login')]);

        $this->post(route('admin.two-factor.challenge.verify'), ['code' => '123456'])
            ->assertRedirect(route('admin.login'))
            ->assertSessionHasErrors(['email' => $message]);
    }

    public function test_switching_to_a_factor_the_person_does_not_have_changes_nothing(): void
    {
        $admin = $this->admin();
        $this->totp($admin);
        $this->signInWithPassword();

        $this->post(route('admin.two-factor.challenge.switch'), ['factor' => 'sms'])->assertRedirect(route('admin.two-factor.challenge'));

        $this->assertSame('totp', session('atrium-2fa.pending.admin.factor'));
        Mail::assertNothingSent();
    }

    public function test_passkey_options_need_a_pending_sign_in(): void
    {
        $this->postJson(route('admin.two-factor.challenge.options'))->assertStatus(419);
    }

    public function test_a_passkey_has_nothing_to_send_and_is_ready_at_once(): void
    {
        $admin = $this->admin();
        Factor::query()->create(['guard' => 'admin', 'user_id' => $admin->id, 'type' => 'passkey', 'data' => ['credential_id' => 'aWQ=', 'public_key' => 'key'], 'confirmed_at' => Carbon::now()]);
        $this->signInWithPassword();
        $this->assertSame('passkey', session('atrium-2fa.pending.admin.factor'));

        $this->post(route('admin.two-factor.challenge.resend'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('atrium-2fa.status', __('atrium-2fa::two-factor.challenge.resent'));
        Mail::assertNothingSent();
    }

    public function test_a_texted_code_never_passes_without_a_confirmed_phone(): void
    {
        $admin = $this->admin();
        Factor::query()->create(['guard' => 'admin', 'user_id' => $admin->id, 'type' => 'sms', 'secret' => '+375291234567']);

        $this->assertFalse(app(SmsFactor::class)->verify($admin, 'admin', Request::create('/', 'POST', ['code' => '123456'])));
    }

    public function test_a_factor_taken_off_the_allowed_list_halfway_leaves_only_the_recovery_code_to_ask_for(): void
    {
        $admin = $this->admin();
        $this->totp($admin);
        $this->signInWithPassword()->assertRedirect(route('admin.two-factor.challenge'));

        config(['atrium-2fa.guards.admin.factors' => ['passkey']]);
        $page = $this->get(route('admin.two-factor.challenge'))->assertOk();

        $this->assertNull($page->viewData('factor'));
        $this->assertNull($page->viewData('destination'));
        $this->assertTrue($page->viewData('recovery'));
        $this->assertSame(0, $page->viewData('resendIn'));
    }

    public function test_a_recovery_code_of_spaces_alone_is_no_recovery_code_even_untrimmed(): void
    {
        TrimStrings::skipWhen(fn (): bool => true);
        $admin = $this->admin();
        $this->totp($admin);
        app(RecoveryCodes::class)->generate('admin', $admin->id);
        $this->signInWithPassword();

        $this->post(route('admin.two-factor.challenge.verify'), ['recovery_code' => '   ', 'code' => ''])
            ->assertSessionHasErrors(['code' => __('atrium-2fa::two-factor.challenge.code_required')]);

        $this->assertGuest('admin');
        $this->assertSame(0, Event::query()->where('event', 'failed')->count());
    }

    public function test_an_app_code_signs_in_without_a_word_about_recovery_codes(): void
    {
        $admin = $this->admin();
        $this->totp($admin);
        app(RecoveryCodes::class)->generate('admin', $admin->id);
        $this->signInWithPassword();

        $this->post(route('admin.two-factor.challenge.verify'), ['code' => $this->code()])
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionMissing('atrium-2fa.status');

        $this->assertAuthenticatedAs($admin, 'admin');
    }
}

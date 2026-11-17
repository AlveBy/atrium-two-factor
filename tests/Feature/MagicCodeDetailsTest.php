<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Tests\Feature;

use Atrium\TwoFactor\Mail\CodeMail;
use Atrium\TwoFactor\Models\Event;
use Atrium\TwoFactor\Tests\TestCase;
use Illuminate\Foundation\Http\Middleware\TrimStrings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;

/**
 * Signing in with a code by e-mail, in detail: how the address is kept, the
 * limits per visitor and per guard, and what a sign-in leaves behind.
 */
final class MagicCodeDetailsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->travelTo(Carbon::createFromTimestamp(1_800_000_015));
    }

    private function send(string $email, string $ip = '203.0.113.1', string $guard = 'atrium-2fa.people'): TestResponse
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])->post(route("{$guard}.magic.send"), ['email' => $email]);
    }

    private function check(string $code, string $ip = '203.0.113.1', string $guard = 'atrium-2fa.people'): TestResponse
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])->post(route("{$guard}.magic.verify"), ['code' => $code]);
    }

    private function throttled(): string
    {
        return __('atrium-2fa::two-factor.challenge.throttle', ['seconds' => 60]);
    }

    public function test_spaces_around_the_address_are_refused_when_nothing_trimmed_them(): void
    {
        TrimStrings::skipWhen(fn (): bool => true);
        $this->person();

        $this->send('  olga@example.org  ')->assertSessionHasErrors(['email' => __('atrium-2fa::two-factor.magic.email_invalid')]);

        Mail::assertNothingSent();
    }

    public function test_capitals_outside_latin_are_made_small_too(): void
    {
        $this->send('ОЛЬГА@example.org');

        $this->assertSame('ольга@example.org', session('atrium-2fa.magic.people'));
    }

    public function test_an_address_left_out_is_asked_for(): void
    {
        $this->post(route('atrium-2fa.people.magic.send'), [])
            ->assertSessionHasErrors(['email' => __('atrium-2fa::two-factor.magic.email_required')]);

        $this->assertNull(session('atrium-2fa.magic.people'));
    }

    public function test_ten_codes_a_minute_are_sent_to_a_visitor_and_another_visitor_or_guard_is_not_held_up(): void
    {
        config(['atrium-2fa.guards.admin.magic_code' => true]);

        foreach (range(1, 10) as $n) {
            $this->send("guess{$n}@example.org")->assertSessionHasNoErrors();
        }
        $this->send('guess11@example.org')->assertSessionHasErrors(['email' => $this->throttled()]);

        $this->send('guess11@example.org', ip: '203.0.113.2')->assertSessionHasNoErrors();
        $this->send('guess11@example.org', guard: 'admin.two-factor')->assertSessionHasNoErrors();

        $this->travel(60)->seconds();
        $this->send('guess11@example.org')->assertSessionHasNoErrors();
    }

    public function test_twenty_codes_a_minute_are_checked_for_a_visitor_and_another_visitor_or_guard_is_not_held_up(): void
    {
        config(['atrium-2fa.guards.admin.magic_code' => true]);
        $this->person();
        $this->admin();
        $this->send('olga@example.org');
        $this->send('admin@example.com', guard: 'admin.two-factor');

        foreach (range(1, 20) as $n) {
            $this->check('000000')->assertSessionHasErrors('code');
            $this->assertNotSame($this->throttled(), session('errors')->first('code'), "try {$n}");
        }
        $this->check('000000')->assertSessionHasErrors(['code' => $this->throttled()]);

        $this->assertNotSame($this->throttled(), $this->check('000000', ip: '203.0.113.2')->baseResponse->getSession()?->get('errors')?->first('code'));
        $this->assertNotSame($this->throttled(), $this->check('000000', guard: 'admin.two-factor')->baseResponse->getSession()?->get('errors')?->first('code'));
    }

    public function test_a_wrong_code_is_logged_with_the_reason(): void
    {
        $person = $this->person();
        $this->send('olga@example.org');

        $this->check('000000');

        $event = Event::query()->where('event', 'failed')->sole();
        $this->assertSame(['people', $person->id, 'code', ['reason' => 'wrong']], [$event->guard, (int) $event->user_id, $event->method, $event->meta]);
    }

    public function test_after_signing_in_the_address_is_forgotten_and_the_browser_is_not_remembered(): void
    {
        $person = $this->person();
        $this->send('olga@example.org');
        $code = null;
        Mail::assertSent(CodeMail::class, function (CodeMail $mail) use (&$code): bool {
            $code = $mail->code;

            return true;
        });

        $response = $this->check((string) $code)->assertRedirect(route('people.home'));

        $this->assertAuthenticatedAs($person, 'people');
        $this->assertFalse(session()->has('atrium-2fa.magic.people'));
        $response->assertCookieMissing(Auth::guard('people')->getRecallerName());
    }
}

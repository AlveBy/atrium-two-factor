<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Tests\Feature;

use Atrium\TwoFactor\Mail\CodeMail;
use Atrium\TwoFactor\Models\Code;
use Atrium\TwoFactor\Models\Event;
use Atrium\TwoFactor\Tests\TestCase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Signing in with a code by e-mail instead of a password, on the "people"
 * guard (a personal account).
 */
final class MagicCodeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->travelTo(Carbon::createFromTimestamp(1_800_000_015));
    }

    private function send(string $email): TestResponse
    {
        // The mail goes out once the response is on its way (deferred).
        return $this->post(route('atrium-2fa.people.magic.send'), ['email' => $email]);
    }

    private function mailedCode(): string
    {
        $code = null;
        Mail::assertSent(CodeMail::class, function (CodeMail $mail) use (&$code): bool {
            $code = $mail->code;

            return $mail->purpose === 'magic';
        });

        return (string) $code;
    }

    public function test_the_code_from_the_mail_signs_in(): void
    {
        $person = $this->person();

        $this->get(route('atrium-2fa.people.magic'))->assertOk()->assertSee('type="email"', false);
        $this->send('olga@example.org')->assertRedirect(route('atrium-2fa.people.magic.code'));
        Mail::assertSent(CodeMail::class, fn (CodeMail $m) => $m->hasTo('olga@example.org'));

        $this->get(route('atrium-2fa.people.magic.code'))->assertOk()->assertSee('olga@example.org');
        $this->post(route('atrium-2fa.people.magic.verify'), ['code' => $this->mailedCode()])->assertRedirect(route('people.home'));

        $this->assertAuthenticatedAs($person, 'people');
        $this->assertSame('code', Event::query()->where('event', 'signed_in')->value('method'));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function spellings(): array
    {
        return [
            'capitals' => ['OLGA@EXAMPLE.ORG'],
            'spaces around' => ['  olga@example.org  '],
            'mixed' => ['Olga@Example.org'],
        ];
    }

    #[DataProvider('spellings')]
    public function test_the_address_is_found_however_it_is_typed(string $typed): void
    {
        $person = $this->person();

        $this->send($typed);
        $this->post(route('atrium-2fa.people.magic.verify'), ['code' => $this->mailedCode()]);

        $this->assertAuthenticatedAs($person, 'people');
    }

    public function test_an_unknown_address_gets_exactly_the_same_answer_and_no_mail(): void
    {
        $this->person();

        $known = $this->send('olga@example.org');
        $this->flushSession();
        $unknown = $this->send('nobody@example.org');

        $this->assertSame($known->headers->get('Location'), $unknown->headers->get('Location'));
        $this->assertSame(session('atrium-2fa.status'), __('atrium-2fa::two-factor.magic.sent'));
        Mail::assertSentCount(1);
        $this->assertSame(1, Event::query()->where('event', 'code_sent')->count());

        // The page that follows reads the same too.
        $this->get(route('atrium-2fa.people.magic.code'))->assertOk()->assertSee('nobody@example.org');
    }

    public function test_wrong_late_and_repeated_tries_answer_the_same_for_unknown_addresses(): void
    {
        $this->person();
        $answers = [];

        foreach (['olga@example.org', 'nobody@example.org'] as $email) {
            $this->flushSession();
            $this->travelTo(Carbon::createFromTimestamp(1_800_000_015));
            $this->send($email);

            $wrong = $this->post(route('atrium-2fa.people.magic.verify'), ['code' => '000000']);
            $this->travel(11)->minutes();
            $late = $this->post(route('atrium-2fa.people.magic.verify'), ['code' => '000000']);
            $tooSoon = $this->send($email);

            $answers[$email] = [
                session('errors')?->first('code'),
                $wrong->headers->get('Location'),
                $late->headers->get('Location'),
                $tooSoon->headers->get('Location'),
            ];
        }

        $this->assertSame($answers['olga@example.org'], $answers['nobody@example.org']);
        $this->assertGuest('people');
    }

    public function test_an_unknown_address_takes_about_as_long_as_a_real_one(): void
    {
        $this->person();
        // The mail goes after the answer (defer), which a test runs inside
        // the request: a fake mailer keeps it out of the measurement.
        Mail::fake();
        $time = function (string $email): float {
            $runs = [];

            foreach (range(1, 7) as $ignored) {
                Code::query()->delete();
                Cache::flush();
                $start = hrtime(true);
                $this->post(route('atrium-2fa.people.magic.send'), ['email' => $email]);
                $runs[] = hrtime(true) - $start;
            }

            sort($runs);

            return (float) $runs[3];
        };

        $time('olga@example.org');
        $known = $time('olga@example.org');
        $unknown = $time('nobody@example.org');

        // Both hash a code; neither sends mail before answering.
        $this->assertLessThan(3.0, max($known, $unknown) / max(1.0, min($known, $unknown)));
        $this->assertSame(1, Code::query()->count(), 'an unknown address gets a code mailed to nobody');
    }

    public function test_a_code_works_once(): void
    {
        $person = $this->person();
        $this->send('olga@example.org');
        $code = $this->mailedCode();

        $this->post(route('atrium-2fa.people.magic.verify'), ['code' => $code]);
        $this->assertAuthenticatedAs($person, 'people');
        $this->app['auth']->guard('people')->logout();

        $this->withSession(['atrium-2fa.magic.people' => 'olga@example.org'])
            ->post(route('atrium-2fa.people.magic.verify'), ['code' => $code])
            ->assertSessionHasErrors(['code' => __('atrium-2fa::two-factor.magic.expired')]);
        $this->assertGuest('people');
    }

    public function test_a_code_expires_after_its_minutes(): void
    {
        $this->person();
        $this->send('olga@example.org');
        $code = $this->mailedCode();

        $this->travel(10 * 60 + 1)->seconds();
        $this->post(route('atrium-2fa.people.magic.verify'), ['code' => $code])
            ->assertSessionHasErrors(['code' => __('atrium-2fa::two-factor.magic.expired')]);
        $this->assertGuest('people');
    }

    public function test_a_code_sent_to_one_address_does_not_open_another(): void
    {
        $this->person();
        $this->person(['email' => 'ivan@example.org']);
        $this->send('olga@example.org');
        $code = $this->mailedCode();

        $this->withSession(['atrium-2fa.magic.people' => 'ivan@example.org'])
            ->post(route('atrium-2fa.people.magic.verify'), ['code' => $code])
            ->assertSessionHasErrors('code');
        $this->assertGuest('people');
    }

    public function test_five_wrong_tries_burn_the_code(): void
    {
        $this->person();
        $this->send('olga@example.org');
        $code = $this->mailedCode();

        foreach (range(1, 5) as $ignored) {
            $this->post(route('atrium-2fa.people.magic.verify'), ['code' => '000000'])
                ->assertSessionHasErrors(['code' => __('atrium-2fa::two-factor.challenge.code_invalid')]);
        }

        $this->post(route('atrium-2fa.people.magic.verify'), ['code' => $code])
            ->assertSessionHasErrors(['code' => __('atrium-2fa::two-factor.magic.too_many')]);
        $this->assertGuest('people');
        $this->assertSame(6, Event::query()->where('event', 'failed')->count());
    }

    public function test_sending_is_limited_per_address_and_per_visitor(): void
    {
        $this->person();
        $this->send('olga@example.org');
        $this->send('olga@example.org');
        Mail::assertSentCount(1);

        foreach (range(1, 8) as $n) {
            $this->send("guess{$n}@example.org")->assertSessionHasNoErrors();
        }

        $this->send('one-too-many@example.org')->assertSessionHasErrors('email');
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function badAddresses(): array
    {
        return [
            'empty' => [''],
            'no at sign' => ['olga.example.org'],
            'too long' => [str_repeat('a', 250).'@example.org'],
        ];
    }

    #[DataProvider('badAddresses')]
    public function test_something_that_is_not_an_address_is_refused(mixed $email): void
    {
        $this->send($email)->assertSessionHasErrors('email');
        Mail::assertNothingSent();
    }

    public function test_entering_a_code_without_asking_for_one_leads_back(): void
    {
        $this->get(route('atrium-2fa.people.magic.code'))->assertRedirect(route('atrium-2fa.people.magic'));
        $this->post(route('atrium-2fa.people.magic.verify'), ['code' => '123456'])->assertRedirect(route('atrium-2fa.people.magic'));
    }

    public function test_an_empty_code_is_asked_for(): void
    {
        $this->withSession(['atrium-2fa.magic.people' => 'olga@example.org'])
            ->post(route('atrium-2fa.people.magic.verify'), ['code' => ''])
            ->assertSessionHasErrors(['code' => __('atrium-2fa::two-factor.challenge.code_required')]);
    }

    public function test_an_app_still_asks_for_its_code_after_the_mail_and_the_mail_is_not_offered_again(): void
    {
        $person = $this->person();
        $this->totp($person, 'people');
        $this->send('olga@example.org');

        $this->post(route('atrium-2fa.people.magic.verify'), ['code' => $this->mailedCode()])
            ->assertRedirect(route('atrium-2fa.people.challenge'));
        $this->assertGuest('people');

        $this->get(route('atrium-2fa.people.challenge'))->assertOk()->assertDontSee('dusk="a2f-switch-email"', false);
        $this->post(route('atrium-2fa.people.challenge.verify'), ['code' => $this->code()])->assertRedirect(route('people.home'));
        $this->assertAuthenticatedAs($person, 'people');
    }

    public function test_the_panel_has_no_code_sign_in_unless_switched_on(): void
    {
        $this->get(route('admin.two-factor.magic'))->assertNotFound();
        $this->post(route('admin.two-factor.magic.send'), ['email' => 'admin@example.com'])->assertNotFound();

        config(['atrium-2fa.guards.admin.magic_code' => true]);
        $this->get(route('admin.two-factor.magic'))->assertOk();
    }

    public function test_the_login_screen_offers_only_the_ways_that_are_on(): void
    {
        $this->get(route('admin.login'))->assertInertia(fn ($page) => $page->where('methods', []));

        config(['atrium-2fa.guards.admin.magic_code' => true]);
        $this->get(route('admin.login'))->assertInertia(fn ($page) => $page->has('methods', 1)->where('methods.0.key', 'code'));

        config(['atrium-2fa.guards.admin.passwordless' => true]);
        $this->get(route('admin.login'))->assertInertia(fn ($page) => $page->has('methods', 2)->where('methods.0.key', 'passkey'));
    }
}

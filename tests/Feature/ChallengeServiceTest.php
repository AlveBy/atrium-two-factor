<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Tests\Feature;

use Atrium\TwoFactor\Contracts\Factor as FactorContract;
use Atrium\TwoFactor\Factors;
use Atrium\TwoFactor\Models\Event;
use Atrium\TwoFactor\Models\Factor;
use Atrium\TwoFactor\Services\Challenge;
use Atrium\TwoFactor\Services\Policy;
use Atrium\TwoFactor\Tests\Support\Person;
use Atrium\TwoFactor\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The second step from the inside: what a pending sign-in remembers, which
 * factors it may switch between, and what is written to the log.
 */
final class ChallengeServiceTest extends TestCase
{
    private Request $request;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->travelTo(Carbon::createFromTimestamp(1_800_000_015));
        $this->request = Request::create('/people/login', 'POST');
        $this->request->setLaravelSession($this->app['session.store']);
    }

    private function challenge(): Challenge
    {
        return $this->app->make(Challenge::class);
    }

    private function policy(): Policy
    {
        return $this->app->make(Policy::class);
    }

    private function confirmed(Person $person, string $type, ?string $secret = null): void
    {
        Factor::query()->create([
            'guard' => 'people',
            'user_id' => $person->id,
            'type' => $type,
            'secret' => $secret,
            'confirmed_at' => Carbon::now(),
        ]);
    }

    /**
     * @param  list<FactorContract>  $factors
     * @return list<string>
     */
    private function keys(array $factors): array
    {
        $this->assertTrue(array_is_list($factors), 'a list, numbered from nought');

        return array_map(fn (FactorContract $factor): string => $factor->key(), $factors);
    }

    public function test_a_code_by_e_mail_as_the_first_step_leaves_e_mail_out_of_the_second(): void
    {
        $person = $this->person();
        $this->totp($person, 'people');
        $this->confirmed($person, 'email');

        $this->assertNotNull(($this->challenge())($this->request, $person, 'people', 'code', false));

        $this->assertSame(['email'], $this->challenge()->pending($this->request, 'people')['except'] ?? null);
        $this->assertSame(['totp'], $this->keys($this->challenge()->factors($this->request, 'people', $person)));
    }

    public function test_a_password_as_the_first_step_leaves_every_factor_in_the_second(): void
    {
        $person = $this->person();
        $this->totp($person, 'people');
        $this->confirmed($person, 'email');

        ($this->challenge())($this->request, $person, 'people', 'password', false);

        $this->assertSame([], $this->challenge()->pending($this->request, 'people')['except'] ?? null);
        $this->assertSame(['totp', 'email'], $this->keys($this->challenge()->factors($this->request, 'people', $person)));
    }

    public function test_the_log_says_which_factor_was_asked_for(): void
    {
        $person = $this->person();
        $this->totp($person, 'people');

        ($this->challenge())($this->request, $person, 'people', 'password', false);

        $this->assertSame(['factor' => 'totp'], Event::query()->where('event', 'challenged')->sole()->meta);
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function brokenPending(): array
    {
        return [
            'no factor' => [['id' => 1, 'remember' => false, 'method' => 'password']],
            'no person' => [['factor' => 'totp', 'remember' => false, 'method' => 'password']],
            'not a list at all' => ['1'],
        ];
    }

    #[DataProvider('brokenPending')]
    public function test_a_pending_sign_in_missing_a_part_is_no_pending_sign_in(mixed $pending): void
    {
        $this->person();
        $this->request->session()->put('atrium-2fa.pending.people', $pending);

        $this->assertNull($this->challenge()->pending($this->request, 'people'));
        $this->assertNull($this->challenge()->user($this->request, 'people'));
    }

    public function test_only_a_code_actually_sent_is_logged_as_sent(): void
    {
        $person = $this->person();
        $email = $this->app->make(Factors::class)->get('email');
        $this->assertNotNull($email);

        $this->assertTrue($this->challenge()->prepare($this->request, 'people', $person, $email));
        $this->assertFalse($this->challenge()->prepare($this->request, 'people', $person, $email), 'too soon for another');

        $this->assertSame(1, Event::query()->where('event', 'code_sent')->count());
    }

    public function test_an_app_or_a_passkey_sends_nothing_and_logs_nothing_as_sent(): void
    {
        $person = $this->person();

        foreach (['totp', 'passkey'] as $key) {
            $factor = $this->app->make(Factors::class)->get($key);
            $this->assertNotNull($factor);
            $this->assertTrue($this->challenge()->prepare($this->request, 'people', $person, $factor), $key);
        }

        $this->assertSame(0, Event::query()->where('event', 'code_sent')->count());
    }

    public function test_the_factors_set_up_come_as_a_list_in_the_order_of_preference(): void
    {
        $person = $this->person();
        $this->confirmed($person, 'sms', '+375291234567');

        $this->assertSame(['sms'], $this->keys($this->policy()->enrolled('people', $person)));

        $this->totp($person, 'people');
        $this->assertSame(['totp', 'sms'], $this->keys($this->policy()->enrolled('people', $person)));
    }

    public function test_factors_already_used_are_left_out_and_the_rest_renumbered(): void
    {
        $person = $this->person();
        $this->totp($person, 'people');
        $this->confirmed($person, 'email');

        $this->assertSame(['totp'], $this->keys($this->policy()->factors('people', $person, ['email'])));
        $this->assertSame(['email'], $this->keys($this->policy()->factors('people', $person, ['totp'])));
        $this->assertSame(['totp', 'email'], $this->keys($this->policy()->factors('people', $person)));
    }

    public function test_nobody_is_sent_to_set_a_factor_up_where_the_second_step_is_optional(): void
    {
        $person = $this->person();

        $this->assertFalse($this->policy()->mustEnrol('people', $person));
        $this->assertNull(($this->challenge())($this->request, $person, 'people', 'password', false));
        $this->assertFalse($this->request->session()->has('atrium-2fa.enrol.people'));

        config(['atrium-2fa.guards.people.required' => 'all', 'atrium-2fa.guards.people.factors' => ['totp']]);
        $this->assertTrue($this->policy()->mustEnrol('people', $person));
    }
}

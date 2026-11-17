<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Tests\Feature;

use Atrium\TwoFactor\Models\Factor;
use Atrium\TwoFactor\Services\Passkeys;
use Atrium\TwoFactor\Tests\Support\SoftAuthenticator;
use Atrium\TwoFactor\Tests\TestCase;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * What the browser is told when it is asked for a passkey, and the parts of
 * its answer that are checked one by one.
 */
final class PasskeyDetailsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    /**
     * @return array<string,mixed>
     */
    private function registrationOptions(Authenticatable $user, string $guard = 'admin', string $route = 'admin.two-factor'): array
    {
        $this->actingAs($user, $guard);

        return $this->postJson(route("{$route}.passkeys.options"))->assertOk()->json();
    }

    private function register(Authenticatable $user, SoftAuthenticator $key, string $guard = 'admin', string $route = 'admin.two-factor', string $name = 'Laptop'): Factor
    {
        $options = $this->registrationOptions($user, $guard, $route);
        $this->postJson(route("{$route}.passkeys.store"), [...$key->create($options), 'name' => $name])->assertOk();
        $this->app['auth']->guard($guard)->logout();
        $this->app['auth']->forgetGuards();

        return Factor::query()->where('type', 'passkey')->latest('id')->firstOrFail();
    }

    public function test_the_browser_is_told_who_the_passkey_is_for(): void
    {
        $admin = $this->admin();

        $options = $this->registrationOptions($admin)['publicKey'];

        $this->assertSame(SoftAuthenticator::b64(hash('sha256', 'admin|'.$admin->id, true)), $options['user']['id']);
        $this->assertSame('admin@example.com', $options['user']['name']);
        $this->assertSame('Admin', $options['user']['displayName']);
        $this->assertSame(['name' => 'Testing site', 'id' => 'localhost'], $options['rp']);
        $this->assertSame(60_000, $options['timeout']);
        $this->assertSame('preferred', $options['authenticatorSelection']['userVerification']);
        $this->assertSame('preferred', $options['authenticatorSelection']['residentKey']);
        $this->assertSame([], $options['excludeCredentials']);
    }

    public function test_the_same_person_id_differs_between_guards(): void
    {
        $admin = $this->admin();
        // The same number on purpose: sequences of real databases do not start over for every test.
        $person = $this->person();
        $person->forceFill(['id' => $admin->id])->save();
        $this->assertSame($admin->id, $person->id);

        $adminId = $this->registrationOptions($admin)['publicKey']['user']['id'];
        $personId = $this->registrationOptions($person, 'people', 'atrium-2fa.people')['publicKey']['user']['id'];

        $this->assertSame(SoftAuthenticator::b64(hash('sha256', 'people|'.$person->id, true)), $personId);
        $this->assertNotSame($adminId, $personId);
    }

    public function test_a_person_without_a_name_is_shown_by_e_mail_and_without_either_by_id(): void
    {
        $nameless = $this->person(['name' => '']);
        $options = $this->registrationOptions($nameless, 'people', 'atrium-2fa.people')['publicKey'];
        $this->assertSame(['olga@example.org', 'olga@example.org'], [$options['user']['name'], $options['user']['displayName']]);

        $anonymous = $this->person(['name' => '', 'email' => '']);
        $options = $this->registrationOptions($anonymous, 'people', 'atrium-2fa.people')['publicKey'];
        $this->assertSame([(string) $anonymous->id, (string) $anonymous->id], [$options['user']['name'], $options['user']['displayName']]);
    }

    public function test_the_relying_party_can_be_named_in_the_config_and_keys_are_checked_against_it(): void
    {
        config(['atrium-2fa.webauthn.rp_id' => 'example.test', 'atrium-2fa.webauthn.rp_name' => 'Example shop']);
        $admin = $this->admin();

        $options = $this->registrationOptions($admin);
        $this->assertSame(['name' => 'Example shop', 'id' => 'example.test'], $options['publicKey']['rp']);

        $this->postJson(route('admin.two-factor.passkeys.store'), (new SoftAuthenticator)->create($options))->assertStatus(422);

        $options = $this->registrationOptions($admin);
        $this->postJson(route('admin.two-factor.passkeys.store'), (new SoftAuthenticator('example.test', 'https://example.test'))->create($options))->assertOk();
        $this->assertSame(1, Factor::query()->count());
    }

    public function test_the_relying_party_is_the_host_the_site_is_opened_on(): void
    {
        $passkeys = $this->app->make(Passkeys::class);

        $this->assertSame('shop.test', $passkeys->rpId(Request::create('https://shop.test/admin')));

        config(['atrium-2fa.webauthn.rp_id' => 'example.test']);
        $this->assertSame('example.test', $passkeys->rpId(Request::create('https://shop.test/admin')));
    }

    /**
     * @return array<string,array{mixed,int}>
     */
    public static function timeouts(): array
    {
        return [
            'as configured' => [90, 90_000],
            'the shortest allowed' => [10, 10_000],
            'one above the shortest' => [11, 11_000],
            'too short is raised' => [9, 10_000],
            'zero is raised' => [0, 10_000],
            'negative is raised' => [-30, 10_000],
            'missing from the config' => [null, 60_000],
        ];
    }

    #[DataProvider('timeouts')]
    public function test_the_browser_waits_as_long_as_the_config_says_but_not_too_little(mixed $seconds, int $milliseconds): void
    {
        config(['atrium-2fa.webauthn' => $seconds === null ? [] : ['timeout' => $seconds]]);
        $person = $this->person();

        $this->assertSame($milliseconds, $this->registrationOptions($person, 'people', 'atrium-2fa.people')['publicKey']['timeout']);
        $this->app['auth']->guard('people')->logout();
        $this->assertSame($milliseconds, $this->postJson(route('atrium-2fa.people.passkey.options'))->json('publicKey.timeout'));
    }

    public function test_the_second_step_offers_only_the_person_s_own_confirmed_passkeys_over_every_transport(): void
    {
        $admin = $this->admin();
        $key = new SoftAuthenticator;
        $this->register($admin, $key);

        // Not confirmed (a set-up left halfway): never offered.
        Factor::query()->create([
            'guard' => 'admin', 'user_id' => $admin->id, 'type' => 'passkey', 'credential_hash' => hash('sha256', 'half'),
            'data' => ['credential_id' => base64_encode('half'), 'public_key' => ''],
        ]);

        $this->signInWithPassword();
        $options = $this->postJson(route('admin.two-factor.challenge.options'))->assertOk()->json('publicKey');

        $this->assertSame([[
            'id' => SoftAuthenticator::b64($key->credentialId),
            'transports' => ['usb', 'nfc', 'ble', 'hybrid', 'internal'],
            'type' => 'public-key',
        ]], $options['allowCredentials']);
        $this->assertSame('preferred', $options['userVerification']);
        $this->assertSame('localhost', $options['rpId']);
        $this->assertSame(60_000, $options['timeout']);
    }

    public function test_only_confirmed_passkeys_are_kept_from_being_registered_again(): void
    {
        $admin = $this->admin();
        $key = new SoftAuthenticator;
        $this->register($admin, $key);
        Factor::query()->create([
            'guard' => 'admin', 'user_id' => $admin->id, 'type' => 'passkey', 'credential_hash' => hash('sha256', 'half'),
            'data' => ['credential_id' => base64_encode('half'), 'public_key' => ''],
        ]);

        $exclude = $this->registrationOptions($admin)['publicKey']['excludeCredentials'];

        $this->assertSame([SoftAuthenticator::b64($key->credentialId)], array_column($exclude, 'id'));
    }

    public function test_a_passkey_alone_lets_the_browser_offer_any_key_but_needs_the_pin(): void
    {
        $options = $this->postJson(route('atrium-2fa.people.passkey.options'))->assertOk()->json('publicKey');

        $this->assertArrayNotHasKey('allowCredentials', $options);
        $this->assertSame('required', $options['userVerification']);
    }

    public function test_a_key_that_did_not_check_for_a_pin_may_still_be_registered_but_one_nobody_touched_may_not(): void
    {
        $admin = $this->admin();

        $options = $this->registrationOptions($admin);
        $this->postJson(route('admin.two-factor.passkeys.store'), (new SoftAuthenticator)->create($options, userPresent: false))->assertStatus(422);

        $options = $this->registrationOptions($admin);
        $this->postJson(route('admin.two-factor.passkeys.store'), (new SoftAuthenticator)->create($options, userVerified: false))->assertOk();

        $this->assertSame(1, Factor::query()->count());
    }

    public function test_the_key_s_counter_is_kept_from_the_start(): void
    {
        $key = new SoftAuthenticator;
        $key->counter = 7;

        $this->assertSame(7, $this->register($this->admin(), $key)->counter);
    }

    /**
     * @return array<string,array{string,?string}>
     */
    public static function names(): array
    {
        return [
            'plain' => ['Laptop', 'Laptop'],
            'spaces around' => ['  Phone  ', 'Phone'],
            'only spaces' => ['   ', null],
            'empty' => ['', null],
            'longest kept' => [str_repeat('a', 100), str_repeat('a', 100)],
            'one too long is cut' => [str_repeat('a', 101), str_repeat('a', 100)],
            'Cyrillic is cut by letters, not bytes' => [str_repeat('ж', 120), str_repeat('ж', 100)],
            'emoji' => ['Key 🔑', 'Key 🔑'],
        ];
    }

    #[DataProvider('names')]
    public function test_the_passkey_keeps_the_name_it_was_given(string $name, ?string $stored): void
    {
        $this->assertSame($stored, $this->register($this->admin(), new SoftAuthenticator, name: $name)->name);
    }

    /**
     * @return array<string,array{string}>
     */
    public static function missingParts(): array
    {
        return [
            'the id' => ['id'],
            'the client data' => ['response.clientDataJSON'],
            'the authenticator data' => ['response.authenticatorData'],
            'the signature' => ['response.signature'],
        ];
    }

    #[DataProvider('missingParts')]
    public function test_an_answer_missing_a_part_is_refused(string $part): void
    {
        $person = $this->person();
        $key = new SoftAuthenticator;
        $this->register($person, $key, 'people', 'atrium-2fa.people');

        $options = $this->postJson(route('atrium-2fa.people.passkey.options'))->json();
        $answer = $key->get($options);

        if ($part === 'id') {
            unset($answer['id'], $answer['rawId']);
        } else {
            data_set($answer, $part, '');
        }

        $this->postJson(route('atrium-2fa.people.passkey.verify'), $answer)->assertStatus(422);
        $this->assertGuest('people');
    }

    #[DataProvider('missingParts')]
    public function test_an_answer_with_a_part_that_is_not_text_is_refused(string $part): void
    {
        $person = $this->person();
        $key = new SoftAuthenticator;
        $this->register($person, $key, 'people', 'atrium-2fa.people');

        $options = $this->postJson(route('atrium-2fa.people.passkey.options'))->json();
        $answer = $key->get($options);
        data_set($answer, $part === 'id' ? 'rawId' : $part, ['not', 'text']);

        if ($part === 'id') {
            unset($answer['id']);
        }

        $this->postJson(route('atrium-2fa.people.passkey.verify'), $answer)->assertStatus(422);
        $this->assertGuest('people');
    }

    public function test_the_id_alone_is_enough_without_the_raw_id(): void
    {
        $person = $this->person();
        $key = new SoftAuthenticator;
        $this->register($person, $key, 'people', 'atrium-2fa.people');

        $options = $this->postJson(route('atrium-2fa.people.passkey.options'))->json();
        $answer = $key->get($options);
        unset($answer['rawId']);

        $this->postJson(route('atrium-2fa.people.passkey.verify'), $answer)->assertOk();
        $this->assertAuthenticatedAs($person, 'people');
    }

    public function test_an_answer_without_asking_for_options_first_is_refused(): void
    {
        $person = $this->person();
        $key = new SoftAuthenticator;
        $this->register($person, $key, 'people', 'atrium-2fa.people');

        $answer = $key->get(['publicKey' => ['challenge' => SoftAuthenticator::b64(random_bytes(32))]]);

        $this->postJson(route('atrium-2fa.people.passkey.verify'), $answer)->assertStatus(422);
        $this->assertGuest('people');
    }

    public function test_a_passkey_set_up_for_the_panel_does_not_open_the_site_account_with_the_same_id(): void
    {
        $admin = $this->admin();
        // The same number on purpose: sequences of real databases do not start over for every test.
        $person = $this->person();
        $person->forceFill(['id' => $admin->id])->save();
        $this->assertSame($admin->id, $person->id);
        $key = new SoftAuthenticator;
        $this->register($admin, $key);

        $options = $this->postJson(route('atrium-2fa.people.passkey.options'))->json();
        $this->postJson(route('atrium-2fa.people.passkey.verify'), $key->get($options))->assertStatus(422);
        $this->assertGuest('people');
    }

    public function test_a_passkey_that_was_never_confirmed_does_not_sign_in(): void
    {
        $person = $this->person();
        $key = new SoftAuthenticator;
        $this->register($person, $key, 'people', 'atrium-2fa.people')->forceFill(['confirmed_at' => null])->save();

        $options = $this->postJson(route('atrium-2fa.people.passkey.options'))->json();
        $this->postJson(route('atrium-2fa.people.passkey.verify'), $key->get($options))->assertStatus(422);
        $this->assertGuest('people');
    }

    public function test_a_counter_that_did_not_move_is_refused(): void
    {
        $person = $this->person();
        $key = new SoftAuthenticator;
        $this->register($person, $key, 'people', 'atrium-2fa.people');

        $options = $this->postJson(route('atrium-2fa.people.passkey.options'))->json();
        $this->postJson(route('atrium-2fa.people.passkey.verify'), $key->get($options, counter: 1))->assertOk();
        $this->assertSame(1, Factor::query()->sole()->counter);
        $this->app['auth']->guard('people')->logout();
        $this->app['auth']->forgetGuards();

        $options = $this->postJson(route('atrium-2fa.people.passkey.options'))->json();
        $this->postJson(route('atrium-2fa.people.passkey.verify'), $key->get($options, counter: 1))->assertStatus(422);
        $this->assertGuest('people');
    }

    public function test_a_key_without_a_counter_signs_in_again_and_again_and_its_use_is_noted(): void
    {
        $person = $this->person();
        $key = new SoftAuthenticator;
        $this->register($person, $key, 'people', 'atrium-2fa.people');
        $this->travelTo(Carbon::parse('2026-11-20 10:00:00'));

        foreach ([1, 2] as $ignored) {
            $options = $this->postJson(route('atrium-2fa.people.passkey.options'))->json();
            $this->postJson(route('atrium-2fa.people.passkey.verify'), $key->get($options, counter: 0))->assertOk();
            $this->assertAuthenticatedAs($person, 'people');
            $this->app['auth']->guard('people')->logout();
            $this->app['auth']->forgetGuards();
        }

        $factor = Factor::query()->sole();
        $this->assertSame(0, $factor->counter);
        $this->assertSame('2026-11-20 10:00:00', $factor->last_used_at?->toDateTimeString());
    }
}

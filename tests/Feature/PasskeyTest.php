<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Tests\Feature;

use Atrium\Admin\Models\AdminUser;
use Atrium\TwoFactor\Models\Event;
use Atrium\TwoFactor\Models\Factor;
use Atrium\TwoFactor\Tests\Support\SoftAuthenticator;
use Atrium\TwoFactor\Tests\TestCase;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Mail;

final class PasskeyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    private function register(Authenticatable $user, SoftAuthenticator $key, string $guard = 'admin', string $route = 'admin.two-factor'): Factor
    {
        $this->actingAs($user, $guard);
        $options = $this->postJson(route("{$route}.passkeys.options"))->assertOk()->json();
        $this->postJson(route("{$route}.passkeys.store"), [...$key->create($options), 'name' => 'Laptop'])->assertOk()->assertJsonStructure(['redirect']);
        $this->app['auth']->guard($guard)->logout();
        $this->app['auth']->forgetGuards();

        return Factor::query()->where('type', 'passkey')->latest('id')->firstOrFail();
    }

    public function test_a_passkey_is_registered_from_the_security_page(): void
    {
        $admin = $this->admin();
        $key = new SoftAuthenticator;

        $this->actingAs($admin, 'admin');
        $options = $this->postJson(route('admin.two-factor.passkeys.options'))->assertOk()->json('publicKey');
        $this->assertSame('localhost', $options['rp']['id']);
        $this->assertSame('admin@example.com', $options['user']['name']);

        $factor = $this->register($admin, $key);

        $this->assertSame('Laptop', $factor->name);
        $this->assertSame(hash('sha256', $key->credentialId), $factor->credential_hash);
        $this->assertStringContainsString('BEGIN PUBLIC KEY', $factor->data['public_key']);
        $this->assertNotNull($factor->confirmed_at);
        $this->assertSame(1, Event::query()->where('event', 'factor_added')->where('method', 'passkey')->count());
    }

    public function test_registering_without_asking_for_options_first_fails(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $key = new SoftAuthenticator;

        $this->postJson(route('admin.two-factor.passkeys.store'), $key->create(['publicKey' => ['challenge' => SoftAuthenticator::b64(random_bytes(32))]]))
            ->assertStatus(422);
        $this->assertSame(0, Factor::query()->count());
    }

    public function test_without_options_asked_for_an_answer_to_an_empty_challenge_registers_nothing(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->postJson(route('admin.two-factor.passkeys.store'), (new SoftAuthenticator)->create(['publicKey' => ['challenge' => '']], ''))
            ->assertStatus(422);
        $this->assertSame(0, Factor::query()->count());
    }

    public function test_without_options_asked_for_an_answer_to_an_empty_challenge_passes_no_second_step(): void
    {
        $key = new SoftAuthenticator;
        $this->register($this->admin(), $key);
        $this->signInWithPassword()->assertRedirect(route('admin.two-factor.challenge'));

        $this->postJson(route('admin.two-factor.challenge.verify'), $key->get(['publicKey' => ['challenge' => '']]))->assertStatus(422);
        $this->assertGuest('admin');
    }

    public function test_without_options_asked_for_an_answer_to_an_empty_challenge_signs_nobody_in(): void
    {
        $key = new SoftAuthenticator;
        $this->register($this->person(), $key, 'people', 'atrium-2fa.people');

        $this->postJson(route('atrium-2fa.people.passkey.verify'), $key->get(['publicKey' => ['challenge' => '']]))->assertStatus(422);
        $this->assertGuest('people');
    }

    public function test_a_passkey_made_for_another_site_or_challenge_is_refused(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $options = $this->postJson(route('admin.two-factor.passkeys.options'))->json();
        $this->postJson(route('admin.two-factor.passkeys.store'), (new SoftAuthenticator('evil.test', 'https://evil.test'))->create($options))->assertStatus(422);

        $options = $this->postJson(route('admin.two-factor.passkeys.options'))->json();
        $this->postJson(route('admin.two-factor.passkeys.store'), (new SoftAuthenticator)->create($options, SoftAuthenticator::b64(random_bytes(32))))->assertStatus(422);

        $this->postJson(route('admin.two-factor.passkeys.store'), ['response' => ['clientDataJSON' => '!!!', 'attestationObject' => '']])->assertStatus(422);

        $this->assertSame(0, Factor::query()->count());
    }

    public function test_the_same_key_cannot_be_registered_twice(): void
    {
        $admin = $this->admin();
        $key = new SoftAuthenticator;
        $this->register($admin, $key);

        $this->actingAs($admin, 'admin');
        $options = $this->postJson(route('admin.two-factor.passkeys.options'))->json();
        $this->assertCount(1, $options['publicKey']['excludeCredentials']);
        $this->postJson(route('admin.two-factor.passkeys.store'), $key->create($options))->assertStatus(422);
        $this->assertSame(1, Factor::query()->count());
    }

    public function test_a_passkey_is_a_second_step_after_the_password(): void
    {
        $admin = $this->admin();
        $key = new SoftAuthenticator;
        $this->register($admin, $key);

        $this->signInWithPassword()->assertRedirect(route('admin.two-factor.challenge'));
        $this->get(route('admin.two-factor.challenge'))->assertOk()->assertSee('data-a2f-passkey="get"', false);

        $options = $this->postJson(route('admin.two-factor.challenge.options'))->assertOk()->json();
        $this->assertSame(SoftAuthenticator::b64($key->credentialId), $options['publicKey']['allowCredentials'][0]['id']);

        $this->postJson(route('admin.two-factor.challenge.verify'), $key->get($options, userVerified: false))
            ->assertOk()
            ->assertJson(['redirect' => route('admin.dashboard')]);
        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertSame(1, Factor::query()->sole()->counter);
    }

    public function test_another_persons_passkey_does_not_pass_my_second_step(): void
    {
        $this->admin();
        $other = $this->admin(['email' => 'other@example.com']);
        $theirs = new SoftAuthenticator;
        $this->register($other, $theirs);
        $mine = new SoftAuthenticator;
        $this->register(AdminUser::query()->where('email', 'admin@example.com')->sole(), $mine);

        $this->signInWithPassword();
        $options = $this->postJson(route('admin.two-factor.challenge.options'))->json();

        $this->postJson(route('admin.two-factor.challenge.verify'), $theirs->get($options))->assertStatus(422);
        $this->assertGuest('admin');
    }

    public function test_an_old_signature_or_a_counter_going_back_is_refused(): void
    {
        $admin = $this->admin();
        $key = new SoftAuthenticator;
        $this->register($admin, $key);

        $this->signInWithPassword();
        $options = $this->postJson(route('admin.two-factor.challenge.options'))->json();
        $answer = $key->get($options, counter: 5);

        // The challenge is used up by the first try, whatever its outcome.
        $this->postJson(route('admin.two-factor.challenge.verify'), $answer)->assertOk();
        $this->post(route('admin.logout'));
        $this->app['auth']->forgetGuards();

        $this->signInWithPassword();
        $this->postJson(route('admin.two-factor.challenge.verify'), $answer)->assertStatus(422);

        $options = $this->postJson(route('admin.two-factor.challenge.options'))->json();
        $this->postJson(route('admin.two-factor.challenge.verify'), $key->get($options, counter: 3))->assertStatus(422);
        $this->assertGuest('admin');
        $this->assertSame(5, Factor::query()->sole()->counter);
    }

    public function test_a_passkey_alone_signs_in_where_passwordless_is_on(): void
    {
        $person = $this->person();
        $key = new SoftAuthenticator;
        $this->register($person, $key, 'people', 'atrium-2fa.people');

        $this->get(route('atrium-2fa.people.passkey'))->assertOk()->assertSee('data-a2f-passkey="get"', false);
        $options = $this->postJson(route('atrium-2fa.people.passkey.options'))->assertOk()->json();
        $this->assertSame('required', $options['publicKey']['userVerification']);

        $this->postJson(route('atrium-2fa.people.passkey.verify'), $key->get($options))
            ->assertOk()
            ->assertJson(['redirect' => route('people.home')]);
        $this->assertAuthenticatedAs($person, 'people');
        $this->assertSame('passkey', Event::query()->where('event', 'signed_in')->value('method'));
    }

    public function test_without_the_pin_or_fingerprint_a_passkey_alone_is_not_enough(): void
    {
        $person = $this->person();
        $key = new SoftAuthenticator;
        $this->register($person, $key, 'people', 'atrium-2fa.people');

        $options = $this->postJson(route('atrium-2fa.people.passkey.options'))->json();
        $this->postJson(route('atrium-2fa.people.passkey.verify'), $key->get($options, userVerified: false))->assertStatus(422);
        $this->assertGuest('people');
    }

    public function test_an_unknown_key_and_a_flood_of_tries_are_refused(): void
    {
        $this->person();
        $stranger = new SoftAuthenticator;

        foreach (range(1, 10) as $ignored) {
            $options = $this->postJson(route('atrium-2fa.people.passkey.options'))->json();
            $this->postJson(route('atrium-2fa.people.passkey.verify'), $stranger->get($options))->assertStatus(422);
        }

        $options = $this->postJson(route('atrium-2fa.people.passkey.options'))->json();
        $this->postJson(route('atrium-2fa.people.passkey.verify'), $stranger->get($options))->assertStatus(429);
    }

    public function test_a_passkey_of_a_switched_off_account_does_not_sign_in(): void
    {
        $person = $this->person();
        $key = new SoftAuthenticator;
        $this->register($person, $key, 'people', 'atrium-2fa.people');
        $person->forceFill(['active' => false])->save();

        $options = $this->postJson(route('atrium-2fa.people.passkey.options'))->json();
        $this->postJson(route('atrium-2fa.people.passkey.verify'), $key->get($options))->assertStatus(422);
        $this->assertGuest('people');
        $this->assertSame('inactive', Event::query()->where('event', 'failed')->value('meta')['reason'] ?? null);
    }

    public function test_the_panel_has_no_passkey_only_sign_in_unless_switched_on(): void
    {
        $this->get(route('admin.two-factor.passkey'))->assertNotFound();
        $this->postJson(route('admin.two-factor.passkey.options'))->assertNotFound();
    }

    public function test_a_passkey_sign_in_skips_the_second_step(): void
    {
        $person = $this->person();
        $this->totp($person, 'people');
        $key = new SoftAuthenticator;
        $this->register($person, $key, 'people', 'atrium-2fa.people');

        $options = $this->postJson(route('atrium-2fa.people.passkey.options'))->json();
        $this->postJson(route('atrium-2fa.people.passkey.verify'), $key->get($options))->assertJson(['redirect' => route('people.home')]);
        $this->assertAuthenticatedAs($person, 'people');
    }
}

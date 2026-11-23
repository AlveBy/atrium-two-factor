<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Tests\Feature;

use Atrium\TwoFactor\Contracts\SmsDriver;
use Atrium\TwoFactor\Http\Controllers\SecurityController;
use Atrium\TwoFactor\Models\Event;
use Atrium\TwoFactor\Models\Factor;
use Atrium\TwoFactor\Models\RecoveryCode;
use Atrium\TwoFactor\Models\TrustedDevice;
use Atrium\TwoFactor\Support\Methods;
use Atrium\TwoFactor\Support\Person;
use Atrium\TwoFactor\Support\Qr;
use Atrium\TwoFactor\Support\Totp;
use Atrium\TwoFactor\Tests\TestCase;
use Illuminate\Foundation\Http\Middleware\TrimStrings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The security page: what it hands the template, and the details of every
 * action on it.
 */
final class SecurityDetailsTest extends TestCase
{
    /** @var list<array{string,string}> */
    private array $sent = [];

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->travelTo(Carbon::createFromTimestamp(1_800_000_015));
        $this->app->instance(SmsDriver::class, new class($this->sent) implements SmsDriver
        {
            /** @param list<array{string,string}> $sent */
            public function __construct(private array &$sent) {}

            public function send(string $phone, string $message): void
            {
                $this->sent[] = [$phone, $message];
            }
        });
    }

    public function test_the_page_tells_set_up_factors_from_ones_still_being_set_up(): void
    {
        $admin = $this->admin();
        $app = $this->totp($admin);
        $this->actingAs($admin, 'admin');
        $this->post(route('admin.two-factor.sms.start'), ['phone' => '+375291234567']);

        $this->get(route('admin.two-factor.security'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/TwoFactor/Security', false)
                ->has('factors', 1)
                ->where('factors.0.id', $app->id)
                ->where('factors.0.type', 'totp')
                ->where('factors.0.remove_url', route('admin.two-factor.factors.destroy', ['factor' => $app->id]))
                ->where('totp', null) // a phone being set up is not an app being set up
                ->where('sms.phone', Person::maskPhone('+375291234567'))
                ->where('mustEnrol', false));
    }

    public function test_an_app_being_set_up_gets_its_qr_code_and_a_phone_already_confirmed_is_not_being_set_up(): void
    {
        config(['atrium-2fa.totp.issuer' => 'Shop & Co']);
        $admin = $this->admin();
        $this->actingAs($admin, 'admin');
        Factor::query()->create(['guard' => 'admin', 'user_id' => $admin->id, 'type' => 'sms', 'secret' => '+375291234567', 'confirmed_at' => Carbon::now()]);
        $this->post(route('admin.two-factor.totp.start'));
        $pending = Factor::query()->where('type', 'totp')->sole();

        $this->get(route('admin.two-factor.security'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('totp.qr', Qr::svg(Totp::fromConfig()->uri((string) $pending->secret, 'Shop & Co', 'admin@example.com')))
                ->where('totp.secret', trim(chunk_split((string) $pending->secret, 4, ' ')))
                ->where('sms', null)
                ->has('factors', 1)
                ->where('factors.0.type', 'sms'));
    }

    public function test_without_an_issuer_in_the_config_the_app_shows_the_site_s_name(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'admin');
        $this->post(route('admin.two-factor.totp.start'));
        $pending = Factor::query()->sole();

        $this->get(route('admin.two-factor.security'))->assertInertia(fn (Assert $page) => $page
            ->where('totp.qr', Qr::svg(Totp::fromConfig()->uri((string) $pending->secret, 'Testing site', 'admin@example.com'))));
    }

    public function test_a_person_sent_to_set_a_factor_up_is_told_so(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')->withSession(['atrium-2fa.enrol.admin' => 1])->get(route('admin.two-factor.security'))
            ->assertInertia(fn (Assert $page) => $page->where('mustEnrol', true));
    }

    public function test_the_page_lists_the_ten_last_events_and_the_devices_still_trusted_newest_first(): void
    {
        $admin = $this->admin();
        foreach (range(1, 12) as $i) {
            Event::query()->create(['guard' => 'admin', 'user_id' => $admin->id, 'event' => 'signed_in', 'method' => 'm'.$i]);
        }
        Event::query()->create(['guard' => 'people', 'user_id' => $admin->id, 'event' => 'signed_in', 'method' => 'other guard']);
        foreach (['old' => -1, 'first' => 5, 'second' => 6] as $name => $days) {
            TrustedDevice::query()->create(['guard' => 'admin', 'user_id' => $admin->id, 'token_hash' => $name, 'name' => $name, 'expires_at' => Carbon::now()->addDays($days)]);
        }

        $this->actingAs($admin, 'admin')->get(route('admin.two-factor.security'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('events', fn ($events) => collect($events)->pluck('how')->all() === array_map(Methods::label(...), ['m12', 'm11', 'm10', 'm9', 'm8', 'm7', 'm6', 'm5', 'm4', 'm3']))
                ->where('events.0.who', null)
                ->where('devices', fn ($devices) => collect($devices)->pluck('name')->all() === ['second', 'first']));
    }

    public function test_starting_an_app_again_replaces_the_one_being_set_up(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'admin');

        $this->post(route('admin.two-factor.totp.start'));
        $this->post(route('admin.two-factor.totp.start'));

        $this->assertSame(1, Factor::query()->whereNull('confirmed_at')->count());
    }

    /**
     * @return array<string,array{?string,?string}>
     */
    public static function appNames(): array
    {
        return [
            'none' => [null, null],
            'empty' => ['', null],
            'spaces' => ['   ', null],
            'trimmed' => ['  My phone ', 'My phone'],
            'longest' => [str_repeat('я', 100), str_repeat('я', 100)],
        ];
    }

    #[DataProvider('appNames')]
    public function test_an_app_is_confirmed_with_or_without_a_name(?string $name, ?string $stored): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'admin');
        $this->post(route('admin.two-factor.totp.start'));
        $secret = (string) Factor::query()->sole()->secret;
        $totp = Totp::fromConfig();

        $this->post(route('admin.two-factor.totp.confirm'), ['code' => $totp->at($secret, $totp->step()), 'name' => $name])->assertSessionHasNoErrors();

        $factor = Factor::query()->sole();
        $this->assertSame($stored, $factor->name);
        $this->assertSame($totp->step(), $factor->counter);
        $this->assertSame(['name' => $stored], Event::query()->where('event', 'factor_added')->sole()->meta);
    }

    public function test_spaces_around_an_app_name_are_cut_off_even_when_the_site_does_not_trim_input(): void
    {
        TrimStrings::skipWhen(fn (): bool => true);
        $this->actingAs($this->admin(), 'admin');
        $this->post(route('admin.two-factor.totp.start'));
        $secret = (string) Factor::query()->sole()->secret;

        $this->post(route('admin.two-factor.totp.confirm'), ['code' => $this->code(0, $secret), 'name' => "  Laptop \t"])->assertSessionHasNoErrors();

        $this->assertSame('Laptop', Factor::query()->sole()->name);
    }

    public function test_an_app_name_that_is_too_long_is_refused(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'admin');
        $this->post(route('admin.two-factor.totp.start'));

        $this->post(route('admin.two-factor.totp.confirm'), ['code' => '123456', 'name' => str_repeat('a', 101)])->assertSessionHasErrors('name');
        $this->post(route('admin.two-factor.totp.confirm'), [])->assertSessionHasErrors('code');
        $this->assertNull(Factor::query()->sole()->confirmed_at);
    }

    /**
     * @return array<string,array{int,bool}>
     */
    public static function clockDrift(): array
    {
        return [
            'one step behind' => [-1, true],
            'one step ahead' => [1, true],
            'two steps behind' => [-2, false],
            'two steps ahead' => [2, false],
        ];
    }

    #[DataProvider('clockDrift')]
    public function test_confirming_an_app_forgives_a_clock_one_step_off_even_without_the_setting(int $offset, bool $accepted): void
    {
        config(['atrium-2fa.totp' => []]);
        $admin = $this->admin();
        $this->actingAs($admin, 'admin');
        $this->post(route('admin.two-factor.totp.start'));
        $secret = (string) Factor::query()->sole()->secret;

        $this->post(route('admin.two-factor.totp.confirm'), ['code' => $this->code($offset, $secret)]);

        $this->assertSame($accepted, Factor::query()->sole()->confirmed_at !== null);
    }

    public function test_a_security_page_left_without_its_auth_middleware_still_shows_nobody_anything(): void
    {
        Route::middleware('web')->get('stray/security', [SecurityController::class, 'index'])->defaults('guard', 'people');

        $this->get('stray/security')->assertForbidden();

        $this->actingAs($this->person(), 'people');
        $this->get('stray/security')->assertOk();
    }

    public function test_a_passkey_that_fails_says_why(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->postJson(route('admin.two-factor.passkeys.store'), ['response' => []])
            ->assertStatus(422)
            ->assertExactJson(['message' => __('atrium-2fa::two-factor.security.passkey_failed')]);
    }

    public function test_a_missing_phone_or_code_is_asked_for(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->post(route('admin.two-factor.sms.start'), [])
            ->assertSessionHasErrors(['phone' => __('atrium-2fa::two-factor.security.phone_invalid')]);
        $this->post(route('admin.two-factor.sms.start'), ['phone' => '+375291234567']);
        $this->post(route('admin.two-factor.sms.confirm'), [])->assertSessionHasErrors('code');
        $this->post(route('admin.two-factor.sms.confirm'), ['code' => str_repeat('1', 11)])->assertSessionHasErrors('code');

        $this->assertNull(Factor::query()->sole()->confirmed_at);
    }

    /**
     * @return array<string,array{string}>
     */
    public static function notPhones(): array
    {
        return ['letters' => ['abc-defg-hij'], 'too short' => ['123456'], 'too long' => [str_repeat('1', 21)], 'a sign inside' => ['+375+291234567']];
    }

    #[DataProvider('notPhones')]
    public function test_what_is_not_a_phone_number_is_refused_in_plain_words(string $phone): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->post(route('admin.two-factor.sms.start'), ['phone' => $phone])
            ->assertSessionHasErrors(['phone' => __('atrium-2fa::two-factor.security.phone_invalid')]);

        $this->assertSame(0, Factor::query()->count());
        $this->assertSame([], $this->sent);
    }

    public function test_removing_a_factor_logs_its_name_and_revoking_devices_logs_how_many(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'admin');
        $app = $this->totp($admin);
        $app->forceFill(['name' => 'Old phone'])->save();
        $otherPhone = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';
        $this->totp($admin, secret: $otherPhone);
        foreach (['a', 'b'] as $token) {
            TrustedDevice::query()->create(['guard' => 'admin', 'user_id' => $admin->id, 'token_hash' => $token, 'expires_at' => Carbon::now()->addDay()]);
        }

        $this->delete(route('admin.two-factor.factors.destroy', $app->id));
        $this->delete(route('admin.two-factor.devices.revoke'));

        $this->assertSame(['name' => 'Old phone'], Event::query()->where('event', 'factor_removed')->sole()->meta);
        $this->assertSame(['count' => 2], Event::query()->where('event', 'device_revoked')->sole()->meta);
        $this->assertSame(['admin', $admin->id], [Event::query()->where('event', 'device_revoked')->sole()->actor_guard, Event::query()->where('event', 'device_revoked')->sole()->actor_id]);
    }

    public function test_removing_a_factor_still_being_set_up_is_not_logged_and_keeps_the_recovery_codes(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'admin');
        $this->totp($admin);
        $this->post(route('admin.two-factor.recovery.regenerate'));
        $this->post(route('admin.two-factor.totp.start'));
        $pending = Factor::query()->whereNull('confirmed_at')->sole();

        $this->delete(route('admin.two-factor.factors.destroy', $pending->id))->assertSessionHasNoErrors();

        $this->assertNull($pending->fresh());
        $this->assertSame(0, Event::query()->where('event', 'factor_removed')->count());
        $this->assertSame(10, RecoveryCode::query()->count());
    }

    public function test_a_passkey_or_mail_switched_off_for_the_guard_cannot_be_added(): void
    {
        config(['atrium-2fa.guards.admin.factors' => ['totp']]);
        $this->actingAs($this->admin(), 'admin');

        $this->postJson(route('admin.two-factor.passkeys.store'), ['response' => []])->assertNotFound();
        $this->post(route('admin.two-factor.email.enable'))->assertNotFound();

        $this->assertSame(0, Factor::query()->count());
    }

    public function test_new_recovery_codes_are_logged_with_who_asked_for_them(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'admin');
        $this->totp($admin);

        $this->post(route('admin.two-factor.recovery.regenerate'))->assertSessionHas('atrium-2fa.recovery_codes');

        $event = Event::query()->where('event', 'recovery_generated')->sole();
        $this->assertSame(['admin', $admin->id, 'admin', $admin->id], [$event->guard, $event->user_id, $event->actor_guard, $event->actor_id]);
    }
}

<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Tests\Feature;

use Atrium\TwoFactor\Contracts\SmsDriver;
use Atrium\TwoFactor\Models\Event;
use Atrium\TwoFactor\Models\Factor;
use Atrium\TwoFactor\Models\RecoveryCode;
use Atrium\TwoFactor\Support\Totp;
use Atrium\TwoFactor\Tests\TestCase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;

final class SecurityPageTest extends TestCase
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

    public function test_the_page_is_only_for_people_who_are_signed_in(): void
    {
        $this->get(route('admin.two-factor.security'))->assertRedirect(route('admin.login'));
        $this->post(route('admin.two-factor.totp.start'))->assertRedirect(route('admin.login'));
    }

    public function test_an_app_is_set_up_with_a_qr_code_and_the_first_factor_brings_recovery_codes_once(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'admin');

        $this->post(route('admin.two-factor.totp.start'))->assertRedirect(route('admin.two-factor.security').'#totp');
        $pending = Factor::query()->sole();
        $this->assertNull($pending->confirmed_at);
        $this->assertNotSame($pending->secret, DB::table('auth_factors')->value('secret'), 'the secret is encrypted at rest');

        $this->get(route('admin.two-factor.security'))->assertOk()->assertSee('<svg', false)->assertSee(trim(chunk_split((string) $pending->secret, 4, ' ')));

        $code = Totp::fromConfig()->at((string) $pending->secret, Totp::fromConfig()->step());
        $this->post(route('admin.two-factor.totp.confirm'), ['code' => $code, 'name' => 'Work phone'])
            ->assertRedirect(route('admin.two-factor.security').'#recovery')
            ->assertSessionHas('atrium-2fa.recovery_codes');

        $codes = session('atrium-2fa.recovery_codes');
        $this->assertCount(10, $codes);
        $this->assertSame('Work phone', $pending->fresh()->name);
        $this->assertNotNull($pending->fresh()->confirmed_at);
        $this->assertSame(['factor_added', 'recovery_generated'], Event::query()->orderBy('id')->pluck('event')->all());

        $this->get(route('admin.two-factor.security'))->assertSee($codes[0]);
        // The next visit no longer shows them.
        $this->get(route('admin.two-factor.security'))->assertDontSee($codes[0]);
    }

    public function test_a_wrong_code_does_not_confirm_the_app(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $this->post(route('admin.two-factor.totp.start'));

        $this->post(route('admin.two-factor.totp.confirm'), ['code' => '000000'])->assertSessionHasErrors('totp');
        $this->post(route('admin.two-factor.totp.confirm'), [])->assertSessionHasErrors('code');

        $this->assertNull(Factor::query()->sole()->confirmed_at);
        $this->assertSame(0, RecoveryCode::query()->count());
    }

    public function test_confirming_without_starting_is_refused(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->post(route('admin.two-factor.totp.confirm'), ['code' => $this->code()])->assertSessionHasErrors('totp');
        $this->assertSame(0, Factor::query()->count());
    }

    public function test_a_factor_switched_off_for_the_guard_cannot_be_set_up(): void
    {
        config(['atrium-2fa.guards.admin.factors' => ['email']]);
        $this->actingAs($this->admin(), 'admin');

        $this->post(route('admin.two-factor.totp.start'))->assertNotFound();
        $this->post(route('admin.two-factor.sms.start'), ['phone' => '+375291234567'])->assertNotFound();
        $this->postJson(route('admin.two-factor.passkeys.options'))->assertNotFound();
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function phones(): array
    {
        return [
            'plain international' => ['+375291234567', '+375291234567'],
            'with spaces and brackets' => ['+7 (912) 345-67-89', '+79123456789'],
            'without a plus' => ['4930123456', '+4930123456'],
        ];
    }

    #[DataProvider('phones')]
    public function test_a_phone_is_confirmed_with_a_texted_code(string $typed, string $stored): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'admin');

        $this->post(route('admin.two-factor.sms.start'), ['phone' => $typed])->assertSessionHasNoErrors();
        $this->assertCount(1, $this->sent);
        $this->assertSame($stored, $this->sent[0][0]);
        preg_match('/\d{6}/', $this->sent[0][1], $m);

        $this->post(route('admin.two-factor.sms.confirm'), ['code' => $m[0]])->assertSessionHasNoErrors();

        $factor = Factor::query()->where('type', 'sms')->sole();
        $this->assertNotNull($factor->confirmed_at);
        $this->assertSame($stored, $factor->secret);
        $this->assertStringNotContainsString(substr($stored, 4, 4), (string) $factor->name, 'the label is masked');
    }

    /**
     * @return array<string, array{string}>
     */
    public static function badPhones(): array
    {
        return [
            'letters' => ['call me'],
            'too short' => ['+12345'],
            'too long' => ['+1234567890123456789012'],
            'empty' => [''],
        ];
    }

    #[DataProvider('badPhones')]
    public function test_a_phone_that_is_not_a_phone_is_refused(string $phone): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->post(route('admin.two-factor.sms.start'), ['phone' => $phone])->assertSessionHasErrors('phone');
        $this->assertSame([], $this->sent);
    }

    public function test_a_wrong_texted_code_and_a_quick_resend_are_refused(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $this->post(route('admin.two-factor.sms.start'), ['phone' => '+375291234567']);

        $this->post(route('admin.two-factor.sms.confirm'), ['code' => '000000'])->assertSessionHasErrors('sms');
        $this->post(route('admin.two-factor.sms.start'), ['phone' => '+375291234567'])->assertSessionHasErrors('sms');
        $this->assertCount(1, $this->sent);
        $this->assertNull(Factor::query()->where('type', 'sms')->sole()->confirmed_at);
    }

    public function test_email_as_a_factor_is_switched_on_once(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'admin');

        $this->post(route('admin.two-factor.email.enable'));
        $this->post(route('admin.two-factor.email.enable'));

        $this->assertSame(1, Factor::query()->where('type', 'email')->count());
        $this->assertSame('ad***@example.com', Factor::query()->where('type', 'email')->value('name'));
    }

    public function test_the_last_factor_of_a_person_who_must_have_one_is_not_removed(): void
    {
        config(['atrium-2fa.guards.admin.required' => 'all', 'atrium-2fa.guards.admin.factors' => ['totp', 'passkey']]);
        $admin = $this->admin();
        $app = $this->totp($admin);
        $this->actingAs($admin, 'admin');

        $this->delete(route('admin.two-factor.factors.destroy', $app->id))
            ->assertSessionHasErrors(['factors' => __('atrium-2fa::two-factor.security.last_factor')]);
        $this->assertNotNull($app->fresh());

        // With a second one, either can go.
        $otherPhone = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';
        $this->totp($admin, secret: $otherPhone);
        $this->delete(route('admin.two-factor.factors.destroy', $app->id))->assertSessionHasNoErrors();
        $this->assertNull($app->fresh());
        $this->assertSame(1, Event::query()->where('event', 'factor_removed')->count());
    }

    public function test_the_last_factor_goes_when_email_codes_remain_as_a_way_in(): void
    {
        config(['atrium-2fa.guards.admin.required' => 'all']);
        $admin = $this->admin();
        $app = $this->totp($admin);
        $this->actingAs($admin, 'admin');
        $this->post(route('admin.two-factor.recovery.regenerate'));
        $this->assertSame(10, RecoveryCode::query()->count());

        $this->delete(route('admin.two-factor.factors.destroy', $app->id))->assertSessionHasNoErrors();

        $this->assertNull($app->fresh());
        $this->assertSame(0, RecoveryCode::query()->count(), 'nothing left to recover');
    }

    public function test_a_person_can_remove_only_their_own_factors(): void
    {
        $owner = $this->admin();
        $app = $this->totp($owner);
        $this->actingAs($this->admin(['email' => 'intruder@example.com']), 'admin');

        $this->delete(route('admin.two-factor.factors.destroy', $app->id))->assertNotFound();
        $this->assertNotNull($app->fresh());
    }

    public function test_recovery_codes_are_renewed_only_with_a_factor_and_the_old_ones_stop_working(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'admin');

        $this->post(route('admin.two-factor.recovery.regenerate'))->assertSessionMissing('atrium-2fa.recovery_codes');
        $this->assertSame(0, RecoveryCode::query()->count());

        $this->totp($admin);
        $this->post(route('admin.two-factor.recovery.regenerate'));
        $first = session('atrium-2fa.recovery_codes');
        $this->post(route('admin.two-factor.recovery.regenerate'));
        $second = session('atrium-2fa.recovery_codes');

        $this->assertSame([], array_intersect($first, $second));
        $this->assertSame(10, RecoveryCode::query()->count());
    }

    public function test_the_security_page_shows_the_last_sign_ins(): void
    {
        $admin = $this->admin();
        $this->totp($admin);
        $this->signInWithPassword();
        $this->post(route('admin.two-factor.challenge.verify'), ['code' => '000000']);
        $this->post(route('admin.two-factor.challenge.verify'), ['code' => $this->code()]);

        $this->get(route('admin.two-factor.security'))
            ->assertOk()
            ->assertSee(__('atrium-2fa::two-factor.events.failed'))
            ->assertSee(__('atrium-2fa::two-factor.events.signed_in'));
    }
}

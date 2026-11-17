<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Tests\Feature;

use Atrium\TwoFactor\Models\Event;
use Atrium\TwoFactor\Tests\TestCase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;

final class TotpChallengeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        // In the middle of a 30-second step, so a step boundary is never crossed by accident.
        $this->travelTo(Carbon::createFromTimestamp(1_800_000_015));
    }

    public function test_an_admin_with_an_app_is_asked_for_its_code_not_for_an_email(): void
    {
        $admin = $this->admin();
        $this->totp($admin);

        $this->signInWithPassword()->assertRedirect(route('admin.two-factor.challenge'));
        Mail::assertNothingSent();

        $this->get(route('admin.two-factor.challenge'))->assertOk()->assertSee(__('atrium-2fa::two-factor.challenge.code_label.totp'));

        $this->post(route('admin.two-factor.challenge.verify'), ['code' => $this->code()])->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin, 'admin');
    }

    /**
     * @return array<string, array{int, bool}>
     */
    public static function clockSkew(): array
    {
        return [
            'phone exactly on time' => [0, true],
            'phone one step behind' => [-1, true],
            'phone one step ahead' => [1, true],
            'phone two steps behind' => [-2, false],
            'phone two steps ahead' => [2, false],
            'phone ten minutes off' => [20, false],
        ];
    }

    #[DataProvider('clockSkew')]
    public function test_a_phone_clock_a_little_off_is_forgiven_a_lot_off_is_not(int $steps, bool $accepted): void
    {
        $admin = $this->admin();
        $this->totp($admin);
        $this->signInWithPassword();

        $this->post(route('admin.two-factor.challenge.verify'), ['code' => $this->code($steps)]);

        $accepted ? $this->assertAuthenticatedAs($admin, 'admin') : $this->assertGuest('admin');
    }

    public function test_a_wider_window_from_the_config_is_honoured(): void
    {
        config(['atrium-2fa.totp.window' => 2]);
        $admin = $this->admin();
        $this->totp($admin);
        $this->signInWithPassword();

        $this->post(route('admin.two-factor.challenge.verify'), ['code' => $this->code(-2)]);

        $this->assertAuthenticatedAs($admin, 'admin');
    }

    /**
     * @return array<string, array{int, bool}>
     */
    public static function driftWithoutWindow(): array
    {
        return ['one step behind' => [-1, true], 'one step ahead' => [1, true], 'two steps behind' => [-2, false], 'two steps ahead' => [2, false]];
    }

    #[DataProvider('driftWithoutWindow')]
    public function test_without_a_window_in_the_config_a_clock_one_step_off_is_forgiven(int $steps, bool $accepted): void
    {
        config(['atrium-2fa.totp' => ['digits' => 6, 'period' => 30]]);
        $admin = $this->admin();
        $this->totp($admin);
        $this->signInWithPassword();

        $this->post(route('admin.two-factor.challenge.verify'), ['code' => $this->code($steps)]);

        $accepted ? $this->assertAuthenticatedAs($admin, 'admin') : $this->assertGuest('admin');
    }

    /**
     * @return array<string, array{string}>
     */
    public static function wrongCodes(): array
    {
        return [
            'all zeros' => ['000000'],
            'too short' => ['12345'],
            'too long' => ['1234567'],
            'letters' => ['abcdef'],
            'injection attempt' => ["1' OR '1'='1"],
            'unicode digits' => ['١٢٣٤٥٦'],
        ];
    }

    #[DataProvider('wrongCodes')]
    public function test_wrong_codes_are_refused(string $code): void
    {
        $this->totp($this->admin());
        $this->signInWithPassword();

        $this->post(route('admin.two-factor.challenge.verify'), ['code' => $code])->assertSessionHasErrors('code');

        $this->assertGuest('admin');
    }

    public function test_spaces_inside_a_code_do_not_matter(): void
    {
        $admin = $this->admin();
        $this->totp($admin);
        $this->signInWithPassword();
        $code = $this->code();

        $this->post(route('admin.two-factor.challenge.verify'), ['code' => substr($code, 0, 3).' '.substr($code, 3)]);

        $this->assertAuthenticatedAs($admin, 'admin');
    }

    public function test_a_code_that_was_used_once_does_not_work_again(): void
    {
        $admin = $this->admin();
        $this->totp($admin);
        $code = $this->code();

        $this->signInWithPassword();
        $this->post(route('admin.two-factor.challenge.verify'), ['code' => $code]);
        $this->assertAuthenticatedAs($admin, 'admin');

        $this->post(route('admin.logout'));
        $this->app['auth']->forgetGuards();

        // Same 30 seconds, same code, overheard: refused.
        $this->signInWithPassword();
        $this->post(route('admin.two-factor.challenge.verify'), ['code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest('admin');

        // An older code of the window is refused too: it is not newer than the used one.
        $this->post(route('admin.two-factor.challenge.verify'), ['code' => $this->code(-1)])->assertSessionHasErrors('code');
        $this->assertGuest('admin');

        // The next step's code is fine.
        $this->travel(30)->seconds();
        $this->post(route('admin.two-factor.challenge.verify'), ['code' => $this->code()]);
        $this->assertAuthenticatedAs($admin, 'admin');
    }

    public function test_after_five_wrong_codes_in_a_minute_even_the_right_one_waits(): void
    {
        $admin = $this->admin();
        $this->totp($admin);
        $this->signInWithPassword();

        foreach (range(1, 5) as $ignored) {
            $this->post(route('admin.two-factor.challenge.verify'), ['code' => '000000']);
        }

        $this->post(route('admin.two-factor.challenge.verify'), ['code' => $this->code()])
            ->assertSessionHasErrors(['code' => __('atrium-2fa::two-factor.challenge.throttle', ['seconds' => 60])]);
        $this->assertGuest('admin');

        $this->travel(61)->seconds();
        $this->post(route('admin.two-factor.challenge.verify'), ['code' => $this->code()]);
        $this->assertAuthenticatedAs($admin, 'admin');
    }

    public function test_ten_wrong_codes_lock_the_account_for_a_quarter_of_an_hour_from_anywhere(): void
    {
        $admin = $this->admin();
        $this->totp($admin);
        $this->signInWithPassword();

        foreach (range(1, 10) as $attempt) {
            if ($attempt === 6) {
                // A new address does not reset the count.
                $this->travel(61)->seconds();
                $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.7']);
            }
            $this->post(route('admin.two-factor.challenge.verify'), ['code' => '000000']);
        }

        $this->assertSame(1, Event::query()->where('event', 'locked')->count());

        $this->travel(5)->minutes();
        $this->post(route('admin.two-factor.challenge.verify'), ['code' => $this->code()])->assertSessionHasErrors('code');
        $this->assertGuest('admin');

        $this->travel(15)->minutes();
        $this->post(route('admin.two-factor.challenge.verify'), ['code' => $this->code()]);
        $this->assertAuthenticatedAs($admin, 'admin');
    }

    public function test_the_challenge_page_without_a_pending_sign_in_goes_back_to_the_login(): void
    {
        $this->get(route('admin.two-factor.challenge'))->assertRedirect(route('admin.login'));
        $this->post(route('admin.two-factor.challenge.verify'), ['code' => '123456'])->assertRedirect(route('admin.login'));
        $this->postJson(route('admin.two-factor.challenge.verify'), ['code' => '123456'])->assertStatus(419);
    }

    public function test_a_person_deactivated_while_waiting_is_not_let_in(): void
    {
        $admin = $this->admin();
        $this->totp($admin);
        $this->signInWithPassword();
        $admin->forceFill(['is_active' => false])->save();

        $this->post(route('admin.two-factor.challenge.verify'), ['code' => $this->code()])->assertRedirect(route('admin.login'));
        $this->assertGuest('admin');
    }

    public function test_cancelling_forgets_the_pending_sign_in(): void
    {
        $this->totp($this->admin());
        $this->signInWithPassword();

        $this->post(route('admin.two-factor.challenge.cancel'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.two-factor.challenge'))->assertRedirect(route('admin.login'));
    }

    public function test_remember_me_survives_the_second_step(): void
    {
        $admin = $this->admin();
        $this->totp($admin);
        $this->signInWithPassword(remember: true);

        $response = $this->post(route('admin.two-factor.challenge.verify'), ['code' => $this->code()]);

        $this->assertNotNull($admin->fresh()->getRememberToken());
        $this->assertTrue(collect($response->headers->getCookies())->contains(fn ($cookie) => str_starts_with($cookie->getName(), 'remember_admin')));
    }
}

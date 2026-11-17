<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Tests\Unit;

use Atrium\Admin\Models\Site;
use Atrium\Admin\Support\Sites;
use Atrium\TwoFactor\Mail\CodeMail;
use Atrium\TwoFactor\Services\Codes;
use Atrium\TwoFactor\Services\Lockout;
use Atrium\TwoFactor\Sms\LogSmsDriver;
use Atrium\TwoFactor\Support\Person;
use Atrium\TwoFactor\Support\Qr;
use Atrium\TwoFactor\Support\Totp;
use Atrium\TwoFactor\Tests\TestCase;
use Illuminate\Auth\GenericUser;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The small helpers in detail: the QR code's size and margin, the mail's
 * subject, the log driver with short numbers, names and addresses, base32
 * and the wait after a lockout.
 */
final class SupportDetailsTest extends TestCase
{
    public function test_the_qr_code_is_two_hundred_pixels_with_a_margin_of_two_modules(): void
    {
        $svg = Qr::svg('x');

        $this->assertStringContainsString('width="200" height="200" viewBox="0 0 200 200"', $svg);
        $this->assertStringContainsString('<g transform="translate(2,2)">', $svg);
        $this->assertStringContainsString('width="120" height="120"', Qr::svg('x', 120));
    }

    public function test_the_mail_s_subject_carries_the_code_and_the_site(): void
    {
        $mail = new CodeMail('482913', 10, Codes::MAGIC, 'Testing site');

        $this->assertSame('482913 is your sign-in code for Testing site', $mail->envelope()->subject);
        $this->assertSame('atrium-2fa::mail.code', $mail->content()->view);
    }

    /**
     * @return array<string,array{string,string}>
     */
    public static function phones(): array
    {
        return [
            'one digit' => ['7', 'SMS to 7: hi'],
            'two digits' => ['67', 'SMS to 67: hi'],
            'three digits' => ['567', 'SMS to *67: hi'],
        ];
    }

    #[DataProvider('phones')]
    public function test_the_log_driver_hides_all_but_the_last_two_digits_of_any_number(string $phone, string $line): void
    {
        Log::shouldReceive('info')->once()->with($line);

        (new LogSmsDriver)->send($phone, 'hi');
    }

    /**
     * @return array<string,array{array<string,mixed>,string}>
     */
    public static function people(): array
    {
        return [
            'with a name' => [['id' => 1, 'name' => 'Olga', 'email' => 'olga@example.org'], 'Olga'],
            'without a name' => [['id' => 1, 'name' => '', 'email' => 'olga@example.org'], 'olga@example.org'],
        ];
    }

    /**
     * @param  array<string,mixed>  $attributes
     */
    #[DataProvider('people')]
    public function test_a_person_is_called_by_name_or_else_by_address(array $attributes, string $called): void
    {
        $this->assertSame($called, Person::name(new GenericUser($attributes)));
    }

    public function test_the_site_is_called_by_its_own_name_or_else_by_the_application_s(): void
    {
        $site = Site::query()->where('code', 'main')->sole();
        $site->forceFill(['name' => 'Lakeside', 'is_default' => true])->save();
        app(Sites::class)->flush();

        $this->assertSame('Lakeside', Person::siteName());

        $site->forceFill(['name' => ''])->save();
        app(Sites::class)->flush();

        $this->assertSame('Testing site', Person::siteName());
    }

    public function test_only_the_first_at_sign_splits_an_address(): void
    {
        $this->assertSame('ol**@ex@ample.org', Person::maskEmail('olga@ex@ample.org'));
    }

    public function test_base32_pads_the_last_group_of_bits_to_five(): void
    {
        $this->assertSame('MY', Totp::base32('f'));
        $this->assertSame('MZXW6', Totp::base32('foo'));
    }

    public function test_the_wait_after_a_lockout_counts_down_to_the_last_second(): void
    {
        config(['atrium-2fa.limits' => ['lockout_after' => 3, 'lockout_minutes' => 2, 'per_minute' => 100]]);
        $this->travelTo(Carbon::createFromTimestamp(1_800_000_000));
        $lockout = $this->app->make(Lockout::class);

        $this->assertFalse($lockout->hit('admin', 1, '203.0.113.1'));
        $this->assertFalse($lockout->hit('admin', 1, '203.0.113.1'));
        $this->assertTrue($lockout->hit('admin', 1, '203.0.113.1'));
        $this->assertSame(120, $lockout->wait('admin', 1, '203.0.113.2'));

        $this->travel(119)->seconds();
        $this->assertSame(1, $lockout->wait('admin', 1, '203.0.113.2'));
        $this->travel(1)->seconds();
        $this->assertSame(0, $lockout->wait('admin', 1, '203.0.113.2'));
    }
}

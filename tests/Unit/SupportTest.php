<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Tests\Unit;

use Atrium\TwoFactor\Support\Person;
use Atrium\TwoFactor\Support\Qr;
use Atrium\TwoFactor\Support\Secrets;
use Atrium\TwoFactor\Tests\TestCase;
use Illuminate\Contracts\Encryption\DecryptException;
use PHPUnit\Framework\Attributes\DataProvider;

final class SupportTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function emails(): array
    {
        return [
            'ordinary' => ['olga@example.org', 'ol**@example.org'],
            'one letter' => ['o@example.org', 'o*@example.org'],
            'two letters' => ['ol@example.org', 'ol*@example.org'],
            'cyrillic' => ['ольга@пример.рф', 'ол***@пример.рф'],
            'not an address' => ['olga', 'ol**'],
        ];
    }

    #[DataProvider('emails')]
    public function test_an_email_is_masked_but_the_domain_kept(string $email, string $masked): void
    {
        $this->assertSame($masked, Person::maskEmail($email));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function phones(): array
    {
        return [
            'belarus' => ['+375291234567', '+3*********67'],
            'short' => ['+12345', '+1**45'],
            'tiny' => ['+123', '+1*23'],
        ];
    }

    #[DataProvider('phones')]
    public function test_a_phone_is_masked_to_its_ends(string $phone, string $masked): void
    {
        $this->assertSame($masked, Person::maskPhone($phone));
    }

    public function test_a_secret_is_sealed_for_one_place_only(): void
    {
        $sealed = Secrets::seal('JBSWY3DP', 'auth_factors.secret');

        $this->assertStringNotContainsString('JBSWY3DP', $sealed);
        $this->assertNotSame($sealed, Secrets::seal('JBSWY3DP', 'auth_factors.secret'), 'a fresh IV every time');
        $this->assertSame('JBSWY3DP', Secrets::reveal($sealed, 'auth_factors.secret'));

        $this->expectException(DecryptException::class);
        Secrets::reveal($sealed, 'auth_social_providers.client_secret');
    }

    public function test_a_tampered_secret_is_refused(): void
    {
        $this->expectException(DecryptException::class);

        Secrets::reveal('eyJpdiI6IiJ9', 'auth_factors.secret');
    }

    public function test_the_qr_code_is_an_inline_svg_black_on_white(): void
    {
        $svg = Qr::svg('otpauth://totp/Site:olga%40example.org?secret=ABCD');

        $this->assertStringStartsWith('<svg', $svg);
        $this->assertStringNotContainsString('<?xml', $svg);
        $this->assertStringContainsString('fill="#ffffff"', $svg);
        $this->assertStringContainsString('fill="#000000"', $svg);
    }

    public function test_the_site_name_comes_from_the_app(): void
    {
        $this->assertSame('Testing site', Person::siteName());
    }
}

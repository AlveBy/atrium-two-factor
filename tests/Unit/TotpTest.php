<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Tests\Unit;

use Atrium\TwoFactor\Support\Totp;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TotpTest extends TestCase
{
    /** "12345678901234567890", the SHA-1 seed of RFC 6238, appendix B. */
    private const RFC_SEED = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

    /**
     * @return array<string, array{int, string}>
     */
    public static function rfc6238(): array
    {
        return [
            '59' => [59, '94287082'],
            '1111111109' => [1111111109, '07081804'],
            '1111111111' => [1111111111, '14050471'],
            '1234567890' => [1234567890, '89005924'],
            '2000000000' => [2000000000, '69279037'],
            '20000000000' => [20000000000, '65353130'],
        ];
    }

    #[DataProvider('rfc6238')]
    public function test_the_codes_match_the_rfc_test_vectors(int $time, string $code): void
    {
        $eight = new Totp(8);
        $six = new Totp(6);

        $this->assertSame($code, $eight->at(self::RFC_SEED, $eight->step($time)));
        $this->assertSame(substr($code, 2), $six->at(self::RFC_SEED, $six->step($time)));
        $this->assertSame($eight->step($time), $eight->verify(self::RFC_SEED, $code, 0, 0, $time));
    }

    public function test_the_window_reaches_exactly_as_far_as_asked(): void
    {
        $totp = new Totp;
        $time = 1_800_000_015;
        $now = $totp->step($time);

        $this->assertSame($now - 1, $totp->verify(self::RFC_SEED, $totp->at(self::RFC_SEED, $now - 1), 0, 1, $time));
        $this->assertSame($now + 1, $totp->verify(self::RFC_SEED, $totp->at(self::RFC_SEED, $now + 1), 0, 1, $time));
        $this->assertNull($totp->verify(self::RFC_SEED, $totp->at(self::RFC_SEED, $now - 2), 0, 1, $time));
        $this->assertNull($totp->verify(self::RFC_SEED, $totp->at(self::RFC_SEED, $now + 1), 0, 0, $time));
    }

    public function test_without_a_window_one_step_either_way_is_forgiven_and_no_more(): void
    {
        $totp = new Totp;
        $time = 1_800_000_015;
        $now = $totp->step($time);

        $this->assertSame($now - 1, $totp->verify(self::RFC_SEED, $totp->at(self::RFC_SEED, $now - 1), time: $time));
        $this->assertSame($now + 1, $totp->verify(self::RFC_SEED, $totp->at(self::RFC_SEED, $now + 1), time: $time));
        $this->assertNull($totp->verify(self::RFC_SEED, $totp->at(self::RFC_SEED, $now - 2), time: $time));
        $this->assertNull($totp->verify(self::RFC_SEED, $totp->at(self::RFC_SEED, $now + 2), time: $time));
    }

    public function test_a_step_already_used_or_older_is_refused(): void
    {
        $totp = new Totp;
        $time = 1_800_000_015;
        $now = $totp->step($time);
        $code = $totp->at(self::RFC_SEED, $now);

        $this->assertNull($totp->verify(self::RFC_SEED, $code, $now, 1, $time));
        $this->assertNull($totp->verify(self::RFC_SEED, $code, $now + 1, 1, $time));
        $this->assertSame($now, $totp->verify(self::RFC_SEED, $code, $now - 1, 1, $time));
    }

    public function test_with_no_step_used_yet_even_the_very_first_step_counts(): void
    {
        $totp = new Totp;
        $first = $totp->at(self::RFC_SEED, 0);

        $this->assertSame(0, $totp->verify(self::RFC_SEED, $first, null, 1, 15));
        $this->assertSame(0, $totp->verify(self::RFC_SEED, $first, time: 15));
        $this->assertNull($totp->verify(self::RFC_SEED, $first, 0, 1, 15));
        $this->assertSame(1, $totp->verify(self::RFC_SEED, $totp->at(self::RFC_SEED, 1), 0, 1, 15));
    }

    public function test_by_default_a_code_has_six_digits_and_a_step_thirty_seconds(): void
    {
        $totp = new Totp;

        $this->assertSame(1, $totp->step(59));
        $this->assertSame(2, $totp->step(60));
        $this->assertSame('287082', $totp->at(self::RFC_SEED, $totp->step(59)));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function notCodes(): array
    {
        return ['empty' => [''], 'five digits' => ['12345'], 'seven digits' => ['1234567'], 'letters' => ['12a456'], 'sign' => ['-12345']];
    }

    #[DataProvider('notCodes')]
    public function test_what_is_not_a_code_is_refused(string $code): void
    {
        $this->assertNull((new Totp)->verify(self::RFC_SEED, $code));
    }

    public function test_base32_goes_both_ways_and_ignores_spaces_dashes_and_case(): void
    {
        $bytes = random_bytes(20);
        $text = Totp::base32($bytes);

        $this->assertSame(32, strlen($text));
        $this->assertSame($bytes, Totp::unbase32($text));
        $this->assertSame($bytes, Totp::unbase32(strtolower(trim(chunk_split($text, 4, ' ')))));
        $this->assertSame('12345678901234567890', Totp::unbase32('gezd-gnbv-gy3t-qojq-gezd-gnbv-gy3t-qojq'));
        $this->assertSame(32, strlen(Totp::secret()));
        $this->assertNotSame(Totp::secret(), Totp::secret());
    }

    public function test_a_secret_with_letters_outside_base32_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Totp::unbase32('ABC1');
    }

    public function test_the_qr_address_carries_the_issuer_account_and_settings(): void
    {
        $uri = (new Totp(8, 60))->uri('ABCD', 'My Site & Co', 'olga@example.org');

        $this->assertSame('otpauth://totp/My%20Site%20%26%20Co:olga%40example.org?secret=ABCD&issuer=My%20Site%20%26%20Co&algorithm=SHA1&digits=8&period=60', $uri);
    }
}

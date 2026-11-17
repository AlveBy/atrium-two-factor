<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Tests\Feature;

use Atrium\TwoFactor\Models\Code;
use Atrium\TwoFactor\Models\RecoveryCode;
use Atrium\TwoFactor\Services\Codes;
use Atrium\TwoFactor\Services\RecoveryCodes;
use Atrium\TwoFactor\Tests\TestCase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The one-time codes sent by mail or SMS and the recovery codes: their shape,
 * the pause between two, how many tries they take and how they are kept.
 */
final class CodesTest extends TestCase
{
    private const ALPHABET = 'abcdefghjkmnpqrstuvwxyz23456789';

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::createFromTimestamp(1_800_000_015));
    }

    private function codes(): Codes
    {
        return $this->app->make(Codes::class);
    }

    public function test_a_code_is_six_digits_and_every_digit_turns_up(): void
    {
        $seen = '';

        foreach (range(1, 60) as $n) {
            $code = (string) $this->codes()->issue('people', Codes::MAGIC, "p{$n}@example.org");
            $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
            $seen .= $code;
        }

        $this->assertCount(10, array_unique(str_split($seen)), 'all ten digits in 360 of them');
    }

    /**
     * @return array<string,array{int,int}>
     */
    public static function pauses(): array
    {
        return [
            'just sent' => [0, 60],
            'a second later' => [1, 59],
            'a second to go' => [59, 1],
            'the pause is over' => [60, 0],
            'long after' => [61, 0],
            'the clock went back' => [-30, 30],
            'the clock went far back' => [-100, 0],
        ];
    }

    #[DataProvider('pauses')]
    public function test_the_wait_for_another_code_counts_down_from_the_pause(int $seconds, int $left): void
    {
        config(['atrium-2fa.codes' => ['resend_after' => 60]]);
        $this->codes()->issue('people', Codes::MAGIC, 'olga@example.org');

        $this->travel($seconds)->seconds();

        $this->assertSame($left, $this->codes()->retryAfter('people', Codes::MAGIC, 'olga@example.org'));
        $this->assertSame($left === 0, $this->codes()->issue('people', Codes::MAGIC, 'olga@example.org') !== null);
    }

    public function test_nothing_sent_yet_means_no_wait(): void
    {
        $this->assertSame(0, $this->codes()->retryAfter('people', Codes::MAGIC, 'olga@example.org'));
    }

    public function test_a_new_code_replaces_the_old_one_for_the_same_address_and_purpose_only(): void
    {
        $this->codes()->issue('people', Codes::MAGIC, 'olga@example.org');
        $this->codes()->issue('people', Codes::TWO_FACTOR, 'olga@example.org');
        $this->codes()->issue('people', Codes::MAGIC, 'ivan@example.org');
        $this->travel(61)->seconds();

        $this->assertNotNull($this->codes()->issue('people', Codes::MAGIC, 'olga@example.org'));

        $this->assertSame(3, Code::query()->count());
        $this->assertSame(1, Code::query()->where('purpose', Codes::MAGIC)->where('target_hash', Codes::targetHash('olga@example.org'))->count());
    }

    public function test_five_wrong_tries_by_default_and_the_code_is_gone_after_them(): void
    {
        config(['atrium-2fa.codes' => []]);
        $code = (string) $this->codes()->issue('people', Codes::MAGIC, 'olga@example.org');
        $wrong = $code === '000000' ? '111111' : '000000';

        foreach (range(1, 5) as $n) {
            $this->assertSame(Codes::WRONG, $this->codes()->verify('people', Codes::MAGIC, 'olga@example.org', $wrong), "try {$n}");
        }
        $this->assertSame(5, Code::query()->sole()->attempts);

        $this->assertSame(Codes::TOO_MANY, $this->codes()->verify('people', Codes::MAGIC, 'olga@example.org', $code));
        $this->assertSame(0, Code::query()->count());
        $this->assertSame(Codes::EXPIRED, $this->codes()->verify('people', Codes::MAGIC, 'olga@example.org', $code));
    }

    public function test_the_address_is_kept_as_a_keyed_hash_of_its_small_trimmed_form(): void
    {
        $this->codes()->issue('people', Codes::MAGIC, '  ОЛЬГА@Example.org ');

        $expected = hash_hmac('sha256', 'ольга@example.org', (string) config('app.key'));
        $this->assertSame($expected, Codes::targetHash('ольга@example.org'));
        $this->assertSame($expected, Code::query()->sole()->target_hash);
    }

    public function test_recovery_codes_are_two_groups_of_five_from_an_alphabet_without_look_alikes(): void
    {
        $codes = $this->app->make(RecoveryCodes::class)->generate('admin', 1);
        foreach (range(2, 40) as $id) {
            $codes = [...$codes, ...$this->app->make(RecoveryCodes::class)->generate('admin', $id)];
        }

        $letters = '';
        $echoes = 0;
        foreach ($codes as $code) {
            $this->assertMatchesRegularExpression('/^['.self::ALPHABET.']{5}-['.self::ALPHABET.']{5}$/', $code);
            $letters .= str_replace('-', '', $code);
            $echoes += (int) ($code[4] === $code[6]);
        }

        $this->assertCount(400, array_unique($codes));
        $this->assertSame(strlen(self::ALPHABET), count(array_unique(str_split($letters))), 'every character of the alphabet turns up in 4000');
        $this->assertLessThan(400, $echoes, 'the second group does not start with the end of the first');
    }

    public function test_a_used_recovery_code_says_when_as_a_date(): void
    {
        $this->travelTo(Carbon::createFromTimestamp(1_800_000_015));
        $codes = app(RecoveryCodes::class)->generate('people', 5);

        $this->assertTrue(app(RecoveryCodes::class)->use('people', 5, $codes[0]));

        $used = RecoveryCode::query()->whereNotNull('used_at')->sole()->used_at;
        $this->assertInstanceOf(\DateTimeInterface::class, $used);
        $this->assertSame(1_800_000_015, $used->getTimestamp());
    }

    public function test_a_recovery_code_is_kept_as_a_keyed_hash_set_apart_from_other_hashes(): void
    {
        [$code] = $this->app->make(RecoveryCodes::class)->generate('admin', 1);

        $this->assertSame(
            hash_hmac('sha256', 'recovery|'.str_replace('-', '', $code), (string) config('app.key')),
            RecoveryCode::query()->first()?->code_hash,
        );
    }

    public function test_ten_recovery_codes_unless_the_config_says_otherwise(): void
    {
        $config = config('atrium-2fa');
        unset($config['recovery_codes']);
        config(['atrium-2fa' => $config]);
        $this->assertCount(10, $this->app->make(RecoveryCodes::class)->generate('admin', 1));

        config(['atrium-2fa.recovery_codes' => 3]);
        $this->assertCount(3, $this->app->make(RecoveryCodes::class)->generate('admin', 1));
        $this->assertSame(3, RecoveryCode::query()->count());
    }
}

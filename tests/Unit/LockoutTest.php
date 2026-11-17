<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Tests\Unit;

use Atrium\TwoFactor\Services\Lockout;
use Atrium\TwoFactor\Tests\TestCase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Wrong answers at the second step, counted per minute from one address and
 * in a row from anywhere.
 */
final class LockoutTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::createFromTimestamp(1_800_000_000));
    }

    private function lockout(): Lockout
    {
        return $this->app->make(Lockout::class);
    }

    public function test_without_limits_in_the_config_five_a_minute_from_one_address_are_allowed(): void
    {
        config(['atrium-2fa.limits' => []]);

        foreach (range(1, 4) as $ignored) {
            $this->lockout()->hit('people', 1, '203.0.113.1');
        }
        $this->assertSame(0, $this->lockout()->wait('people', 1, '203.0.113.1'));

        $this->lockout()->hit('people', 1, '203.0.113.1');
        $this->assertSame(60, $this->lockout()->wait('people', 1, '203.0.113.1'));
        $this->assertSame(0, $this->lockout()->wait('people', 1, '203.0.113.2'), 'another address is not held up');
    }

    public function test_without_limits_in_the_config_the_tenth_wrong_answer_in_a_row_locks_for_fifteen_minutes(): void
    {
        config(['atrium-2fa.limits' => []]);

        foreach (range(1, 9) as $n) {
            $this->assertFalse($this->lockout()->hit('people', 1, "203.0.113.{$n}"), "answer {$n}");
        }
        $this->assertSame(0, $this->lockout()->wait('people', 1, '203.0.113.50'));

        $this->assertTrue($this->lockout()->hit('people', 1, '203.0.113.10'));
        $this->assertSame(900, $this->lockout()->wait('people', 1, '203.0.113.50'));
        $this->assertFalse($this->lockout()->hit('people', 1, '203.0.113.11'), 'only the answer that locks says so');
    }

    public function test_limits_written_as_text_as_env_gives_them_still_count(): void
    {
        config(['atrium-2fa.limits' => ['per_minute' => '100', 'lockout_after' => '2', 'lockout_minutes' => '3']]);

        $this->assertFalse($this->lockout()->hit('people', 1, '203.0.113.1'));
        $this->assertTrue($this->lockout()->hit('people', 1, '203.0.113.1'));
        $this->assertSame(180, $this->lockout()->wait('people', 1, '203.0.113.1'));
    }

    public function test_a_right_answer_clears_both_the_minute_and_the_count_in_a_row(): void
    {
        config(['atrium-2fa.limits' => ['per_minute' => 2, 'lockout_after' => 3, 'lockout_minutes' => 15]]);

        $this->lockout()->hit('people', 1, '203.0.113.1');
        $this->lockout()->hit('people', 1, '203.0.113.1');
        $this->assertSame(60, $this->lockout()->wait('people', 1, '203.0.113.1'));

        $this->lockout()->clear('people', 1, '203.0.113.1');
        $this->assertSame(0, $this->lockout()->wait('people', 1, '203.0.113.1'));

        // Two more do not lock: the count in a row started again.
        $this->assertFalse($this->lockout()->hit('people', 1, '203.0.113.2'));
        $this->assertFalse($this->lockout()->hit('people', 1, '203.0.113.3'));
        $this->assertSame(0, $this->lockout()->wait('people', 1, '203.0.113.4'));
    }

    public function test_a_lock_running_out_this_very_second_still_asks_to_wait_a_second_not_none(): void
    {
        RateLimiter::shouldReceive('tooManyAttempts')->andReturn(true);
        RateLimiter::shouldReceive('availableIn')->andReturn(0);

        $this->assertSame(1, $this->lockout()->wait('people', 1, '203.0.113.1'));
    }

    public function test_nobody_waits_before_a_wrong_answer(): void
    {
        $this->assertSame(0, $this->lockout()->wait('people', 1, '203.0.113.1'));
    }
}

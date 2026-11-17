<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Tests\Unit;

use Atrium\TwoFactor\Services\Audit;
use Atrium\TwoFactor\Tests\TestCase;
use Illuminate\Http\Request;

/**
 * A line of the sign-in log: where the request came from, and every text cut
 * to what its column holds.
 */
final class AuditTest extends TestCase
{
    private function arriveFrom(string $ip, string $agent): void
    {
        $this->app->instance('request', Request::create('/', 'POST', server: ['REMOTE_ADDR' => $ip, 'HTTP_USER_AGENT' => $agent]));
    }

    public function test_a_line_keeps_the_address_and_the_browser_it_came_from(): void
    {
        $this->arriveFrom('203.0.113.7', 'Firefox');

        $event = app(Audit::class)->record('people', 5, 'signed_in', 'password')->fresh();

        $this->assertSame(['people', 5, 'signed_in', 'password', '203.0.113.7', 'Firefox'], [$event?->guard, $event?->user_id, $event?->event, $event?->method, $event?->ip, $event?->user_agent]);
    }

    public function test_long_texts_are_cut_to_their_columns_and_short_ones_are_kept_whole(): void
    {
        $this->arriveFrom('203.0.113.7', str_repeat('b', 300));

        $event = app(Audit::class)->record('people', 5, str_repeat('e', 45), str_repeat('m', 45))->fresh();

        $this->assertSame(str_repeat('e', 40), $event?->event);
        $this->assertSame(str_repeat('m', 40), $event?->method);
        $this->assertSame(str_repeat('b', 250), $event?->user_agent);

        $this->arriveFrom('203.0.113.7', str_repeat('b', 250));
        $event = app(Audit::class)->record('people', 5, str_repeat('e', 40), str_repeat('m', 40))->fresh();

        $this->assertSame([40, 40, 250], [strlen((string) $event?->event), strlen((string) $event?->method), strlen((string) $event?->user_agent)]);
    }

    public function test_a_line_without_a_person_or_a_method_leaves_them_empty(): void
    {
        $this->arriveFrom('203.0.113.7', 'Firefox');

        $event = app(Audit::class)->record('people', null, 'failed')->fresh();

        $this->assertNull($event?->method);
        $this->assertNull($event?->user_id);
    }
}

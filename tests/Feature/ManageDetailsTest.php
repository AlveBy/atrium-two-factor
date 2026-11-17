<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Tests\Feature;

use Atrium\TwoFactor\Models\Event;
use Atrium\TwoFactor\Tests\TestCase;
use Illuminate\Foundation\Http\Middleware\TrimStrings;

/**
 * The sign-in log and the list of people in detail: pages, filters left
 * empty, guards the package does not serve and the people named in the log.
 */
final class ManageDetailsTest extends TestCase
{
    public function test_the_log_shows_fifty_events_a_page_newest_first_and_empty_filters_filter_nothing(): void
    {
        $admin = $this->admin();
        foreach (range(1, 51) as $i) {
            Event::query()->create(['guard' => 'admin', 'user_id' => $admin->id, 'event' => 'signed_in', 'method' => 'm'.$i]);
        }
        $this->actingAs($admin, 'admin');

        $page = $this->get(route('admin.two-factor.log', ['guard' => '', 'event' => '', 'user' => '']))->assertOk();

        $this->assertSame(50, $page->viewData('events')->count());
        $this->assertSame(51, $page->viewData('events')->total());
        $this->assertSame('m51', $page->viewData('events')->first()->method);
        $this->assertSame(['m1'], $this->get(route('admin.two-factor.log', ['page' => 2]))->viewData('events')->pluck('method')->all());
    }

    public function test_the_log_offers_every_event_to_filter_by(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->assertSame(
            ['signed_in', 'challenged', 'failed', 'locked', 'trusted_device', 'code_sent', 'factor_added', 'factor_removed', 'recovery_used', 'recovery_generated', 'device_trusted', 'device_revoked', 'reset'],
            $this->get(route('admin.two-factor.log'))->viewData('eventNames'),
        );
    }

    public function test_the_log_names_people_of_every_guard_it_can_look_up_and_skips_the_ones_it_cannot(): void
    {
        $admin = $this->admin();
        $person = $this->person();
        Event::query()->create(['guard' => 'people', 'user_id' => $person->id, 'event' => 'signed_in']);
        Event::query()->create(['guard' => 'people', 'user_id' => $person->id, 'event' => 'failed']);
        Event::query()->create(['guard' => 'admin', 'user_id' => $admin->id, 'event' => 'signed_in']);
        Event::query()->create(['guard' => 'gone', 'user_id' => 3, 'event' => 'signed_in']);
        $this->actingAs($admin, 'admin');

        $this->assertSame(
            ['admin:'.$admin->id => 'admin@example.com', 'people:'.$person->id => 'olga@example.org'],
            $this->get(route('admin.two-factor.log'))->viewData('people'),
        );
    }

    public function test_people_are_listed_thirty_a_page(): void
    {
        foreach (range(1, 31) as $i) {
            $this->person(['email' => "p{$i}@example.org"]);
        }
        $this->actingAs($this->admin(), 'admin');

        $page = $this->get(route('admin.two-factor.users', ['guard' => 'people']))->assertOk();

        $this->assertSame(30, $page->viewData('people')->count());
        $this->assertSame(31, $page->viewData('people')->total());
        $this->assertSame('people', $page->viewData('selected'));
    }

    public function test_the_search_finds_a_piece_from_the_middle_of_the_address_and_is_trimmed_even_without_the_middleware(): void
    {
        TrimStrings::skipWhen(fn (): bool => true);
        $this->person();
        $this->person(['email' => 'ivan@example.com']);
        $this->actingAs($this->admin(), 'admin');

        $page = $this->get(route('admin.two-factor.users', ['guard' => 'people', 'q' => ' example.org ']));

        $this->assertSame(['olga@example.org'], $page->viewData('people')->pluck('email')->all());
        $this->assertSame('example.org', $page->viewData('search'));
    }

    public function test_guards_the_package_does_not_serve_or_cannot_list_are_not_found(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->get(route('admin.two-factor.users', ['guard' => 'web']))->assertNotFound();

        config(['auth.providers.people' => ['driver' => 'database', 'table' => 'people']]);
        $this->get(route('admin.two-factor.users', ['guard' => 'people']))->assertNotFound();
    }

    public function test_a_reset_needs_the_guard(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->post(route('admin.two-factor.reset'), ['user' => 1])->assertSessionHasErrors('guard');
        $this->assertSame(0, Event::query()->count());
    }
}

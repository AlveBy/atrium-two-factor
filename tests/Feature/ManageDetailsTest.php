<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Tests\Feature;

use Atrium\TwoFactor\Guards;
use Atrium\TwoFactor\Models\Event;
use Atrium\TwoFactor\Support\Methods;
use Atrium\TwoFactor\Tests\TestCase;
use Illuminate\Foundation\Http\Middleware\TrimStrings;
use Inertia\Testing\AssertableInertia as Assert;

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

        $this->get(route('admin.two-factor.log', ['guard' => '', 'event' => '', 'user' => '']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/TwoFactor/Log', false)
                ->has('events', 50)
                ->where('events.0.how', Methods::label('m51'))
                ->where('events.49.how', Methods::label('m2'))
                ->where('newer', null)
                ->where('older', route('admin.two-factor.log', ['page' => 2]))
                ->where('filters', ['guard' => '', 'event' => '']));

        $this->get(route('admin.two-factor.log', ['page' => 2]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('events', 1)
                ->where('events.0.how', Methods::label('m1'))
                ->where('newer', route('admin.two-factor.log', ['page' => 1]))
                ->where('older', null));
    }

    public function test_the_log_offers_every_event_to_filter_by(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->get(route('admin.two-factor.log'))->assertInertia(fn (Assert $page) => $page
            ->where('eventNames', ['signed_in', 'challenged', 'failed', 'locked', 'trusted_device', 'code_sent', 'factor_added', 'factor_removed', 'recovery_used', 'recovery_generated', 'device_trusted', 'device_revoked', 'reset'])
            ->where('guards', $this->app->make(Guards::class)->names()));
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

        $this->get(route('admin.two-factor.log'))->assertInertia(fn (Assert $page) => $page
            ->has('events', 4)
            // Newest first: the guard that is gone, the panel, then the person twice.
            ->where('events.0.who', __('atrium-2fa::two-factor.guards.gone').': #3')
            ->where('events.1.who', __('atrium-2fa::two-factor.guards.admin').': admin@example.com')
            ->where('events.2.who', __('atrium-2fa::two-factor.guards.people').': olga@example.org')
            ->where('events.3.who', __('atrium-2fa::two-factor.guards.people').': olga@example.org'));
    }

    public function test_people_are_listed_thirty_a_page(): void
    {
        foreach (range(1, 31) as $i) {
            $this->person(['email' => "p{$i}@example.org"]);
        }
        $this->actingAs($this->admin(), 'admin');

        $this->get(route('admin.two-factor.users', ['guard' => 'people']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/TwoFactor/People', false)
                ->has('people', 30)
                ->where('selected', 'people')
                ->where('older', route('admin.two-factor.users', ['guard' => 'people', 'page' => 2]))
                ->where('newer', null)
                ->where('resetUrl', route('admin.two-factor.reset')));
    }

    public function test_the_search_finds_a_piece_from_the_middle_of_the_address_and_is_trimmed_even_without_the_middleware(): void
    {
        TrimStrings::skipWhen(fn (): bool => true);
        $this->person();
        $this->person(['email' => 'ivan@example.com']);
        $this->actingAs($this->admin(), 'admin');

        $this->get(route('admin.two-factor.users', ['guard' => 'people', 'q' => ' example.org ']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('people', 1)
                ->where('people.0.email', 'olga@example.org')
                ->where('search', 'example.org'));
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

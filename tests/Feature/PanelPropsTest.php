<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Tests\Feature;

use Atrium\TwoFactor\Models\Event;
use Atrium\TwoFactor\Models\Factor;
use Atrium\TwoFactor\Models\TrustedDevice;
use Atrium\TwoFactor\Support\Methods;
use Atrium\TwoFactor\Tests\TestCase;
use Illuminate\Support\Carbon;

/**
 * What the panel's pages are handed, word for word: the Vue pages show it as
 * it comes, so every prop is the page's contract.
 */
final class PanelPropsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::create(2026, 11, 20, 10, 30, 0, 'UTC'));
    }

    /**
     * @return array<string,mixed>
     */
    private function props(string $url): array
    {
        return $this->get($url)->assertOk()->viewData('page')['props'];
    }

    public function test_the_security_page_hands_over_everything_it_shows(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'admin');
        Factor::query()->create(['guard' => 'admin', 'user_id' => $admin->id + 1, 'type' => 'totp', 'name' => 'Other', 'secret' => self::SECRET, 'confirmed_at' => Carbon::now()]);
        $factor = Factor::query()->create([
            'guard' => 'admin', 'user_id' => $admin->id, 'type' => 'totp', 'name' => 'Phone', 'secret' => self::SECRET,
            'confirmed_at' => Carbon::now()->subDays(3), 'last_used_at' => Carbon::now()->subDay(),
        ]);
        $device = TrustedDevice::query()->create(['guard' => 'admin', 'user_id' => $admin->id, 'token_hash' => 'h', 'name' => 'Firefox', 'ip' => '10.0.0.5', 'expires_at' => Carbon::now()->addDays(5)]);
        $this->post(route('admin.two-factor.recovery.regenerate'));
        $codes = session('atrium-2fa.recovery_codes');

        $props = $this->withSession(['atrium-2fa.status' => 'Done.', 'atrium-2fa.error' => 'Oops.', 'atrium-2fa.recovery_codes' => $codes])
            ->props(route('admin.two-factor.security'));

        $this->assertSame(trans('atrium-2fa::two-factor'), $props['t']);
        $this->assertSame('Done.', $props['status']);
        $this->assertSame('Oops.', $props['error']);
        $this->assertSame($codes, $props['recoveryCodes']);
        $this->assertSame(['totp', 'passkey', 'email', 'sms'], $props['allowed']);
        $this->assertGreaterThan(1, $factor->id);
        $this->assertSame([[
            'id' => $factor->id,
            'type' => 'totp',
            'name' => 'Phone',
            'added_at' => 'November 17, 2026',
            'used_at' => 'November 19, 2026',
            'remove_url' => route('admin.two-factor.factors.destroy', ['factor' => $factor->id]),
        ]], $props['factors']);
        $this->assertSame('10 unused codes left.', $props['recoveryLeft']);
        $this->assertSame([[
            'id' => $device->id,
            'name' => 'Firefox',
            'ip' => '10.0.0.5',
            'until' => 'November 25, 2026',
        ]], $props['devices']);
        $this->assertSame(30, $props['trustedDays']);
        $this->assertSame('ad***@example.com', $props['email']);
        $this->assertNull($props['totp']);
        $this->assertNull($props['sms']);
        $expected = [];
        foreach (['totp.start', 'totp.confirm', 'passkeys.options', 'passkeys.store', 'sms.start', 'sms.confirm', 'email.enable', 'recovery.regenerate', 'devices.revoke'] as $name) {
            $expected[$name] = route('admin.two-factor.'.$name);
        }
        $this->assertSame($expected, $props['urls']);
    }

    public function test_a_factor_never_used_has_no_date_of_use(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'admin');
        $this->totp($admin);

        $this->assertNull($this->props(route('admin.two-factor.security'))['factors'][0]['used_at']);
    }

    public function test_the_log_hands_over_its_rows_filters_and_wording(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'admin');
        $byAdmin = Event::query()->create(['guard' => 'admin', 'user_id' => $admin->id, 'event' => 'reset', 'ip' => '10.0.0.9', 'actor_guard' => 'admin', 'actor_id' => $admin->id]);
        $alone = Event::query()->create(['guard' => 'admin', 'user_id' => $admin->id, 'event' => 'signed_in', 'method' => 'totp']);

        $props = $this->props(route('admin.two-factor.log'));

        $this->assertSame(trans('atrium-2fa::two-factor'), $props['t']);
        $this->assertSame(route('admin.two-factor.log'), $props['url']);
        $this->assertSame(['guard' => '', 'event' => ''], $props['filters']);
        $this->assertSame([
            [
                'id' => $alone->id,
                'when_iso' => '2026-11-20T10:30:00+00:00',
                'when' => 'Nov 20, 2026 10:30 AM',
                'who' => __('atrium-2fa::two-factor.guards.admin').': admin@example.com',
                'what' => __('atrium-2fa::two-factor.events.signed_in'),
                'by' => null,
                'how' => Methods::label('totp'),
                'ip' => null,
            ],
            [
                'id' => $byAdmin->id,
                'when_iso' => '2026-11-20T10:30:00+00:00',
                'when' => 'Nov 20, 2026 10:30 AM',
                'who' => __('atrium-2fa::two-factor.guards.admin').': admin@example.com',
                'what' => __('atrium-2fa::two-factor.events.reset'),
                'by' => __('atrium-2fa::two-factor.log.by_admin', ['id' => $admin->id]),
                'how' => '—',
                'ip' => '10.0.0.9',
            ],
        ], $props['events']);

        $this->assertSame(['guard' => 'admin', 'event' => 'reset'], $this->props(route('admin.two-factor.log', ['guard' => 'admin', 'event' => 'reset']))['filters']);
    }

    public function test_the_list_of_people_hands_over_each_with_the_ways_they_confirm(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $person = $this->person();
        $this->totp($person, 'people');
        Factor::query()->create(['guard' => 'people', 'user_id' => $person->id, 'type' => 'email', 'confirmed_at' => Carbon::now()]);
        $other = $this->person(['email' => 'ivan@example.org']);

        $props = $this->withSession(['atrium-2fa.status' => 'Reset.'])->props(route('admin.two-factor.users', ['guard' => 'people']));

        $this->assertSame(trans('atrium-2fa::two-factor'), $props['t']);
        $this->assertSame(route('admin.two-factor.users'), $props['url']);
        $this->assertSame('Reset.', $props['status']);
        $this->assertSame([
            ['id' => $person->id, 'email' => 'olga@example.org', 'factors' => [__('atrium-2fa::two-factor.factors.totp'), __('atrium-2fa::two-factor.factors.email')]],
            ['id' => $other->id, 'email' => 'ivan@example.org', 'factors' => []],
        ], $props['people']);
    }
}

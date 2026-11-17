<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Tests\Feature;

use Atrium\TwoFactor\Models\TrustedDevice;
use Atrium\TwoFactor\Services\TrustedDevices;
use Atrium\TwoFactor\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * "Trust this device": what the cookie and the table keep, and when the
 * cookie stops counting.
 */
final class TrustedDevicesTest extends TestCase
{
    private function devices(): TrustedDevices
    {
        return $this->app->make(TrustedDevices::class);
    }

    /**
     * @param  array<string,string>  $cookies
     * @param  array<string,string>  $server
     */
    private function request(array $cookies = [], array $server = [], string $url = 'http://localhost/'): Request
    {
        return Request::create($url, 'POST', [], $cookies, [], ['REMOTE_ADDR' => '203.0.113.7', ...$server]);
    }

    /**
     * @return array<string,array{int}>
     */
    public static function days(): array
    {
        return [
            'one day' => [1],
            'a month' => [30],
            'a year' => [365],
        ];
    }

    #[DataProvider('days')]
    public function test_the_device_is_trusted_for_exactly_the_guard_s_days(int $days): void
    {
        config(['atrium-2fa.guards.people.trusted_days' => $days]);
        $this->travelTo(Carbon::parse('2026-11-20 10:00:00'));

        $cookie = $this->devices()->trust($this->request(server: ['HTTP_USER_AGENT' => 'Firefox']), 'people', 5);

        $device = TrustedDevice::query()->sole();
        $this->assertSame(Carbon::now()->addDays($days)->getTimestamp(), $device->expires_at->getTimestamp());
        $this->assertSame(Carbon::now()->addDays($days)->getTimestamp(), $cookie->getExpiresTime());
        $this->assertSame(['people', 5, 'Firefox', '203.0.113.7'], [$device->guard, (int) $device->user_id, $device->name, $device->ip]);
        $this->assertSame(TrustedDevices::cookie('people', 5), $cookie->getName());
        $this->assertSame(64, strlen((string) $cookie->getValue()));
        $this->assertSame(hash('sha256', (string) $cookie->getValue()), $device->token_hash);
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertFalse($cookie->isSecure());
        $this->assertSame('lax', $cookie->getSameSite());
    }

    public function test_over_https_the_cookie_is_secure(): void
    {
        $cookie = $this->devices()->trust($this->request(url: 'https://localhost/'), 'people', 5);

        $this->assertTrue($cookie->isSecure());
    }

    public function test_a_long_browser_name_is_cut_to_what_the_table_keeps(): void
    {
        $this->devices()->trust($this->request(server: ['HTTP_USER_AGENT' => str_repeat('a', 300)]), 'people', 5);

        $this->assertSame(str_repeat('a', 250), TrustedDevice::query()->value('name'));
    }

    public function test_every_device_gets_its_own_token(): void
    {
        $first = $this->devices()->trust($this->request(), 'people', 5);
        $second = $this->devices()->trust($this->request(), 'people', 5);

        $this->assertNotSame($first->getValue(), $second->getValue());
        $this->assertSame(2, TrustedDevice::query()->count());
    }

    public function test_the_cookie_is_named_for_the_guard_and_the_person_without_showing_the_id(): void
    {
        $this->assertSame('atrium_2fa_admin_'.substr(hash('sha256', 'admin|42'), 0, 16), TrustedDevices::cookie('admin', 42));
        $this->assertNotSame(TrustedDevices::cookie('admin', 42), TrustedDevices::cookie('people', 42));
        $this->assertNotSame(TrustedDevices::cookie('admin', 4), TrustedDevices::cookie('admin', 42));
    }

    public function test_a_trusted_device_is_noted_each_time_it_is_used_until_it_expires(): void
    {
        config(['atrium-2fa.guards.people.trusted_days' => 2]);
        $this->travelTo(Carbon::parse('2026-11-20 10:00:00'));
        $cookie = $this->devices()->trust($this->request(), 'people', 5);
        $request = $this->request([$cookie->getName() => (string) $cookie->getValue()]);

        $this->travelTo(Carbon::parse('2026-11-21 09:00:00'));
        $this->assertTrue($this->devices()->trusted($request, 'people', 5));
        $this->assertSame('2026-11-21 09:00:00', TrustedDevice::query()->sole()->last_used_at?->toDateTimeString());

        $this->assertFalse($this->devices()->trusted($request, 'people', 6), 'another person on the same browser');
        $this->assertFalse($this->devices()->trusted($request, 'admin', 5), 'the same id in another guard');

        $this->travelTo(Carbon::parse('2026-11-22 10:00:00'));
        $this->assertFalse($this->devices()->trusted($request, 'people', 5));
    }

    public function test_a_device_trusted_before_the_days_were_set_to_zero_is_not_trusted_any_more(): void
    {
        $cookie = $this->devices()->trust($this->request(), 'people', 5);
        $request = $this->request([$cookie->getName() => (string) $cookie->getValue()]);
        $this->assertTrue($this->devices()->trusted($request, 'people', 5));

        config(['atrium-2fa.guards.people.trusted_days' => 0]);

        $this->assertFalse($this->devices()->trusted($request, 'people', 5));
    }

    public function test_an_empty_cookie_is_not_trust(): void
    {
        $this->devices()->trust($this->request(), 'people', 5);

        $this->assertFalse($this->devices()->trusted($this->request([TrustedDevices::cookie('people', 5) => '']), 'people', 5));
        $this->assertFalse($this->devices()->trusted($this->request(), 'people', 5));
    }

    public function test_revoking_takes_every_device_of_the_person_and_no_one_else_s(): void
    {
        $this->devices()->trust($this->request(), 'people', 5);
        $this->devices()->trust($this->request(), 'people', 5);
        $this->devices()->trust($this->request(), 'people', 6);
        $this->devices()->trust($this->request(), 'admin', 5);

        $this->assertSame(2, $this->devices()->revokeAll('people', 5));
        $this->assertSame([['admin', 5], ['people', 6]], TrustedDevice::query()->orderBy('guard')->get()->map(fn ($d) => [$d->guard, (int) $d->user_id])->all());
    }
}

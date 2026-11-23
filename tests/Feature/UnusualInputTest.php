<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Tests\Feature;

use Atrium\TwoFactor\Factors\TotpFactor;
use Atrium\TwoFactor\Guards;
use Atrium\TwoFactor\Http\Middleware\EnforceEnrolment;
use Atrium\TwoFactor\Models\Factor;
use Atrium\TwoFactor\Support\Methods;
use Atrium\TwoFactor\Support\Secrets;
use Atrium\TwoFactor\Support\Totp;
use Atrium\TwoFactor\Tests\TestCase;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpFoundation\Response;

/**
 * What comes in a shape the forms never send: a number for a code, a number
 * for a name, a request without a route, a value from a neighbouring column.
 */
final class UnusualInputTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::createFromTimestamp(1_800_000_015));
    }

    public function test_an_app_needs_nothing_sent_before_its_code_is_asked_for(): void
    {
        $this->assertTrue($this->app->make(TotpFactor::class)->prepare($this->admin(), 'admin'));
    }

    public function test_a_code_that_arrives_as_a_number_still_counts_and_a_wrong_one_does_not(): void
    {
        $admin = $this->admin();
        $this->totp($admin);
        $code = $this->code();
        $factor = $this->app->make(TotpFactor::class);

        $this->assertNotSame('0', $code[0], 'a leading zero would be lost in a number');
        $this->assertFalse($factor->verify($admin, 'admin', Request::create('/', 'POST', ['code' => 111111])));
        $this->assertTrue($factor->verify($admin, 'admin', Request::create('/', 'POST', ['code' => (int) $code])));
    }

    public function test_an_issuer_written_as_a_number_in_the_config_is_still_shown_as_text(): void
    {
        config(['atrium-2fa.totp.issuer' => 2026]);
        $admin = $this->admin();
        Factor::query()->create(['guard' => 'admin', 'user_id' => $admin->id, 'type' => 'totp', 'secret' => self::SECRET]);

        $this->actingAs($admin, 'admin')->get(route('admin.two-factor.security'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('totp.qr', fn (string $svg) => str_contains($svg, '<svg')));
    }

    public function test_a_request_without_a_route_is_not_one_of_the_open_pages(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'admin');
        $request = Request::create('/somewhere');
        $request->setLaravelSession($this->app->make('session.store'));
        $request->session()->put('atrium-2fa.enrol.admin', 1);

        $response = $this->app->make(EnforceEnrolment::class)->handle($request, fn () => new Response('passed'));

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(route('admin.two-factor.security'), $response->headers->get('Location'));
    }

    public function test_nobody_is_found_without_an_id_and_somebody_is_with_one(): void
    {
        $admin = $this->admin();
        $guards = $this->app->make(Guards::class);

        $this->assertNull($guards->find('admin', null));
        $this->assertSame($admin->id, $guards->find('admin', $admin->id)?->getAuthIdentifier());
    }

    public function test_a_method_whose_translation_is_a_group_of_lines_is_shown_by_its_key(): void
    {
        // The file first, so the extra line goes next to it, not instead of it.
        $this->assertSame('Authenticator app', Methods::label('totp'));
        app('translator')->addLines(['two-factor.methods.nested.one' => 'One'], 'en', 'atrium-2fa');

        $this->assertSame('nested', Methods::label('nested'));
        $this->assertSame('unheard-of', Methods::label('unheard-of'));
        $this->assertSame('Authenticator app', Methods::label('totp'));
    }

    public function test_a_secret_of_a_column_whose_name_merely_starts_the_same_is_refused(): void
    {
        $sealed = Secrets::seal('JBSWY3DP', 'auth_factors.secret_backup');

        $this->expectException(DecryptException::class);

        Secrets::reveal($sealed, 'auth_factors.secret');
    }

    public function test_without_settings_an_app_uses_six_digits_and_thirty_seconds(): void
    {
        config(['atrium-2fa.totp' => []]);
        $totp = Totp::fromConfig();

        $this->assertSame(1, $totp->step(59));
        $this->assertSame(2, $totp->step(60));
        // RFC 6238, appendix B: 94287082 at 59 seconds, the last six of them.
        $this->assertSame('287082', $totp->at('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', $totp->step(59)));
    }
}

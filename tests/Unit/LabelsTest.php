<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Tests\Unit;

use Atrium\TwoFactor\Factors;
use Atrium\TwoFactor\Mail\CodeMail;
use Atrium\TwoFactor\Services\Codes;
use Atrium\TwoFactor\Sms\LogSmsDriver;
use Atrium\TwoFactor\Support\Methods;
use Atrium\TwoFactor\Support\Redirects;
use Atrium\TwoFactor\Tests\TestCase;
use Atrium\TwoFactor\TwoFactorSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Cookie;

final class LabelsTest extends TestCase
{
    /**
     * @return array<string,array{string,string}>
     */
    public static function factors(): array
    {
        return [
            'app' => ['totp', 'atrium-2fa::two-factor.factors.totp'],
            'passkey' => ['passkey', 'atrium-2fa::two-factor.factors.passkey'],
            'sms' => ['sms', 'atrium-2fa::two-factor.factors.sms'],
            'email' => ['email', 'atrium-2fa::two-factor.factors.email'],
        ];
    }

    #[DataProvider('factors')]
    public function test_every_factor_is_named_in_the_reader_s_language(string $key, string $label): void
    {
        $this->app->setLocale('de');

        $factor = $this->app->make(Factors::class)->get($key);

        $this->assertNotNull($factor);
        $this->assertSame(__($label), $factor->label());
        $this->assertNotSame($label, $factor->label());
    }

    /**
     * @return array<string,array{string,string}>
     */
    public static function methods(): array
    {
        return [
            'social' => ['social:github', 'Github'],
            'social with dashes' => ['social:vk-id', 'Vk Id'],
            'unknown' => ['carrier-pigeon', 'carrier-pigeon'],
        ];
    }

    #[DataProvider('methods')]
    public function test_a_way_in_is_named_for_the_log(string $method, string $label): void
    {
        $this->assertSame($label, Methods::label($method));
    }

    public function test_a_known_way_in_is_translated(): void
    {
        $this->assertSame(__('atrium-2fa::two-factor.methods.password'), Methods::label('password'));
        $this->assertNotSame('password', Methods::label('password'));
    }

    public function test_the_settings_page_describes_the_module(): void
    {
        $this->assertSame(__('atrium-2fa::two-factor.settings.description'), $this->app->make(TwoFactorSettings::class)->description());
    }

    public function test_the_mail_shows_the_code_and_its_lifetime(): void
    {
        $html = (new CodeMail('482913', 10, Codes::MAGIC, 'Testing site'))->render();

        $this->assertStringContainsString('482913', $html);
        $this->assertStringContainsString('Testing site', $html);
    }

    public function test_the_log_driver_writes_the_text_but_hides_the_number(): void
    {
        Log::shouldReceive('info')->once()->with('SMS to ***********67: Your code is 123456');

        (new LogSmsDriver)->send('+375291234567', 'Your code is 123456');
    }

    public function test_a_redirect_for_a_script_becomes_json_and_keeps_its_cookies(): void
    {
        $redirect = redirect('/somewhere');
        $redirect->headers->setCookie(Cookie::create('trusted', 'yes'));

        $json = Redirects::forScript($redirect);

        $this->assertInstanceOf(JsonResponse::class, $json);
        $this->assertSame(url('/somewhere'), $json->getData(true)['redirect']);
        $this->assertSame('yes', $json->headers->getCookies()[0]->getValue());
    }

    public function test_an_answer_that_is_not_a_redirect_is_left_alone(): void
    {
        $response = new Response('page');

        $this->assertSame($response, Redirects::forScript($response));
    }
}

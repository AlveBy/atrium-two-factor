<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Tests\Feature;

use Atrium\TwoFactor\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * A person told to set a factor up is kept on the security page: which pages
 * stay open meanwhile, and how several guards in one browser get along.
 */
final class EnrolmentTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        parent::defineRoutes($router);

        $router->middleware('web')->get('plain', fn () => 'plain');
        $router->middleware('web')->get('people/bye', fn () => 'bye')->name('people.logout');
        $router->middleware('web')->get('ru/people/auth/page', fn () => 'own')->name('localized.atrium-2fa.people.page');
        $router->middleware('web')->get('ru/people/home', fn () => 'home')->name('localized.people.home');
    }

    /**
     * @return array<string,array{string,bool}>
     */
    public static function pages(): array
    {
        return [
            'a page without a name' => ['/plain', false],
            'another guard\'s page in another language' => ['/ru/people/home', false],
            'signing out, even by a link' => ['/people/bye', true],
            'its own page in another language' => ['/ru/people/auth/page', true],
        ];
    }

    #[DataProvider('pages')]
    public function test_only_the_package_s_own_pages_and_signing_out_stay_open(string $url, bool $open): void
    {
        $this->actingAs($this->person(), 'people')->withSession(['atrium-2fa.enrol.people' => 1]);

        $response = $this->get($url);

        $open ? $response->assertOk() : $response->assertRedirect(route('atrium-2fa.people.security'));
    }

    public function test_a_reminder_left_by_a_guard_no_longer_signed_in_does_not_spare_the_next_guard(): void
    {
        $this->actingAs($this->person(), 'people')
            ->withSession(['atrium-2fa.enrol.admin' => 1, 'atrium-2fa.enrol.people' => 1]);

        $this->get(route('people.home'))->assertRedirect(route('atrium-2fa.people.security'));

        $this->assertFalse(session()->has('atrium-2fa.enrol.admin'));
        $this->assertTrue(session()->has('atrium-2fa.enrol.people'));
    }

    public function test_a_page_open_for_one_guard_is_not_open_for_the_next(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->actingAs($this->person(), 'people')
            ->withSession(['atrium-2fa.enrol.admin' => 1, 'atrium-2fa.enrol.people' => 1]);

        $this->get(route('admin.two-factor.security'))->assertRedirect(route('atrium-2fa.people.security'));
    }
}

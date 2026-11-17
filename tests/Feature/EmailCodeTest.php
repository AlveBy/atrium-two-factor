<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Tests\Feature;

use Atrium\TwoFactor\Mail\CodeMail;
use Atrium\TwoFactor\Models\Event;
use Atrium\TwoFactor\Tests\TestCase;
use Illuminate\Support\Facades\Mail;

/**
 * The code by e-mail that the panel always sent, now behind the same
 * contract as every other factor: the behaviour has to stay as it was.
 */
final class EmailCodeTest extends TestCase
{
    public function test_the_password_leads_to_the_code_and_the_code_signs_in(): void
    {
        Mail::fake();
        $admin = $this->admin();

        $this->signInWithPassword()->assertRedirect(route('admin.two-factor.challenge'));
        $this->assertGuest('admin');

        $code = null;
        Mail::assertSent(CodeMail::class, function (CodeMail $mail) use (&$code): bool {
            $code = $mail->code;

            return $mail->hasTo('admin@example.com') && $mail->purpose === 'two_factor';
        });

        $this->get(route('admin.two-factor.challenge'))->assertOk()->assertSee('ad***@example.com');

        $this->post(route('admin.two-factor.challenge.verify'), ['code' => $code])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertSame(
            ['challenged', 'code_sent', 'signed_in'],
            Event::query()->orderBy('id')->pluck('event')->sort()->values()->all(),
        );
    }

    public function test_a_wrong_code_is_refused_and_logged(): void
    {
        Mail::fake();
        $this->admin();
        $this->signInWithPassword();

        $this->post(route('admin.two-factor.challenge.verify'), ['code' => '000000'])
            ->assertRedirect(route('admin.two-factor.challenge'))
            ->assertSessionHasErrors('code');

        $this->assertGuest('admin');
        $this->assertSame(1, Event::query()->where('event', 'failed')->count());
    }

    public function test_switching_the_old_setting_off_lets_the_password_alone_in(): void
    {
        config(['atrium.auth.two_factor.enabled' => false]);
        Mail::fake();
        $admin = $this->admin();

        $this->signInWithPassword()->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin, 'admin');
        Mail::assertNothingSent();
    }
}

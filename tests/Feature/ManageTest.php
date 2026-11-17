<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Tests\Feature;

use Atrium\Admin\Facades\Hook;
use Atrium\Admin\Models\AdminRole;
use Atrium\Admin\Models\AdminUser;
use Atrium\TwoFactor\Models\Event;
use Atrium\TwoFactor\Models\Factor;
use Atrium\TwoFactor\Models\RecoveryCode;
use Atrium\TwoFactor\Models\TrustedDevice;
use Atrium\TwoFactor\Services\RecoveryCodes;
use Atrium\TwoFactor\Tests\TestCase;
use Illuminate\Support\Carbon;

/**
 * The sign-in log and the reset of a person's second step, for whoever may
 * set the module up.
 */
final class ManageTest extends TestCase
{
    private function editor(array $permissions = []): AdminUser
    {
        $role = AdminRole::query()->create(['name' => 'Editors', 'permissions' => $permissions]);
        $editor = $this->admin(['email' => 'editor@example.com'], superadmin: false);
        $editor->roles()->attach($role);

        return $editor;
    }

    public function test_an_editor_without_the_permission_sees_neither_the_log_nor_the_resets(): void
    {
        $editor = $this->editor();
        $this->actingAs($editor, 'admin');

        $this->get(route('admin.two-factor.log'))->assertForbidden();
        $this->get(route('admin.two-factor.users'))->assertForbidden();
        $this->post(route('admin.two-factor.reset'), ['guard' => 'admin', 'user' => 1])->assertForbidden();

        $menu = Hook::apply('atrium.admin.menu', [], $editor);
        $this->assertSame(['admin.two-factor.security'], array_column(end($menu)['children'], 'route'));
    }

    public function test_the_permission_to_set_up_the_module_opens_both(): void
    {
        $editor = $this->editor(['two_factor.settings']);
        $this->actingAs($editor, 'admin');

        $this->get(route('admin.two-factor.log'))->assertOk();
        $this->get(route('admin.two-factor.users'))->assertOk();

        $menu = Hook::apply('atrium.admin.menu', [], $editor);
        $this->assertSame(
            ['admin.two-factor.security', 'admin.two-factor.log', 'admin.two-factor.users'],
            array_column(end($menu)['children'], 'route'),
        );
    }

    public function test_nobody_signed_in_gets_no_menu_entry(): void
    {
        $this->assertSame([], Hook::apply('atrium.admin.menu', [], null));
    }

    public function test_the_log_shows_and_filters_sign_ins_of_every_guard(): void
    {
        $admin = $this->admin();
        $person = $this->person();
        Event::query()->create(['guard' => 'admin', 'user_id' => $admin->id, 'event' => 'signed_in', 'method' => 'password', 'ip' => '10.0.0.1', 'created_at' => Carbon::now()]);
        Event::query()->create(['guard' => 'people', 'user_id' => $person->id, 'event' => 'failed', 'method' => 'code', 'ip' => '10.0.0.2', 'created_at' => Carbon::now()]);
        Event::query()->create(['guard' => 'people', 'user_id' => null, 'event' => 'locked', 'method' => null, 'ip' => '10.0.0.3', 'created_at' => Carbon::now()]);
        $this->actingAs($admin, 'admin');

        $this->get(route('admin.two-factor.log'))->assertOk()->assertSee('olga@example.org')->assertSee('admin@example.com')->assertSee('10.0.0.3');
        $this->get(route('admin.two-factor.log', ['guard' => 'people']))->assertSee('olga@example.org')->assertDontSee('10.0.0.1');
        $this->get(route('admin.two-factor.log', ['event' => 'signed_in']))->assertSee('10.0.0.1')->assertDontSee('10.0.0.2');
        $this->get(route('admin.two-factor.log', ['user' => $person->id, 'guard' => 'people']))->assertSee('10.0.0.2')->assertDontSee('10.0.0.3');
        $this->get(route('admin.two-factor.log', ['user' => 'not-a-number']))->assertSessionHasErrors('user');
    }

    public function test_people_are_listed_with_their_factors_and_searched_by_email(): void
    {
        $admin = $this->admin();
        $this->totp($admin);
        $this->person();
        $this->person(['email' => 'ivan@example.org']);
        $this->actingAs($admin, 'admin');

        $this->get(route('admin.two-factor.users'))->assertOk()->assertSee('admin@example.com')->assertSee(__('atrium-2fa::two-factor.factors.totp'));
        $this->get(route('admin.two-factor.users', ['guard' => 'people', 'q' => 'ivan']))->assertSee('ivan@example.org')->assertDontSee('olga@example.org');
        // A search with wildcards finds nobody rather than everybody.
        $this->get(route('admin.two-factor.users', ['guard' => 'people', 'q' => '%']))->assertDontSee('ivan@example.org');
        $this->get(route('admin.two-factor.users', ['guard' => 'nope']))->assertNotFound();
    }

    public function test_a_reset_takes_away_every_factor_code_and_device_and_is_logged(): void
    {
        $admin = $this->admin();
        $person = $this->person();
        $this->totp($person, 'people');
        app(RecoveryCodes::class)->generate('people', $person->id);
        TrustedDevice::query()->create(['guard' => 'people', 'user_id' => $person->id, 'token_hash' => str_repeat('a', 64), 'expires_at' => Carbon::now()->addDay()]);
        $mine = $this->totp($admin);
        $this->actingAs($admin, 'admin');

        $this->from(route('admin.two-factor.users', ['guard' => 'people']))
            ->post(route('admin.two-factor.reset'), ['guard' => 'people', 'user' => $person->id])
            ->assertRedirect(route('admin.two-factor.users', ['guard' => 'people']))
            ->assertSessionHas('atrium-2fa.status');

        $this->assertSame(0, Factor::query()->where('guard', 'people')->count());
        $this->assertSame(0, RecoveryCode::query()->count());
        $this->assertSame(0, TrustedDevice::query()->count());
        $this->assertNotNull($mine->fresh(), 'nobody else is touched');

        $event = Event::query()->where('event', 'reset')->sole();
        $this->assertSame('people', $event->guard);
        $this->assertSame($person->id, (int) $event->user_id);
        $this->assertSame($admin->id, (int) $event->actor_id);
        $this->assertSame(1, $event->meta['factors']);
    }

    public function test_a_reset_needs_a_known_guard_and_a_person(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->post(route('admin.two-factor.reset'), ['guard' => 'nope', 'user' => 1])->assertNotFound();
        $this->post(route('admin.two-factor.reset'), ['guard' => 'admin'])->assertSessionHasErrors('user');
    }
}

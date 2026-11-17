<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Tests\Unit;

use Atrium\Admin\Facades\Setting;
use Atrium\TwoFactor\Guards;
use Atrium\TwoFactor\Tests\TestCase;
use Illuminate\Auth\GenericUser;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * How each guard is set up: the config, and the panel's settings over it.
 */
final class GuardsTest extends TestCase
{
    private function guards(): Guards
    {
        return $this->app->make(Guards::class);
    }

    /**
     * @return array<string,array{array<string,mixed>,list<string>}>
     */
    public static function guardLists(): array
    {
        return [
            'switched on, known to Laravel' => [['people' => ['enabled' => true]], ['people']],
            'switched off' => [['people' => ['enabled' => false]], []],
            'not said whether it is on' => [['people' => ['prefix' => 'p']], []],
            'on, but Laravel has no such guard' => [['ghost' => ['enabled' => true]], []],
            'a guard named by a number' => [['7' => ['enabled' => true]], ['7']],
            'several, in the order of the config' => [['people' => ['enabled' => true], 'admin' => ['enabled' => true]], ['people', 'admin']],
        ];
    }

    /**
     * @param  array<string,mixed>  $config
     * @param  list<string>  $names
     */
    #[DataProvider('guardLists')]
    public function test_only_guards_that_are_on_and_known_to_laravel_are_served(array $config, array $names): void
    {
        config(['auth.guards.7' => ['driver' => 'session', 'provider' => 'people'], 'atrium-2fa.guards' => $config]);

        $this->assertSame($names, $this->guards()->names());

        foreach ($names as $name) {
            $this->assertTrue($this->guards()->has($name));
        }
    }

    public function test_no_guards_in_the_config_means_none_are_served(): void
    {
        config(['atrium-2fa.guards' => null]);

        $this->assertSame([], $this->guards()->names());
        $this->assertFalse($this->guards()->has('admin'));
    }

    public function test_the_roles_and_factors_come_as_a_list_of_strings_and_the_settings_win(): void
    {
        config(['atrium-2fa.guards.admin.required_roles' => ['a' => 3, 'b' => 5], 'atrium-2fa.guards.admin.factors' => ['x' => 'totp', 'y' => 'sms']]);

        $this->assertSame(['3', '5'], $this->guards()->requiredRoles('admin'));
        $this->assertSame(['totp', 'sms'], $this->guards()->factors('admin'));

        Setting::save('two_factor', ['admin_roles' => [8], 'admin_factors' => ['passkey']]);

        $this->assertSame(['8'], $this->guards()->requiredRoles('admin'));
        $this->assertSame(['passkey'], $this->guards()->factors('admin'));
    }

    public function test_a_factor_written_as_a_number_is_still_named_as_text(): void
    {
        config(['atrium-2fa.guards.people.factors' => ['totp', 2026]]);

        $this->assertSame(['totp', '2026'], $this->guards()->factors('people'));
        $this->assertTrue($this->guards()->allows('people', '2026'));
    }

    public function test_switching_the_old_e_mailed_code_off_takes_e_mail_off_the_panel_only(): void
    {
        config([
            'atrium.auth.two_factor.enabled' => false,
            'atrium-2fa.guards.admin.factors' => ['totp', 'email', 'sms'],
            'atrium-2fa.guards.people.factors' => ['totp', 'email', 'sms'],
        ]);

        $this->assertSame(['totp', 'sms'], $this->guards()->factors('admin'));
        $this->assertSame(['totp', 'email', 'sms'], $this->guards()->factors('people'));

        config(['atrium.auth.two_factor.enabled' => true]);
        $this->assertSame(['totp', 'email', 'sms'], $this->guards()->factors('admin'));
    }

    public function test_the_ways_in_without_a_password_follow_the_settings_over_the_config(): void
    {
        config(['atrium-2fa.guards.admin.passwordless' => true, 'atrium-2fa.guards.admin.magic_code' => true]);
        $this->assertTrue($this->guards()->passwordless('admin'));
        $this->assertTrue($this->guards()->magicCode('admin'));

        Setting::save('two_factor', ['admin_passwordless' => '0', 'admin_magic_code' => '0']);
        $this->assertFalse($this->guards()->passwordless('admin'));
        $this->assertFalse($this->guards()->magicCode('admin'));

        Setting::save('two_factor', ['admin_passwordless' => '1', 'admin_magic_code' => '1', 'admin_factors' => ['totp']]);
        $this->assertFalse($this->guards()->passwordless('admin'), 'a passkey alone needs passkeys allowed');
        $this->assertTrue($this->guards()->magicCode('admin'));
    }

    public function test_a_guard_that_does_not_mention_the_ways_in_has_neither(): void
    {
        config(['atrium-2fa.guards.people' => ['enabled' => true, 'factors' => ['passkey']]]);

        $this->assertFalse($this->guards()->passwordless('people'));
        $this->assertFalse($this->guards()->magicCode('people'));
        $this->assertSame(0, $this->guards()->trustedDays('people'));
        $this->assertSame([], $this->guards()->requiredRoles('people'));
    }

    /**
     * @return array<string,array{mixed,mixed,int}>
     */
    public static function trustedDays(): array
    {
        return [
            'from the config' => [30, null, 30],
            'none' => [0, null, 0],
            'negative is none' => [-5, null, 0],
            'the setting wins' => [30, '7', 7],
            'a setting of zero wins' => [30, '0', 0],
            'an empty setting keeps the config' => [30, '', 30],
        ];
    }

    #[DataProvider('trustedDays')]
    public function test_trusted_days_are_never_negative(mixed $config, mixed $setting, int $days): void
    {
        config(['atrium-2fa.guards.people.trusted_days' => $config]);

        if ($setting !== null) {
            Setting::save('two_factor', ['people_trusted_days' => $setting]);
        }

        $this->assertSame($days, $this->guards()->trustedDays('people'));
    }

    public function test_the_panel_keeps_its_own_code_lifetime_and_pause_and_others_use_the_package_s(): void
    {
        config([
            'atrium.auth.two_factor.ttl' => 3, 'atrium.auth.two_factor.resend_after' => 20,
            'atrium-2fa.codes.ttl' => 7, 'atrium-2fa.codes.resend_after' => 45,
        ]);

        $this->assertSame([3, 20], [$this->guards()->codeTtl('admin'), $this->guards()->resendAfter('admin')]);
        $this->assertSame([7, 45], [$this->guards()->codeTtl('people'), $this->guards()->resendAfter('people')]);

        config(['atrium.auth.two_factor' => ['enabled' => true]]);
        $this->assertSame([7, 45], [$this->guards()->codeTtl('admin'), $this->guards()->resendAfter('admin')]);

        config(['atrium-2fa.codes' => []]);
        $this->assertSame([10, 60], [$this->guards()->codeTtl('admin'), $this->guards()->resendAfter('admin')]);
        $this->assertSame([10, 60], [$this->guards()->codeTtl('people'), $this->guards()->resendAfter('people')]);
    }

    public function test_routes_are_named_and_built_per_guard(): void
    {
        $this->assertSame('admin.two-factor.challenge', $this->guards()->routeName('admin', 'challenge'));
        $this->assertSame('atrium-2fa.people.challenge', $this->guards()->routeName('people', 'challenge'));
        $this->assertSame(url('/people/auth/challenge'), $this->guards()->route('people', 'challenge'));
        $this->assertSame(url('/people/auth/challenge?recovery=1'), $this->guards()->route('people', 'challenge', ['recovery' => 1]));
        $this->assertSame(url('/people/login'), $this->guards()->loginUrl('people'));
        $this->assertSame(url('/people/home'), $this->guards()->url('people', 'people.home'));
    }

    public function test_a_route_of_a_localized_guard_is_built_in_the_visitor_s_language(): void
    {
        $this->assertSame(url('/people/auth/code'), $this->guards()->url('people', 'atrium-2fa.people.magic'));

        config(['atrium-2fa.guards.people.localized' => true]);
        $this->app->setLocale('de');

        // The site has no German pages: ContentLanguages asks for the
        // localized route, which proves the guard was taken as localized.
        $this->expectException(RouteNotFoundException::class);
        $this->guards()->url('people', 'atrium-2fa.people.magic');
    }

    public function test_people_are_found_by_id_or_e_mail_only_while_they_may_sign_in(): void
    {
        $olga = $this->person(['email' => 'olga@example.org']);
        $this->person(['email' => 'ольга@пример.бел']);

        $this->assertTrue($olga->is($this->guards()->find('people', $olga->id)));
        $this->assertTrue($olga->is($this->guards()->findByEmail('people', '  OLGA@Example.ORG ')));
        $this->assertSame('ольга@пример.бел', $this->guards()->findByEmail('people', 'ОЛЬГА@ПРИМЕР.БЕЛ')?->email);
        $this->assertNull($this->guards()->find('people', null));
        $this->assertNull($this->guards()->find('people', 999));
        $this->assertNull($this->guards()->findByEmail('people', 'nobody@example.org'));

        $olga->forceFill(['active' => false])->save();
        $this->assertNull($this->guards()->find('people', $olga->id));
        $this->assertNull($this->guards()->findByEmail('people', 'olga@example.org'));
    }

    public function test_a_guard_whose_people_cannot_be_looked_up_finds_nobody(): void
    {
        config(['auth.guards.ghost' => ['driver' => 'session', 'provider' => 'nowhere']]);

        $this->assertNull($this->guards()->find('ghost', 1));
        $this->assertNull($this->guards()->findByEmail('ghost', 'olga@example.org'));
    }

    /**
     * @return array<string,array{?string,array<string,mixed>,bool}>
     */
    public static function activity(): array
    {
        return [
            'no column: everybody' => [null, ['active' => 0], true],
            'active' => ['active', ['active' => 1], true],
            'inactive' => ['active', ['active' => 0], false],
            'column missing on the person' => ['active', [], false],
            'another column' => ['is_active', ['is_active' => true, 'active' => 0], true],
        ];
    }

    /**
     * @param  array<string,mixed>  $attributes
     */
    #[DataProvider('activity')]
    public function test_whether_a_person_may_sign_in_is_read_from_the_guard_s_column(?string $column, array $attributes, bool $active): void
    {
        config(['atrium-2fa.guards.people.active_column' => $column]);

        $this->assertSame($active, $this->guards()->active('people', new GenericUser(['id' => 1, ...$attributes])));
    }

    public function test_the_panel_s_guard_is_the_one_atrium_signs_in_with(): void
    {
        $this->assertSame('admin', $this->guards()->adminGuard());

        config(['atrium.auth.guard' => 'staff']);
        $this->assertSame('staff', $this->guards()->adminGuard());
        $this->assertSame('atrium-2fa.admin.challenge', $this->guards()->routeName('admin', 'challenge'));
    }

    /**
     * @return array<string,array{mixed,mixed,string}>
     */
    public static function modes(): array
    {
        return [
            'all from the config' => ['all', null, Guards::ALL],
            'roles from the config' => ['roles', null, Guards::ROLES],
            'optional from the config' => ['optional', null, Guards::OPTIONAL],
            'nonsense is optional' => ['sometimes', null, Guards::OPTIONAL],
            'the setting wins' => ['all', 'roles', Guards::ROLES],
            'an empty setting keeps the config' => ['all', '', Guards::ALL],
        ];
    }

    #[DataProvider('modes')]
    public function test_who_must_pass_a_second_step(mixed $config, mixed $setting, string $mode): void
    {
        config(['atrium-2fa.guards.people.required' => $config]);

        if ($setting !== null) {
            Setting::save('two_factor', ['people_required' => $setting]);
        }

        $this->assertSame($mode, $this->guards()->required('people'));
    }

    public function test_the_panel_without_a_mode_follows_the_old_switch_and_other_guards_are_optional(): void
    {
        config(['atrium-2fa.guards.admin.required' => null, 'atrium-2fa.guards.people.required' => null]);

        $this->assertSame(Guards::ALL, $this->guards()->required('admin'));
        $this->assertSame(Guards::OPTIONAL, $this->guards()->required('people'));

        config(['atrium.auth.two_factor.enabled' => false]);
        $this->assertSame(Guards::OPTIONAL, $this->guards()->required('admin'));
    }
}

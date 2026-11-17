<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Tests\Feature;

use Atrium\Admin\Fields\Field;
use Atrium\Admin\Models\AdminRole;
use Atrium\TwoFactor\Tests\TestCase;
use Atrium\TwoFactor\TwoFactorSettings;

/**
 * Modules → Two-factor sign-in: one block of fields for every guard served,
 * each field saying which guard it is about.
 */
final class SettingsFormTest extends TestCase
{
    /**
     * @return array<string, array<string, mixed>>
     */
    private function fields(): array
    {
        // The file first, so the extra line goes next to it, not instead of it.
        __('atrium-2fa::two-factor.guards.admin');
        app('translator')->addLines(['two-factor.guards.people' => 'Personal area'], 'en', 'atrium-2fa');

        $fields = [];

        foreach ($this->app->make(TwoFactorSettings::class)->fields() as $field) {
            $this->assertInstanceOf(Field::class, $field);
            $fields[$field->getName()] = $field->toArray() + ['rules' => $field->getValidationRules()];
        }

        return $fields;
    }

    public function test_every_served_guard_gets_its_block_and_only_the_panel_has_roles(): void
    {
        $this->assertSame([
            'admin_required', 'admin_roles', 'admin_factors', 'admin_passwordless', 'admin_magic_code', 'admin_trusted_days',
            'people_required', 'people_factors', 'people_passwordless', 'people_magic_code', 'people_trusted_days',
        ], array_keys($this->fields()));
    }

    public function test_every_label_names_the_guard_it_is_about(): void
    {
        $labels = array_map(fn (array $field): string => $field['label'], $this->fields());

        foreach (['required', 'roles', 'factors', 'passwordless', 'magic_code', 'trusted_days'] as $key) {
            $this->assertSame(__("atrium-2fa::two-factor.settings.{$key}", ['who' => 'Admin panel']), $labels["admin_{$key}"], $key);
            $this->assertStringStartsWith('Admin panel: ', $labels["admin_{$key}"]);
        }

        $this->assertSame('Personal area: who must pass a second step', $labels['people_required']);
        $this->assertSame('Personal area: allowed ways to confirm', $labels['people_factors']);
    }

    public function test_the_choices_offered_and_what_an_empty_field_means(): void
    {
        $fields = $this->fields();
        $options = fn (string $name): array => array_column($fields[$name]['options'], 'label', 'value');

        $this->assertSame([
            'all' => __('atrium-2fa::two-factor.settings.modes.all'),
            'roles' => __('atrium-2fa::two-factor.settings.modes.roles'),
            'optional' => __('atrium-2fa::two-factor.settings.modes.optional'),
        ], $options('people_required'));
        $this->assertSame([
            'totp' => __('atrium-2fa::two-factor.factors.totp'),
            'passkey' => __('atrium-2fa::two-factor.factors.passkey'),
            'email' => __('atrium-2fa::two-factor.factors.email'),
            'sms' => __('atrium-2fa::two-factor.factors.sms'),
        ], $options('people_factors'));
        $this->assertSame([1 => 'Yes', 0 => 'No'], $options('people_passwordless'));
        $this->assertSame([1 => 'Yes', 0 => 'No'], $options('admin_magic_code'));

        $this->assertTrue($fields['people_required']['clearable']);
        $this->assertTrue($fields['people_passwordless']['clearable']);
        $this->assertTrue($fields['people_magic_code']['clearable']);
        $this->assertSame(__('atrium-2fa::two-factor.settings.from_config'), $fields['people_required']['hint']);
        $this->assertSame(__('atrium-2fa::two-factor.settings.from_config'), $fields['people_factors']['hint']);
        $this->assertSame(__('atrium-2fa::two-factor.settings.trusted_days_hint'), $fields['people_trusted_days']['hint']);
        $this->assertSame(AdminRole::class, $fields['admin_roles']['attributes']['modelClass']);
        $this->assertSame(['nullable', 'integer', 'min:0', 'max:365', 'numeric'], $fields['people_trusted_days']['rules']);
    }

    public function test_no_guard_served_means_no_fields(): void
    {
        config(['atrium-2fa.guards' => []]);

        $this->assertSame([], $this->app->make(TwoFactorSettings::class)->fields());
    }
}

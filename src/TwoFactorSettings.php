<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor;

use Atrium\Admin\Fields\MultiSelectField;
use Atrium\Admin\Fields\NumberField;
use Atrium\Admin\Fields\SelectField;
use Atrium\Admin\Models\AdminRole;
use Atrium\Admin\Modules\ModuleSettings;

/**
 * Modules → Two-factor sign-in: for every guard, who must pass a second
 * step, with which factors, and the ways in without a password. An empty
 * field keeps what config/atrium-2fa.php says.
 */
final class TwoFactorSettings extends ModuleSettings
{
    public function __construct(private readonly Guards $guards) {}

    public function description(): string
    {
        return __('atrium-2fa::two-factor.settings.description');
    }

    public function fields(): array
    {
        $fields = [];

        foreach ($this->guards->names() as $guard) {
            $who = __('atrium-2fa::two-factor.guards.'.$guard);
            $yesNo = ['1' => __('atrium-2fa::two-factor.settings.yes'), '0' => __('atrium-2fa::two-factor.settings.no')];

            $fields[] = SelectField::make("{$guard}_required")
                ->label(__('atrium-2fa::two-factor.settings.required', ['who' => $who]))
                ->hint(__('atrium-2fa::two-factor.settings.from_config'))
                ->options([
                    Guards::ALL => __('atrium-2fa::two-factor.settings.modes.all'),
                    Guards::ROLES => __('atrium-2fa::two-factor.settings.modes.roles'),
                    Guards::OPTIONAL => __('atrium-2fa::two-factor.settings.modes.optional'),
                ])
                ->clearable();

            if ($guard === $this->guards->adminGuard()) {
                $fields[] = MultiSelectField::make("{$guard}_roles")
                    ->label(__('atrium-2fa::two-factor.settings.roles', ['who' => $who]))
                    ->fromModel(AdminRole::class);
            }

            $fields[] = MultiSelectField::make("{$guard}_factors")
                ->label(__('atrium-2fa::two-factor.settings.factors', ['who' => $who]))
                ->hint(__('atrium-2fa::two-factor.settings.from_config'))
                ->options([
                    'totp' => __('atrium-2fa::two-factor.factors.totp'),
                    'passkey' => __('atrium-2fa::two-factor.factors.passkey'),
                    'email' => __('atrium-2fa::two-factor.factors.email'),
                    'sms' => __('atrium-2fa::two-factor.factors.sms'),
                ]);

            $fields[] = SelectField::make("{$guard}_passwordless")
                ->label(__('atrium-2fa::two-factor.settings.passwordless', ['who' => $who]))
                ->options($yesNo)
                ->clearable();

            $fields[] = SelectField::make("{$guard}_magic_code")
                ->label(__('atrium-2fa::two-factor.settings.magic_code', ['who' => $who]))
                ->options($yesNo)
                ->clearable();

            $fields[] = NumberField::make("{$guard}_trusted_days")
                ->label(__('atrium-2fa::two-factor.settings.trusted_days', ['who' => $who]))
                ->hint(__('atrium-2fa::two-factor.settings.trusted_days_hint'))
                ->rules(['integer', 'min:0', 'max:365']);
        }

        return $fields;
    }
}

<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;

/**
 * A second factor. Register your own with
 * `app(Atrium\TwoFactor\Factors::class)->extend(new MyFactor)`, and list its
 * key in a guard's `factors`; its name on screens is the translation
 * `atrium-2fa::two-factor.factors.{key}` unless label() says otherwise.
 */
interface Factor
{
    public function key(): string;

    public function label(): string;

    /**
     * What the challenge page asks for: "code" (a field for digits) or
     * "webauthn" (a button that asks the browser for a passkey).
     */
    public function input(): string;

    /**
     * The person has set this factor up and may pass the second step with it.
     */
    public function enrolled(Authenticatable $user, string $guard): bool;

    /**
     * The person picked it at the challenge: send a code if it needs one.
     *
     * @return bool false when nothing could be sent right now (too soon)
     */
    public function prepare(Authenticatable $user, string $guard): bool;

    /**
     * Check the person's answer.
     */
    public function verify(Authenticatable $user, string $guard, Request $request): bool;
}

<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Services;

use Atrium\TwoFactor\Models\Event;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Str;

/**
 * The sign-in log. Other modules write to it through the action
 * `atrium.auth.event` (guard, user or null, event, method or null, meta),
 * so they need no dependency on this package.
 */
final class Audit
{
    public const SIGNED_IN = 'signed_in';

    public const CHALLENGED = 'challenged';

    public const FAILED = 'failed';

    public const LOCKED = 'locked';

    public const TRUSTED_DEVICE = 'trusted_device';

    public const FACTOR_ADDED = 'factor_added';

    public const FACTOR_REMOVED = 'factor_removed';

    public const RECOVERY_USED = 'recovery_used';

    public const RECOVERY_GENERATED = 'recovery_generated';

    public const DEVICE_TRUSTED = 'device_trusted';

    public const DEVICE_REVOKED = 'device_revoked';

    public const RESET = 'reset';

    public const CODE_SENT = 'code_sent';

    /**
     * @param  array<string,mixed>  $meta
     */
    public function record(
        string $guard,
        Authenticatable|int|string|null $user,
        string $event,
        ?string $method = null,
        array $meta = [],
        ?Authenticatable $actor = null,
        ?string $actorGuard = null,
    ): Event {
        $request = request();

        return Event::query()->create([
            'guard' => $guard,
            'user_id' => $user instanceof Authenticatable ? $user->getAuthIdentifier() : $user,
            'event' => Str::limit($event, 40, ''),
            'method' => $method === null ? null : Str::limit($method, 40, ''),
            'ip' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
            'actor_guard' => $actor === null ? null : $actorGuard,
            'actor_id' => $actor?->getAuthIdentifier(),
            'meta' => $meta === [] ? null : $meta,
        ]);
    }
}

<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Support;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * A passkey is checked by a script, which cannot follow a redirect it did
 * not ask for: the redirect becomes {"redirect": url}, cookies kept.
 */
final class Redirects
{
    public static function forScript(Response $response): Response
    {
        if (! $response instanceof RedirectResponse) {
            return $response;
        }

        $json = new JsonResponse(['redirect' => $response->getTargetUrl()]);

        foreach ($response->headers->getCookies() as $cookie) {
            $json->headers->setCookie($cookie);
        }

        return $json;
    }
}

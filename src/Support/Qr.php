<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Support;

use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * A QR code as inline SVG, black on white whatever the theme: phone cameras
 * read dark modules on a light ground best, and the contrast is 21:1.
 */
final class Qr
{
    public static function svg(string $text, int $size = 200): string
    {
        $style = new RendererStyle($size, 2, null, null, Fill::uniformColor(new Rgb(255, 255, 255), new Rgb(0, 0, 0)));
        $svg = (new Writer(new ImageRenderer($style, new SvgImageBackEnd)))->writeString($text);

        // The XML prolog is not allowed inside HTML.
        return trim((string) preg_replace('/^<\?xml[^>]*\?>/', '', $svg));
    }
}

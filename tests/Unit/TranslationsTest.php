<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Tests\Unit;

use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Every screen and letter of the package in all fifteen languages, with the
 * same keys and the same :placeholders as the English text.
 */
final class TranslationsTest extends TestCase
{
    private const LOCALES = ['en', 'ru', 'uk', 'be', 'kk', 'uz', 'de', 'fr', 'es', 'pt_BR', 'it', 'pl', 'tr', 'zh_CN', 'ja'];

    /**
     * @return array<string, array{string}>
     */
    public static function locales(): array
    {
        return array_combine(self::LOCALES, array_map(fn (string $l): array => [$l], self::LOCALES));
    }

    /**
     * @return array<string,string>
     */
    private static function strings(string $locale): array
    {
        $file = __DIR__.'/../../lang/'.$locale.'/two-factor.php';
        self::assertFileExists($file);

        return Arr::dot(require $file);
    }

    #[DataProvider('locales')]
    public function test_the_language_has_every_key_and_placeholder_of_english(string $locale): void
    {
        $english = self::strings('en');
        $strings = self::strings($locale);

        $this->assertSame([], array_values(array_diff(array_keys($english), array_keys($strings))), 'missing');
        $this->assertSame([], array_values(array_diff(array_keys($strings), array_keys($english))), 'extra');

        foreach ($english as $key => $text) {
            preg_match_all('/:([a-z_]+)/', (string) $text, $want);
            preg_match_all('/:([a-z_]+)/', (string) $strings[$key], $have);
            $this->assertEqualsCanonicalizing(array_unique($want[1]), array_unique($have[1]), "{$locale}: {$key}");
            $this->assertNotSame('', trim((string) $strings[$key]), "{$locale}: {$key} is empty");
        }
    }

    public function test_there_are_exactly_fifteen_languages(): void
    {
        $dirs = array_map('basename', glob(__DIR__.'/../../lang/*', GLOB_ONLYDIR) ?: []);
        sort($dirs);
        $expected = self::LOCALES;
        sort($expected);

        $this->assertSame($expected, $dirs);
    }
}

<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Support;

use Illuminate\Support\Carbon;

/**
 * Time-based one-time passwords, RFC 6238 over RFC 4226 (HOTP), as the
 * authenticator apps make them: HMAC-SHA1, 6 digits, 30-second steps.
 *
 * verify() accepts the codes of a few steps before and after now (the
 * window), for a phone whose clock is off, and never a step at or before the
 * last one used, so a code that was seen once cannot be replayed.
 */
final class Totp
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public function __construct(
        private readonly int $digits = 6,
        private readonly int $period = 30,
    ) {}

    public static function fromConfig(): self
    {
        return new self((int) config('atrium-2fa.totp.digits', 6), (int) config('atrium-2fa.totp.period', 30));
    }

    /**
     * A new secret: 20 random bytes (160 bits, as RFC 4226 recommends), in Base32.
     */
    public static function secret(): string
    {
        return self::base32(random_bytes(20));
    }

    public function step(?int $time = null): int
    {
        return intdiv($time ?? Carbon::now()->getTimestamp(), $this->period);
    }

    /**
     * The code of a time step.
     */
    public function at(string $secret, int $step): string
    {
        $hash = hash_hmac('sha1', pack('J', $step), self::unbase32($secret), true);
        $offset = ord($hash[19]) & 0x0F;
        $number = ((ord($hash[$offset]) & 0x7F) << 24)
            | (ord($hash[$offset + 1]) << 16)
            | (ord($hash[$offset + 2]) << 8)
            | ord($hash[$offset + 3]);

        return str_pad((string) ($number % 10 ** $this->digits), $this->digits, '0', STR_PAD_LEFT);
    }

    /**
     * The step a code belongs to, or null when it is wrong, outside the
     * window or not newer than $lastStep (null: no step used yet). Anything
     * that is not a code simply matches none of the steps.
     */
    public function verify(string $secret, string $code, ?int $lastStep = null, int $window = 1, ?int $time = null): ?int
    {
        $code = preg_replace('/\s+/', '', $code) ?? '';
        $now = $this->step($time);

        for ($step = $now - $window; $step <= $now + $window; $step++) {
            if (($lastStep === null || $step > $lastStep) && hash_equals($this->at($secret, $step), $code)) {
                return $step;
            }
        }

        return null;
    }

    /**
     * The otpauth:// address the QR code holds.
     */
    public function uri(string $secret, string $issuer, string $account): string
    {
        return 'otpauth://totp/'.rawurlencode($issuer).':'.rawurlencode($account).'?'.http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => 'SHA1',
            'digits' => $this->digits,
            'period' => $this->period,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    public static function base32(string $bytes): string
    {
        $bits = '';

        foreach (str_split($bytes) as $byte) {
            $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }

        $out = '';

        foreach (str_split($bits, 5) as $chunk) {
            $out .= self::ALPHABET[bindec(str_pad($chunk, 5, '0'))];
        }

        return $out;
    }

    public static function unbase32(string $text): string
    {
        $text = strtoupper(preg_replace('/[\s=-]+/', '', $text) ?? '');
        $bits = '';

        foreach (str_split($text) as $char) {
            $value = strpos(self::ALPHABET, $char);

            if ($value === false) {
                throw new \InvalidArgumentException('Not a Base32 string.');
            }

            $bits .= str_pad(decbin($value), 5, '0', STR_PAD_LEFT);
        }

        $out = '';

        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $out .= chr((int) bindec($byte));
            }
        }

        return $out;
    }
}

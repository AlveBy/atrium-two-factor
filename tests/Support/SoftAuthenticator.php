<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Tests\Support;

use OpenSSLAsymmetricKey;
use RuntimeException;

/**
 * A passkey in software, for the tests: a P-256 key that answers the
 * browser's navigator.credentials calls the way a real authenticator does
 * ("none" attestation, ES256 signatures, a counter).
 */
final class SoftAuthenticator
{
    public readonly string $credentialId;

    private OpenSSLAsymmetricKey $key;

    public int $counter = 0;

    public function __construct(
        public string $rpId = 'localhost',
        public string $origin = 'http://localhost',
    ) {
        $key = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);

        if ($key === false) {
            throw new RuntimeException('No EC support in OpenSSL.');
        }

        $this->key = $key;
        $this->credentialId = random_bytes(32);
    }

    /**
     * What navigator.credentials.create() hands back for these options.
     *
     * @param  array<string,mixed>  $options
     * @return array<string,mixed>
     */
    public function create(array $options, ?string $challenge = null, bool $userVerified = true, bool $userPresent = true): array
    {
        $clientData = $this->clientData('webauthn.create', $challenge ?? (string) $options['publicKey']['challenge']);

        $details = openssl_pkey_get_details($this->key);
        $cose = self::cbor([
            1 => 2,
            3 => -7,
            -1 => 1,
            -2 => new Bytes(str_pad($details['ec']['x'], 32, "\0", STR_PAD_LEFT)),
            -3 => new Bytes(str_pad($details['ec']['y'], 32, "\0", STR_PAD_LEFT)),
        ]);

        $authData = hash('sha256', $this->rpId, true)
            .chr(0x40 | ($userPresent ? 0x01 : 0) | ($userVerified ? 0x04 : 0))
            .pack('N', $this->counter)
            .str_repeat("\0", 16)
            .pack('n', strlen($this->credentialId))
            .$this->credentialId
            .$cose;

        return [
            'id' => self::b64($this->credentialId),
            'rawId' => self::b64($this->credentialId),
            'type' => 'public-key',
            'response' => [
                'clientDataJSON' => self::b64($clientData),
                'attestationObject' => self::b64(self::cbor(['fmt' => 'none', 'attStmt' => [], 'authData' => new Bytes($authData)])),
            ],
        ];
    }

    /**
     * What navigator.credentials.get() hands back for these options.
     *
     * @param  array<string,mixed>  $options
     * @return array<string,mixed>
     */
    public function get(array $options, bool $userVerified = true, ?int $counter = null): array
    {
        $this->counter = $counter ?? $this->counter + 1;
        $clientData = $this->clientData('webauthn.get', (string) $options['publicKey']['challenge']);
        $authData = hash('sha256', $this->rpId, true).chr(0x01 | ($userVerified ? 0x04 : 0)).pack('N', $this->counter);

        openssl_sign($authData.hash('sha256', $clientData, true), $signature, $this->key, OPENSSL_ALGO_SHA256);

        return [
            'id' => self::b64($this->credentialId),
            'rawId' => self::b64($this->credentialId),
            'type' => 'public-key',
            'response' => [
                'clientDataJSON' => self::b64($clientData),
                'authenticatorData' => self::b64($authData),
                'signature' => self::b64($signature),
                'userHandle' => null,
            ],
        ];
    }

    public static function b64(string $binary): string
    {
        return rtrim(strtr(base64_encode($binary), '+/', '-_'), '=');
    }

    private function clientData(string $type, string $challenge): string
    {
        return (string) json_encode(['type' => $type, 'challenge' => $challenge, 'origin' => $this->origin, 'crossOrigin' => false]);
    }

    /**
     * Just enough CBOR: maps, text, byte strings and small integers.
     */
    private static function cbor(mixed $value): string
    {
        return match (true) {
            $value instanceof Bytes => self::head(2, strlen($value->value)).$value->value,
            is_string($value) => self::head(3, strlen($value)).$value,
            is_int($value) && $value >= 0 => self::head(0, $value),
            is_int($value) => self::head(1, -1 - $value),
            is_array($value) => self::head(5, count($value)).implode('', array_map(
                fn ($k, $v): string => self::cbor($k).self::cbor($v),
                array_keys($value),
                $value,
            )),
            default => throw new RuntimeException('Not in this CBOR subset.'),
        };
    }

    private static function head(int $major, int $length): string
    {
        $type = $major << 5;

        return match (true) {
            $length < 24 => chr($type | $length),
            $length < 256 => chr($type | 24).chr($length),
            $length < 65536 => chr($type | 25).pack('n', $length),
            default => chr($type | 26).pack('N', $length),
        };
    }
}

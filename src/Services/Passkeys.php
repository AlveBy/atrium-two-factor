<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Services;

use Atrium\TwoFactor\Models\Factor;
use Atrium\TwoFactor\Support\Person;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use lbuchs\WebAuthn\Binary\ByteBuffer;
use lbuchs\WebAuthn\WebAuthn;
use lbuchs\WebAuthn\WebAuthnException;

/**
 * Passkeys (WebAuthn): the options the browser needs and the check of what
 * it sends back. The challenge waits in the session under a purpose, so a
 * challenge made for setting a passkey up cannot sign anybody in.
 */
final class Passkeys
{
    /** The second step of a sign-in. */
    public const CHALLENGE = 'challenge';

    /** A sign-in with the passkey alone. */
    public const PASSWORDLESS = 'passwordless';

    /** Setting a passkey up. */
    public const REGISTER = 'register';

    /**
     * Options for navigator.credentials.create().
     *
     * @return array<string,mixed>
     */
    public function registrationOptions(Request $request, string $guard, Authenticatable $user): array
    {
        $webauthn = $this->webauthn($request);
        $exclude = $this->credentialIds($guard, $user->getAuthIdentifier());
        $email = Person::email($user);
        $email = $email !== '' ? $email : (string) $user->getAuthIdentifier();
        $name = Person::name($user);

        $args = $webauthn->getCreateArgs(
            hash('sha256', $guard.'|'.$user->getAuthIdentifier(), true),
            $email,
            $name !== '' ? $name : $email,
            $this->timeout(),
            'preferred',
            'preferred',
            null,
            $exclude,
        );

        $this->remember($request, $guard, self::REGISTER, $webauthn);

        return $this->toArray($args);
    }

    /**
     * Checks the browser's answer to registrationOptions() and stores the
     * passkey, confirmed, under the name the caller has already tidied up.
     */
    public function register(Request $request, string $guard, Authenticatable $user, ?string $name = null): ?Factor
    {
        $challenge = $this->pull($request, $guard, self::REGISTER);

        if ($challenge === null) {
            return null;
        }

        try {
            $data = $this->webauthn($request)->processCreate(
                $this->binary($request->input('response.clientDataJSON')),
                $this->binary($request->input('response.attestationObject')),
                $challenge,
            );
        } catch (WebAuthnException) {
            return null;
        }

        $credentialId = $data->credentialId;
        $hash = hash('sha256', $credentialId);

        if (Factor::query()->where('credential_hash', $hash)->exists()) {
            return null;
        }

        return Factor::query()->create([
            'guard' => $guard,
            'user_id' => $user->getAuthIdentifier(),
            'type' => 'passkey',
            'name' => $name,
            'credential_hash' => $hash,
            'data' => [
                'credential_id' => base64_encode($credentialId),
                'public_key' => $data->credentialPublicKey,
            ],
            'counter' => $data->signatureCounter ?? 0,
            'confirmed_at' => now(),
        ]);
    }

    /**
     * Options for navigator.credentials.get(). With a person, only their
     * passkeys are offered; without one (a sign-in by passkey alone), the
     * browser offers whatever passkeys it keeps for this site.
     *
     * @return array<string,mixed>
     */
    public function assertionOptions(Request $request, string $guard, string $purpose, ?Authenticatable $user = null): array
    {
        $webauthn = $this->webauthn($request);
        $ids = $user !== null ? $this->credentialIds($guard, $user->getAuthIdentifier()) : [];

        $args = $webauthn->getGetArgs($ids, $this->timeout(), requireUserVerification: $purpose === self::PASSWORDLESS ? 'required' : 'preferred');

        $this->remember($request, $guard, $purpose, $webauthn);

        return $this->toArray($args);
    }

    /**
     * Checks a signed challenge. Returns the passkey used, or null. With a
     * person, the passkey has to be theirs.
     */
    public function verifyAssertion(Request $request, string $guard, string $purpose, ?Authenticatable $user = null): ?Factor
    {
        $challenge = $this->pull($request, $guard, $purpose);

        if ($challenge === null) {
            return null;
        }

        try {
            $rawId = $this->binary($request->input('rawId', $request->input('id')));
            $clientData = $this->binary($request->input('response.clientDataJSON'));
            $authenticatorData = $this->binary($request->input('response.authenticatorData'));
            $signature = $this->binary($request->input('response.signature'));
        } catch (WebAuthnException) {
            return null;
        }

        $query = Factor::query()->where('guard', $guard)->where('type', 'passkey')->confirmed()
            ->where('credential_hash', hash('sha256', $rawId));

        if ($user !== null) {
            $query->where('user_id', $user->getAuthIdentifier());
        }

        $factor = $query->first();

        if ($factor === null) {
            return null;
        }

        $webauthn = $this->webauthn($request);

        try {
            $webauthn->processGet(
                $clientData,
                $authenticatorData,
                $signature,
                (string) ($factor->data['public_key'] ?? ''),
                $challenge,
                $factor->counter,
                $purpose === self::PASSWORDLESS,
            );
        } catch (WebAuthnException) {
            return null;
        }

        $factor->forceFill([
            'counter' => $webauthn->getSignatureCounter() ?? $factor->counter,
            'last_used_at' => now(),
        ])->save();

        return $factor;
    }

    /**
     * The relying party: the site's host unless config says otherwise.
     */
    public function rpId(Request $request): string
    {
        return (string) (config('atrium-2fa.webauthn.rp_id') ?: $request->getHost());
    }

    private function webauthn(Request $request): WebAuthn
    {
        return new WebAuthn(
            (string) (config('atrium-2fa.webauthn.rp_name') ?: Person::siteName()),
            $this->rpId($request),
            ['none', 'packed', 'fido-u2f', 'apple', 'android-key', 'tpm'],
            true,
        );
    }

    private function timeout(): int
    {
        return max(10, (int) config('atrium-2fa.webauthn.timeout', 60));
    }

    /**
     * @return list<string> raw credential ids of the person's passkeys
     */
    private function credentialIds(string $guard, int|string $userId): array
    {
        return Factor::query()->of($guard, $userId)->confirmed()->where('type', 'passkey')->get()
            ->map(fn (Factor $factor): string => base64_decode($factor->data['credential_id']))
            ->all();
    }

    private function remember(Request $request, string $guard, string $purpose, WebAuthn $webauthn): void
    {
        $request->session()->put($this->key($guard, $purpose), base64_encode($webauthn->getChallenge()->getBinaryString()));
    }

    private function pull(Request $request, string $guard, string $purpose): ?ByteBuffer
    {
        $stored = $request->session()->pull($this->key($guard, $purpose));

        return is_string($stored) ? new ByteBuffer(base64_decode($stored)) : null;
    }

    private function key(string $guard, string $purpose): string
    {
        return "atrium-2fa.webauthn.{$guard}.{$purpose}";
    }

    /**
     * What the browser sent, base64url, as bytes. A part that is missing or
     * not base64 is refused like any other bad answer.
     *
     * @throws WebAuthnException
     */
    private function binary(mixed $value): string
    {
        $decoded = is_string($value) ? base64_decode(strtr($value, '-_', '+/'), true) : false;

        if ($decoded === false) {
            throw new WebAuthnException('A part of the answer is missing or is not base64.', WebAuthnException::INVALID_DATA);
        }

        return $decoded;
    }

    /**
     * @return array<string,mixed>
     */
    private function toArray(object $args): array
    {
        return json_decode(json_encode($args, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);
    }
}

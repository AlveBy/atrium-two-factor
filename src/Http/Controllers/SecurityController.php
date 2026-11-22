<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\TwoFactor\Http\Controllers;

use Atrium\TwoFactor\Factors\SmsFactor;
use Atrium\TwoFactor\Guards;
use Atrium\TwoFactor\Models\Event;
use Atrium\TwoFactor\Models\Factor;
use Atrium\TwoFactor\Models\TrustedDevice;
use Atrium\TwoFactor\Services\Audit;
use Atrium\TwoFactor\Services\Challenge;
use Atrium\TwoFactor\Services\Codes;
use Atrium\TwoFactor\Services\Passkeys;
use Atrium\TwoFactor\Services\Policy;
use Atrium\TwoFactor\Services\RecoveryCodes;
use Atrium\TwoFactor\Services\TrustedDevices;
use Atrium\TwoFactor\Support\Person;
use Atrium\TwoFactor\Support\Qr;
use Atrium\TwoFactor\Support\Totp;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * The person's own security page: authenticator apps, passkeys, a phone for
 * SMS, the e-mailed code, recovery codes, trusted devices and the last
 * sign-ins.
 */
final class SecurityController extends Controller
{
    public function __construct(
        Guards $guards,
        private readonly Policy $policy,
        private readonly RecoveryCodes $recovery,
        private readonly TrustedDevices $devices,
        private readonly Passkeys $passkeys,
        private readonly Codes $codes,
        private readonly Audit $audit,
    ) {
        parent::__construct($guards);
    }

    public function index(Request $request): Response|InertiaResponse
    {
        $guard = $this->guard($request);
        $user = $this->user($request);

        // A public demo: nothing here may be changed, so there are no forms.
        if (config('atrium.demo.enabled')) {
            return response($this->view($request, 'security-demo'));
        }

        $id = $user->getAuthIdentifier();

        $factors = Factor::query()->of($guard, $id)->orderBy('id')->get();
        $totpSetup = $factors->first(fn (Factor $f): bool => $f->type === 'totp' && $f->confirmed_at === null);
        $issuer = (string) (config('atrium-2fa.totp.issuer') ?: Person::siteName());
        $confirmed = $factors->filter(fn (Factor $f): bool => $f->confirmed_at !== null)->values();
        $recoveryLeft = $this->recovery->remaining($guard, $id);
        $devices = TrustedDevice::query()->where('guard', $guard)->where('user_id', $id)->where('expires_at', '>', Carbon::now())->latest('id')->get();
        $events = Event::query()->where('guard', $guard)->where('user_id', $id)->latest('id')->limit(10)->get();
        $allowed = array_map(fn ($factor): string => $factor->key(), $this->policy->allowed($guard));
        $required = $this->policy->required($guard, $user);
        $mustEnrol = (bool) $request->session()->get(app(Challenge::class)->enrolKey($guard));
        $smsSetup = $factors->first(fn (Factor $f): bool => $f->type === 'sms' && $f->confirmed_at === null);
        $totpQr = $totpSetup !== null ? Qr::svg(Totp::fromConfig()->uri((string) $totpSetup->secret, $issuer, Person::email($user))) : null;
        $recoveryCodes = $request->session()->get('atrium-2fa.recovery_codes');
        $trustedDays = $this->guards->trustedDays($guard);
        $passwordless = $this->guards->passwordless($guard);

        if ($this->inPanel($request)) {
            return Inertia::render('Admin/TwoFactor/Security', [
                't' => trans('atrium-2fa::two-factor'),
                'status' => $request->session()->get('atrium-2fa.status'),
                'error' => $request->session()->get('atrium-2fa.error'),
                'mustEnrol' => $mustEnrol,
                'required' => $required,
                'passwordless' => $passwordless,
                'allowed' => $allowed,
                'recoveryCodes' => $recoveryCodes,
                'factors' => $confirmed->map(function (Factor $f) use ($guard): array {
                    /** @var Carbon $added Only confirmed factors are listed. */
                    $added = $f->confirmed_at;

                    return [
                        'id' => $f->id,
                        'type' => $f->type,
                        'name' => $f->name,
                        'added_at' => $added->isoFormat('LL'),
                        'used_at' => $f->last_used_at?->isoFormat('LL'),
                        'remove_url' => $this->guards->route($guard, 'factors.destroy', ['factor' => $f->id]),
                    ];
                })->all(),
                'totp' => $totpSetup === null ? null : [
                    'qr' => $totpQr,
                    'secret' => trim(chunk_split((string) $totpSetup->secret, 4, ' ')),
                ],
                'sms' => $smsSetup === null ? null : ['phone' => Person::maskPhone((string) $smsSetup->secret)],
                'email' => Person::maskEmail(Person::email($user)),
                'recoveryLeft' => trans_choice('atrium-2fa::two-factor.security.recovery_left', $recoveryLeft),
                'trustedDays' => $trustedDays,
                'devices' => $devices->map(fn (TrustedDevice $d): array => [
                    'id' => $d->id,
                    'name' => $d->name,
                    'ip' => $d->ip,
                    'until' => $d->expires_at->isoFormat('LL'),
                ])->all(),
                'events' => $this->eventRows($events, null),
                'urls' => collect(['totp.start', 'totp.confirm', 'passkeys.options', 'passkeys.store', 'sms.start', 'sms.confirm', 'email.enable', 'recovery.regenerate', 'devices.revoke'])
                    ->mapWithKeys(fn (string $name): array => [$name => $this->guards->route($guard, $name)])
                    ->all(),
            ]);
        }

        return response($this->view($request, 'security', [
            'user' => $user,
            'allowed' => $allowed,
            'required' => $required,
            'mustEnrol' => $mustEnrol,
            'factors' => $confirmed,
            'totpSetup' => $totpSetup,
            'totpQr' => $totpQr,
            'smsSetup' => $smsSetup,
            'recoveryLeft' => $recoveryLeft,
            'recoveryCodes' => $recoveryCodes,
            'devices' => $devices,
            'trustedDays' => $trustedDays,
            'events' => $events,
            'passwordless' => $passwordless,
        ]));
    }

    public function startTotp(Request $request): RedirectResponse
    {
        $guard = $this->guard($request);
        $user = $this->user($request);
        $this->allowOrFail($guard, 'totp');

        Factor::query()->of($guard, $user->getAuthIdentifier())->where('type', 'totp')->whereNull('confirmed_at')->delete();
        Factor::query()->create([
            'guard' => $guard,
            'user_id' => $user->getAuthIdentifier(),
            'type' => 'totp',
            'secret' => Totp::secret(),
        ]);

        return $this->back($guard, '#totp');
    }

    public function confirmTotp(Request $request): RedirectResponse
    {
        $guard = $this->guard($request);
        $user = $this->user($request);
        $request->validate(['code' => ['required', 'string', 'max:10'], 'name' => ['nullable', 'string', 'max:100']]);

        $factor = Factor::query()->of($guard, $user->getAuthIdentifier())->where('type', 'totp')->whereNull('confirmed_at')->latest('id')->first();
        $step = $factor !== null
            ? Totp::fromConfig()->verify((string) $factor->secret, (string) $request->input('code'), window: (int) config('atrium-2fa.totp.window', 1))
            : null;

        if ($factor === null || $step === null) {
            return $this->back($guard, '#totp')->withErrors(['totp' => __('atrium-2fa::two-factor.challenge.code_invalid')]);
        }

        $factor->forceFill([
            'name' => $this->name($request),
            'counter' => $step,
            'confirmed_at' => Carbon::now(),
        ])->save();

        return $this->added($request, $guard, $user, $factor);
    }

    public function passkeyOptions(Request $request): JsonResponse
    {
        $guard = $this->guard($request);
        $this->allowOrFail($guard, 'passkey');

        return new JsonResponse($this->passkeys->registrationOptions($request, $guard, $this->user($request)));
    }

    public function storePasskey(Request $request): Response
    {
        $guard = $this->guard($request);
        $user = $this->user($request);
        $this->allowOrFail($guard, 'passkey');

        $factor = $this->passkeys->register($request, $guard, $user, $this->name($request));

        if ($factor === null) {
            return new JsonResponse(['message' => __('atrium-2fa::two-factor.security.passkey_failed')], 422);
        }

        $response = $this->added($request, $guard, $user, $factor);

        return $request->expectsJson() ? new JsonResponse(['redirect' => $response->getTargetUrl()]) : $response;
    }

    public function startSms(Request $request, SmsFactor $sms): RedirectResponse
    {
        $guard = $this->guard($request);
        $user = $this->user($request);
        $this->allowOrFail($guard, 'sms');

        $data = $request->validate(
            ['phone' => ['required', 'string', 'regex:/^\+?[0-9 ()\-]{7,20}$/']],
            ['phone.regex' => __('atrium-2fa::two-factor.security.phone_invalid'), 'phone.required' => __('atrium-2fa::two-factor.security.phone_invalid')],
        );
        $phone = '+'.preg_replace('/\D/', '', (string) $data['phone']);

        Factor::query()->of($guard, $user->getAuthIdentifier())->where('type', 'sms')->whereNull('confirmed_at')->delete();
        Factor::query()->create(['guard' => $guard, 'user_id' => $user->getAuthIdentifier(), 'type' => 'sms', 'secret' => $phone]);

        if (! $sms->send($guard, Codes::SETUP, $phone, $user->getAuthIdentifier())) {
            return $this->back($guard, '#sms')->withErrors(['sms' => __('atrium-2fa::two-factor.challenge.resend_too_soon')]);
        }

        return $this->back($guard, '#sms');
    }

    public function confirmSms(Request $request): RedirectResponse
    {
        $guard = $this->guard($request);
        $user = $this->user($request);
        $request->validate(['code' => ['required', 'string', 'max:10']]);

        $factor = Factor::query()->of($guard, $user->getAuthIdentifier())->where('type', 'sms')->whereNull('confirmed_at')->latest('id')->first();

        if ($factor === null || $this->codes->verify($guard, Codes::SETUP, (string) $factor->secret, (string) $request->input('code')) !== Codes::OK) {
            return $this->back($guard, '#sms')->withErrors(['sms' => __('atrium-2fa::two-factor.challenge.code_invalid')]);
        }

        $factor->forceFill(['confirmed_at' => Carbon::now(), 'name' => Person::maskPhone((string) $factor->secret)])->save();

        return $this->added($request, $guard, $user, $factor);
    }

    public function enableEmail(Request $request): RedirectResponse
    {
        $guard = $this->guard($request);
        $user = $this->user($request);
        $this->allowOrFail($guard, 'email');

        $factor = Factor::query()->firstOrCreate(
            ['guard' => $guard, 'user_id' => $user->getAuthIdentifier(), 'type' => 'email'],
            ['confirmed_at' => Carbon::now(), 'name' => Person::maskEmail(Person::email($user))],
        );

        return $this->added($request, $guard, $user, $factor);
    }

    public function destroy(Request $request, int $factor): RedirectResponse
    {
        $guard = $this->guard($request);
        $user = $this->user($request);
        $model = Factor::query()->of($guard, $user->getAuthIdentifier())->findOrFail($factor);

        if ($model->confirmed_at !== null && $this->wouldLeaveNothing($guard, $user, $model)) {
            return $this->back($guard)->withErrors(['factors' => __('atrium-2fa::two-factor.security.last_factor')]);
        }

        $model->delete();

        if ($model->confirmed_at !== null) {
            $this->audit->record($guard, $user, Audit::FACTOR_REMOVED, $model->type, ['name' => $model->name], $user, $guard);

            if ($this->policy->enrolled($guard, $user) === []) {
                // Nothing left to recover: old codes would only be a way in.
                $this->recovery->forget($guard, $user->getAuthIdentifier());
            }
        }

        return $this->back($guard)->with('atrium-2fa.status', __('atrium-2fa::two-factor.security.removed'));
    }

    public function regenerateRecovery(Request $request): RedirectResponse
    {
        $guard = $this->guard($request);
        $user = $this->user($request);

        if ($this->policy->enrolled($guard, $user) === []) {
            return $this->back($guard);
        }

        $codes = $this->recovery->generate($guard, $user->getAuthIdentifier());
        $this->audit->record($guard, $user, Audit::RECOVERY_GENERATED, null, [], $user, $guard);

        return $this->back($guard, '#recovery')->with('atrium-2fa.recovery_codes', $codes);
    }

    public function revokeDevices(Request $request): RedirectResponse
    {
        $guard = $this->guard($request);
        $user = $this->user($request);

        $count = $this->devices->revokeAll($guard, $user->getAuthIdentifier());
        $this->audit->record($guard, $user, Audit::DEVICE_REVOKED, null, ['count' => $count], $user, $guard);

        return $this->back($guard, '#devices')->with('atrium-2fa.status', __('atrium-2fa::two-factor.security.devices_revoked'));
    }

    /**
     * A factor is confirmed: logged, and the first one comes with recovery
     * codes, shown once.
     */
    private function added(Request $request, string $guard, Authenticatable $user, Factor $factor): RedirectResponse
    {
        $this->audit->record($guard, $user, Audit::FACTOR_ADDED, $factor->type, ['name' => $factor->name], $user, $guard);
        $request->session()->forget(app(Challenge::class)->enrolKey($guard));

        $back = $this->back($guard)->with('atrium-2fa.status', __('atrium-2fa::two-factor.security.added'));

        if ($this->recovery->remaining($guard, $user->getAuthIdentifier()) === 0) {
            $codes = $this->recovery->generate($guard, $user->getAuthIdentifier());
            $this->audit->record($guard, $user, Audit::RECOVERY_GENERATED, null, [], $user, $guard);
            $back = $this->back($guard, '#recovery')->with('atrium-2fa.recovery_codes', $codes)
                ->with('atrium-2fa.status', __('atrium-2fa::two-factor.security.added'));
        }

        return $back;
    }

    /**
     * Removing it would leave a person who must pass a second step with no
     * way to pass it.
     */
    private function wouldLeaveNothing(string $guard, Authenticatable $user, Factor $removing): bool
    {
        if (! $this->policy->required($guard, $user)) {
            return false;
        }

        $others = Factor::query()->of($guard, $user->getAuthIdentifier())->confirmed()->whereKeyNot($removing->id)
            ->whereIn('type', $this->guards->factors($guard))->exists();

        return ! $others && ! $this->guards->allows($guard, 'email');
    }

    private function allowOrFail(string $guard, string $factor): void
    {
        abort_unless($this->guards->allows($guard, $factor), 404);
    }

    private function name(Request $request): ?string
    {
        $name = trim((string) $request->input('name', ''));

        return $name === '' ? null : mb_substr($name, 0, 100);
    }

    private function back(string $guard, string $anchor = ''): RedirectResponse
    {
        return redirect()->to($this->guards->route($guard, 'security').$anchor);
    }
}

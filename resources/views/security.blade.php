{{--
    This file is part of Atrium (https://github.com/AlveBy/atrium).
    Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
--}}
@extends($layout)

@section('title', __('atrium-2fa::two-factor.security.title'))

@section('a2f')
@php
    $has = fn (string $type): bool => $factors->contains('type', $type);
@endphp
<div class="a2f-card a2f-wide" dusk="a2f-security">
    <h1>{{ __('atrium-2fa::two-factor.security.title') }}</h1>
    <p class="a2f-muted">{{ __($required ? 'atrium-2fa::two-factor.security.required' : 'atrium-2fa::two-factor.security.optional') }}</p>

    @if ($mustEnrol)
        <div class="a2f-alert a2f-error" role="alert" data-error dusk="a2f-must-enrol">{{ __('atrium-2fa::two-factor.security.must_enrol') }}</div>
    @endif

    @include('atrium-2fa::partials.status', ['keys' => ['factors']])

    @if ($recoveryCodes)
        <section id="recovery-codes" class="a2f-alert a2f-ok" role="status" dusk="a2f-recovery-codes">
            <h2>{{ __('atrium-2fa::two-factor.security.recovery_new') }}</h2>
            <p>{{ __('atrium-2fa::two-factor.security.recovery_once') }}</p>
            <div class="a2f-codes">
                @foreach ($recoveryCodes as $code)
                    <span>{{ $code }}</span>
                @endforeach
            </div>
        </section>
    @endif

    {{-- What is set up --}}
    <section class="a2f-section" aria-labelledby="a2f-factors">
        <h2 id="a2f-factors">{{ __('atrium-2fa::two-factor.security.factors') }}</h2>
        @if ($factors->isEmpty())
            <p class="a2f-muted" dusk="a2f-no-factors">{{ __('atrium-2fa::two-factor.security.none') }}</p>
        @else
            <ul class="a2f-list" dusk="a2f-factor-list">
                @foreach ($factors as $factor)
                    <li>
                        <span>
                            <strong>{{ __('atrium-2fa::two-factor.factors.'.$factor->type) }}</strong>
                            @if ($factor->name) — {{ $factor->name }} @endif
                            <br><span class="a2f-muted a2f-small">
                                {{ __('atrium-2fa::two-factor.security.added_at', ['date' => $factor->confirmed_at?->isoFormat('LL')]) }}
                                @if ($factor->last_used_at) · {{ __('atrium-2fa::two-factor.security.used_at', ['date' => $factor->last_used_at->isoFormat('LL')]) }} @endif
                            </span>
                        </span>
                        <form method="POST" action="{{ $routes->url('factors.destroy', ['factor' => $factor->id]) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="a2f-btn" dusk="a2f-remove-{{ $factor->id }}">
                                {{ __('atrium-2fa::two-factor.security.remove') }}<span class="a2f-sr"> {{ __('atrium-2fa::two-factor.factors.'.$factor->type) }} {{ $factor->name }}</span>
                            </button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    {{-- Authenticator app --}}
    @if (in_array('totp', $allowed, true))
        <section id="totp" class="a2f-section" aria-labelledby="a2f-totp">
            <h2 id="a2f-totp">{{ __('atrium-2fa::two-factor.factors.totp') }}</h2>
            <p class="a2f-muted">{{ __('atrium-2fa::two-factor.security.totp_hint') }}</p>
            @error('totp')<p class="a2f-field-error" role="alert" data-error>{{ $message }}</p>@enderror
            @if ($totpSetup)
                <div class="a2f-row" style="align-items:flex-start">
                    <div class="a2f-qr" role="img" aria-label="{{ __('atrium-2fa::two-factor.security.qr') }}" dusk="a2f-qr">{!! $totpQr !!}</div>
                    <form method="POST" action="{{ $routes->url('totp.confirm') }}" class="a2f-stack" style="flex:1;min-width:14rem">
                        @csrf
                        <p class="a2f-small">{{ __('atrium-2fa::two-factor.security.totp_manual') }}<br><span class="a2f-secret" dusk="a2f-secret">{{ trim(chunk_split((string) $totpSetup->secret, 4, ' ')) }}</span></p>
                        <div>
                            <label for="a2f-totp-name">{{ __('atrium-2fa::two-factor.security.name') }}</label>
                            <input id="a2f-totp-name" type="text" name="name" maxlength="100" placeholder="{{ __('atrium-2fa::two-factor.security.name_totp') }}">
                        </div>
                        <div>
                            <label for="a2f-totp-code">{{ __('atrium-2fa::two-factor.challenge.code_label.totp') }}</label>
                            <input id="a2f-totp-code" class="a2f-code" type="text" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="10" required dusk="a2f-totp-code">
                        </div>
                        <button type="submit" class="a2f-btn a2f-primary" dusk="a2f-totp-confirm">{{ __('atrium-2fa::two-factor.security.confirm') }}</button>
                    </form>
                </div>
            @else
                <form method="POST" action="{{ $routes->url('totp.start') }}">
                    @csrf
                    <button type="submit" class="a2f-btn" dusk="a2f-totp-start">{{ __($has('totp') ? 'atrium-2fa::two-factor.security.totp_another' : 'atrium-2fa::two-factor.security.totp_add') }}</button>
                </form>
            @endif
        </section>
    @endif

    {{-- Passkeys --}}
    @if (in_array('passkey', $allowed, true))
        <section id="passkeys" class="a2f-section" aria-labelledby="a2f-passkeys">
            <h2 id="a2f-passkeys">{{ __('atrium-2fa::two-factor.factors.passkey') }}</h2>
            <p class="a2f-muted">{{ __($passwordless ? 'atrium-2fa::two-factor.security.passkey_hint_passwordless' : 'atrium-2fa::two-factor.security.passkey_hint') }}</p>
            <form method="POST" action="{{ $routes->url('passkeys.store') }}" class="a2f-row">
                @csrf
                <div style="flex:1;min-width:12rem">
                    <label for="a2f-passkey-name">{{ __('atrium-2fa::two-factor.security.name') }}</label>
                    <input id="a2f-passkey-name" type="text" name="name" maxlength="100" placeholder="{{ __('atrium-2fa::two-factor.security.name_passkey') }}">
                </div>
                <button type="button" class="a2f-btn" style="align-self:flex-end" data-a2f-passkey="create" data-options="{{ $routes->url('passkeys.options') }}" data-verify="{{ $routes->url('passkeys.store') }}" dusk="a2f-passkey-add">
                    {!! \Atrium\TwoFactor\Support\Icons::PASSKEY !!} {{ __('atrium-2fa::two-factor.security.passkey_add') }}
                </button>
                <p class="a2f-alert a2f-error" role="alert" data-a2f-message hidden style="flex-basis:100%"></p>
            </form>
            @include('atrium-2fa::partials.webauthn')
        </section>
    @endif

    {{-- SMS --}}
    @if (in_array('sms', $allowed, true))
        <section id="sms" class="a2f-section" aria-labelledby="a2f-sms">
            <h2 id="a2f-sms">{{ __('atrium-2fa::two-factor.factors.sms') }}</h2>
            @error('sms')<p class="a2f-field-error" role="alert" data-error>{{ $message }}</p>@enderror
            @error('phone')<p class="a2f-field-error" role="alert" data-error>{{ $message }}</p>@enderror
            @if ($smsSetup)
                <form method="POST" action="{{ $routes->url('sms.confirm') }}" class="a2f-row">
                    @csrf
                    <div style="flex:1;min-width:12rem">
                        <label for="a2f-sms-code">{{ __('atrium-2fa::two-factor.security.sms_sent', ['phone' => \Atrium\TwoFactor\Support\Person::maskPhone((string) $smsSetup->secret)]) }}</label>
                        <input id="a2f-sms-code" class="a2f-code" type="text" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="10" required dusk="a2f-sms-code">
                    </div>
                    <button type="submit" class="a2f-btn a2f-primary" style="align-self:flex-end" dusk="a2f-sms-confirm">{{ __('atrium-2fa::two-factor.security.confirm') }}</button>
                </form>
            @else
                <form method="POST" action="{{ $routes->url('sms.start') }}" class="a2f-row">
                    @csrf
                    <div style="flex:1;min-width:12rem">
                        <label for="a2f-phone">{{ __('atrium-2fa::two-factor.security.phone') }}</label>
                        <input id="a2f-phone" type="tel" name="phone" autocomplete="tel" placeholder="+1 555 0100" required dusk="a2f-phone">
                    </div>
                    <button type="submit" class="a2f-btn" style="align-self:flex-end" dusk="a2f-sms-start">{{ __('atrium-2fa::two-factor.security.sms_add') }}</button>
                </form>
            @endif
        </section>
    @endif

    {{-- E-mail --}}
    @if (in_array('email', $allowed, true) && ! $has('email'))
        <section id="email" class="a2f-section" aria-labelledby="a2f-email-h">
            <h2 id="a2f-email-h">{{ __('atrium-2fa::two-factor.factors.email') }}</h2>
            <p class="a2f-muted">{{ __('atrium-2fa::two-factor.security.email_hint', ['email' => \Atrium\TwoFactor\Support\Person::maskEmail(\Atrium\TwoFactor\Support\Person::email($user))]) }}</p>
            <form method="POST" action="{{ $routes->url('email.enable') }}">
                @csrf
                <button type="submit" class="a2f-btn" dusk="a2f-email-enable">{{ __('atrium-2fa::two-factor.security.email_add') }}</button>
            </form>
        </section>
    @endif

    {{-- Recovery codes --}}
    @if ($factors->isNotEmpty())
        <section id="recovery" class="a2f-section" aria-labelledby="a2f-recovery-h">
            <h2 id="a2f-recovery-h">{{ __('atrium-2fa::two-factor.security.recovery') }}</h2>
            <p class="a2f-muted" dusk="a2f-recovery-left">{{ trans_choice('atrium-2fa::two-factor.security.recovery_left', $recoveryLeft, ['count' => $recoveryLeft]) }}</p>
            <form method="POST" action="{{ $routes->url('recovery.regenerate') }}">
                @csrf
                <button type="submit" class="a2f-btn" dusk="a2f-recovery-regenerate">{{ __('atrium-2fa::two-factor.security.recovery_regenerate') }}</button>
            </form>
        </section>
    @endif

    {{-- Trusted devices --}}
    @if ($trustedDays > 0 || $devices->isNotEmpty())
        <section id="devices" class="a2f-section" aria-labelledby="a2f-devices-h">
            <h2 id="a2f-devices-h">{{ __('atrium-2fa::two-factor.security.devices') }}</h2>
            @if ($devices->isEmpty())
                <p class="a2f-muted">{{ __('atrium-2fa::two-factor.security.devices_none') }}</p>
            @else
                <ul class="a2f-list">
                    @foreach ($devices as $device)
                        <li><span>{{ $device->name ?: '—' }}<br><span class="a2f-muted a2f-small">{{ $device->ip }} · {{ __('atrium-2fa::two-factor.security.until', ['date' => $device->expires_at->isoFormat('LL')]) }}</span></span></li>
                    @endforeach
                </ul>
                <form method="POST" action="{{ $routes->url('devices.revoke') }}" style="margin-top:1rem">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="a2f-btn" dusk="a2f-devices-revoke">{{ __('atrium-2fa::two-factor.security.devices_revoke') }}</button>
                </form>
            @endif
        </section>
    @endif

    {{-- Recent sign-ins --}}
    <section class="a2f-section" aria-labelledby="a2f-events-h">
        <h2 id="a2f-events-h">{{ __('atrium-2fa::two-factor.security.events') }}</h2>
        @include('atrium-2fa::partials.events', ['events' => $events, 'people' => null])
    </section>
</div>
@endsection

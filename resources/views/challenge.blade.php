{{--
    This file is part of Atrium (https://github.com/AlveBy/atrium).
    Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
--}}
@extends($layout)

@section('title', __('atrium-2fa::two-factor.challenge.title'))

@section('a2f')
<div class="a2f-card" dusk="a2f-challenge">
    <h1>{{ __('atrium-2fa::two-factor.challenge.title') }}</h1>

    @include('atrium-2fa::partials.status', ['keys' => ['code', 'email']])

    @if ($recovery || $factor === null)
        <form method="POST" action="{{ $routes->url('challenge.verify') }}" class="a2f-stack" dusk="a2f-recovery-form">
            @csrf
            <p class="a2f-muted">{{ __('atrium-2fa::two-factor.challenge.recovery_hint') }}</p>
            <div>
                <label for="a2f-recovery">{{ __('atrium-2fa::two-factor.challenge.recovery_code') }}</label>
                <input id="a2f-recovery" type="text" name="recovery_code" autocomplete="one-time-code" autocapitalize="off" spellcheck="false" required autofocus dusk="a2f-recovery-code">
            </div>
            <button type="submit" class="a2f-btn a2f-primary a2f-block" dusk="a2f-submit">{{ __('atrium-2fa::two-factor.challenge.submit') }}</button>
            @if ($factor !== null)
                <p class="a2f-small"><a class="a2f-link" href="{{ $routes->url('challenge') }}">{{ __('atrium-2fa::two-factor.challenge.back_to_factor') }}</a></p>
            @endif
        </form>
    @elseif ($factor->input() === 'webauthn')
        <form method="POST" action="{{ $routes->url('challenge.verify') }}" class="a2f-stack">
            @csrf
            <p class="a2f-muted">{{ __('atrium-2fa::two-factor.challenge.passkey_hint') }}</p>
            @include('atrium-2fa::partials.trust')
            <p class="a2f-alert a2f-error" role="alert" data-a2f-message hidden></p>
            <button type="button" class="a2f-btn a2f-primary a2f-block" data-a2f-passkey="get" data-options="{{ $routes->url('challenge.options') }}" data-verify="{{ $routes->url('challenge.verify') }}" dusk="a2f-passkey">
                {!! \Atrium\TwoFactor\Support\Icons::PASSKEY !!} {{ __('atrium-2fa::two-factor.challenge.use_passkey') }}
            </button>
        </form>
        @include('atrium-2fa::partials.webauthn')
    @else
        <form method="POST" action="{{ $routes->url('challenge.verify') }}" class="a2f-stack" dusk="a2f-code-form">
            @csrf
            <p class="a2f-muted">
                @if ($destination)
                    {{ __('atrium-2fa::two-factor.challenge.sent_to', ['destination' => $destination]) }}
                @else
                    {{ __('atrium-2fa::two-factor.challenge.totp_hint') }}
                @endif
            </p>
            <div>
                <label for="a2f-code">{{ __('atrium-2fa::two-factor.challenge.code_label.'.$factor->key()) }}</label>
                <input id="a2f-code" class="a2f-code" type="text" name="code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9 ]*" maxlength="10" required autofocus dusk="a2f-code">
            </div>
            @include('atrium-2fa::partials.trust')
            <button type="submit" class="a2f-btn a2f-primary a2f-block" dusk="a2f-submit">{{ __('atrium-2fa::two-factor.challenge.submit') }}</button>
        </form>
        @if (in_array($factor->key(), ['email', 'sms'], true))
            <form method="POST" action="{{ $routes->url('challenge.resend') }}" style="margin-top:1rem">
                @csrf
                <button type="submit" class="a2f-link a2f-small" @disabled($resendIn > 0) dusk="a2f-resend">
                    {{ $resendIn > 0 ? __('atrium-2fa::two-factor.challenge.resend_in', ['seconds' => $resendIn]) : __('atrium-2fa::two-factor.challenge.resend') }}
                </button>
            </form>
        @endif
    @endif

    @if (count($factors) > 1 || ($hasRecovery && ! $recovery))
        <div class="a2f-section">
            <h2>{{ __('atrium-2fa::two-factor.challenge.other_ways') }}</h2>
            <div class="a2f-tabs">
                @foreach ($factors as $other)
                    @if ($factor === null || $other->key() !== $factor->key() || $recovery)
                        <form method="POST" action="{{ $routes->url('challenge.switch') }}">
                            @csrf
                            <input type="hidden" name="factor" value="{{ $other->key() }}">
                            <button type="submit" class="a2f-btn" dusk="a2f-switch-{{ $other->key() }}">{{ $other->label() }}</button>
                        </form>
                    @endif
                @endforeach
                @if ($hasRecovery && ! $recovery)
                    <a class="a2f-btn" href="{{ $routes->url('challenge', ['recovery' => 1]) }}" dusk="a2f-use-recovery">{{ __('atrium-2fa::two-factor.challenge.use_recovery') }}</a>
                @endif
            </div>
        </div>
    @endif

    <form method="POST" action="{{ $routes->url('challenge.cancel') }}" class="a2f-section">
        @csrf
        <button type="submit" class="a2f-link a2f-small" dusk="a2f-cancel">{{ __('atrium-2fa::two-factor.challenge.cancel') }}</button>
    </form>
</div>
@endsection

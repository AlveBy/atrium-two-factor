{{--
    This file is part of Atrium (https://github.com/AlveBy/atrium).
    Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
--}}
@extends($layout)

@section('title', __('atrium-2fa::two-factor.passkey.title'))

@section('a2f')
<div class="a2f-card" dusk="a2f-passkey-login">
    <h1>{{ __('atrium-2fa::two-factor.passkey.title') }}</h1>
    <p class="a2f-muted">{{ __('atrium-2fa::two-factor.passkey.hint') }}</p>

    <form method="POST" action="{{ $routes->url('passkey.verify') }}" class="a2f-stack">
        @csrf
        <p class="a2f-alert a2f-error" role="alert" data-a2f-message hidden></p>
        <button type="button" class="a2f-btn a2f-primary a2f-block" data-a2f-passkey="get" data-options="{{ $routes->url('passkey.options') }}" data-verify="{{ $routes->url('passkey.verify') }}" dusk="a2f-passkey">
            {!! \Atrium\TwoFactor\Support\Icons::PASSKEY !!} {{ __('atrium-2fa::two-factor.passkey.button') }}
        </button>
    </form>
    @include('atrium-2fa::partials.webauthn')

    <p class="a2f-section a2f-small"><a class="a2f-link" href="{{ $routes->login() }}">{{ __('atrium-2fa::two-factor.back_to_login') }}</a></p>
</div>
@endsection

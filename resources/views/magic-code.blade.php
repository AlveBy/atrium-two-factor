{{--
    This file is part of Atrium (https://github.com/AlveBy/atrium).
    Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
--}}
@extends($layout)

@section('title', __('atrium-2fa::two-factor.magic.title'))

@section('a2f')
<div class="a2f-card" dusk="a2f-magic-code">
    <h1>{{ __('atrium-2fa::two-factor.magic.title') }}</h1>

    @include('atrium-2fa::partials.status', ['keys' => ['code']])

    {{-- The same words whether the address is known: nobody learns who has an account. --}}
    <p class="a2f-muted">{{ __('atrium-2fa::two-factor.magic.check_mail', ['email' => $email]) }}</p>

    <form method="POST" action="{{ $routes->url('magic.verify') }}" class="a2f-stack">
        @csrf
        <div>
            <label for="a2f-code">{{ __('atrium-2fa::two-factor.challenge.code_label.email') }}</label>
            <input id="a2f-code" class="a2f-code" type="text" name="code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9 ]*" maxlength="10" required autofocus dusk="a2f-code">
        </div>
        <button type="submit" class="a2f-btn a2f-primary a2f-block" dusk="a2f-submit">{{ __('atrium-2fa::two-factor.challenge.submit') }}</button>
    </form>

    <p class="a2f-section a2f-small"><a class="a2f-link" href="{{ $routes->url('magic') }}" dusk="a2f-other-email">{{ __('atrium-2fa::two-factor.magic.other_email') }}</a></p>
</div>
@endsection

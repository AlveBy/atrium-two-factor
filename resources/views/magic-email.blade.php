{{--
    This file is part of Atrium (https://github.com/AlveBy/atrium).
    Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
--}}
@extends($layout)

@section('title', __('atrium-2fa::two-factor.magic.title'))

@section('a2f')
<div class="a2f-card" dusk="a2f-magic">
    <h1>{{ __('atrium-2fa::two-factor.magic.title') }}</h1>
    <p class="a2f-muted">{{ __('atrium-2fa::two-factor.magic.hint') }}</p>

    @include('atrium-2fa::partials.status', ['keys' => ['email']])

    <form method="POST" action="{{ $routes->url('magic.send') }}" class="a2f-stack">
        @csrf
        <div>
            <label for="a2f-email">{{ __('atrium-2fa::two-factor.magic.email') }}</label>
            <input id="a2f-email" type="email" name="email" value="{{ old('email') }}" autocomplete="username" required autofocus dusk="a2f-email">
        </div>
        <button type="submit" class="a2f-btn a2f-primary a2f-block" dusk="a2f-submit">{{ __('atrium-2fa::two-factor.magic.send') }}</button>
    </form>

    <p class="a2f-section a2f-small"><a class="a2f-link" href="{{ $routes->login() }}">{{ __('atrium-2fa::two-factor.back_to_login') }}</a></p>
</div>
@endsection

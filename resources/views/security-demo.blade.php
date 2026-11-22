{{--
    This file is part of Atrium (https://github.com/AlveBy/atrium).
    Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
--}}
@extends($layout)

@section('title', __('atrium-2fa::two-factor.security.title'))

@section('a2f')
<div class="a2f-card a2f-wide" dusk="a2f-security">
    <h1>{{ __('atrium-2fa::two-factor.security.title') }}</h1>
    <p class="a2f-alert" role="status" dusk="a2f-demo-locked">{{ __('atrium-2fa::two-factor.security.demo_locked') }}</p>
</div>
@endsection

{{--
    This file is part of Atrium (https://github.com/AlveBy/atrium).
    Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
--}}
@extends($layout)

@section('title', __('atrium-2fa::two-factor.menu.log'))

@section('a2f')
<div class="a2f-card a2f-wide" dusk="a2f-log">
    <h1>{{ __('atrium-2fa::two-factor.menu.log') }}</h1>

    <form method="GET" class="a2f-row" style="margin-bottom:1rem" role="search">
        <div>
            <label for="a2f-guard">{{ __('atrium-2fa::two-factor.log.guard') }}</label>
            <select id="a2f-guard" name="guard">
                <option value="">{{ __('atrium-2fa::two-factor.log.all') }}</option>
                @foreach ($guards as $name)
                    <option value="{{ $name }}" @selected(($filters['guard'] ?? null) === $name)>{{ __('atrium-2fa::two-factor.guards.'.$name) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="a2f-event">{{ __('atrium-2fa::two-factor.log.what') }}</label>
            <select id="a2f-event" name="event" dusk="a2f-event-filter">
                <option value="">{{ __('atrium-2fa::two-factor.log.all') }}</option>
                @foreach ($eventNames as $name)
                    <option value="{{ $name }}" @selected(($filters['event'] ?? null) === $name)>{{ __('atrium-2fa::two-factor.events.'.$name) }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="a2f-btn" style="align-self:flex-end">{{ __('atrium-2fa::two-factor.log.filter') }}</button>
    </form>

    @include('atrium-2fa::partials.events', ['events' => $events, 'people' => $people])

    <nav class="a2f-pager" aria-label="{{ __('atrium-2fa::two-factor.log.pages') }}">
        @if ($events->previousPageUrl())<a class="a2f-link" href="{{ $events->previousPageUrl() }}">← {{ __('atrium-2fa::two-factor.log.newer') }}</a>@endif
        @if ($events->nextPageUrl())<a class="a2f-link" href="{{ $events->nextPageUrl() }}">{{ __('atrium-2fa::two-factor.log.older') }} →</a>@endif
    </nav>
</div>
@endsection

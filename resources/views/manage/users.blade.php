{{--
    This file is part of Atrium (https://github.com/AlveBy/atrium).
    Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
--}}
@extends($layout)

@section('title', __('atrium-2fa::two-factor.menu.people'))

@section('a2f')
<div class="a2f-card a2f-wide" dusk="a2f-people">
    <h1>{{ __('atrium-2fa::two-factor.menu.people') }}</h1>
    <p class="a2f-muted">{{ __('atrium-2fa::two-factor.manage.hint') }}</p>

    @include('atrium-2fa::partials.status')

    <form method="GET" class="a2f-row" style="margin-bottom:1rem" role="search">
        @if (count($guards) > 1)
            <div>
                <label for="a2f-guard">{{ __('atrium-2fa::two-factor.log.guard') }}</label>
                <select id="a2f-guard" name="guard">
                    @foreach ($guards as $name)
                        <option value="{{ $name }}" @selected($selected === $name)>{{ __('atrium-2fa::two-factor.guards.'.$name) }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div style="flex:1;min-width:12rem">
            <label for="a2f-q">{{ __('atrium-2fa::two-factor.manage.search') }}</label>
            <input id="a2f-q" type="search" name="q" value="{{ $search }}" dusk="a2f-search">
        </div>
        <button type="submit" class="a2f-btn" style="align-self:flex-end">{{ __('atrium-2fa::two-factor.log.filter') }}</button>
    </form>

    <div style="overflow-x:auto">
        <table class="a2f-table">
            <thead>
                <tr>
                    <th scope="col">{{ __('atrium-2fa::two-factor.manage.email') }}</th>
                    <th scope="col">{{ __('atrium-2fa::two-factor.security.factors') }}</th>
                    <th scope="col"><span class="a2f-sr">{{ __('atrium-2fa::two-factor.manage.reset') }}</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($people as $person)
                    @php $own = $factors->get($person->getKey(), collect()); @endphp
                    <tr dusk="a2f-person-{{ $person->getKey() }}">
                        <td>{{ $person->getAttribute('email') }}</td>
                        <td>
                            @forelse ($own as $factor)
                                {{ __('atrium-2fa::two-factor.factors.'.$factor->type) }}@if (! $loop->last), @endif
                            @empty
                                <span class="a2f-muted">{{ __('atrium-2fa::two-factor.security.none_short') }}</span>
                            @endforelse
                        </td>
                        <td>
                            @if ($own->isNotEmpty())
                                <form method="POST" action="{{ route('admin.two-factor.reset') }}" data-a2f-confirm="{{ __('atrium-2fa::two-factor.manage.confirm', ['email' => $person->getAttribute('email')]) }}">
                                    @csrf
                                    <input type="hidden" name="guard" value="{{ $selected }}">
                                    <input type="hidden" name="user" value="{{ $person->getKey() }}">
                                    <button type="submit" class="a2f-btn" dusk="a2f-reset-{{ $person->getKey() }}">{{ __('atrium-2fa::two-factor.manage.reset') }}<span class="a2f-sr"> {{ $person->getAttribute('email') }}</span></button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <nav class="a2f-pager" aria-label="{{ __('atrium-2fa::two-factor.log.pages') }}">
        @if ($people->previousPageUrl())<a class="a2f-link" href="{{ $people->previousPageUrl() }}">← {{ __('atrium-2fa::two-factor.log.newer') }}</a>@endif
        @if ($people->nextPageUrl())<a class="a2f-link" href="{{ $people->nextPageUrl() }}">{{ __('atrium-2fa::two-factor.log.older') }} →</a>@endif
    </nav>
</div>
<script @if ($nonce = \Illuminate\Support\Facades\Vite::cspNonce()) nonce="{{ $nonce }}" @endif>
document.querySelectorAll('form[data-a2f-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (event) {
        if (!window.confirm(form.getAttribute('data-a2f-confirm'))) event.preventDefault();
    });
});
</script>
@endsection

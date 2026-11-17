{{--
    This file is part of Atrium (https://github.com/AlveBy/atrium).
    Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
--}}
@if ($events->isEmpty())
    <p class="a2f-muted">{{ __('atrium-2fa::two-factor.log.empty') }}</p>
@else
    <div style="overflow-x:auto">
        <table class="a2f-table" dusk="a2f-events">
            <thead>
                <tr>
                    <th scope="col">{{ __('atrium-2fa::two-factor.log.when') }}</th>
                    @if ($people !== null)
                        <th scope="col">{{ __('atrium-2fa::two-factor.log.who') }}</th>
                    @endif
                    <th scope="col">{{ __('atrium-2fa::two-factor.log.what') }}</th>
                    <th scope="col">{{ __('atrium-2fa::two-factor.log.how') }}</th>
                    <th scope="col">{{ __('atrium-2fa::two-factor.log.where') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($events as $event)
                    <tr>
                        <td><time datetime="{{ $event->created_at?->toIso8601String() }}">{{ $event->created_at?->isoFormat('lll') }}</time></td>
                        @if ($people !== null)
                            <td>{{ __('atrium-2fa::two-factor.guards.'.$event->guard) }}: {{ $people[$event->guard.':'.$event->user_id] ?? ($event->user_id ? '#'.$event->user_id : '—') }}</td>
                        @endif
                        <td>{{ __('atrium-2fa::two-factor.events.'.$event->event) }}@if ($event->actor_id) <span class="a2f-muted">({{ __('atrium-2fa::two-factor.log.by_admin', ['id' => $event->actor_id]) }})</span>@endif</td>
                        <td>{{ $event->method ? \Atrium\TwoFactor\Support\Methods::label($event->method) : '—' }}</td>
                        <td class="a2f-muted">{{ $event->ip }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

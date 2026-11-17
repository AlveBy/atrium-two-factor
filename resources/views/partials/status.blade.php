{{--
    This file is part of Atrium (https://github.com/AlveBy/atrium).
    Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
--}}
@if (session('atrium-2fa.status'))
    <div class="a2f-alert a2f-ok" role="status" dusk="a2f-status">{{ session('atrium-2fa.status') }}</div>
@endif
@if (session('atrium-2fa.error'))
    <div class="a2f-alert a2f-error" role="alert" data-error dusk="a2f-error">{{ session('atrium-2fa.error') }}</div>
@endif
@foreach (($keys ?? []) as $key)
    @error($key)
        <div class="a2f-alert a2f-error" role="alert" data-error dusk="a2f-error-{{ $key }}">{{ $message }}</div>
    @enderror
@endforeach

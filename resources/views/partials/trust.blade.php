{{--
    This file is part of Atrium (https://github.com/AlveBy/atrium).
    Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
--}}
@if ($trustedDays > 0)
    <label class="a2f-check">
        <input type="checkbox" name="trust" value="1" dusk="a2f-trust">
        {{ trans_choice('atrium-2fa::two-factor.challenge.trust', $trustedDays, ['days' => $trustedDays]) }}
    </label>
@endif

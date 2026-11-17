{{--
    This file is part of Atrium (https://github.com/AlveBy/atrium).
    Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
--}}
{{-- Dark text on white, 15.9:1; the code itself 16.1:1. No colour carries meaning. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('atrium-2fa::two-factor.mail.title.'.$purpose) }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f5;font-family:Arial,Helvetica,sans-serif;color:#1f2328;">
    <div style="max-width:600px;margin:0 auto;padding:20px;">
        <div style="background:#ffffff;padding:24px;border:1px solid #d0d7de;border-radius:6px;">
            <h1 style="margin:0 0 4px;font-size:18px;">{{ __('atrium-2fa::two-factor.mail.heading.'.$purpose, ['site' => $siteName]) }}</h1>
            <div style="margin:0 0 20px;font-size:14px;color:#4b5563;">{{ config('app.url') }}</div>
            <p style="margin:0 0 8px;font-size:14px;">{{ __('atrium-2fa::two-factor.mail.code_label') }}</p>
            <div style="font-size:34px;font-weight:bold;letter-spacing:8px;color:#111827;padding:8px 0;">{{ $code }}</div>
            <p style="margin:16px 0 0;font-size:14px;color:#4b5563;">{{ trans_choice('atrium-2fa::two-factor.mail.valid_for', $ttlMinutes, ['count' => $ttlMinutes]) }}</p>
            <p style="margin:18px 0 0;font-size:13px;color:#4b5563;">{{ __('atrium-2fa::two-factor.mail.not_you') }}</p>
        </div>
    </div>
</body>
</html>

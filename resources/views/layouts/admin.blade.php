{{--
    This file is part of Atrium (https://github.com/AlveBy/atrium).
    Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
--}}
{{-- A page of the panel outside its Vue bundle: the panel's stylesheet and colour theme, nothing else. --}}
@php
    $themes = app(\Atrium\Admin\Support\Themes::class);
    $admin = auth()->guard(config('atrium.auth.guard', 'admin'))->user();
    $themeChoice = $themes->choiceFor($admin instanceof \Atrium\Admin\Models\AdminUser ? $admin : null);
    $manifest = public_path(\Atrium\Admin\Support\Assets::BUILD_DIRECTORY.'/manifest.json');
    $css = is_file($manifest) ? (array) (json_decode((string) file_get_contents($manifest), true)['resources/js/app.js']['css'] ?? []) : [];
    $cspNonce = \Illuminate\Support\Facades\Vite::cspNonce();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ $themes->render($themeChoice) }}" data-theme-choice="{{ $themeChoice }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <script @if ($cspNonce) nonce="{{ $cspNonce }}" @endif>
        (function () {
            var root = document.documentElement;
            if (root.dataset.themeChoice === 'system' && window.matchMedia) {
                root.dataset.theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'night' : 'graphite';
            }
        })();
    </script>
    <title>@yield('title') — {{ config('atrium.branding.name', 'Atrium') }}</title>
    @foreach ($css as $file)
        <link rel="stylesheet" href="{{ asset(\Atrium\Admin\Support\Assets::BUILD_DIRECTORY.'/'.$file) }}">
    @endforeach
    @include('atrium-2fa::partials.styles')
</head>
<body class="a2f" style="margin:0;background:var(--a2f-bg)">
    @if ($admin)
        <nav class="a2f-row" style="padding:.75rem 1.5rem;border-bottom:1px solid var(--a2f-line);background:var(--a2f-card)" aria-label="{{ __('atrium-2fa::two-factor.menu.title') }}">
            <a class="a2f-link" href="{{ route(config('atrium.auth.redirect_after_login', 'admin.dashboard')) }}" dusk="back-to-panel">← {{ __('atrium-2fa::two-factor.back_to_panel') }}</a>
            <a class="a2f-link" href="{{ route('admin.two-factor.security') }}">{{ __('atrium-2fa::two-factor.menu.security') }}</a>
            @if (app(\Atrium\Admin\Auth\Permissions::class)->canSetUp($admin, 'two_factor'))
                <a class="a2f-link" href="{{ route('admin.two-factor.log') }}">{{ __('atrium-2fa::two-factor.menu.log') }}</a>
                <a class="a2f-link" href="{{ route('admin.two-factor.users') }}">{{ __('atrium-2fa::two-factor.menu.people') }}</a>
            @endif
        </nav>
        <main class="a2f-site">
            @yield('a2f')
        </main>
    @else
        <main class="a2f-page">
            @yield('a2f')
        </main>
    @endif
</body>
</html>

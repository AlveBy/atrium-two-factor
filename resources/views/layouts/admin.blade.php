{{--
    This file is part of Atrium (https://github.com/AlveBy/atrium).
    Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
--}}
{{-- A page of the panel before sign-in, outside its Vue bundle: the panel's stylesheet and colour theme, nothing else. Signed-in pages are Inertia pages in the panel's own layout. --}}
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
    <main class="a2f-page">
        @yield('a2f')
    </main>
</body>
</html>

<!DOCTYPE html>
{{-- Dark only: the "dark" class stays on so the shadcn components' dark: styles always apply --}}
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        @php($setting = \App\Models\Setting::current())
        <meta name="application-name" content="{{ $setting->app_name }}">

        @isset($og)
            <meta property="og:type" content="website">
            <meta property="og:title" content="{{ $og['title'] }}">
            <meta property="og:description" content="{{ $og['description'] }}">
            <meta property="og:url" content="{{ $og['url'] }}">
            <meta property="og:site_name" content="{{ $og['site_name'] }}">
            <meta property="og:image" content="{{ $og['image'] }}">
            <meta name="twitter:card" content="summary_large_image">
        @endisset

        @if ($setting->faviconUrl())
            <link rel="icon" href="{{ $setting->faviconUrl() }}">
            <link rel="apple-touch-icon" href="{{ $setting->faviconUrl() }}">
        @else
            <link rel="icon" href="/favicon.ico" sizes="any">
            <link rel="icon" href="/favicon.png" type="image/png">
            <link rel="apple-touch-icon" href="/apple-touch-icon.png">
        @endif

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])

        {{-- The active theme preset; after app.css so it overrides the @theme tokens --}}
        <style>
            :root {
                @foreach ($setting->colors() as $token => $value)
                    --color-{{ $token }}: {{ $value }};
                @endforeach
            }
            html {
                background-color: {{ $setting->colors()['bg'] }};
            }
        </style>
        <x-inertia::head>
            <title>{{ $setting->app_name }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>

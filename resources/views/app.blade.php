<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Finisher Legacy transforma cada logro deportivo en una historia que puedes conservar, revivir y compartir.">

        <meta name="application-name" content="Finisher Legacy">
        <meta name="apple-mobile-web-app-title" content="Finisher Legacy">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="theme-color" content="#09090B">
        <meta name="msapplication-TileColor" content="#09090B">

        {{-- Open Graph --}}
        <meta property="og:site_name" content="Finisher Legacy">
        <meta property="og:type" content="website">
        <meta property="og:title" content="Finisher Legacy">
        <meta property="og:description" content="Tu meta termina. Tu historia no. Conserva, revive y comparte cada logro deportivo.">
        <meta property="og:image" content="{{ asset('images/brand/og-finisher-legacy.jpg') }}">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta property="og:image:type" content="image/jpeg">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="Finisher Legacy">
        <meta name="twitter:description" content="Tu meta termina. Tu historia no. Conserva, revive y comparte cada logro deportivo.">
        <meta name="twitter:image" content="{{ asset('images/brand/og-finisher-legacy.jpg') }}">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }
        </style>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="icon" href="/favicon-32x32.png" sizes="32x32" type="image/png">
        <link rel="icon" href="/favicon-16x16.png" sizes="16x16" type="image/png">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">
        <link rel="manifest" href="/site.webmanifest">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ config('app.name', 'Finisher Legacy') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>

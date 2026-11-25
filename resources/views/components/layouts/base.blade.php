<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? 'system') === 'dark'])>
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="csrf-token" content="{{ csrf_token() }}" />

        <script>
            (function () {
                const serverAppearance = @js($appearance ?? 'system');
                const stored = localStorage.getItem('appearance');
                const appearance = stored || serverAppearance || 'system';
                const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                if (appearance === 'dark' || (appearance === 'system' && prefersDark)) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            })();
        </script>

        <style>
            html {
                background-color: #faf7f2;
            }
            html.dark {
                background-color: #150e0a;
            }
        </style>

        <title>{{ isset($title) && $title !== '' ? $title.' - '.config('app.name', 'Our Coffee') : config('app.name', 'Our Coffee') }}</title>

        <link rel="icon" href="/favicon.ico" sizes="any" />
        <link rel="icon" href="/favicon.svg" type="image/svg+xml" />
        <link rel="apple-touch-icon" href="/apple-touch-icon.png" />

        <link rel="preconnect" href="https://fonts.bunny.net" />
        <link
            href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800|jetbrains-mono:500,600,700"
            rel="stylesheet"
        />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('head')
    </head>
    <body class="font-sans antialiased bg-background text-foreground selection:bg-crema/20 selection:text-foreground">
        {{ $slot }}

        @php
            $flashToasts = [];
            if (session('success')) {
                $flashToasts[] = ['type' => 'success', 'message' => session('success')];
            }
            if (session('error')) {
                $flashToasts[] = ['type' => 'error', 'message' => session('error')];
            }
            if (session('status')) {
                $flashToasts[] = ['type' => 'info', 'message' => session('status')];
            }
        @endphp
        <script type="application/json" id="flash-toasts-data">@json($flashToasts)</script>
        <x-toaster />
        @stack('scripts')
    </body>
</html>

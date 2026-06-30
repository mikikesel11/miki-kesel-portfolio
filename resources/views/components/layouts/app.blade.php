@props(['title' => null, 'description' => null, 'image' => null])

@php
    $pageTitle = $title ?? config('app.name');
    $pageDescription = $description ?? '';
    $ogImage = $image ?? asset('og-image.png');
    $canonical = url()->current();
@endphp
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    <link rel="canonical" href="{{ $canonical }}">

    {{-- Favicons (generated from resources/og/favicon.svg via `npm run favicon:build`) --}}
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" href="{{ asset('favicon-32.png') }}" type="image/png" sizes="32x32">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

    {{-- Open Graph (LinkedIn, Slack, iMessage, Facebook, …) --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">

    {{-- Twitter / X --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $pageDescription }}">
    <meta name="twitter:image" content="{{ $ogImage }}">

    {{-- Apply the saved theme before paint to avoid a flash of the wrong mode. --}}
    <script>
        (function () {
            const saved = localStorage.getItem('theme');
            const dark = saved ? saved === 'dark'
                : window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.classList.toggle('dark', dark);
        })();
    </script>

    @if (config('services.brevo.client_key'))
        {{-- Brevo JS event tracker --}}
        <script src="https://cdn.brevo.com/js/sdk-loader.js" async></script>
        <script>
            // Version: 2.0
            window.Brevo = window.Brevo || [];
            Brevo.push([
                "init",
                {
                client_key: "{{ config('services.brevo.client_key') }}",
                // Optional: Add other initialization options, see documentation
                }
            ]);
        </script>
    @endif

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-zinc-50 text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
    {{ $slot }}
</body>
</html>

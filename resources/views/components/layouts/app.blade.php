<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>
    <meta name="description" content="{{ $description ?? '' }}">

    {{-- Apply the saved theme before paint to avoid a flash of the wrong mode. --}}
    <script>
        (function () {
            const saved = localStorage.getItem('theme');
            const dark = saved ? saved === 'dark'
                : window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.classList.toggle('dark', dark);
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
    {{ $slot }}
</body>
</html>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark" data-theme="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Page Title' }}</title>

    </script>
    {{-- ✅ Always include styles first --}}
    @livewireStyles

    {{-- ✅ Then Vite CSS and JS --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/@marcreichel/alpine-auto-animate@latest/dist/alpine-auto-animate.min.js"
        defer></script>
    {{-- ❌ Do NOT load @livewireScripts in <head> --}}
</head>

<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100 dark:bg-zinc-950">
        <main>
            {{ $slot }}
        </main>
    </div>

    {{-- ✅ Load Livewire scripts at the end of <body> --}}
    @livewireScriptConfig

    {{-- ✅ AlpineJS must be loaded after Livewire --}}
    {{-- <script src="https://unpkg.com/alpinejs" defer></script> --}}

</body>

</html>

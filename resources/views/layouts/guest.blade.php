<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'MikPOS - Sistema POS Multisucursal' }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700,800" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="antialiased">
    <div class="min-h-screen bg-gradient-to-br from-[#1a1225] via-[#2d1f3d] to-[#1a1225] relative">
        <!-- Decorative gradient orbs -->
        <div class="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none">
            <div class="absolute top-[-20%] right-[-10%] w-[500px] h-[500px] rounded-full bg-[#ff7261]/20 blur-[120px]"></div>
            <div class="absolute bottom-[-20%] left-[-10%] w-[500px] h-[500px] rounded-full bg-[#a855f7]/20 blur-[120px]"></div>
        </div>
        {{ $slot }}
    </div>

    @livewireScriptConfig
</body>
</html>

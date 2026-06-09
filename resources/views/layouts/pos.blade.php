<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>POS - MikPOS</title>
    @php
        $currentBranch = auth()->user()?->branch;
        $branchLogo = $currentBranch?->logo;
        $branchLogoUrl = $branchLogo ? Storage::url($branchLogo) : null;
        $faviconVersion = $currentBranch?->updated_at?->timestamp ?? 0;
        $faviconUrl = $branchLogoUrl ? ($branchLogoUrl . '?v=' . $faviconVersion) : asset('favicon.ico');
        $touchIconUrl = (is_string($branchLogo) && preg_match('/\.(png|jpe?g|webp)$/i', $branchLogo))
            ? ($branchLogoUrl . '?v=' . $faviconVersion)
            : asset('favicon.ico');
    @endphp
    <link rel="icon" href="{{ $faviconUrl }}">
    <link rel="apple-touch-icon" href="{{ $touchIconUrl }}">
    <link rel="manifest" href="{{ route('manifest', ['context' => 'app', 'v' => $faviconVersion]) }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700,800" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>
        /* Hide scrollbar but allow scrolling */
        .scrollbar-hide::-webkit-scrollbar {
            display: none;
        }
        .scrollbar-hide {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        /* Custom scrollbar for cart */
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
</head>

<body class="antialiased bg-slate-100 font-sans overflow-hidden">
    <div class="h-screen flex flex-col">
        {{ $slot }}
    </div>

    <!-- Toast Notifications -->
    <x-toast />
    
    @livewireScriptConfig
</body>

</html>

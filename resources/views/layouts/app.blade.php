<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Dashboard - MikPOS' }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700,800" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="antialiased bg-slate-50 font-sans">
    <div class="min-h-screen flex flex-col">
        <!-- Top Navigation -->
        <nav class="bg-white/80 backdrop-blur-md border-b border-slate-200 sticky top-0 z-40">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <!-- Left Side -->
                    <div class="flex items-center gap-8">
                        <!-- Logo -->
                        <div class="flex-shrink-0 flex items-center">
                            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 group">
                                <div class="w-10 h-10 bg-gradient-to-br from-[#ff7261] to-[#a855f7] rounded-xl flex items-center justify-center shadow-md group-hover:shadow-lg transition-all duration-300 group-hover:scale-105">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                                    </svg>
                                </div>
                                <span class="text-xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-slate-800 to-slate-600">MikPOS</span>
                            </a>
                        </div>

                        <!-- Navigation Links -->
                        <div class="hidden space-x-1 sm:flex items-center">
                            <a href="{{ route('dashboard') }}" class="px-3 py-2 rounded-lg text-sm font-medium text-slate-600 hover:text-[#ff7261] hover:bg-orange-50 transition-all duration-200 {{ request()->routeIs('dashboard') ? 'text-[#ff7261] bg-orange-50' : '' }}">
                                Dashboard
                            </a>
                            
                            <a href="#" class="px-3 py-2 rounded-lg text-sm font-medium text-slate-600 hover:text-[#ff7261] hover:bg-orange-50 transition-all duration-200">
                                POS
                            </a>

                            <!-- Administración Dropdown -->
                            <div class="relative group" x-data="{ open: false }">
                                <button @click="open = !open" @click.away="open = false" class="flex items-center px-3 py-2 rounded-lg text-sm font-medium text-slate-600 hover:text-[#ff7261] hover:bg-orange-50 transition-all duration-200 focus:outline-none">
                                    <span>Administración</span>
                                    <svg class="w-4 h-4 ml-1 transform transition-transform duration-200" :class="{'rotate-180': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                
                                <!-- Dropdown Menu -->
                                <div x-show="open" 
                                     x-transition:enter="transition ease-out duration-200"
                                     x-transition:enter-start="opacity-0 translate-y-1"
                                     x-transition:enter-end="opacity-100 translate-y-0"
                                     x-transition:leave="transition ease-in duration-150"
                                     x-transition:leave-start="opacity-100 translate-y-0"
                                     x-transition:leave-end="opacity-0 translate-y-1"
                                     class="absolute left-0 mt-2 w-48 bg-white rounded-xl shadow-xl border border-slate-100 py-1 z-50" 
                                     style="display: none;">
                                    <a href="{{ route('users') }}" class="block px-4 py-2.5 text-sm text-slate-600 hover:bg-orange-50 hover:text-[#ff7261] transition-colors first:rounded-t-xl last:rounded-b-xl">
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                            Usuarios
                                        </div>
                                    </a>
                                    <a href="#" class="block px-4 py-2.5 text-sm text-slate-600 hover:bg-orange-50 hover:text-[#ff7261] transition-colors">
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                            Sucursales
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Side (User Menu) -->
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-3 pl-4 border-l border-slate-200">
                            <div class="text-right hidden md:block">
                                <p class="text-sm font-semibold text-slate-700">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-slate-500 capitalize">{{ auth()->user()->role }}</p>
                            </div>
                            <div class="relative" x-data="{ open: false }">
                                <button @click="open = !open" @click.away="open = false" class="flex items-center focus:outline-none">
                                    @if(auth()->user()->avatar)
                                        <img class="h-9 w-9 rounded-full object-cover ring-2 ring-white shadow-sm" src="{{ auth()->user()->avatar }}" alt="{{ auth()->user()->name }}" />
                                    @else
                                        <div class="h-9 w-9 rounded-full bg-gradient-to-br from-[#ff7261] to-[#a855f7] flex items-center justify-center text-white font-bold shadow-sm ring-2 ring-white">
                                            {{ substr(auth()->user()->name, 0, 1) }}
                                        </div>
                                    @endif
                                </button>

                                <!-- User Dropdown -->
                                <div x-show="open" 
                                     x-transition:enter="transition ease-out duration-200"
                                     x-transition:enter-start="opacity-0 scale-95"
                                     x-transition:enter-end="opacity-100 scale-100"
                                     x-transition:leave="transition ease-in duration-75"
                                     x-transition:leave-start="opacity-100 scale-100"
                                     x-transition:leave-end="opacity-0 scale-95"
                                     class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-xl border border-slate-100 py-1 z-50"
                                     style="display: none;">
                                    <div class="px-4 py-2 border-b border-slate-100 md:hidden">
                                        <p class="text-sm font-semibold text-slate-700">{{ auth()->user()->name }}</p>
                                        <p class="text-xs text-slate-500">{{ auth()->user()->email }}</p>
                                    </div>
                                    <a href="#" class="block px-4 py-2 text-sm text-slate-600 hover:bg-orange-50 hover:text-[#ff7261] transition-colors">Perfil</a>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors">
                                            Cerrar Sesión
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <main class="py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                {{ $slot }}
            </div>
        </main>
    </div>

    @livewireScripts
</body>
</html>

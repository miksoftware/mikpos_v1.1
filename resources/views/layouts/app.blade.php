<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Dashboard - MikPOS' }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700,800" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="antialiased bg-slate-100 font-sans" x-data="{ sidebarOpen: true, mobileMenuOpen: false }">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <aside :class="sidebarOpen ? 'w-64' : 'w-20'"
            class="fixed inset-y-0 left-0 z-50 bg-gradient-to-b from-[#1a1225] via-[#231730] to-[#1a1225] transition-all duration-300 ease-in-out hidden lg:flex lg:flex-col">
            <!-- Logo -->
            <div class="flex flex-col items-start px-4 py-3 border-b border-white/10">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                    <div
                        class="w-10 h-10 bg-gradient-to-br from-[#ff7261] to-[#a855f7] rounded-xl flex items-center justify-center shadow-lg flex-shrink-0">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                        </svg>
                    </div>
                    <span x-show="sidebarOpen" x-transition:enter="transition-opacity duration-200"
                        x-transition:leave="transition-opacity duration-200"
                        class="text-lg font-bold text-white truncate max-w-[160px]">{{ auth()->user()->branch?->name ?? 'MikPOS' }}</span>
                </a>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto scrollbar-hide">
                <!-- Dashboard -->
                <a href="{{ route('dashboard') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 group {{ request()->routeIs('dashboard') ? 'bg-gradient-to-r from-[#ff7261]/20 to-[#a855f7]/20 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                    <svg class="w-5 h-5 flex-shrink-0 {{ request()->routeIs('dashboard') ? 'text-[#ff7261]' : 'group-hover:text-[#ff7261]' }}"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
                        </path>
                    </svg>
                    <span x-show="sidebarOpen" class="font-medium">Dashboard</span>
                </a>

                <!-- POS -->
                @if (auth()->user()->hasPermission('pos.access'))
                <a href="{{ route('pos') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 group {{ request()->routeIs('pos') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                    <svg class="w-5 h-5 flex-shrink-0 {{ request()->routeIs('pos') ? 'text-[#a855f7]' : 'group-hover:text-[#a855f7]' }}" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z">
                        </path>
                    </svg>
                    <span x-show="sidebarOpen" class="font-medium">POS</span>
                </a>
                @endif

                <!-- Cajas Section -->
                @if (auth()->user()->hasPermission('cash_registers.view') || auth()->user()->hasPermission('cash_reconciliations.view'))
                <div x-data="{ cajasOpen: {{ request()->routeIs('cash-registers') || request()->routeIs('cash-reconciliations') ? 'true' : 'false' }} }">
                    <button @click="cajasOpen = !cajasOpen"
                        class="w-full flex items-center justify-between gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 group text-slate-400 hover:bg-white/5 hover:text-white">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 flex-shrink-0 group-hover:text-[#a855f7]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z">
                                </path>
                            </svg>
                            <span x-show="sidebarOpen" class="font-medium">Cajas</span>
                        </div>
                        <svg x-show="sidebarOpen" class="w-4 h-4 transition-transform duration-200"
                            :class="cajasOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                            </path>
                        </svg>
                    </button>

                    <div x-show="cajasOpen && sidebarOpen" x-collapse
                        class="mt-1 ml-4 pl-4 border-l border-white/10 space-y-1">
                        @if (auth()->user()->hasPermission('cash_registers.view'))
                        <a href="{{ route('cash-registers') }}"
                            class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all duration-200 {{ request()->routeIs('cash-registers') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            <span class="text-sm">Creación de Cajas</span>
                        </a>
                        @endif
                        @if (auth()->user()->hasPermission('cash_reconciliations.view'))
                        <a href="{{ route('cash-reconciliations') }}"
                            class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all duration-200 {{ request()->routeIs('cash-reconciliations') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                            <span class="text-sm">Arqueos de Caja</span>
                        </a>
                        @endif
                    </div>
                </div>
                @endif

                <!-- Ventas -->
                @if (auth()->user()->hasPermission('sales.view'))
                <a href="{{ route('sales') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 group {{ request()->routeIs('sales') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                    <svg class="w-5 h-5 flex-shrink-0 group-hover:text-[#a855f7]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                    <span x-show="sidebarOpen" class="font-medium">Ventas</span>
                </a>
                @endif

                <!-- Administración Section -->
                <div x-data="{ adminOpen: {{ request()->routeIs('users') || request()->routeIs('branches') || request()->routeIs('roles') || request()->routeIs('departments') || request()->routeIs('municipalities') || request()->routeIs('tax-documents') || request()->routeIs('currencies') || request()->routeIs('payment-methods') || request()->routeIs('taxes') || request()->routeIs('system-documents') || request()->routeIs('categories') || request()->routeIs('subcategories') || request()->routeIs('brands') || request()->routeIs('units') || request()->routeIs('product-models') || request()->routeIs('presentations') || request()->routeIs('colors') || request()->routeIs('imeis') || request()->routeIs('product-field-config') || request()->routeIs('billing-settings') ? 'true' : 'false' }}, configOpen: {{ request()->routeIs('departments') || request()->routeIs('municipalities') || request()->routeIs('tax-documents') || request()->routeIs('currencies') || request()->routeIs('payment-methods') || request()->routeIs('taxes') || request()->routeIs('system-documents') || request()->routeIs('product-field-config') || request()->routeIs('billing-settings') ? 'true' : 'false' }}, productsOpen: {{ request()->routeIs('categories') || request()->routeIs('subcategories') || request()->routeIs('brands') || request()->routeIs('units') || request()->routeIs('product-models') || request()->routeIs('presentations') || request()->routeIs('colors') || request()->routeIs('imeis') ? 'true' : 'false' }} }">
                    <button @click="adminOpen = !adminOpen"
                        class="w-full flex items-center justify-between gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 group text-slate-400 hover:bg-white/5 hover:text-white">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 flex-shrink-0 group-hover:text-[#a855f7]" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                                </path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <span x-show="sidebarOpen" class="font-medium">Administración</span>
                        </div>
                        <svg x-show="sidebarOpen" class="w-4 h-4 transition-transform duration-200"
                            :class="adminOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                            </path>
                        </svg>
                    </button>

                    <div x-show="adminOpen && sidebarOpen" x-collapse
                        class="mt-1 ml-4 pl-4 border-l border-white/10 space-y-1">
                        <a href="{{ route('users') }}"
                            class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all duration-200 {{ request()->routeIs('users') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                                </path>
                            </svg>
                            <span class="text-sm">Usuarios</span>
                        </a>
                        <a href="{{ route('branches') }}"
                            class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all duration-200 {{ request()->routeIs('branches') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                                </path>
                            </svg>
                            <span class="text-sm">Sucursales</span>
                        </a>
                        <a href="{{ route('roles') }}"
                            class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all duration-200 {{ request()->routeIs('roles') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z">
                                </path>
                            </svg>
                            <span class="text-sm">Roles</span>
                        </a>

                        <!-- Configuración Submenu -->
                        <div>
                            <button @click="configOpen = !configOpen"
                                class="w-full flex items-center justify-between gap-3 px-3 py-2 rounded-lg transition-all duration-200 text-slate-400 hover:bg-white/5 hover:text-white">
                                <div class="flex items-center gap-3">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4">
                                        </path>
                                    </svg>
                                    <span class="text-sm">Configuración</span>
                                </div>
                                <svg class="w-3 h-3 transition-transform duration-200"
                                    :class="configOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>

                            <div x-show="configOpen" x-collapse
                                class="mt-1 ml-4 pl-3 border-l border-white/5 space-y-1">
                                <a href="{{ route('departments') }}"
                                    class="flex items-center gap-3 px-3 py-1.5 rounded-lg transition-all duration-200 {{ request()->routeIs('departments') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7">
                                        </path>
                                    </svg>
                                    <span class="text-sm">Departamentos</span>
                                </a>
                                <a href="{{ route('municipalities') }}"
                                    class="flex items-center gap-3 px-3 py-1.5 rounded-lg transition-all duration-200 {{ request()->routeIs('municipalities') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z">
                                        </path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    <span class="text-sm">Municipios</span>
                                </a>
                                <a href="{{ route('tax-documents') }}"
                                    class="flex items-center gap-3 px-3 py-1.5 rounded-lg transition-all duration-200 {{ request()->routeIs('tax-documents') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                        </path>
                                    </svg>
                                    <span class="text-sm">Documentos Tributarios</span>
                                </a>
                                <a href="{{ route('currencies') }}"
                                    class="flex items-center gap-3 px-3 py-1.5 rounded-lg transition-all duration-200 {{ request()->routeIs('currencies') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                                        </path>
                                    </svg>
                                    <span class="text-sm">Monedas</span>
                                </a>
                                <a href="{{ route('payment-methods') }}"
                                    class="flex items-center gap-3 px-3 py-1.5 rounded-lg transition-all duration-200 {{ request()->routeIs('payment-methods') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z">
                                        </path>
                                    </svg>
                                    <span class="text-sm">Medios de Pago</span>
                                </a>
                                <a href="{{ route('taxes') }}"
                                    class="flex items-center gap-3 px-3 py-1.5 rounded-lg transition-all duration-200 {{ request()->routeIs('taxes') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z">
                                        </path>
                                    </svg>
                                    <span class="text-sm">Impuestos</span>
                                </a>
                                @if (auth()->user()->hasPermission('system_documents.view'))
                                <a href="{{ route('system-documents') }}"
                                    class="flex items-center gap-3 px-3 py-1.5 rounded-lg transition-all duration-200 {{ request()->routeIs('system-documents') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                        </path>
                                    </svg>
                                    <span class="text-sm">Documentos Sistema</span>
                                </a>
                                @endif
                                @if (auth()->user()->hasPermission('product_field_config.view'))
                                <a href="{{ route('product-field-config') }}"
                                    class="flex items-center gap-3 px-3 py-1.5 rounded-lg transition-all duration-200 {{ request()->routeIs('product-field-config') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4">
                                        </path>
                                    </svg>
                                    <span class="text-sm">Config. Campos Producto</span>
                                </a>
                                @endif
                                @if (auth()->user()->hasPermission('billing_settings.view'))
                                <a href="{{ route('billing-settings') }}"
                                    class="flex items-center gap-3 px-3 py-1.5 rounded-lg transition-all duration-200 {{ request()->routeIs('billing-settings') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                        </path>
                                    </svg>
                                    <span class="text-sm">Facturación Electrónica</span>
                                </a>
                                @endif

                                <!-- Productos Submenu dentro de Configuración -->
                                <div>
                                    <button @click="productsOpen = !productsOpen"
                                        class="w-full flex items-center justify-between gap-3 px-3 py-1.5 rounded-lg transition-all duration-200 text-slate-400 hover:bg-white/5 hover:text-white">
                                        <div class="flex items-center gap-3">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4">
                                                </path>
                                            </svg>
                                            <span class="text-sm">Productos</span>
                                        </div>
                                        <svg class="w-3 h-3 transition-transform duration-200"
                                            :class="productsOpen ? 'rotate-180' : ''" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </button>

                                    <div x-show="productsOpen" x-collapse
                                        class="mt-1 ml-4 pl-3 border-l border-white/5 space-y-1">
                                        <a href="{{ route('categories') }}"
                                            class="flex items-center gap-3 px-3 py-1.5 rounded-lg transition-all duration-200 {{ request()->routeIs('categories') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z">
                                                </path>
                                            </svg>
                                            <span class="text-xs">Categorías</span>
                                        </a>
                                        <a href="{{ route('subcategories') }}"
                                            class="flex items-center gap-3 px-3 py-1.5 rounded-lg transition-all duration-200 {{ request()->routeIs('subcategories') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5a2 2 0 01-2 2z">
                                                </path>
                                            </svg>
                                            <span class="text-xs">Subcategorías</span>
                                        </a>
                                        <a href="{{ route('brands') }}"
                                            class="flex items-center gap-3 px-3 py-1.5 rounded-lg transition-all duration-200 {{ request()->routeIs('brands') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z">
                                                </path>
                                            </svg>
                                            <span class="text-xs">Marcas</span>
                                        </a>
                                        <a href="{{ route('units') }}"
                                            class="flex items-center gap-3 px-3 py-1.5 rounded-lg transition-all duration-200 {{ request()->routeIs('units') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3">
                                                </path>
                                            </svg>
                                            <span class="text-xs">Unidades</span>
                                        </a>
                                        <a href="{{ route('product-models') }}"
                                            class="flex items-center gap-3 px-3 py-1.5 rounded-lg transition-all duration-200 {{ request()->routeIs('product-models') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4">
                                                </path>
                                            </svg>
                                            <span class="text-xs">Modelos</span>
                                        </a>
                                        <a href="{{ route('presentations') }}"
                                            class="flex items-center gap-3 px-3 py-1.5 rounded-lg transition-all duration-200 {{ request()->routeIs('presentations') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7">
                                                </path>
                                            </svg>
                                            <span class="text-xs">Presentaciones</span>
                                        </a>
                                        <a href="{{ route('colors') }}"
                                            class="flex items-center gap-3 px-3 py-1.5 rounded-lg transition-all duration-200 {{ request()->routeIs('colors') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01">
                                                </path>
                                            </svg>
                                            <span class="text-xs">Colores</span>
                                        </a>
                                        <a href="{{ route('imeis') }}"
                                            class="flex items-center gap-3 px-3 py-1.5 rounded-lg transition-all duration-200 {{ request()->routeIs('imeis') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z">
                                                </path>
                                            </svg>
                                            <span class="text-xs">IMEIs</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Creación Section -->
                <div x-data="{ creacionOpen: {{ request()->routeIs('customers') || request()->routeIs('suppliers') || request()->routeIs('products') || request()->routeIs('combos') ? 'true' : 'false' }} }">
                    <button @click="creacionOpen = !creacionOpen"
                        class="w-full flex items-center justify-between gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 group text-slate-400 hover:bg-white/5 hover:text-white">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 flex-shrink-0 group-hover:text-[#a855f7]" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            <span x-show="sidebarOpen" class="font-medium">Creación</span>
                        </div>
                        <svg x-show="sidebarOpen" class="w-4 h-4 transition-transform duration-200"
                            :class="creacionOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                            </path>
                        </svg>
                    </button>

                    <div x-show="creacionOpen && sidebarOpen" x-collapse
                        class="mt-1 ml-4 pl-4 border-l border-white/10 space-y-1">
                        @if (auth()->user()->hasPermission('products.view'))
                            <a href="{{ route('products') }}"
                                class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all duration-200 {{ request()->routeIs('products') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4">
                                    </path>
                                </svg>
                                <span class="text-sm">Productos</span>
                            </a>
                        @endif
                        @if (auth()->user()->hasPermission('customers.view'))
                            <a href="{{ route('customers') }}"
                                class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all duration-200 {{ request()->routeIs('customers') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                                    </path>
                                </svg>
                                <span class="text-sm">Clientes</span>
                            </a>
                        @endif
                        @if (auth()->user()->hasPermission('suppliers.view'))
                            <a href="{{ route('suppliers') }}"
                                class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all duration-200 {{ request()->routeIs('suppliers') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                                    </path>
                                </svg>
                                <span class="text-sm">Proveedores</span>
                            </a>
                        @endif
                        @if (auth()->user()->hasPermission('combos.view'))
                            <a href="{{ route('combos') }}"
                                class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all duration-200 {{ request()->routeIs('combos') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                                    </path>
                                </svg>
                                <span class="text-sm">Combos</span>
                            </a>
                        @endif
                        @if (auth()->user()->hasPermission('services.view'))
                            <a href="{{ route('services') }}"
                                class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all duration-200 {{ request()->routeIs('services') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                                    </path>
                                </svg>
                                <span class="text-sm">Servicios</span>
                            </a>
                        @endif
                    </div>
                </div>

                <!-- Inventarios Section -->
                <div x-data="{ inventariosOpen: {{ request()->routeIs('purchases') || request()->routeIs('purchases.create') || request()->routeIs('inventory-adjustments') || request()->routeIs('inventory-transfers') ? 'true' : 'false' }} }">
                    <button @click="inventariosOpen = !inventariosOpen"
                        class="w-full flex items-center justify-between gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 group text-slate-400 hover:bg-white/5 hover:text-white">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 flex-shrink-0 group-hover:text-[#a855f7]" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                            <span x-show="sidebarOpen" class="font-medium">Inventarios</span>
                        </div>
                        <svg x-show="sidebarOpen" class="w-4 h-4 transition-transform duration-200"
                            :class="inventariosOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                            </path>
                        </svg>
                    </button>

                    <div x-show="inventariosOpen && sidebarOpen" x-collapse
                        class="mt-1 ml-4 pl-4 border-l border-white/10 space-y-1">
                        @if (auth()->user()->hasPermission('purchases.view'))
                            <a href="{{ route('purchases') }}"
                                class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all duration-200 {{ request()->routeIs('purchases') || request()->routeIs('purchases.create') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z">
                                    </path>
                                </svg>
                                <span class="text-sm">Compras</span>
                            </a>
                        @endif
                        @if (auth()->user()->hasPermission('inventory_adjustments.view'))
                            <a href="{{ route('inventory-adjustments') }}"
                                class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all duration-200 {{ request()->routeIs('inventory-adjustments') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z">
                                    </path>
                                </svg>
                                <span class="text-sm">Ajustes Inventario</span>
                            </a>
                        @endif
                        @if (auth()->user()->hasPermission('inventory_transfers.view'))
                            <a href="{{ route('inventory-transfers') }}"
                                class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all duration-200 {{ request()->routeIs('inventory-transfers') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4">
                                    </path>
                                </svg>
                                <span class="text-sm">Traslados</span>
                            </a>
                        @endif
                    </div>
                </div>
            </nav>

            <!-- Toggle Button -->
            <div class="p-3 border-t border-white/10">
                <button @click="sidebarOpen = !sidebarOpen"
                    class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-xl bg-white/5 hover:bg-white/10 text-slate-400 hover:text-white transition-all duration-200">
                    <svg :class="sidebarOpen ? 'rotate-180' : ''" class="w-5 h-5 transition-transform duration-300"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path>
                    </svg>
                    <span x-show="sidebarOpen" class="text-sm font-medium">Colapsar</span>
                </button>
            </div>
        </aside>

        <!-- Main Content -->
        <div :class="sidebarOpen ? 'lg:pl-64' : 'lg:pl-20'" class="flex-1 transition-all duration-300">
            <!-- Top Bar -->
            <header
                class="h-16 bg-white border-b border-slate-200 sticky top-0 z-40 flex items-center justify-between px-4 lg:px-6">
                <!-- Mobile menu button -->
                <button @click="mobileMenuOpen = true"
                    class="lg:hidden p-2 rounded-lg text-slate-500 hover:bg-slate-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>

                <!-- Page Title / Breadcrumb -->
                <div class="hidden lg:block">
                </div>

                <!-- Right Side -->
                <div class="flex items-center gap-4">
                    <!-- Notifications -->
                    <button class="p-2 rounded-lg text-slate-500 hover:bg-slate-100 relative">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9">
                            </path>
                        </svg>
                        <span class="absolute top-1 right-1 w-2 h-2 bg-[#ff7261] rounded-full"></span>
                    </button>

                    <!-- User Menu -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open"
                            class="flex items-center gap-3 p-1.5 rounded-xl hover:bg-slate-100 transition-colors">
                            @if (auth()->user()->avatar)
                                <img class="h-8 w-8 rounded-lg object-cover" src="{{ auth()->user()->avatar }}"
                                    alt="{{ auth()->user()->name }}" />
                            @else
                                <div
                                    class="h-8 w-8 rounded-lg bg-gradient-to-br from-[#ff7261] to-[#a855f7] flex items-center justify-center text-white font-semibold text-sm">
                                    {{ substr(auth()->user()->name, 0, 1) }}
                                </div>
                            @endif
                            <div class="hidden md:block text-left">
                                <p class="text-sm font-medium text-slate-700">{{ auth()->user()->name }}</p>
                                <p class="text-sm text-slate-500">
                                    {{ auth()->user()->roles->first()?->display_name ?? 'Sin rol' }}
                                </p>
                            </div>
                            <svg class="w-4 h-4 text-slate-400 hidden md:block" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>

                        <!-- Dropdown -->
                        <div x-show="open" @click.away="open = false" x-transition
                            class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg border border-slate-200 py-1 z-50">
                            <a href="#"
                                class="flex items-center gap-2 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                Mi Perfil
                            </a>
                            <hr class="my-1 border-slate-100">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                    class="flex items-center gap-2 w-full px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1">
                                        </path>
                                    </svg>
                                    Cerrar Sesión
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="p-4 lg:p-6">
                {{ $slot }}
            </main>
        </div>

        <!-- Mobile Sidebar Overlay -->
        <div x-show="mobileMenuOpen" x-transition:enter="transition-opacity ease-out duration-300"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-in duration-200" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" class="fixed inset-0 bg-black/50 z-50 lg:hidden"
            @click="mobileMenuOpen = false"></div>

        <!-- Mobile Sidebar -->
        <aside x-show="mobileMenuOpen" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
            class="fixed inset-y-0 left-0 w-64 bg-gradient-to-b from-[#1a1225] via-[#231730] to-[#1a1225] z-50 lg:hidden">
            <div class="flex flex-col px-4 py-3 border-b border-white/10">
                <div class="flex items-center justify-between">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 bg-gradient-to-br from-[#ff7261] to-[#a855f7] rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                            </svg>
                        </div>
                        <span class="text-lg font-bold text-white truncate max-w-[140px]">{{ auth()->user()->branch?->name ?? 'MikPOS' }}</span>
                    </a>
                    <button @click="mobileMenuOpen = false"
                        class="p-2 rounded-lg text-slate-400 hover:text-white hover:bg-white/10">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            <nav class="px-3 py-4 space-y-1">
                <a href="{{ route('dashboard') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl {{ request()->routeIs('dashboard') ? 'bg-gradient-to-r from-[#ff7261]/20 to-[#a855f7]/20 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
                        </path>
                    </svg>
                    <span class="font-medium">Dashboard</span>
                </a>
                @if (auth()->user()->hasPermission('pos.access'))
                <a href="{{ route('pos') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl {{ request()->routeIs('pos') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                    <svg class="w-5 h-5 {{ request()->routeIs('pos') ? 'text-[#a855f7]' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z">
                        </path>
                    </svg>
                    <span class="font-medium">POS</span>
                </a>
                @endif
                <div class="pt-2 border-t border-white/10 mt-2">
                    <p class="px-3 py-2 text-sm font-semibold text-slate-500 uppercase">Administración</p>
                    <a href="{{ route('users') }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-xl {{ request()->routeIs('users') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                            </path>
                        </svg>
                        <span>Usuarios</span>
                    </a>
                    <a href="{{ route('branches') }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-xl {{ request()->routeIs('branches') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                            </path>
                        </svg>
                        <span>Sucursales</span>
                    </a>
                    <a href="{{ route('roles') }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-xl {{ request()->routeIs('roles') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z">
                            </path>
                        </svg>
                        <span>Roles</span>
                    </a>
                    <p class="px-3 py-2 text-sm font-semibold text-slate-500 uppercase mt-2">Configuración</p>
                    <a href="{{ route('departments') }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-xl {{ request()->routeIs('departments') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7">
                            </path>
                        </svg>
                        <span>Departamentos</span>
                    </a>
                    <a href="{{ route('municipalities') }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-xl {{ request()->routeIs('municipalities') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z">
                            </path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <span>Municipios</span>
                    </a>
                    <a href="{{ route('tax-documents') }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-xl {{ request()->routeIs('tax-documents') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                            </path>
                        </svg>
                        <span>Doc. Tributarios</span>
                    </a>
                    <a href="{{ route('currencies') }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-xl {{ request()->routeIs('currencies') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                            </path>
                        </svg>
                        <span>Monedas</span>
                    </a>
                    <a href="{{ route('payment-methods') }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-xl {{ request()->routeIs('payment-methods') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z">
                            </path>
                        </svg>
                        <span>Medios de Pago</span>
                    </a>
                    <a href="{{ route('taxes') }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-xl {{ request()->routeIs('taxes') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z">
                            </path>
                        </svg>
                        <span>Impuestos</span>
                    </a>
                    @if (auth()->user()->hasPermission('system_documents.view'))
                    <a href="{{ route('system-documents') }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-xl {{ request()->routeIs('system-documents') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                            </path>
                        </svg>
                        <span>Doc. Sistema</span>
                    </a>
                    @endif
                    @if (auth()->user()->hasPermission('product_field_config.view'))
                    <a href="{{ route('product-field-config') }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-xl {{ request()->routeIs('product-field-config') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4">
                            </path>
                        </svg>
                        <span>Config. Campos</span>
                    </a>
                    @endif
                    <p class="px-3 py-2 text-sm font-semibold text-slate-500 uppercase mt-2">Productos</p>
                    <a href="{{ route('categories') }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-xl {{ request()->routeIs('categories') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                        </svg>
                        <span>Categorías</span>
                    </a>
                    <a href="{{ route('subcategories') }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-xl {{ request()->routeIs('subcategories') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5a2 2 0 01-2 2z">
                            </path>
                        </svg>
                        <span>Subcategorías</span>
                    </a>
                    <a href="{{ route('brands') }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-xl {{ request()->routeIs('brands') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z">
                            </path>
                        </svg>
                        <span>Marcas</span>
                    </a>
                    <a href="{{ route('units') }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-xl {{ request()->routeIs('units') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3">
                            </path>
                        </svg>
                        <span>Unidades</span>
                    </a>
                    <a href="{{ route('product-models') }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-xl {{ request()->routeIs('product-models') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                        <span>Modelos</span>
                    </a>
                    <a href="{{ route('presentations') }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-xl {{ request()->routeIs('presentations') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7">
                            </path>
                        </svg>
                        <span>Presentaciones</span>
                    </a>
                    <a href="{{ route('colors') }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-xl {{ request()->routeIs('colors') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01">
                            </path>
                        </svg>
                        <span>Colores</span>
                    </a>
                    <a href="{{ route('imeis') }}"
                        class="flex items-center gap-3 px-3 py-2 rounded-xl {{ request()->routeIs('imeis') ? 'bg-white/10 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                        <span>IMEIs</span>
                    </a>
                </div>
            </nav>
        </aside>
    </div>

    <!-- Toast Notifications -->
    <x-toast />

    @livewireScripts
    @stack('scripts')
</body>

</html>

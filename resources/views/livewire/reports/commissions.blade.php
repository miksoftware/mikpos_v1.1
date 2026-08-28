<div class="space-y-6">
    <x-toast />

    {{-- Top Header with Title and Mode Switcher --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#ff7261] to-[#a855f7] flex items-center justify-center text-white shadow-md shadow-[#ff7261]/20">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
            </div>
            <div>
                <h1 class="text-2xl font-black text-slate-800 tracking-tight flex items-center gap-2">
                    Comisiones
                    @if($viewMode === 'versus')
                    <span class="inline-flex items-center gap-1 text-xs font-bold px-2.5 py-1 rounded-full bg-gradient-to-r from-[#ff7261] to-[#a855f7] text-white shadow-sm">
                        ⚔️ MODO VERSUS
                    </span>
                    @endif
                </h1>
                <p class="text-xs sm:text-sm text-slate-500">
                    {{ $viewMode === 'versus' ? 'Comparativa de comisiones y rendimiento de vendedores inter-meses' : 'Análisis de comisiones por vendedor y producto' }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-3 flex-wrap">
            {{-- Mode Switcher Segmented Control --}}
            <div class="bg-slate-100 p-1 rounded-xl flex items-center border border-slate-200 text-sm font-semibold">
                <button wire:click="setViewMode('standard')"
                    class="px-3.5 py-1.5 rounded-lg transition-all flex items-center gap-1.5 {{ $viewMode === 'standard' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-800' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path></svg>
                    Estándar
                </button>
                <button wire:click="setViewMode('versus')"
                    class="px-3.5 py-1.5 rounded-lg transition-all flex items-center gap-1.5 {{ $viewMode === 'versus' ? 'bg-gradient-to-r from-[#ff7261] to-[#a855f7] text-white shadow-sm' : 'text-slate-500 hover:text-slate-800' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    ⚔️ Versus
                </button>
            </div>

            @if($viewMode === 'versus')
                @if(auth()->user()->hasPermission('reports.export'))
                <button wire:click="exportExcel" wire:loading.attr="disabled" wire:target="exportExcel"
                    class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-xl shadow-sm hover:shadow transition-all duration-200 disabled:opacity-50 gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    <span wire:loading.remove wire:target="exportExcel">Excel Comparativo</span>
                    <span wire:loading wire:target="exportExcel">Exportando...</span>
                </button>
                @endif
            @else
                {{-- Standard Export Dropdown --}}
                <div class="flex items-center gap-2">
                    @if(auth()->user()->hasPermission('reports.export'))
                    <button wire:click="exportExcel" wire:loading.attr="disabled" wire:target="exportExcel"
                        class="inline-flex items-center px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-sm hover:shadow transition-all duration-200 disabled:opacity-50 gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        <span>Excel</span>
                    </button>
                    @endif

                    <div x-data="{ exportOpen: false }" class="relative">
                        <button @click="exportOpen = !exportOpen" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea] transition shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            Exportar PDF
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                        <div x-show="exportOpen" @click.away="exportOpen = false" x-transition class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-lg border border-slate-200 z-50 overflow-hidden text-xs">
                            <button wire:click="exportPdf('detailed')" @click="exportOpen = false" class="w-full flex items-center gap-3 px-4 py-3 text-slate-700 hover:bg-slate-50 transition">
                                <svg class="w-4 h-4 text-[#a855f7]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                <div class="text-left">
                                    <p class="font-bold text-slate-800">Discriminado</p>
                                    <p class="text-[10px] text-slate-400">Detalle por venta individual</p>
                                </div>
                            </button>
                            <button wire:click="exportPdf('totalized')" @click="exportOpen = false" class="w-full flex items-center gap-3 px-4 py-3 text-slate-700 hover:bg-slate-50 transition border-t border-slate-100">
                                <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path></svg>
                                <div class="text-left">
                                    <p class="font-bold text-slate-800">Totalizado</p>
                                    <p class="text-[10px] text-slate-400">Agrupado por producto/servicio</p>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- FILTERS SECTION --}}
    @if($viewMode === 'standard')
    {{-- Standard Filters --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7 gap-3">
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Período</label>
                <select wire:model.live="dateRange" class="w-full px-3 py-2 text-xs border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                    <option value="today">Hoy</option>
                    <option value="yesterday">Ayer</option>
                    <option value="week">Esta semana</option>
                    <option value="month">Este mes</option>
                    <option value="last_month">Mes anterior</option>
                    <option value="quarter">Este trimestre</option>
                    <option value="year">Este año</option>
                    <option value="custom">Personalizado</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Desde</label>
                <input wire:model.live="startDate" type="date" class="w-full px-3 py-2 text-xs border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" @if($dateRange !== 'custom') disabled @endif>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Hasta</label>
                <input wire:model.live="endDate" type="date" class="w-full px-3 py-2 text-xs border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" @if($dateRange !== 'custom') disabled @endif>
            </div>
            @if($isSuperAdmin)
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Sucursal</label>
                <select wire:model.live="selectedBranchId" class="w-full px-3 py-2 text-xs border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                    <option value="">Todas</option>
                    @foreach($branches as $branch)
                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Vendedor</label>
                <select wire:model.live="selectedUserId" class="w-full px-3 py-2 text-xs border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                    <option value="">Todos</option>
                    @foreach($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Categoría</label>
                <select wire:model.live="selectedCategoryId" class="w-full px-3 py-2 text-xs border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                    <option value="">Todas</option>
                    @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Marca</label>
                <select wire:model.live="selectedBrandId" class="w-full px-3 py-2 text-xs border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                    <option value="">Todas</option>
                    @foreach($brands as $brand)
                    <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="flex items-center justify-end mt-3 pt-3 border-t border-slate-100">
            <button wire:click="clearFilters" class="text-xs text-slate-500 hover:text-slate-700 flex items-center gap-1 font-semibold">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                Limpiar Filtros
            </button>
        </div>
    </div>
    @else
    {{-- Versus Filters (Light System Design) --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            {{-- Quick Presets --}}
            <div class="space-y-1.5">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Comparación Rápida de Comisiones</span>
                <div class="flex flex-wrap gap-2">
                    <button wire:click="$set('versusPreset', 'mom')"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 {{ $versusPreset === 'mom' ? 'bg-gradient-to-r from-[#ff7261] to-[#a855f7] text-white shadow-sm' : 'bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200' }}">
                        ⚡ Mes Actual vs Mes Anterior (MoM)
                    </button>
                    <button wire:click="$set('versusPreset', 'yoy')"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 {{ $versusPreset === 'yoy' ? 'bg-gradient-to-r from-[#ff7261] to-[#a855f7] text-white shadow-sm' : 'bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200' }}">
                        ⚡ Mismo Mes vs Año Anterior (YoY)
                    </button>
                    <button wire:click="$set('versusPreset', 'custom')"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 {{ $versusPreset === 'custom' ? 'bg-gradient-to-r from-[#ff7261] to-[#a855f7] text-white shadow-sm' : 'bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200' }}">
                        ⚙️ Personalizado
                    </button>
                </div>
            </div>

            {{-- Period Selectors --}}
            <div class="flex flex-wrap items-center gap-3">
                {{-- Period A --}}
                <div class="bg-orange-50/80 rounded-xl p-2.5 border border-[#ff7261]/40 flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-[#ff7261] flex-shrink-0"></span>
                    <div>
                        <div class="text-[10px] uppercase font-bold text-[#ff7261]">Período A (Base)</div>
                        @if($versusPreset === 'custom')
                        <input wire:model.live="monthA" type="month" class="bg-transparent text-slate-800 text-xs font-bold border-0 p-0 focus:ring-0 cursor-pointer">
                        @else
                        <span class="text-xs font-bold text-slate-800">{{ ucfirst($labelA) }}</span>
                        @endif
                    </div>
                </div>

                <div class="text-center font-black text-slate-400 text-xs px-1">VS</div>

                {{-- Period B --}}
                <div class="bg-purple-50/80 rounded-xl p-2.5 border border-[#a855f7]/40 flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-[#a855f7] flex-shrink-0"></span>
                    <div>
                        <div class="text-[10px] uppercase font-bold text-[#a855f7]">Período B (Comparado)</div>
                        @if($versusPreset === 'custom')
                        <input wire:model.live="monthB" type="month" class="bg-transparent text-slate-800 text-xs font-bold border-0 p-0 focus:ring-0 cursor-pointer">
                        @else
                        <span class="text-xs font-bold text-slate-800">{{ ucfirst($labelB) }}</span>
                        @endif
                    </div>
                </div>

                {{-- Branch Filter --}}
                @if($isSuperAdmin)
                <div>
                    <select wire:model.live="selectedBranchId" class="px-3 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 text-xs font-semibold focus:ring-2 focus:ring-[#ff7261]">
                        <option value="">Todas las sucursales</option>
                        @foreach($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- ========================================================================= --}}
    {{-- MODO VERSUS CONTENT --}}
    {{-- ========================================================================= --}}
    @if($viewMode === 'versus' && !empty($versusSummary))
    @php
        $A = $versusSummary['A'];
        $B = $versusSummary['B'];

        $diffComm = $B['totalCommissions'] - $A['totalCommissions'];
        $growthComm = $A['totalCommissions'] > 0 ? ($diffComm / $A['totalCommissions']) * 100 : ($B['totalCommissions'] > 0 ? 100 : 0);

        $diffSales = $B['totalSales'] - $A['totalSales'];
        $growthSales = $A['totalSales'] > 0 ? ($diffSales / $A['totalSales']) * 100 : ($B['totalSales'] > 0 ? 100 : 0);

        $diffRate = $B['avgCommissionRate'] - $A['avgCommissionRate'];
    @endphp

    {{-- BATTLE CARDS (Crystal Clear Comparison Layout) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Card 1: Total Comisiones --}}
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200 hover:shadow-md transition-all flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Comisiones</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold {{ $growthComm >= 0 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-red-50 text-red-700 border border-red-200' }}">
                        {{ $growthComm >= 0 ? '▲ +' : '▼ ' }}{{ number_format($growthComm, 1) }}%
                    </span>
                </div>

                {{-- Side-by-side Period Boxes --}}
                <div class="grid grid-cols-2 gap-2 bg-slate-50/80 rounded-xl p-3 border border-slate-100 mb-3">
                    <div class="border-r border-slate-200/80 pr-2">
                        <div class="flex items-center gap-1.5 mb-1">
                            <span class="w-2.5 h-2.5 rounded-full bg-[#ff7261] flex-shrink-0"></span>
                            <span class="text-[11px] font-bold text-slate-600 truncate">{{ ucfirst($labelA) }} (A)</span>
                        </div>
                        <div class="text-base sm:text-lg font-black text-slate-800">
                            ${{ number_format($A['totalCommissions'], 0) }}
                        </div>
                    </div>
                    <div class="pl-2">
                        <div class="flex items-center gap-1.5 mb-1">
                            <span class="w-2.5 h-2.5 rounded-full bg-[#a855f7] flex-shrink-0"></span>
                            <span class="text-[11px] font-bold text-slate-600 truncate">{{ ucfirst($labelB) }} (B)</span>
                        </div>
                        <div class="text-base sm:text-lg font-black text-purple-700">
                            ${{ number_format($B['totalCommissions'], 0) }}
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between text-xs pt-1 border-t border-slate-100">
                    <span class="text-slate-500 font-medium">Diferencia (B vs A):</span>
                    <span class="font-black {{ $diffComm >= 0 ? 'text-purple-600' : 'text-red-600' }}">
                        {{ $diffComm >= 0 ? '+' : '' }}${{ number_format($diffComm, 0) }}
                    </span>
                </div>
                <div class="mt-2">
                    <div class="h-1.5 w-full bg-slate-100 rounded-full flex overflow-hidden">
                        <div class="bg-[#ff7261] h-full" style="width: {{ $A['totalCommissions'] + $B['totalCommissions'] > 0 ? ($A['totalCommissions'] / ($A['totalCommissions'] + $B['totalCommissions'])) * 100 : 50 }}%"></div>
                        <div class="bg-[#a855f7] h-full" style="width: {{ $A['totalCommissions'] + $B['totalCommissions'] > 0 ? ($B['totalCommissions'] / ($A['totalCommissions'] + $B['totalCommissions'])) * 100 : 50 }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card 2: Ventas Comisionables --}}
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200 hover:shadow-md transition-all flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Ventas Comisionables</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold {{ $growthSales >= 0 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-red-50 text-red-700 border border-red-200' }}">
                        {{ $growthSales >= 0 ? '▲ +' : '▼ ' }}{{ number_format($growthSales, 1) }}%
                    </span>
                </div>

                {{-- Side-by-side Period Boxes --}}
                <div class="grid grid-cols-2 gap-2 bg-slate-50/80 rounded-xl p-3 border border-slate-100 mb-3">
                    <div class="border-r border-slate-200/80 pr-2">
                        <div class="flex items-center gap-1.5 mb-1">
                            <span class="w-2.5 h-2.5 rounded-full bg-[#ff7261] flex-shrink-0"></span>
                            <span class="text-[11px] font-bold text-slate-600 truncate">{{ ucfirst($labelA) }} (A)</span>
                        </div>
                        <div class="text-base sm:text-lg font-black text-slate-800">
                            ${{ number_format($A['totalSales'], 0) }}
                        </div>
                    </div>
                    <div class="pl-2">
                        <div class="flex items-center gap-1.5 mb-1">
                            <span class="w-2.5 h-2.5 rounded-full bg-[#a855f7] flex-shrink-0"></span>
                            <span class="text-[11px] font-bold text-slate-600 truncate">{{ ucfirst($labelB) }} (B)</span>
                        </div>
                        <div class="text-base sm:text-lg font-black text-slate-800">
                            ${{ number_format($B['totalSales'], 0) }}
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between text-xs pt-1 border-t border-slate-100">
                    <span class="text-slate-500 font-medium">Diferencia:</span>
                    <span class="font-black {{ $diffSales >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                        {{ $diffSales >= 0 ? '+' : '' }}${{ number_format($diffSales, 0) }}
                    </span>
                </div>
                <div class="mt-2 text-[10px] text-slate-400 flex justify-between">
                    <span>Items vendidos:</span>
                    <span class="font-bold">{{ number_format($B['totalItems'], 0) }} (B) vs {{ number_format($A['totalItems'], 0) }} (A)</span>
                </div>
            </div>
        </div>

        {{-- Card 3: Tasa Promedio de Comisión --}}
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200 hover:shadow-md transition-all flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Tasa Promedio Comisión</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold {{ $diffRate >= 0 ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-slate-100 text-slate-600' }}">
                        {{ $diffRate >= 0 ? '+' : '' }}{{ number_format($diffRate, 2) }} pts
                    </span>
                </div>

                {{-- Side-by-side Period Boxes --}}
                <div class="grid grid-cols-2 gap-2 bg-slate-50/80 rounded-xl p-3 border border-slate-100 mb-3">
                    <div class="border-r border-slate-200/80 pr-2">
                        <div class="flex items-center gap-1.5 mb-1">
                            <span class="w-2.5 h-2.5 rounded-full bg-[#ff7261] flex-shrink-0"></span>
                            <span class="text-[11px] font-bold text-slate-600 truncate">{{ ucfirst($labelA) }} (A)</span>
                        </div>
                        <div class="text-base sm:text-lg font-black text-slate-800">
                            {{ number_format($A['avgCommissionRate'], 1) }}%
                        </div>
                    </div>
                    <div class="pl-2">
                        <div class="flex items-center gap-1.5 mb-1">
                            <span class="w-2.5 h-2.5 rounded-full bg-[#a855f7] flex-shrink-0"></span>
                            <span class="text-[11px] font-bold text-slate-600 truncate">{{ ucfirst($labelB) }} (B)</span>
                        </div>
                        <div class="text-base sm:text-lg font-black text-slate-800">
                            {{ number_format($B['avgCommissionRate'], 1) }}%
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between text-xs pt-1 border-t border-slate-100">
                    <span class="text-slate-500 font-medium">Comisión s/ Venta:</span>
                    <span class="font-bold text-slate-700">Porcentaje medio liquidado</span>
                </div>
                <div class="mt-2 text-[10px] text-slate-400 flex justify-between">
                    <span>Transacciones:</span>
                    <span class="font-bold">{{ $B['totalTransactions'] }} (B) vs {{ $A['totalTransactions'] }} (A)</span>
                </div>
            </div>
        </div>

        {{-- Card 4: Vendedor Estrella / Mayor Comisión --}}
        <div class="bg-white rounded-2xl p-5 shadow-sm border-2 border-purple-200 hover:shadow-md transition-all flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-black uppercase tracking-wider text-slate-700 flex items-center gap-1">
                        🏆 VENDEDOR LÍDER
                    </span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black bg-purple-100 text-purple-800">
                        Top Comisionista
                    </span>
                </div>

                {{-- Side-by-side Period Boxes --}}
                <div class="grid grid-cols-2 gap-2 bg-slate-50/80 rounded-xl p-3 border border-slate-100 mb-3">
                    <div class="border-r border-slate-200/80 pr-2">
                        <div class="flex items-center gap-1.5 mb-1">
                            <span class="w-2.5 h-2.5 rounded-full bg-[#ff7261] flex-shrink-0"></span>
                            <span class="text-[11px] font-bold text-slate-600 truncate">{{ ucfirst($labelA) }} (A)</span>
                        </div>
                        <div class="text-xs font-bold text-slate-800 truncate" title="{{ $A['topSeller']['name'] }}">
                            {{ $A['topSeller']['name'] }}
                        </div>
                        <span class="text-sm font-black text-slate-700 block mt-0.5">${{ number_format($A['topSeller']['commission'], 0) }}</span>
                    </div>
                    <div class="pl-2">
                        <div class="flex items-center gap-1.5 mb-1">
                            <span class="w-2.5 h-2.5 rounded-full bg-[#a855f7] flex-shrink-0"></span>
                            <span class="text-[11px] font-bold text-slate-600 truncate">{{ ucfirst($labelB) }} (B)</span>
                        </div>
                        <div class="text-xs font-bold text-purple-900 truncate" title="{{ $B['topSeller']['name'] }}">
                            {{ $B['topSeller']['name'] }}
                        </div>
                        <span class="text-sm font-black text-purple-700 block mt-0.5">${{ number_format($B['topSeller']['commission'], 0) }}</span>
                    </div>
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between text-xs pt-1 border-t border-slate-100">
                    <span class="text-slate-500 font-medium">Mayor Comisionista:</span>
                    <span class="font-bold text-purple-700 truncate max-w-[130px]" title="{{ $B['topSeller']['name'] }}">
                        {{ $B['topSeller']['name'] }} 🌟
                    </span>
                </div>
                <div class="mt-2 text-[10px] text-slate-400 flex justify-between">
                    <span>Ventas líder B:</span>
                    <span class="font-bold">${{ number_format($B['topSeller']['sales'], 0) }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- INTERACTIVE CHART ROW 1: Superimposed Daily Commissions Curve --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
            <div>
                <h3 class="font-bold text-slate-800 text-lg flex items-center gap-2">
                    <svg class="w-5 h-5 text-[#ff7261]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                    Curva Comparativa Diaria de Comisiones (Día 1 al 31)
                </h3>
                <p class="text-xs text-slate-500">Superposición de las comisiones generadas día a día entre ambos meses</p>
            </div>
            <div class="flex items-center gap-4 text-xs font-bold">
                <span class="flex items-center gap-1.5 text-[#ff7261]">
                    <span class="w-3 h-3 rounded-full bg-[#ff7261]"></span> {{ ucfirst($labelA) }} (A)
                </span>
                <span class="flex items-center gap-1.5 text-[#a855f7]">
                    <span class="w-3 h-3 rounded-full bg-[#a855f7]"></span> {{ ucfirst($labelB) }} (B)
                </span>
            </div>
        </div>
        <div class="relative w-full" style="height: 320px;"
             x-data="{
                 chart: null,
                 initChart() {
                     if (typeof Chart === 'undefined') return;
                     if (this.chart) this.chart.destroy();
                     const data = @js($versusDaily);
                     if (!data || !data.length) return;
                     this.chart = new Chart(this.$refs.canvas, {
                         type: 'line',
                         data: {
                             labels: data.map(d => d.label),
                             datasets: [
                                 {
                                     label: '{{ ucfirst($labelA) }} (A)',
                                     data: data.map(d => d.commA),
                                     borderColor: 'rgba(255, 114, 97, 1)',
                                     backgroundColor: 'rgba(255, 114, 97, 0.1)',
                                     fill: true,
                                     tension: 0.35,
                                     pointRadius: 3,
                                     pointBackgroundColor: 'rgba(255, 114, 97, 1)',
                                 },
                                 {
                                     label: '{{ ucfirst($labelB) }} (B)',
                                     data: data.map(d => d.commB),
                                     borderColor: 'rgba(168, 85, 247, 1)',
                                     backgroundColor: 'rgba(168, 85, 247, 0.1)',
                                     fill: true,
                                     tension: 0.35,
                                     pointRadius: 4,
                                     pointBackgroundColor: 'rgba(168, 85, 247, 1)',
                                 }
                             ]
                         },
                         options: {
                             responsive: true,
                             maintainAspectRatio: false,
                             interaction: { mode: 'index', intersect: false },
                             plugins: {
                                 tooltip: {
                                     callbacks: { label: ctx => ctx.dataset.label + ': $' + Number(ctx.raw).toLocaleString('es-CO') }
                                 }
                             },
                             scales: {
                                 y: {
                                     beginAtZero: true,
                                     ticks: { callback: v => '$' + (v >= 1000000 ? (v/1000000).toFixed(1) + 'M' : (v >= 1000 ? (v/1000).toFixed(0) + 'k' : v)) }
                                 }
                             }
                         }
                     });
                 }
             }"
             x-init="$nextTick(() => initChart())"
             wire:key="comm-versus-daily-{{ $startDateA }}-{{ $startDateB }}">
            <canvas x-ref="canvas"></canvas>
        </div>
    </div>

    {{-- CHART ROW 2: Sellers Comparison & Categories Comparison --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Sellers Commissions Comparison --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h3 class="font-bold text-slate-800 text-base mb-3 flex items-center gap-2">
                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                Comisiones por Vendedor (Mes A vs Mes B)
            </h3>
            <div class="relative w-full" style="height: 280px;"
                 x-data="{
                     chart: null,
                     initChart() {
                         if (typeof Chart === 'undefined') return;
                         if (this.chart) this.chart.destroy();
                         const rawData = @js(array_slice($versusSellers, 0, 7));
                         if (!rawData || !rawData.length) return;
                         this.chart = new Chart(this.$refs.canvas, {
                             type: 'bar',
                             data: {
                                 labels: rawData.map(s => s.name),
                                 datasets: [
                                     {
                                         label: '{{ ucfirst($labelA) }}',
                                         data: rawData.map(s => s.commA),
                                         backgroundColor: 'rgba(255, 114, 97, 0.85)',
                                         borderRadius: 6,
                                     },
                                     {
                                         label: '{{ ucfirst($labelB) }}',
                                         data: rawData.map(s => s.commB),
                                         backgroundColor: 'rgba(168, 85, 247, 0.85)',
                                         borderRadius: 6,
                                     }
                                 ]
                             },
                             options: {
                                 responsive: true,
                                 maintainAspectRatio: false,
                                 plugins: {
                                     tooltip: { callbacks: { label: ctx => ctx.dataset.label + ': $' + Number(ctx.raw).toLocaleString('es-CO') } }
                                 },
                                 scales: {
                                     y: {
                                         beginAtZero: true,
                                         ticks: { callback: v => '$' + (v >= 1000000 ? (v/1000000).toFixed(1) + 'M' : (v >= 1000 ? (v/1000).toFixed(0) + 'k' : v)) }
                                     }
                                 }
                             }
                         });
                     }
                 }"
                 x-init="$nextTick(() => initChart())"
                 wire:key="comm-versus-sellers-{{ $startDateA }}-{{ $startDateB }}">
                <canvas x-ref="canvas"></canvas>
            </div>
        </div>

        {{-- Categories Comparison Chart --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h3 class="font-bold text-slate-800 text-base mb-3 flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                Comisiones por Categoría (Mes A vs Mes B)
            </h3>
            <div class="relative w-full" style="height: 280px;"
                 x-data="{
                     chart: null,
                     initChart() {
                         if (typeof Chart === 'undefined') return;
                         if (this.chart) this.chart.destroy();
                         const rawData = @js(array_slice($versusCategories, 0, 7));
                         if (!rawData || !rawData.length) return;
                         this.chart = new Chart(this.$refs.canvas, {
                             type: 'bar',
                             data: {
                                 labels: rawData.map(c => c.name),
                                 datasets: [
                                     {
                                         label: '{{ ucfirst($labelA) }}',
                                         data: rawData.map(c => c.commA),
                                         backgroundColor: 'rgba(255, 114, 97, 0.85)',
                                         borderRadius: 6,
                                     },
                                     {
                                         label: '{{ ucfirst($labelB) }}',
                                         data: rawData.map(c => c.commB),
                                         backgroundColor: 'rgba(16, 185, 129, 0.85)',
                                         borderRadius: 6,
                                     }
                                 ]
                             },
                             options: {
                                 responsive: true,
                                 maintainAspectRatio: false,
                                 plugins: {
                                     tooltip: { callbacks: { label: ctx => ctx.dataset.label + ': $' + Number(ctx.raw).toLocaleString('es-CO') } }
                                 },
                                 scales: {
                                     y: {
                                         beginAtZero: true,
                                         ticks: { callback: v => '$' + (v >= 1000000 ? (v/1000000).toFixed(1) + 'M' : (v >= 1000 ? (v/1000).toFixed(0) + 'k' : v)) }
                                     }
                                 }
                             }
                         });
                     }
                 }"
                 x-init="$nextTick(() => initChart())"
                 wire:key="comm-versus-cat-{{ $startDateA }}-{{ $startDateB }}">
                <canvas x-ref="canvas"></canvas>
            </div>
        </div>
    </div>

    {{-- SIDE-BY-SIDE SELLERS COMPARISON TABLE --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <h3 class="font-bold text-slate-800 text-base flex items-center gap-2">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    Comparativa Completa de Comisiones por Vendedor (Lado a Lado)
                </h3>
                <p class="text-xs text-slate-500">Desglose de ventas comisionables, comisiones totales, deltas y porcentaje de crecimiento</p>
            </div>
            <div class="flex items-center gap-3 text-xs">
                <span class="px-2.5 py-1 rounded-lg bg-orange-50 text-[#ff7261] font-bold border border-[#ff7261]/30">Período A: {{ ucfirst($labelA) }}</span>
                <span class="px-2.5 py-1 rounded-lg bg-purple-50 text-[#a855f7] font-bold border border-[#a855f7]/30">Período B: {{ ucfirst($labelB) }}</span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-xs">
                <thead class="bg-slate-50 text-slate-600 font-bold uppercase">
                    <tr>
                        <th class="px-4 py-3 text-left">Vendedor</th>
                        <th class="px-3 py-3 text-right bg-orange-50/50 text-[#ff7261]">Ventas {{ ucfirst($labelA) }}</th>
                        <th class="px-3 py-3 text-right bg-orange-50/50 text-[#ff7261]">Comisión {{ ucfirst($labelA) }}</th>
                        <th class="px-3 py-3 text-right bg-purple-50/50 text-[#7c3aed]">Ventas {{ ucfirst($labelB) }}</th>
                        <th class="px-3 py-3 text-right bg-purple-50/50 text-[#7c3aed]">Comisión {{ ucfirst($labelB) }}</th>
                        <th class="px-3 py-3 text-right">Variación Comis. ($)</th>
                        <th class="px-3 py-3 text-right">Crecimiento (%)</th>
                        <th class="px-3 py-3 text-center">Tendencia</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($versusSellers as $s)
                    <tr class="hover:bg-slate-50/60">
                        <td class="px-4 py-2.5 font-bold text-slate-800">
                            {{ $s['name'] }}
                            <span class="text-[10px] text-slate-400 block">{{ $s['itemsB'] }} items (B) vs {{ $s['itemsA'] }} (A)</span>
                        </td>
                        <td class="px-3 py-2.5 text-right font-medium text-slate-600 bg-orange-50/20">${{ number_format($s['salesA'], 0) }}</td>
                        <td class="px-3 py-2.5 text-right font-bold text-slate-800 bg-orange-50/20">${{ number_format($s['commA'], 0) }}</td>
                        <td class="px-3 py-2.5 text-right font-medium text-slate-600 bg-purple-50/20">${{ number_format($s['salesB'], 0) }}</td>
                        <td class="px-3 py-2.5 text-right font-bold text-purple-700 bg-purple-50/20">${{ number_format($s['commB'], 0) }}</td>
                        <td class="px-3 py-2.5 text-right font-black {{ $s['diffComm'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                            {{ $s['diffComm'] >= 0 ? '+' : '' }}${{ number_format($s['diffComm'], 0) }}
                        </td>
                        <td class="px-3 py-2.5 text-right font-black {{ $s['growthComm'] >= 0 ? 'text-emerald-700' : 'text-red-700' }}">
                            {{ $s['growthComm'] >= 0 ? '+' : '' }}{{ $s['growthComm'] }}%
                        </td>
                        <td class="px-3 py-2.5 text-center">
                            @if($s['diffComm'] > 0)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700">
                                ▲ Sube
                            </span>
                            @elseif($s['diffComm'] < 0)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-700">
                                ▼ Baja
                            </span>
                            @else
                            <span class="text-slate-400 text-xs">— Igual</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="px-4 py-8 text-center text-slate-400">Sin datos de comisiones de vendedores</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- TOP PRODUCTS COMPARISON TABLE --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 bg-slate-50 border-b border-slate-200">
            <h3 class="font-bold text-slate-800 text-sm flex items-center gap-2">
                <svg class="w-4 h-4 text-[#ff7261]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                Top Productos / Servicios con Mayor Comisión Generada
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-xs">
                <thead class="bg-slate-50 text-slate-600 font-bold uppercase">
                    <tr>
                        <th class="px-4 py-2.5 text-left">Producto / Servicio</th>
                        <th class="px-3 py-2.5 text-right bg-orange-50/40 text-[#ff7261]">Comis. {{ ucfirst($labelA) }}</th>
                        <th class="px-3 py-2.5 text-right bg-purple-50/40 text-[#7c3aed]">Comis. {{ ucfirst($labelB) }}</th>
                        <th class="px-3 py-2.5 text-right">Variación ($)</th>
                        <th class="px-3 py-2.5 text-right">% Crecimiento</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($versusProducts as $p)
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-4 py-2.5 font-medium text-slate-800">
                            {{ $p['name'] }}
                            @if($p['sku'])<span class="text-[10px] text-slate-400 block">{{ $p['sku'] }}</span>@endif
                        </td>
                        <td class="px-3 py-2.5 text-right text-slate-600 bg-orange-50/20">${{ number_format($p['commA'], 0) }}</td>
                        <td class="px-3 py-2.5 text-right font-bold text-slate-900 bg-purple-50/20">${{ number_format($p['commB'], 0) }}</td>
                        <td class="px-3 py-2.5 text-right font-bold {{ $p['diffComm'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                            {{ $p['diffComm'] >= 0 ? '+' : '' }}${{ number_format($p['diffComm'], 0) }}
                        </td>
                        <td class="px-3 py-2.5 text-right font-bold {{ $p['growthComm'] >= 0 ? 'text-emerald-700' : 'text-red-700' }}">
                            {{ $p['growthComm'] >= 0 ? '+' : '' }}{{ $p['growthComm'] }}%
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-slate-400">Sin datos de productos comisionables</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- ========================================================================= --}}
    {{-- STANDARD VIEW CONTENT --}}
    {{-- ========================================================================= --}}
    @if($viewMode === 'standard')
    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        {{-- Total Commissions --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-purple-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-[#a855f7]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <span class="text-sm text-slate-500 font-medium">Total Comisiones</span>
            </div>
            <p class="text-2xl font-bold text-[#a855f7]">${{ number_format($totalCommissions, 0, ',', '.') }}</p>
            <p class="text-xs text-slate-400 mt-1">Generadas en el período</p>
        </div>

        {{-- Total Sales with Commission --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                </div>
                <span class="text-sm text-slate-500 font-medium">Ventas Comisionables</span>
            </div>
            <p class="text-2xl font-bold text-slate-800">${{ number_format($totalSales, 0, ',', '.') }}</p>
            <p class="text-xs text-slate-400 mt-1">{{ $totalTransactions }} ventas registradas</p>
        </div>

        {{-- Items Sold --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-green-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                </div>
                <span class="text-sm text-slate-500 font-medium">Items Vendidos</span>
            </div>
            <p class="text-2xl font-bold text-slate-800">{{ number_format($totalItemsSold) }}</p>
            <p class="text-xs text-slate-400 mt-1">Con comisión asignada</p>
        </div>

        {{-- Average Commission Rate --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-orange-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-[#ff7261]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                </div>
                <span class="text-sm text-slate-500 font-medium">Tasa Promedio</span>
            </div>
            <p class="text-2xl font-bold text-slate-800">{{ number_format($averageCommissionRate, 1) }}%</p>
            <p class="text-xs text-slate-400 mt-1">Sobre ventas totales</p>
        </div>

        {{-- Active Sellers --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                </div>
                <span class="text-sm text-slate-500 font-medium">Vendedores</span>
            </div>
            <p class="text-2xl font-bold text-slate-800">{{ count($commissionsByUser) }}</p>
            <p class="text-xs text-slate-400 mt-1">Con comisiones activas</p>
        </div>
    </div>

    {{-- Sellers List Table (Standard Mode) --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center">
            <h3 class="font-bold text-slate-800 text-sm flex items-center gap-2">
                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                Comisiones por Vendedor
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-xs">
                <thead class="bg-slate-50 text-slate-500 font-semibold uppercase">
                    <tr>
                        <th class="px-4 py-3 text-left">Vendedor</th>
                        <th class="px-4 py-3 text-right">Ventas Totales</th>
                        <th class="px-4 py-3 text-right">Items</th>
                        <th class="px-4 py-3 text-right">Comisión Total</th>
                        <th class="px-4 py-3 text-right">% Efectivo</th>
                        <th class="px-4 py-3 text-center">Detalle</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($commissionsByUser as $userComm)
                    <tr class="hover:bg-slate-50/60">
                        <td class="px-4 py-3 font-bold text-slate-800">{{ $userComm['user_name'] }}</td>
                        <td class="px-4 py-3 text-right text-slate-600">${{ number_format($userComm['sales'], 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right text-slate-600">{{ number_format($userComm['items']) }}</td>
                        <td class="px-4 py-3 text-right font-black text-[#a855f7]">${{ number_format($userComm['commission'], 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-slate-700">
                            {{ $userComm['sales'] > 0 ? number_format(($userComm['commission'] / $userComm['sales']) * 100, 1) : 0 }}%
                        </td>
                        <td class="px-4 py-3 text-center">
                            <button wire:click="toggleUserDetail({{ $userComm['user_id'] }})" class="p-1.5 rounded-lg bg-slate-100 hover:bg-[#a855f7] hover:text-white text-slate-600 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                            </button>
                        </td>
                    </tr>
                    @if($expandedUserId === $userComm['user_id'] && !empty($userSalesDetail))
                    <tr>
                        <td colspan="6" class="px-6 py-4 bg-slate-50 border-y border-slate-200">
                            <div class="text-xs font-bold text-slate-700 mb-2">Desglose de Ventas de {{ $userComm['user_name'] }}:</div>
                            <div class="max-h-60 overflow-y-auto rounded-xl border border-slate-200 bg-white">
                                <table class="min-w-full divide-y divide-slate-100 text-[11px]">
                                    <thead class="bg-slate-100 text-slate-600 font-semibold">
                                        <tr>
                                            <th class="px-3 py-2 text-left">Factura</th>
                                            <th class="px-3 py-2 text-left">Fecha</th>
                                            <th class="px-3 py-2 text-left">Producto / Servicio</th>
                                            <th class="px-3 py-2 text-right">Cant.</th>
                                            <th class="px-3 py-2 text-right">Total</th>
                                            <th class="px-3 py-2 text-right">Comisión</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach($userSalesDetail as $det)
                                        <tr>
                                            <td class="px-3 py-1.5 font-bold">{{ $det['invoice_number'] }}</td>
                                            <td class="px-3 py-1.5 text-slate-500">{{ $det['date'] }}</td>
                                            <td class="px-3 py-1.5">{{ $det['product_name'] }}</td>
                                            <td class="px-3 py-1.5 text-right">{{ $det['quantity'] }}</td>
                                            <td class="px-3 py-1.5 text-right">${{ number_format($det['total'], 0, ',', '.') }}</td>
                                            <td class="px-3 py-1.5 text-right font-bold text-[#a855f7]">${{ number_format($det['commission'], 0, ',', '.') }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </td>
                    </tr>
                    @endif
                    @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">No se encontraron comisiones para este período</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>

<div class="space-y-6">
    <x-toast />

    {{-- Top Header with Title and Mode Switcher --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#ff7261] to-[#a855f7] flex items-center justify-center text-white shadow-md shadow-[#ff7261]/20">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
            </div>
            <div>
                <h1 class="text-2xl font-black text-slate-800 tracking-tight flex items-center gap-2">
                    Pérdidas y Ganancias
                    @if($viewMode === 'versus')
                    <span class="inline-flex items-center gap-1 text-xs font-bold px-2.5 py-1 rounded-full bg-gradient-to-r from-[#ff7261] to-[#a855f7] text-white shadow-sm">
                        ⚔️ MODO VERSUS
                    </span>
                    @endif
                </h1>
                <p class="text-xs sm:text-sm text-slate-500">
                    {{ $viewMode === 'versus' ? 'Comparativa financiera interactiva mes contra mes (MoM / YoY)' : 'Análisis financiero detallado del negocio' }}
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

            @if(auth()->user()->hasPermission('reports.export'))
            <button wire:click="exportExcel" wire:loading.attr="disabled" wire:target="exportExcel"
                class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-xl shadow-sm hover:shadow transition-all duration-200 disabled:opacity-50 gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                <span wire:loading.remove wire:target="exportExcel">{{ $viewMode === 'versus' ? 'Excel Comparativo' : 'Exportar Excel' }}</span>
                <span wire:loading wire:target="exportExcel">Exportando...</span>
            </button>
            @endif
        </div>
    </div>

    {{-- FILTERS SECTION --}}
    @if($viewMode === 'standard')
    {{-- Standard Filters --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
        <div class="flex flex-col sm:flex-row gap-3 items-end">
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Período</label>
                <select wire:model.live="dateRange" class="px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] sm:text-sm font-medium text-slate-700">
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
            @if($dateRange === 'custom')
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Desde</label>
                <input wire:model.live="startDate" type="date" class="px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] sm:text-sm font-medium text-slate-700">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Hasta</label>
                <input wire:model.live="endDate" type="date" class="px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] sm:text-sm font-medium text-slate-700">
            </div>
            @endif
            @if($isSuperAdmin)
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Sucursal</label>
                <select wire:model.live="selectedBranchId" class="px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] sm:text-sm min-w-[160px] font-medium text-slate-700">
                    <option value="">Todas las sucursales</option>
                    @foreach($branches as $branch)
                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <button wire:click="clearFilters" class="px-3 py-2.5 text-slate-500 hover:text-slate-700 hover:bg-slate-100 rounded-xl transition-colors text-sm font-medium flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                Limpiar
            </button>
        </div>
    </div>
    @else
    {{-- Versus Filters (Light System Design) --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            {{-- Quick Presets --}}
            <div class="space-y-1.5">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Comparación Rápida</span>
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
                <div class="bg-orange-50/70 rounded-xl p-2.5 border border-[#ff7261]/30 flex items-center gap-2.5">
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
                <div class="bg-purple-50/70 rounded-xl p-2.5 border border-[#a855f7]/30 flex items-center gap-2.5">
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

        $incRealA = $A['totalRevenue'] + $A['totalCashIncome'];
        $incRealB = $B['totalRevenue'] + $B['totalCashIncome'];
        $diffIncome = $incRealB - $incRealA;
        $growthIncome = $incRealA > 0 ? (($diffIncome) / $incRealA) * 100 : ($incRealB > 0 ? 100 : 0);

        $diffNet = $B['netProfit'] - $A['netProfit'];
        $growthNet = $A['netProfit'] != 0 ? (($diffNet) / abs($A['netProfit'])) * 100 : ($B['netProfit'] > 0 ? 100 : 0);

        $diffGross = $B['grossProfit'] - $A['grossProfit'];
        $growthGross = $A['grossProfit'] != 0 ? (($diffGross) / abs($A['grossProfit'])) * 100 : ($B['grossProfit'] > 0 ? 100 : 0);

        $diffCost = $B['totalCost'] - $A['totalCost'];
        $growthCost = $A['totalCost'] > 0 ? (($diffCost) / $A['totalCost']) * 100 : 0;
    @endphp

    {{-- VERSUS BATTLE CARDS (Light Theme) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Card 1: Ingresos Reales --}}
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200 hover:shadow-md transition-all relative overflow-hidden">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Ingresos Reales</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold {{ $growthIncome >= 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                    {{ $growthIncome >= 0 ? '▲ +' : '▼ ' }}{{ number_format($growthIncome, 1) }}%
                </span>
            </div>
            <div class="flex items-baseline justify-between">
                <div>
                    <span class="text-xs font-semibold text-[#ff7261]">A: ${{ number_format($incRealA, 0) }}</span>
                    <div class="text-2xl font-black text-slate-800">${{ number_format($incRealB, 0) }}</div>
                    <span class="text-[11px] font-semibold text-[#a855f7]">B: {{ ucfirst($labelB) }}</span>
                </div>
                <div class="text-right">
                    <span class="text-[11px] font-medium text-slate-400 block">Diferencia</span>
                    <span class="text-sm font-black {{ $diffIncome >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                        {{ $diffIncome >= 0 ? '+' : '' }}${{ number_format($diffIncome, 0) }}
                    </span>
                </div>
            </div>
            {{-- Victory Mini Bar --}}
            <div class="mt-3 pt-2 border-t border-slate-100">
                <div class="flex justify-between text-[10px] text-slate-400 font-semibold mb-1">
                    <span>A: {{ number_format($incRealA + $incRealB > 0 ? ($incRealA / ($incRealA + $incRealB)) * 100 : 50, 0) }}%</span>
                    <span>B: {{ number_format($incRealA + $incRealB > 0 ? ($incRealB / ($incRealA + $incRealB)) * 100 : 50, 0) }}%</span>
                </div>
                <div class="h-1.5 w-full bg-slate-100 rounded-full flex overflow-hidden">
                    <div class="bg-[#ff7261] h-full" style="width: {{ $incRealA + $incRealB > 0 ? ($incRealA / ($incRealA + $incRealB)) * 100 : 50 }}%"></div>
                    <div class="bg-[#a855f7] h-full" style="width: {{ $incRealA + $incRealB > 0 ? ($incRealB / ($incRealA + $incRealB)) * 100 : 50 }}%"></div>
                </div>
            </div>
        </div>

        {{-- Card 2: Costo de Ventas --}}
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200 hover:shadow-md transition-all relative overflow-hidden">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Costo de Ventas</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold {{ $growthCost <= 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                    {{ $growthCost >= 0 ? '▲ +' : '▼ ' }}{{ number_format($growthCost, 1) }}%
                </span>
            </div>
            <div class="flex items-baseline justify-between">
                <div>
                    <span class="text-xs font-semibold text-[#ff7261]">A: ${{ number_format($A['totalCost'], 0) }}</span>
                    <div class="text-2xl font-black text-slate-800">${{ number_format($B['totalCost'], 0) }}</div>
                    <span class="text-[11px] font-semibold text-[#a855f7]">B: {{ ucfirst($labelB) }}</span>
                </div>
                <div class="text-right">
                    <span class="text-[11px] font-medium text-slate-400 block">Diferencia</span>
                    <span class="text-sm font-black {{ $diffCost <= 0 ? 'text-emerald-600' : 'text-slate-700' }}">
                        {{ $diffCost >= 0 ? '+' : '' }}${{ number_format($diffCost, 0) }}
                    </span>
                </div>
            </div>
            <div class="mt-3 pt-2 border-t border-slate-100 text-[11px] text-slate-500 flex justify-between">
                <span>Costo % sobre ingreso:</span>
                <span class="font-bold">{{ number_format($incRealB > 0 ? ($B['totalCost'] / $incRealB) * 100 : 0, 1) }}% (B) vs {{ number_format($incRealA > 0 ? ($A['totalCost'] / $incRealA) * 100 : 0, 1) }}% (A)</span>
            </div>
        </div>

        {{-- Card 3: Utilidad Bruta --}}
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200 hover:shadow-md transition-all relative overflow-hidden">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Utilidad Bruta</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold {{ $growthGross >= 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                    {{ $growthGross >= 0 ? '▲ +' : '▼ ' }}{{ number_format($growthGross, 1) }}%
                </span>
            </div>
            <div class="flex items-baseline justify-between">
                <div>
                    <span class="text-xs font-semibold text-[#ff7261]">A: ${{ number_format($A['grossProfit'], 0) }} ({{ number_format($A['grossMargin'], 1) }}%)</span>
                    <div class="text-2xl font-black text-slate-800">${{ number_format($B['grossProfit'], 0) }}</div>
                    <span class="text-[11px] font-semibold text-[#a855f7]">B: Margen {{ number_format($B['grossMargin'], 1) }}%</span>
                </div>
                <div class="text-right">
                    <span class="text-[11px] font-medium text-slate-400 block">Diferencia</span>
                    <span class="text-sm font-black {{ $diffGross >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                        {{ $diffGross >= 0 ? '+' : '' }}${{ number_format($diffGross, 0) }}
                    </span>
                </div>
            </div>
            <div class="mt-3 pt-2 border-t border-slate-100 text-[11px] text-slate-500 flex justify-between">
                <span>Variación Margen:</span>
                <span class="font-bold {{ $B['grossMargin'] >= $A['grossMargin'] ? 'text-emerald-600' : 'text-red-600' }}">
                    {{ $B['grossMargin'] >= $A['grossMargin'] ? '+' : '' }}{{ number_format($B['grossMargin'] - $A['grossMargin'], 1) }} pts
                </span>
            </div>
        </div>

        {{-- Card 4: UTILIDAD NETA (Light System Theme) --}}
        <div class="bg-white rounded-2xl p-5 shadow-sm border-2 {{ $B['netProfit'] >= $A['netProfit'] ? 'border-emerald-300' : 'border-[#a855f7]/40' }} hover:shadow-md transition-all relative overflow-hidden">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-black uppercase tracking-wider text-slate-700 flex items-center gap-1">
                    🏆 UTILIDAD NETA
                </span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold {{ $diffNet >= 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                    {{ $growthNet >= 0 ? '▲ +' : '▼ ' }}{{ number_format($growthNet, 1) }}%
                </span>
            </div>
            <div class="flex items-baseline justify-between">
                <div>
                    <span class="text-xs font-semibold text-[#ff7261]">A: ${{ number_format($A['netProfit'], 0) }}</span>
                    <div class="text-2xl font-black {{ $B['netProfit'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">${{ number_format($B['netProfit'], 0) }}</div>
                    <span class="text-[11px] font-semibold text-[#a855f7]">B: Margen Neto {{ number_format($B['netMargin'], 1) }}%</span>
                </div>
                <div class="text-right">
                    <span class="text-[10px] uppercase font-bold text-slate-400 block">Delta Neto</span>
                    <span class="text-sm font-black {{ $diffNet >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                        {{ $diffNet >= 0 ? '+' : '' }}${{ number_format($diffNet, 0) }}
                    </span>
                </div>
            </div>
            <div class="mt-3 pt-2 border-t border-slate-100 text-[11px] flex justify-between items-center">
                <span class="text-slate-500 font-medium">Mes Ganador:</span>
                <span class="font-bold px-2 py-0.5 rounded-md {{ $B['netProfit'] >= $A['netProfit'] ? 'bg-emerald-50 text-emerald-700' : 'bg-purple-50 text-purple-700' }}">
                    {{ $B['netProfit'] >= $A['netProfit'] ? ucfirst($labelB) . ' 🏆' : ucfirst($labelA) . ' 🏆' }}
                </span>
            </div>
        </div>
    </div>

    {{-- Secondary Metrics Row --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm">
            <span class="text-xs font-semibold text-slate-400 block">Total Egresos (Gastos + Nómina)</span>
            <p class="text-lg font-bold text-red-600 mt-1">${{ number_format($B['totalExpenses'] + $B['totalPayrollExpenses'], 0) }}</p>
            <span class="text-xs text-slate-500">vs ${{ number_format($A['totalExpenses'] + $A['totalPayrollExpenses'], 0) }} (A)</span>
        </div>
        <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm">
            <span class="text-xs font-semibold text-slate-400 block">Transacciones / Ventas</span>
            <p class="text-lg font-bold text-blue-600 mt-1">{{ number_format($B['totalTransactions']) }}</p>
            <span class="text-xs text-slate-500">vs {{ number_format($A['totalTransactions']) }} (A) [{{ $B['totalTransactions'] - $A['totalTransactions'] >= 0 ? '+' : '' }}{{ $B['totalTransactions'] - $A['totalTransactions'] }}]</span>
        </div>
        <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm">
            <span class="text-xs font-semibold text-slate-400 block">Ticket Promedio</span>
            <p class="text-lg font-bold text-purple-600 mt-1">${{ number_format($B['totalTransactions'] > 0 ? $B['totalRevenue'] / $B['totalTransactions'] : 0, 0) }}</p>
            <span class="text-xs text-slate-500">vs ${{ number_format($A['totalTransactions'] > 0 ? $A['totalRevenue'] / $A['totalTransactions'] : 0, 0) }} (A)</span>
        </div>
        <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm">
            <span class="text-xs font-semibold text-slate-400 block">Devoluciones & NC</span>
            <p class="text-lg font-bold text-orange-600 mt-1">${{ number_format($B['totalRefunds'], 0) }}</p>
            <span class="text-xs text-slate-500">vs ${{ number_format($A['totalRefunds'], 0) }} (A)</span>
        </div>
    </div>

    {{-- INTERACTIVE CHART ROW 1: Superimposed Daily Trend Curve (Day 1..31) --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
            <div>
                <h3 class="font-bold text-slate-800 text-lg flex items-center gap-2">
                    <svg class="w-5 h-5 text-[#ff7261]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                    Curva Comparativa Diaria: Ventas vs Ventas (Día 1 al 31)
                </h3>
                <p class="text-xs text-slate-500">Superposición del ritmo de ventas diario entre ambos períodos</p>
            </div>
            <div class="flex items-center gap-4 text-xs font-bold">
                <span class="flex items-center gap-1.5 text-[#ff7261]">
                    <span class="w-3 h-3 rounded-full bg-[#ff7261]"></span> Período A ({{ ucfirst($labelA) }})
                </span>
                <span class="flex items-center gap-1.5 text-[#a855f7]">
                    <span class="w-3 h-3 rounded-full bg-[#a855f7]"></span> Período B ({{ ucfirst($labelB) }})
                </span>
            </div>
        </div>
        <div class="relative w-full" style="height: 320px;">
            <canvas id="versusDailyCurveChart"></canvas>
        </div>
    </div>

    {{-- CHART ROW 2: Categories Comparison & Payment Methods --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Categories Comparison Chart --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h3 class="font-bold text-slate-800 text-base mb-3 flex items-center gap-2">
                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                Ventas por Categoría (Mes A vs Mes B)
            </h3>
            <div class="relative w-full" style="height: 280px;">
                <canvas id="versusCategoriesChart"></canvas>
            </div>
        </div>

        {{-- Payment Methods Comparison Chart --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h3 class="font-bold text-slate-800 text-base mb-3 flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                Distribución por Métodos de Pago
            </h3>
            <div class="relative w-full" style="height: 280px;">
                <canvas id="versusPaymentMethodsChart"></canvas>
            </div>
        </div>
    </div>

    {{-- SIDE-BY-SIDE P&L STATEMENT (TABLA FORMAL DE ESTADO DE RESULTADOS - LIGHT THEME) --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <h3 class="font-bold text-slate-800 text-base flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Estado Financiero de Pérdidas y Ganancias (Comparativo Lado a Lado)
                </h3>
                <p class="text-xs text-slate-500">Análisis discriminado con porcentajes de participación y variaciones</p>
            </div>
            <div class="flex items-center gap-3 text-xs">
                <span class="px-2.5 py-1 rounded-lg bg-orange-50 text-[#ff7261] font-bold border border-[#ff7261]/30">Período A: {{ ucfirst($labelA) }}</span>
                <span class="px-2.5 py-1 rounded-lg bg-purple-50 text-[#a855f7] font-bold border border-[#a855f7]/30">Período B: {{ ucfirst($labelB) }}</span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-slate-600 font-bold text-xs uppercase">
                    <tr>
                        <th class="px-4 py-3 text-left">Rubro / Concepto</th>
                        <th class="px-4 py-3 text-right bg-orange-50/50 text-[#ff7261]">{{ ucfirst($labelA) }} ($)</th>
                        <th class="px-3 py-3 text-right bg-orange-50/50 text-slate-500">% Ing.</th>
                        <th class="px-4 py-3 text-right bg-purple-50/50 text-[#7c3aed]">{{ ucfirst($labelB) }} ($)</th>
                        <th class="px-3 py-3 text-right bg-purple-50/50 text-slate-500">% Ing.</th>
                        <th class="px-4 py-3 text-right">Variación ($)</th>
                        <th class="px-4 py-3 text-right">Crecimiento (%)</th>
                        <th class="px-4 py-3 text-center">Tendencia</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @php
                        $rows = [
                            ['Ingresos por Ventas (Brutos)', $A['rawRevenue'], $B['rawRevenue'], false, true],
                            ['(+) Otros Ingresos (Caja)', $A['totalCashIncome'], $B['totalCashIncome'], false, true],
                            ['(-) Devoluciones y Notas Crédito', $A['totalRefunds'], $B['totalRefunds'], false, false],
                            ['(=) INGRESOS REALES', $incRealA, $incRealB, true, true],
                            ['(-) Descuentos Otorgados', $A['totalDiscount'], $B['totalDiscount'], false, false],
                            ['    Impuestos Recaudados (IVA/ICO)', $A['totalTax'], $B['totalTax'], false, true],
                            ['(-) Costo de Ventas (COGS)', $A['totalCost'], $B['totalCost'], false, false],
                            ['(=) UTILIDAD BRUTA', $A['grossProfit'], $B['grossProfit'], true, true],
                            ['(-) Gastos de Caja (Egresos)', $A['totalCashExpenses'], $B['totalCashExpenses'], false, false],
                            ['(-) Gastos Registrados en Módulo', $A['totalModuleExpenses'], $B['totalModuleExpenses'], false, false],
                            ['(-) Total Gastos Operativos', $A['totalExpenses'], $B['totalExpenses'], false, false],
                            ['(-) Nómina Pagada', $A['totalPayrollExpenses'], $B['totalPayrollExpenses'], false, false],
                            ['(=) UTILIDAD NETA DEL EJERCICIO', $A['netProfit'], $B['netProfit'], true, true],
                        ];
                    @endphp

                    @foreach($rows as $r)
                    @php
                        $name = $r[0];
                        $valA = $r[1];
                        $valB = $r[2];
                        $isHeader = $r[3];
                        $higherGood = $r[4];

                        $pctA = $incRealA > 0 ? ($valA / $incRealA) * 100 : 0;
                        $pctB = $incRealB > 0 ? ($valB / $incRealB) * 100 : 0;
                        $diffVal = $valB - $valA;
                        $growthVal = $valA != 0 ? (($diffVal) / abs($valA)) * 100 : ($valB > 0 ? 100 : 0);
                    @endphp
                    <tr class="{{ $isHeader ? ($name === '(=) UTILIDAD NETA DEL EJERCICIO' ? 'bg-emerald-50 font-black text-slate-900' : 'bg-slate-50 font-bold text-slate-800') : 'hover:bg-slate-50/70 text-slate-600' }}">
                        <td class="px-4 py-2.5 {{ $isHeader ? 'font-bold' : (str_starts_with($name, ' ') ? 'pl-8 text-xs text-slate-500' : '') }}">
                            {{ $name }}
                        </td>
                        <td class="px-4 py-2.5 text-right font-medium text-slate-700 bg-orange-50/20">
                            ${{ number_format($valA, 2) }}
                        </td>
                        <td class="px-3 py-2.5 text-right text-xs text-slate-400 bg-orange-50/20">
                            {{ number_format($pctA, 1) }}%
                        </td>
                        <td class="px-4 py-2.5 text-right font-bold text-slate-900 bg-purple-50/20">
                            ${{ number_format($valB, 2) }}
                        </td>
                        <td class="px-3 py-2.5 text-right text-xs text-slate-400 bg-purple-50/20">
                            {{ number_format($pctB, 1) }}%
                        </td>
                        <td class="px-4 py-2.5 text-right font-bold {{ $diffVal >= 0 ? ($higherGood ? 'text-emerald-600' : 'text-red-600') : ($higherGood ? 'text-red-600' : 'text-emerald-600') }}">
                            {{ $diffVal >= 0 ? '+' : '' }}${{ number_format($diffVal, 2) }}
                        </td>
                        <td class="px-4 py-2.5 text-right font-bold {{ $growthVal >= 0 ? ($higherGood ? 'text-emerald-600' : 'text-red-600') : ($higherGood ? 'text-red-600' : 'text-emerald-600') }}">
                            {{ $growthVal >= 0 ? '+' : '' }}{{ number_format($growthVal, 1) }}%
                        </td>
                        <td class="px-4 py-2.5 text-center">
                            @if($diffVal > 0)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold {{ $higherGood ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                ▲ Sube
                            </span>
                            @elseif($diffVal < 0)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold {{ $higherGood ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700' }}">
                                ▼ Baja
                            </span>
                            @else
                            <span class="text-xs text-slate-400">— Igual</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- TOP PRODUCTS SHIFTS --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Top Gaining Products --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="font-bold text-slate-800 flex items-center gap-2 text-sm">
                    <span class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center font-black text-xs">🚀</span>
                    Productos con Mayor Crecimiento en Utilidad
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-xs">
                    <thead class="bg-slate-50 text-slate-500 font-semibold">
                        <tr>
                            <th class="px-4 py-2 text-left">Producto</th>
                            <th class="px-3 py-2 text-right">Util. A</th>
                            <th class="px-3 py-2 text-right">Util. B</th>
                            <th class="px-3 py-2 text-right">Ganancia (+)</th>
                            <th class="px-3 py-2 text-right">%</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($versusTopGrowthProducts as $item)
                        <tr class="hover:bg-emerald-50/30">
                            <td class="px-4 py-2 font-medium text-slate-800 truncate max-w-[160px]">
                                {{ $item['name'] }}
                                <span class="text-[10px] text-slate-400 block">{{ $item['sku'] }}</span>
                            </td>
                            <td class="px-3 py-2 text-right text-slate-500">${{ number_format($item['profitA'], 0) }}</td>
                            <td class="px-3 py-2 text-right font-bold text-slate-800">${{ number_format($item['profitB'], 0) }}</td>
                            <td class="px-3 py-2 text-right font-bold text-emerald-600">+${{ number_format($item['diffProfit'], 0) }}</td>
                            <td class="px-3 py-2 text-right font-bold text-emerald-700">+{{ $item['growth'] }}%</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="px-4 py-6 text-center text-slate-400">Sin datos</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Top Dropping Products --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="font-bold text-slate-800 flex items-center gap-2 text-sm">
                    <span class="w-6 h-6 rounded-lg bg-red-100 text-red-600 flex items-center justify-center font-black text-xs">⚠️</span>
                    Productos con Mayor Caída en Utilidad
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-xs">
                    <thead class="bg-slate-50 text-slate-500 font-semibold">
                        <tr>
                            <th class="px-4 py-2 text-left">Producto</th>
                            <th class="px-3 py-2 text-right">Util. A</th>
                            <th class="px-3 py-2 text-right">Util. B</th>
                            <th class="px-3 py-2 text-right">Caída (-)</th>
                            <th class="px-3 py-2 text-right">%</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($versusTopDropProducts as $item)
                        <tr class="hover:bg-red-50/30">
                            <td class="px-4 py-2 font-medium text-slate-800 truncate max-w-[160px]">
                                {{ $item['name'] }}
                                <span class="text-[10px] text-slate-400 block">{{ $item['sku'] }}</span>
                            </td>
                            <td class="px-3 py-2 text-right text-slate-500">${{ number_format($item['profitA'], 0) }}</td>
                            <td class="px-3 py-2 text-right font-bold text-slate-800">${{ number_format($item['profitB'], 0) }}</td>
                            <td class="px-3 py-2 text-right font-bold text-red-600">${{ number_format($item['diffProfit'], 0) }}</td>
                            <td class="px-3 py-2 text-right font-bold text-red-700">{{ $item['growth'] }}%</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="px-4 py-6 text-center text-slate-400">Sin datos</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- ========================================================================= --}}
    {{-- STANDARD VIEW CONTENT --}}
    {{-- ========================================================================= --}}
    @if($viewMode === 'standard')
    {{-- KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7 gap-4">
        {{-- Gross Revenue --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                </div>
                <span class="text-sm text-slate-500">Ingresos Brutos</span>
            </div>
            <p class="text-2xl font-bold text-slate-800">${{ number_format($rawRevenue + $totalCashIncome, 0, ',', '.') }}</p>
            <p class="text-xs text-slate-400 mt-1">{{ $totalTransactions }} ventas{{ $totalCashIncome > 0 ? ' + mov.' : '' }}</p>
        </div>

        {{-- Returns --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-orange-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path></svg>
                </div>
                <span class="text-sm text-slate-500">Devoluciones</span>
            </div>
            <p class="text-2xl font-bold text-orange-600">${{ number_format($totalRefunds, 0, ',', '.') }}</p>
            <p class="text-xs text-slate-400 mt-1">{{ $totalRefundsCount }} dev. + N. crédito</p>
        </div>

        {{-- Real Income (gross - returns) --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-teal-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <span class="text-sm text-slate-500">Ingresos Reales</span>
            </div>
            <p class="text-2xl font-bold text-teal-700">${{ number_format($totalRevenue + $totalCashIncome, 0, ',', '.') }}</p>
            <p class="text-xs text-slate-400 mt-1">Brutos - devoluciones</p>
        </div>

        {{-- Cost --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                </div>
                <span class="text-sm text-slate-500">Costo de Ventas</span>
            </div>
            <p class="text-2xl font-bold text-slate-800">${{ number_format($totalCost, 0, ',', '.') }}</p>
            <p class="text-xs text-slate-400 mt-1">Costo neto (sin devoluc.)</p>
        </div>

        {{-- Total Expenses --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-red-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
                <span class="text-sm text-slate-500">Gastos Operativos</span>
            </div>
            <p class="text-2xl font-bold text-red-600">${{ number_format($totalExpenses, 0, ',', '.') }}</p>
            <p class="text-xs text-slate-400 mt-1">Caja + gastos registrados</p>
        </div>

        {{-- Payroll --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-purple-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                </div>
                <span class="text-sm text-slate-500">Nómina</span>
            </div>
            <p class="text-2xl font-bold text-purple-600">${{ number_format($totalPayrollExpenses, 0, ',', '.') }}</p>
            <p class="text-xs text-slate-400 mt-1">Períodos pagados</p>
        </div>

        {{-- Net Profit --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 {{ $netProfit >= 0 ? 'ring-2 ring-emerald-200' : 'ring-2 ring-red-200' }}">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl {{ $netProfit >= 0 ? 'bg-emerald-100' : 'bg-red-100' }} flex items-center justify-center">
                    <svg class="w-5 h-5 {{ $netProfit >= 0 ? 'text-emerald-600' : 'text-red-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <span class="text-sm text-slate-500">Utilidad Neta</span>
            </div>
            <p class="text-2xl font-bold {{ $netProfit >= 0 ? 'text-emerald-600' : 'text-red-600' }}">${{ number_format($netProfit, 0, ',', '.') }}</p>
            <p class="text-xs {{ $netMargin >= 0 ? 'text-emerald-500' : 'text-red-500' }} mt-1">Margen neto: {{ number_format($netMargin, 1) }}%</p>
        </div>
    </div>

    {{-- P&G Statement Card --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-[#a855f7]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            Estado de Pérdidas y Ganancias
        </h3>
        <div class="space-y-2 text-sm">
            <div class="flex justify-between py-2 px-3 bg-blue-50 rounded-lg">
                <span class="font-medium text-blue-800">Ingresos por Ventas (Brutos)</span>
                <span class="font-bold text-blue-800">${{ number_format($rawRevenue, 2) }}</span>
            </div>
            @if($totalCashIncome > 0)
            <div class="flex justify-between py-2 px-3 bg-green-50 rounded-lg">
                <span class="font-medium text-green-800">(+) Otros Ingresos (Mov. Caja)</span>
                <span class="font-bold text-green-800">${{ number_format($totalCashIncome, 2) }}</span>
            </div>
            @endif
            @if($totalRefunds > 0)
            <div class="flex justify-between py-2 px-3 bg-orange-50 rounded-lg">
                <span class="font-medium text-orange-800">(-) Devoluciones y Notas Crédito</span>
                <span class="font-bold text-orange-800">-${{ number_format($totalRefunds, 2) }}</span>
            </div>
            <div class="flex justify-between py-2 px-3 bg-teal-50 rounded-lg">
                <span class="font-medium text-teal-800">= Ingresos Reales</span>
                <span class="font-bold text-teal-800">${{ number_format($totalRevenue + $totalCashIncome, 2) }}</span>
            </div>
            @endif
            @if($totalDiscount > 0)
            <div class="flex justify-between py-2 px-3">
                <span class="text-slate-600 pl-4">(-) Descuentos</span>
                <span class="text-red-600">${{ number_format($totalDiscount, 2) }}</span>
            </div>
            @endif
            @if($totalTax > 0)
            <div class="flex justify-between py-2 px-3">
                <span class="text-slate-600 pl-4">Impuestos recaudados</span>
                <span class="text-slate-600">${{ number_format($totalTax, 2) }}</span>
            </div>
            @endif
            <div class="flex justify-between py-2 px-3 bg-amber-50 rounded-lg">
                <span class="font-medium text-amber-800">(-) Costo de Ventas</span>
                <span class="font-bold text-amber-800">${{ number_format($totalCost, 2) }}</span>
            </div>
            <div class="flex justify-between py-2 px-3 {{ $grossProfit >= 0 ? 'bg-emerald-50' : 'bg-red-50' }} rounded-lg border-2 {{ $grossProfit >= 0 ? 'border-emerald-200' : 'border-red-200' }}">
                <span class="font-bold {{ $grossProfit >= 0 ? 'text-emerald-800' : 'text-red-800' }}">= Utilidad Bruta</span>
                <span class="font-bold {{ $grossProfit >= 0 ? 'text-emerald-800' : 'text-red-800' }}">${{ number_format($grossProfit, 2) }} ({{ number_format($grossMargin, 1) }}%)</span>
            </div>
            @if($totalExpenses > 0)
            <div class="flex justify-between py-2 px-3 bg-red-50 rounded-lg mt-2">
                <span class="font-medium text-red-800">(-) Gastos Operativos</span>
                <span class="font-bold text-red-800">${{ number_format($totalExpenses, 2) }}</span>
            </div>
            @if($totalCashExpenses > 0)
            <div class="flex justify-between py-2 px-3">
                <span class="text-slate-600 pl-4">Egresos de Caja</span>
                <span class="text-red-600">${{ number_format($totalCashExpenses, 2) }}</span>
            </div>
            @endif
            @if($totalModuleExpenses > 0)
            <div class="flex justify-between py-2 px-3">
                <span class="text-slate-600 pl-4">Gastos Registrados</span>
                <span class="text-red-600">${{ number_format($totalModuleExpenses, 2) }}</span>
            </div>
            @endif
            @endif
            @if($totalPayrollExpenses > 0)
            <div class="flex justify-between py-2 px-3 bg-purple-50 rounded-lg mt-2">
                <span class="font-medium text-purple-800">(-) Nómina</span>
                <span class="font-bold text-purple-800">${{ number_format($totalPayrollExpenses, 2) }}</span>
            </div>
            @endif
            <div class="flex justify-between py-3 px-3 {{ $netProfit >= 0 ? 'bg-emerald-100' : 'bg-red-100' }} rounded-lg border-2 {{ $netProfit >= 0 ? 'border-emerald-300' : 'border-red-300' }}">
                <span class="font-bold text-lg {{ $netProfit >= 0 ? 'text-emerald-900' : 'text-red-900' }}">= UTILIDAD NETA</span>
                <span class="font-bold text-lg {{ $netProfit >= 0 ? 'text-emerald-900' : 'text-red-900' }}">${{ number_format($netProfit, 2) }} ({{ number_format($netMargin, 1) }}%)</span>
            </div>
        </div>
    </div>

    {{-- Standard Charts Row --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-[#ff7261]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                Tendencia Diaria
            </h3>
            <div class="space-y-2 max-h-[300px] overflow-y-auto">
                @php $maxRevenue = collect($profitByDay)->max('revenue') ?: 1; @endphp
                @forelse($profitByDay as $day)
                <div>
                    <div class="flex justify-between text-xs mb-1">
                        <span class="text-slate-600">{{ $day['label'] }}</span>
                        <div class="flex gap-3">
                            <span class="text-blue-600">Venta: ${{ number_format($day['revenue'], 0) }}</span>
                            <span class="{{ $day['profit'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                Util: ${{ number_format($day['profit'], 0) }}
                            </span>
                        </div>
                    </div>
                    <div class="flex gap-1 h-2">
                        <div class="bg-blue-400 rounded-full" style="width: {{ ($day['revenue'] / $maxRevenue) * 70 }}%"></div>
                        <div class="{{ $day['profit'] >= 0 ? 'bg-emerald-400' : 'bg-red-400' }} rounded-full" style="width: {{ $maxRevenue > 0 ? (abs($day['profit']) / $maxRevenue) * 30 : 0 }}%"></div>
                    </div>
                </div>
                @empty
                <p class="text-slate-400 text-center py-8">No hay datos disponibles</p>
                @endforelse
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                Ingresos y Utilidad por Categoría
            </h3>
            <div class="space-y-3 max-h-[300px] overflow-y-auto">
                @php $maxCatRev = collect($revenueByCategory)->max('revenue') ?: 1; @endphp
                @forelse($revenueByCategory as $cat)
                <div>
                    <div class="flex justify-between text-xs mb-1">
                        <span class="font-medium text-slate-700">{{ $cat['name'] }}</span>
                        <div class="flex gap-3">
                            <span class="text-slate-600">Venta: ${{ number_format($cat['revenue'], 0) }}</span>
                            <span class="{{ $cat['profit'] >= 0 ? 'text-emerald-600' : 'text-red-600' }} font-medium">
                                Util: ${{ number_format($cat['profit'], 0) }}
                            </span>
                        </div>
                    </div>
                    <div class="h-2 bg-slate-100 rounded-full overflow-hidden flex">
                        <div class="bg-purple-500 rounded-full" style="width: {{ ($cat['revenue'] / $maxCatRev) * 100 }}%"></div>
                    </div>
                </div>
                @empty
                <p class="text-slate-400 text-center py-8">No hay categorías registradas</p>
                @endforelse
            </div>
        </div>
    </div>
    @endif

    {{-- CHART ENGINE (ROBUST SCRIPT) --}}
    <script>
        function loadScriptIfNotPresent(src, callback) {
            if (typeof Chart !== 'undefined') {
                callback();
                return;
            }
            let existingScript = document.querySelector(`script[src="${src}"]`);
            if (existingScript) {
                existingScript.addEventListener('load', callback);
            } else {
                let script = document.createElement('script');
                script.src = src;
                script.onload = callback;
                document.head.appendChild(script);
            }
        }

        window.mikposPygCharts = window.mikposPygCharts || {};

        function renderPygVersusCharts(data) {
            loadScriptIfNotPresent('https://cdn.jsdelivr.net/npm/chart.js', function() {
                // 1. Daily Superimposed Curve
                const dailyCanvas = document.getElementById('versusDailyCurveChart');
                if (dailyCanvas && data && data.daily) {
                    if (window.mikposPygCharts.daily) {
                        try { window.mikposPygCharts.daily.destroy(); } catch(e) {}
                    }
                    const labels = data.daily.map(d => d.label);
                    const revA = data.daily.map(d => d.revenueA);
                    const revB = data.daily.map(d => d.revenueB);

                    window.mikposPygCharts.daily = new Chart(dailyCanvas, {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [
                                {
                                    label: (data.labelA || 'Período A'),
                                    data: revA,
                                    borderColor: 'rgba(255, 114, 97, 1)',
                                    backgroundColor: 'rgba(255, 114, 97, 0.1)',
                                    fill: true,
                                    tension: 0.35,
                                    pointRadius: 3,
                                    pointBackgroundColor: 'rgba(255, 114, 97, 1)',
                                },
                                {
                                    label: (data.labelB || 'Período B'),
                                    data: revB,
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
                                    callbacks: {
                                        label: ctx => ctx.dataset.label + ': $' + Number(ctx.raw).toLocaleString('es-CO')
                                    }
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

                // 2. Categories Chart
                const catCanvas = document.getElementById('versusCategoriesChart');
                if (catCanvas && data && data.categories) {
                    if (window.mikposPygCharts.cat) {
                        try { window.mikposPygCharts.cat.destroy(); } catch(e) {}
                    }
                    const catLabels = data.categories.map(c => c.name);
                    const catRevA = data.categories.map(c => c.revenueA);
                    const catRevB = data.categories.map(c => c.revenueB);

                    window.mikposPygCharts.cat = new Chart(catCanvas, {
                        type: 'bar',
                        data: {
                            labels: catLabels,
                            datasets: [
                                {
                                    label: (data.labelA || 'A'),
                                    data: catRevA,
                                    backgroundColor: 'rgba(255, 114, 97, 0.85)',
                                    borderRadius: 6,
                                },
                                {
                                    label: (data.labelB || 'B'),
                                    data: catRevB,
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

                // 3. Payment Methods Chart
                const pmCanvas = document.getElementById('versusPaymentMethodsChart');
                if (pmCanvas && data && data.paymentMethods) {
                    if (window.mikposPygCharts.pm) {
                        try { window.mikposPygCharts.pm.destroy(); } catch(e) {}
                    }
                    const pmLabels = data.paymentMethods.map(p => p.name);
                    const pmTotA = data.paymentMethods.map(p => p.totalA);
                    const pmTotB = data.paymentMethods.map(p => p.totalB);

                    window.mikposPygCharts.pm = new Chart(pmCanvas, {
                        type: 'bar',
                        data: {
                            labels: pmLabels,
                            datasets: [
                                {
                                    label: (data.labelA || 'A'),
                                    data: pmTotA,
                                    backgroundColor: 'rgba(255, 114, 97, 0.8)',
                                    borderRadius: 6,
                                },
                                {
                                    label: (data.labelB || 'B'),
                                    data: pmTotB,
                                    backgroundColor: 'rgba(59, 130, 246, 0.8)',
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
            });
        }

        // Livewire event listener
        window.addEventListener('pyg-versus-charts-ready', function(e) {
            const data = Array.isArray(e.detail) ? e.detail[0] : e.detail;
            setTimeout(() => renderPygVersusCharts(data), 50);
        });

        // Initialize on DOM load if data is pre-rendered
        document.addEventListener('DOMContentLoaded', function() {
            @if($viewMode === 'versus' && !empty($versusDaily))
            const initialData = {
                daily: @json($versusDaily),
                categories: @json(array_slice($versusCategories, 0, 7)),
                paymentMethods: @json($versusPaymentMethods),
                labelA: @json(ucfirst($labelA)),
                labelB: @json(ucfirst($labelB))
            };
            setTimeout(() => renderPygVersusCharts(initialData), 100);
            @endif
        });

        document.addEventListener('livewire:navigated', function() {
            @if($viewMode === 'versus' && !empty($versusDaily))
            const initialData = {
                daily: @json($versusDaily),
                categories: @json(array_slice($versusCategories, 0, 7)),
                paymentMethods: @json($versusPaymentMethods),
                labelA: @json(ucfirst($labelA)),
                labelB: @json(ucfirst($labelB))
            };
            setTimeout(() => renderPygVersusCharts(initialData), 100);
            @endif
        });
    </script>
</div>

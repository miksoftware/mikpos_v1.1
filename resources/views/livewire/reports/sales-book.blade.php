<div class="space-y-6">
    <x-toast />

    {{-- Top Header with Title and Mode Switcher --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#ff7261] to-[#a855f7] flex items-center justify-center text-white shadow-md shadow-[#ff7261]/20">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div>
                <h1 class="text-2xl font-black text-slate-800 tracking-tight flex items-center gap-2">
                    Libro de Ventas
                    @if($viewMode === 'versus')
                    <span class="inline-flex items-center gap-1 text-xs font-bold px-2.5 py-1 rounded-full bg-gradient-to-r from-[#ff7261] to-[#a855f7] text-white shadow-sm">
                        ⚔️ MODO VERSUS
                    </span>
                    @endif
                </h1>
                <p class="text-xs sm:text-sm text-slate-500">
                    {{ $viewMode === 'versus' ? 'Comparativa de rendimiento de ventas inter-meses con análisis de vendedores y horarios' : 'Reporte completo de ventas con análisis detallado' }}
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
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-8 gap-3">
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Rango de Fecha</label>
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

            @if($dateRange === 'custom')
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Desde</label>
                <input wire:model.live="startDate" type="date" class="w-full px-3 py-2 text-xs border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Hasta</label>
                <input wire:model.live="endDate" type="date" class="w-full px-3 py-2 text-xs border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
            </div>
            @endif

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
                <label class="block text-xs font-semibold text-slate-500 mb-1">Método Pago</label>
                <select wire:model.live="selectedPaymentMethodId" class="w-full px-3 py-2 text-xs border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                    <option value="">Todos</option>
                    @foreach($paymentMethods as $pm)
                    <option value="{{ $pm->id }}">{{ $pm->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Caja</label>
                <select wire:model.live="selectedCashRegisterId" class="w-full px-3 py-2 text-xs border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                    <option value="">Todas</option>
                    @foreach($cashRegisters as $cr)
                    <option value="{{ $cr->id }}">{{ $cr->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Estado</label>
                <select wire:model.live="statusFilter" class="w-full px-3 py-2 text-xs border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                    <option value="all">Todos</option>
                    <option value="completed">Completadas</option>
                    <option value="cancelled">Canceladas</option>
                </select>
            </div>
        </div>

        <div class="flex items-center justify-between mt-3 pt-3 border-t border-slate-100">
            <div class="relative flex-1 max-w-sm">
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Buscar factura, cliente, cédula..." class="w-full pl-9 pr-3 py-1.5 text-xs border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50">
                <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
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
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Comparación Rápida de Ventas</span>
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
                <div class="bg-orange-50/70 rounded-xl p-2.5 border border-[#ff7261]/30 flex items-center gap-2">
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
                <div class="bg-purple-50/70 rounded-xl p-2.5 border border-[#a855f7]/30 flex items-center gap-2">
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

        $diffSales = $B['totalSales'] - $A['totalSales'];
        $growthSales = $A['totalSales'] > 0 ? ($diffSales / $A['totalSales']) * 100 : ($B['totalSales'] > 0 ? 100 : 0);

        $diffTrans = $B['totalTransactions'] - $A['totalTransactions'];
        $growthTrans = $A['totalTransactions'] > 0 ? ($diffTrans / $A['totalTransactions']) * 100 : ($B['totalTransactions'] > 0 ? 100 : 0);

        $diffTicket = $B['averageTicket'] - $A['averageTicket'];
        $growthTicket = $A['averageTicket'] > 0 ? ($diffTicket / $A['averageTicket']) * 100 : 0;

        $diffProfit = $B['totalProfit'] - $A['totalProfit'];
        $growthProfit = $A['totalProfit'] != 0 ? ($diffProfit / abs($A['totalProfit'])) * 100 : ($B['totalProfit'] > 0 ? 100 : 0);
    @endphp

    {{-- BATTLE CARDS (Light System Theme) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Card 1: Total Ventas --}}
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200 hover:shadow-md transition-all relative overflow-hidden">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Ventas</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold {{ $growthSales >= 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                    {{ $growthSales >= 0 ? '▲ +' : '▼ ' }}{{ number_format($growthSales, 1) }}%
                </span>
            </div>
            <div class="flex items-baseline justify-between">
                <div>
                    <span class="text-xs font-semibold text-[#ff7261]">A: ${{ number_format($A['totalSales'], 0) }}</span>
                    <div class="text-2xl font-black text-slate-800">${{ number_format($B['totalSales'], 0) }}</div>
                    <span class="text-[11px] font-semibold text-[#a855f7]">B: {{ ucfirst($labelB) }}</span>
                </div>
                <div class="text-right">
                    <span class="text-[11px] font-medium text-slate-400 block">Diferencia</span>
                    <span class="text-sm font-black {{ $diffSales >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                        {{ $diffSales >= 0 ? '+' : '' }}${{ number_format($diffSales, 0) }}
                    </span>
                </div>
            </div>
            {{-- Victory Mini Bar --}}
            <div class="mt-3 pt-2 border-t border-slate-100">
                <div class="flex justify-between text-[10px] text-slate-400 font-semibold mb-1">
                    <span>A: {{ number_format($A['totalSales'] + $B['totalSales'] > 0 ? ($A['totalSales'] / ($A['totalSales'] + $B['totalSales'])) * 100 : 50, 0) }}%</span>
                    <span>B: {{ number_format($A['totalSales'] + $B['totalSales'] > 0 ? ($B['totalSales'] / ($A['totalSales'] + $B['totalSales'])) * 100 : 50, 0) }}%</span>
                </div>
                <div class="h-1.5 w-full bg-slate-100 rounded-full flex overflow-hidden">
                    <div class="bg-[#ff7261] h-full" style="width: {{ $A['totalSales'] + $B['totalSales'] > 0 ? ($A['totalSales'] / ($A['totalSales'] + $B['totalSales'])) * 100 : 50 }}%"></div>
                    <div class="bg-[#a855f7] h-full" style="width: {{ $A['totalSales'] + $B['totalSales'] > 0 ? ($B['totalSales'] / ($A['totalSales'] + $B['totalSales'])) * 100 : 50 }}%"></div>
                </div>
            </div>
        </div>

        {{-- Card 2: Transacciones --}}
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200 hover:shadow-md transition-all relative overflow-hidden">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Transacciones</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold {{ $growthTrans >= 0 ? 'bg-blue-100 text-blue-700' : 'bg-red-100 text-red-700' }}">
                    {{ $growthTrans >= 0 ? '▲ +' : '▼ ' }}{{ number_format($growthTrans, 1) }}%
                </span>
            </div>
            <div class="flex items-baseline justify-between">
                <div>
                    <span class="text-xs font-semibold text-[#ff7261]">A: {{ number_format($A['totalTransactions']) }}</span>
                    <div class="text-2xl font-black text-slate-800">{{ number_format($B['totalTransactions']) }}</div>
                    <span class="text-[11px] font-semibold text-[#a855f7]">B: {{ ucfirst($labelB) }}</span>
                </div>
                <div class="text-right">
                    <span class="text-[11px] font-medium text-slate-400 block">Diferencia</span>
                    <span class="text-sm font-black {{ $diffTrans >= 0 ? 'text-blue-600' : 'text-red-600' }}">
                        {{ $diffTrans >= 0 ? '+' : '' }}{{ number_format($diffTrans) }}
                    </span>
                </div>
            </div>
            <div class="mt-3 pt-2 border-t border-slate-100 text-[11px] text-slate-500 flex justify-between">
                <span>Promedio diario:</span>
                <span class="font-bold">{{ number_format($B['totalTransactions'] / 30, 1) }} v/día (B) vs {{ number_format($A['totalTransactions'] / 30, 1) }} (A)</span>
            </div>
        </div>

        {{-- Card 3: Ticket Promedio --}}
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200 hover:shadow-md transition-all relative overflow-hidden">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Ticket Promedio</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold {{ $growthTicket >= 0 ? 'bg-purple-100 text-purple-700' : 'bg-red-100 text-red-700' }}">
                    {{ $growthTicket >= 0 ? '▲ +' : '▼ ' }}{{ number_format($growthTicket, 1) }}%
                </span>
            </div>
            <div class="flex items-baseline justify-between">
                <div>
                    <span class="text-xs font-semibold text-[#ff7261]">A: ${{ number_format($A['averageTicket'], 0) }}</span>
                    <div class="text-2xl font-black text-slate-800">${{ number_format($B['averageTicket'], 0) }}</div>
                    <span class="text-[11px] font-semibold text-[#a855f7]">B: {{ ucfirst($labelB) }}</span>
                </div>
                <div class="text-right">
                    <span class="text-[11px] font-medium text-slate-400 block">Diferencia</span>
                    <span class="text-sm font-black {{ $diffTicket >= 0 ? 'text-purple-600' : 'text-red-600' }}">
                        {{ $diffTicket >= 0 ? '+' : '' }}${{ number_format($diffTicket, 0) }}
                    </span>
                </div>
            </div>
            <div class="mt-3 pt-2 border-t border-slate-100 text-[11px] text-slate-500 flex justify-between">
                <span>Ticket medio:</span>
                <span class="font-bold">{{ $diffTicket >= 0 ? 'Mayor compra en B' : 'Mayor compra en A' }}</span>
            </div>
        </div>

        {{-- Card 4: Ganancia Estimada (Light System Theme) --}}
        <div class="bg-white rounded-2xl p-5 shadow-sm border-2 {{ $B['totalProfit'] >= $A['totalProfit'] ? 'border-emerald-300' : 'border-[#a855f7]/40' }} hover:shadow-md transition-all relative overflow-hidden">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-black uppercase tracking-wider text-slate-700">
                    Ganancia de Ventas
                </span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-black {{ $growthProfit >= 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                    {{ $growthProfit >= 0 ? '▲ +' : '▼ ' }}{{ number_format($growthProfit, 1) }}%
                </span>
            </div>
            <div class="flex items-baseline justify-between">
                <div>
                    <span class="text-xs font-semibold text-[#ff7261]">A: ${{ number_format($A['totalProfit'], 0) }}</span>
                    <div class="text-2xl font-black text-emerald-600">${{ number_format($B['totalProfit'], 0) }}</div>
                    <span class="text-[11px] font-semibold text-[#a855f7]">B: {{ ucfirst($labelB) }}</span>
                </div>
                <div class="text-right">
                    <span class="text-[10px] uppercase font-bold text-slate-400 block">Diferencia</span>
                    <span class="text-sm font-black {{ $diffProfit >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                        {{ $diffProfit >= 0 ? '+' : '' }}${{ number_format($diffProfit, 0) }}
                    </span>
                </div>
            </div>
            <div class="mt-3 pt-2 border-t border-slate-100 text-[11px] flex justify-between items-center">
                <span class="text-slate-500">Margen s/ Venta:</span>
                <span class="font-bold text-emerald-700">{{ number_format($B['totalSales'] > 0 ? ($B['totalProfit'] / $B['totalSales']) * 100 : 0, 1) }}% (B) vs {{ number_format($A['totalSales'] > 0 ? ($A['totalProfit'] / $A['totalSales']) * 100 : 0, 1) }}% (A)</span>
            </div>
        </div>
    </div>

    {{-- INTERACTIVE CHART ROW 1: Superimposed Daily Sales Curve --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
            <div>
                <h3 class="font-bold text-slate-800 text-lg flex items-center gap-2">
                    <svg class="w-5 h-5 text-[#ff7261]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                    Comparativa de Ventas Diarias (Día 1 al 31)
                </h3>
                <p class="text-xs text-slate-500">Superposición del volumen vendido día a día entre ambos meses</p>
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
        <div class="relative w-full" style="height: 320px;">
            <canvas id="versusSalesDailyChart"></canvas>
        </div>
    </div>

    {{-- CHART ROW 2: Hourly Peak Comparison & Seller Performance --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Hourly Peak Comparison --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h3 class="font-bold text-slate-800 text-base mb-3 flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Ventas por Franja Horaria (Horas Pico)
            </h3>
            <div class="relative w-full" style="height: 280px;">
                <canvas id="versusSalesHourlyChart"></canvas>
            </div>
        </div>

        {{-- Seller Performance Comparison --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h3 class="font-bold text-slate-800 text-base mb-3 flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                Ventas por Vendedor (Mes A vs Mes B)
            </h3>
            <div class="relative w-full" style="height: 280px;">
                <canvas id="versusSalesSellersChart"></canvas>
            </div>
        </div>
    </div>

    {{-- COMPARATIVE TABLES: Sellers & Payment Methods --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Sellers Table --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-5 py-4 bg-slate-50 border-b border-slate-200">
                <h3 class="font-bold text-slate-800 text-sm flex items-center gap-2">
                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    Comparativa de Rendimiento por Vendedor
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-xs">
                    <thead class="bg-slate-50 text-slate-500 font-semibold">
                        <tr>
                            <th class="px-4 py-2.5 text-left">Vendedor</th>
                            <th class="px-3 py-2.5 text-right bg-orange-50/40 text-[#ff7261]">{{ ucfirst($labelA) }}</th>
                            <th class="px-3 py-2.5 text-right bg-purple-50/40 text-[#7c3aed]">{{ ucfirst($labelB) }}</th>
                            <th class="px-3 py-2.5 text-right">Variación ($)</th>
                            <th class="px-3 py-2.5 text-right">%</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($versusSellers as $s)
                        <tr class="hover:bg-slate-50/50">
                            <td class="px-4 py-2 font-medium text-slate-800">{{ $s['name'] }}</td>
                            <td class="px-3 py-2 text-right text-slate-600 bg-orange-50/20">${{ number_format($s['totalA'], 0) }}</td>
                            <td class="px-3 py-2 text-right font-bold text-slate-800 bg-purple-50/20">${{ number_format($s['totalB'], 0) }}</td>
                            <td class="px-3 py-2 text-right font-bold {{ $s['diff'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                {{ $s['diff'] >= 0 ? '+' : '' }}${{ number_format($s['diff'], 0) }}
                            </td>
                            <td class="px-3 py-2 text-right font-bold {{ $s['growth'] >= 0 ? 'text-emerald-700' : 'text-red-700' }}">
                                {{ $s['growth'] >= 0 ? '+' : '' }}{{ $s['growth'] }}%
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="px-4 py-6 text-center text-slate-400">Sin datos de vendedores</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Payment Methods Table --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-5 py-4 bg-slate-50 border-b border-slate-200">
                <h3 class="font-bold text-slate-800 text-sm flex items-center gap-2">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                    Comparativa por Método de Pago
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-xs">
                    <thead class="bg-slate-50 text-slate-500 font-semibold">
                        <tr>
                            <th class="px-4 py-2.5 text-left">Método</th>
                            <th class="px-3 py-2.5 text-right bg-orange-50/40 text-[#ff7261]">{{ ucfirst($labelA) }}</th>
                            <th class="px-3 py-2.5 text-right bg-purple-50/40 text-[#7c3aed]">{{ ucfirst($labelB) }}</th>
                            <th class="px-3 py-2.5 text-right">Variación ($)</th>
                            <th class="px-3 py-2.5 text-right">%</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($versusPaymentMethods as $pm)
                        <tr class="hover:bg-slate-50/50">
                            <td class="px-4 py-2 font-medium text-slate-800">{{ $pm['name'] }}</td>
                            <td class="px-3 py-2 text-right text-slate-600 bg-orange-50/20">${{ number_format($pm['totalA'], 0) }}</td>
                            <td class="px-3 py-2 text-right font-bold text-slate-800 bg-purple-50/20">${{ number_format($pm['totalB'], 0) }}</td>
                            <td class="px-3 py-2 text-right font-bold {{ $pm['diff'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                {{ $pm['diff'] >= 0 ? '+' : '' }}${{ number_format($pm['diff'], 0) }}
                            </td>
                            <td class="px-3 py-2 text-right font-bold {{ $pm['growth'] >= 0 ? 'text-emerald-700' : 'text-red-700' }}">
                                {{ $pm['growth'] >= 0 ? '+' : '' }}{{ $pm['growth'] }}%
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="px-4 py-6 text-center text-slate-400">Sin datos de métodos de pago</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- SCRIPT FOR SALES VERSUS CHARTS (ROBUST) --}}
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

        window.mikposSalesCharts = window.mikposSalesCharts || {};

        function renderSalesVersusCharts(data) {
            loadScriptIfNotPresent('https://cdn.jsdelivr.net/npm/chart.js', function() {
                // 1. Daily Sales Chart
                const dailyCtx = document.getElementById('versusSalesDailyChart');
                if (dailyCtx && data && data.daily) {
                    if (window.mikposSalesCharts.daily) {
                        try { window.mikposSalesCharts.daily.destroy(); } catch(e) {}
                    }
                    window.mikposSalesCharts.daily = new Chart(dailyCtx, {
                        type: 'line',
                        data: {
                            labels: data.daily.map(d => d.label),
                            datasets: [
                                {
                                    label: (data.labelA || 'Período A') + ' (A)',
                                    data: data.daily.map(d => d.totalA),
                                    borderColor: 'rgba(255, 114, 97, 1)',
                                    backgroundColor: 'rgba(255, 114, 97, 0.1)',
                                    fill: true,
                                    tension: 0.35,
                                    pointRadius: 3,
                                    pointBackgroundColor: 'rgba(255, 114, 97, 1)',
                                },
                                {
                                    label: (data.labelB || 'Período B') + ' (B)',
                                    data: data.daily.map(d => d.totalB),
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

                // 2. Hourly Sales Chart
                const hourlyCtx = document.getElementById('versusSalesHourlyChart');
                if (hourlyCtx && data && data.hourly) {
                    if (window.mikposSalesCharts.hourly) {
                        try { window.mikposSalesCharts.hourly.destroy(); } catch(e) {}
                    }
                    window.mikposSalesCharts.hourly = new Chart(hourlyCtx, {
                        type: 'bar',
                        data: {
                            labels: data.hourly.map(h => h.hour),
                            datasets: [
                                {
                                    label: (data.labelA || 'A'),
                                    data: data.hourly.map(h => h.totalA),
                                    backgroundColor: 'rgba(255, 114, 97, 0.85)',
                                    borderRadius: 4,
                                },
                                {
                                    label: (data.labelB || 'B'),
                                    data: data.hourly.map(h => h.totalB),
                                    backgroundColor: 'rgba(99, 102, 241, 0.85)',
                                    borderRadius: 4,
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

                // 3. Sellers Chart
                const sellerCtx = document.getElementById('versusSalesSellersChart');
                if (sellerCtx && data && data.sellers) {
                    if (window.mikposSalesCharts.sellers) {
                        try { window.mikposSalesCharts.sellers.destroy(); } catch(e) {}
                    }
                    window.mikposSalesCharts.sellers = new Chart(sellerCtx, {
                        type: 'bar',
                        data: {
                            labels: data.sellers.map(s => s.name),
                            datasets: [
                                {
                                    label: (data.labelA || 'A'),
                                    data: data.sellers.map(s => s.totalA),
                                    backgroundColor: 'rgba(255, 114, 97, 0.85)',
                                    borderRadius: 6,
                                },
                                {
                                    label: (data.labelB || 'B'),
                                    data: data.sellers.map(s => s.totalB),
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
            });
        }

        // Livewire event listener
        window.addEventListener('sales-versus-charts-ready', function(e) {
            const data = Array.isArray(e.detail) ? e.detail[0] : e.detail;
            setTimeout(() => renderSalesVersusCharts(data), 50);
        });

        // Initialize on DOM load if data is pre-rendered
        document.addEventListener('DOMContentLoaded', function() {
            @if($viewMode === 'versus' && !empty($versusDaily))
            const initialData = {
                daily: @json($versusDaily),
                hourly: @json($versusHourly),
                sellers: @json(array_slice($versusSellers, 0, 7)),
                labelA: @json(ucfirst($labelA)),
                labelB: @json(ucfirst($labelB))
            };
            setTimeout(() => renderSalesVersusCharts(initialData), 100);
            @endif
        });

        document.addEventListener('livewire:navigated', function() {
            @if($viewMode === 'versus' && !empty($versusDaily))
            const initialData = {
                daily: @json($versusDaily),
                hourly: @json($versusHourly),
                sellers: @json(array_slice($versusSellers, 0, 7)),
                labelA: @json(ucfirst($labelA)),
                labelB: @json(ucfirst($labelB))
            };
            setTimeout(() => renderSalesVersusCharts(initialData), 100);
            @endif
        });
    </script>

    @else
    {{-- ========================================================================= --}}
    {{-- STANDARD VIEW CONTENT --}}
    {{-- ========================================================================= --}}
    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-7 gap-4">
        {{-- Total Sales --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-green-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Total Ventas</p>
                    <p class="text-lg font-bold text-green-600">${{ number_format($totalSales, 0) }}</p>
                </div>
            </div>
        </div>

        {{-- Transactions --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Transacciones</p>
                    <p class="text-lg font-bold text-blue-600">{{ number_format($totalTransactions) }}</p>
                </div>
            </div>
        </div>

        {{-- Subtotal --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Subtotal</p>
                    <p class="text-lg font-bold text-slate-600">${{ number_format($totalSubtotal, 0) }}</p>
                </div>
            </div>
        </div>

        {{-- Tax --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Impuestos</p>
                    <p class="text-lg font-bold text-amber-600">${{ number_format($totalTax, 0) }}</p>
                </div>
            </div>
        </div>

        {{-- Discount --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-red-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Descuentos</p>
                    <p class="text-lg font-bold text-red-600">${{ number_format($totalDiscount, 0) }}</p>
                </div>
            </div>
        </div>

        {{-- Average Ticket --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-purple-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Ticket Promedio</p>
                    <p class="text-lg font-bold text-purple-600">${{ number_format($averageTicket, 0) }}</p>
                </div>
            </div>
        </div>

        {{-- Profit --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Ganancia</p>
                    <p class="text-lg font-bold text-emerald-600">${{ number_format($totalProfit, 0) }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts Section --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Sales Trend --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-[#ff7261]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                </svg>
                Tendencia de Ventas
            </h3>
            <div class="space-y-2 max-h-[260px] overflow-y-auto">
                @php $maxSale = collect($salesByDay)->max('total') ?: 1; @endphp
                @forelse($salesByDay as $day)
                <div>
                    <div class="flex justify-between text-xs mb-1">
                        <span class="text-slate-600 font-medium">{{ $day['label'] }}</span>
                        <div class="flex gap-3">
                            <span class="text-slate-400">{{ $day['count'] }} ventas</span>
                            <span class="font-bold text-slate-700">${{ number_format($day['total'], 0) }}</span>
                        </div>
                    </div>
                    <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                        <div class="bg-gradient-to-r from-[#ff7261] to-[#a855f7] h-full rounded-full" style="width: {{ ($day['total'] / $maxSale) * 100 }}%"></div>
                    </div>
                </div>
                @empty
                <p class="text-center text-slate-400 py-8">No hay ventas registradas</p>
                @endforelse
            </div>
        </div>

        {{-- Sales by Payment Method --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                Ventas por Método de Pago
            </h3>
            <div class="space-y-3 max-h-[260px] overflow-y-auto">
                @php $maxPm = collect($salesByPaymentMethod)->max('total') ?: 1; @endphp
                @forelse($salesByPaymentMethod as $pm)
                <div>
                    <div class="flex justify-between text-xs mb-1">
                        <span class="font-medium text-slate-700">{{ $pm['name'] }}</span>
                        <div class="flex gap-3">
                            <span class="text-slate-400">{{ $pm['count'] }} trans.</span>
                            <span class="font-bold text-slate-800">${{ number_format($pm['total'], 0) }}</span>
                        </div>
                    </div>
                    <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                        <div class="bg-emerald-500 h-full rounded-full" style="width: {{ ($pm['total'] / $maxPm) * 100 }}%"></div>
                    </div>
                </div>
                @empty
                <p class="text-center text-slate-400 py-8">No hay datos</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Sales List Table (Standard Mode) --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center">
            <h3 class="font-bold text-slate-800 text-sm flex items-center gap-2">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                Listado Detallado de Ventas
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-xs">
                <thead class="bg-slate-50 text-slate-500 font-semibold uppercase">
                    <tr>
                        <th class="px-4 py-3 text-left">Factura</th>
                        <th class="px-4 py-3 text-left">Fecha</th>
                        <th class="px-4 py-3 text-left">Cliente</th>
                        <th class="px-4 py-3 text-left">Vendedor</th>
                        <th class="px-4 py-3 text-right">Subtotal</th>
                        <th class="px-4 py-3 text-right">Impuesto</th>
                        <th class="px-4 py-3 text-right">Descuento</th>
                        <th class="px-4 py-3 text-right">Total</th>
                        <th class="px-4 py-3 text-center">Estado</th>
                        <th class="px-4 py-3 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($sales as $sale)
                    <tr class="hover:bg-slate-50/60">
                        <td class="px-4 py-2.5 font-bold text-slate-800">
                            {{ $sale->invoice_number }}
                            @if($sale->dian_number)
                            <span class="text-[10px] text-purple-600 block">DIAN: {{ $sale->dian_number }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-2.5 text-slate-600">{{ $sale->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-2.5 font-medium text-slate-700">{{ $sale->customer?->full_name ?? 'Consumidor Final' }}</td>
                        <td class="px-4 py-2.5 text-slate-600">{{ $sale->seller?->name ?? $sale->user?->name ?? '—' }}</td>
                        <td class="px-4 py-2.5 text-right text-slate-600">${{ number_format($sale->subtotal, 0) }}</td>
                        <td class="px-4 py-2.5 text-right text-amber-600">${{ number_format($sale->tax_total, 0) }}</td>
                        <td class="px-4 py-2.5 text-right text-red-600">${{ number_format($sale->discount, 0) }}</td>
                        <td class="px-4 py-2.5 text-right font-black text-slate-900">${{ number_format($sale->total, 0) }}</td>
                        <td class="px-4 py-2.5 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold {{ $sale->status === 'completed' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                                {{ $sale->status === 'completed' ? 'Completada' : 'Cancelada' }}
                            </span>
                        </td>
                        <td class="px-4 py-2.5 text-center">
                            <button wire:click="viewSaleDetail({{ $sale->id }})" class="p-1.5 rounded-lg bg-slate-100 hover:bg-[#ff7261] hover:text-white text-slate-600 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="10" class="px-4 py-8 text-center text-slate-400">No se encontraron ventas para este período</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($sales instanceof \Illuminate\Pagination\LengthAwarePaginator && $sales->hasPages())
        <div class="px-4 py-3 border-t border-slate-100">
            {{ $sales->links() }}
        </div>
        @endif
    </div>

    {{-- Detail Modal --}}
    @if($isDetailModalOpen && $selectedSale)
    <div class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full p-6 space-y-4" @click.away="$wire.closeDetailModal()">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h3 class="text-lg font-bold text-slate-800">Detalle de Venta {{ $selectedSale->invoice_number }}</h3>
                    <p class="text-xs text-slate-400">{{ $selectedSale->created_at->format('d/m/Y H:i:s') }}</p>
                </div>
                <button wire:click="closeDetailModal" class="p-1.5 rounded-lg text-slate-400 hover:bg-slate-100 text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <div class="space-y-3">
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs">
                    <div class="p-2.5 rounded-xl bg-slate-50"><span class="text-slate-400 block">Cliente:</span><span class="font-bold text-slate-800">{{ $selectedSale->customer?->full_name ?? 'Consumidor Final' }}</span></div>
                    <div class="p-2.5 rounded-xl bg-slate-50"><span class="text-slate-400 block">Vendedor:</span><span class="font-bold text-slate-800">{{ $selectedSale->seller?->name ?? '—' }}</span></div>
                    <div class="p-2.5 rounded-xl bg-slate-50"><span class="text-slate-400 block">Sucursal:</span><span class="font-bold text-slate-800">{{ $selectedSale->branch?->name ?? '—' }}</span></div>
                </div>

                <div class="border rounded-xl overflow-hidden text-xs">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50 text-slate-500 font-bold">
                            <tr>
                                <th class="px-3 py-2 text-left">Producto / Servicio</th>
                                <th class="px-3 py-2 text-right">Cant.</th>
                                <th class="px-3 py-2 text-right">Precio</th>
                                <th class="px-3 py-2 text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($selectedSale->items as $item)
                            <tr>
                                <td class="px-3 py-2 font-medium text-slate-800">{{ $item->product?->name ?? $item->service?->name ?? 'Item' }}</td>
                                <td class="px-3 py-2 text-right">{{ $item->quantity }}</td>
                                <td class="px-3 py-2 text-right">${{ number_format($item->unit_price, 0) }}</td>
                                <td class="px-3 py-2 text-right font-bold">${{ number_format($item->subtotal, 0) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-end gap-6 text-xs pt-2">
                    <div class="text-right space-y-1">
                        <div class="text-slate-500">Subtotal: <span class="font-bold text-slate-800">${{ number_format($selectedSale->subtotal, 0) }}</span></div>
                        <div class="text-slate-500">Impuestos: <span class="font-bold text-amber-600">${{ number_format($selectedSale->tax_total, 0) }}</span></div>
                        <div class="text-slate-500">Descuento: <span class="font-bold text-red-600">-${{ number_format($selectedSale->discount, 0) }}</span></div>
                        <div class="text-sm font-black text-slate-900 border-t pt-1">Total: ${{ number_format($selectedSale->total, 0) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
    @endif
</div>

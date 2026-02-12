<div class="space-y-6">
    <x-toast />

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Pérdidas y Ganancias</h1>
            <p class="text-slate-500 mt-1">Análisis financiero del negocio</p>
        </div>
        @if(auth()->user()->hasPermission('reports.export'))
        <button wire:click="exportExcel" wire:loading.attr="disabled" wire:target="exportExcel"
            class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-semibold rounded-xl shadow-sm hover:shadow transition-all duration-200 disabled:opacity-50">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
            <span wire:loading.remove wire:target="exportExcel">Exportar Excel</span>
            <span wire:loading wire:target="exportExcel">Exportando...</span>
        </button>
        @endif
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
        <div class="flex flex-col sm:flex-row gap-3 items-end">
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Período</label>
                <select wire:model.live="dateRange" class="px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] sm:text-sm">
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
                <label class="block text-xs font-medium text-slate-500 mb-1">Desde</label>
                <input wire:model.live="startDate" type="date" class="px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] sm:text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Hasta</label>
                <input wire:model.live="endDate" type="date" class="px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] sm:text-sm">
            </div>
            @endif
            @if($isSuperAdmin)
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Sucursal</label>
                <select wire:model.live="selectedBranchId" class="px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] sm:text-sm min-w-[160px]">
                    <option value="">Todas</option>
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

    {{-- KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Revenue --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                </div>
                <span class="text-sm text-slate-500">Ingresos</span>
            </div>
            <p class="text-2xl font-bold text-slate-800">${{ number_format($totalRevenue, 0, ',', '.') }}</p>
            <p class="text-xs text-slate-400 mt-1">{{ $totalTransactions }} ventas</p>
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
            <p class="text-xs text-slate-400 mt-1">Costo de productos vendidos</p>
        </div>

        {{-- Gross Profit --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl {{ $grossProfit >= 0 ? 'bg-emerald-100' : 'bg-red-100' }} flex items-center justify-center">
                    <svg class="w-5 h-5 {{ $grossProfit >= 0 ? 'text-emerald-600' : 'text-red-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                </div>
                <span class="text-sm text-slate-500">Utilidad Bruta</span>
            </div>
            <p class="text-2xl font-bold {{ $grossProfit >= 0 ? 'text-emerald-600' : 'text-red-600' }}">${{ number_format($grossProfit, 0, ',', '.') }}</p>
            <p class="text-xs {{ $grossMargin >= 0 ? 'text-emerald-500' : 'text-red-500' }} mt-1">Margen: {{ number_format($grossMargin, 1) }}%</p>
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
                <span class="font-medium text-blue-800">Ingresos por Ventas</span>
                <span class="font-bold text-blue-800">${{ number_format($totalRevenue, 2) }}</span>
            </div>
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
            @endif
            <div class="flex justify-between py-3 px-3 {{ $netProfit >= 0 ? 'bg-emerald-100' : 'bg-red-100' }} rounded-lg border-2 {{ $netProfit >= 0 ? 'border-emerald-300' : 'border-red-300' }}">
                <span class="font-bold text-lg {{ $netProfit >= 0 ? 'text-emerald-900' : 'text-red-900' }}">= UTILIDAD NETA</span>
                <span class="font-bold text-lg {{ $netProfit >= 0 ? 'text-emerald-900' : 'text-red-900' }}">${{ number_format($netProfit, 2) }} ({{ number_format($netMargin, 1) }}%)</span>
            </div>
        </div>
    </div>

    {{-- Charts Row 1: Profit Trend + Revenue by Category --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Daily Profit Trend --}}
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
            @if(count($profitByDay) > 0)
            <div class="flex gap-4 mt-3 pt-3 border-t border-slate-100 text-xs text-slate-500">
                <div class="flex items-center gap-1"><span class="w-3 h-2 bg-blue-400 rounded-full"></span> Ventas</div>
                <div class="flex items-center gap-1"><span class="w-3 h-2 bg-emerald-400 rounded-full"></span> Utilidad</div>
            </div>
            @endif
        </div>

        {{-- Revenue by Category --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-[#a855f7]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path></svg>
                Rentabilidad por Categoría
            </h3>
            <div class="space-y-3 max-h-[300px] overflow-y-auto">
                @php
                    $maxCatRevenue = collect($revenueByCategory)->max('revenue') ?: 1;
                    $catColors = ['bg-purple-500', 'bg-blue-500', 'bg-emerald-500', 'bg-amber-500', 'bg-red-500', 'bg-indigo-500', 'bg-pink-500', 'bg-teal-500'];
                @endphp
                @forelse($revenueByCategory as $index => $cat)
                <div class="p-3 rounded-lg bg-slate-50">
                    <div class="flex items-center justify-between mb-1">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full {{ $catColors[$index % count($catColors)] }}"></div>
                            <span class="text-sm font-medium text-slate-700">{{ $cat['name'] }}</span>
                        </div>
                        <span class="text-sm font-bold {{ $cat['profit'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                            ${{ number_format($cat['profit'], 0) }}
                        </span>
                    </div>
                    <div class="flex justify-between text-xs text-slate-500">
                        <span>Ventas: ${{ number_format($cat['revenue'], 0) }}</span>
                        <span>Costo: ${{ number_format($cat['cost'], 0) }}</span>
                    </div>
                    <div class="h-1.5 bg-slate-200 rounded-full mt-1 overflow-hidden">
                        <div class="{{ $catColors[$index % count($catColors)] }} rounded-full h-full" style="width: {{ ($cat['revenue'] / $maxCatRevenue) * 100 }}%"></div>
                    </div>
                </div>
                @empty
                <p class="text-slate-400 text-center py-8">No hay datos disponibles</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Charts Row 2: Payment Methods + Expenses --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Revenue by Payment Method --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                Ingresos por Método de Pago
            </h3>
            <div class="space-y-3">
                @php
                    $maxPay = collect($revenueByPaymentMethod)->max('total') ?: 1;
                    $payColors = ['bg-emerald-500', 'bg-blue-500', 'bg-purple-500', 'bg-amber-500', 'bg-red-500', 'bg-indigo-500'];
                @endphp
                @forelse($revenueByPaymentMethod as $index => $method)
                <div class="flex items-center gap-3">
                    <div class="w-3 h-3 rounded-full {{ $payColors[$index % count($payColors)] }}"></div>
                    <div class="flex-1">
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-slate-600">{{ $method['name'] }} <span class="text-xs text-slate-400">({{ $method['count'] }})</span></span>
                            <span class="font-medium text-slate-800">${{ number_format($method['total'], 0) }}</span>
                        </div>
                        <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full {{ $payColors[$index % count($payColors)] }} rounded-full" style="width: {{ ($method['total'] / $maxPay) * 100 }}%"></div>
                        </div>
                    </div>
                </div>
                @empty
                <p class="text-slate-400 text-center py-8">No hay datos disponibles</p>
                @endforelse
            </div>
        </div>

        {{-- Expense Breakdown --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                Desglose de Gastos
            </h3>
            <div class="space-y-2">
                @php $maxExp = collect($expenseBreakdown)->max('total') ?: 1; @endphp
                @forelse($expenseBreakdown as $expense)
                <div class="flex items-center justify-between p-2 rounded-lg hover:bg-red-50 transition-colors">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-slate-700 truncate">{{ $expense['concept'] }}</p>
                        <div class="h-1 bg-slate-100 rounded-full mt-1 overflow-hidden">
                            <div class="h-full bg-red-400 rounded-full" style="width: {{ ($expense['total'] / $maxExp) * 100 }}%"></div>
                        </div>
                    </div>
                    <span class="text-sm font-medium text-red-600 ml-3">${{ number_format($expense['total'], 0) }}</span>
                </div>
                @empty
                <p class="text-slate-400 text-center py-8">No hay gastos registrados</p>
                @endforelse
                @if($totalExpenses > 0)
                <div class="flex justify-between pt-2 border-t border-slate-200 text-sm">
                    <span class="font-medium text-slate-700">Total Gastos:</span>
                    <span class="font-bold text-red-600">${{ number_format($totalExpenses, 0) }}</span>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Top Products Tables --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Most Profitable --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200">
                <h3 class="font-semibold text-slate-800 flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path></svg>
                    Top 10 Productos Más Rentables
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">#</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Producto</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Cant.</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Utilidad</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Margen</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($topProfitableProducts as $index => $product)
                        <tr class="hover:bg-slate-50/50">
                            <td class="px-4 py-2.5">
                                <span class="w-6 h-6 rounded-full bg-gradient-to-r from-[#ff7261] to-[#a855f7] text-white text-xs flex items-center justify-center font-bold">{{ $index + 1 }}</span>
                            </td>
                            <td class="px-4 py-2.5">
                                <p class="text-sm font-medium text-slate-800 truncate max-w-[200px]">{{ $product['name'] }}</p>
                                <p class="text-xs text-slate-400">{{ $product['sku'] }}</p>
                            </td>
                            <td class="px-4 py-2.5 text-right text-sm text-slate-600">{{ rtrim(rtrim(number_format($product['qty'], 3), '0'), '.') }}</td>
                            <td class="px-4 py-2.5 text-right text-sm font-medium text-emerald-600">${{ number_format($product['profit'], 0) }}</td>
                            <td class="px-4 py-2.5 text-right">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $product['margin'] >= 20 ? 'bg-emerald-100 text-emerald-700' : ($product['margin'] >= 10 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') }}">
                                    {{ $product['margin'] }}%
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-slate-400 text-sm">No hay datos</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Least Profitable / Loss --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200">
                <h3 class="font-semibold text-slate-800 flex items-center gap-2">
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path></svg>
                    Productos con Pérdida
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">#</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Producto</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Cant.</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Pérdida</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Margen</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($topLossProducts as $index => $product)
                        <tr class="hover:bg-red-50/30">
                            <td class="px-4 py-2.5 text-sm text-slate-500">{{ $index + 1 }}</td>
                            <td class="px-4 py-2.5">
                                <p class="text-sm font-medium text-slate-800 truncate max-w-[200px]">{{ $product['name'] }}</p>
                                <p class="text-xs text-slate-400">{{ $product['sku'] }}</p>
                            </td>
                            <td class="px-4 py-2.5 text-right text-sm text-slate-600">{{ rtrim(rtrim(number_format($product['qty'], 3), '0'), '.') }}</td>
                            <td class="px-4 py-2.5 text-right text-sm font-medium text-red-600">${{ number_format(abs($product['profit']), 0) }}</td>
                            <td class="px-4 py-2.5 text-right">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">{{ $product['margin'] }}%</span>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-emerald-500 text-sm">No hay productos con pérdida 🎉</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Additional Info --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            Información Adicional
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
            <div class="p-3 rounded-lg bg-slate-50">
                <span class="text-slate-500">Ticket Promedio</span>
                <p class="font-bold text-slate-800 text-lg">${{ $totalTransactions > 0 ? number_format($totalRevenue / $totalTransactions, 0, ',', '.') : '0' }}</p>
            </div>
            <div class="p-3 rounded-lg bg-slate-50">
                <span class="text-slate-500">Compras del Período</span>
                <p class="font-bold text-slate-800 text-lg">${{ number_format($totalPurchases, 0, ',', '.') }}</p>
            </div>
            <div class="p-3 rounded-lg bg-slate-50">
                <span class="text-slate-500">Impuestos Recaudados</span>
                <p class="font-bold text-slate-800 text-lg">${{ number_format($totalTax, 0, ',', '.') }}</p>
            </div>
            <div class="p-3 rounded-lg bg-slate-50">
                <span class="text-slate-500">Descuentos Otorgados</span>
                <p class="font-bold text-red-600 text-lg">${{ number_format($totalDiscount, 0, ',', '.') }}</p>
            </div>
        </div>
    </div>
</div>

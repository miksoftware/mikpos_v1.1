<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Libro de Ventas</h1>
            <p class="text-slate-500 mt-1">Reporte completo de ventas con análisis detallado</p>
        </div>
    </div>

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
            <div class="space-y-2">
                @php
                    $maxSale = collect($salesByDay)->max('total') ?: 1;
                @endphp
                @forelse($salesByDay as $day)
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-slate-600">{{ $day['label'] }}</span>
                        <span class="font-medium text-slate-800">${{ number_format($day['total'], 0) }} ({{ $day['count'] }})</span>
                    </div>
                    <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-full transition-all duration-500" 
                            style="width: {{ ($day['total'] / $maxSale) * 100 }}%"></div>
                    </div>
                </div>
                @empty
                <p class="text-slate-400 text-center py-4">No hay datos disponibles</p>
                @endforelse
            </div>
        </div>

        {{-- Sales by Payment Method --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-[#a855f7]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                </svg>
                Ventas por Forma de Pago
            </h3>
            <div class="space-y-3">
                @php
                    $maxPayment = collect($salesByPaymentMethod)->max('total') ?: 1;
                    $colors = ['bg-green-500', 'bg-blue-500', 'bg-purple-500', 'bg-amber-500', 'bg-red-500', 'bg-indigo-500'];
                @endphp
                @forelse($salesByPaymentMethod as $index => $method)
                <div class="flex items-center gap-3">
                    <div class="w-3 h-3 rounded-full {{ $colors[$index % count($colors)] }}"></div>
                    <div class="flex-1">
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-slate-600">{{ $method->name }}</span>
                            <span class="font-medium text-slate-800">${{ number_format($method->total, 0) }}</span>
                        </div>
                        <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full {{ $colors[$index % count($colors)] }} rounded-full" 
                                style="width: {{ ($method->total / $maxPayment) * 100 }}%"></div>
                        </div>
                    </div>
                </div>
                @empty
                <p class="text-slate-400 text-center py-4">No hay datos disponibles</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Second Row Charts --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Sales by User --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                Ventas por Vendedor
            </h3>
            <div class="space-y-2">
                @forelse($salesByUser as $index => $user)
                <div class="flex items-center gap-3 p-2 rounded-lg hover:bg-slate-50 transition-colors">
                    <span class="w-6 h-6 rounded-full bg-gradient-to-r from-[#ff7261] to-[#a855f7] text-white text-xs flex items-center justify-center font-bold">{{ $index + 1 }}</span>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-slate-800 truncate">{{ $user['name'] }}</p>
                        <p class="text-xs text-slate-500">{{ $user['count'] }} ventas</p>
                    </div>
                    <span class="text-sm font-bold text-green-600">${{ number_format($user['total'], 0) }}</span>
                </div>
                @empty
                <p class="text-slate-400 text-center py-4">No hay datos disponibles</p>
                @endforelse
            </div>
        </div>

        {{-- Sales by Cash Register --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                Ventas por Caja
            </h3>
            <div class="space-y-2">
                @forelse($salesByCashRegister as $register)
                <div class="flex items-center justify-between p-3 rounded-lg bg-slate-50">
                    <div>
                        <p class="font-medium text-slate-800">{{ $register['name'] }}</p>
                        <p class="text-xs text-slate-500">{{ $register['count'] }} transacciones</p>
                    </div>
                    <span class="text-lg font-bold text-indigo-600">${{ number_format($register['total'], 0) }}</span>
                </div>
                @empty
                <p class="text-slate-400 text-center py-4">No hay datos disponibles</p>
                @endforelse
            </div>
        </div>
    </div>


    {{-- Filters --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
        <div class="flex flex-col gap-4">
            {{-- First Row: Date Range and Search --}}
            <div class="flex flex-col lg:flex-row gap-4">
                <div class="flex flex-wrap gap-3">
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
                    @if($dateRange === 'custom')
                    <input wire:model.live="startDate" type="date" class="px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] sm:text-sm">
                    <input wire:model.live="endDate" type="date" class="px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] sm:text-sm">
                    @endif
                </div>
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input wire:model.live.debounce.300ms="search" type="text" 
                        class="block w-full pl-10 pr-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 placeholder-slate-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] transition-all sm:text-sm" 
                        placeholder="Buscar por factura, cliente...">
                </div>
            </div>
            
            {{-- Second Row: Additional Filters --}}
            <div class="flex flex-wrap gap-3">
                @if($isSuperAdmin)
                <select wire:model.live="selectedBranchId" class="px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] sm:text-sm min-w-[150px]">
                    <option value="">Todas las sucursales</option>
                    @foreach($branches as $branch)
                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
                @endif
                <select wire:model.live="selectedUserId" class="px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] sm:text-sm min-w-[140px]">
                    <option value="">Todos los vendedores</option>
                    @foreach($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
                <select wire:model.live="selectedPaymentMethodId" class="px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] sm:text-sm min-w-[150px]">
                    <option value="">Todas las formas de pago</option>
                    @foreach($paymentMethods as $method)
                    <option value="{{ $method->id }}">{{ $method->name }}</option>
                    @endforeach
                </select>
                <select wire:model.live="selectedCashRegisterId" class="px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] sm:text-sm min-w-[130px]">
                    <option value="">Todas las cajas</option>
                    @foreach($cashRegisters as $register)
                    <option value="{{ $register->id }}">{{ $register->name }}</option>
                    @endforeach
                </select>
                <select wire:model.live="statusFilter" class="px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] sm:text-sm min-w-[120px]">
                    <option value="all">Todos los estados</option>
                    <option value="completed">Completadas</option>
                    <option value="cancelled">Anuladas</option>
                </select>
                @if($search || $selectedUserId || $selectedPaymentMethodId || $selectedCashRegisterId || $statusFilter !== 'all' || ($isSuperAdmin && $selectedBranchId))
                <button wire:click="clearFilters" class="px-3 py-2.5 text-slate-500 hover:text-slate-700 hover:bg-slate-100 rounded-xl transition-colors text-sm font-medium flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    Limpiar
                </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Sales Table --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Factura</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Fecha</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Cliente</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Vendedor</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Forma Pago</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-500 uppercase">Total</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold text-slate-500 uppercase">Estado</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold text-slate-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($sales as $sale)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-4">
                            <div>
                                <p class="font-medium text-slate-800">{{ $sale->invoice_number }}</p>
                                @if($sale->is_electronic && $sale->dian_number)
                                <p class="text-xs text-green-600">FE: {{ $sale->dian_number }}</p>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 text-slate-600">
                            <p>{{ $sale->created_at->format('d/m/Y') }}</p>
                            <p class="text-xs text-slate-400">{{ $sale->created_at->format('H:i') }}</p>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-slate-800">{{ $sale->customer?->name ?? $sale->customer?->business_name ?? 'Consumidor Final' }}</p>
                            @if($sale->customer?->document_number)
                            <p class="text-xs text-slate-500">{{ $sale->customer->document_number }}</p>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-slate-600">{{ $sale->user?->name ?? '-' }}</td>
                        <td class="px-6 py-4">
                            <div class="flex flex-wrap gap-1">
                                @foreach($sale->payments as $payment)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
                                    {{ $payment->paymentMethod?->name ?? 'N/A' }}
                                </span>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right font-bold text-green-600">${{ number_format($sale->total, 0) }}</td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $sale->status === 'completed' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                {{ $sale->status === 'completed' ? 'Completada' : 'Anulada' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <button wire:click="viewSaleDetail({{ $sale->id }})" class="p-2 text-slate-400 hover:text-[#a855f7] hover:bg-[#a855f7]/10 rounded-lg transition-colors" title="Ver Detalle">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center">
                            <svg class="w-12 h-12 text-slate-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            <p class="text-slate-500">No se encontraron ventas</p>
                            <button wire:click="clearFilters" class="mt-2 text-[#ff7261] hover:underline text-sm">Limpiar filtros</button>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if($sales->hasPages())
    <div class="mt-6">
        {{ $sales->links() }}
    </div>
    @endif


    {{-- Sale Detail Modal --}}
    @if($isDetailModalOpen && $selectedSale)
    <div class="relative z-[100]">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="closeDetailModal"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-4xl bg-white rounded-2xl shadow-xl">
                    {{-- Header --}}
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-gradient-to-r from-[#ff7261]/10 to-[#a855f7]/10 rounded-t-2xl">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">Detalle de Venta</h3>
                            <p class="text-sm text-slate-500">{{ $selectedSale->invoice_number }}</p>
                        </div>
                        <div class="flex items-center gap-4">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $selectedSale->status === 'completed' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                {{ $selectedSale->status === 'completed' ? 'Completada' : 'Anulada' }}
                            </span>
                            <button wire:click="closeDetailModal" class="p-2 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-white/50">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Content --}}
                    <div class="px-6 py-4 max-h-[70vh] overflow-y-auto">
                        {{-- Sale Info --}}
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                            <div class="p-3 bg-slate-50 rounded-xl">
                                <p class="text-xs text-slate-500">Fecha</p>
                                <p class="font-medium text-slate-800">{{ $selectedSale->created_at->format('d/m/Y H:i') }}</p>
                            </div>
                            <div class="p-3 bg-slate-50 rounded-xl">
                                <p class="text-xs text-slate-500">Cliente</p>
                                <p class="font-medium text-slate-800">{{ $selectedSale->customer?->name ?? $selectedSale->customer?->business_name ?? 'Consumidor Final' }}</p>
                            </div>
                            <div class="p-3 bg-slate-50 rounded-xl">
                                <p class="text-xs text-slate-500">Vendedor</p>
                                <p class="font-medium text-slate-800">{{ $selectedSale->user?->name ?? '-' }}</p>
                            </div>
                            <div class="p-3 bg-slate-50 rounded-xl">
                                <p class="text-xs text-slate-500">Caja</p>
                                <p class="font-medium text-slate-800">{{ $selectedSale->cashReconciliation?->cashRegister?->name ?? '-' }}</p>
                            </div>
                        </div>

                        @if($selectedSale->is_electronic)
                        <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl">
                            <div class="flex items-center gap-2 mb-2">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span class="font-medium text-green-800">Factura Electrónica Validada</span>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                                <p><span class="text-green-600">CUFE:</span> <span class="text-slate-700 break-all">{{ $selectedSale->cufe }}</span></p>
                                <p><span class="text-green-600">Número DIAN:</span> <span class="text-slate-700">{{ $selectedSale->dian_number }}</span></p>
                            </div>
                        </div>
                        @endif

                        {{-- Items Table --}}
                        <h4 class="font-semibold text-slate-800 mb-3">Productos/Servicios</h4>
                        <div class="border border-slate-200 rounded-xl overflow-hidden mb-6">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Producto</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 uppercase">Cant.</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">P. Unit.</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Desc.</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">IVA</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200">
                                    @foreach($selectedSale->items as $item)
                                    <tr>
                                        <td class="px-4 py-3">
                                            <p class="font-medium text-slate-800">{{ $item->product_name }}</p>
                                            <p class="text-xs text-slate-500">{{ $item->product_sku }}</p>
                                            @if($item->isService())
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-700">Servicio</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-center text-slate-600">{{ $item->quantity }}</td>
                                        <td class="px-4 py-3 text-right text-slate-600">${{ number_format($item->unit_price, 2) }}</td>
                                        <td class="px-4 py-3 text-right text-red-600">
                                            @if($item->discount_amount > 0)
                                            -${{ number_format($item->discount_amount, 2) }}
                                            @else
                                            -
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-right text-slate-600">${{ number_format($item->tax_amount, 2) }}</td>
                                        <td class="px-4 py-3 text-right font-medium text-slate-800">${{ number_format($item->total, 2) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Payments --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h4 class="font-semibold text-slate-800 mb-3">Formas de Pago</h4>
                                <div class="space-y-2">
                                    @foreach($selectedSale->payments as $payment)
                                    <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg">
                                        <span class="text-slate-600">{{ $payment->paymentMethod?->name ?? 'N/A' }}</span>
                                        <span class="font-medium text-slate-800">${{ number_format($payment->amount, 2) }}</span>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            <div>
                                <h4 class="font-semibold text-slate-800 mb-3">Resumen</h4>
                                <div class="space-y-2">
                                    <div class="flex justify-between p-2">
                                        <span class="text-slate-600">Subtotal</span>
                                        <span class="text-slate-800">${{ number_format($selectedSale->subtotal, 2) }}</span>
                                    </div>
                                    <div class="flex justify-between p-2">
                                        <span class="text-slate-600">Impuestos</span>
                                        <span class="text-slate-800">${{ number_format($selectedSale->tax_total, 2) }}</span>
                                    </div>
                                    @if($selectedSale->discount > 0)
                                    <div class="flex justify-between p-2">
                                        <span class="text-slate-600">Descuento</span>
                                        <span class="text-red-600">-${{ number_format($selectedSale->discount, 2) }}</span>
                                    </div>
                                    @endif
                                    <div class="flex justify-between p-3 bg-gradient-to-r from-[#ff7261]/10 to-[#a855f7]/10 rounded-lg">
                                        <span class="font-bold text-slate-800">Total</span>
                                        <span class="font-bold text-lg text-green-600">${{ number_format($selectedSale->total, 2) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end rounded-b-2xl">
                        <button wire:click="closeDetailModal" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

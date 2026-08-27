<div class="p-6">
    <!-- Header -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Reporte de Medios de Pago</h1>
            <p class="text-slate-500 text-sm mt-1">Análisis integral de entradas (ventas y cartera) y salidas (gastos, nómina y proveedores) por método de pago</p>
        </div>
        @if(auth()->user()->hasPermission('reports.export'))
        <a href="{{ route('reports.payment-methods.excel', [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'branch_id' => $selectedBranchId,
            'cash_register_id' => $selectedCashRegisterId,
            'payment_method_id' => $selectedPaymentMethodId,
            'user_id' => $selectedUserId,
            'flow_type' => $flowType,
            'concept_type' => $conceptType,
            'cash_affectation' => $cashAffectation,
        ]) }}"
            class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea] transition shadow-lg shadow-purple-500/25">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            Exportar Excel
        </a>
        @endif
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-9 gap-3">
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Período</label>
                <select wire:model.live="dateRange" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-xs">
                    <option value="today">Hoy</option>
                    <option value="week">Esta semana</option>
                    <option value="month">Este mes</option>
                    <option value="last_month">Mes anterior</option>
                    <option value="quarter">Este trimestre</option>
                    <option value="year">Este año</option>
                    <option value="custom">Personalizado</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Desde</label>
                <input wire:model.live="startDate" type="date" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-xs" @if($dateRange !== 'custom') disabled @endif>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Hasta</label>
                <input wire:model.live="endDate" type="date" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-xs" @if($dateRange !== 'custom') disabled @endif>
            </div>
            @if($isSuperAdmin)
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Sucursal</label>
                <select wire:model.live="selectedBranchId" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-xs">
                    <option value="">Todas</option>
                    @foreach($branches as $branch)
                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Caja</label>
                <select wire:model.live="selectedCashRegisterId" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-xs">
                    <option value="">Todas</option>
                    @foreach($cashRegisters as $register)
                    <option value="{{ $register->id }}">{{ $register->name }}{{ $register->user ? ' - ' . $register->user->name : '' }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Método de Pago</label>
                <select wire:model.live="selectedPaymentMethodId" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-xs">
                    <option value="">Todos</option>
                    @foreach($paymentMethods as $pm)
                    <option value="{{ $pm->id }}">{{ $pm->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Flujo</label>
                <select wire:model.live="flowType" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-xs font-medium text-slate-700">
                    <option value="all">Todos (Ingresos + Egresos)</option>
                    <option value="income">🟢 Solo Entradas (Ingresos)</option>
                    <option value="expense">🔴 Solo Salidas (Egresos)</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Concepto</label>
                <select wire:model.live="conceptType" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-xs font-medium text-slate-700">
                    <option value="all">Todos los Conceptos</option>
                    <option value="sales">Ventas POS</option>
                    <option value="receivables">Cobros Cartera (Clientes)</option>
                    <option value="expenses">Gastos</option>
                    <option value="payrolls">Nómina</option>
                    <option value="payables">Pagos Proveedores (Créditos)</option>
                    <option value="purchases">Compras de Contado</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Afecta Caja</label>
                <select wire:model.live="cashAffectation" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-xs font-medium text-slate-700">
                    <option value="all">Todos los movimientos</option>
                    <option value="with_cash">Afectó Caja</option>
                    <option value="without_cash">Fuera de Caja (No)</option>
                </select>
            </div>
        </div>
        <div class="flex justify-end mt-3">
            <button wire:click="clearFilters" class="px-4 py-1.5 text-xs font-medium text-slate-600 bg-slate-100 rounded-lg hover:bg-slate-200 transition">
                Limpiar Filtros
            </button>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Total Entradas (Ingresos)</p>
                    <p class="text-lg font-bold text-emerald-700">+${{ number_format($summary['grandTotalIncome'], 2) }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-red-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Total Salidas (Egresos)</p>
                    <p class="text-lg font-bold text-red-600">-${{ number_format($summary['grandTotalExpense'], 2) }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl {{ $summary['grandNetTotal'] >= 0 ? 'bg-purple-100' : 'bg-amber-100' }} flex items-center justify-center">
                    <svg class="w-5 h-5 {{ $summary['grandNetTotal'] >= 0 ? 'text-purple-600' : 'text-amber-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Balance Neto</p>
                    <p class="text-lg font-bold {{ $summary['grandNetTotal'] >= 0 ? 'text-purple-700' : 'text-red-600' }}">
                        ${{ number_format($summary['grandNetTotal'], 2) }}
                    </p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Operaciones Totales</p>
                    <p class="text-lg font-bold text-slate-800">{{ number_format($summary['totalTransactions']) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab Buttons -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-2 mb-6">
        <div class="flex flex-wrap gap-1">
            @php
                $tabs = [
                    'summary' => 'Resumen por Método',
                    'detail' => 'Detalle de Movimientos',
                    'by_user' => 'Por Vendedor / Usuario',
                ];
            @endphp
            @foreach($tabs as $key => $label)
            <button wire:click="$set('viewMode', '{{ $key }}')"
                class="px-4 py-2.5 text-sm font-medium rounded-xl transition-all {{ $viewMode === $key ? 'bg-gradient-to-r from-[#ff7261] to-[#a855f7] text-white shadow-lg shadow-purple-500/25' : 'text-slate-600 hover:bg-slate-100' }}">
                {{ $label }}
            </button>
            @endforeach
        </div>
    </div>

    <!-- Content -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">

        {{-- Resumen por Método --}}
        @if($viewMode === 'summary')
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3.5 text-left text-xs font-bold text-slate-600 uppercase">Método de Pago</th>
                        <th class="px-4 py-3.5 text-right text-xs font-bold text-emerald-700 uppercase bg-emerald-50/50">Ventas Directas</th>
                        <th class="px-4 py-3.5 text-right text-xs font-bold text-blue-700 uppercase bg-blue-50/50">Abonos Cartera</th>
                        <th class="px-4 py-3.5 text-right text-xs font-bold text-emerald-800 uppercase bg-emerald-100/40">Total Ingresos (+)</th>
                        <th class="px-4 py-3.5 text-right text-xs font-bold text-red-700 uppercase bg-red-50/50">Gastos</th>
                        <th class="px-4 py-3.5 text-right text-xs font-bold text-purple-700 uppercase bg-purple-50/50">Nómina</th>
                        <th class="px-4 py-3.5 text-right text-xs font-bold text-amber-700 uppercase bg-amber-50/50">Proveedores / Compras</th>
                        <th class="px-4 py-3.5 text-right text-xs font-bold text-red-800 uppercase bg-red-100/40">Total Egresos (-)</th>
                        <th class="px-4 py-3.5 text-right text-xs font-bold text-slate-800 uppercase">Balance Neto</th>
                        <th class="px-4 py-3.5 text-right text-xs font-bold text-slate-600 uppercase">Transacciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($summary['items'] as $item)
                    <tr class="hover:bg-slate-50/60 transition-colors">
                        {{-- Método --}}
                        <td class="px-4 py-3.5">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-gradient-to-r from-[#ff7261] to-[#a855f7] flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                    </svg>
                                </div>
                                <span class="text-sm font-semibold text-slate-800">{{ $item->name }}</span>
                            </div>
                        </td>

                        {{-- Ventas POS con Hover Tooltip --}}
                        <td class="px-4 py-3.5 text-right bg-emerald-50/20">
                            <div class="text-sm font-semibold text-emerald-700">${{ number_format($item->sales_total, 2) }}</div>
                            @if($item->sales_count > 0)
                            <div class="relative inline-block mt-0.5" x-data="{ open: false }">
                                <button @mouseenter="open = true" @mouseleave="open = false"
                                    class="text-[11px] font-medium text-emerald-600 hover:text-emerald-800 transition inline-flex items-center gap-0.5 underline decoration-emerald-300 cursor-pointer">
                                    <span>{{ $item->sales_count }} vts</span>
                                    <svg class="w-2.5 h-2.5 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </button>
                                <div x-show="open" x-cloak
                                    x-transition:enter="transition ease-out duration-150"
                                    x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                                    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                    x-transition:leave="transition ease-in duration-100"
                                    x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                    x-transition:leave-end="opacity-0 translate-y-2 scale-95"
                                    class="absolute z-50 right-0 mt-1.5 w-72 p-3 bg-slate-900/95 backdrop-blur-md text-white rounded-xl shadow-2xl border border-slate-700/50 text-left pointer-events-none">
                                    <div class="flex items-center justify-between border-b border-slate-700/60 pb-1.5 mb-2">
                                        <span class="font-bold text-xs text-emerald-400">Ventas Directas ({{ $item->sales_count }})</span>
                                        <span class="font-bold text-xs text-slate-300">${{ number_format($item->sales_total, 2) }}</span>
                                    </div>
                                    <div class="space-y-1 max-h-48 overflow-hidden">
                                        @foreach(array_slice($item->sales_docs, 0, 6) as $doc)
                                        <div class="flex items-center justify-between text-[11px] bg-slate-800/70 px-2 py-1 rounded-lg">
                                            <div class="truncate max-w-[150px]">
                                                <span class="font-semibold text-white">{{ $doc['doc'] }}</span>
                                                <span class="text-slate-400 block text-[10px] truncate">{{ $doc['third'] }}</span>
                                            </div>
                                            <span class="font-bold text-emerald-400">${{ number_format($doc['amount'], 2) }}</span>
                                        </div>
                                        @endforeach
                                    </div>
                                    @if(count($item->sales_docs) > 6)
                                    <p class="text-[10px] text-slate-400 text-center mt-1.5 pt-1 border-t border-slate-800">+{{ $item->sales_count - 6 }} más en pestaña Detalle</p>
                                    @endif
                                </div>
                            </div>
                            @else
                            <span class="text-[11px] text-slate-400 block">0 vts</span>
                            @endif
                        </td>

                        {{-- Abonos Cartera con Hover Tooltip --}}
                        <td class="px-4 py-3.5 text-right bg-blue-50/20">
                            <div class="text-sm font-semibold text-blue-700">${{ number_format($item->receivables_total, 2) }}</div>
                            @if($item->receivables_count > 0)
                            <div class="relative inline-block mt-0.5" x-data="{ open: false }">
                                <button @mouseenter="open = true" @mouseleave="open = false"
                                    class="text-[11px] font-medium text-blue-600 hover:text-blue-800 transition inline-flex items-center gap-0.5 underline decoration-blue-300 cursor-pointer">
                                    <span>{{ $item->receivables_count }} abonos</span>
                                    <svg class="w-2.5 h-2.5 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </button>
                                <div x-show="open" x-cloak
                                    x-transition:enter="transition ease-out duration-150"
                                    x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                                    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                    x-transition:leave="transition ease-in duration-100"
                                    x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                    x-transition:leave-end="opacity-0 translate-y-2 scale-95"
                                    class="absolute z-50 right-0 mt-1.5 w-72 p-3 bg-slate-900/95 backdrop-blur-md text-white rounded-xl shadow-2xl border border-slate-700/50 text-left pointer-events-none">
                                    <div class="flex items-center justify-between border-b border-slate-700/60 pb-1.5 mb-2">
                                        <span class="font-bold text-xs text-blue-400">Abonos de Cartera ({{ $item->receivables_count }})</span>
                                        <span class="font-bold text-xs text-slate-300">${{ number_format($item->receivables_total, 2) }}</span>
                                    </div>
                                    <div class="space-y-1 max-h-48 overflow-hidden">
                                        @foreach(array_slice($item->receivables_docs, 0, 6) as $doc)
                                        <div class="flex items-center justify-between text-[11px] bg-slate-800/70 px-2 py-1 rounded-lg">
                                            <div class="truncate max-w-[150px]">
                                                <span class="font-semibold text-white">{{ $doc['doc'] }}</span>
                                                <span class="text-slate-400 block text-[10px] truncate">{{ $doc['third'] }}</span>
                                            </div>
                                            <span class="font-bold text-blue-400">${{ number_format($doc['amount'], 2) }}</span>
                                        </div>
                                        @endforeach
                                    </div>
                                    @if(count($item->receivables_docs) > 6)
                                    <p class="text-[10px] text-slate-400 text-center mt-1.5 pt-1 border-t border-slate-800">+{{ $item->receivables_count - 6 }} más en pestaña Detalle</p>
                                    @endif
                                </div>
                            </div>
                            @else
                            <span class="text-[11px] text-slate-400 block">0 abonos</span>
                            @endif
                        </td>

                        {{-- Total Ingresos --}}
                        <td class="px-4 py-3.5 text-right bg-emerald-100/30">
                            <span class="text-sm font-bold text-emerald-800">+${{ number_format($item->total_income, 2) }}</span>
                        </td>

                        {{-- Gastos con Hover Tooltip --}}
                        <td class="px-4 py-3.5 text-right bg-red-50/20">
                            <div class="text-sm font-semibold text-red-600">${{ number_format($item->expenses_total, 2) }}</div>
                            @if($item->expenses_count > 0)
                            <div class="relative inline-block mt-0.5" x-data="{ open: false }">
                                <button @mouseenter="open = true" @mouseleave="open = false"
                                    class="text-[11px] font-medium text-red-500 hover:text-red-700 transition inline-flex items-center gap-0.5 underline decoration-red-300 cursor-pointer">
                                    <span>{{ $item->expenses_count }} gastos</span>
                                    <svg class="w-2.5 h-2.5 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </button>
                                <div x-show="open" x-cloak
                                    x-transition:enter="transition ease-out duration-150"
                                    x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                                    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                    x-transition:leave="transition ease-in duration-100"
                                    x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                    x-transition:leave-end="opacity-0 translate-y-2 scale-95"
                                    class="absolute z-50 right-0 mt-1.5 w-72 p-3 bg-slate-900/95 backdrop-blur-md text-white rounded-xl shadow-2xl border border-slate-700/50 text-left pointer-events-none">
                                    <div class="flex items-center justify-between border-b border-slate-700/60 pb-1.5 mb-2">
                                        <span class="font-bold text-xs text-red-400">Gastos Registrados ({{ $item->expenses_count }})</span>
                                        <span class="font-bold text-xs text-slate-300">${{ number_format($item->expenses_total, 2) }}</span>
                                    </div>
                                    <div class="space-y-1 max-h-48 overflow-hidden">
                                        @foreach(array_slice($item->expenses_docs, 0, 6) as $doc)
                                        <div class="flex items-center justify-between text-[11px] bg-slate-800/70 px-2 py-1 rounded-lg">
                                            <div class="truncate max-w-[150px]">
                                                <span class="font-semibold text-white">{{ $doc['doc'] }}</span>
                                                <span class="text-slate-400 block text-[10px] truncate">{{ $doc['third'] }}</span>
                                            </div>
                                            <span class="font-bold text-red-400">${{ number_format($doc['amount'], 2) }}</span>
                                        </div>
                                        @endforeach
                                    </div>
                                    @if(count($item->expenses_docs) > 6)
                                    <p class="text-[10px] text-slate-400 text-center mt-1.5 pt-1 border-t border-slate-800">+{{ $item->expenses_count - 6 }} más en pestaña Detalle</p>
                                    @endif
                                </div>
                            </div>
                            @else
                            <span class="text-[11px] text-slate-400 block">0</span>
                            @endif
                        </td>

                        {{-- Nómina con Hover Tooltip --}}
                        <td class="px-4 py-3.5 text-right bg-purple-50/20">
                            <div class="text-sm font-semibold text-purple-700">${{ number_format($item->payrolls_total, 2) }}</div>
                            @if($item->payrolls_count > 0)
                            <div class="relative inline-block mt-0.5" x-data="{ open: false }">
                                <button @mouseenter="open = true" @mouseleave="open = false"
                                    class="text-[11px] font-medium text-purple-600 hover:text-purple-800 transition inline-flex items-center gap-0.5 underline decoration-purple-300 cursor-pointer">
                                    <span>{{ $item->payrolls_count }} pagos</span>
                                    <svg class="w-2.5 h-2.5 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </button>
                                <div x-show="open" x-cloak
                                    x-transition:enter="transition ease-out duration-150"
                                    x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                                    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                    x-transition:leave="transition ease-in duration-100"
                                    x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                    x-transition:leave-end="opacity-0 translate-y-2 scale-95"
                                    class="absolute z-50 right-0 mt-1.5 w-72 p-3 bg-slate-900/95 backdrop-blur-md text-white rounded-xl shadow-2xl border border-slate-700/50 text-left pointer-events-none">
                                    <div class="flex items-center justify-between border-b border-slate-700/60 pb-1.5 mb-2">
                                        <span class="font-bold text-xs text-purple-400">Pagos de Nómina ({{ $item->payrolls_count }})</span>
                                        <span class="font-bold text-xs text-slate-300">${{ number_format($item->payrolls_total, 2) }}</span>
                                    </div>
                                    <div class="space-y-1 max-h-48 overflow-hidden">
                                        @foreach(array_slice($item->payrolls_docs, 0, 6) as $doc)
                                        <div class="flex items-center justify-between text-[11px] bg-slate-800/70 px-2 py-1 rounded-lg">
                                            <div class="truncate max-w-[150px]">
                                                <span class="font-semibold text-white">{{ $doc['doc'] }}</span>
                                                <span class="text-slate-400 block text-[10px] truncate">{{ $doc['third'] }}</span>
                                            </div>
                                            <span class="font-bold text-purple-400">${{ number_format($doc['amount'], 2) }}</span>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            @else
                            <span class="text-[11px] text-slate-400 block">0</span>
                            @endif
                        </td>

                        {{-- Proveedores / Compras con Hover Tooltip --}}
                        <td class="px-4 py-3.5 text-right bg-amber-50/20">
                            @php $provTotal = $item->payables_total + $item->purchases_total; @endphp
                            <div class="text-sm font-semibold text-amber-700">${{ number_format($provTotal, 2) }}</div>
                            @php $provCount = $item->payables_count + $item->purchases_count; @endphp
                            @if($provCount > 0)
                            <div class="relative inline-block mt-0.5" x-data="{ open: false }">
                                <button @mouseenter="open = true" @mouseleave="open = false"
                                    class="text-[11px] font-medium text-amber-600 hover:text-amber-800 transition inline-flex items-center gap-0.5 underline decoration-amber-300 cursor-pointer">
                                    <span>{{ $provCount }} pagos</span>
                                    <svg class="w-2.5 h-2.5 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </button>
                                <div x-show="open" x-cloak
                                    x-transition:enter="transition ease-out duration-150"
                                    x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                                    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                    x-transition:leave="transition ease-in duration-100"
                                    x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                    x-transition:leave-end="opacity-0 translate-y-2 scale-95"
                                    class="absolute z-50 right-0 mt-1.5 w-72 p-3 bg-slate-900/95 backdrop-blur-md text-white rounded-xl shadow-2xl border border-slate-700/50 text-left pointer-events-none">
                                    <div class="flex items-center justify-between border-b border-slate-700/60 pb-1.5 mb-2">
                                        <span class="font-bold text-xs text-amber-400">Proveedores y Compras ({{ $provCount }})</span>
                                        <span class="font-bold text-xs text-slate-300">${{ number_format($provTotal, 2) }}</span>
                                    </div>
                                    <div class="space-y-1 max-h-48 overflow-hidden">
                                        @foreach(array_slice(array_merge($item->payables_docs, $item->purchases_docs), 0, 6) as $doc)
                                        <div class="flex items-center justify-between text-[11px] bg-slate-800/70 px-2 py-1 rounded-lg">
                                            <div class="truncate max-w-[150px]">
                                                <span class="font-semibold text-white">{{ $doc['doc'] }}</span>
                                                <span class="text-slate-400 block text-[10px] truncate">{{ $doc['third'] }}</span>
                                            </div>
                                            <span class="font-bold text-amber-400">${{ number_format($doc['amount'], 2) }}</span>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            @else
                            <span class="text-[11px] text-slate-400 block">0</span>
                            @endif
                        </td>

                        {{-- Total Egresos --}}
                        <td class="px-4 py-3.5 text-right bg-red-100/30">
                            <span class="text-sm font-bold text-red-700">-${{ number_format($item->total_expense, 2) }}</span>
                        </td>

                        {{-- Balance Neto --}}
                        <td class="px-4 py-3.5 text-right font-bold {{ $item->net_total >= 0 ? 'text-purple-700' : 'text-red-600' }}">
                            ${{ number_format($item->net_total, 2) }}
                        </td>

                        {{-- Transacciones --}}
                        <td class="px-4 py-3.5 text-right text-sm font-medium text-slate-700">
                            {{ number_format($item->transaction_count) }}
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="10" class="px-4 py-8 text-center text-slate-400">No hay datos para mostrar con los filtros seleccionados</td></tr>
                    @endforelse
                </tbody>
                @if(count($summary['items']) > 0)
                <tfoot class="bg-slate-50 border-t-2 border-slate-300 font-bold">
                    <tr>
                        <td class="px-4 py-3.5 text-sm text-slate-800">Total General</td>
                        <td class="px-4 py-3.5 text-sm text-right text-emerald-700">${{ number_format($summary['totalSales'], 2) }}</td>
                        <td class="px-4 py-3.5 text-sm text-right text-blue-700">${{ number_format($summary['totalReceivables'], 2) }}</td>
                        <td class="px-4 py-3.5 text-sm text-right text-emerald-800 bg-emerald-100/40">+${{ number_format($summary['grandTotalIncome'], 2) }}</td>
                        <td class="px-4 py-3.5 text-sm text-right text-red-600">${{ number_format($summary['totalExpenses'], 2) }}</td>
                        <td class="px-4 py-3.5 text-sm text-right text-purple-700">${{ number_format($summary['totalPayrolls'], 2) }}</td>
                        <td class="px-4 py-3.5 text-sm text-right text-amber-700">${{ number_format($summary['totalPayables'] + $summary['totalPurchases'], 2) }}</td>
                        <td class="px-4 py-3.5 text-sm text-right text-red-700 bg-red-100/40">-${{ number_format($summary['grandTotalExpense'], 2) }}</td>
                        <td class="px-4 py-3.5 text-sm text-right {{ $summary['grandNetTotal'] >= 0 ? 'text-purple-700' : 'text-red-600' }}">
                            ${{ number_format($summary['grandNetTotal'], 2) }}
                        </td>
                        <td class="px-4 py-3.5 text-sm text-right text-slate-800">{{ number_format($summary['totalTransactions']) }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
        @endif

        {{-- Detalle de Movimientos --}}
        @if($viewMode === 'detail')
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Concepto</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Documento</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Tercero / Cliente</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Fecha</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Método</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Registrado por</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Sucursal</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 uppercase">Afectó Caja</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Monto</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($detailData as $row)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-4 py-3">
                            @if($row->concept_type === 'sales')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                                Venta POS
                            </span>
                            @elseif($row->concept_type === 'receivables')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                                Cobro Cartera
                            </span>
                            @elseif($row->concept_type === 'expenses')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                                Gasto
                            </span>
                            @elseif($row->concept_type === 'payrolls')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-purple-100 text-purple-800">
                                Nómina
                            </span>
                            @elseif($row->concept_type === 'payables')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                                Pago Prov.
                            </span>
                            @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-pink-100 text-pink-800">
                                {{ $row->operation_label }}
                            </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm font-semibold text-slate-800">{{ $row->document_number }}</td>
                        <td class="px-4 py-3 text-sm text-slate-700 max-w-xs truncate">{{ $row->third_party_name ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ \Carbon\Carbon::parse($row->payment_date)->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-700">
                                {{ $row->payment_method_name }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $row->user_name ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $row->branch_name ?? '-' }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($row->affects_cash)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">Sí</span>
                            @else
                            <span class="text-xs text-slate-400 font-medium">No</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-right font-bold {{ $row->flow_type === 'income' ? 'text-emerald-700' : 'text-red-600' }}">
                            {{ $row->flow_type === 'income' ? '+' : '-' }}${{ number_format($row->amount, 2) }}
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="px-4 py-8 text-center text-slate-400">No hay movimientos para mostrar con los filtros seleccionados</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($detailData && $detailData->hasPages())
        <div class="px-4 py-3 border-t border-slate-200">
            {{ $detailData->links() }}
        </div>
        @endif
        @endif

        {{-- Por Vendedor / Usuario --}}
        @if($viewMode === 'by_user')
        <div class="divide-y divide-slate-200">
            @forelse($byUserData as $userName => $methods)
            <div class="p-4">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                        <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        {{ $userName }}
                    </h3>
                    <div class="flex gap-2">
                        <span class="text-xs font-semibold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-lg">
                            Ingresos: +${{ number_format(collect($methods)->sum('income_total'), 2) }}
                        </span>
                        <span class="text-xs font-semibold text-red-600 bg-red-50 px-2.5 py-1 rounded-lg">
                            Egresos: -${{ number_format(collect($methods)->sum('expense_total'), 2) }}
                        </span>
                        <span class="text-xs font-bold text-slate-800 bg-slate-100 px-2.5 py-1 rounded-lg">
                            Neto: ${{ number_format(collect($methods)->sum('net_total'), 2) }}
                        </span>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach($methods as $method)
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                        <div class="flex items-center justify-between mb-1">
                            <p class="text-sm font-semibold text-slate-700">{{ $method['payment_method_name'] }}</p>
                            <p class="text-sm font-bold {{ $method['net_total'] >= 0 ? 'text-purple-700' : 'text-red-600' }}">
                                ${{ number_format($method['net_total'], 2) }}
                            </p>
                        </div>
                        <div class="flex justify-between text-xs text-slate-400">
                            <span class="text-emerald-600">Entradas: +${{ number_format($method['income_total'], 2) }}</span>
                            <span class="text-red-500">Salidas: -${{ number_format($method['expense_total'], 2) }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @empty
            <div class="px-4 py-8 text-center text-slate-400">No hay datos para mostrar</div>
            @endforelse
        </div>
        @endif
    </div>
</div>

<div class="p-6">
    <!-- Header -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Reporte de Medios de Pago</h1>
            <p class="text-slate-500 text-sm mt-1">Análisis consolidado de ventas directas y cobros de cartera por método de pago</p>
        </div>
        @if(auth()->user()->hasPermission('reports.export'))
        <a href="{{ route('reports.payment-methods.excel', [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'branch_id' => $selectedBranchId,
            'cash_register_id' => $selectedCashRegisterId,
            'payment_method_id' => $selectedPaymentMethodId,
            'user_id' => $selectedUserId,
            'operation_type' => $operationType,
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
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-8 gap-3">
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
                <label class="block text-xs font-medium text-slate-500 mb-1">Tipo Operación</label>
                <select wire:model.live="operationType" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-xs font-medium text-slate-700">
                    <option value="all">Todos (Ventas + Abonos)</option>
                    <option value="sales">Solo Ventas POS</option>
                    <option value="credits">Solo Abonos Cartera</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Afecta Caja</label>
                <select wire:model.live="cashAffectation" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-xs font-medium text-slate-700">
                    <option value="all">Todos los recaudos</option>
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
                <div class="w-10 h-10 rounded-xl bg-purple-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Total Recaudado</p>
                    <p class="text-lg font-bold text-slate-800">${{ number_format($summary['grandTotal'], 2) }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Ventas Directas POS</p>
                    <p class="text-lg font-bold text-emerald-700">${{ number_format($summary['totalSales'], 2) }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Abonos a Créditos</p>
                    <p class="text-lg font-bold text-blue-700">${{ number_format($summary['totalCredits'], 2) }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-orange-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Transacciones Totales</p>
                    <p class="text-lg font-bold text-slate-800">{{ number_format($summary['transactionCount']) }}</p>
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
                    'detail' => 'Detalle de Pagos',
                    'by_user' => 'Por Vendedor',
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
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Método de Pago</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Ventas Directas</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Abonos Créditos</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Transacciones</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Total Recaudado</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">% del Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($summary['items'] as $item)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-gradient-to-r from-[#ff7261] to-[#a855f7] flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                    </svg>
                                </div>
                                <span class="text-sm font-semibold text-slate-800">{{ $item->name }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm text-right text-slate-600">
                            ${{ number_format($item->sales_total, 2) }}
                            <span class="block text-xs text-slate-400">({{ $item->sales_count }} vts)</span>
                        </td>
                        <td class="px-4 py-3 text-sm text-right text-blue-600 font-medium">
                            ${{ number_format($item->credits_total, 2) }}
                            <span class="block text-xs text-slate-400">({{ $item->credits_count }} abonos)</span>
                        </td>
                        <td class="px-4 py-3 text-sm text-right font-medium text-slate-700">{{ number_format($item->transaction_count) }}</td>
                        <td class="px-4 py-3 text-sm text-right font-bold text-slate-900">${{ number_format($item->total, 2) }}</td>
                        <td class="px-4 py-3 text-right">
                            @php $pct = $summary['grandTotal'] > 0 ? ($item->total / $summary['grandTotal']) * 100 : 0; @endphp
                            <div class="flex items-center justify-end gap-2">
                                <div class="w-16 bg-slate-100 rounded-full h-2">
                                    <div class="bg-gradient-to-r from-[#ff7261] to-[#a855f7] h-2 rounded-full" style="width: {{ min($pct, 100) }}%"></div>
                                </div>
                                <span class="text-sm font-semibold text-slate-600">{{ number_format($pct, 1) }}%</span>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">No hay datos para mostrar</td></tr>
                    @endforelse
                </tbody>
                @if(count($summary['items']) > 0)
                <tfoot class="bg-slate-50 border-t-2 border-slate-200">
                    <tr>
                        <td class="px-4 py-3 text-sm font-bold text-slate-800">Total General</td>
                        <td class="px-4 py-3 text-sm text-right font-bold text-slate-800">${{ number_format($summary['totalSales'], 2) }}</td>
                        <td class="px-4 py-3 text-sm text-right font-bold text-blue-700">${{ number_format($summary['totalCredits'], 2) }}</td>
                        <td class="px-4 py-3 text-sm text-right font-bold text-slate-800">{{ number_format($summary['transactionCount']) }}</td>
                        <td class="px-4 py-3 text-sm text-right font-bold text-slate-900">${{ number_format($summary['grandTotal'], 2) }}</td>
                        <td class="px-4 py-3 text-sm text-right font-bold text-slate-800">100%</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
        @endif

        {{-- Detalle de Pagos --}}
        @if($viewMode === 'detail')
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Tipo</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Documento</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Cliente / Tercero</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Fecha</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Método</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Vendedor</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Sucursal</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 uppercase">Afectó Caja</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Monto Pagado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($detailData as $row)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-4 py-3">
                            @if($row->operation_type === 'sale')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                                Venta POS
                            </span>
                            @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                                Cobro Cartera
                            </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm font-semibold text-slate-800">{{ $row->document_number }}</td>
                        <td class="px-4 py-3 text-sm text-slate-700">{{ $row->customer_name }}</td>
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
                        <td class="px-4 py-3 text-sm text-right font-bold text-slate-900">${{ number_format($row->amount, 2) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="px-4 py-8 text-center text-slate-400">No hay datos para mostrar</td></tr>
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

        {{-- Por Vendedor --}}
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
                    <span class="text-xs font-semibold text-slate-600 bg-slate-100 px-2.5 py-1 rounded-lg">
                        Total: ${{ number_format(collect($methods)->sum('total'), 2) }}
                    </span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach($methods as $method)
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                        <div class="flex items-center justify-between mb-1">
                            <p class="text-sm font-semibold text-slate-700">{{ $method['payment_method_name'] }}</p>
                            <p class="text-sm font-bold text-slate-800">${{ number_format($method['total'], 2) }}</p>
                        </div>
                        <div class="flex justify-between text-xs text-slate-400">
                            <span>Ventas: ${{ number_format($method['sales_total'], 2) }} ({{ $method['sales_count'] }})</span>
                            <span>Abonos: ${{ number_format($method['credits_total'], 2) }} ({{ $method['credits_count'] }})</span>
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

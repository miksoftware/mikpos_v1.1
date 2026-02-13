<div class="space-y-6">
    <x-toast />

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Reporte de Créditos</h1>
            <p class="text-slate-500 mt-1">Análisis de cuentas por pagar y por cobrar</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
        <div class="flex flex-col sm:flex-row gap-3 items-end flex-wrap">
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Período</label>
                <select wire:model.live="dateRange" class="px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] sm:text-sm">
                    <option value="all">Todo</option>
                    <option value="today">Hoy</option>
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
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Tipo</label>
                <select wire:model.live="creditType" class="px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] sm:text-sm">
                    <option value="">Todos</option>
                    <option value="payable">Por Pagar</option>
                    <option value="receivable">Por Cobrar</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Estado</label>
                <select wire:model.live="paymentStatus" class="px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] sm:text-sm">
                    <option value="">Todos</option>
                    <option value="pending">Pendiente</option>
                    <option value="partial">Parcial</option>
                    <option value="paid">Pagado</option>
                </select>
            </div>
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

    {{-- View Mode Tabs --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-1.5 flex flex-wrap gap-1">
        @foreach([
            'summary' => 'Resumen',
            'by_customer' => 'Por Cliente',
            'by_supplier' => 'Por Proveedor',
            'by_date' => 'Por Fecha',
            'payments' => 'Abonos/Pagos',
        ] as $mode => $label)
        <button wire:click="$set('viewMode', '{{ $mode }}')"
            class="px-4 py-2 text-sm font-medium rounded-xl transition-all {{ $viewMode === $mode ? 'bg-gradient-to-r from-[#ff7261] to-[#a855f7] text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }}">
            {{ $label }}
        </button>
        @endforeach
    </div>

    @if($viewMode === 'summary')
    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path></svg>
                </div>
                <div>
                    <p class="text-xs text-slate-500 uppercase font-semibold">Debemos</p>
                    <p class="text-xl font-bold text-red-600">${{ number_format($summary['total_payable'], 2) }}</p>
                    <p class="text-xs text-slate-400">{{ $payableSummary['count'] }} crédito(s)</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                </div>
                <div>
                    <p class="text-xs text-slate-500 uppercase font-semibold">Nos Deben</p>
                    <p class="text-xl font-bold text-blue-600">${{ number_format($summary['total_receivable'], 2) }}</p>
                    <p class="text-xs text-slate-400">{{ $receivableSummary['count'] }} crédito(s)</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full {{ $summary['net_balance'] >= 0 ? 'bg-emerald-100' : 'bg-amber-100' }} flex items-center justify-center">
                    <svg class="w-5 h-5 {{ $summary['net_balance'] >= 0 ? 'text-emerald-600' : 'text-amber-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"></path></svg>
                </div>
                <div>
                    <p class="text-xs text-slate-500 uppercase font-semibold">Balance Neto</p>
                    <p class="text-xl font-bold {{ $summary['net_balance'] >= 0 ? 'text-emerald-600' : 'text-amber-600' }}">${{ number_format($summary['net_balance'], 2) }}</p>
                    <p class="text-xs text-slate-400">{{ $summary['net_balance'] >= 0 ? 'A favor' : 'En contra' }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                </div>
                <div>
                    <p class="text-xs text-slate-500 uppercase font-semibold">Total Pagado</p>
                    <p class="text-xl font-bold text-green-600">${{ number_format($summary['total_payments_made'], 2) }}</p>
                    <p class="text-xs text-slate-400">{{ $summary['total_credits'] }} crédito(s) total</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Status Breakdown --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Payable Status --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path></svg>
                Cuentas por Pagar (Proveedores)
            </h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between p-3 bg-slate-50 rounded-xl">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-red-500"></span>
                        <span class="text-sm text-slate-700">Pendientes</span>
                    </div>
                    <div class="text-right">
                        <span class="text-sm font-bold text-slate-800">{{ $payableSummary['pending_count'] }}</span>
                    </div>
                </div>
                <div class="flex items-center justify-between p-3 bg-slate-50 rounded-xl">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-amber-500"></span>
                        <span class="text-sm text-slate-700">Parciales</span>
                    </div>
                    <div class="text-right">
                        <span class="text-sm font-bold text-slate-800">{{ $payableSummary['partial_count'] }}</span>
                    </div>
                </div>
                <div class="flex items-center justify-between p-3 bg-slate-50 rounded-xl">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-green-500"></span>
                        <span class="text-sm text-slate-700">Pagados</span>
                    </div>
                    <div class="text-right">
                        <span class="text-sm font-bold text-slate-800">{{ $payableSummary['paid_count'] }}</span>
                    </div>
                </div>
                <div class="pt-3 border-t border-slate-200">
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">Total créditos:</span>
                        <span class="font-semibold">${{ number_format($payableSummary['total_credit'], 2) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">Pagado:</span>
                        <span class="font-semibold text-green-600">${{ number_format($payableSummary['total_paid'], 2) }}</span>
                    </div>
                    <div class="flex justify-between text-sm font-bold">
                        <span class="text-red-600">Pendiente:</span>
                        <span class="text-red-600">${{ number_format($payableSummary['total_remaining'], 2) }}</span>
                    </div>
                    @if($payableSummary['total_credit'] > 0)
                    <div class="h-2 bg-slate-200 rounded-full mt-2 overflow-hidden">
                        <div class="bg-green-500 h-full rounded-full" style="width: {{ min(($payableSummary['total_paid'] / $payableSummary['total_credit']) * 100, 100) }}%"></div>
                    </div>
                    <p class="text-xs text-slate-400 mt-1 text-right">{{ number_format(($payableSummary['total_paid'] / $payableSummary['total_credit']) * 100, 1) }}% pagado</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Receivable Status --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                Cuentas por Cobrar (Clientes)
            </h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between p-3 bg-slate-50 rounded-xl">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-red-500"></span>
                        <span class="text-sm text-slate-700">Pendientes</span>
                    </div>
                    <div class="text-right">
                        <span class="text-sm font-bold text-slate-800">{{ $receivableSummary['pending_count'] }}</span>
                    </div>
                </div>
                <div class="flex items-center justify-between p-3 bg-slate-50 rounded-xl">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-amber-500"></span>
                        <span class="text-sm text-slate-700">Parciales</span>
                    </div>
                    <div class="text-right">
                        <span class="text-sm font-bold text-slate-800">{{ $receivableSummary['partial_count'] }}</span>
                    </div>
                </div>
                <div class="flex items-center justify-between p-3 bg-slate-50 rounded-xl">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-green-500"></span>
                        <span class="text-sm text-slate-700">Cobrados</span>
                    </div>
                    <div class="text-right">
                        <span class="text-sm font-bold text-slate-800">{{ $receivableSummary['paid_count'] }}</span>
                    </div>
                </div>
                <div class="pt-3 border-t border-slate-200">
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">Total créditos:</span>
                        <span class="font-semibold">${{ number_format($receivableSummary['total_credit'], 2) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">Cobrado:</span>
                        <span class="font-semibold text-green-600">${{ number_format($receivableSummary['total_paid'], 2) }}</span>
                    </div>
                    <div class="flex justify-between text-sm font-bold">
                        <span class="text-blue-600">Pendiente:</span>
                        <span class="text-blue-600">${{ number_format($receivableSummary['total_remaining'], 2) }}</span>
                    </div>
                    @if($receivableSummary['total_credit'] > 0)
                    <div class="h-2 bg-slate-200 rounded-full mt-2 overflow-hidden">
                        <div class="bg-blue-500 h-full rounded-full" style="width: {{ min(($receivableSummary['total_paid'] / $receivableSummary['total_credit']) * 100, 100) }}%"></div>
                    </div>
                    <p class="text-xs text-slate-400 mt-1 text-right">{{ number_format(($receivableSummary['total_paid'] / $receivableSummary['total_credit']) * 100, 1) }}% cobrado</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Charts Row: Top Debtors + Top Creditors --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Top Debtors (Clientes que nos deben) --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                Top Clientes Deudores
            </h3>
            <div class="space-y-2 max-h-[320px] overflow-y-auto">
                @php $maxDebtor = collect($topDebtors)->max('remaining') ?: 1; @endphp
                @forelse($topDebtors as $debtor)
                <div class="p-3 rounded-lg bg-slate-50">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-sm font-medium text-slate-700 truncate">{{ $debtor['name'] }}</span>
                        <span class="text-sm font-bold text-blue-600 ml-2">${{ number_format($debtor['remaining'], 0) }}</span>
                    </div>
                    <div class="flex justify-between text-xs text-slate-500">
                        <span>{{ $debtor['count'] }} crédito(s) · Total: ${{ number_format($debtor['total'], 0) }}</span>
                        <span>Pagado: ${{ number_format($debtor['paid'], 0) }}</span>
                    </div>
                    <div class="h-1.5 bg-slate-200 rounded-full mt-1.5 overflow-hidden">
                        <div class="bg-blue-400 rounded-full h-full" style="width: {{ ($debtor['remaining'] / $maxDebtor) * 100 }}%"></div>
                    </div>
                </div>
                @empty
                <p class="text-slate-400 text-center py-8 text-sm">No hay clientes deudores</p>
                @endforelse
            </div>
        </div>

        {{-- Top Creditors (Proveedores a quienes debemos) --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                Top Proveedores Acreedores
            </h3>
            <div class="space-y-2 max-h-[320px] overflow-y-auto">
                @php $maxCreditor = collect($topCreditors)->max('remaining') ?: 1; @endphp
                @forelse($topCreditors as $creditor)
                <div class="p-3 rounded-lg bg-slate-50">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-sm font-medium text-slate-700 truncate">{{ $creditor['name'] }}</span>
                        <span class="text-sm font-bold text-red-600 ml-2">${{ number_format($creditor['remaining'], 0) }}</span>
                    </div>
                    <div class="flex justify-between text-xs text-slate-500">
                        <span>{{ $creditor['count'] }} crédito(s) · Total: ${{ number_format($creditor['total'], 0) }}</span>
                        <span>Pagado: ${{ number_format($creditor['paid'], 0) }}</span>
                    </div>
                    <div class="h-1.5 bg-slate-200 rounded-full mt-1.5 overflow-hidden">
                        <div class="bg-red-400 rounded-full h-full" style="width: {{ ($creditor['remaining'] / $maxCreditor) * 100 }}%"></div>
                    </div>
                </div>
                @empty
                <p class="text-slate-400 text-center py-8 text-sm">No hay proveedores acreedores</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Charts Row: Aging + Payments by Method --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Aging Analysis --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Antigüedad de Deudas
            </h3>
            @php $agingColors = ['bg-green-400', 'bg-amber-400', 'bg-orange-400', 'bg-red-500']; @endphp
            <div class="space-y-4">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase mb-2">Por Pagar (Proveedores)</p>
                    @php $maxAgingP = collect($agingPayable)->max('amount') ?: 1; @endphp
                    @foreach($agingPayable as $i => $aging)
                    <div class="flex items-center gap-3 mb-2">
                        <span class="text-xs text-slate-600 w-20">{{ $aging['label'] }}</span>
                        <div class="flex-1 h-4 bg-slate-100 rounded-full overflow-hidden">
                            <div class="{{ $agingColors[$i] }} h-full rounded-full" style="width: {{ $maxAgingP > 0 ? ($aging['amount'] / $maxAgingP) * 100 : 0 }}%"></div>
                        </div>
                        <span class="text-xs font-semibold text-slate-700 w-24 text-right">${{ number_format($aging['amount'], 0) }} ({{ $aging['count'] }})</span>
                    </div>
                    @endforeach
                </div>
                <div class="border-t border-slate-200 pt-4">
                    <p class="text-xs font-semibold text-slate-500 uppercase mb-2">Por Cobrar (Clientes)</p>
                    @php $maxAgingR = collect($agingReceivable)->max('amount') ?: 1; @endphp
                    @foreach($agingReceivable as $i => $aging)
                    <div class="flex items-center gap-3 mb-2">
                        <span class="text-xs text-slate-600 w-20">{{ $aging['label'] }}</span>
                        <div class="flex-1 h-4 bg-slate-100 rounded-full overflow-hidden">
                            <div class="{{ $agingColors[$i] }} h-full rounded-full" style="width: {{ $maxAgingR > 0 ? ($aging['amount'] / $maxAgingR) * 100 : 0 }}%"></div>
                        </div>
                        <span class="text-xs font-semibold text-slate-700 w-24 text-right">${{ number_format($aging['amount'], 0) }} ({{ $aging['count'] }})</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Payments by Method --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-[#a855f7]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                Pagos por Método
            </h3>
            <div class="space-y-3 max-h-[320px] overflow-y-auto">
                @php
                    $maxPM = collect($paymentsByMethod)->max('total') ?: 1;
                    $pmColors = ['bg-purple-500', 'bg-blue-500', 'bg-emerald-500', 'bg-amber-500', 'bg-red-500', 'bg-indigo-500'];
                @endphp
                @forelse($paymentsByMethod as $i => $pm)
                <div class="p-3 rounded-lg bg-slate-50">
                    <div class="flex items-center justify-between mb-1">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full {{ $pmColors[$i % count($pmColors)] }}"></div>
                            <span class="text-sm font-medium text-slate-700">{{ $pm['name'] }}</span>
                        </div>
                        <span class="text-sm font-bold text-slate-800">${{ number_format($pm['total'], 0) }}</span>
                    </div>
                    <div class="flex justify-between text-xs text-slate-500">
                        <span>{{ $pm['count'] }} pago(s)</span>
                        <span>Prov: ${{ number_format($pm['payable'], 0) }} · Cli: ${{ number_format($pm['receivable'], 0) }}</span>
                    </div>
                    <div class="h-1.5 bg-slate-200 rounded-full mt-1.5 overflow-hidden">
                        <div class="{{ $pmColors[$i % count($pmColors)] }} rounded-full h-full" style="width: {{ ($pm['total'] / $maxPM) * 100 }}%"></div>
                    </div>
                </div>
                @empty
                <p class="text-slate-400 text-center py-8 text-sm">No hay pagos registrados</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Monthly Payments Trend --}}
    @if(count($monthlyPayments) > 0)
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
            Tendencia Mensual de Pagos
        </h3>
        <div class="space-y-2 max-h-[300px] overflow-y-auto">
            @php $maxMonthly = collect($monthlyPayments)->max('total') ?: 1; @endphp
            @foreach($monthlyPayments as $month)
            <div>
                <div class="flex justify-between text-xs mb-1">
                    <span class="text-slate-600 font-medium">{{ $month['label'] }}</span>
                    <div class="flex gap-3">
                        <span class="text-red-500">Prov: ${{ number_format($month['payable'], 0) }}</span>
                        <span class="text-blue-500">Cli: ${{ number_format($month['receivable'], 0) }}</span>
                        <span class="text-slate-700 font-semibold">Total: ${{ number_format($month['total'], 0) }}</span>
                    </div>
                </div>
                <div class="flex gap-0.5 h-3">
                    @if($month['payable'] > 0)
                    <div class="bg-red-400 rounded-l-full" style="width: {{ ($month['payable'] / $maxMonthly) * 100 }}%"></div>
                    @endif
                    @if($month['receivable'] > 0)
                    <div class="bg-blue-400 {{ $month['payable'] == 0 ? 'rounded-l-full' : '' }} rounded-r-full" style="width: {{ ($month['receivable'] / $maxMonthly) * 100 }}%"></div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        <div class="flex gap-4 mt-3 pt-3 border-t border-slate-100 text-xs text-slate-500">
            <div class="flex items-center gap-1"><span class="w-3 h-2 bg-red-400 rounded-full"></span> Pagos a Proveedores</div>
            <div class="flex items-center gap-1"><span class="w-3 h-2 bg-blue-400 rounded-full"></span> Cobros de Clientes</div>
        </div>
    </div>
    @endif

    @endif {{-- end summary --}}

    {{-- Detail Views --}}
    @if($viewMode !== 'summary')
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 mb-4">
        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Buscar..."
            class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
    </div>

    @if($viewMode === 'by_customer')
    {{-- By Customer Table --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-slate-500 uppercase">Factura</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-slate-500 uppercase">Cliente</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-slate-500 uppercase">Fecha</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold text-slate-500 uppercase">Total</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold text-slate-500 uppercase">Pagado</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold text-slate-500 uppercase">Pendiente</th>
                        <th class="px-6 py-3 text-center text-sm font-semibold text-slate-500 uppercase">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($detailData as $item)
                    @php $rem = (float)$item->credit_amount - (float)$item->paid_amount; @endphp
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-3 text-sm font-semibold text-slate-800">{{ $item->invoice_number }}</td>
                        <td class="px-6 py-3">
                            <p class="text-sm font-medium text-slate-700">{{ $item->customer_name }}</p>
                            <p class="text-xs text-slate-400">{{ $item->document_number }}</p>
                        </td>
                        <td class="px-6 py-3 text-sm text-slate-600">{{ $item->created_at->format('d/m/Y') }}</td>
                        <td class="px-6 py-3 text-right text-sm font-semibold">${{ number_format($item->credit_amount, 2) }}</td>
                        <td class="px-6 py-3 text-right text-sm text-green-600">${{ number_format($item->paid_amount, 2) }}</td>
                        <td class="px-6 py-3 text-right text-sm font-bold text-blue-600">${{ number_format($rem, 2) }}</td>
                        <td class="px-6 py-3 text-center">
                            @if($item->payment_status === 'paid')
                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">Pagado</span>
                            @elseif($item->payment_status === 'partial')
                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-700">Parcial</span>
                            @else
                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">Pendiente</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-6 py-12 text-center text-slate-400">No hay datos</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(method_exists($detailData, 'links'))
        <div class="px-6 py-4 border-t border-slate-200">{{ $detailData->links() }}</div>
        @endif
    </div>
    @endif

    @if($viewMode === 'by_supplier')
    {{-- By Supplier Table --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-slate-500 uppercase">Compra</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-slate-500 uppercase">Proveedor</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-slate-500 uppercase">Fecha</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold text-slate-500 uppercase">Total</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold text-slate-500 uppercase">Pagado</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold text-slate-500 uppercase">Pendiente</th>
                        <th class="px-6 py-3 text-center text-sm font-semibold text-slate-500 uppercase">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($detailData as $item)
                    @php $rem = (float)$item->credit_amount - (float)$item->paid_amount; @endphp
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-3 text-sm font-semibold text-slate-800">{{ $item->purchase_number }}</td>
                        <td class="px-6 py-3 text-sm font-medium text-slate-700">{{ $item->supplier_name }}</td>
                        <td class="px-6 py-3 text-sm text-slate-600">{{ $item->created_at->format('d/m/Y') }}</td>
                        <td class="px-6 py-3 text-right text-sm font-semibold">${{ number_format($item->credit_amount, 2) }}</td>
                        <td class="px-6 py-3 text-right text-sm text-green-600">${{ number_format($item->paid_amount, 2) }}</td>
                        <td class="px-6 py-3 text-right text-sm font-bold text-red-600">${{ number_format($rem, 2) }}</td>
                        <td class="px-6 py-3 text-center">
                            @if($item->payment_status === 'paid')
                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">Pagado</span>
                            @elseif($item->payment_status === 'partial')
                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-700">Parcial</span>
                            @else
                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">Pendiente</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-6 py-12 text-center text-slate-400">No hay datos</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(method_exists($detailData, 'links'))
        <div class="px-6 py-4 border-t border-slate-200">{{ $detailData->links() }}</div>
        @endif
    </div>
    @endif

    @if($viewMode === 'by_date')
    {{-- By Date Table --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-slate-500 uppercase">Tipo</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-slate-500 uppercase">Documento</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-slate-500 uppercase">Entidad</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-slate-500 uppercase">Fecha</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold text-slate-500 uppercase">Total</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold text-slate-500 uppercase">Pagado</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold text-slate-500 uppercase">Pendiente</th>
                        <th class="px-6 py-3 text-center text-sm font-semibold text-slate-500 uppercase">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($detailData as $item)
                    @php $rem = (float)$item->credit_amount - (float)$item->paid_amount; @endphp
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-3">
                            @if($item->record_type === 'purchase')
                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">Por Pagar</span>
                            @else
                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700">Por Cobrar</span>
                            @endif
                        </td>
                        <td class="px-6 py-3 text-sm font-semibold text-slate-800">{{ $item->doc_number }}</td>
                        <td class="px-6 py-3 text-sm text-slate-700">{{ $item->entity_name }}</td>
                        <td class="px-6 py-3 text-sm text-slate-600">{{ \Carbon\Carbon::parse($item->created_at)->format('d/m/Y') }}</td>
                        <td class="px-6 py-3 text-right text-sm font-semibold">${{ number_format($item->credit_amount, 2) }}</td>
                        <td class="px-6 py-3 text-right text-sm text-green-600">${{ number_format($item->paid_amount, 2) }}</td>
                        <td class="px-6 py-3 text-right text-sm font-bold {{ $item->record_type === 'purchase' ? 'text-red-600' : 'text-blue-600' }}">${{ number_format($rem, 2) }}</td>
                        <td class="px-6 py-3 text-center">
                            @if($item->payment_status === 'paid')
                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">Pagado</span>
                            @elseif($item->payment_status === 'partial')
                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-700">Parcial</span>
                            @else
                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">Pendiente</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="px-6 py-12 text-center text-slate-400">No hay datos</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif

    @if($viewMode === 'payments')
    {{-- Payments/Abonos Table --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-slate-500 uppercase">Nº Pago</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-slate-500 uppercase">Tipo</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-slate-500 uppercase">Entidad</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-slate-500 uppercase">Método</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold text-slate-500 uppercase">Monto</th>
                        <th class="px-6 py-3 text-center text-sm font-semibold text-slate-500 uppercase">Afecta Caja</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-slate-500 uppercase">Usuario</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-slate-500 uppercase">Fecha</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($detailData as $payment)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-3 text-sm font-semibold text-slate-800">{{ $payment->payment_number }}</td>
                        <td class="px-6 py-3">
                            @if($payment->credit_type === 'payable')
                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">Pago Prov.</span>
                            @else
                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700">Cobro Cli.</span>
                            @endif
                        </td>
                        <td class="px-6 py-3 text-sm text-slate-700">
                            @if($payment->credit_type === 'payable')
                                {{ $payment->purchase?->supplier?->name ?? '-' }}
                            @else
                                {{ $payment->sale?->customer ? $payment->sale->customer->full_name : '-' }}
                            @endif
                        </td>
                        <td class="px-6 py-3 text-sm text-slate-600">{{ $payment->paymentMethod?->name ?? '-' }}</td>
                        <td class="px-6 py-3 text-right text-sm font-bold {{ $payment->credit_type === 'payable' ? 'text-red-600' : 'text-blue-600' }}">${{ number_format($payment->amount, 2) }}</td>
                        <td class="px-6 py-3 text-center">
                            @if($payment->affects_cash)
                            <span class="px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-700">Sí</span>
                            @else
                            <span class="text-xs text-slate-400">No</span>
                            @endif
                        </td>
                        <td class="px-6 py-3 text-sm text-slate-600">{{ $payment->user?->name ?? '-' }}</td>
                        <td class="px-6 py-3 text-sm text-slate-600">{{ $payment->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="px-6 py-12 text-center text-slate-400">No hay pagos registrados</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(method_exists($detailData, 'links'))
        <div class="px-6 py-4 border-t border-slate-200">{{ $detailData->links() }}</div>
        @endif
    </div>
    @endif
    @endif
</div>

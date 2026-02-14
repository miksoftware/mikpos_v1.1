<div class="p-6">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Header -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Reporte de Compras</h1>
            <p class="text-slate-500 text-sm mt-1">Análisis detallado de compras, créditos y abonos</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-8 gap-4">
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Período</label>
                <select wire:model.live="dateRange" class="w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm">
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
                <input wire:model.live="startDate" type="date" class="w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm" @if($dateRange !== 'custom') disabled @endif>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Hasta</label>
                <input wire:model.live="endDate" type="date" class="w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm" @if($dateRange !== 'custom') disabled @endif>
            </div>
            @if($isSuperAdmin)
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Sucursal</label>
                <select wire:model.live="selectedBranchId" class="w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm">
                    <option value="">Todas</option>
                    @foreach($branches as $branch)
                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Proveedor</label>
                <select wire:model.live="selectedSupplierId" class="w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm">
                    <option value="">Todos</option>
                    @foreach($suppliers as $supplier)
                    <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Tipo Pago</label>
                <select wire:model.live="paymentType" class="w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm">
                    <option value="">Todos</option>
                    <option value="cash">Contado</option>
                    <option value="credit">Crédito</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Estado Pago</label>
                <select wire:model.live="paymentStatus" class="w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm">
                    <option value="">Todos</option>
                    <option value="paid">Pagada</option>
                    <option value="partial">Parcial</option>
                    <option value="pending">Pendiente</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Buscar</label>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Buscar..." class="w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm">
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Total Compras</p>
                    <p class="text-lg font-bold text-slate-800">{{ $summary['total_count'] ?? 0 }}</p>
                    <p class="text-xs text-slate-400">${{ number_format($summary['total_amount'] ?? 0, 2) }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-green-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Contado</p>
                    <p class="text-lg font-bold text-green-600">${{ number_format($summary['cash_total'] ?? 0, 2) }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Crédito</p>
                    <p class="text-lg font-bold text-amber-600">${{ number_format($summary['credit_total'] ?? 0, 2) }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Total Pagado</p>
                    <p class="text-lg font-bold text-emerald-600">${{ number_format($summary['total_paid'] ?? 0, 2) }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-red-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Pendiente</p>
                    <p class="text-lg font-bold text-red-600">${{ number_format($summary['total_pending'] ?? 0, 2) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- By Supplier Pie -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
            <h3 class="text-sm font-semibold text-slate-700 mb-3">Compras por Proveedor</h3>
            <div class="h-64">
                <canvas id="supplierChart" wire:ignore></canvas>
            </div>
        </div>
        <!-- By Date Line -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
            <h3 class="text-sm font-semibold text-slate-700 mb-3">Compras por Fecha</h3>
            <div class="h-64">
                <canvas id="dateChart" wire:ignore></canvas>
            </div>
        </div>
        <!-- Payment Type Doughnut -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
            <h3 class="text-sm font-semibold text-slate-700 mb-3">Distribución Tipo de Pago</h3>
            <div class="h-64">
                <canvas id="paymentTypeChart" wire:ignore></canvas>
            </div>
        </div>
    </div>

    <!-- Tab Buttons -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-2 mb-6">
        <div class="flex flex-wrap gap-1">
            @php
                $tabs = [
                    'by_supplier' => 'Compras x Proveedor',
                    'by_date' => 'Compras x Fechas',
                    'payments_by_date' => 'Abonos x Fechas',
                    'credits_by_supplier' => 'Créditos x Proveedor',
                    'credits_by_date' => 'Créditos x Fechas',
                    'details_by_supplier' => 'Detalles x Proveedor',
                    'details_by_date' => 'Detalles x Fechas',
                ];
            @endphp
            @foreach($tabs as $key => $label)
            <button wire:click="$set('viewMode', '{{ $key }}')"
                class="px-3 py-2 text-sm font-medium rounded-xl transition-all {{ $viewMode === $key ? 'bg-gradient-to-r from-[#ff7261] to-[#a855f7] text-white shadow-lg shadow-purple-500/25' : 'text-slate-600 hover:bg-slate-100' }}">
                {{ $label }}
            </button>
            @endforeach
        </div>
    </div>

    <!-- Data Tables -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">

        {{-- Compras x Proveedor --}}
        @if($viewMode === 'by_supplier')
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Proveedor</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 uppercase"># Compras</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Subtotal</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Impuestos</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($detailData as $row)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-4 py-3 text-sm font-medium text-slate-800">{{ $row->supplier_name }}</td>
                        <td class="px-4 py-3 text-sm text-center text-slate-600">{{ $row->purchase_count }}</td>
                        <td class="px-4 py-3 text-sm text-right text-slate-600">${{ number_format($row->total_subtotal, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-right text-slate-600">${{ number_format($row->total_tax, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-right font-semibold text-slate-800">${{ number_format($row->total_amount, 2) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-slate-400">No hay datos para mostrar</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-slate-200">{{ $detailData->links() }}</div>
        @endif

        {{-- Compras x Fechas --}}
        @if($viewMode === 'by_date')
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Fecha</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase"># Compra</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Proveedor</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 uppercase">Tipo</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 uppercase">Estado</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($detailData as $row)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $row->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-slate-800">{{ $row->purchase_number }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $row->supplier_name }}</td>
                        <td class="px-4 py-3 text-sm text-center">
                            <span class="px-2 py-1 rounded-full text-xs font-medium {{ $row->payment_type === 'cash' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">
                                {{ $row->payment_type === 'cash' ? 'Contado' : 'Crédito' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-center">
                            <span class="px-2 py-1 rounded-full text-xs font-medium {{ $row->payment_status === 'paid' ? 'bg-green-100 text-green-700' : ($row->payment_status === 'partial' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') }}">
                                {{ $row->payment_status === 'paid' ? 'Pagada' : ($row->payment_status === 'partial' ? 'Parcial' : 'Pendiente') }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-right font-semibold text-slate-800">${{ number_format($row->total, 2) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">No hay datos para mostrar</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-slate-200">{{ $detailData->links() }}</div>
        @endif

        {{-- Abonos x Fechas --}}
        @if($viewMode === 'payments_by_date')
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Fecha</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase"># Pago</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase"># Compra</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Proveedor</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Método</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Monto</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($detailData as $row)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $row->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-slate-800">{{ $row->payment_number }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $row->purchase_number }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $row->supplier_name }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $row->payment_method_name ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-right font-semibold text-emerald-600">${{ number_format($row->amount, 2) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">No hay abonos para mostrar</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-slate-200">{{ $detailData->links() }}</div>
        @endif

        {{-- Créditos x Proveedor --}}
        @if($viewMode === 'credits_by_supplier')
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Proveedor</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 uppercase"># Créditos</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Total Crédito</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Pagado</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Pendiente</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($detailData as $row)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-4 py-3 text-sm font-medium text-slate-800">{{ $row->supplier_name }}</td>
                        <td class="px-4 py-3 text-sm text-center text-slate-600">{{ $row->credit_count }}</td>
                        <td class="px-4 py-3 text-sm text-right text-slate-600">${{ number_format($row->total_credit, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-right text-emerald-600">${{ number_format($row->total_paid, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-right font-semibold text-red-600">${{ number_format($row->total_pending, 2) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-slate-400">No hay créditos para mostrar</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-slate-200">{{ $detailData->links() }}</div>
        @endif

        {{-- Créditos x Fechas --}}
        @if($viewMode === 'credits_by_date')
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Fecha</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase"># Compra</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Proveedor</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 uppercase">Estado</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Crédito</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Pagado</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Pendiente</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($detailData as $row)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $row->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-slate-800">{{ $row->purchase_number }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $row->supplier_name }}</td>
                        <td class="px-4 py-3 text-sm text-center">
                            <span class="px-2 py-1 rounded-full text-xs font-medium {{ $row->payment_status === 'paid' ? 'bg-green-100 text-green-700' : ($row->payment_status === 'partial' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') }}">
                                {{ $row->payment_status === 'paid' ? 'Pagada' : ($row->payment_status === 'partial' ? 'Parcial' : 'Pendiente') }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-right text-slate-600">${{ number_format($row->credit_amount, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-right text-emerald-600">${{ number_format($row->paid_amount, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-right font-semibold text-red-600">${{ number_format((float)$row->credit_amount - (float)$row->paid_amount, 2) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-slate-400">No hay créditos para mostrar</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-slate-200">{{ $detailData->links() }}</div>
        @endif

        {{-- Detalles x Proveedor --}}
        @if($viewMode === 'details_by_supplier')
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Proveedor</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase"># Compra</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Fecha</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Producto</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">SKU</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 uppercase">Cant.</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Costo Unit.</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Descuento</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($detailData as $row)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $row->supplier_name }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-slate-800">{{ $row->purchase_number }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ \Carbon\Carbon::parse($row->purchase_date)->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-sm text-slate-800">{{ $row->product_name ?? 'N/A' }}</td>
                        <td class="px-4 py-3 text-sm text-slate-500">{{ $row->sku ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-center text-slate-600">{{ rtrim(rtrim(number_format($row->quantity, 3), '0'), '.') }}</td>
                        <td class="px-4 py-3 text-sm text-right text-slate-600">${{ number_format($row->unit_cost, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-right text-slate-600">${{ number_format($row->discount, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-right font-semibold text-slate-800">${{ number_format($row->total, 2) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="px-4 py-8 text-center text-slate-400">No hay detalles para mostrar</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-slate-200">{{ $detailData->links() }}</div>
        @endif

        {{-- Detalles x Fechas --}}
        @if($viewMode === 'details_by_date')
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Fecha</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase"># Compra</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Proveedor</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Producto</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">SKU</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 uppercase">Cant.</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Costo Unit.</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Descuento</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($detailData as $row)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-4 py-3 text-sm text-slate-600">{{ \Carbon\Carbon::parse($row->purchase_date)->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-slate-800">{{ $row->purchase_number }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $row->supplier_name }}</td>
                        <td class="px-4 py-3 text-sm text-slate-800">{{ $row->product_name ?? 'N/A' }}</td>
                        <td class="px-4 py-3 text-sm text-slate-500">{{ $row->sku ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-center text-slate-600">{{ rtrim(rtrim(number_format($row->quantity, 3), '0'), '.') }}</td>
                        <td class="px-4 py-3 text-sm text-right text-slate-600">${{ number_format($row->unit_cost, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-right text-slate-600">${{ number_format($row->discount, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-right font-semibold text-slate-800">${{ number_format($row->total, 2) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="px-4 py-8 text-center text-slate-400">No hay detalles para mostrar</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-slate-200">{{ $detailData->links() }}</div>
        @endif
    </div>

    <!-- Charts Script -->
    <script>
        document.addEventListener('livewire:navigated', initPurchaseCharts);
        document.addEventListener('DOMContentLoaded', initPurchaseCharts);

        function initPurchaseCharts() {
            // Destroy existing charts
            ['supplierChart', 'dateChart', 'paymentTypeChart'].forEach(id => {
                const existing = Chart.getChart(id);
                if (existing) existing.destroy();
            });

            const colors = ['#ff7261', '#a855f7', '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#ec4899', '#14b8a6'];

            // Supplier Pie Chart
            const supplierData = @json($chartBySupplier);
            const supplierCtx = document.getElementById('supplierChart');
            if (supplierCtx && supplierData.length > 0) {
                new Chart(supplierCtx, {
                    type: 'pie',
                    data: {
                        labels: supplierData.map(s => s.label),
                        datasets: [{
                            data: supplierData.map(s => s.value),
                            backgroundColor: colors.slice(0, supplierData.length),
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } }
                        }
                    }
                });
            }

            // Date Line Chart
            const dateData = @json($chartByDate);
            const dateCtx = document.getElementById('dateChart');
            if (dateCtx && dateData.length > 0) {
                new Chart(dateCtx, {
                    type: 'line',
                    data: {
                        labels: dateData.map(d => d.label),
                        datasets: [{
                            label: 'Total',
                            data: dateData.map(d => d.value),
                            borderColor: '#a855f7',
                            backgroundColor: 'rgba(168, 85, 247, 0.1)',
                            fill: true,
                            tension: 0.3,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: { ticks: { maxRotation: 45, font: { size: 10 } } },
                            y: { beginAtZero: true }
                        },
                        plugins: { legend: { display: false } }
                    }
                });
            }

            // Payment Type Doughnut
            const paymentData = @json($chartPaymentType);
            const paymentCtx = document.getElementById('paymentTypeChart');
            if (paymentCtx && paymentData.length > 0) {
                new Chart(paymentCtx, {
                    type: 'doughnut',
                    data: {
                        labels: paymentData.map(p => p.label),
                        datasets: [{
                            data: paymentData.map(p => p.value),
                            backgroundColor: ['#10b981', '#f59e0b'],
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } }
                        }
                    }
                });
            }
        }
    </script>
</div>

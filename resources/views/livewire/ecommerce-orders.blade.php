<div>
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Pedidos Tienda</h1>
            <p class="text-sm text-slate-500 mt-1">Gestiona los pedidos realizados desde la tienda en línea.</p>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="flex items-center gap-1 mb-6 bg-slate-100 rounded-xl p-1 w-fit">
        <button wire:click="$set('activeTab', 'pending')"
            class="px-4 py-2 text-sm font-medium rounded-lg transition-all {{ $activeTab === 'pending' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
            Pendientes
            @if($pendingCount > 0)
                <span class="ml-1.5 inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-amber-500 rounded-full">{{ $pendingCount }}</span>
            @endif
        </button>
        <button wire:click="$set('activeTab', 'approved')"
            class="px-4 py-2 text-sm font-medium rounded-lg transition-all {{ $activeTab === 'approved' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
            Aprobados
            <span class="ml-1 text-xs text-slate-400">({{ $approvedCount }})</span>
        </button>
        <button wire:click="$set('activeTab', 'rejected')"
            class="px-4 py-2 text-sm font-medium rounded-lg transition-all {{ $activeTab === 'rejected' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
            Rechazados
            <span class="ml-1 text-xs text-slate-400">({{ $rejectedCount }})</span>
        </button>
        <button wire:click="$set('activeTab', 'products')"
            class="px-4 py-2 text-sm font-medium rounded-lg transition-all {{ $activeTab === 'products' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
            <svg class="w-4 h-4 inline -mt-0.5 mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            Productos
        </button>
        <button wire:click="$set('activeTab', 'report')"
            class="px-4 py-2 text-sm font-medium rounded-lg transition-all {{ $activeTab === 'report' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
            <svg class="w-4 h-4 inline -mt-0.5 mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Reporte
        </button>
    </div>

    @if($activeTab === 'products')
    {{-- Aggregated Products View --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        @if($aggregatedProducts->count() > 0)
        <div class="px-4 py-3 bg-slate-50 border-b border-slate-200">
            <p class="text-sm text-slate-600">
                <span class="font-semibold">{{ $aggregatedProducts->count() }}</span> producto(s) en
                <span class="font-semibold">{{ $pendingCount }}</span> pedido(s) pendiente(s)
            </p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-slate-50">
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Producto</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 uppercase">Cantidad Total</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 uppercase">Pedidos</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 uppercase">Stock Actual</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 uppercase">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($aggregatedProducts as $product)
                    @php
                        $totalQty = (float) $product->total_quantity;
                        $stock = (float) $product->current_stock;
                        $managesInv = $product->manages_inventory;
                        $hasEnough = !$managesInv || $stock >= $totalQty;
                    @endphp
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                @if($product->image)
                                <img src="{{ Storage::url($product->image) }}" alt="" class="w-9 h-9 rounded-lg object-cover flex-shrink-0">
                                @else
                                <div class="w-9 h-9 rounded-lg bg-slate-100 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                </div>
                                @endif
                                <div>
                                    <p class="text-sm font-medium text-slate-900">{{ $product->product_name }}</p>
                                    @if($product->product_sku)
                                    <p class="text-xs text-slate-500">{{ $product->product_sku }}</p>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="text-sm font-bold text-slate-900">{{ rtrim(rtrim(number_format($totalQty, 3), '0'), '.') }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="text-sm text-slate-600">{{ $product->order_count }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($managesInv)
                            <span class="text-sm font-medium {{ $stock >= $totalQty ? 'text-green-600' : 'text-red-600' }}">
                                {{ rtrim(rtrim(number_format($stock, 3), '0'), '.') }}
                            </span>
                            @else
                            <span class="text-xs font-medium text-purple-600">∞</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($hasEnough)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Disponible</span>
                            @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                Faltan {{ rtrim(rtrim(number_format($totalQty - $stock, 3), '0'), '.') }}
                            </span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="px-4 py-12 text-center">
            <svg class="w-12 h-12 text-slate-300 mb-3 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            <p class="text-slate-500">No hay productos en pedidos pendientes</p>
        </div>
        @endif
    </div>
    @elseif($activeTab === 'report')
    {{-- Report Tab --}}
    <div wire:key="report-tab-{{ $reportDateFrom }}-{{ $reportDateTo }}-{{ $reportStatus }}">
        {{-- Report Filters --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 mb-5">
            <div class="flex flex-wrap items-end gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Período</label>
                    <select wire:model.live="reportPeriod" class="px-3 py-2 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                        <option value="today">Hoy</option>
                        <option value="yesterday">Ayer</option>
                        <option value="week">Esta semana</option>
                        <option value="last_week">Semana anterior</option>
                        <option value="month">Este mes</option>
                        <option value="last_month">Mes anterior</option>
                        <option value="custom">Personalizado</option>
                    </select>
                </div>
                @if($reportPeriod === 'custom')
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Desde</label>
                    <input type="date" wire:model.live="reportDateFrom" class="px-3 py-2 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Hasta</label>
                    <input type="date" wire:model.live="reportDateTo" class="px-3 py-2 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                </div>
                @endif
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Estado</label>
                    <select wire:model.live="reportStatus" class="px-3 py-2 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                        <option value="all">Todos</option>
                        <option value="pending">Pendientes</option>
                        <option value="approved">Aprobados</option>
                        <option value="rejected">Rechazados</option>
                    </select>
                </div>
                <a href="{{ route('ecommerce-orders.report-pdf', ['date_from' => $reportDateFrom, 'date_to' => $reportDateTo, 'status' => $reportStatus]) }}"
                    target="_blank"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea] transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Descargar PDF
                </a>
            </div>
        </div>

        @if(!empty($reportData))
        @php
            $rProducts = $reportData['products'] ?? [];
            $rCustomers = $reportData['customers'] ?? [];
            $rCustomerTotals = $reportData['customerTotals'] ?? [];
            $rGrandTotal = $reportData['grandTotal'] ?? 0;
        @endphp

        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-5">
            <div class="bg-gradient-to-br from-[#ff7261] to-[#a855f7] rounded-2xl p-5 text-white">
                <p class="text-xs opacity-80">Productos</p>
                <p class="text-2xl font-bold mt-1">{{ count($rProducts) }}</p>
            </div>
            <div class="bg-gradient-to-br from-blue-500 to-purple-500 rounded-2xl p-5 text-white">
                <p class="text-xs opacity-80">Clientes</p>
                <p class="text-2xl font-bold mt-1">{{ count($rCustomers) }}</p>
            </div>
            <div class="bg-gradient-to-br from-emerald-500 to-teal-500 rounded-2xl p-5 text-white">
                <p class="text-xs opacity-80">Total Unidades</p>
                <p class="text-2xl font-bold mt-1">{{ rtrim(rtrim(number_format($rGrandTotal, 3), '0'), '.') }}</p>
            </div>
        </div>

        {{-- Cross-tab Table --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            @if(count($rProducts) > 0)
            <div class="px-5 py-3 bg-slate-50 border-b border-slate-200">
                <p class="text-sm text-slate-600">
                    <span class="font-semibold">{{ count($rProducts) }}</span> producto(s) ×
                    <span class="font-semibold">{{ count($rCustomers) }}</span> cliente(s)
                </p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-slate-50">
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase sticky left-0 bg-slate-50 z-10 min-w-[200px]">Producto</th>
                            @foreach($rCustomers as $key => $name)
                            <th class="px-3 py-3 text-center text-xs font-semibold text-slate-500 uppercase min-w-[80px]" title="{{ $name }}">
                                {{ \Illuminate\Support\Str::limit($name, 12) }}
                            </th>
                            @endforeach
                            <th class="px-4 py-3 text-center text-xs font-bold text-purple-700 uppercase bg-purple-50 min-w-[80px]">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($rProducts as $product)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-4 py-2.5 sticky left-0 bg-white z-10">
                                <p class="text-sm font-medium text-slate-900">{{ $product['name'] }}</p>
                                @if($product['sku'])
                                <p class="text-xs text-slate-400">{{ $product['sku'] }}</p>
                                @endif
                            </td>
                            @foreach($rCustomers as $custKey => $custName)
                            <td class="px-3 py-2.5 text-center">
                                @if(($product['quantities'][$custKey] ?? 0) > 0)
                                <span class="text-sm font-semibold text-slate-900">{{ rtrim(rtrim(number_format($product['quantities'][$custKey], 3), '0'), '.') }}</span>
                                @else
                                <span class="text-slate-300">-</span>
                                @endif
                            </td>
                            @endforeach
                            <td class="px-4 py-2.5 text-center bg-purple-50">
                                <span class="text-sm font-bold text-purple-700">{{ rtrim(rtrim(number_format($product['total'], 3), '0'), '.') }}</span>
                            </td>
                        </tr>
                        @endforeach
                        <tr class="bg-slate-800 text-white">
                            <td class="px-4 py-3 sticky left-0 bg-slate-800 z-10 font-bold text-sm">TOTAL</td>
                            @foreach($rCustomers as $custKey => $custName)
                            <td class="px-3 py-3 text-center font-bold text-sm">
                                @if(($rCustomerTotals[$custKey] ?? 0) > 0)
                                {{ rtrim(rtrim(number_format($rCustomerTotals[$custKey], 3), '0'), '.') }}
                                @else
                                -
                                @endif
                            </td>
                            @endforeach
                            <td class="px-4 py-3 text-center font-bold text-sm bg-purple-900">
                                {{ rtrim(rtrim(number_format($rGrandTotal, 3), '0'), '.') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            @else
            <div class="px-4 py-12 text-center">
                <svg class="w-12 h-12 text-slate-300 mb-3 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <p class="text-slate-500">No hay datos para los filtros seleccionados</p>
            </div>
            @endif
        </div>
        @endif
    </div>
    @else
    {{-- Filters --}}
    <div class="flex flex-wrap items-center gap-3 mb-4">
        <div class="relative flex-1 min-w-[200px] max-w-md">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar por número o cliente..."
                class="w-full pl-9 pr-3 py-2 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
        </div>
        <input type="date" wire:model.live="dateFrom" class="px-3 py-2 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
        <input type="date" wire:model.live="dateTo" class="px-3 py-2 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">

        @if($activeTab === 'pending' && count($selectedOrders) > 0)
            <button wire:click="bulkApprove" wire:confirm="¿Aprobar {{ count($selectedOrders) }} pedido(s) seleccionados?"
                class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-xl hover:bg-green-700 transition-colors">
                Aprobar seleccionados ({{ count($selectedOrders) }})
            </button>
        @endif
    </div>

    {{-- Orders Table --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-slate-50">
                        @if($activeTab === 'pending')
                        <th class="px-4 py-3 text-left">
                            <input type="checkbox" wire:model.live="selectAll" class="w-4 h-4 rounded border-slate-300 text-[#ff7261] focus:ring-[#ff7261]">
                        </th>
                        @endif
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Pedido</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Cliente</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Fecha</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Total</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Pago</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Estado</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($orders as $order)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        @if($activeTab === 'pending')
                        <td class="px-4 py-3">
                            <input type="checkbox" wire:model.live="selectedOrders" value="{{ $order->id }}" class="w-4 h-4 rounded border-slate-300 text-[#ff7261] focus:ring-[#ff7261]">
                        </td>
                        @endif
                        <td class="px-4 py-3">
                            <span class="text-sm font-semibold text-slate-900">{{ $order->invoice_number }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-sm text-slate-700">{{ $order->customer?->full_name ?? 'Sin cliente' }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-sm text-slate-500">{{ $order->created_at->format('d/m/Y H:i') }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <span class="text-sm font-semibold text-slate-900">${{ number_format($order->total, 0, ',', '.') }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-sm text-slate-600">{{ $order->payments->first()?->paymentMethod?->name ?? '-' }}</span>
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $ecoStatus = $order->ecommerceOrder?->status ?? 'pending';
                                $statusConfig = match($ecoStatus) {
                                    'pending' => ['label' => 'Pendiente', 'class' => 'bg-amber-100 text-amber-800'],
                                    'approved' => ['label' => 'Aprobado', 'class' => 'bg-green-100 text-green-800'],
                                    'partial' => ['label' => 'Parcial', 'class' => 'bg-orange-100 text-orange-800'],
                                    'rejected' => ['label' => 'Rechazado', 'class' => 'bg-red-100 text-red-800'],
                                    default => ['label' => ucfirst($ecoStatus), 'class' => 'bg-slate-100 text-slate-800'],
                                };
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusConfig['class'] }}">
                                {{ $statusConfig['label'] }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-1">
                                <button wire:click="viewOrder({{ $order->id }})" class="p-1.5 text-slate-400 hover:text-[#a855f7] rounded-lg hover:bg-slate-100" title="Ver detalle">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </button>
                                @if($activeTab === 'pending')
                                <button wire:click="approveOrder({{ $order->id }})" wire:confirm="¿Aprobar este pedido?" class="p-1.5 text-slate-400 hover:text-green-600 rounded-lg hover:bg-green-50" title="Aprobar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </button>
                                <button wire:click="openRejectModal({{ $order->id }})" class="p-1.5 text-slate-400 hover:text-red-600 rounded-lg hover:bg-red-50" title="Rechazar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $activeTab === 'pending' ? 8 : 7 }}" class="px-4 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <svg class="w-12 h-12 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                <p class="text-slate-500">No hay pedidos {{ $activeTab === 'pending' ? 'pendientes' : ($activeTab === 'approved' ? 'aprobados' : 'rechazados') }}</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-slate-200">
            {{ $orders->links() }}
        </div>
    </div>
    @endif {{-- end activeTab !== products --}}

    {{-- Detail Modal --}}
    @if($showDetailModal && $selectedSale)
    <div class="relative z-[100]" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="closeDetailModal"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-xl">
                    {{-- Header --}}
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">Pedido {{ $selectedSale->invoice_number }}</h3>
                            <p class="text-sm text-slate-500">{{ $selectedSale->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                        <button wire:click="closeDetailModal" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="px-6 py-4 space-y-4 max-h-[70vh] overflow-y-auto">
                        {{-- Customer --}}
                        <div class="bg-slate-50 rounded-xl p-4">
                            <p class="text-xs font-semibold text-slate-500 uppercase mb-2">Cliente</p>
                            <p class="text-sm font-medium text-slate-900">{{ $selectedSale->customer?->full_name ?? 'Sin cliente' }}</p>
                            @if($selectedSale->customer?->document_number)
                                <p class="text-sm text-slate-500">{{ $selectedSale->customer->taxDocument?->abbreviation ?? 'Doc' }}: {{ $selectedSale->customer->document_number }}</p>
                            @endif
                        </div>

                        {{-- Shipping --}}
                        @if($selectedOrder)
                        <div class="bg-blue-50 rounded-xl p-4">
                            <p class="text-xs font-semibold text-blue-600 uppercase mb-2">Envío</p>
                            @if($selectedOrder->shipping_address)
                                <p class="text-sm text-slate-700">{{ $selectedOrder->shipping_address }}</p>
                            @endif
                            @if($selectedOrder->shippingMunicipality || $selectedOrder->shippingDepartment)
                                <p class="text-sm text-slate-500">{{ $selectedOrder->shippingMunicipality?->name }}{{ $selectedOrder->shippingDepartment ? ', ' . $selectedOrder->shippingDepartment->name : '' }}</p>
                            @endif
                            @if($selectedOrder->shipping_phone)
                                <p class="text-sm text-slate-500">Tel: {{ $selectedOrder->shipping_phone }}</p>
                            @endif
                            @if($selectedOrder->customer_notes)
                                <p class="text-sm text-slate-600 mt-2 italic">"{{ $selectedOrder->customer_notes }}"</p>
                            @endif
                        </div>
                        @endif

                        {{-- Products --}}
                        <div>
                            <p class="text-xs font-semibold text-slate-500 uppercase mb-2">Productos</p>
                            <div class="space-y-2">
                                @foreach($selectedSale->items as $item)
                                <div class="border rounded-xl p-3 {{ ($unavailableItems[$item->id]['is_unavailable'] ?? false) ? 'border-orange-300 bg-orange-50' : 'border-slate-200' }}">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-slate-900 truncate">{{ $item->product_name }}</p>
                                            <p class="text-xs text-slate-500">${{ number_format($item->unit_price, 0, ',', '.') }} x {{ rtrim(rtrim(number_format($item->quantity, 3), '0'), '.') }}</p>
                                        </div>
                                        <span class="text-sm font-semibold text-slate-900 ml-3">${{ number_format($item->total, 0, ',', '.') }}</span>
                                    </div>

                                    @if($selectedSale->status === 'pending_approval')
                                    <div class="mt-2 pt-2 border-t border-slate-100">
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="checkbox" wire:click="toggleItemUnavailable({{ $item->id }})"
                                                {{ ($unavailableItems[$item->id]['is_unavailable'] ?? false) ? 'checked' : '' }}
                                                class="w-4 h-4 rounded border-slate-300 text-orange-500 focus:ring-orange-500">
                                            <span class="text-xs text-orange-700 font-medium">No se enviará este producto</span>
                                        </label>
                                        @if($unavailableItems[$item->id]['is_unavailable'] ?? false)
                                        <input type="text" wire:model.blur="unavailableItems.{{ $item->id }}.reason"
                                            placeholder="Motivo (ej: Sin stock, Producto descontinuado...)"
                                            class="mt-2 w-full px-3 py-1.5 text-xs border border-orange-300 rounded-lg focus:ring-2 focus:ring-orange-500/50 focus:border-orange-500 bg-white">
                                        @endif
                                    </div>
                                    @elseif($item->is_unavailable)
                                    <div class="mt-2 pt-2 border-t border-orange-200">
                                        <p class="text-xs text-orange-700 font-medium">⚠ No enviado: {{ $item->unavailable_reason ?? 'Sin motivo' }}</p>
                                    </div>
                                    @endif
                                </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Totals --}}
                        @php
                            $availableItems = $selectedSale->items->where('is_unavailable', false);
                            $modalSubtotal = $availableItems->sum('subtotal');
                            $modalTaxTotal = $availableItems->sum('tax_amount');
                            $modalTotal = $availableItems->sum('total');
                            $hasAnyUnavailable = $selectedSale->items->where('is_unavailable', true)->count() > 0;
                        @endphp
                        <div class="bg-slate-50 rounded-xl p-4 space-y-1">
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-500">Subtotal</span>
                                <span class="text-slate-900">${{ number_format($modalSubtotal, 0, ',', '.') }}</span>
                            </div>
                            @if($modalTaxTotal > 0)
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-500">Impuestos</span>
                                <span class="text-slate-900">${{ number_format($modalTaxTotal, 0, ',', '.') }}</span>
                            </div>
                            @endif
                            <div class="flex justify-between text-base font-bold pt-2 border-t border-slate-200">
                                <span class="text-slate-900">Total</span>
                                <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#ff7261] to-[#a855f7]">${{ number_format($modalTotal, 0, ',', '.') }}</span>
                            </div>
                            @if($hasAnyUnavailable)
                            <p class="text-xs text-orange-600 pt-1">* Total ajustado excluyendo productos no disponibles</p>
                            @endif
                        </div>

                        {{-- Payment --}}
                        @if($selectedSale->payments->isNotEmpty())
                        <div>
                            <p class="text-xs font-semibold text-slate-500 uppercase mb-1">Método de pago</p>
                            @foreach($selectedSale->payments as $payment)
                                <p class="text-sm text-slate-700">{{ $payment->paymentMethod->name ?? 'N/A' }} - ${{ number_format($payment->amount, 0, ',', '.') }}</p>
                            @endforeach
                        </div>
                        @endif

                        {{-- Rejection reason --}}
                        @if($selectedSale->status === 'rejected' && $selectedOrder?->rejection_reason)
                        <div class="bg-red-50 border border-red-200 rounded-xl p-4">
                            <p class="text-xs font-semibold text-red-600 uppercase mb-1">Motivo de rechazo</p>
                            <p class="text-sm text-red-700">{{ $selectedOrder->rejection_reason }}</p>
                        </div>
                        @endif
                    </div>

                    {{-- Footer --}}
                    @if($selectedSale->status === 'pending_approval')
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-between">
                        <button wire:click="openRejectModal({{ $selectedSale->id }})" class="px-4 py-2 text-sm font-medium text-red-700 bg-white border border-red-300 rounded-xl hover:bg-red-50">
                            Rechazar
                        </button>
                        <div class="flex gap-3">
                            @if(collect($unavailableItems)->contains('is_unavailable', true))
                            <button wire:click="saveUnavailableItems" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">
                                Guardar cambios
                            </button>
                            @endif
                            <button wire:click="approveOrder" class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea]">
                                Aprobar pedido
                            </button>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Reject Modal --}}
    @if($showRejectModal)
    <div class="relative z-[100]" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="closeRejectModal"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-md bg-white rounded-2xl shadow-xl p-6 text-center">
                    <div class="mx-auto w-12 h-12 rounded-full bg-red-100 flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Rechazar Pedido</h3>
                    <p class="text-slate-500 mb-4 text-sm">Indica el motivo del rechazo. El cliente podrá verlo.</p>
                    <textarea wire:model="rejectReason" rows="3" placeholder="Motivo del rechazo (mínimo 10 caracteres)..."
                        class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-red-500/50 focus:border-red-500 text-sm mb-1"></textarea>
                    @error('rejectReason')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                    <div class="flex justify-center gap-3 mt-4">
                        <button wire:click="closeRejectModal" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">Cancelar</button>
                        <button wire:click="rejectOrder" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-xl hover:bg-red-700">Rechazar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

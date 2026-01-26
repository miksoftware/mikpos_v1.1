<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Ventas</h1>
            <p class="text-slate-500 mt-1">Historial de ventas y facturas electrónicas</p>
        </div>
        <div class="flex items-center gap-3">
            <!-- Today Stats -->
            <div class="flex items-center gap-4 px-4 py-2 bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl border border-green-200">
                <div class="text-center">
                    <p class="text-xs text-green-600 font-medium">Hoy</p>
                    <p class="text-lg font-bold text-green-700">${{ number_format($todaySales, 0, ',', '.') }}</p>
                </div>
                <div class="h-8 w-px bg-green-200"></div>
                <div class="text-center">
                    <p class="text-xs text-green-600 font-medium">Ventas</p>
                    <p class="text-lg font-bold text-green-700">{{ $todayCount }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4">
            <!-- Search -->
            <div class="lg:col-span-2">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                    <input wire:model.live.debounce.300ms="search" type="text" class="block w-full pl-10 pr-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 placeholder-slate-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] transition-all sm:text-sm" placeholder="Buscar por factura, cliente...">
                </div>
            </div>

            <!-- Date From -->
            <div>
                <input wire:model.live="dateFrom" type="date" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm">
            </div>

            <!-- Date To -->
            <div>
                <input wire:model.live="dateTo" type="date" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm">
            </div>

            <!-- Type Filter -->
            <div>
                <select wire:model.live="filterElectronic" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm">
                    <option value="">Todos los tipos</option>
                    <option value="electronic">Electrónica ✓</option>
                    <option value="pos">Solo POS</option>
                    <option value="failed">Con error DIAN</option>
                </select>
            </div>

            <!-- Branch Filter (Super Admin) -->
            @if($isSuperAdmin)
            <div>
                <select wire:model.live="filterBranch" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm">
                    <option value="">Todas las sucursales</option>
                    @foreach($branches as $branch)
                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
        </div>
    </div>

    <!-- Sales Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Factura</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Cliente</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Fecha</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-500 uppercase">Total</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold text-slate-500 uppercase">Tipo</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold text-slate-500 uppercase">Estado DIAN</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($sales as $sale)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-4">
                            <div>
                                <p class="font-medium text-slate-900">{{ $sale->invoice_number }}</p>
                                @if($sale->dian_number)
                                <p class="text-xs text-slate-500">DIAN: {{ $sale->dian_number }}</p>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-slate-100 to-slate-200 flex items-center justify-center text-slate-600 text-sm font-medium">
                                    {{ substr($sale->customer->first_name ?? $sale->customer->business_name ?? '?', 0, 1) }}
                                </div>
                                <div>
                                    <p class="font-medium text-slate-800">{{ $sale->customer->full_name ?? 'Sin cliente' }}</p>
                                    <p class="text-xs text-slate-500">{{ $sale->customer->document_number ?? '' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-slate-800">{{ $sale->created_at->format('d/m/Y') }}</p>
                            <p class="text-xs text-slate-500">{{ $sale->created_at->format('H:i') }}</p>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <p class="font-semibold text-slate-900">${{ number_format($sale->total, 0, ',', '.') }}</p>
                            <p class="text-xs text-slate-500">{{ $sale->payments->count() }} pago(s)</p>
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($sale->is_electronic)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Electrónica
                            </span>
                            @else
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-600">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z"></path></svg>
                                POS
                            </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($sale->is_electronic && $sale->cufe)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                Validada
                            </span>
                            @elseif($sale->is_electronic && !$sale->cufe)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Error
                            </span>
                            @else
                            <span class="text-slate-400 text-xs">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <!-- View Detail -->
                                <button wire:click="viewSale({{ $sale->id }})" class="p-2 text-slate-400 hover:text-[#ff7261] hover:bg-orange-50 rounded-lg transition-colors" title="Ver detalle">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                </button>
                                
                                <!-- Reprint Receipt -->
                                <button wire:click="reprintReceipt({{ $sale->id }})" class="p-2 text-slate-400 hover:text-purple-500 hover:bg-purple-50 rounded-lg transition-colors relative" title="Reimprimir recibo">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                    @if($sale->reprints_count > 0)
                                    <span class="absolute -top-1 -right-1 w-4 h-4 text-[10px] font-bold bg-purple-500 text-white rounded-full flex items-center justify-center">{{ $sale->reprints_count }}</span>
                                    @endif
                                </button>
                                
                                <!-- View Electronic PDF -->
                                @if($sale->is_electronic && $sale->cufe && $sale->dian_public_url)
                                <button wire:click="viewElectronicPdf({{ $sale->id }})" class="p-2 text-slate-400 hover:text-blue-500 hover:bg-blue-50 rounded-lg transition-colors" title="Ver factura electrónica PDF">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                </button>
                                @endif
                                
                                <!-- Retry Electronic Invoice -->
                                @if($sale->is_electronic && !$sale->cufe)
                                <button wire:click="retryElectronicInvoice({{ $sale->id }})" wire:loading.attr="disabled" class="p-2 text-slate-400 hover:text-orange-500 hover:bg-orange-50 rounded-lg transition-colors" title="Reintentar factura electrónica">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                </button>
                                @endif
                                
                                <!-- View Reprints History -->
                                @if($sale->reprints_count > 0)
                                <button wire:click="viewReprints({{ $sale->id }})" class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-colors" title="Ver historial de reimpresiones">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                            <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                            No hay ventas en el período seleccionado
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($sales->hasPages())
        <div class="px-6 py-4 border-t border-slate-200">
            {{ $sales->links() }}
        </div>
        @endif
    </div>

    <!-- Detail Modal -->
    @if($showDetailModal && $selectedSale)
    <div class="relative z-[100]">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="closeDetailModal"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-3xl bg-white rounded-2xl shadow-xl">
                    <!-- Header -->
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">Detalle de Venta</h3>
                            <p class="text-sm text-slate-500">{{ $selectedSale->invoice_number }}</p>
                        </div>
                        <button wire:click="closeDetailModal" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    <div class="px-6 py-4 space-y-6 max-h-[70vh] overflow-y-auto">
                        <!-- Status Banner -->
                        @if($selectedSale->is_electronic && $selectedSale->cufe)
                        <div class="p-4 bg-green-50 border border-green-200 rounded-xl">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </div>
                                <div class="flex-1">
                                    <p class="font-medium text-green-700">Factura Electrónica Validada</p>
                                    <p class="text-sm text-green-600">DIAN: {{ $selectedSale->dian_number }}</p>
                                </div>
                                @if($selectedSale->qr_code)
                                <a href="{{ $selectedSale->qr_code }}" target="_blank" class="px-3 py-1.5 text-xs font-medium text-green-700 bg-green-100 rounded-lg hover:bg-green-200">
                                    Ver en DIAN
                                </a>
                                @endif
                            </div>
                            @if($selectedSale->cufe)
                            <p class="mt-2 text-xs text-green-600 font-mono break-all">CUFE: {{ $selectedSale->cufe }}</p>
                            @endif
                        </div>
                        @elseif($selectedSale->is_electronic && !$selectedSale->cufe)
                        <div class="p-4 bg-red-50 border border-red-200 rounded-xl">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </div>
                                <div class="flex-1">
                                    <p class="font-medium text-red-700">Error en Factura Electrónica</p>
                                    <p class="text-sm text-red-600">La factura no pudo ser validada por la DIAN</p>
                                </div>
                                <button wire:click="retryElectronicInvoice({{ $selectedSale->id }})" wire:loading.attr="disabled" class="px-3 py-1.5 text-xs font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">
                                    Reintentar
                                </button>
                            </div>
                            
                            {{-- Error details for super admin only --}}
                            @if($isSuperAdmin && $selectedSale->dian_response)
                            <div class="mt-4 pt-4 border-t border-red-200">
                                <p class="text-xs font-semibold text-red-700 mb-2 flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                    Detalle del Error (Solo Super Admin)
                                </p>
                                @php
                                    $dianResponse = $selectedSale->dian_response;
                                    $errorMessage = $dianResponse['message'] ?? null;
                                    $errors = $dianResponse['errors'] ?? [];
                                @endphp
                                
                                @if($errorMessage)
                                <div class="mb-2 p-2 bg-red-100 rounded-lg">
                                    <p class="text-xs text-red-800 font-medium">Mensaje:</p>
                                    <p class="text-xs text-red-700">{{ $errorMessage }}</p>
                                </div>
                                @endif
                                
                                @if(!empty($errors))
                                <div class="p-2 bg-red-100 rounded-lg">
                                    <p class="text-xs text-red-800 font-medium mb-1">Errores de validación:</p>
                                    <ul class="text-xs text-red-700 space-y-1">
                                        @foreach($errors as $field => $fieldErrors)
                                            @if(is_array($fieldErrors))
                                                @foreach($fieldErrors as $error)
                                                <li class="flex items-start gap-1">
                                                    <span class="text-red-500 mt-0.5">•</span>
                                                    <span><strong>{{ $field }}:</strong> {{ $error }}</span>
                                                </li>
                                                @endforeach
                                            @else
                                                <li class="flex items-start gap-1">
                                                    <span class="text-red-500 mt-0.5">•</span>
                                                    <span><strong>{{ $field }}:</strong> {{ $fieldErrors }}</span>
                                                </li>
                                            @endif
                                        @endforeach
                                    </ul>
                                </div>
                                @endif
                                
                                {{-- Raw JSON for debugging --}}
                                <details class="mt-2">
                                    <summary class="text-xs text-red-600 cursor-pointer hover:text-red-800">Ver respuesta completa (JSON)</summary>
                                    <pre class="mt-2 p-2 bg-slate-800 text-green-400 text-xs rounded-lg overflow-x-auto max-h-48">{{ json_encode($dianResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                </details>
                            </div>
                            @endif
                        </div>
                        @else
                        <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z"></path></svg>
                                </div>
                                <div>
                                    <p class="font-medium text-slate-700">Venta POS</p>
                                    <p class="text-sm text-slate-500">Sin factura electrónica</p>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Info Grid -->
                        <div class="grid grid-cols-2 gap-4">
                            <div class="p-4 bg-slate-50 rounded-xl">
                                <p class="text-xs text-slate-500 mb-1">Cliente</p>
                                <p class="font-medium text-slate-800">{{ $selectedSale->customer->full_name ?? 'Sin cliente' }}</p>
                                <p class="text-sm text-slate-500">{{ $selectedSale->customer->document_number ?? '' }}</p>
                            </div>
                            <div class="p-4 bg-slate-50 rounded-xl">
                                <p class="text-xs text-slate-500 mb-1">Vendedor</p>
                                <p class="font-medium text-slate-800">{{ $selectedSale->user->name ?? 'N/A' }}</p>
                                <p class="text-sm text-slate-500">{{ $selectedSale->branch->name ?? '' }}</p>
                            </div>
                            <div class="p-4 bg-slate-50 rounded-xl">
                                <p class="text-xs text-slate-500 mb-1">Fecha</p>
                                <p class="font-medium text-slate-800">{{ $selectedSale->created_at->format('d/m/Y H:i') }}</p>
                            </div>
                            <div class="p-4 bg-slate-50 rounded-xl">
                                <p class="text-xs text-slate-500 mb-1">Caja</p>
                                <p class="font-medium text-slate-800">{{ $selectedSale->cashReconciliation->cashRegister->name ?? 'N/A' }}</p>
                            </div>
                        </div>

                        <!-- Items -->
                        <div>
                            <h4 class="text-sm font-semibold text-slate-700 mb-3">Productos</h4>
                            <div class="border border-slate-200 rounded-xl overflow-hidden">
                                <table class="min-w-full divide-y divide-slate-200">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-slate-500">Producto</th>
                                            <th class="px-4 py-2 text-center text-xs font-medium text-slate-500">Cant.</th>
                                            <th class="px-4 py-2 text-right text-xs font-medium text-slate-500">Precio</th>
                                            <th class="px-4 py-2 text-right text-xs font-medium text-slate-500">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-200">
                                        @foreach($selectedSale->items as $item)
                                        <tr>
                                            <td class="px-4 py-2">
                                                <p class="text-sm text-slate-800">{{ $item->product_name }}</p>
                                                <p class="text-xs text-slate-500">{{ $item->product_sku }}</p>
                                            </td>
                                            <td class="px-4 py-2 text-center text-sm">{{ $item->quantity }}</td>
                                            <td class="px-4 py-2 text-right text-sm">${{ number_format($item->unit_price, 0, ',', '.') }}</td>
                                            <td class="px-4 py-2 text-right text-sm font-medium">${{ number_format($item->total, 0, ',', '.') }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Payments -->
                        <div>
                            <h4 class="text-sm font-semibold text-slate-700 mb-3">Pagos</h4>
                            <div class="space-y-2">
                                @foreach($selectedSale->payments as $payment)
                                <div class="flex items-center justify-between p-3 bg-slate-50 rounded-xl">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-slate-200 flex items-center justify-center">
                                            <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z"></path></svg>
                                        </div>
                                        <span class="text-sm text-slate-700">{{ $payment->paymentMethod->name ?? 'N/A' }}</span>
                                    </div>
                                    <span class="font-medium text-slate-800">${{ number_format($payment->amount, 0, ',', '.') }}</span>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Totals -->
                        <div class="border-t border-slate-200 pt-4">
                            <div class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span class="text-slate-500">Subtotal</span>
                                    <span class="text-slate-700">${{ number_format($selectedSale->subtotal, 0, ',', '.') }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-slate-500">Impuestos</span>
                                    <span class="text-slate-700">${{ number_format($selectedSale->tax_total, 0, ',', '.') }}</span>
                                </div>
                                @if($selectedSale->discount > 0)
                                <div class="flex justify-between text-sm">
                                    <span class="text-slate-500">Descuento</span>
                                    <span class="text-red-600">-${{ number_format($selectedSale->discount, 0, ',', '.') }}</span>
                                </div>
                                @endif
                                <div class="flex justify-between text-lg font-bold pt-2 border-t border-slate-200">
                                    <span class="text-slate-800">Total</span>
                                    <span class="text-slate-900">${{ number_format($selectedSale->total, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <!-- Reprint Receipt -->
                            <button wire:click="reprintReceipt({{ $selectedSale->id }})" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-purple-700 bg-purple-50 border border-purple-200 rounded-xl hover:bg-purple-100 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                Reimprimir
                            </button>
                            
                            <!-- View Electronic PDF -->
                            @if($selectedSale->is_electronic && $selectedSale->cufe && $selectedSale->dian_public_url)
                            <button wire:click="viewElectronicPdf({{ $selectedSale->id }})" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-xl hover:bg-blue-100 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                Ver PDF DIAN
                            </button>
                            @endif
                            
                            <!-- View Reprints History -->
                            @if($selectedSale->reprints->count() > 0)
                            <button wire:click="viewReprints({{ $selectedSale->id }})" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-slate-600 bg-slate-100 border border-slate-200 rounded-xl hover:bg-slate-200 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Historial ({{ $selectedSale->reprints->count() }})
                            </button>
                            @endif
                        </div>
                        <button wire:click="closeDetailModal" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Reprints History Modal -->
    @if($showReprintsModal)
    <div class="relative z-[100]">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="closeReprintsModal"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-md bg-white rounded-2xl shadow-xl">
                    <!-- Header -->
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">Historial de Reimpresiones</h3>
                            <p class="text-sm text-slate-500">{{ count($selectedSaleReprints) }} registro(s)</p>
                        </div>
                        <button wire:click="closeReprintsModal" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    <div class="px-6 py-4 max-h-[60vh] overflow-y-auto">
                        @if(count($selectedSaleReprints) > 0)
                        <div class="space-y-3">
                            @foreach($selectedSaleReprints as $reprint)
                            <div class="flex items-center gap-4 p-3 bg-slate-50 rounded-xl">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center {{ $reprint->type === 'pos' ? 'bg-purple-100' : 'bg-blue-100' }}">
                                    @if($reprint->type === 'pos')
                                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                    @else
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-slate-800">{{ $reprint->user->name ?? 'Usuario desconocido' }}</p>
                                    <p class="text-xs text-slate-500">
                                        {{ $reprint->type === 'pos' ? 'Recibo POS' : 'PDF Electrónico' }}
                                        · {{ $reprint->created_at->format('d/m/Y H:i') }}
                                    </p>
                                    @if($reprint->ip_address)
                                    <p class="text-xs text-slate-400">IP: {{ $reprint->ip_address }}</p>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @else
                        <div class="text-center py-8 text-slate-500">
                            <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <p>No hay reimpresiones registradas</p>
                        </div>
                        @endif
                    </div>

                    <!-- Footer -->
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end">
                        <button wire:click="closeReprintsModal" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- JavaScript for print and URL events -->
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('print-receipt', (data) => {
                const printWindow = window.open('/receipt/' + data.saleId, '_blank', 'width=400,height=600');
                if (printWindow) {
                    printWindow.focus();
                }
            });
            
            Livewire.on('open-url', (data) => {
                window.open(data.url, '_blank');
            });
        });
    </script>
</div>

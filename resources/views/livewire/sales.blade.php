<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Ventas</h1>
            <p class="text-slate-500 mt-1">Historial de ventas y facturas electrónicas</p>
        </div>
        <div class="flex items-center gap-3">
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
            <div class="lg:col-span-2">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                    <input wire:model.live.debounce.300ms="search" type="text" class="block w-full pl-10 pr-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 placeholder-slate-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] transition-all sm:text-sm" placeholder="Buscar por factura, cliente...">
                </div>
            </div>
            <div>
                <input wire:model.live="dateFrom" type="date" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm">
            </div>
            <div>
                <input wire:model.live="dateTo" type="date" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm">
            </div>
            <div>
                <select wire:model.live="filterElectronic" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm">
                    <option value="">Todos los tipos</option>
                    <option value="electronic">Electrónica ✓</option>
                    <option value="pos">Solo POS</option>
                    <option value="failed">Con error DIAN</option>
                </select>
            </div>
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
                        <th class="px-6 py-4 text-center text-sm font-semibold text-slate-500 uppercase">Estado</th>
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
                                @if($sale->credit_notes_count > 0 || $sale->refunds_count > 0)
                                <div class="flex gap-1 mt-1">
                                    @if($sale->credit_notes_count > 0)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-amber-100 text-amber-700">NC: {{ $sale->credit_notes_count }}</span>
                                    @endif
                                    @if($sale->refunds_count > 0)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-red-100 text-red-700">DEV: {{ $sale->refunds_count }}</span>
                                    @endif
                                </div>
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
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($sale->is_electronic)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700">FE</span>
                            @else
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-600">POS</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($sale->is_electronic && $sale->cufe)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">✓</span>
                            @elseif($sale->is_electronic && !$sale->cufe)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">Error</span>
                            @else
                            <span class="text-slate-400 text-xs">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <button wire:click="viewSale({{ $sale->id }})" class="p-2 text-slate-400 hover:text-[#ff7261] hover:bg-orange-50 rounded-lg transition-colors" title="Ver detalle">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                </button>
                                <button wire:click="reprintReceipt({{ $sale->id }})" class="p-2 text-slate-400 hover:text-purple-500 hover:bg-purple-50 rounded-lg transition-colors relative" title="Reimprimir">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                </button>
                                @if($sale->is_electronic && $sale->cufe)
                                <button wire:click="openCreditNoteModal({{ $sale->id }})" class="p-2 text-slate-400 hover:text-amber-500 hover:bg-amber-50 rounded-lg transition-colors" title="Nota Crédito">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"></path></svg>
                                </button>
                                @elseif(!$sale->is_electronic || !$sale->cufe)
                                <button wire:click="openRefundModal({{ $sale->id }})" class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Devolución">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z"></path></svg>
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
                            </div>
                            <p class="mt-2 text-xs text-green-600 font-mono break-all">CUFE: {{ $selectedSale->cufe }}</p>
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
                                <button wire:click="retryElectronicInvoice({{ $selectedSale->id }})" class="px-3 py-1.5 text-xs font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">Reintentar</button>
                            </div>
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
                                <div class="flex justify-between text-lg font-bold pt-2 border-t border-slate-200">
                                    <span class="text-slate-800">Total</span>
                                    <span class="text-slate-900">${{ number_format($selectedSale->total, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Credit Notes / Refunds History -->
                        @if($selectedSale->creditNotes->count() > 0 || $selectedSale->refunds->count() > 0)
                        <div class="border-t border-slate-200 pt-4">
                            <h4 class="text-sm font-semibold text-slate-700 mb-3">Notas Crédito / Devoluciones</h4>
                            <div class="space-y-2">
                                @foreach($selectedSale->creditNotes as $cn)
                                @if($cn->status === 'validated' && $cn->cufe)
                                <div class="flex items-center justify-between p-3 bg-amber-50 border border-amber-200 rounded-xl">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <p class="font-medium text-amber-800">{{ $cn->number }}</p>
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-green-100 text-green-700">✓ DIAN</span>
                                        </div>
                                        <p class="text-xs text-amber-600">{{ $cn->created_at->format('d/m/Y H:i') }} - {{ $cn->type === 'total' ? 'Total' : 'Parcial' }}</p>
                                        @if($cn->dian_number)
                                        <p class="text-xs text-amber-500">DIAN: {{ $cn->dian_number }}</p>
                                        @endif
                                    </div>
                                    <div class="text-right">
                                        <p class="font-bold text-amber-800">-${{ number_format($cn->total, 0, ',', '.') }}</p>
                                        @if($cn->dian_public_url)
                                        <button wire:click="viewCreditNotePdf({{ $cn->id }})" class="text-xs text-amber-600 hover:text-amber-800">Ver PDF</button>
                                        @endif
                                    </div>
                                </div>
                                @else
                                <div class="p-3 bg-red-50 border border-red-200 rounded-xl">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <p class="font-medium text-red-800">{{ $cn->number }}</p>
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-red-100 text-red-700">Error DIAN</span>
                                            </div>
                                            <p class="text-xs text-red-600">{{ $cn->created_at->format('d/m/Y H:i') }} - {{ $cn->type === 'total' ? 'Total' : 'Parcial' }}</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-bold text-red-800">-${{ number_format($cn->total, 0, ',', '.') }}</p>
                                            <button wire:click="retryCreditNote({{ $cn->id }})" class="text-xs text-red-600 hover:text-red-800 font-medium">Reintentar</button>
                                        </div>
                                    </div>
                                    @if($cn->dian_response && isset($cn->dian_response['message']))
                                    <div class="mt-2 p-2 bg-red-100 rounded-lg">
                                        <p class="text-xs text-red-700">{{ $cn->dian_response['message'] }}</p>
                                        @if(isset($cn->dian_response['errors']))
                                        <ul class="mt-1 text-xs text-red-600 list-disc list-inside">
                                            @foreach((array) $cn->dian_response['errors'] as $error)
                                            <li>{{ is_array($error) ? implode(', ', $error) : $error }}</li>
                                            @endforeach
                                        </ul>
                                        @endif
                                    </div>
                                    @endif
                                </div>
                                @endif
                                @endforeach
                                @foreach($selectedSale->refunds as $refund)
                                <div class="flex items-center justify-between p-3 bg-red-50 border border-red-200 rounded-xl">
                                    <div>
                                        <p class="font-medium text-red-800">{{ $refund->number }}</p>
                                        <p class="text-xs text-red-600">{{ $refund->created_at->format('d/m/Y H:i') }} - {{ $refund->type === 'total' ? 'Total' : 'Parcial' }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-bold text-red-800">-${{ number_format($refund->total, 0, ',', '.') }}</p>
                                        <button wire:click="printRefund({{ $refund->id }})" class="text-xs text-red-600 hover:text-red-800">Imprimir</button>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- Footer -->
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex flex-wrap items-center justify-between gap-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <button wire:click="reprintReceipt({{ $selectedSale->id }})" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-purple-700 bg-purple-50 border border-purple-200 rounded-xl hover:bg-purple-100">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                Reimprimir
                            </button>
                            @if($selectedSale->is_electronic && $selectedSale->cufe && $selectedSale->dian_public_url)
                            <button wire:click="viewElectronicPdf({{ $selectedSale->id }})" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-xl hover:bg-blue-100">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                PDF DIAN
                            </button>
                            <button wire:click="openCreditNoteModal({{ $selectedSale->id }})" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-amber-700 bg-amber-50 border border-amber-200 rounded-xl hover:bg-amber-100">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"></path></svg>
                                Nota Crédito
                            </button>
                            @elseif(!$selectedSale->is_electronic || !$selectedSale->cufe)
                            <button wire:click="openRefundModal({{ $selectedSale->id }})" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-red-700 bg-red-50 border border-red-200 rounded-xl hover:bg-red-100">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z"></path></svg>
                                Devolución
                            </button>
                            @endif
                        </div>
                        <button wire:click="closeDetailModal" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Credit Note Modal -->
    @if($showCreditNoteModal && $selectedSale)
    <div class="relative z-[100]">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="closeCreditNoteModal"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-xl">
                    <div class="px-6 py-4 border-b border-slate-200">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"></path></svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">Crear Nota Crédito</h3>
                                <p class="text-sm text-slate-500">Factura: {{ $selectedSale->dian_number }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 space-y-6 max-h-[60vh] overflow-y-auto">
                        <!-- Type Selection -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-3">Tipo de Nota Crédito</label>
                            <div class="grid grid-cols-2 gap-4">
                                <button type="button" wire:click="$set('creditNoteType', 'total')"
                                    class="p-4 rounded-xl border-2 transition-all text-left {{ $creditNoteType === 'total' ? 'border-amber-500 bg-amber-50' : 'border-slate-200 hover:border-amber-300' }}">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full {{ $creditNoteType === 'total' ? 'bg-amber-100' : 'bg-slate-100' }} flex items-center justify-center">
                                            <svg class="w-5 h-5 {{ $creditNoteType === 'total' ? 'text-amber-600' : 'text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        </div>
                                        <div>
                                            <p class="font-medium {{ $creditNoteType === 'total' ? 'text-amber-700' : 'text-slate-700' }}">Total</p>
                                            <p class="text-xs text-slate-500">Anula toda la factura</p>
                                        </div>
                                    </div>
                                </button>
                                <button type="button" wire:click="$set('creditNoteType', 'partial')"
                                    class="p-4 rounded-xl border-2 transition-all text-left {{ $creditNoteType === 'partial' ? 'border-amber-500 bg-amber-50' : 'border-slate-200 hover:border-amber-300' }}">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full {{ $creditNoteType === 'partial' ? 'bg-amber-100' : 'bg-slate-100' }} flex items-center justify-center">
                                            <svg class="w-5 h-5 {{ $creditNoteType === 'partial' ? 'text-amber-600' : 'text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        </div>
                                        <div>
                                            <p class="font-medium {{ $creditNoteType === 'partial' ? 'text-amber-700' : 'text-slate-700' }}">Parcial</p>
                                            <p class="text-xs text-slate-500">Selecciona productos</p>
                                        </div>
                                    </div>
                                </button>
                            </div>
                        </div>

                        <!-- Correction Concept -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Concepto de Corrección DIAN</label>
                            <select wire:model="creditNoteCorrectionCode" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500">
                                @foreach($correctionConcepts as $code => $name)
                                <option value="{{ $code }}">{{ $code }}. {{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Reason -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Motivo de la Nota Crédito</label>
                            <textarea wire:model="creditNoteReason" rows="2" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500" placeholder="Describa el motivo de la nota crédito..."></textarea>
                            @error('creditNoteReason') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <!-- Items -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-3">Productos a Acreditar</label>
                            <div class="border border-slate-200 rounded-xl overflow-hidden">
                                <table class="min-w-full divide-y divide-slate-200">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-slate-500">Producto</th>
                                            <th class="px-4 py-2 text-center text-xs font-medium text-slate-500">Disp.</th>
                                            <th class="px-4 py-2 text-center text-xs font-medium text-slate-500">Cant.</th>
                                            <th class="px-4 py-2 text-right text-xs font-medium text-slate-500">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-200">
                                        @foreach($creditNoteItems as $index => $item)
                                        <tr class="{{ $item['selected'] ? 'bg-amber-50' : '' }}">
                                            <td class="px-4 py-2">
                                                <label class="flex items-center gap-2 cursor-pointer">
                                                    <input type="checkbox" wire:model.live="creditNoteItems.{{ $index }}.selected" {{ $creditNoteType === 'total' ? 'disabled' : '' }} class="rounded border-slate-300 text-amber-600 focus:ring-amber-500">
                                                    <div>
                                                        <p class="text-sm text-slate-800">{{ $item['product_name'] }}</p>
                                                        <p class="text-xs text-slate-500">${{ number_format($item['unit_price'], 0, ',', '.') }} c/u</p>
                                                    </div>
                                                </label>
                                            </td>
                                            <td class="px-4 py-2 text-center text-sm text-slate-600">{{ $item['remaining_quantity'] }}</td>
                                            <td class="px-4 py-2 text-center">
                                                <input type="number" wire:model.live="creditNoteItems.{{ $index }}.quantity" min="1" max="{{ $item['remaining_quantity'] }}" {{ !$item['selected'] || $creditNoteType === 'total' ? 'disabled' : '' }} class="w-16 px-2 py-1 text-center border border-slate-300 rounded-lg text-sm disabled:bg-slate-100">
                                            </td>
                                            <td class="px-4 py-2 text-right text-sm font-medium text-slate-800">
                                                @if($item['selected'])
                                                ${{ number_format($item['unit_price'] * $item['quantity'] * (1 + $item['tax_rate']/100), 0, ',', '.') }}
                                                @else
                                                -
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Total -->
                        <div class="p-4 bg-amber-50 border border-amber-200 rounded-xl">
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-medium text-amber-800">Total Nota Crédito:</span>
                                <span class="text-2xl font-bold text-amber-900">${{ number_format($this->creditNoteTotal, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
                        <button wire:click="closeCreditNoteModal" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">Cancelar</button>
                        <button wire:click="processCreditNote" wire:loading.attr="disabled" class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-amber-500 to-orange-500 rounded-xl hover:from-amber-600 hover:to-orange-600 disabled:opacity-50">
                            <span wire:loading.remove wire:target="processCreditNote">Crear Nota Crédito</span>
                            <span wire:loading wire:target="processCreditNote">Procesando...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Refund Modal (POS) -->
    @if($showRefundModal && $selectedSale)
    <div class="relative z-[100]">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="closeRefundModal"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-xl">
                    <div class="px-6 py-4 border-b border-slate-200">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z"></path></svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">Crear Devolución</h3>
                                <p class="text-sm text-slate-500">Venta: {{ $selectedSale->invoice_number }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 space-y-6 max-h-[60vh] overflow-y-auto">
                        <!-- Type Selection -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-3">Tipo de Devolución</label>
                            <div class="grid grid-cols-2 gap-4">
                                <button type="button" wire:click="$set('refundType', 'total')"
                                    class="p-4 rounded-xl border-2 transition-all text-left {{ $refundType === 'total' ? 'border-red-500 bg-red-50' : 'border-slate-200 hover:border-red-300' }}">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full {{ $refundType === 'total' ? 'bg-red-100' : 'bg-slate-100' }} flex items-center justify-center">
                                            <svg class="w-5 h-5 {{ $refundType === 'total' ? 'text-red-600' : 'text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        </div>
                                        <div>
                                            <p class="font-medium {{ $refundType === 'total' ? 'text-red-700' : 'text-slate-700' }}">Total</p>
                                            <p class="text-xs text-slate-500">Devuelve todo</p>
                                        </div>
                                    </div>
                                </button>
                                <button type="button" wire:click="$set('refundType', 'partial')"
                                    class="p-4 rounded-xl border-2 transition-all text-left {{ $refundType === 'partial' ? 'border-red-500 bg-red-50' : 'border-slate-200 hover:border-red-300' }}">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full {{ $refundType === 'partial' ? 'bg-red-100' : 'bg-slate-100' }} flex items-center justify-center">
                                            <svg class="w-5 h-5 {{ $refundType === 'partial' ? 'text-red-600' : 'text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        </div>
                                        <div>
                                            <p class="font-medium {{ $refundType === 'partial' ? 'text-red-700' : 'text-slate-700' }}">Parcial</p>
                                            <p class="text-xs text-slate-500">Selecciona productos</p>
                                        </div>
                                    </div>
                                </button>
                            </div>
                        </div>

                        <!-- Reason -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Motivo de la Devolución</label>
                            <textarea wire:model="refundReason" rows="2" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-red-500/50 focus:border-red-500" placeholder="Describa el motivo de la devolución..."></textarea>
                            @error('refundReason') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <!-- Items -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-3">Productos a Devolver</label>
                            <div class="border border-slate-200 rounded-xl overflow-hidden">
                                <table class="min-w-full divide-y divide-slate-200">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-slate-500">Producto</th>
                                            <th class="px-4 py-2 text-center text-xs font-medium text-slate-500">Disp.</th>
                                            <th class="px-4 py-2 text-center text-xs font-medium text-slate-500">Cant.</th>
                                            <th class="px-4 py-2 text-right text-xs font-medium text-slate-500">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-200">
                                        @foreach($refundItems as $index => $item)
                                        <tr class="{{ $item['selected'] ? 'bg-red-50' : '' }}">
                                            <td class="px-4 py-2">
                                                <label class="flex items-center gap-2 cursor-pointer">
                                                    <input type="checkbox" wire:model.live="refundItems.{{ $index }}.selected" {{ $refundType === 'total' ? 'disabled' : '' }} class="rounded border-slate-300 text-red-600 focus:ring-red-500">
                                                    <div>
                                                        <p class="text-sm text-slate-800">{{ $item['product_name'] }}</p>
                                                        <p class="text-xs text-slate-500">${{ number_format($item['unit_price'], 0, ',', '.') }} c/u</p>
                                                    </div>
                                                </label>
                                            </td>
                                            <td class="px-4 py-2 text-center text-sm text-slate-600">{{ $item['remaining_quantity'] }}</td>
                                            <td class="px-4 py-2 text-center">
                                                <input type="number" wire:model.live="refundItems.{{ $index }}.quantity" min="1" max="{{ $item['remaining_quantity'] }}" {{ !$item['selected'] || $refundType === 'total' ? 'disabled' : '' }} class="w-16 px-2 py-1 text-center border border-slate-300 rounded-lg text-sm disabled:bg-slate-100">
                                            </td>
                                            <td class="px-4 py-2 text-right text-sm font-medium text-slate-800">
                                                @if($item['selected'])
                                                ${{ number_format($item['unit_price'] * $item['quantity'] * (1 + $item['tax_rate']/100), 0, ',', '.') }}
                                                @else
                                                -
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Total -->
                        <div class="p-4 bg-red-50 border border-red-200 rounded-xl">
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-medium text-red-800">Total Devolución:</span>
                                <span class="text-2xl font-bold text-red-900">${{ number_format($this->refundTotal, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
                        <button wire:click="closeRefundModal" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">Cancelar</button>
                        <button wire:click="processRefund" wire:loading.attr="disabled" class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-red-500 to-rose-500 rounded-xl hover:from-red-600 hover:to-rose-600 disabled:opacity-50">
                            <span wire:loading.remove wire:target="processRefund">Crear Devolución</span>
                            <span wire:loading wire:target="processRefund">Procesando...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- JavaScript -->
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('print-receipt', (data) => {
                window.open('/receipt/' + data.saleId, '_blank', 'width=400,height=600');
            });
            
            Livewire.on('print-refund', (data) => {
                window.open('/refund-receipt/' + data.refundId, '_blank', 'width=400,height=600');
            });
            
            Livewire.on('open-url', (data) => {
                window.open(data.url, '_blank');
            });
        });
    </script>
</div>

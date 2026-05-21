<div class="space-y-6"
    x-on:print-quote.window="(e) => { window.open('/quote-receipt/' + e.detail.quoteId, '_blank'); }">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Cotizaciones</h1>
            <p class="text-slate-500 mt-1">Listado de cotizaciones y conversión a ventas</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="flex items-center gap-4 px-4 py-2 bg-gradient-to-r from-orange-50 to-purple-50 rounded-xl border border-orange-200">
                <div class="text-center">
                    <p class="text-xs text-orange-600 font-medium">Periodo</p>
                    <p class="text-lg font-bold text-orange-700">${{ number_format($totalAmount, 0, ',', '.') }}</p>
                </div>
                <div class="h-8 w-px bg-orange-200"></div>
                <div class="text-center">
                    <p class="text-xs text-orange-600 font-medium">Total</p>
                    <p class="text-lg font-bold text-orange-700">{{ $totalQuotes }}</p>
                </div>
                <div class="h-8 w-px bg-orange-200"></div>
                <div class="text-center">
                    <p class="text-xs text-emerald-600 font-medium">Convertidas</p>
                    <p class="text-lg font-bold text-emerald-700">{{ $convertedCount }}</p>
                </div>
            </div>
            @if(auth()->user()->hasPermission('quotes.create'))
            <a href="{{ route('quotes.create') }}" class="px-4 py-2.5 bg-gradient-to-r from-[#ff7261] to-[#a855f7] text-white text-sm font-medium rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea] flex items-center gap-2 shadow-md">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Nueva Cotización
            </a>
            @endif
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-{{ $isSuperAdmin ? '6' : '5' }} gap-4">
            <div class="lg:col-span-2">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                    <input wire:model.live.debounce.300ms="search" type="text" class="block w-full pl-10 pr-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 placeholder-slate-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] transition-all sm:text-sm" placeholder="Buscar por número, cliente...">
                </div>
            </div>
            <div>
                <input wire:model.live="dateFrom" type="date" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm">
            </div>
            <div>
                <input wire:model.live="dateTo" type="date" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm">
            </div>
            <div>
                <select wire:model.live="filterStatus" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm">
                    <option value="">Todos los estados</option>
                    <option value="draft">Borrador</option>
                    <option value="converted">Convertida</option>
                    <option value="cancelled">Cancelada</option>
                </select>
            </div>
            @if($isSuperAdmin)
            <div>
                <select wire:model.live="filterBranch" class="w-full px-3 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm">
                    <option value="">Todas las sucursales</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}">{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
        </div>
    </div>

    <!-- Quotes Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Número</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Fecha</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Cliente</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Vendedor</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Válida hasta</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Total</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 uppercase">Estado</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-100">
                    @forelse($quotes as $quote)
                    @php
                        $isExpired = $quote->valid_until && $quote->valid_until->isPast() && $quote->status === 'draft';
                    @endphp
                    <tr class="hover:bg-slate-50/50 transition-colors {{ $isExpired ? 'bg-red-50/40' : '' }}">
                        <td class="px-4 py-3 text-sm font-semibold {{ $isExpired ? 'text-red-700' : 'text-slate-800' }}">
                            {{ $quote->quote_number }}
                            @if($isExpired)
                                <span class="ml-1 px-1.5 py-0.5 text-[10px] bg-red-100 text-red-700 rounded font-bold">VENCIDA</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $quote->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 text-sm text-slate-700">
                            @if($quote->customer)
                                <div class="font-medium">
                                    {{ $quote->customer->customer_type === 'juridico' ? $quote->customer->business_name : trim($quote->customer->first_name . ' ' . $quote->customer->last_name) }}
                                </div>
                                <div class="text-xs text-slate-500">{{ $quote->customer->document_number }}</div>
                            @else
                                <span class="text-slate-400">Sin cliente</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $quote->user->name ?? 'N/A' }}</td>
                        <td class="px-4 py-3 text-sm {{ $isExpired ? 'text-red-700 font-semibold' : 'text-slate-600' }}">
                            {{ $quote->valid_until ? $quote->valid_until->format('d/m/Y') : '—' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-right font-semibold text-slate-800">${{ number_format($quote->total, 2) }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($quote->status === 'draft')
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-700">Borrador</span>
                            @elseif($quote->status === 'converted')
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-700">Convertida</span>
                            @elseif($quote->status === 'cancelled')
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-slate-200 text-slate-600">Cancelada</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="inline-flex items-center gap-1">
                                <button wire:click="viewQuote({{ $quote->id }})" class="p-1.5 text-slate-500 hover:text-[#a855f7] hover:bg-purple-50 rounded-lg" title="Ver detalle">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                </button>
                                <button wire:click="printQuote({{ $quote->id }})" class="p-1.5 text-slate-500 hover:text-[#ff7261] hover:bg-orange-50 rounded-lg" title="Imprimir">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                </button>
                                @if($quote->status === 'draft' && auth()->user()->hasPermission('quotes.convert'))
                                <button wire:click="convertToSale({{ $quote->id }})" class="px-2 py-1 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white text-xs font-semibold rounded-lg hover:from-emerald-600 hover:to-emerald-700" title="Convertir a venta">
                                    Convertir
                                </button>
                                @endif
                                @if($quote->status === 'draft' && auth()->user()->hasPermission('quotes.delete'))
                                <button wire:click="openCancelModal({{ $quote->id }})" class="p-1.5 text-slate-500 hover:text-red-600 hover:bg-red-50 rounded-lg" title="Cancelar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center text-slate-500">
                            <svg class="w-12 h-12 mx-auto text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            <p class="font-medium">No hay cotizaciones</p>
                            <p class="text-sm text-slate-400 mt-1">Crea una nueva cotización para comenzar</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($quotes->hasPages())
        <div class="px-4 py-3 border-t border-slate-200 bg-slate-50">
            {{ $quotes->links() }}
        </div>
        @endif
    </div>

    <!-- Detail Modal -->
    @if($showDetailModal && $selectedQuote)
    <div class="relative z-[100]" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="closeDetail"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-3xl bg-white rounded-2xl shadow-xl">
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">{{ $selectedQuote->quote_number }}</h3>
                            <p class="text-sm text-slate-500">{{ $selectedQuote->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                        <button wire:click="closeDetail" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                    <div class="px-6 py-4 max-h-[70vh] overflow-y-auto space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-slate-50 rounded-xl p-3">
                                <p class="text-xs text-slate-500 uppercase">Cliente</p>
                                @if($selectedQuote->customer)
                                <p class="font-semibold text-slate-800">
                                    {{ $selectedQuote->customer->customer_type === 'juridico' ? $selectedQuote->customer->business_name : trim($selectedQuote->customer->first_name . ' ' . $selectedQuote->customer->last_name) }}
                                </p>
                                <p class="text-xs text-slate-500">{{ $selectedQuote->customer->document_number }}</p>
                                @else
                                <p class="text-slate-400 italic">Sin cliente</p>
                                @endif
                            </div>
                            <div class="bg-slate-50 rounded-xl p-3">
                                <p class="text-xs text-slate-500 uppercase">Vendedor</p>
                                <p class="font-semibold text-slate-800">{{ $selectedQuote->user->name ?? 'N/A' }}</p>
                                <p class="text-xs text-slate-500">{{ $selectedQuote->branch->name ?? '' }}</p>
                            </div>
                            <div class="bg-slate-50 rounded-xl p-3">
                                <p class="text-xs text-slate-500 uppercase">Válida hasta</p>
                                <p class="font-semibold {{ $selectedQuote->valid_until && $selectedQuote->valid_until->isPast() && $selectedQuote->status === 'draft' ? 'text-red-700' : 'text-slate-800' }}">
                                    {{ $selectedQuote->valid_until ? $selectedQuote->valid_until->format('d/m/Y') : '—' }}
                                </p>
                            </div>
                            <div class="bg-slate-50 rounded-xl p-3">
                                <p class="text-xs text-slate-500 uppercase">Estado</p>
                                @if($selectedQuote->status === 'draft')
                                    <p class="font-semibold text-blue-700">Borrador</p>
                                @elseif($selectedQuote->status === 'converted')
                                    <p class="font-semibold text-emerald-700">Convertida</p>
                                    @if($selectedQuote->convertedToSale)
                                        <p class="text-xs text-slate-500">→ Venta {{ $selectedQuote->convertedToSale->invoice_number }}</p>
                                    @endif
                                @else
                                    <p class="font-semibold text-slate-600">Cancelada</p>
                                @endif
                            </div>
                        </div>

                        @if($selectedQuote->notes)
                        <div class="bg-amber-50 border border-amber-200 rounded-xl p-3">
                            <p class="text-xs font-semibold text-amber-700 uppercase mb-1">Notas</p>
                            <p class="text-sm text-amber-900">{{ $selectedQuote->notes }}</p>
                        </div>
                        @endif

                        <div>
                            <p class="text-xs font-semibold text-slate-500 uppercase mb-2">Items</p>
                            <div class="border border-slate-200 rounded-xl overflow-hidden">
                                <table class="min-w-full">
                                    <thead class="bg-slate-50 text-xs">
                                        <tr>
                                            <th class="px-3 py-2 text-left">Producto</th>
                                            <th class="px-3 py-2 text-right">Cant.</th>
                                            <th class="px-3 py-2 text-right">P. Unit.</th>
                                            <th class="px-3 py-2 text-right">Desc.</th>
                                            <th class="px-3 py-2 text-right">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 text-sm">
                                        @foreach($selectedQuote->items as $item)
                                        <tr>
                                            <td class="px-3 py-2">
                                                <p class="font-medium">{{ $item->product_name }}</p>
                                                <p class="text-xs text-slate-500">{{ $item->product_sku }}</p>
                                            </td>
                                            <td class="px-3 py-2 text-right">{{ rtrim(rtrim(number_format($item->quantity, 3), '0'), '.') }}</td>
                                            <td class="px-3 py-2 text-right">${{ number_format($item->unit_price, 2) }}</td>
                                            <td class="px-3 py-2 text-right text-orange-600">{{ $item->discount_amount > 0 ? '-$' . number_format($item->discount_amount, 2) : '—' }}</td>
                                            <td class="px-3 py-2 text-right font-semibold">${{ number_format($item->total, 2) }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <div class="w-64 space-y-1 text-sm">
                                <div class="flex justify-between"><span class="text-slate-600">Subtotal</span><span>${{ number_format($selectedQuote->subtotal, 2) }}</span></div>
                                @if($selectedQuote->tax_total > 0)
                                <div class="flex justify-between"><span class="text-slate-600">IVA</span><span>${{ number_format($selectedQuote->tax_total, 2) }}</span></div>
                                @endif
                                @if($selectedQuote->discount > 0)
                                <div class="flex justify-between"><span class="text-slate-600">Descuento</span><span class="text-orange-600">-${{ number_format($selectedQuote->discount, 2) }}</span></div>
                                @endif
                                <div class="flex justify-between pt-2 border-t border-slate-200 font-bold text-base">
                                    <span>Total</span>
                                    <span class="bg-gradient-to-r from-[#ff7261] to-[#a855f7] bg-clip-text text-transparent">${{ number_format($selectedQuote->total, 2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
                        <button wire:click="printQuote({{ $selectedQuote->id }})" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                            Imprimir
                        </button>
                        @if($selectedQuote->status === 'draft' && auth()->user()->hasPermission('quotes.convert'))
                        <button wire:click="convertToSale({{ $selectedQuote->id }})" class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-emerald-500 to-emerald-600 rounded-xl hover:from-emerald-600 hover:to-emerald-700">
                            Convertir a venta
                        </button>
                        @endif
                        <button wire:click="closeDetail" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Cancel Confirmation Modal -->
    @if($showCancelModal)
    <div class="relative z-[100]" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="closeCancelModal"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-md bg-white rounded-2xl shadow-xl p-6 text-center">
                    <div class="mx-auto w-12 h-12 rounded-full bg-red-100 flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Cancelar cotización</h3>
                    <p class="text-slate-500 mb-6">¿Estás seguro de cancelar esta cotización? Esta acción no se puede deshacer.</p>
                    <div class="flex justify-center gap-3">
                        <button wire:click="closeCancelModal" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">Cerrar</button>
                        <button wire:click="confirmCancel" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-xl hover:bg-red-700">Cancelar cotización</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

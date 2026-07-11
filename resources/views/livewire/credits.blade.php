<div class="p-4 sm:p-6 lg:p-8">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Créditos y Pagos</h1>
            <p class="text-slate-500 mt-1">Gestiona cuentas por pagar y por cobrar</p>
        </div>
        @if(auth()->user()->hasPermission('credits.pay'))
        <div class="flex flex-wrap items-center gap-2">
            <button wire:click="openBulkPaymentModal('receivable')"
                class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl hover:from-blue-600 hover:to-blue-700 transition-all shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Cobrar a varias facturas
            </button>
            <button wire:click="openBulkPaymentModal('payable')"
                class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-red-500 to-red-600 rounded-xl hover:from-red-600 hover:to-red-700 transition-all shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Pagar a varias facturas
            </button>
        </div>
        @endif
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path></svg>
                </div>
                <div>
                    <p class="text-xs text-slate-500 uppercase font-semibold">Debemos (Proveedores)</p>
                    <p class="text-xl font-bold text-red-600">${{ number_format($totals['payable_remaining'], 2) }}</p>
                    <p class="text-xs text-slate-400">{{ $totals['payable_count'] }} crédito(s)</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                </div>
                <div>
                    <p class="text-xs text-slate-500 uppercase font-semibold">Nos Deben (Clientes)</p>
                    <p class="text-xl font-bold text-blue-600">${{ number_format($totals['receivable_remaining'], 2) }}</p>
                    <p class="text-xs text-slate-400">{{ $totals['receivable_count'] }} crédito(s)</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 mb-6">
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1">
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Buscar por número, proveedor o cliente..."
                    class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
            </div>
            <select wire:model.live="filterType" class="px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                <option value="">Todos los tipos</option>
                <option value="payable">Por Pagar (Proveedores)</option>
                <option value="receivable">Por Cobrar (Clientes)</option>
            </select>
            <select wire:model.live="filterStatus" class="px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                <option value="">Pendientes</option>
                <option value="pending">Solo Pendientes</option>
                <option value="partial">Solo Parciales</option>
                <option value="paid">Pagados</option>
            </select>
            @if($needsBranchSelection)
            <select wire:model.live="filterBranch" class="px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                <option value="">Todas las sucursales</option>
                @foreach($branches as $branch)
                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                @endforeach
            </select>
            @endif
        </div>
    </div>

    {{-- Credits Table --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-slate-500 uppercase">Tipo</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-slate-500 uppercase">Documento</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-slate-500 uppercase">Proveedor / Cliente</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-slate-500 uppercase">Fecha</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold text-slate-500 uppercase">Total</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold text-slate-500 uppercase">Pagado</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold text-slate-500 uppercase">Pendiente</th>
                        <th class="px-6 py-3 text-center text-sm font-semibold text-slate-500 uppercase">Estado</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold text-slate-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($items as $item)
                    @php
                        $remaining = $item->credit_amount - $item->paid_amount;
                        $percentage = $item->credit_amount > 0 ? ($item->paid_amount / $item->credit_amount) * 100 : 0;
                        $isPurchase = $item->record_type === 'purchase';
                    @endphp
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-4">
                            @if($isPurchase)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path></svg>
                                Por Pagar
                            </span>
                            @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                                Por Cobrar
                            </span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <p class="font-semibold text-slate-800">{{ $item->document_number }}</p>
                            @if($item->extra_doc)
                            <p class="text-xs text-slate-400">Fact: {{ $item->extra_doc }}</p>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <p class="font-medium text-slate-700">{{ $item->entity_name }}</p>
                            <p class="text-xs text-slate-400">{{ $item->branch_name }}</p>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-600">
                            {{ $item->date->format('d/m/Y') }}
                            @if($item->due_date)
                            <p class="text-xs {{ $item->due_date->isPast() ? 'text-red-500 font-medium' : 'text-slate-400' }}">
                                Vence: {{ $item->due_date->format('d/m/Y') }}
                                @if($item->due_date->isPast()) (Vencido) @endif
                            </p>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right font-semibold text-slate-800">${{ number_format($item->credit_amount, 2) }}</td>
                        <td class="px-6 py-4 text-right font-medium text-green-600">${{ number_format($item->paid_amount, 2) }}</td>
                        <td class="px-6 py-4 text-right font-bold {{ $isPurchase ? 'text-red-600' : 'text-blue-600' }}">${{ number_format($remaining, 2) }}</td>
                        <td class="px-6 py-4 text-center">
                            @if($item->payment_status === 'paid')
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">Pagado</span>
                            @elseif($item->payment_status === 'partial')
                            <div>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-700">Parcial</span>
                                <div class="w-full bg-slate-200 rounded-full h-1.5 mt-1">
                                    <div class="bg-amber-500 h-1.5 rounded-full" style="width: {{ min($percentage, 100) }}%"></div>
                                </div>
                            </div>
                            @else
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">Pendiente</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-1">
                                @if($item->payment_status !== 'paid')
                                @if(auth()->user()->hasPermission('credits.pay'))
                                <button wire:click="openPaymentModal({{ $item->id }}, '{{ $item->record_type }}')" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-lg hover:from-[#e55a4a] hover:to-[#9333ea] transition-all" title="{{ $isPurchase ? 'Registrar pago' : 'Registrar cobro' }}">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    {{ $isPurchase ? 'Pagar' : 'Cobrar' }}
                                </button>
                                @endif
                                @endif
                                <button wire:click="viewHistory({{ $item->id }}, '{{ $item->record_type }}')" class="p-2 text-slate-400 hover:text-[#a855f7] hover:bg-purple-50 rounded-lg transition-colors" title="Ver historial">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </button>
                                @if($item->paid_amount > 0 && $item->record_type === 'sale')
                                <a href="{{ route('credit-receipt.show', ['type' => $item->record_type, 'id' => $item->id]) }}?print=auto" target="_blank" class="p-2 text-slate-400 hover:text-blue-500 hover:bg-blue-50 rounded-lg transition-colors" title="Imprimir pagos">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-6 py-12 text-center">
                            <svg class="w-12 h-12 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <p class="text-slate-500 font-medium">No hay créditos pendientes</p>
                            <p class="text-sm text-slate-400">Los créditos de compras y ventas aparecerán aquí</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Payment Modal --}}
    @if($isPaymentModalOpen)
    <div class="relative z-[100]" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="$set('isPaymentModalOpen', false)"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-xl">
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">{{ $paymentCreditType === 'payable' ? 'Registrar Pago' : 'Registrar Cobro' }}</h3>
                            <p class="text-sm text-slate-500">{{ $paymentEntityName }}</p>
                        </div>
                        <button wire:click="$set('isPaymentModalOpen', false)" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                    <div class="px-6 py-4 space-y-4">
                        <div class="bg-slate-50 rounded-xl p-4 space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-500">Total crédito:</span>
                                <span class="font-semibold text-slate-800">${{ number_format($paymentTotal, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-500">Ya pagado:</span>
                                <span class="font-medium text-green-600">${{ number_format($paymentPaid, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-sm border-t border-slate-200 pt-2">
                                <span class="text-slate-700 font-medium">Saldo pendiente:</span>
                                <span class="font-bold text-red-600">${{ number_format($paymentRemaining, 2) }}</span>
                            </div>
                        </div>

                        <label class="flex items-center gap-3 p-3 bg-green-50 border border-green-200 rounded-xl cursor-pointer">
                            <input wire:model.live="paymentMarkComplete" type="checkbox" class="w-4 h-4 text-green-600 border-slate-300 rounded focus:ring-green-500">
                            <div>
                                <span class="text-sm font-medium text-green-700">Marcar como pagado completo</span>
                                <p class="text-xs text-green-600">Se registrará el pago por ${{ number_format($paymentRemaining, 2) }}</p>
                            </div>
                        </label>

                        {{-- Payment Lines --}}
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-sm font-medium text-slate-700">Medios de pago *</label>
                                <button wire:click="addPaymentLine" type="button" class="inline-flex items-center gap-1 text-xs font-medium text-purple-600 hover:text-purple-800 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                                    Agregar método
                                </button>
                            </div>
                            <div class="space-y-2">
                                @foreach($paymentLines as $index => $line)
                                <div class="flex items-center gap-2">
                                    <select wire:model="paymentLines.{{ $index }}.payment_method_id" class="flex-1 px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                        <option value="">Método...</option>
                                        @foreach($paymentMethods as $method)
                                        <option value="{{ $method->id }}">{{ $method->name }}</option>
                                        @endforeach
                                    </select>
                                    @if(!$paymentMarkComplete || count($paymentLines) > 1)
                                    <div class="relative w-32">
                                        <span class="absolute inset-y-0 left-0 pl-2 flex items-center text-slate-400 text-sm">$</span>
                                        <input wire:model.live="paymentLines.{{ $index }}.amount" type="number" step="0.01" min="0.01"
                                            class="w-full pl-6 pr-2 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]"
                                            placeholder="0.00">
                                    </div>
                                    @endif
                                    @if(count($paymentLines) > 1)
                                    <button wire:click="removePaymentLine({{ $index }})" type="button" class="p-1.5 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                    @endif
                                </div>
                                @endforeach
                            </div>

                            {{-- Total de líneas vs saldo --}}
                            @if(!$paymentMarkComplete || count($paymentLines) > 1)
                            @php
                                $linesTotal = collect($paymentLines)->sum(fn($l) => (float) ($l['amount'] ?? 0));
                            @endphp
                            <div class="mt-2 flex justify-between text-sm px-1">
                                <span class="text-slate-500">Total a pagar:</span>
                                <span class="font-semibold {{ $linesTotal > $paymentRemaining ? 'text-red-600' : 'text-slate-800' }}">${{ number_format($linesTotal, 2) }}</span>
                            </div>
                            @if($linesTotal > $paymentRemaining)
                            <p class="text-xs text-red-500 mt-1">El total excede el saldo pendiente</p>
                            @endif
                            @endif
                        </div>

                        <label class="flex items-center gap-3 p-3 bg-amber-50 border border-amber-200 rounded-xl cursor-pointer">
                            <input wire:model="paymentAffectsCash" type="checkbox" class="w-4 h-4 text-amber-600 border-slate-300 rounded focus:ring-amber-500">
                            <div>
                                <span class="text-sm font-medium text-amber-700">¿Afecta caja?</span>
                                <p class="text-xs text-amber-600">Si se marca, el movimiento se registrará en el arqueo de caja actual</p>
                            </div>
                        </label>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Notas</label>
                            <textarea wire:model="paymentNotes" rows="2" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="Observaciones del pago..."></textarea>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3 rounded-b-2xl">
                        <button wire:click="$set('isPaymentModalOpen', false)" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">Cancelar</button>
                        <button wire:click="storePayment" class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea]">
                            <span wire:loading.remove wire:target="storePayment">Registrar Pago</span>
                            <span wire:loading wire:target="storePayment">Procesando...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Bulk Payment Modal --}}
    @if($isBulkModalOpen)
    <div class="relative z-[100]" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="closeBulkPaymentModal"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-4xl bg-white rounded-2xl shadow-xl">
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">
                                {{ $bulkType === 'receivable' ? 'Cobrar a varias facturas' : 'Pagar a varias facturas' }}
                            </h3>
                            <p class="text-sm text-slate-500">Distribuye un pago entre varias facturas con sus métodos de pago</p>
                        </div>
                        <button wire:click="closeBulkPaymentModal" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    <div class="px-6 py-4 max-h-[75vh] overflow-y-auto">
                        {{-- Type toggle --}}
                        <div class="grid grid-cols-2 gap-3 mb-4">
                            <button wire:click="setBulkType('receivable')" type="button"
                                class="p-3 rounded-xl border-2 transition-all {{ $bulkType === 'receivable' ? 'border-blue-500 bg-blue-50' : 'border-slate-200 hover:border-blue-300' }}">
                                <div class="flex items-center gap-2 justify-center">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                                    <span class="font-medium text-sm {{ $bulkType === 'receivable' ? 'text-blue-700' : 'text-slate-600' }}">Cobrar a Cliente</span>
                                </div>
                            </button>
                            <button wire:click="setBulkType('payable')" type="button"
                                class="p-3 rounded-xl border-2 transition-all {{ $bulkType === 'payable' ? 'border-red-500 bg-red-50' : 'border-slate-200 hover:border-red-300' }}">
                                <div class="flex items-center gap-2 justify-center">
                                    <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path></svg>
                                    <span class="font-medium text-sm {{ $bulkType === 'payable' ? 'text-red-700' : 'text-slate-600' }}">Pagar a Proveedor</span>
                                </div>
                            </button>
                        </div>

                        {{-- Entity search --}}
                        @if(!$bulkSelectedEntity)
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                {{ $bulkType === 'receivable' ? 'Buscar cliente' : 'Buscar proveedor' }} *
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                </div>
                                <input wire:model.live.debounce.300ms="bulkEntitySearch" type="text"
                                    class="w-full pl-9 pr-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]"
                                    placeholder="{{ $bulkType === 'receivable' ? 'Nombre, documento, razón social...' : 'Nombre del proveedor...' }}">
                            </div>

                            @if(strlen(trim($bulkEntitySearch)) >= 2)
                            <div class="mt-2 border border-slate-200 rounded-xl divide-y divide-slate-100 max-h-60 overflow-y-auto">
                                @forelse($bulkEntityResults as $ent)
                                <button wire:click="selectBulkEntity({{ $ent['id'] }})" type="button"
                                    class="w-full text-left px-3 py-2 hover:bg-slate-50 flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-[#ff7261] to-[#a855f7] flex items-center justify-center text-white font-bold flex-shrink-0">
                                        {{ strtoupper(substr($ent['name'], 0, 1)) }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-semibold text-sm text-slate-800 truncate">{{ $ent['name'] }}</p>
                                        @if($ent['doc'])
                                        <p class="text-xs text-slate-500">{{ $ent['doc'] }}</p>
                                        @endif
                                    </div>
                                </button>
                                @empty
                                <p class="text-sm text-slate-400 text-center py-4">Sin resultados con créditos pendientes</p>
                                @endforelse
                            </div>
                            @else
                            <p class="text-xs text-slate-400 mt-1">Escribe al menos 2 caracteres</p>
                            @endif
                        </div>
                        @else
                        {{-- Selected entity --}}
                        <div class="mb-4 flex items-center gap-3 p-3 bg-slate-50 rounded-xl">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-[#ff7261] to-[#a855f7] flex items-center justify-center text-white font-bold">
                                {{ strtoupper(substr($bulkSelectedEntity['name'], 0, 1)) }}
                            </div>
                            <div class="flex-1">
                                <p class="font-semibold text-slate-800">{{ $bulkSelectedEntity['name'] }}</p>
                                <p class="text-xs text-slate-500">{{ count($bulkInvoices) }} factura(s) pendiente(s)</p>
                            </div>
                            <button wire:click="clearBulkEntity" type="button" class="px-3 py-1 text-xs font-medium text-slate-600 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">
                                Cambiar
                            </button>
                        </div>

                        {{-- Invoices list --}}
                        @if(count($bulkInvoices) > 0)
                        <div class="space-y-3">
                            @foreach($bulkInvoices as $idx => $inv)
                            @php
                                $allocated = (float) ($inv['allocated'] ?? 0);
                                $exceeds = $allocated > $inv['remaining'] + 0.01;
                                $fullyAllocated = $allocated > 0 && abs($allocated - $inv['remaining']) < 0.01;
                            @endphp
                            <div class="border-2 rounded-xl p-3 transition-colors {{ $exceeds ? 'border-red-300 bg-red-50' : ($fullyAllocated ? 'border-emerald-300 bg-emerald-50/30' : ($allocated > 0 ? 'border-amber-300 bg-amber-50/30' : 'border-slate-200')) }}">
                                <div class="flex items-start justify-between gap-3 mb-2">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="font-semibold text-slate-800">{{ $inv['document_number'] }}</span>
                                            <span class="text-xs text-slate-500">{{ $inv['date'] }}</span>
                                            @if($inv['branch_name'])
                                            <span class="text-xs px-1.5 py-0.5 bg-slate-100 text-slate-600 rounded">{{ $inv['branch_name'] }}</span>
                                            @endif
                                        </div>
                                        <div class="flex gap-3 text-xs text-slate-500 mt-1">
                                            <span>Total: <strong class="text-slate-700">${{ number_format($inv['total'], 2) }}</strong></span>
                                            <span>Pagado: <strong class="text-green-600">${{ number_format($inv['paid'], 2) }}</strong></span>
                                            <span>Saldo: <strong class="{{ $bulkType === 'payable' ? 'text-red-600' : 'text-blue-600' }}">${{ number_format($inv['remaining'], 2) }}</strong></span>
                                        </div>
                                    </div>
                                    <div class="text-right flex-shrink-0">
                                        <p class="text-[10px] uppercase font-bold text-slate-400">Asignado</p>
                                        <p class="text-lg font-bold {{ $exceeds ? 'text-red-600' : ($fullyAllocated ? 'text-emerald-600' : 'text-slate-800') }}">
                                            ${{ number_format($allocated, 2) }}
                                        </p>
                                    </div>
                                </div>

                                {{-- Payment methods for this invoice --}}
                                <div class="space-y-2 pt-2 border-t border-slate-200">
                                    @foreach($inv['lines'] as $li => $line)
                                    <div class="flex items-center gap-2">
                                        <select wire:model="bulkInvoices.{{ $idx }}.lines.{{ $li }}.payment_method_id"
                                            class="flex-1 px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                            <option value="">Método de pago...</option>
                                            @foreach($paymentMethods as $method)
                                            <option value="{{ $method->id }}">{{ $method->name }}</option>
                                            @endforeach
                                        </select>
                                        <div class="relative w-36">
                                            <span class="absolute inset-y-0 left-0 pl-2 flex items-center text-slate-400 text-sm">$</span>
                                            <input wire:model.live.debounce.400ms="bulkInvoices.{{ $idx }}.lines.{{ $li }}.amount"
                                                type="number" step="0.01" min="0"
                                                class="w-full pl-6 pr-2 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]"
                                                placeholder="0.00">
                                        </div>
                                        @if(count($inv['lines']) > 1)
                                        <button wire:click="removeBulkPaymentLine({{ $idx }}, {{ $li }})" type="button" class="p-1.5 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3"></path></svg>
                                        </button>
                                        @endif
                                    </div>
                                    @endforeach
                                    <button wire:click="addBulkPaymentLine({{ $idx }})" type="button"
                                        class="text-xs font-medium text-purple-600 hover:text-purple-800 inline-flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                                        Agregar otro método para esta factura
                                    </button>
                                </div>

                                @if($exceeds)
                                <p class="text-xs text-red-600 font-medium mt-2">⚠ El monto asignado excede el saldo pendiente</p>
                                @endif
                            </div>
                            @endforeach
                        </div>

                        {{-- Total pago --}}
                        <div class="mt-4 p-4 bg-gradient-to-r from-slate-50 to-slate-100 rounded-xl flex items-center justify-between">
                            <div>
                                <p class="text-xs text-slate-500 uppercase font-bold">Total del pago</p>
                                <p class="text-xs text-slate-400">Suma de todas las asignaciones</p>
                            </div>
                            <p class="text-2xl font-bold bg-gradient-to-r from-[#ff7261] to-[#a855f7] bg-clip-text text-transparent">
                                ${{ number_format($this->bulkGrandTotal, 2) }}
                            </p>
                        </div>

                        {{-- Affects cash + notes --}}
                        <div class="mt-4 space-y-3">
                            <label class="flex items-center gap-3 p-3 bg-amber-50 border border-amber-200 rounded-xl cursor-pointer">
                                <input wire:model="bulkAffectsCash" type="checkbox" class="w-4 h-4 text-amber-600 border-slate-300 rounded focus:ring-amber-500">
                                <div>
                                    <span class="text-sm font-medium text-amber-700">¿Afecta caja?</span>
                                    <p class="text-xs text-amber-600">Si se marca, se registrará un movimiento por cada línea en el arqueo de caja actual</p>
                                </div>
                            </label>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Notas (aplican a todos los pagos)</label>
                                <textarea wire:model="bulkNotes" rows="2"
                                    class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]"
                                    placeholder="Observaciones..."></textarea>
                            </div>
                        </div>
                        @else
                        <p class="text-center text-slate-500 py-8 text-sm">
                            Este {{ $bulkType === 'receivable' ? 'cliente' : 'proveedor' }} no tiene facturas pendientes en la sucursal seleccionada
                        </p>
                        @endif
                        @endif
                    </div>

                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3 rounded-b-2xl">
                        <button wire:click="closeBulkPaymentModal" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">
                            Cancelar
                        </button>
                        @if($bulkSelectedEntity && count($bulkInvoices) > 0)
                        <button wire:click="storeBulkPayment"
                            class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea] disabled:opacity-50">
                            <span wire:loading.remove wire:target="storeBulkPayment">Registrar Pago Múltiple</span>
                            <span wire:loading wire:target="storeBulkPayment">Procesando...</span>
                        </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- History Modal --}}
    @if($isHistoryModalOpen)
    <div class="relative z-[100]" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="$set('isHistoryModalOpen', false)"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-xl">
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">Historial de Pagos</h3>
                            <p class="text-sm text-slate-500">{{ $historyEntityName }}</p>
                        </div>
                        <button wire:click="$set('isHistoryModalOpen', false)" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                    <div class="px-6 py-4 max-h-96 overflow-y-auto">
                        @if(count($historyPayments) > 0)
                        <div class="space-y-3">
                            @foreach($historyPayments as $payment)
                            <div class="flex items-start gap-3 p-3 bg-slate-50 rounded-xl">
                                <div class="w-8 h-8 rounded-full {{ $payment->affects_cash ? 'bg-amber-100' : 'bg-green-100' }} flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <svg class="w-4 h-4 {{ $payment->affects_cash ? 'text-amber-600' : 'text-green-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between">
                                        <p class="text-sm font-semibold text-slate-800">${{ number_format($payment->amount, 2) }}</p>
                                        <span class="text-xs text-slate-400">{{ $payment->created_at->format('d/m/Y H:i') }}</span>
                                    </div>
                                    <p class="text-xs text-slate-500">{{ $payment->paymentMethod->name ?? '-' }} · {{ $payment->user->name ?? '-' }}</p>
                                    @if($payment->affects_cash)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-700 mt-1">Afectó caja</span>
                                    @endif
                                    @if($payment->notes)
                                    <p class="text-xs text-slate-400 mt-1">{{ $payment->notes }}</p>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @else
                        <div class="text-center py-8">
                            <svg class="w-10 h-10 text-slate-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <p class="text-sm text-slate-500">No hay pagos registrados</p>
                        </div>
                        @endif
                    </div>
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end rounded-b-2xl">
                        <button wire:click="$set('isHistoryModalOpen', false)" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

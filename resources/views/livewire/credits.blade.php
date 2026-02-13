<div class="p-4 sm:p-6 lg:p-8">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Créditos y Pagos</h1>
            <p class="text-slate-500 mt-1">Gestiona cuentas por pagar y por cobrar</p>
        </div>
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

                        @if(!$paymentMarkComplete)
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Monto del abono *</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">$</span>
                                <input wire:model="paymentAmount" type="number" step="0.01" min="0.01" max="{{ $paymentRemaining }}"
                                    class="w-full pl-8 pr-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]"
                                    placeholder="0.00">
                            </div>
                            @error('paymentAmount') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                        @endif

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Método de pago *</label>
                            <select wire:model="paymentMethodId" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                <option value="">Seleccionar...</option>
                                @foreach($paymentMethods as $method)
                                <option value="{{ $method->id }}">{{ $method->name }}</option>
                                @endforeach
                            </select>
                            @error('paymentMethodId') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
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

<div>
    <x-toast />

    @script
    <script>
        $wire.on('print-cash-reconciliation', ({ id }) => {
            window.open(`/cash-reconciliation-receipt/${id}`, '_blank');
        });
    </script>
    @endscript

    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-800">Arqueos de Caja</h1>
        <p class="text-slate-500 text-sm mt-1">Gestión de apertura y cierre de cajas</p>
    </div>

    <!-- Filters and Actions -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 mb-6">
        <div class="flex flex-col md:flex-row gap-4 justify-between">
            <div class="flex flex-col sm:flex-row gap-4 flex-1">
                <!-- Search -->
                <div class="relative flex-1 max-w-xs">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Buscar caja..."
                        class="w-full pl-10 pr-4 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500">
                </div>

                @if($needsBranchSelection)
                <!-- Branch Filter -->
                <select wire:model.live="filterBranch"
                    class="px-4 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500">
                    <option value="">Todas las sucursales</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
                @endif

                <!-- Status Filter -->
                <select wire:model.live="filterStatus"
                    class="px-4 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500">
                    <option value="">Todos los estados</option>
                    <option value="open">Abiertos</option>
                    <option value="closed">Cerrados</option>
                </select>
            </div>

            @if(auth()->user()->hasPermission('cash_reconciliations.create'))
            <button wire:click="openCashRegister"
                class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-[#ff7261] to-[#a855f7] text-white rounded-xl text-sm font-medium hover:opacity-90 transition-opacity">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Abrir Caja
            </button>
            @endif
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Caja</th>
                        @if($needsBranchSelection)
                        <th class="text-left px-6 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Sucursal</th>
                        @endif
                        <th class="text-left px-6 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Apertura</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Cierre</th>
                        <th class="text-right px-6 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Monto Inicial</th>
                        <th class="text-right px-6 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Monto Cierre</th>
                        <th class="text-right px-6 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Diferencia</th>
                        <th class="text-center px-6 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Estado</th>
                        <th class="text-center px-6 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($items as $item)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="font-medium text-slate-800">{{ $item->cashRegister->name }}</div>
                            <div class="text-xs text-slate-500">Nº {{ $item->cashRegister->number }}</div>
                        </td>
                        @if($needsBranchSelection)
                        <td class="px-6 py-4 text-sm text-slate-600">{{ $item->branch->name }}</td>
                        @endif
                        <td class="px-6 py-4">
                            <div class="text-sm text-slate-800">{{ $item->opened_at->format('d/m/Y H:i') }}</div>
                            <div class="text-xs text-slate-500">{{ $item->openedByUser->name }}</div>
                        </td>
                        <td class="px-6 py-4">
                            @if($item->closed_at)
                            <div class="text-sm text-slate-800">{{ $item->closed_at->format('d/m/Y H:i') }}</div>
                            <div class="text-xs text-slate-500">{{ $item->closedByUser?->name }}</div>
                            @else
                            <span class="text-sm text-slate-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="text-sm font-medium text-slate-800">${{ number_format($item->opening_amount, 2) }}</span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            @if($item->closing_amount !== null)
                            <span class="text-sm font-medium text-slate-800">${{ number_format($item->closing_amount, 2) }}</span>
                            @else
                            <span class="text-sm text-slate-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            @if($item->closing_amount !== null)
                            @php
                                $itemExpected = $item->calculateExpectedAmount();
                                $itemDifference = (float) $item->closing_amount - $itemExpected;
                            @endphp
                            <span class="text-sm font-medium {{ $itemDifference >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                {{ $itemDifference >= 0 ? '+' : '' }}${{ number_format($itemDifference, 2) }}
                            </span>
                            @else
                            <span class="text-sm text-slate-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($item->status === 'open')
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                Abierta
                            </span>
                            @else
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-slate-500"></span>
                                Cerrada
                            </span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-center gap-1">
                                <button wire:click="viewReconciliation({{ $item->id }})"
                                    class="p-2 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                    title="Ver detalle">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>
                                @if($item->status === 'closed')
                                <a href="{{ route('cash-reconciliation-receipt.show', $item->id) }}" target="_blank"
                                    class="p-2 text-slate-400 hover:text-purple-600 hover:bg-purple-50 rounded-lg transition-colors"
                                    title="Imprimir cierre">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                    </svg>
                                </a>
                                @if(auth()->user()->hasPermission('cash_reconciliations.edit_closed'))
                                <button wire:click="openEditModal({{ $item->id }})"
                                    class="p-2 text-slate-400 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-colors"
                                    title="Editar arqueo">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                @endif
                                <button wire:click="viewHistory({{ $item->id }})"
                                    class="p-2 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors"
                                    title="Historial de ediciones">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </button>
                                @endif
                                @if($item->status === 'open' && auth()->user()->hasPermission('cash_reconciliations.edit'))
                                <button wire:click="openMovementModal({{ $item->id }})"
                                    class="p-2 text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors"
                                    title="Registrar movimiento">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </button>
                                <button wire:click="closeCashRegister({{ $item->id }})"
                                    class="p-2 text-slate-400 hover:text-orange-600 hover:bg-orange-50 rounded-lg transition-colors"
                                    title="Cerrar caja">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $needsBranchSelection ? 9 : 8 }}" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <svg class="w-12 h-12 text-slate-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                <p class="text-slate-500 font-medium">No hay arqueos registrados</p>
                                <p class="text-slate-400 text-sm mt-1">Abre una caja para comenzar</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($items->hasPages())
        <div class="px-6 py-4 border-t border-slate-200">
            {{ $items->links() }}
        </div>
        @endif
    </div>

    <!-- Open Cash Register Modal -->
    @if($isOpenModalOpen)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <!-- Backdrop -->
            <div class="fixed inset-0 transition-opacity bg-slate-900/75" wire:click="$set('isOpenModalOpen', false)"></div>

            <!-- Modal -->
            <div class="relative z-10 w-full max-w-lg mx-auto bg-white rounded-2xl shadow-xl">
                <div class="px-6 py-4 border-b border-slate-200">
                    <h3 class="text-lg font-semibold text-slate-800">Abrir Caja</h3>
                    @if($hasAssignedCashRegister)
                    <p class="text-sm text-slate-500 mt-1">{{ $userCashRegister->name }} - Nº {{ $userCashRegister->number }}</p>
                    @else
                    <p class="text-sm text-slate-500 mt-1">Ingrese los datos para abrir la caja</p>
                    @endif
                </div>
                <div class="px-6 py-4 space-y-4 bg-slate-50">
                    @if(!$hasAssignedCashRegister)
                        @if($needsBranchSelection)
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Sucursal <span class="text-red-500">*</span></label>
                            <select wire:model.live="selectedBranchId"
                                class="w-full px-4 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 bg-white">
                                <option value="">Seleccionar sucursal...</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                            @error('selectedBranchId') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        @endif

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Caja <span class="text-red-500">*</span></label>
                            <select wire:model="cash_register_id"
                                class="w-full px-4 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 bg-white"
                                @if(count($cashRegisters) === 0) disabled @endif>
                                <option value="">Seleccionar caja...</option>
                                @foreach($cashRegisters as $register)
                                    <option value="{{ $register->id }}">{{ $register->number }} - {{ $register->name }}</option>
                                @endforeach
                            </select>
                            @if($needsBranchSelection && !$selectedBranchId)
                            <span class="text-slate-400 text-xs mt-1 block">Seleccione una sucursal primero</span>
                            @elseif(count($cashRegisters) === 0 && $selectedBranchId)
                            <span class="text-amber-500 text-xs mt-1 block">No hay cajas activas en esta sucursal</span>
                            @endif
                            @error('cash_register_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    @else
                        <!-- Show assigned cash register info -->
                        <div class="bg-white rounded-xl p-4 border border-slate-200">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-[#ff7261] to-[#a855f7] rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-medium text-slate-800">{{ $userCashRegister->name }}</p>
                                    <p class="text-sm text-slate-500">Caja Nº {{ $userCashRegister->number }} - {{ $userCashRegister->branch->name }}</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Monto Inicial <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">$</span>
                            <input wire:model="opening_amount" type="number" step="0.01" min="0"
                                class="w-full pl-8 pr-4 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 bg-white"
                                placeholder="0.00">
                        </div>
                        @error('opening_amount') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Notas (opcional)</label>
                        <textarea wire:model="opening_notes" rows="2"
                            class="w-full px-4 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 bg-white"
                            placeholder="Observaciones de apertura..."></textarea>
                    </div>
                </div>
                <div class="px-6 py-4 flex justify-end gap-3 border-t border-slate-200 bg-white rounded-b-2xl">
                    <button wire:click="$set('isOpenModalOpen', false)" type="button"
                        class="px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 rounded-xl transition-colors">
                        Cancelar
                    </button>
                    <button wire:click="storeOpen" type="button"
                        class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:opacity-90 transition-opacity">
                        Abrir Caja
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Close Cash Register Modal -->
    @if($isCloseModalOpen && $currentReconciliation)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <!-- Backdrop -->
            <div class="fixed inset-0 transition-opacity bg-slate-900/75" wire:click="$set('isCloseModalOpen', false)"></div>

            <!-- Modal -->
            <div class="relative z-10 w-full max-w-2xl mx-auto bg-white rounded-2xl shadow-xl max-h-[90vh] overflow-hidden flex flex-col">
                <div class="px-6 py-4 border-b border-slate-200">
                    <h3 class="text-lg font-semibold text-slate-800">Cerrar Caja</h3>
                    <p class="text-sm text-slate-500 mt-1">{{ $currentReconciliation->cashRegister->name }} - Nº {{ $currentReconciliation->cashRegister->number }}</p>
                </div>
                <div class="px-6 py-4 space-y-4 bg-slate-50 overflow-y-auto flex-1">
                    <!-- Summary -->
                    <div class="bg-white rounded-xl p-4 border border-slate-200">
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-slate-500">Apertura:</span>
                                <span class="font-medium text-slate-800 ml-2">{{ $currentReconciliation->opened_at->format('d/m/Y H:i') }}</span>
                            </div>
                            <div>
                                <span class="text-slate-500">Abierta por:</span>
                                <span class="font-medium text-slate-800 ml-2">{{ $currentReconciliation->openedByUser->name }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Sales Summary by Payment Method -->
                    @php
                        $salesByMethod = $currentReconciliation->getSalesByPaymentMethod();
                        $totalSales = $currentReconciliation->total_sales;
                        $salesCount = $currentReconciliation->sales_count;
                        $cashSales = $currentReconciliation->total_cash_sales;
                    @endphp
                    @if($salesCount > 0)
                    <div class="bg-white rounded-xl p-4 border border-slate-200">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="font-medium text-slate-800 text-sm">Ventas del Turno</h4>
                            <span class="text-xs text-slate-500">{{ $salesCount }} venta(s)</span>
                        </div>
                        <div class="space-y-2 text-sm">
                            @foreach($salesByMethod as $method)
                            <div class="flex justify-between items-center py-2 px-3 bg-slate-50 rounded-lg">
                                <div class="flex items-center gap-2">
                                    <div class="w-2 h-2 rounded-full {{ str_contains(strtolower($method['method_name']), 'efectivo') ? 'bg-emerald-500' : 'bg-blue-500' }}"></div>
                                    <span class="text-slate-700">{{ $method['method_name'] }}</span>
                                    <span class="text-xs text-slate-400">({{ $method['count'] }})</span>
                                </div>
                                <span class="font-medium text-slate-800">${{ number_format($method['total'], 2) }}</span>
                            </div>
                            @endforeach
                            <div class="flex justify-between pt-2 border-t border-slate-200">
                                <span class="text-slate-700 font-medium">Total Ventas:</span>
                                <span class="font-bold text-slate-800">${{ number_format($totalSales, 2) }}</span>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Cash Movement Summary -->
                    <div class="bg-white rounded-xl p-4 border border-slate-200">
                        <h4 class="font-medium text-slate-800 text-sm mb-3">Resumen de Caja (Efectivo)</h4>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-slate-500">Monto inicial:</span>
                                <span class="font-medium text-slate-800">${{ number_format($currentReconciliation->opening_amount, 2) }}</span>
                            </div>
                            @if($cashSales > 0)
                            <div class="flex justify-between">
                                <span class="text-emerald-600">+ Ventas en efectivo:</span>
                                <span class="font-medium text-emerald-600">${{ number_format($cashSales, 2) }}</span>
                            </div>
                            @endif
                            @if($currentReconciliation->total_income > 0)
                            <div class="flex justify-between">
                                <span class="text-emerald-600">+ Otros ingresos:</span>
                                <span class="font-medium text-emerald-600">${{ number_format($currentReconciliation->total_income, 2) }}</span>
                            </div>
                            @endif
                            @if($currentReconciliation->total_expenses > 0)
                            <div class="flex justify-between">
                                <span class="text-red-600">- Egresos:</span>
                                <span class="font-medium text-red-600">${{ number_format($currentReconciliation->total_expenses, 2) }}</span>
                            </div>
                            @endif
                            <div class="flex justify-between pt-2 border-t border-slate-200">
                                <span class="text-slate-700 font-medium">Efectivo esperado en caja:</span>
                                <span class="font-bold text-[#ff7261]">${{ number_format($currentReconciliation->calculateExpectedAmount(), 2) }}</span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Monto de Cierre (Efectivo contado) <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">$</span>
                            <input wire:model="closing_amount" type="number" step="0.01" min="0"
                                class="w-full pl-8 pr-4 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 bg-white"
                                placeholder="0.00">
                        </div>
                        @error('closing_amount') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Notas de Cierre (opcional)</label>
                        <textarea wire:model="closing_notes" rows="2"
                            class="w-full px-4 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 bg-white"
                            placeholder="Observaciones de cierre..."></textarea>
                    </div>
                </div>
                <div class="px-6 py-4 flex justify-end gap-3 border-t border-slate-200 bg-white rounded-b-2xl">
                    <button wire:click="$set('isCloseModalOpen', false)" type="button"
                        class="px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 rounded-xl transition-colors">
                        Cancelar
                    </button>
                    <button wire:click="storeClose" type="button"
                        class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-orange-500 to-red-500 rounded-xl hover:opacity-90 transition-opacity">
                        Cerrar Caja
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- View Modal -->
    @if($isViewModalOpen && $viewReconciliation)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <!-- Backdrop -->
            <div class="fixed inset-0 transition-opacity bg-slate-900/75" wire:click="$set('isViewModalOpen', false)"></div>

            <!-- Modal -->
            <div class="relative z-10 w-full max-w-2xl mx-auto bg-white rounded-2xl shadow-xl max-h-[90vh] overflow-hidden flex flex-col">
                <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-800">Detalle de Arqueo</h3>
                        <p class="text-sm text-slate-500 mt-1">{{ $viewReconciliation->cashRegister->name }} - Nº {{ $viewReconciliation->cashRegister->number }}</p>
                    </div>
                    @if($viewReconciliation->status === 'open')
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        Abierta
                    </span>
                    @else
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-500"></span>
                        Cerrada
                    </span>
                    @endif
                </div>
                <div class="px-6 py-4 space-y-4 bg-slate-50 overflow-y-auto flex-1">
                    <div class="bg-white rounded-xl p-4 border border-slate-200 space-y-3">
                        <h4 class="font-medium text-slate-800 text-sm">Apertura</h4>
                        <div class="grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <span class="text-slate-500">Fecha:</span>
                                <span class="font-medium text-slate-800 ml-2">{{ $viewReconciliation->opened_at->format('d/m/Y H:i') }}</span>
                            </div>
                            <div>
                                <span class="text-slate-500">Usuario:</span>
                                <span class="font-medium text-slate-800 ml-2">{{ $viewReconciliation->openedByUser->name }}</span>
                            </div>
                            <div class="col-span-2">
                                <span class="text-slate-500">Monto:</span>
                                <span class="font-medium text-slate-800 ml-2">${{ number_format($viewReconciliation->opening_amount, 2) }}</span>
                            </div>
                            @if($viewReconciliation->opening_notes)
                            <div class="col-span-2">
                                <span class="text-slate-500">Notas:</span>
                                <p class="text-slate-700 mt-1">{{ $viewReconciliation->opening_notes }}</p>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Movements Section -->
                    <div class="bg-white rounded-xl p-4 border border-slate-200 space-y-3">
                        <div class="flex justify-between items-center">
                            <h4 class="font-medium text-slate-800 text-sm">Movimientos de Caja</h4>
                            <div class="flex gap-3 text-xs">
                                <span class="text-emerald-600">Ingresos: ${{ number_format($viewReconciliation->total_income, 2) }}</span>
                                <span class="text-red-600">Egresos: ${{ number_format($viewReconciliation->total_expenses, 2) }}</span>
                            </div>
                        </div>
                        @if($viewReconciliation->movements->count() > 0)
                        <div class="divide-y divide-slate-100 max-h-48 overflow-y-auto">
                            @foreach($viewReconciliation->movements as $movement)
                            <div class="py-2 flex justify-between items-start">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        @if($movement->type === 'income')
                                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                        @else
                                        <span class="w-2 h-2 rounded-full bg-red-500"></span>
                                        @endif
                                        <span class="text-sm font-medium text-slate-800">{{ $movement->concept }}</span>
                                    </div>
                                    <div class="text-xs text-slate-500 ml-4 mt-0.5">
                                        {{ $movement->created_at->format('d/m/Y H:i') }} - {{ $movement->user->name }}
                                        @if($movement->notes)
                                        <span class="block text-slate-400 mt-0.5">{{ $movement->notes }}</span>
                                        @endif
                                    </div>
                                </div>
                                <span class="text-sm font-medium {{ $movement->type === 'income' ? 'text-emerald-600' : 'text-red-600' }}">
                                    {{ $movement->type === 'income' ? '+' : '-' }}${{ number_format($movement->amount, 2) }}
                                </span>
                            </div>
                            @endforeach
                        </div>
                        @else
                        <p class="text-sm text-slate-400 text-center py-4">No hay movimientos registrados</p>
                        @endif
                    </div>

                    <!-- Sales Section -->
                    @php
                        $viewSalesByMethod = $viewReconciliation->getSalesByPaymentMethod();
                        $viewTotalSales = $viewReconciliation->total_sales;
                        $viewSalesCount = $viewReconciliation->sales_count;
                        $viewCashSales = $viewReconciliation->total_cash_sales;
                    @endphp
                    <div class="bg-white rounded-xl p-4 border border-slate-200 space-y-3">
                        <div class="flex justify-between items-center">
                            <h4 class="font-medium text-slate-800 text-sm">Ventas</h4>
                            <span class="text-xs text-slate-500">{{ $viewSalesCount }} venta(s) - Total: ${{ number_format($viewTotalSales, 2) }}</span>
                        </div>
                        @if($viewSalesCount > 0)
                        <div class="space-y-2">
                            @foreach($viewSalesByMethod as $method)
                            <div class="flex justify-between items-center py-2 px-3 bg-slate-50 rounded-lg text-sm">
                                <div class="flex items-center gap-2">
                                    <div class="w-2 h-2 rounded-full {{ str_contains(strtolower($method['method_name']), 'efectivo') ? 'bg-emerald-500' : 'bg-blue-500' }}"></div>
                                    <span class="text-slate-700">{{ $method['method_name'] }}</span>
                                    <span class="text-xs text-slate-400">({{ $method['count'] }} pagos)</span>
                                </div>
                                <span class="font-medium text-slate-800">${{ number_format($method['total'], 2) }}</span>
                            </div>
                            @endforeach
                        </div>
                        <div class="pt-2 border-t border-slate-200 text-sm">
                            <div class="flex justify-between">
                                <span class="text-slate-500">Efectivo en caja por ventas:</span>
                                <span class="font-medium text-emerald-600">${{ number_format($viewCashSales, 2) }}</span>
                            </div>
                        </div>
                        @else
                        <p class="text-sm text-slate-400 text-center py-4">No hay ventas registradas</p>
                        @endif
                    </div>

                    @if($viewReconciliation->status === 'closed')
                    <div class="bg-white rounded-xl p-4 border border-slate-200 space-y-3">
                        <h4 class="font-medium text-slate-800 text-sm">Cierre</h4>
                        <div class="grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <span class="text-slate-500">Fecha:</span>
                                <span class="font-medium text-slate-800 ml-2">{{ $viewReconciliation->closed_at->format('d/m/Y H:i') }}</span>
                            </div>
                            <div>
                                <span class="text-slate-500">Usuario:</span>
                                <span class="font-medium text-slate-800 ml-2">{{ $viewReconciliation->closedByUser?->name }}</span>
                            </div>
                            <div>
                                <span class="text-slate-500">Monto cierre:</span>
                                <span class="font-medium text-slate-800 ml-2">${{ number_format($viewReconciliation->closing_amount, 2) }}</span>
                            </div>
                            @php
                                $viewExpected = $viewReconciliation->calculateExpectedAmount();
                                $viewDifference = (float) $viewReconciliation->closing_amount - $viewExpected;
                            @endphp
                            <div>
                                <span class="text-slate-500">Esperado:</span>
                                <span class="font-medium text-slate-800 ml-2">${{ number_format($viewExpected, 2) }}</span>
                            </div>
                            <div class="col-span-2">
                                <span class="text-slate-500">Diferencia:</span>
                                <span class="font-medium ml-2 {{ $viewDifference >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                    {{ $viewDifference >= 0 ? '+' : '' }}${{ number_format($viewDifference, 2) }}
                                </span>
                            </div>
                            @if($viewReconciliation->closing_notes)
                            <div class="col-span-2">
                                <span class="text-slate-500">Notas:</span>
                                <p class="text-slate-700 mt-1">{{ $viewReconciliation->closing_notes }}</p>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Edit History in View Modal -->
                    @if($viewReconciliation->edits->count() > 0)
                    <div class="bg-white rounded-xl p-4 border border-amber-200 space-y-3">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <h4 class="font-medium text-slate-800 text-sm">Historial de Ediciones ({{ $viewReconciliation->edits->count() }})</h4>
                        </div>
                        <div class="space-y-3 max-h-48 overflow-y-auto">
                            @foreach($viewReconciliation->edits->sortByDesc('created_at') as $edit)
                            <div class="border-l-2 border-amber-300 pl-3 py-1">
                                <div class="flex items-center gap-2 text-xs text-slate-500">
                                    <span class="font-medium text-slate-700">{{ $edit->user->name }}</span>
                                    <span>•</span>
                                    <span>{{ $edit->created_at->format('d/m/Y H:i') }}</span>
                                </div>
                                <div class="text-sm mt-1">
                                    @if($edit->field_changed === 'closing_amount')
                                    <span class="text-slate-600">Monto cierre:</span>
                                    <span class="text-red-500 line-through">${{ $edit->old_value }}</span>
                                    <span class="text-slate-400">→</span>
                                    <span class="text-emerald-600 font-medium">${{ $edit->new_value }}</span>
                                    @elseif($edit->field_changed === 'closing_notes')
                                    <span class="text-slate-600">Notas cierre modificadas</span>
                                    @endif
                                </div>
                                <p class="text-xs text-slate-500 mt-1 italic">"{{ $edit->comment }}"</p>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                    @endif
                </div>
                <div class="px-6 py-4 flex justify-between border-t border-slate-200 bg-white rounded-b-2xl">
                    <div>
                        @if($viewReconciliation->status === 'closed')
                        <a href="{{ route('cash-reconciliation-receipt.show', $viewReconciliation->id) }}" target="_blank"
                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-purple-700 bg-purple-50 hover:bg-purple-100 rounded-xl transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            Imprimir Cierre
                        </a>
                        @endif
                    </div>
                    <button wire:click="$set('isViewModalOpen', false)" type="button"
                        class="px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 rounded-xl transition-colors">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Movement Modal -->
    @if($isMovementModalOpen)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-slate-900/75" wire:click="$set('isMovementModalOpen', false)"></div>
            <div class="relative z-10 w-full max-w-lg mx-auto bg-white rounded-2xl shadow-xl">
                <div class="px-6 py-4 border-b border-slate-200">
                    <h3 class="text-lg font-semibold text-slate-800">Registrar Movimiento</h3>
                </div>
                <div class="px-6 py-4 space-y-4 bg-slate-50">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-3">Tipo de Movimiento</label>
                        <div class="grid grid-cols-2 gap-3">
                            <!-- Income Card -->
                            <label class="cursor-pointer">
                                <input type="radio" wire:model.live="movement_type" value="income" class="sr-only peer">
                                <div class="p-4 rounded-xl border-2 transition-all peer-checked:border-emerald-500 peer-checked:bg-emerald-50 border-slate-200 hover:border-emerald-300 hover:bg-emerald-50/50">
                                    <div class="flex items-center gap-3 mb-2">
                                        <div class="w-10 h-10 rounded-lg bg-emerald-100 flex items-center justify-center">
                                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m6-6H6" />
                                            </svg>
                                        </div>
                                        <span class="font-semibold text-emerald-700">Ingreso</span>
                                    </div>
                                    <p class="text-xs text-slate-500">Dinero que entra a la caja</p>
                                </div>
                            </label>
                            <!-- Expense Card -->
                            <label class="cursor-pointer">
                                <input type="radio" wire:model.live="movement_type" value="expense" class="sr-only peer">
                                <div class="p-4 rounded-xl border-2 transition-all peer-checked:border-red-500 peer-checked:bg-red-50 border-slate-200 hover:border-red-300 hover:bg-red-50/50">
                                    <div class="flex items-center gap-3 mb-2">
                                        <div class="w-10 h-10 rounded-lg bg-red-100 flex items-center justify-center">
                                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                                            </svg>
                                        </div>
                                        <span class="font-semibold text-red-700">Egreso</span>
                                    </div>
                                    <p class="text-xs text-slate-500">Dinero que sale de la caja</p>
                                </div>
                            </label>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Monto <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">$</span>
                            <input wire:model="movement_amount" type="number" step="0.01" min="0.01"
                                class="w-full pl-8 pr-4 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 bg-white">
                        </div>
                        @error('movement_amount') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Concepto <span class="text-red-500">*</span></label>
                        <input wire:model="movement_concept" type="text"
                            class="w-full px-4 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 bg-white"
                            placeholder="Ej: Pago a proveedor, Venta de servicio...">
                        @error('movement_concept') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Notas (opcional)</label>
                        <textarea wire:model="movement_notes" rows="2"
                            class="w-full px-4 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 bg-white"></textarea>
                    </div>
                </div>
                <div class="px-6 py-4 flex justify-end gap-3 border-t border-slate-200 bg-white rounded-b-2xl">
                    <button wire:click="$set('isMovementModalOpen', false)" type="button"
                        class="px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 rounded-xl transition-colors">Cancelar</button>
                    <button wire:click="storeMovement" type="button"
                        class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:opacity-90 transition-opacity">Guardar</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Edit Cash Reconciliation Modal -->
    @if($isEditModalOpen)
    <div class="relative z-[100]" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="$set('isEditModalOpen', false)"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-xl">
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-slate-900">Editar Arqueo de Caja</h3>
                        </div>
                        <button wire:click="$set('isEditModalOpen', false)" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="px-6 py-4 space-y-4">
                        <div class="bg-amber-50 border border-amber-200 rounded-xl p-3">
                            <div class="flex items-start gap-2">
                                <svg class="w-5 h-5 text-amber-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                </svg>
                                <p class="text-sm text-amber-700">Los cambios quedarán registrados en el historial de ediciones. Debe indicar el motivo del cambio.</p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Monto de Cierre <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">$</span>
                                <input wire:model="edit_closing_amount" type="number" step="0.01" min="0"
                                    class="w-full pl-8 pr-4 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]"
                                    placeholder="0.00">
                            </div>
                            @error('edit_closing_amount') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Notas de Cierre</label>
                            <textarea wire:model="edit_closing_notes" rows="2"
                                class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]"
                                placeholder="Observaciones de cierre..."></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Motivo del cambio <span class="text-red-500">*</span></label>
                            <textarea wire:model="edit_comment" rows="3"
                                class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]"
                                placeholder="Explique por qué se modifica este arqueo..."></textarea>
                            @error('edit_comment') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3 rounded-b-2xl">
                        <button wire:click="$set('isEditModalOpen', false)"
                            class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">
                            Cancelar
                        </button>
                        <button wire:click="storeEdit"
                            class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:opacity-90 transition-opacity">
                            Guardar Cambios
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- History Modal -->
    @if($isHistoryModalOpen && $historyReconciliationId)
    <div class="relative z-[100]" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="$set('isHistoryModalOpen', false)"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-xl max-h-[80vh] overflow-hidden flex flex-col">
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-slate-900">Historial de Ediciones</h3>
                        </div>
                        <button wire:click="$set('isHistoryModalOpen', false)" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="px-6 py-4 overflow-y-auto flex-1">
                        @if($historyEdits->count() > 0)
                        <div class="space-y-4">
                            @foreach($historyEdits as $edit)
                            <div class="border-l-2 border-indigo-300 pl-4 py-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-slate-800">{{ $edit->user->name }}</span>
                                    <span class="text-xs text-slate-500">{{ $edit->created_at->format('d/m/Y H:i') }}</span>
                                </div>
                                <div class="mt-2 bg-slate-50 rounded-lg p-3 text-sm">
                                    @if($edit->field_changed === 'closing_amount')
                                    <div class="flex items-center gap-2">
                                        <span class="text-slate-600">Monto cierre:</span>
                                        <span class="text-red-500 line-through">${{ $edit->old_value }}</span>
                                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                        </svg>
                                        <span class="text-emerald-600 font-medium">${{ $edit->new_value }}</span>
                                    </div>
                                    @elseif($edit->field_changed === 'closing_notes')
                                    <div>
                                        <span class="text-slate-600">Notas de cierre:</span>
                                        <div class="mt-1 grid grid-cols-1 gap-1">
                                            <div class="text-xs"><span class="text-slate-400">Antes:</span> <span class="text-red-500">{{ $edit->old_value ?: '(vacío)' }}</span></div>
                                            <div class="text-xs"><span class="text-slate-400">Después:</span> <span class="text-emerald-600">{{ $edit->new_value ?: '(vacío)' }}</span></div>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                                <p class="text-xs text-slate-500 mt-2 italic">"{{ $edit->comment }}"</p>
                            </div>
                            @endforeach
                        </div>
                        @else
                        <div class="text-center py-8">
                            <svg class="w-12 h-12 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="text-slate-500 font-medium">Sin ediciones</p>
                            <p class="text-slate-400 text-sm mt-1">Este arqueo no ha sido modificado</p>
                        </div>
                        @endif
                    </div>
                    <div class="px-6 py-4 border-t border-slate-200 flex justify-end rounded-b-2xl">
                        <button wire:click="$set('isHistoryModalOpen', false)"
                            class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

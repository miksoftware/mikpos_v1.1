<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Compras</h1>
            <p class="text-slate-500 mt-1">Gestiona las compras de productos</p>
        </div>
        @if(auth()->user()->hasPermission('purchases.create'))
        <a href="{{ route('purchases.create') }}" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-[#ff7261] to-[#a855f7] hover:from-[#e55a4a] hover:to-[#9333ea] text-white text-sm font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-[1.02] transition-all duration-200">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Nueva Compra
        </a>
        @endif
    </div>

    {{-- Search and Filters --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
        <div class="flex flex-col lg:flex-row gap-4">
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <input wire:model.live.debounce.300ms="search" type="text" class="block w-full pl-10 pr-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 placeholder-slate-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] transition-all sm:text-sm" placeholder="Buscar por número, factura o proveedor...">
            </div>
            <div class="flex flex-col sm:flex-row gap-3">
                @if($needsBranchSelection)
                <select wire:model.live="filterBranch" class="px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] sm:text-sm min-w-[150px]">
                    <option value="">Todas las sucursales</option>
                    @foreach($branches as $branch)
                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
                @endif
                <select wire:model.live="filterStatus" class="px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] sm:text-sm min-w-[130px]">
                    <option value="">Todos los estados</option>
                    <option value="draft">Borrador</option>
                    <option value="completed">Completada</option>
                    <option value="cancelled">Cancelada</option>
                </select>
                <select wire:model.live="filterSupplier" class="px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] sm:text-sm min-w-[150px]">
                    <option value="">Todos los proveedores</option>
                    @foreach($suppliers as $supplier)
                    <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                    @endforeach
                </select>
                <input wire:model.live="dateFrom" type="date" class="px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] sm:text-sm">
                <input wire:model.live="dateTo" type="date" class="px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] sm:text-sm">
                @if($search || $filterStatus || $filterSupplier || $filterBranch || $dateFrom || $dateTo)
                <button wire:click="clearFilters" class="px-3 py-2.5 text-slate-500 hover:text-slate-700 hover:bg-slate-100 rounded-xl transition-colors text-sm font-medium">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Purchases Table --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Compra</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Proveedor</th>
                        @if($needsBranchSelection)
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Sucursal</th>
                        @endif
                        <th class="px-6 py-4 text-center text-sm font-semibold text-slate-500 uppercase">Fecha</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold text-slate-500 uppercase">Tipo</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-500 uppercase">Total</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold text-slate-500 uppercase">Estado</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($items as $item)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-4">
                            <div>
                                <div class="font-semibold text-slate-900">{{ $item->purchase_number }}</div>
                                @if($item->supplier_invoice)
                                <div class="text-sm text-slate-500">Fact: {{ $item->supplier_invoice }}</div>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            @if($item->supplier)
                            <span class="text-slate-700">{{ $item->supplier->name }}</span>
                            @else
                            <span class="text-slate-400">Sin proveedor</span>
                            @endif
                        </td>
                        @if($needsBranchSelection)
                        <td class="px-6 py-4">
                            <span class="text-slate-700">{{ $item->branch?->name ?? '-' }}</span>
                        </td>
                        @endif
                        <td class="px-6 py-4 text-center">
                            <span class="text-slate-700">{{ $item->purchase_date->format('d/m/Y') }}</span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium {{ $item->payment_type === 'credit' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                                {{ $item->getPaymentTypeLabel() }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="text-lg font-bold text-slate-800">${{ number_format($item->total, 2) }}</span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-sm font-medium
                                {{ $item->status === 'completed' ? 'bg-green-100 text-green-700' : '' }}
                                {{ $item->status === 'draft' ? 'bg-amber-100 text-amber-700' : '' }}
                                {{ $item->status === 'cancelled' ? 'bg-red-100 text-red-700' : '' }}
                            ">
                                {{ $item->getStatusLabel() }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <button wire:click="viewPurchase({{ $item->id }})" class="p-2 text-slate-400 hover:text-blue-500 hover:bg-blue-50 rounded-lg transition-colors" title="Ver detalle">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                </button>
                                @if($item->status === 'draft' && auth()->user()->hasPermission('purchases.edit'))
                                <button wire:click="continuePurchase({{ $item->id }})" class="p-2 text-slate-400 hover:text-indigo-500 hover:bg-indigo-50 rounded-lg transition-colors" title="Continuar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </button>
                                <button wire:click="completePurchase({{ $item->id }})" class="p-2 text-slate-400 hover:text-green-500 hover:bg-green-50 rounded-lg transition-colors" title="Completar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                </button>
                                @endif
                                @if($item->status === 'completed' && auth()->user()->hasPermission('purchases.edit'))
                                <button wire:click="confirmEditCompleted({{ $item->id }})" class="p-2 text-slate-400 hover:text-amber-500 hover:bg-amber-50 rounded-lg transition-colors" title="Editar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </button>
                                @endif
                                @if($item->status !== 'cancelled' && auth()->user()->hasPermission('purchases.edit'))
                                <button wire:click="cancelPurchase({{ $item->id }})" class="p-2 text-slate-400 hover:text-amber-500 hover:bg-amber-50 rounded-lg transition-colors" title="Cancelar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                                </button>
                                @endif
                                @if($item->status === 'draft' && auth()->user()->hasPermission('purchases.delete'))
                                <button wire:click="confirmDelete({{ $item->id }})" class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Eliminar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $needsBranchSelection ? 8 : 7 }}" class="px-6 py-12 text-center">
                            <svg class="w-12 h-12 text-slate-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                            <p class="text-slate-500">No hay compras registradas</p>
                            @if($search || $filterStatus || $filterSupplier || $filterBranch || $dateFrom || $dateTo)
                            <button wire:click="clearFilters" class="mt-2 text-[#ff7261] hover:underline text-sm">Limpiar filtros</button>
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if($items->hasPages())
    <div class="mt-6">
        {{ $items->links() }}
    </div>
    @endif

    {{-- View Modal --}}
    @if($isViewModalOpen && $viewingPurchase)
    <div class="relative z-[100]">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="$set('isViewModalOpen', false)"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-3xl bg-white rounded-2xl shadow-xl">
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">Compra {{ $viewingPurchase->purchase_number }}</h3>
                            <p class="text-sm text-slate-500">{{ $viewingPurchase->purchase_date->format('d/m/Y') }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium {{ $viewingPurchase->payment_type === 'credit' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                                {{ $viewingPurchase->getPaymentTypeLabel() }}
                            </span>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                {{ $viewingPurchase->status === 'completed' ? 'bg-green-100 text-green-700' : '' }}
                                {{ $viewingPurchase->status === 'draft' ? 'bg-amber-100 text-amber-700' : '' }}
                                {{ $viewingPurchase->status === 'cancelled' ? 'bg-red-100 text-red-700' : '' }}
                            ">
                                {{ $viewingPurchase->getStatusLabel() }}
                            </span>
                        </div>
                    </div>
                    <div class="px-6 py-4 max-h-[60vh] overflow-y-auto">
                        {{-- Info --}}
                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div>
                                <p class="text-sm text-slate-500">Proveedor</p>
                                <p class="font-medium text-slate-800">{{ $viewingPurchase->supplier?->name ?? 'Sin proveedor' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-slate-500">Factura Proveedor</p>
                                <p class="font-medium text-slate-800">{{ $viewingPurchase->supplier_invoice ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-slate-500">Registrado por</p>
                                <p class="font-medium text-slate-800">{{ $viewingPurchase->user?->name ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-slate-500">Sucursal</p>
                                <p class="font-medium text-slate-800">{{ $viewingPurchase->branch?->name ?? '-' }}</p>
                            </div>
                            @if($viewingPurchase->payment_type === 'cash')
                            <div>
                                <p class="text-sm text-slate-500">Método de Pago</p>
                                <p class="font-medium text-slate-800">{{ $viewingPurchase->paymentMethod?->name ?? '-' }}</p>
                            </div>
                            @else
                            <div>
                                <p class="text-sm text-slate-500">Fecha de Pago</p>
                                <p class="font-medium text-slate-800">{{ $viewingPurchase->payment_due_date?->format('d/m/Y') ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-slate-500">Monto Crédito</p>
                                <p class="font-medium text-slate-800">${{ number_format($viewingPurchase->credit_amount, 2) }}</p>
                            </div>
                            @if($viewingPurchase->paid_amount > 0)
                            <div>
                                <p class="text-sm text-slate-500">Abonado</p>
                                <p class="font-medium text-green-600">${{ number_format($viewingPurchase->paid_amount, 2) }} ({{ $viewingPurchase->partialPaymentMethod?->name ?? '-' }})</p>
                            </div>
                            <div>
                                <p class="text-sm text-slate-500">Saldo Pendiente</p>
                                <p class="font-medium text-red-600">${{ number_format($viewingPurchase->getRemainingCredit(), 2) }}</p>
                            </div>
                            @endif
                            @endif
                        </div>

                        {{-- Items --}}
                        <h4 class="font-semibold text-slate-800 mb-3">Productos</h4>
                        <div class="border border-slate-200 rounded-xl overflow-hidden mb-4">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Producto</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 uppercase">Cant.</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Costo</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200">
                                    @foreach($viewingPurchase->items as $pitem)
                                    <tr>
                                        <td class="px-4 py-3">
                                            <p class="font-medium text-slate-800">{{ $pitem->product?->name ?? 'Producto eliminado' }}</p>
                                            <p class="text-xs text-slate-500">{{ $pitem->product?->sku ?? '' }}</p>
                                        </td>
                                        <td class="px-4 py-3 text-center">{{ $pitem->quantity }} {{ $pitem->product?->unit?->abbreviation ?? 'und' }}</td>
                                        <td class="px-4 py-3 text-right">${{ number_format($pitem->unit_cost, 2) }}</td>
                                        <td class="px-4 py-3 text-right font-medium">${{ number_format($pitem->subtotal, 2) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Totals --}}
                        <div class="flex justify-end">
                            <div class="w-64 space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span class="text-slate-500">Subtotal</span>
                                    <span class="font-medium">${{ number_format($viewingPurchase->subtotal, 2) }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-slate-500">Impuestos</span>
                                    <span class="font-medium">${{ number_format($viewingPurchase->tax_amount, 2) }}</span>
                                </div>
                                @if($viewingPurchase->discount_amount > 0)
                                <div class="flex justify-between text-sm">
                                    <span class="text-slate-500">Descuentos</span>
                                    <span class="font-medium text-green-600">-${{ number_format($viewingPurchase->discount_amount, 2) }}</span>
                                </div>
                                @endif
                                <div class="flex justify-between pt-2 border-t border-slate-200">
                                    <span class="font-semibold text-slate-800">Total</span>
                                    <span class="text-xl font-bold text-[#ff7261]">${{ number_format($viewingPurchase->total, 2) }}</span>
                                </div>
                            </div>
                        </div>

                        @if($viewingPurchase->notes)
                        <div class="mt-4 p-3 bg-slate-50 rounded-xl">
                            <p class="text-sm text-slate-500">Notas</p>
                            <p class="text-slate-700">{{ $viewingPurchase->notes }}</p>
                        </div>
                        @endif
                    </div>
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end">
                        <button wire:click="$set('isViewModalOpen', false)" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Delete Confirmation Modal --}}
    @if($isDeleteModalOpen)
    <div class="relative z-[100]">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="$set('isDeleteModalOpen', false)"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-md bg-white rounded-2xl shadow-xl p-6 text-center">
                    <div class="mx-auto w-12 h-12 rounded-full bg-red-100 flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Eliminar Compra</h3>
                    <p class="text-slate-500 mb-6">¿Estás seguro de eliminar esta compra? Esta acción no se puede deshacer.</p>
                    <div class="flex justify-center gap-3">
                        <button wire:click="$set('isDeleteModalOpen', false)" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">Cancelar</button>
                        <button wire:click="delete" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-xl hover:bg-red-700">
                            <span wire:loading.remove wire:target="delete">Eliminar</span>
                            <span wire:loading wire:target="delete">Eliminando...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Edit Completed Confirmation Modal --}}
    @if($isEditConfirmModalOpen)
    <div class="relative z-[100]">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="$set('isEditConfirmModalOpen', false)"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-md bg-white rounded-2xl shadow-xl p-6 text-center">
                    <div class="mx-auto w-12 h-12 rounded-full bg-amber-100 flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Editar Compra Completada</h3>
                    <p class="text-slate-500 mb-6">¿Estás seguro de editar esta compra? Los cambios afectarán el inventario de los productos.</p>
                    <div class="flex justify-center gap-3">
                        <button wire:click="$set('isEditConfirmModalOpen', false)" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">Cancelar</button>
                        <button wire:click="editCompleted" class="px-4 py-2 text-sm font-medium text-white bg-amber-600 rounded-xl hover:bg-amber-700">
                            <span wire:loading.remove wire:target="editCompleted">Sí, Editar</span>
                            <span wire:loading wire:target="editCompleted">Cargando...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

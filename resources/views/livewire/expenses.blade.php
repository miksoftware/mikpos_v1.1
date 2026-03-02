<div class="space-y-6">
    <x-toast />

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Gastos</h1>
            <p class="text-slate-500 mt-1">Registro y control de gastos del negocio</p>
        </div>
        @if(auth()->user()->hasPermission('expenses.create'))
        <button wire:click="create" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-[#ff7261] to-[#a855f7] hover:from-[#e55a4a] hover:to-[#9333ea] text-white text-sm font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-[1.02] transition-all duration-200">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Nuevo Gasto
        </button>
        @endif
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
        <div class="flex flex-col lg:flex-row gap-4">
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <input wire:model.live.debounce.300ms="search" type="text" class="block w-full pl-10 pr-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 placeholder-slate-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] transition-all sm:text-sm" placeholder="Buscar por detalle...">
            </div>
            <div class="flex flex-col sm:flex-row gap-3">
                <select wire:model.live="filterPaymentMethod" class="px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] sm:text-sm min-w-[160px]">
                    <option value="">Todas las formas de pago</option>
                    @foreach($paymentMethods as $pm)
                    <option value="{{ $pm->id }}">{{ $pm->name }}</option>
                    @endforeach
                </select>
                <input wire:model.live="filterDateFrom" type="date" class="px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] sm:text-sm">
                <input wire:model.live="filterDateTo" type="date" class="px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] sm:text-sm">
                @if($search || $filterPaymentMethod || $filterDateFrom || $filterDateTo)
                <button wire:click="clearFilters" class="px-3 py-2.5 text-slate-500 hover:text-slate-700 hover:bg-slate-100 rounded-xl transition-colors text-sm font-medium">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Total Card --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-red-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path></svg>
                </div>
                <div>
                    <p class="text-sm text-slate-500">Total gastos (filtro actual)</p>
                    <p class="text-xl font-bold text-red-600">${{ number_format($totalFiltered, 2) }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Fecha</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Detalle</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Proveedor / Cliente</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Forma de Pago</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Registrado por</th>
                        @if($needsBranchSelection)
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Sucursal</th>
                        @endif
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-500 uppercase">Valor</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($items as $item)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-4 text-sm text-slate-600">
                            {{ $item->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm font-medium text-slate-900">{{ $item->description }}</span>
                        </td>
                        <td class="px-6 py-4">
                            @if($item->contact_type && $item->contact_id)
                                <div class="flex items-center gap-1.5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $item->contact_type === 'supplier' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                                        {{ $item->contact_type === 'supplier' ? 'Prov.' : 'Cliente' }}
                                    </span>
                                    <span class="text-sm text-slate-700">{{ $item->contact_name }}</span>
                                </div>
                            @else
                                <span class="text-sm text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
                                {{ $item->paymentMethod->name ?? '-' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-600">
                            {{ $item->user->name ?? '-' }}
                        </td>
                        @if($needsBranchSelection)
                        <td class="px-6 py-4 text-sm text-slate-600">
                            {{ $item->branch->name ?? '-' }}
                        </td>
                        @endif
                        <td class="px-6 py-4 text-right">
                            <span class="text-sm font-bold text-red-600">${{ number_format($item->amount, 2) }}</span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-1">
                                @if(auth()->user()->hasPermission('expenses.edit'))
                                <button wire:click="edit({{ $item->id }})" class="p-2 text-slate-400 hover:text-[#ff7261] hover:bg-orange-50 rounded-lg transition-colors" title="Editar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </button>
                                @endif
                                @if(auth()->user()->hasPermission('expenses.delete'))
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
                            <svg class="w-12 h-12 text-slate-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path></svg>
                            <p class="text-slate-500">No hay gastos registrados</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($items->hasPages())
    <div class="mt-6">{{ $items->links() }}</div>
    @endif

    {{-- Create/Edit Modal --}}
    @if($isModalOpen)
    <div class="relative z-[100]" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="$set('isModalOpen', false)"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-xl">
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-slate-900">{{ $itemId ? 'Editar' : 'Registrar' }} Gasto</h3>
                        <button wire:click="$set('isModalOpen', false)" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                    <div class="px-6 py-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Detalle *</label>
                            <input wire:model="description" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="Ej: Pago de arriendo, Compra de insumos...">
                            @error('description')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Valor *</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">$</span>
                                <input wire:model="amount" type="number" step="0.01" min="0.01" class="w-full pl-7 pr-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="0.00">
                            </div>
                            @error('amount')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Forma de Pago *</label>
                            <select wire:model="payment_method_id" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                <option value="">Seleccionar...</option>
                                @foreach($paymentMethods as $pm)
                                <option value="{{ $pm->id }}">{{ $pm->name }}</option>
                                @endforeach
                            </select>
                            @error('payment_method_id')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Proveedor / Cliente</label>
                            <x-searchable-select
                                wire:model="contact_id"
                                :options="$contacts"
                                placeholder="Seleccionar (opcional)..."
                                searchPlaceholder="Buscar proveedor o cliente..."
                            />
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
                        <button wire:click="$set('isModalOpen', false)" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">Cancelar</button>
                        <button wire:click="store" class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea]">
                            {{ $itemId ? 'Actualizar' : 'Guardar' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Delete Modal --}}
    @if($isDeleteModalOpen)
    <div class="relative z-[100]" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="$set('isDeleteModalOpen', false)"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-md bg-white rounded-2xl shadow-xl p-6 text-center">
                    <div class="mx-auto w-12 h-12 rounded-full bg-red-100 flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Eliminar Gasto</h3>
                    <p class="text-slate-500 mb-6">¿Estás seguro de que deseas eliminar este gasto? Esta acción no se puede deshacer.</p>
                    <div class="flex justify-center gap-3">
                        <button wire:click="$set('isDeleteModalOpen', false)" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">Cancelar</button>
                        <button wire:click="delete" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-xl hover:bg-red-700">Eliminar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

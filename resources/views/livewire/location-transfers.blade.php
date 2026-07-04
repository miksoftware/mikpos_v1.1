<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Traslados de Ubicación</h1>
            <p class="text-slate-500 mt-1">Mueve productos entre estantes, pasillos y bodegas</p>
        </div>
        @if(auth()->user()->hasPermission('location_transfers.create'))
        <button wire:click="create"
            class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-[#ff7261] to-[#a855f7] hover:from-[#e55a4a] hover:to-[#9333ea] text-white text-sm font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-[1.02] transition-all duration-200">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Nuevo Traslado
        </button>
        @endif
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div class="relative sm:col-span-2">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <input wire:model.live.debounce.300ms="search" type="text"
                    class="w-full pl-9 pr-3 py-2 border border-slate-200 rounded-xl bg-slate-50 placeholder-slate-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm"
                    placeholder="Buscar por número, ubicación o notas...">
            </div>
            @if($isSuperAdmin)
            <select wire:model.live="filterBranch" class="px-3 py-2 border border-slate-200 rounded-xl bg-slate-50 focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm">
                <option value="">Todas las sucursales</option>
                @foreach($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endforeach
            </select>
            @endif
            <input wire:model.live="filterDateFrom" type="date" class="px-3 py-2 border border-slate-200 rounded-xl bg-slate-50 focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm">
            <input wire:model.live="filterDateTo" type="date" class="px-3 py-2 border border-slate-200 rounded-xl bg-slate-50 focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm">
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Número</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Origen → Destino</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold text-slate-500 uppercase">Productos</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Usuario</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Fecha</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($transfers as $transfer)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-4">
                            <span class="font-mono text-sm font-semibold text-slate-900">{{ $transfer->transfer_number }}</span>
                            @if($transfer->notes)
                            <p class="text-xs text-slate-500 mt-0.5 truncate max-w-[160px]">{{ $transfer->notes }}</p>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2 text-sm">
                                <span class="font-medium text-slate-700">{{ $transfer->fromLocation?->name }}</span>
                                <svg class="w-4 h-4 text-purple-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
                                <span class="font-medium text-slate-700">{{ $transfer->toLocation?->name }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center px-2 py-1 rounded-lg bg-purple-50 text-purple-700 text-sm font-medium">{{ $transfer->items_count }}</span>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-600">{{ $transfer->user?->name ?? '—' }}</td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-slate-600">{{ $transfer->created_at->format('d/m/Y') }}</div>
                            <div class="text-xs text-slate-400">{{ $transfer->created_at->format('H:i') }}</div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <button wire:click="viewTransfer({{ $transfer->id }})" class="p-2 text-slate-400 hover:text-blue-500 hover:bg-blue-50 rounded-lg transition-colors" title="Ver detalle">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                </button>
                                @if(auth()->user()->hasPermission('location_transfers.delete'))
                                <button wire:click="confirmDelete({{ $transfer->id }})" class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Eliminar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                            <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                            No hay traslados de ubicación registrados
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($transfers->hasPages())
        <div class="px-6 py-4 border-t border-slate-200">{{ $transfers->links() }}</div>
        @endif
    </div>

    <!-- Create Modal -->
    @if($isModalOpen)
    <div class="relative z-[100]" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-xl">
                    <div class="px-6 py-4 border-b border-slate-200">
                        <h3 class="text-lg font-bold text-slate-900">Nuevo Traslado de Ubicación</h3>
                        <p class="text-sm text-slate-500 mt-0.5">Mueve productos entre ubicaciones dentro de la misma sucursal</p>
                    </div>
                    <div class="px-6 py-4 space-y-4 max-h-[65vh] overflow-y-auto">
                        @if($isSuperAdmin)
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Sucursal *</label>
                            <select wire:model.live="branch_id" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                <option value="">Seleccionar sucursal...</option>
                                @foreach($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endforeach
                            </select>
                            @error('branch_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        @endif
                        <!-- Location selectors -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Ubicación Origen *</label>
                                <select wire:model.live="from_location_id" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                    <option value="">Seleccionar...</option>
                                    @foreach($formLocations as $loc)<option value="{{ $loc->id }}">{{ $loc->display_name }}</option>@endforeach
                                </select>
                                @error('from_location_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Ubicación Destino *</label>
                                <select wire:model.live="to_location_id" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                    <option value="">Seleccionar...</option>
                                    @foreach($formLocations as $loc)
                                        @if($loc->id != $from_location_id)
                                        <option value="{{ $loc->id }}">{{ $loc->display_name }}</option>
                                        @endif
                                    @endforeach
                                </select>
                                @error('to_location_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        @if($from_location_id && $to_location_id)
                        <div class="flex items-center justify-center gap-2 py-2 px-4 bg-purple-50 rounded-xl">
                            <span class="text-sm font-semibold text-purple-700">{{ $formLocations->find($from_location_id)?->display_name }}</span>
                            <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
                            <span class="text-sm font-semibold text-purple-700">{{ $formLocations->find($to_location_id)?->display_name }}</span>
                        </div>
                        @endif
                        <!-- Product search -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Agregar Producto</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                </div>
                                <input wire:model.live.debounce.300ms="productSearch" type="text"
                                    class="w-full pl-10 pr-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]"
                                    placeholder="Buscar producto por nombre o SKU...">
                                @if($showProductDropdown && $searchProducts->isNotEmpty())
                                <div class="absolute z-10 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-lg max-h-48 overflow-y-auto">
                                    @foreach($searchProducts as $product)
                                    <button wire:click="addProduct({{ $product->id }})" type="button"
                                        class="w-full px-3 py-2 text-left hover:bg-slate-50 flex items-center justify-between border-b border-slate-100 last:border-0">
                                        <div>
                                            <span class="font-medium text-slate-900 text-sm">{{ $product->name }}</span>
                                            <span class="text-xs text-slate-400 ml-2">{{ $product->sku }}</span>
                                        </div>
                                        <span class="text-xs px-2 py-0.5 rounded bg-slate-100 text-slate-600">Stock total: {{ rtrim(rtrim(number_format((float)$product->current_stock,3),'0'),'.') }}</span>
                                    </button>
                                    @endforeach
                                </div>
                                @endif
                            </div>
                        </div>

                        <!-- Items list -->
                        @if(count($items) > 0)
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Productos a trasladar ({{ count($items) }})</label>
                            <div class="border border-slate-200 rounded-xl overflow-hidden">
                                <table class="min-w-full divide-y divide-slate-200">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500">Producto</th>
                                            <th class="px-3 py-2 text-center text-xs font-semibold text-slate-500">En origen</th>
                                            <th class="px-3 py-2 text-center text-xs font-semibold text-slate-500">Cantidad</th>
                                            <th class="px-3 py-2 text-center text-xs font-semibold text-slate-500">Restante</th>
                                            <th class="px-3 py-2"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach($items as $index => $item)
                                        @php
                                            $remaining = $item['location_stock'] - $item['quantity'];
                                            $insufficient = $remaining < 0;
                                        @endphp
                                        <tr class="{{ $insufficient ? 'bg-red-50' : '' }}">
                                            <td class="px-3 py-2">
                                                <div class="font-medium text-slate-900 text-sm">{{ $item['name'] }}</div>
                                                <div class="text-xs text-slate-400">{{ $item['sku'] }}</div>
                                            </td>
                                            <td class="px-3 py-2 text-center text-sm text-slate-600">
                                                {{ rtrim(rtrim(number_format((float)$item['location_stock'],3),'0'),'.') }}
                                            </td>
                                            <td class="px-3 py-2 text-center">
                                                <input type="number" step="0.001" min="0.001"
                                                    wire:change="updateQuantity({{ $index }}, $event.target.value)"
                                                    value="{{ $item['quantity'] }}"
                                                    class="w-20 px-2 py-1 text-center border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                            </td>
                                            <td class="px-3 py-2 text-center">
                                                <span class="font-bold text-sm {{ $insufficient ? 'text-red-600' : 'text-slate-700' }}">
                                                    {{ rtrim(rtrim(number_format($remaining,3),'0'),'.') }}
                                                </span>
                                                @if($insufficient)
                                                <svg class="w-4 h-4 inline text-red-500 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 text-right">
                                                <button wire:click="removeItem({{ $index }})" type="button" class="p-1 text-slate-400 hover:text-red-500">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                                </button>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        @else
                        <div class="text-center py-8 border-2 border-dashed border-slate-200 rounded-xl">
                            <svg class="w-10 h-10 mx-auto text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                            <p class="text-slate-500 text-sm">Busca y agrega productos al traslado</p>
                        </div>
                        @endif
                        <!-- Notes -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Notas <span class="text-slate-400 font-normal">(opcional)</span></label>
                            <textarea wire:model="notes" rows="2" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="Motivo del traslado..."></textarea>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-between items-center">
                        <span class="text-sm text-slate-500">{{ count($items) }} producto(s)</span>
                        <div class="flex gap-3">
                            <button wire:click="$set('isModalOpen', false)" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">Cancelar</button>
                            <button wire:click="store" {{ count($items) === 0 ? 'disabled' : '' }}
                                class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea] disabled:opacity-50">
                                Registrar Traslado
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- View Modal -->
    @if($isViewModalOpen && $viewingTransfer)
    <div class="relative z-[100]" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="$set('isViewModalOpen', false)"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-xl">
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">{{ $viewingTransfer->transfer_number }}</h3>
                            <p class="text-sm text-slate-500">{{ $viewingTransfer->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                        <button wire:click="$set('isViewModalOpen', false)" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                    <div class="px-6 py-4 space-y-4 max-h-[60vh] overflow-y-auto">
                        <div class="flex items-center justify-center gap-3 py-3 px-4 bg-purple-50 rounded-xl">
                            <div class="text-center">
                                <p class="text-xs text-purple-500">Origen</p>
                                <p class="font-semibold text-purple-700">{{ $viewingTransfer->fromLocation?->display_name }}</p>
                            </div>
                            <svg class="w-6 h-6 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
                            <div class="text-center">
                                <p class="text-xs text-purple-500">Destino</p>
                                <p class="font-semibold text-purple-700">{{ $viewingTransfer->toLocation?->display_name }}</p>
                            </div>
                        </div>
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Producto</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 uppercase">Cantidad</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($viewingTransfer->items as $item)
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-slate-900 text-sm">{{ $item->product?->name }}</div>
                                        <div class="text-xs text-slate-400">{{ $item->product?->sku }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-center font-semibold text-purple-600">
                                        {{ rtrim(rtrim(number_format((float)$item->quantity,3),'0'),'.') }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @if($viewingTransfer->notes)
                        <div class="p-3 bg-slate-50 rounded-xl text-sm text-slate-600">
                            <span class="font-medium text-slate-500 text-xs uppercase">Notas:</span>
                            <p class="mt-1">{{ $viewingTransfer->notes }}</p>
                        </div>
                        @endif
                        <p class="text-xs text-slate-400 text-right">Por: {{ $viewingTransfer->user?->name ?? 'Sistema' }}</p>
                    </div>
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end">
                        <button wire:click="$set('isViewModalOpen', false)" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Delete Modal -->
    @if($isDeleteModalOpen)
    <div class="relative z-[100]" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="$set('isDeleteModalOpen', false)"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-md bg-white rounded-2xl shadow-xl p-6 text-center">
                    <div class="mx-auto w-12 h-12 rounded-full bg-red-100 flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Eliminar Traslado</h3>
                    <p class="text-slate-500 mb-2">¿Eliminar este traslado de ubicación?</p>
                    <p class="text-sm text-amber-600 bg-amber-50 rounded-lg p-2 mb-6">Las cantidades serán revertidas a la ubicación de origen</p>
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

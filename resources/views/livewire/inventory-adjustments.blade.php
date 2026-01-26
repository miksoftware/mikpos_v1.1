<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Ajustes de Inventario</h1>
            <p class="text-slate-500 mt-1">Registra entradas y salidas manuales para corregir el inventario</p>
        </div>
        @if(auth()->user()->hasPermission('inventory_adjustments.create'))
        <button wire:click="create" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-[#ff7261] to-[#a855f7] hover:from-[#e55a4a] hover:to-[#9333ea] text-white text-sm font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-[1.02] transition-all duration-200">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Nuevo Ajuste
        </button>
        @endif
    </div>

    <!-- Info Alert -->
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
        <div class="flex gap-3">
            <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div class="text-sm text-blue-800">
                <p class="font-medium">¿Cómo funciona?</p>
                <p class="mt-1">Después de un conteo físico, crea un ajuste y agrega todos los productos que necesitan corrección. Para cada producto puedes elegir <span class="font-semibold text-green-700">+Entrada</span> si encontraste más unidades o <span class="font-semibold text-red-700">-Salida</span> si hay menos. Todo queda en un solo documento.</p>
            </div>
        </div>
    </div>

    @if(!$hasAdjustmentDocument)
    <div class="bg-red-50 border border-red-200 rounded-xl p-4">
        <div class="flex gap-3">
            <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div class="text-sm text-red-800">
                <p class="font-medium">Documento no configurado</p>
                <p class="mt-1">Crea "Ajuste de Inventario" en <a href="{{ route('system-documents') }}" class="underline font-medium">Documentos Sistema</a>.</p>
            </div>
        </div>
    </div>
    @endif

    <!-- Filters -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
        <div class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1 relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <input wire:model.live.debounce.300ms="search" type="text" class="block w-full pl-10 pr-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 placeholder-slate-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] transition-all sm:text-sm" placeholder="Buscar por documento, producto o notas...">
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
                @if($search || $filterBranch)
                <button wire:click="clearFilters" class="px-3 py-2.5 text-slate-500 hover:text-slate-700 hover:bg-slate-100 rounded-xl transition-colors text-sm font-medium">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
                @endif
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Documento</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold text-slate-500 uppercase">Productos</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold text-slate-500 uppercase">Entradas</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold text-slate-500 uppercase">Salidas</th>
                        @if($needsBranchSelection)
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Sucursal</th>
                        @endif
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Usuario</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Fecha</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($documents as $doc)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-4">
                            <span class="font-mono text-sm font-medium text-slate-900">{{ $doc->document_number }}</span>
                            @if($doc->notes)
                            <p class="text-xs text-slate-500 mt-0.5 truncate max-w-[200px]">{{ $doc->notes }}</p>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center px-2 py-1 rounded-lg bg-slate-100 text-slate-700 text-sm font-medium">
                                {{ $doc->items_count }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($doc->total_in > 0)
                            <span class="inline-flex items-center text-green-600 font-semibold">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"></path></svg>
                                +{{ $doc->total_in }}
                            </span>
                            @else
                            <span class="text-slate-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($doc->total_out > 0)
                            <span class="inline-flex items-center text-red-600 font-semibold">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"></path></svg>
                                -{{ $doc->total_out }}
                            </span>
                            @else
                            <span class="text-slate-400">-</span>
                            @endif
                        </td>
                        @if($needsBranchSelection)
                        <td class="px-6 py-4">
                            <span class="text-sm text-slate-600">{{ $doc->branch?->name ?? 'N/A' }}</span>
                        </td>
                        @endif
                        <td class="px-6 py-4">
                            <span class="text-sm text-slate-600">{{ $doc->user?->name ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-slate-600">{{ $doc->created_at->format('d/m/Y') }}</div>
                            <div class="text-xs text-slate-400">{{ $doc->created_at->format('H:i') }}</div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <button wire:click="viewDocument('{{ $doc->document_number }}')" class="p-2 text-slate-400 hover:text-blue-500 hover:bg-blue-50 rounded-lg transition-colors" title="Ver detalle">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                </button>
                                @if(auth()->user()->hasPermission('inventory_adjustments.delete'))
                                <button wire:click="confirmDelete('{{ $doc->document_number }}')" class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Eliminar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $needsBranchSelection ? 8 : 7 }}" class="px-6 py-12 text-center text-slate-500">
                            <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                            No hay ajustes registrados
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($documents->hasPages())
        <div class="px-6 py-4 border-t border-slate-200">{{ $documents->links() }}</div>
        @endif
    </div>

    <!-- Create Modal -->
    @if($isModalOpen)
    <div class="relative z-[100]">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-3xl bg-white rounded-2xl shadow-xl">
                    <div class="px-6 py-4 border-b border-slate-200">
                        <h3 class="text-lg font-bold text-slate-900">Nuevo Ajuste de Inventario</h3>
                        <p class="text-sm text-slate-500 mt-1">Agrega productos y define si cada uno es entrada (+) o salida (-)</p>
                    </div>
                    <div class="px-6 py-4 space-y-4 max-h-[65vh] overflow-y-auto">
                        <!-- Branch Selector for Super Admin -->
                        @if($needsBranchSelection)
                        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-amber-800">Selecciona la sucursal</p>
                                    <p class="text-xs text-amber-600 mt-1">Como administrador general, debes seleccionar la sucursal donde se registrará este ajuste.</p>
                                    <select wire:model="branch_id" class="mt-2 w-full px-3 py-2 border border-amber-300 rounded-lg bg-white focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500 text-sm">
                                        <option value="">Seleccionar sucursal...</option>
                                        @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Product Search -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Agregar Producto</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                </div>
                                <input wire:model.live.debounce.300ms="productSearch" type="text" class="w-full pl-10 pr-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="Buscar producto por nombre o SKU...">
                                @if($showProductDropdown && count($products) > 0)
                                <div class="absolute z-10 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-lg max-h-48 overflow-y-auto">
                                    @foreach($products as $product)
                                    <button wire:click="addProduct({{ $product->id }})" type="button" class="w-full px-3 py-2 text-left hover:bg-slate-50 flex items-center justify-between border-b border-slate-100 last:border-0">
                                        <div>
                                            <span class="font-medium text-slate-900">{{ $product->name }}</span>
                                            <span class="text-sm text-slate-500 ml-2">{{ $product->sku }}</span>
                                        </div>
                                        <span class="text-xs px-2 py-1 rounded bg-slate-100 text-slate-600">Stock: {{ $product->current_stock ?? 0 }}</span>
                                    </button>
                                    @endforeach
                                </div>
                                @elseif($showProductDropdown && strlen($productSearch) >= 2)
                                <div class="absolute z-10 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-lg p-3 text-center text-slate-500 text-sm">
                                    No se encontraron productos
                                </div>
                                @endif
                            </div>
                        </div>

                        <!-- Items List -->
                        @if(count($items) > 0)
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Productos a ajustar ({{ count($items) }})</label>
                            <div class="border border-slate-200 rounded-xl overflow-hidden">
                                <table class="min-w-full divide-y divide-slate-200">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500">Producto</th>
                                            <th class="px-3 py-2 text-center text-xs font-semibold text-slate-500">Stock</th>
                                            <th class="px-3 py-2 text-center text-xs font-semibold text-slate-500">Tipo</th>
                                            <th class="px-3 py-2 text-center text-xs font-semibold text-slate-500">Cant.</th>
                                            <th class="px-3 py-2 text-center text-xs font-semibold text-slate-500">Nuevo</th>
                                            <th class="px-3 py-2"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach($items as $index => $item)
                                        @php
                                            $newStock = $item['type'] === 'in' 
                                                ? $item['current_stock'] + $item['quantity'] 
                                                : $item['current_stock'] - $item['quantity'];
                                            $isNegative = $newStock < 0;
                                        @endphp
                                        <tr class="{{ $isNegative ? 'bg-red-50' : '' }}">
                                            <td class="px-3 py-2">
                                                <div class="font-medium text-slate-900 text-sm">{{ $item['name'] }}</div>
                                                <div class="text-xs text-slate-500">{{ $item['sku'] }}</div>
                                            </td>
                                            <td class="px-3 py-2 text-center text-sm text-slate-600">{{ $item['current_stock'] }}</td>
                                            <td class="px-3 py-2 text-center">
                                                <div class="inline-flex rounded-lg border border-slate-200 overflow-hidden">
                                                    <button wire:click="updateItemType({{ $index }}, 'in')" type="button" class="px-2 py-1 text-xs font-medium transition-colors {{ $item['type'] === 'in' ? 'bg-green-500 text-white' : 'bg-white text-slate-600 hover:bg-green-50' }}">
                                                        +Entrada
                                                    </button>
                                                    <button wire:click="updateItemType({{ $index }}, 'out')" type="button" class="px-2 py-1 text-xs font-medium transition-colors {{ $item['type'] === 'out' ? 'bg-red-500 text-white' : 'bg-white text-slate-600 hover:bg-red-50' }}">
                                                        -Salida
                                                    </button>
                                                </div>
                                            </td>
                                            <td class="px-3 py-2 text-center">
                                                <input type="number" wire:change="updateQuantity({{ $index }}, $event.target.value)" value="{{ $item['quantity'] }}" min="1" class="w-16 px-2 py-1 text-center border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                            </td>
                                            <td class="px-3 py-2 text-center">
                                                <span class="font-bold text-sm {{ $item['type'] === 'in' ? 'text-green-600' : ($isNegative ? 'text-red-600' : 'text-orange-600') }}">
                                                    {{ $newStock }}
                                                </span>
                                                @if($isNegative)
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
                            @php
                                $totalIn = collect($items)->where('type', 'in')->sum('quantity');
                                $totalOut = collect($items)->where('type', 'out')->sum('quantity');
                            @endphp
                            <div class="flex justify-end gap-4 mt-2 text-sm">
                                <span class="text-green-600 font-medium">Total entradas: +{{ $totalIn }}</span>
                                <span class="text-red-600 font-medium">Total salidas: -{{ $totalOut }}</span>
                            </div>
                        </div>
                        @else
                        <div class="text-center py-8 border-2 border-dashed border-slate-200 rounded-xl">
                            <svg class="w-10 h-10 mx-auto text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                            <p class="text-slate-500 text-sm">Busca y agrega productos al ajuste</p>
                        </div>
                        @endif

                        <!-- Notes -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Motivo / Notas</label>
                            <textarea wire:model="notes" rows="2" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="Ej: Ajuste por conteo físico del 24/01/2026"></textarea>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-between items-center">
                        <span class="text-sm text-slate-500">{{ count($items) }} producto(s)</span>
                        <div class="flex gap-3">
                            <button wire:click="$set('isModalOpen', false)" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">Cancelar</button>
                            <button wire:click="store" class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea] disabled:opacity-50" {{ count($items) === 0 ? 'disabled' : '' }}>
                                Registrar Ajuste
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- View Document Modal -->
    @if($isViewModalOpen && $viewingDocument)
    <div class="relative z-[100]">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="$set('isViewModalOpen', false)"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-xl">
                    <div class="px-6 py-4 border-b border-slate-200">
                        <h3 class="text-lg font-bold text-slate-900">Detalle del Ajuste</h3>
                        <p class="text-sm text-slate-500">{{ $viewingDocument->first()?->document_number }}</p>
                    </div>
                    <div class="px-6 py-4 max-h-[60vh] overflow-y-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Producto</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 uppercase">Tipo</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 uppercase">Cantidad</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 uppercase">Antes → Después</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($viewingDocument as $movement)
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-slate-900">{{ $movement->product?->name ?? 'N/A' }}</div>
                                        <div class="text-xs text-slate-500">{{ $movement->product?->sku }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if($movement->movement_type === 'in')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-green-100 text-green-700">+Entrada</span>
                                        @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-red-100 text-red-700">-Salida</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="font-semibold {{ $movement->movement_type === 'in' ? 'text-green-600' : 'text-red-600' }}">
                                            {{ $movement->movement_type === 'in' ? '+' : '-' }}{{ $movement->quantity }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center text-sm">
                                        <span class="text-slate-500">{{ $movement->stock_before }}</span>
                                        <svg class="w-3 h-3 inline mx-1 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
                                        <span class="font-medium text-slate-900">{{ $movement->stock_after }}</span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @if($viewingDocument->first()?->notes)
                        <div class="mt-4 p-3 bg-slate-50 rounded-xl">
                            <p class="text-xs font-medium text-slate-500 mb-1">Notas:</p>
                            <p class="text-sm text-slate-700">{{ $viewingDocument->first()->notes }}</p>
                        </div>
                        @endif
                        <div class="mt-4 flex items-center justify-between text-xs text-slate-500">
                            <span>Usuario: {{ $viewingDocument->first()?->user?->name }}</span>
                            <span>{{ $viewingDocument->first()?->created_at->format('d/m/Y H:i') }}</span>
                        </div>
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
    <div class="relative z-[100]">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="$set('isDeleteModalOpen', false)"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-md bg-white rounded-2xl shadow-xl p-6 text-center">
                    <div class="mx-auto w-12 h-12 rounded-full bg-red-100 flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Eliminar Ajuste</h3>
                    <p class="text-slate-500 mb-2">¿Eliminar <span class="font-mono font-medium">{{ $documentToDelete }}</span>?</p>
                    <p class="text-sm text-amber-600 bg-amber-50 rounded-lg p-2 mb-4">El stock de todos los productos será revertido</p>
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

<div class="space-y-6">
    <x-toast />

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Descuentos</h1>
            <p class="text-slate-500 mt-1">Gestión de descuentos masivos por categoría, marca o producto</p>
        </div>
        @if(auth()->user()->hasPermission('discounts.create'))
        <button wire:click="create" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-[#ff7261] to-[#a855f7] hover:from-[#e55a4a] hover:to-[#9333ea] text-white text-sm font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-[1.02] transition-all duration-200">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Nuevo Descuento
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
                <input wire:model.live.debounce.300ms="search" type="text" class="block w-full pl-10 pr-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 placeholder-slate-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] transition-all sm:text-sm" placeholder="Buscar descuento...">
            </div>
            <div class="flex flex-col sm:flex-row gap-3">
                <select wire:model.live="filterScope" class="px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] sm:text-sm min-w-[150px]">
                    <option value="">Todos los alcances</option>
                    <option value="all">Todos los productos</option>
                    <option value="category">Categoría</option>
                    <option value="subcategory">Subcategoría</option>
                    <option value="brand">Marca</option>
                    <option value="products">Productos específicos</option>
                </select>
                <select wire:model.live="filterStatus" class="px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] sm:text-sm min-w-[140px]">
                    <option value="">Todos los estados</option>
                    <option value="active">Activo</option>
                    <option value="scheduled">Programado</option>
                    <option value="expired">Expirado</option>
                    <option value="inactive">Inactivo</option>
                </select>
                @if($search || $filterScope || $filterStatus)
                <button wire:click="clearFilters" class="px-3 py-2.5 text-slate-500 hover:text-slate-700 hover:bg-slate-100 rounded-xl transition-colors text-sm font-medium">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Nombre</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Alcance</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Descuento</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Vigencia</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold text-slate-500 uppercase">Estado</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold text-slate-500 uppercase">Activo</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($items as $item)
                    @php $statusLabel = $item->status_label; @endphp
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-4">
                            <p class="text-sm font-medium text-slate-900">{{ $item->name }}</p>
                            @if($item->description)
                            <p class="text-xs text-slate-400 truncate max-w-[200px]">{{ $item->description }}</p>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $item->scope === 'all' ? 'bg-purple-100 text-purple-700' : '' }}
                                {{ $item->scope === 'category' ? 'bg-blue-100 text-blue-700' : '' }}
                                {{ $item->scope === 'subcategory' ? 'bg-indigo-100 text-indigo-700' : '' }}
                                {{ $item->scope === 'brand' ? 'bg-amber-100 text-amber-700' : '' }}
                                {{ $item->scope === 'product' || $item->scope === 'products' ? 'bg-emerald-100 text-emerald-700' : '' }}
                            ">
                                {{ $item->scope_label }}
                            </span>
                            @if($item->scope_name)
                            <p class="text-xs text-slate-500 mt-0.5">{{ $item->scope_name }}</p>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm font-bold text-red-600">
                                @if($item->discount_type === 'percentage')
                                    {{ rtrim(rtrim(number_format($item->discount_value, 2), '0'), '.') }}%
                                @else
                                    ${{ number_format($item->discount_value, 0, ',', '.') }}
                                @endif
                            </span>
                            <p class="text-xs text-slate-400">{{ $item->discount_type === 'percentage' ? 'Porcentaje' : 'Valor fijo' }}</p>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-sm text-slate-700">{{ $item->start_date->format('d/m/Y') }}</p>
                            <p class="text-xs text-slate-400">al {{ $item->end_date->format('d/m/Y') }}</p>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $statusLabel === 'Activo' ? 'bg-emerald-100 text-emerald-700' : '' }}
                                {{ $statusLabel === 'Programado' ? 'bg-blue-100 text-blue-700' : '' }}
                                {{ $statusLabel === 'Expirado' ? 'bg-slate-100 text-slate-500' : '' }}
                                {{ $statusLabel === 'Inactivo' ? 'bg-red-100 text-red-700' : '' }}
                            ">
                                {{ $statusLabel }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if(auth()->user()->hasPermission('discounts.edit'))
                            <button wire:click="toggleStatus({{ $item->id }})" class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors {{ $item->is_active ? 'bg-gradient-to-r from-[#ff7261] to-[#a855f7]' : 'bg-slate-300' }}">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform shadow-sm {{ $item->is_active ? 'translate-x-6' : 'translate-x-1' }}"></span>
                            </button>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <button wire:click="showDetail({{ $item->id }})" class="p-2 text-slate-400 hover:text-blue-500 hover:bg-blue-50 rounded-lg transition-colors" title="Ver detalle">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                </button>
                                @if(auth()->user()->hasPermission('discounts.edit'))
                                <button wire:click="edit({{ $item->id }})" class="p-2 text-slate-400 hover:text-[#ff7261] hover:bg-orange-50 rounded-lg transition-colors" title="Editar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </button>
                                @endif
                                @if(auth()->user()->hasPermission('discounts.delete'))
                                <button wire:click="confirmDelete({{ $item->id }})" class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Eliminar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <svg class="w-12 h-12 text-slate-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                            <p class="text-slate-500">No hay descuentos registrados</p>
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
                        <h3 class="text-lg font-bold text-slate-900">{{ $itemId ? 'Editar' : 'Crear' }} Descuento</h3>
                        <button wire:click="$set('isModalOpen', false)" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                    <div class="px-6 py-4 space-y-4 max-h-[70vh] overflow-y-auto">
                        {{-- Name --}}
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Nombre del descuento *</label>
                            <input wire:model="name" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="Ej: Descuento de temporada, Black Friday...">
                            @error('name')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                        </div>

                        {{-- Description --}}
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Descripción</label>
                            <input wire:model="description" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="Descripción opcional...">
                        </div>

                        {{-- Scope --}}
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Aplicar a *</label>
                            <select wire:model.live="scope" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                <option value="all">Todos los productos</option>
                                <option value="category">Categoría</option>
                                <option value="subcategory">Subcategoría</option>
                                <option value="brand">Marca</option>
                                <option value="products">Productos específicos</option>
                            </select>
                            @error('scope')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                        </div>

                        {{-- Scope ID selector --}}
                        @if($scope === 'category')
                        <div wire:key="scope-category">
                            <label class="block text-sm font-medium text-slate-700 mb-1">Categoría *</label>
                            <x-searchable-select
                                wire:model="scope_id"
                                :options="$categories->map(fn($c) => ['id' => $c->id, 'name' => $c->name])->toArray()"
                                placeholder="Seleccionar categoría..."
                                searchPlaceholder="Buscar categoría..."
                            />
                        </div>
                        @elseif($scope === 'subcategory')
                        <div wire:key="scope-subcategory">
                            <label class="block text-sm font-medium text-slate-700 mb-1">Subcategoría *</label>
                            <x-searchable-select
                                wire:model="scope_id"
                                :options="$subcategories->map(fn($s) => ['id' => $s->id, 'name' => $s->name])->toArray()"
                                placeholder="Seleccionar subcategoría..."
                                searchPlaceholder="Buscar subcategoría..."
                            />
                        </div>
                        @elseif($scope === 'brand')
                        <div wire:key="scope-brand">
                            <label class="block text-sm font-medium text-slate-700 mb-1">Marca *</label>
                            <x-searchable-select
                                wire:model="scope_id"
                                :options="$brands->map(fn($b) => ['id' => $b->id, 'name' => $b->name])->toArray()"
                                placeholder="Seleccionar marca..."
                                searchPlaceholder="Buscar marca..."
                            />
                        </div>
                        @elseif($scope === 'products')
                        <div wire:key="scope-products">
                            <label class="block text-sm font-medium text-slate-700 mb-1">Productos *</label>
                            {{-- Search input --}}
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                </div>
                                <input wire:model.live.debounce.300ms="productSearch" type="text" class="w-full pl-9 pr-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm" placeholder="Buscar producto por nombre o SKU...">
                            </div>
                            {{-- Search results --}}
                            @if(trim($productSearch) && $products->count() > 0)
                            <div class="mt-1 border border-slate-200 rounded-xl max-h-40 overflow-y-auto bg-white shadow-sm">
                                @foreach($products as $prod)
                                <button type="button" wire:click="addProduct({{ $prod->id }})" class="w-full text-left px-3 py-2 text-sm hover:bg-slate-50 flex items-center justify-between transition-colors">
                                    <div>
                                        <span class="text-slate-800">{{ $prod->name }}</span>
                                        <span class="text-slate-400 text-xs ml-1">({{ $prod->sku }})</span>
                                    </div>
                                    <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                </button>
                                @endforeach
                            </div>
                            @elseif(trim($productSearch) && $products->count() === 0)
                            <p class="mt-1 text-xs text-slate-400 px-1">No se encontraron productos</p>
                            @endif
                            {{-- Selected products chips --}}
                            @if(count($selectedProductIds) > 0)
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach($selectedProductNames as $prodId => $prodName)
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-purple-50 text-purple-700 text-xs font-medium border border-purple-200">
                                    {{ $prodName }}
                                    <button type="button" wire:click="removeProduct({{ $prodId }})" class="text-purple-400 hover:text-purple-700">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </span>
                                @endforeach
                            </div>
                            <p class="text-xs text-slate-400 mt-1">{{ count($selectedProductIds) }} producto(s) seleccionado(s)</p>
                            @endif
                        </div>
                        @endif

                        {{-- Discount type and value --}}
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Tipo *</label>
                                <select wire:model.live="discount_type" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                    <option value="percentage">Porcentaje (%)</option>
                                    <option value="fixed">Valor fijo ($)</option>
                                </select>
                                @error('discount_type')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Valor *</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">{{ $discount_type === 'percentage' ? '%' : '$' }}</span>
                                    <input wire:model="discount_value" type="number" step="0.01" min="0.01" {{ $discount_type === 'percentage' ? 'max=100' : '' }} class="w-full pl-7 pr-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="0.00">
                                </div>
                                @error('discount_value')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                            </div>
                        </div>

                        {{-- Date range --}}
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Fecha inicio *</label>
                                <input wire:model="start_date" type="date" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                @error('start_date')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Fecha fin *</label>
                                <input wire:model="end_date" type="date" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                @error('end_date')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                            </div>
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

    {{-- Detail Modal --}}
    @if($isDetailModalOpen && $detailDiscount)
    <div class="relative z-[100]" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="$set('isDetailModalOpen', false)"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-xl">
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-slate-900">Detalle del Descuento</h3>
                        <button wire:click="$set('isDetailModalOpen', false)" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                    <div class="px-6 py-4 space-y-4 max-h-[70vh] overflow-y-auto">
                        {{-- Info grid --}}
                        <div class="grid grid-cols-2 gap-4">
                            <div class="p-3 rounded-lg bg-slate-50">
                                <span class="text-xs text-slate-500">Nombre</span>
                                <p class="font-medium text-slate-800">{{ $detailDiscount->name }}</p>
                            </div>
                            <div class="p-3 rounded-lg bg-slate-50">
                                <span class="text-xs text-slate-500">Estado</span>
                                <p class="font-medium">
                                    @php $sl = $detailDiscount->status_label; @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                        {{ $sl === 'Activo' ? 'bg-emerald-100 text-emerald-700' : '' }}
                                        {{ $sl === 'Programado' ? 'bg-blue-100 text-blue-700' : '' }}
                                        {{ $sl === 'Expirado' ? 'bg-slate-100 text-slate-500' : '' }}
                                        {{ $sl === 'Inactivo' ? 'bg-red-100 text-red-700' : '' }}
                                    ">{{ $sl }}</span>
                                </p>
                            </div>
                            <div class="p-3 rounded-lg bg-slate-50">
                                <span class="text-xs text-slate-500">Alcance</span>
                                <p class="font-medium text-slate-800">{{ $detailDiscount->scope_label }}</p>
                                @if($detailDiscount->scope_name)
                                <p class="text-sm text-slate-500">{{ $detailDiscount->scope_name }}</p>
                                @endif
                            </div>
                            <div class="p-3 rounded-lg bg-red-50">
                                <span class="text-xs text-red-500">Descuento</span>
                                <p class="font-bold text-red-600 text-lg">
                                    @if($detailDiscount->discount_type === 'percentage')
                                        {{ rtrim(rtrim(number_format($detailDiscount->discount_value, 2), '0'), '.') }}%
                                    @else
                                        ${{ number_format($detailDiscount->discount_value, 0, ',', '.') }}
                                    @endif
                                </p>
                            </div>
                            <div class="p-3 rounded-lg bg-slate-50">
                                <span class="text-xs text-slate-500">Vigencia</span>
                                <p class="font-medium text-slate-800">{{ $detailDiscount->start_date->format('d/m/Y') }} - {{ $detailDiscount->end_date->format('d/m/Y') }}</p>
                            </div>
                            <div class="p-3 rounded-lg bg-slate-50">
                                <span class="text-xs text-slate-500">Creado por</span>
                                <p class="font-medium text-slate-800">{{ $detailDiscount->creator?->name ?? '-' }}</p>
                            </div>
                        </div>

                        @if($detailDiscount->description)
                        <div class="p-3 rounded-lg bg-slate-50">
                            <span class="text-xs text-slate-500">Descripción</span>
                            <p class="text-sm text-slate-700">{{ $detailDiscount->description }}</p>
                        </div>
                        @endif

                        {{-- Affected products preview --}}
                        @if(count($affectedProducts) > 0)
                        <div>
                            <h4 class="text-sm font-semibold text-slate-700 mb-2">Productos afectados (muestra)</h4>
                            <div class="border border-slate-200 rounded-xl overflow-hidden">
                                <table class="min-w-full divide-y divide-slate-200">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500">Producto</th>
                                            <th class="px-4 py-2 text-right text-xs font-semibold text-slate-500">Precio</th>
                                            <th class="px-4 py-2 text-right text-xs font-semibold text-slate-500">Con descuento</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach($affectedProducts as $ap)
                                        <tr>
                                            <td class="px-4 py-2">
                                                <p class="text-sm text-slate-800">{{ $ap['name'] }}</p>
                                                <p class="text-xs text-slate-400">{{ $ap['sku'] }}</p>
                                            </td>
                                            <td class="px-4 py-2 text-right text-sm text-slate-600">${{ number_format($ap['sale_price'], 0, ',', '.') }}</td>
                                            <td class="px-4 py-2 text-right text-sm font-bold text-red-600">${{ number_format($ap['discounted_price'], 0, ',', '.') }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        @endif
                    </div>
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end">
                        <button wire:click="$set('isDetailModalOpen', false)" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">Cerrar</button>
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
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Eliminar Descuento</h3>
                    <p class="text-slate-500 mb-6">¿Estás seguro de que deseas eliminar este descuento? Esta acción no se puede deshacer.</p>
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

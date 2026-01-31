<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Productos</h1>
            <p class="text-slate-500 mt-1">Gestiona los productos y sus variantes</p>
        </div>
        <div class="flex items-center gap-2">
            @if(auth()->user()->hasPermission('products.create'))
            <button wire:click="openImportModal" class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-semibold rounded-xl shadow-sm hover:shadow transition-all duration-200">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                Importar CSV
            </button>
            <button wire:click="create" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-[#ff7261] to-[#a855f7] hover:from-[#e55a4a] hover:to-[#9333ea] text-white text-sm font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-[1.02] transition-all duration-200">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Nuevo Producto
            </button>
            @endif
        </div>
    </div>

    {{-- Search and Filters --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
        <div class="flex flex-col lg:flex-row gap-4">
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <input wire:model.live.debounce.300ms="search" type="text" class="block w-full pl-10 pr-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 placeholder-slate-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] transition-all sm:text-sm" placeholder="Buscar por nombre, SKU o descripción...">
            </div>
            <div class="flex flex-col sm:flex-row gap-3">
                <select wire:model.live="filterCategory" class="px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] sm:text-sm min-w-[160px]">
                    <option value="">Todas las categorías</option>
                    @foreach($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
                <select wire:model.live="filterBrand" class="px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] sm:text-sm min-w-[140px]">
                    <option value="">Todas las marcas</option>
                    @foreach($brands as $brand)
                    <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                    @endforeach
                </select>
                <select wire:model.live="filterStatus" class="px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] sm:text-sm min-w-[120px]">
                    <option value="">Todos</option>
                    <option value="1">Activos</option>
                    <option value="0">Inactivos</option>
                </select>
                @if($needsBranchSelection)
                <select wire:model.live="filterBranch" class="px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] sm:text-sm min-w-[160px]">
                    <option value="">Todas las sucursales</option>
                    @foreach($branches as $branch)
                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
                @endif
                @if($search || $filterCategory || $filterBrand || $filterStatus !== null && $filterStatus !== '' || $filterBranch)
                <button wire:click="clearFilters" class="px-3 py-2.5 text-slate-500 hover:text-slate-700 hover:bg-slate-100 rounded-xl transition-colors text-sm font-medium">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Products Table --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase w-10"></th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Producto</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Categoría</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-500 uppercase">Precio</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold text-slate-500 uppercase">Stock</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold text-slate-500 uppercase">Variantes</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold text-slate-500 uppercase">Estado</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($items as $item)
                    <tr class="hover:bg-slate-50/50 transition-colors {{ in_array($item->id, $expandedProducts) ? 'bg-slate-50' : '' }}">
                        <td class="px-6 py-4">
                            @if($item->children_count > 0)
                            <button wire:click="toggleExpand({{ $item->id }})" class="p-1 text-slate-400 hover:text-slate-600 rounded transition-colors">
                                <svg class="w-5 h-5 transform transition-transform {{ in_array($item->id, $expandedProducts) ? 'rotate-90' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </button>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                @if($item->image)
                                <img src="{{ Storage::url($item->image) }}" alt="{{ $item->name }}" class="w-10 h-10 rounded-lg object-cover">
                                @else
                                <div class="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                </div>
                                @endif
                                <div>
                                    <div class="font-medium text-slate-900">{{ $item->name }}</div>
                                    <div class="text-sm text-slate-500">SKU: {{ $item->sku ?? 'Sin SKU' }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            @if($item->category)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium bg-purple-100 text-purple-700">{{ $item->category->name }}</span>
                            @if($item->subcategory)
                            <div class="text-xs text-slate-500 mt-1">{{ $item->subcategory->name }}</div>
                            @endif
                            @else
                            <span class="text-slate-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="text-sm">
                                <span class="text-slate-900 font-medium">${{ number_format($item->sale_price, 2) }}</span>
                                @if($item->hasNegativeMargin())
                                <span class="ml-1 text-red-500" title="Margen negativo">⚠️</span>
                                @else
                                @php $margin = $item->getMargin(); @endphp
                                @if($margin !== null)
                                <span class="ml-1 text-xs {{ $margin >= 20 ? 'text-green-600' : ($margin >= 10 ? 'text-amber-600' : 'text-red-500') }}">
                                    ({{ number_format($margin, 1) }}%)
                                </span>
                                @endif
                                @endif
                            </div>
                            @if($item->brand)
                            <div class="text-xs text-slate-500">{{ $item->brand->name }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="text-sm {{ $item->isLowStock() ? 'text-amber-600 font-medium' : 'text-slate-600' }}">
                                {{ $item->current_stock }} {{ $item->unit?->abbreviation ?? 'und' }}
                            </span>
                            @if($item->isLowStock())
                            <div class="text-xs text-amber-500">Stock bajo</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($item->children_count > 0)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium bg-blue-100 text-blue-700">
                                {{ $item->children_count }} {{ $item->children_count === 1 ? 'variante' : 'variantes' }}
                            </span>
                            @else
                            <span class="text-slate-400 text-sm">Sin variantes</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if(auth()->user()->hasPermission('products.edit'))
                            <button wire:click="toggleStatus({{ $item->id }})" class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 {{ $item->is_active ? 'bg-[#ff7261]' : 'bg-slate-200' }}">
                                <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200 {{ $item->is_active ? 'translate-x-5' : 'translate-x-0' }}"></span>
                            </button>
                            @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-sm font-medium {{ $item->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                {{ $item->is_active ? 'Activo' : 'Inactivo' }}
                            </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-1">
                                @if(auth()->user()->hasPermission('products.create'))
                                <button wire:click="createChild({{ $item->id }})" class="p-2 text-slate-400 hover:text-blue-500 hover:bg-blue-50 rounded-lg transition-colors" title="Agregar variante">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                                </button>
                                @endif
                                @if(auth()->user()->hasPermission('products.edit'))
                                <button wire:click="edit({{ $item->id }})" class="p-2 text-slate-400 hover:text-[#ff7261] hover:bg-orange-50 rounded-lg transition-colors" title="Editar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </button>
                                @endif
                                @if(auth()->user()->hasPermission('products.delete'))
                                <button wire:click="confirmDelete({{ $item->id }})" class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Eliminar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @if(in_array($item->id, $expandedProducts) && $item->children_count > 0)
                    @foreach($item->children as $child)
                    <tr class="bg-slate-50/70 hover:bg-slate-100/50 transition-colors">
                        <td class="px-6 py-3"></td>
                        <td class="px-6 py-3">
                            <div class="flex items-center gap-3 pl-6">
                                <div class="w-1 h-8 bg-slate-300 rounded-full"></div>
                                @if($child->image || $item->image)
                                <img src="{{ Storage::url($child->getDisplayImage()) }}" alt="{{ $child->name }}" class="w-8 h-8 rounded-lg object-cover">
                                @else
                                <div class="w-8 h-8 rounded-lg bg-slate-200 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                </div>
                                @endif
                                <div>
                                    <div class="font-medium text-slate-700 text-sm">{{ $child->name }}</div>
                                    <div class="text-xs text-slate-500">
                                        @if($child->sku)SKU: {{ $child->sku }}@endif
                                        @if($child->barcode) · {{ $child->barcode }}@endif
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-3">
                            @if($child->presentation)
                            <span class="text-sm text-slate-600">{{ $child->presentation->name }}</span>
                            @endif
                            @if($child->color)
                            <div class="flex items-center gap-1 mt-1">
                                @if($child->color->hex_code)
                                <span class="w-3 h-3 rounded-full border border-slate-300" style="background-color: {{ $child->color->hex_code }}"></span>
                                @endif
                                <span class="text-xs text-slate-500">{{ $child->color->name }}</span>
                            </div>
                            @endif
                        </td>
                        <td class="px-6 py-3">
                            <div class="text-sm">
                                <span class="text-slate-600">${{ number_format($child->sale_price, 2) }}</span>
                                @if($child->hasNegativeMargin())
                                <span class="ml-1 text-red-500" title="Margen negativo">⚠️</span>
                                @else
                                @php $margin = $child->getMargin(); @endphp
                                @if($margin !== null)
                                <span class="ml-1 text-xs {{ $margin >= 20 ? 'text-green-600' : ($margin >= 10 ? 'text-amber-600' : 'text-red-500') }}">
                                    ({{ number_format($margin, 1) }}%)
                                </span>
                                @endif
                                @endif
                            </div>
                            @if($child->isLowStock())
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-700">Stock bajo</span>
                            @endif
                        </td>
                        <td class="px-6 py-3 text-center">
                            <span class="text-sm text-slate-500" title="Stock del producto padre">
                                {{ $item->current_stock }} {{ $item->unit?->abbreviation ?? 'und' }}
                            </span>
                            <div class="text-xs text-slate-400">(padre)</div>
                        </td>
                        <td class="px-6 py-3 text-center">
                            @if(auth()->user()->hasPermission('products.edit'))
                            <button wire:click="toggleChildStatus({{ $child->id }})" class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 {{ $child->is_active ? 'bg-[#ff7261]' : 'bg-slate-200' }}">
                                <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow transition duration-200 {{ $child->is_active ? 'translate-x-4' : 'translate-x-0' }}"></span>
                            </button>
                            @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $child->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                {{ $child->is_active ? 'Activo' : 'Inactivo' }}
                            </span>
                            @endif
                        </td>
                        <td class="px-6 py-3 text-right">
                            <div class="flex items-center justify-end gap-1">
                                @if(auth()->user()->hasPermission('products.edit'))
                                <button wire:click="editChild({{ $child->id }})" class="p-1.5 text-slate-400 hover:text-[#ff7261] hover:bg-orange-50 rounded-lg transition-colors" title="Editar variante">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </button>
                                @endif
                                @if(auth()->user()->hasPermission('products.delete'))
                                <button wire:click="confirmDeleteChild({{ $child->id }})" class="p-1.5 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Eliminar variante">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                    @endif
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-slate-500">
                            <div class="flex flex-col items-center">
                                <svg class="w-12 h-12 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                                <p>No hay productos registrados</p>
                                @if($search || $filterCategory || $filterBrand || ($filterStatus !== null && $filterStatus !== ''))
                                <button wire:click="clearFilters" class="mt-2 text-[#ff7261] hover:underline text-sm">Limpiar filtros</button>
                                @endif
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

    {{-- Create/Edit Parent Product Modal --}}
    @if($isModalOpen)
    <div class="relative z-[100]" x-data="{
        purchasePrice: @entangle('purchase_price'),
        salePrice: @entangle('sale_price'),
        get margin() {
            if (!this.purchasePrice || this.purchasePrice <= 0) return null;
            return ((this.salePrice - this.purchasePrice) / this.purchasePrice * 100).toFixed(1);
        },
        get hasNegativeMargin() {
            return this.salePrice < this.purchasePrice;
        }
    }">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="$set('isModalOpen', false)"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-xl">
                    <div class="px-6 py-4 border-b border-slate-200">
                        <h3 class="text-lg font-bold text-slate-900">{{ $itemId ? 'Editar' : 'Nuevo' }} Producto</h3>
                    </div>
                    <div class="px-6 py-4 space-y-6 max-h-[70vh] overflow-y-auto">
                        {{-- Branch Selection (for super_admin or users without branch) --}}
                        @if($needsBranchSelection)
                        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-amber-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                <div class="flex-1">
                                    <h4 class="text-sm font-semibold text-amber-800 mb-1">Selección de Sucursal Requerida</h4>
                                    <p class="text-sm text-amber-700 mb-3">Como administrador general, debes seleccionar la sucursal a la que pertenecerá este producto.</p>
                                    <select wire:model="branch_id" class="w-full px-3 py-2 border border-amber-300 rounded-xl bg-white focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500">
                                        <option value="">Seleccionar sucursal...</option>
                                        @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('branch_id') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                        @endif

                        <div>
                            <h4 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                                Información Básica
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-slate-700 mb-1">SKU <span class="text-slate-400 font-normal">(se genera automáticamente)</span></label>
                                    <input wire:model="sku" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="Ej: MED-00001">
                                    @error('sku')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Nombre *</label>
                                    <input wire:model="name" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="Ej: Acetaminofén">
                                    @error('name')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                                </div>
                            </div>
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-slate-700 mb-1">Descripción</label>
                                <textarea wire:model="description" rows="2" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="Descripción del producto"></textarea>
                            </div>
                        </div>
                        <div>
                            <h4 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                                Clasificación
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Categoría *</label>
                                    <select wire:model.live="category_id" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                        <option value="">Seleccionar categoría...</option>
                                        @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('category_id')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Subcategoría</label>
                                    <select wire:model="subcategory_id" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" {{ empty($subcategories) ? 'disabled' : '' }}>
                                        <option value="">Seleccionar subcategoría...</option>
                                        @foreach($subcategories as $subcat)
                                        <option value="{{ $subcat->id }}">{{ $subcat->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Marca</label>
                                    <select wire:model="brand_id" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                        <option value="">Seleccionar marca...</option>
                                        @foreach($brands as $brand)
                                        <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Unidad Base *</label>
                                    <select wire:model="unit_id" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                        <option value="">Seleccionar unidad...</option>
                                        @foreach($units as $unit)
                                        <option value="{{ $unit->id }}">{{ $unit->name }} ({{ $unit->abbreviation }})</option>
                                        @endforeach
                                    </select>
                                    @error('unit_id')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                                </div>
                            </div>
                        </div>
                        {{-- Configurable Fields Section (Parent) --}}
                        @php
                            $hasVisibleFields = false;
                            foreach (['barcode', 'presentation_id', 'color_id', 'product_model_id', 'size', 'weight', 'imei'] as $fn) {
                                $f = $fieldSettings[$fn] ?? null;
                                if ($f && (is_object($f) ? $f->parent_visible : ($f['parent_visible'] ?? false))) {
                                    $hasVisibleFields = true;
                                    break;
                                }
                            }
                        @endphp
                        @if($hasVisibleFields)
                        <div>
                            <h4 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
                                Atributos Adicionales
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                @php
                                    $barcodeField = $fieldSettings['barcode'] ?? null;
                                    $barcodeVisible = $barcodeField ? (is_object($barcodeField) ? $barcodeField->parent_visible : ($barcodeField['parent_visible'] ?? false)) : false;
                                    $barcodeRequired = $barcodeField ? (is_object($barcodeField) ? $barcodeField->parent_required : ($barcodeField['parent_required'] ?? false)) : false;
                                @endphp
                                @if($barcodeVisible)
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Código de Barras @if($barcodeRequired)*@endif</label>
                                    <input wire:model="barcode" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="Ej: 7701234567890">
                                    @error('barcode')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                                </div>
                                @endif

                                @php
                                    $presentationField = $fieldSettings['presentation_id'] ?? null;
                                    $presentationVisible = $presentationField ? (is_object($presentationField) ? $presentationField->parent_visible : ($presentationField['parent_visible'] ?? false)) : false;
                                    $presentationRequired = $presentationField ? (is_object($presentationField) ? $presentationField->parent_required : ($presentationField['parent_required'] ?? false)) : false;
                                @endphp
                                @if($presentationVisible)
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Presentación @if($presentationRequired)*@endif</label>
                                    <select wire:model="presentation_id" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                        <option value="">Seleccionar presentación...</option>
                                        @foreach($presentations as $presentation)
                                        <option value="{{ $presentation->id }}">{{ $presentation->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('presentation_id')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                                </div>
                                @endif

                                @php
                                    $colorField = $fieldSettings['color_id'] ?? null;
                                    $colorVisible = $colorField ? (is_object($colorField) ? $colorField->parent_visible : ($colorField['parent_visible'] ?? false)) : false;
                                    $colorRequired = $colorField ? (is_object($colorField) ? $colorField->parent_required : ($colorField['parent_required'] ?? false)) : false;
                                @endphp
                                @if($colorVisible)
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Color @if($colorRequired)*@endif</label>
                                    <select wire:model="color_id" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                        <option value="">Seleccionar color...</option>
                                        @foreach($colors as $color)
                                        <option value="{{ $color->id }}">{{ $color->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('color_id')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                                </div>
                                @endif

                                @php
                                    $modelField = $fieldSettings['product_model_id'] ?? null;
                                    $modelVisible = $modelField ? (is_object($modelField) ? $modelField->parent_visible : ($modelField['parent_visible'] ?? false)) : false;
                                    $modelRequired = $modelField ? (is_object($modelField) ? $modelField->parent_required : ($modelField['parent_required'] ?? false)) : false;
                                @endphp
                                @if($modelVisible)
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Modelo @if($modelRequired)*@endif</label>
                                    <select wire:model="product_model_id" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                        <option value="">Seleccionar modelo...</option>
                                        @foreach($productModels as $model)
                                        <option value="{{ $model->id }}">{{ $model->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('product_model_id')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                                </div>
                                @endif

                                @php
                                    $sizeField = $fieldSettings['size'] ?? null;
                                    $sizeVisible = $sizeField ? (is_object($sizeField) ? $sizeField->parent_visible : ($sizeField['parent_visible'] ?? false)) : false;
                                    $sizeRequired = $sizeField ? (is_object($sizeField) ? $sizeField->parent_required : ($sizeField['parent_required'] ?? false)) : false;
                                @endphp
                                @if($sizeVisible)
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Talla @if($sizeRequired)*@endif</label>
                                    <input wire:model="size" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="Ej: M, L, XL">
                                    @error('size')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                                </div>
                                @endif

                                @php
                                    $weightField = $fieldSettings['weight'] ?? null;
                                    $weightVisible = $weightField ? (is_object($weightField) ? $weightField->parent_visible : ($weightField['parent_visible'] ?? false)) : false;
                                    $weightRequired = $weightField ? (is_object($weightField) ? $weightField->parent_required : ($weightField['parent_required'] ?? false)) : false;
                                @endphp
                                @if($weightVisible)
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Peso (g) @if($weightRequired)*@endif</label>
                                    <input wire:model="weight" type="number" step="0.001" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="Ej: 100.5">
                                    @error('weight')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                                </div>
                                @endif

                                @php
                                    $imeiField = $fieldSettings['imei'] ?? null;
                                    $imeiVisible = $imeiField ? (is_object($imeiField) ? $imeiField->parent_visible : ($imeiField['parent_visible'] ?? false)) : false;
                                    $imeiRequired = $imeiField ? (is_object($imeiField) ? $imeiField->parent_required : ($imeiField['parent_required'] ?? false)) : false;
                                @endphp
                                @if($imeiVisible)
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">IMEI @if($imeiRequired)*@endif</label>
                                    <input wire:model="imei" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="15-17 dígitos">
                                    @error('imei')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                                </div>
                                @endif
                            </div>
                        </div>
                        @endif
                        <div>
                            <h4 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Precios
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Precio Compra *</label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500">$</span>
                                        <input wire:model.live="purchase_price" x-model.number="purchasePrice" type="number" step="0.01" min="0" class="w-full pl-7 pr-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="0.00">
                                    </div>
                                    @error('purchase_price')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Precio Venta *</label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500">$</span>
                                        <input wire:model.live="sale_price" x-model.number="salePrice" type="number" step="0.01" min="0" class="w-full pl-7 pr-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="0.00">
                                    </div>
                                    @error('sale_price')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                                </div>
                            </div>
                            <div class="mt-3 flex flex-wrap items-center gap-4">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input wire:model="price_includes_tax" type="checkbox" class="w-4 h-4 rounded border-slate-300 text-[#ff7261] focus:ring-[#ff7261]">
                                    <span class="text-sm text-slate-700">Precio incluye impuesto</span>
                                </label>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm text-slate-500">Margen:</span>
                                    <template x-if="margin !== null">
                                        <span class="text-sm font-semibold" :class="{
                                            'text-green-600': margin >= 20,
                                            'text-amber-600': margin >= 10 && margin < 20,
                                            'text-red-500': margin < 10
                                        }" x-text="margin + '%'"></span>
                                    </template>
                                    <template x-if="margin === null">
                                        <span class="text-sm text-slate-400">-</span>
                                    </template>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm text-slate-500">Ganancia:</span>
                                    <template x-if="purchasePrice > 0">
                                        <span class="text-sm font-semibold" :class="{
                                            'text-green-600': (salePrice - purchasePrice) > 0,
                                            'text-red-500': (salePrice - purchasePrice) <= 0
                                        }" x-text="'$' + (salePrice - purchasePrice).toFixed(2)"></span>
                                    </template>
                                    <template x-if="!purchasePrice || purchasePrice <= 0">
                                        <span class="text-sm text-slate-400">-</span>
                                    </template>
                                </div>
                            </div>
                            <div x-show="hasNegativeMargin" x-transition class="mt-3 p-3 bg-red-50 border border-red-200 rounded-xl flex items-center gap-2">
                                <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                <span class="text-sm text-red-700">Advertencia: El precio de venta es menor al precio de compra</span>
                            </div>
                        </div>
                        <div>
                            <h4 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                                Inventario
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Stock Inicial *</label>
                                    <input wire:model="current_stock" type="number" min="0" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="0">
                                    @error('current_stock')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Stock Mínimo</label>
                                    <input wire:model="min_stock" type="number" min="0" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="0">
                                    @error('min_stock')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Stock Máximo</label>
                                    <input wire:model="max_stock" type="number" min="0" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="Opcional">
                                    @error('max_stock')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                                </div>
                            </div>
                        </div>
                        <div>
                            <h4 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"></path></svg>
                                Impuesto
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Impuesto</label>
                                    <select wire:model="tax_id" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                        <option value="">Sin impuesto</option>
                                        @foreach($taxes as $tax)
                                        <option value="{{ $tax->id }}">{{ $tax->name }} ({{ $tax->rate }}%)</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        {{-- Commission Section --}}
                        <div x-data="{
                            hasCommission: @entangle('has_commission'),
                            commissionType: @entangle('commission_type'),
                            commissionValue: @entangle('commission_value'),
                            get commissionAmount() {
                                if (!this.hasCommission || !this.commissionValue) return 0;
                                if (this.commissionType === 'percentage') {
                                    return (salePrice * (this.commissionValue / 100)).toFixed(2);
                                }
                                return parseFloat(this.commissionValue).toFixed(2);
                            },
                            get profitAfterCommission() {
                                const profit = salePrice - purchasePrice;
                                return (profit - parseFloat(this.commissionAmount)).toFixed(2);
                            }
                        }">
                            <h4 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                Comisión Vendedor
                            </h4>
                            <div class="space-y-4">
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input wire:model.live="has_commission" x-model="hasCommission" type="checkbox" class="w-5 h-5 rounded border-slate-300 text-[#ff7261] focus:ring-[#ff7261]">
                                    <span class="text-sm text-slate-700">Este producto tiene comisión para el vendedor</span>
                                </label>
                                
                                <div x-show="hasCommission" x-transition class="p-4 bg-gradient-to-r from-purple-50 to-pink-50 rounded-xl border border-purple-100 space-y-4">
                                    <div class="flex gap-3">
                                        <button type="button" @click="commissionType = 'percentage'; $wire.set('commission_type', 'percentage')" 
                                            :class="commissionType === 'percentage' ? 'bg-gradient-to-r from-[#ff7261] to-[#a855f7] text-white shadow-lg' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50'"
                                            class="flex-1 py-2.5 px-4 rounded-xl font-medium transition-all text-sm">
                                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                                            Porcentaje
                                        </button>
                                        <button type="button" @click="commissionType = 'fixed'; $wire.set('commission_type', 'fixed')"
                                            :class="commissionType === 'fixed' ? 'bg-gradient-to-r from-[#ff7261] to-[#a855f7] text-white shadow-lg' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50'"
                                            class="flex-1 py-2.5 px-4 rounded-xl font-medium transition-all text-sm">
                                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            Valor Fijo
                                        </button>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1" x-text="commissionType === 'percentage' ? 'Porcentaje de comisión' : 'Valor de comisión'"></label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500" x-text="commissionType === 'percentage' ? '%' : '$'"></span>
                                            <input wire:model.live="commission_value" x-model.number="commissionValue" type="number" step="0.01" min="0" :max="commissionType === 'percentage' ? 100 : undefined" class="w-full pl-8 pr-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="0.00">
                                        </div>
                                    </div>
                                    
                                    <div class="grid grid-cols-2 gap-4 pt-2">
                                        <div class="p-3 bg-white rounded-xl border border-purple-200">
                                            <p class="text-xs text-slate-500 mb-1">Comisión por venta</p>
                                            <p class="text-lg font-bold text-purple-600">$<span x-text="commissionAmount">0.00</span></p>
                                        </div>
                                        <div class="p-3 bg-white rounded-xl border border-green-200">
                                            <p class="text-xs text-slate-500 mb-1">Ganancia neta</p>
                                            <p class="text-lg font-bold" :class="profitAfterCommission >= 0 ? 'text-green-600' : 'text-red-500'">$<span x-text="profitAfterCommission">0.00</span></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="pt-2">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input wire:model="is_active" type="checkbox" class="w-4 h-4 rounded border-slate-300 text-[#ff7261] focus:ring-[#ff7261]">
                                <span class="text-sm text-slate-700">Producto activo</span>
                            </label>
                        </div>
                        {{-- Image Upload Section --}}
                        <div>
                            <h4 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                Imagen del Producto
                            </h4>
                            <div class="space-y-3">
                                @if($existingImage || $image)
                                <div class="flex items-center gap-4">
                                    <div class="relative">
                                        @if($image)
                                        <img src="{{ $image->temporaryUrl() }}" alt="Preview" class="w-24 h-24 rounded-xl object-cover border border-slate-200">
                                        @elseif($existingImage)
                                        <img src="{{ Storage::url($existingImage) }}" alt="Imagen actual" class="w-24 h-24 rounded-xl object-cover border border-slate-200">
                                        @endif
                                        <button type="button" wire:click="removeImage" class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center hover:bg-red-600 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        </button>
                                    </div>
                                    <div class="text-sm text-slate-500">
                                        @if($image)
                                        <p>Nueva imagen seleccionada</p>
                                        @else
                                        <p>Imagen actual</p>
                                        @endif
                                    </div>
                                </div>
                                @endif
                                <div class="flex items-center justify-center w-full">
                                    <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-slate-300 border-dashed rounded-xl cursor-pointer bg-slate-50 hover:bg-slate-100 transition-colors">
                                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                            <svg class="w-8 h-8 mb-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                                            <p class="mb-1 text-sm text-slate-500"><span class="font-semibold">Clic para subir</span> o arrastra y suelta</p>
                                            <p class="text-xs text-slate-400">JPG, PNG o WebP (máx. 2MB)</p>
                                        </div>
                                        <input wire:model="image" type="file" class="hidden" accept="image/jpeg,image/png,image/webp">
                                    </label>
                                </div>
                                @error('image')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                                <div wire:loading wire:target="image" class="text-sm text-slate-500 flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4 text-[#ff7261]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Subiendo imagen...
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
                        <button wire:click="$set('isModalOpen', false)" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">Cancelar</button>
                        <button wire:click="store" class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea]">
                            <span wire:loading.remove wire:target="store">Guardar</span>
                            <span wire:loading wire:target="store">Guardando...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Create/Edit Child Product Modal --}}
    @if($isChildModalOpen)
    <div class="relative z-[100]" x-data="{
        unitQuantity: @entangle('childUnitQuantity'),
        parentPurchasePrice: {{ $parentProduct?->purchase_price ?? 0 }},
        salePrice: @entangle('childSalePrice'),
        get purchasePrice() {
            return this.parentPurchasePrice * this.unitQuantity;
        },
        get margin() {
            if (!this.purchasePrice || this.purchasePrice <= 0) return null;
            return ((this.salePrice - this.purchasePrice) / this.purchasePrice * 100).toFixed(1);
        },
        get profit() {
            return this.salePrice - this.purchasePrice;
        },
        get hasNegativeMargin() {
            return this.profit < 0;
        }
    }">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="$set('isChildModalOpen', false)"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-xl">
                    <div class="px-6 py-4 border-b border-slate-200">
                        <h3 class="text-lg font-bold text-slate-900">
                            {{ $childId ? 'Editar' : 'Nueva' }} Variante
                            @if($parentProduct)
                            <span class="text-slate-500 font-normal">- {{ $parentProduct->name }}</span>
                            @endif
                        </h3>
                    </div>
                    <div class="px-6 py-4 space-y-6 max-h-[70vh] overflow-y-auto">
                        {{-- Inherited Info from Parent (Read-only) --}}
                        @if($parentProduct)
                        <div class="bg-slate-50 rounded-xl p-4">
                            <h4 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Información Heredada del Producto Padre
                            </h4>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-sm">
                                <div>
                                    <span class="text-slate-500">Categoría:</span>
                                    <p class="font-medium text-slate-700">{{ $parentProduct->category?->name ?? '-' }}</p>
                                </div>
                                <div>
                                    <span class="text-slate-500">Marca:</span>
                                    <p class="font-medium text-slate-700">{{ $parentProduct->brand?->name ?? '-' }}</p>
                                </div>
                                <div>
                                    <span class="text-slate-500">Impuesto:</span>
                                    <p class="font-medium text-slate-700">{{ $parentProduct->tax ? $parentProduct->tax->name . ' (' . $parentProduct->tax->rate . '%)' : 'Sin impuesto' }}</p>
                                </div>
                                <div>
                                    <span class="text-slate-500">Unidad Base:</span>
                                    <p class="font-medium text-slate-700">{{ $parentProduct->unit?->name ?? '-' }} ({{ $parentProduct->unit?->abbreviation ?? '-' }})</p>
                                </div>
                                <div>
                                    <span class="text-slate-500">Precio Compra (por unidad):</span>
                                    <p class="font-medium text-slate-700">${{ number_format($parentProduct->purchase_price, 2) }}</p>
                                </div>
                                <div>
                                    <span class="text-slate-500">Stock Actual:</span>
                                    <p class="font-medium text-slate-700">{{ $parentProduct->current_stock }} {{ $parentProduct->unit?->abbreviation ?? 'und' }}</p>
                                </div>
                            </div>
                        </div>
                        @endif

                        {{-- Variant Basic Info --}}
                        <div>
                            <h4 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                                Información de Variante
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Nombre Variante *</label>
                                    <input wire:model="childName" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="Ej: Caja x100">
                                    @error('childName')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">SKU <span class="text-slate-400 font-normal">(opcional)</span></label>
                                    <input wire:model="childSku" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="Ej: MED-001-100">
                                    @error('childSku')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-slate-700 mb-1">
                                        Cantidad de {{ $parentProduct?->unit?->name ?? 'unidades' }} por variante *
                                        <span class="text-slate-400 font-normal">(cuántas {{ $parentProduct?->unit?->abbreviation ?? 'und' }} del padre consume esta variante)</span>
                                    </label>
                                    <div class="flex items-center gap-2">
                                        <input wire:model.live="childUnitQuantity" x-model.number="unitQuantity" type="number" step="0.001" min="0.001" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="Ej: 6">
                                        <span class="text-sm text-slate-500 whitespace-nowrap">{{ $parentProduct?->unit?->abbreviation ?? 'und' }}</span>
                                    </div>
                                    <p class="text-xs text-slate-500 mt-1">Ej: Si el padre es "Pastilla" y la variante es "Tableta x6", ingresa 6</p>
                                    @error('childUnitQuantity')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                                </div>
                            </div>
                        </div>

                        {{-- Configurable Fields --}}
                        <div>
                            <h4 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
                                Atributos
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                @php
                                    $barcodeField = $fieldSettings['barcode'] ?? null;
                                    $barcodeVisible = $barcodeField ? (is_object($barcodeField) ? $barcodeField->child_visible : ($barcodeField['child_visible'] ?? false)) : false;
                                    $barcodeRequired = $barcodeField ? (is_object($barcodeField) ? $barcodeField->child_required : ($barcodeField['child_required'] ?? false)) : false;
                                @endphp
                                @if($barcodeVisible)
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Código de Barras @if($barcodeRequired)*@endif</label>
                                    <input wire:model="childBarcode" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="Ej: 7701234567890">
                                    @error('childBarcode')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                                </div>
                                @endif

                                @php
                                    $presentationField = $fieldSettings['presentation_id'] ?? null;
                                    $presentationVisible = $presentationField ? (is_object($presentationField) ? $presentationField->child_visible : ($presentationField['child_visible'] ?? false)) : false;
                                    $presentationRequired = $presentationField ? (is_object($presentationField) ? $presentationField->child_required : ($presentationField['child_required'] ?? false)) : false;
                                @endphp
                                @if($presentationVisible)
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Presentación @if($presentationRequired)*@endif</label>
                                    <select wire:model="childPresentationId" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                        <option value="">Seleccionar presentación...</option>
                                        @foreach($presentations as $presentation)
                                        <option value="{{ $presentation->id }}">{{ $presentation->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('childPresentationId')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                                </div>
                                @endif

                                @php
                                    $colorField = $fieldSettings['color_id'] ?? null;
                                    $colorVisible = $colorField ? (is_object($colorField) ? $colorField->child_visible : ($colorField['child_visible'] ?? false)) : false;
                                    $colorRequired = $colorField ? (is_object($colorField) ? $colorField->child_required : ($colorField['child_required'] ?? false)) : false;
                                @endphp
                                @if($colorVisible)
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Color @if($colorRequired)*@endif</label>
                                    <select wire:model="childColorId" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                        <option value="">Seleccionar color...</option>
                                        @foreach($colors as $color)
                                        <option value="{{ $color->id }}">{{ $color->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('childColorId')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                                </div>
                                @endif

                                @php
                                    $modelField = $fieldSettings['product_model_id'] ?? null;
                                    $modelVisible = $modelField ? (is_object($modelField) ? $modelField->child_visible : ($modelField['child_visible'] ?? false)) : false;
                                    $modelRequired = $modelField ? (is_object($modelField) ? $modelField->child_required : ($modelField['child_required'] ?? false)) : false;
                                @endphp
                                @if($modelVisible)
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Modelo @if($modelRequired)*@endif</label>
                                    <select wire:model="childProductModelId" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                        <option value="">Seleccionar modelo...</option>
                                        @foreach($productModels as $model)
                                        <option value="{{ $model->id }}">{{ $model->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('childProductModelId')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                                </div>
                                @endif

                                @php
                                    $sizeField = $fieldSettings['size'] ?? null;
                                    $sizeVisible = $sizeField ? (is_object($sizeField) ? $sizeField->child_visible : ($sizeField['child_visible'] ?? false)) : false;
                                    $sizeRequired = $sizeField ? (is_object($sizeField) ? $sizeField->child_required : ($sizeField['child_required'] ?? false)) : false;
                                @endphp
                                @if($sizeVisible)
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Talla @if($sizeRequired)*@endif</label>
                                    <input wire:model="childSize" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="Ej: M, L, XL">
                                    @error('childSize')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                                </div>
                                @endif

                                @php
                                    $weightField = $fieldSettings['weight'] ?? null;
                                    $weightVisible = $weightField ? (is_object($weightField) ? $weightField->child_visible : ($weightField['child_visible'] ?? false)) : false;
                                    $weightRequired = $weightField ? (is_object($weightField) ? $weightField->child_required : ($weightField['child_required'] ?? false)) : false;
                                @endphp
                                @if($weightVisible)
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Peso (g) @if($weightRequired)*@endif</label>
                                    <input wire:model="childWeight" type="number" step="0.001" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="Ej: 100.5">
                                    @error('childWeight')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                                </div>
                                @endif

                                @php
                                    $imeiField = $fieldSettings['imei'] ?? null;
                                    $imeiVisible = $imeiField ? (is_object($imeiField) ? $imeiField->child_visible : ($imeiField['child_visible'] ?? false)) : false;
                                    $imeiRequired = $imeiField ? (is_object($imeiField) ? $imeiField->child_required : ($imeiField['child_required'] ?? false)) : false;
                                @endphp
                                @if($imeiVisible)
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">IMEI @if($imeiRequired)*@endif</label>
                                    <input wire:model="childImei" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="15-17 dígitos">
                                    @error('childImei')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                                </div>
                                @endif
                            </div>
                        </div>

                        {{-- Prices Section with Real-time Margin Calculation --}}
                        <div>
                            <h4 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Precios
                            </h4>
                            {{-- Calculated Purchase Price (Read-only) --}}
                            <div class="bg-blue-50 rounded-xl p-3 mb-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-blue-700">Costo calculado ({{ $parentProduct?->unit?->abbreviation ?? 'und' }} x cantidad):</span>
                                    <span class="text-lg font-bold text-blue-800" x-text="'$' + purchasePrice.toFixed(2)"></span>
                                </div>
                                <p class="text-xs text-blue-600 mt-1">= ${{ number_format($parentProduct?->purchase_price ?? 0, 2) }} × <span x-text="unitQuantity"></span> {{ $parentProduct?->unit?->abbreviation ?? 'und' }}</p>
                            </div>
                            <div class="grid grid-cols-1 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Precio Venta *</label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500">$</span>
                                        <input wire:model.live="childSalePrice" x-model.number="salePrice" type="number" step="0.01" min="0" class="w-full pl-7 pr-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="0.00">
                                    </div>
                                    @error('childSalePrice')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                                </div>
                            </div>
                            <div class="mt-3 flex flex-wrap items-center gap-4">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input wire:model="childPriceIncludesTax" type="checkbox" class="w-4 h-4 rounded border-slate-300 text-[#ff7261] focus:ring-[#ff7261]">
                                    <span class="text-sm text-slate-700">Precio incluye impuesto</span>
                                </label>
                                {{-- Real-time Margin Display --}}
                                <div class="flex items-center gap-2">
                                    <span class="text-sm text-slate-500">Margen:</span>
                                    <template x-if="margin !== null">
                                        <span class="text-sm font-semibold" :class="{
                                            'text-green-600': margin >= 20,
                                            'text-amber-600': margin >= 10 && margin < 20,
                                            'text-red-500': margin < 10
                                        }" x-text="margin + '%'"></span>
                                    </template>
                                    <template x-if="margin === null">
                                        <span class="text-sm text-slate-400">-</span>
                                    </template>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm text-slate-500">Ganancia:</span>
                                    <span class="text-sm font-semibold" :class="{
                                        'text-green-600': profit > 0,
                                        'text-red-500': profit <= 0
                                    }" x-text="'$' + profit.toFixed(2)"></span>
                                </div>
                            </div>
                            {{-- Warning for negative margin --}}
                            <div x-show="hasNegativeMargin" x-transition class="mt-3 p-3 bg-red-50 border border-red-200 rounded-xl flex items-center gap-2">
                                <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                <span class="text-sm text-red-700">Advertencia: El precio de venta es menor al costo calculado</span>
                            </div>
                        </div>

                        {{-- Commission Section for Child --}}
                        <div x-data="{
                            hasCommission: @entangle('childHasCommission'),
                            commissionType: @entangle('childCommissionType'),
                            commissionValue: @entangle('childCommissionValue'),
                            get commissionAmount() {
                                if (!this.hasCommission || !this.commissionValue) return 0;
                                if (this.commissionType === 'percentage') {
                                    return (salePrice * (this.commissionValue / 100)).toFixed(2);
                                }
                                return parseFloat(this.commissionValue).toFixed(2);
                            },
                            get profitAfterCommission() {
                                return (profit - parseFloat(this.commissionAmount)).toFixed(2);
                            }
                        }">
                            <h4 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                Comisión Vendedor
                            </h4>
                            <div class="space-y-4">
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input wire:model.live="childHasCommission" x-model="hasCommission" type="checkbox" class="w-5 h-5 rounded border-slate-300 text-[#ff7261] focus:ring-[#ff7261]">
                                    <span class="text-sm text-slate-700">Esta variante tiene comisión para el vendedor</span>
                                </label>
                                
                                <div x-show="hasCommission" x-transition class="p-4 bg-gradient-to-r from-purple-50 to-pink-50 rounded-xl border border-purple-100 space-y-4">
                                    <div class="flex gap-3">
                                        <button type="button" @click="commissionType = 'percentage'; $wire.set('childCommissionType', 'percentage')" 
                                            :class="commissionType === 'percentage' ? 'bg-gradient-to-r from-[#ff7261] to-[#a855f7] text-white shadow-lg' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50'"
                                            class="flex-1 py-2 px-3 rounded-xl font-medium transition-all text-sm">
                                            Porcentaje
                                        </button>
                                        <button type="button" @click="commissionType = 'fixed'; $wire.set('childCommissionType', 'fixed')"
                                            :class="commissionType === 'fixed' ? 'bg-gradient-to-r from-[#ff7261] to-[#a855f7] text-white shadow-lg' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50'"
                                            class="flex-1 py-2 px-3 rounded-xl font-medium transition-all text-sm">
                                            Valor Fijo
                                        </button>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1" x-text="commissionType === 'percentage' ? 'Porcentaje de comisión' : 'Valor de comisión'"></label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500" x-text="commissionType === 'percentage' ? '%' : '$'"></span>
                                            <input wire:model.live="childCommissionValue" x-model.number="commissionValue" type="number" step="0.01" min="0" :max="commissionType === 'percentage' ? 100 : undefined" class="w-full pl-8 pr-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="0.00">
                                        </div>
                                    </div>
                                    
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="p-3 bg-white rounded-xl border border-purple-200">
                                            <p class="text-xs text-slate-500 mb-1">Comisión por venta</p>
                                            <p class="text-lg font-bold text-purple-600">$<span x-text="commissionAmount">0.00</span></p>
                                        </div>
                                        <div class="p-3 bg-white rounded-xl border border-green-200">
                                            <p class="text-xs text-slate-500 mb-1">Ganancia neta</p>
                                            <p class="text-lg font-bold" :class="profitAfterCommission >= 0 ? 'text-green-600' : 'text-red-500'">$<span x-text="profitAfterCommission">0.00</span></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Status --}}
                        <div class="pt-2">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input wire:model="childIsActive" type="checkbox" class="w-4 h-4 rounded border-slate-300 text-[#ff7261] focus:ring-[#ff7261]">
                                <span class="text-sm text-slate-700">Variante activa</span>
                            </label>
                        </div>

                        {{-- Image Upload Section for Child --}}
                        <div>
                            <h4 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                Imagen de la Variante
                                <span class="text-slate-400 font-normal text-xs">(opcional, usa imagen del padre si no se especifica)</span>
                            </h4>
                            <div class="space-y-3">
                                @if($childExistingImage || $childImage)
                                <div class="flex items-center gap-4">
                                    <div class="relative">
                                        @if($childImage)
                                        <img src="{{ $childImage->temporaryUrl() }}" alt="Preview" class="w-20 h-20 rounded-xl object-cover border border-slate-200">
                                        @elseif($childExistingImage)
                                        <img src="{{ Storage::url($childExistingImage) }}" alt="Imagen actual" class="w-20 h-20 rounded-xl object-cover border border-slate-200">
                                        @endif
                                        <button type="button" wire:click="removeChildImage" class="absolute -top-2 -right-2 w-5 h-5 bg-red-500 text-white rounded-full flex items-center justify-center hover:bg-red-600 transition-colors">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        </button>
                                    </div>
                                    <div class="text-sm text-slate-500">
                                        @if($childImage)
                                        <p>Nueva imagen seleccionada</p>
                                        @else
                                        <p>Imagen actual</p>
                                        @endif
                                    </div>
                                </div>
                                @endif
                                <div class="flex items-center justify-center w-full">
                                    <label class="flex flex-col items-center justify-center w-full h-28 border-2 border-slate-300 border-dashed rounded-xl cursor-pointer bg-slate-50 hover:bg-slate-100 transition-colors">
                                        <div class="flex flex-col items-center justify-center pt-4 pb-5">
                                            <svg class="w-6 h-6 mb-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                                            <p class="mb-1 text-sm text-slate-500"><span class="font-semibold">Clic para subir</span></p>
                                            <p class="text-xs text-slate-400">JPG, PNG o WebP (máx. 2MB)</p>
                                        </div>
                                        <input wire:model="childImage" type="file" class="hidden" accept="image/jpeg,image/png,image/webp">
                                    </label>
                                </div>
                                @error('childImage')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                                <div wire:loading wire:target="childImage" class="text-sm text-slate-500 flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4 text-[#ff7261]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Subiendo imagen...
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
                        <button wire:click="$set('isChildModalOpen', false)" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">Cancelar</button>
                        <button wire:click="storeChild" class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea]">
                            <span wire:loading.remove wire:target="storeChild">Guardar</span>
                            <span wire:loading wire:target="storeChild">Guardando...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Delete Parent Product Confirmation Modal --}}
    @if($isDeleteModalOpen)
    <div class="relative z-[100]">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="$set('isDeleteModalOpen', false)"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-md bg-white rounded-2xl shadow-xl p-6 text-center">
                    <div class="mx-auto w-12 h-12 rounded-full bg-red-100 flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Eliminar Producto</h3>
                    <p class="text-slate-500 mb-6">¿Estás seguro? Esta acción eliminará el producto y todas sus variantes. No se puede deshacer.</p>
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

    {{-- Delete Child Product Confirmation Modal --}}
    @if($isChildDeleteModalOpen)
    <div class="relative z-[100]">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="$set('isChildDeleteModalOpen', false)"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-md bg-white rounded-2xl shadow-xl p-6 text-center">
                    <div class="mx-auto w-12 h-12 rounded-full bg-red-100 flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Eliminar Variante</h3>
                    <p class="text-slate-500 mb-6">¿Estás seguro de eliminar esta variante? Esta acción no se puede deshacer.</p>
                    <div class="flex justify-center gap-3">
                        <button wire:click="$set('isChildDeleteModalOpen', false)" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">Cancelar</button>
                        <button wire:click="deleteChild" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-xl hover:bg-red-700">
                            <span wire:loading.remove wire:target="deleteChild">Eliminar</span>
                            <span wire:loading wire:target="deleteChild">Eliminando...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Import CSV Modal --}}
    @if($isImportModalOpen)
    <div class="relative z-[100]" x-data="{ dragover: false }">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="closeImportModal"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-3xl bg-white rounded-2xl shadow-xl">
                    {{-- Header --}}
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">Importar Productos desde CSV</h3>
                            <p class="text-sm text-slate-500 mt-0.5">Carga masiva de productos y variantes</p>
                        </div>
                        <button wire:click="closeImportModal" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    {{-- Content --}}
                    <div class="px-6 py-4 space-y-6 max-h-[70vh] overflow-y-auto">
                        {{-- Instructions --}}
                        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-blue-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <div class="flex-1">
                                    <h4 class="text-sm font-semibold text-blue-800 mb-2">Pasos para Importar</h4>
                                    <ol class="text-sm text-blue-700 space-y-1 list-decimal list-inside">
                                        <li>Descarga la plantilla Excel (.xlsx)</li>
                                        <li>Abre el archivo y completa los datos (ver hoja "Instrucciones")</li>
                                        <li>Elimina las filas de ejemplo</li>
                                        <li><span class="font-semibold">Guarda como CSV:</span> Archivo → Guardar como → CSV UTF-8</li>
                                        <li>Sube el archivo CSV aquí</li>
                                    </ol>
                                </div>
                            </div>
                        </div>

                        {{-- Download Template Button --}}
                        <div class="flex justify-center">
                            <button wire:click="downloadTemplate" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-xl shadow-sm transition-colors">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                Descargar Plantilla Excel
                            </button>
                        </div>

                        {{-- Branch Selection Warning for Super Admin --}}
                        @if($needsBranchSelection && !$filterBranch)
                        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-amber-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                <div>
                                    <h4 class="text-sm font-semibold text-amber-800">Selecciona una Sucursal</h4>
                                    <p class="text-sm text-amber-700 mt-1">Debes seleccionar una sucursal en los filtros antes de importar productos.</p>
                                </div>
                            </div>
                        </div>
                        @endif

                        {{-- File Upload Area --}}
                        <div 
                            class="relative border-2 border-dashed rounded-xl p-8 text-center transition-colors"
                            :class="dragover ? 'border-[#ff7261] bg-orange-50' : 'border-slate-300 hover:border-slate-400'"
                            x-on:dragover.prevent="dragover = true"
                            x-on:dragleave.prevent="dragover = false"
                            x-on:drop.prevent="dragover = false; $refs.fileInput.files = $event.dataTransfer.files; $refs.fileInput.dispatchEvent(new Event('change'))"
                        >
                            <input 
                                type="file" 
                                wire:model="importFile" 
                                accept=".csv"
                                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                x-ref="fileInput"
                            >
                            {{-- Normal state --}}
                            <div class="pointer-events-none" wire:loading.remove wire:target="importFile">
                                <svg class="w-12 h-12 mx-auto text-slate-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                                <p class="text-slate-600 font-medium">Arrastra tu archivo CSV aquí</p>
                                <p class="text-slate-400 text-sm mt-1">Solo archivos .csv (guardado desde Excel)</p>
                            </div>
                            {{-- Loading state --}}
                            <div wire:loading wire:target="importFile" class="pointer-events-none">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-12 h-12 text-[#ff7261] animate-spin mb-3" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <p class="text-slate-700 font-semibold">Procesando archivo...</p>
                                    <p class="text-slate-500 text-sm mt-1">Por favor espere</p>
                                </div>
                            </div>
                        </div>

                        {{-- Import Errors --}}
                        @if(count($importErrors) > 0)
                        <div class="bg-red-50 border border-red-200 rounded-xl p-4">
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-red-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <div class="flex-1">
                                    <h4 class="text-sm font-semibold text-red-800 mb-2">Errores encontrados</h4>
                                    <ul class="text-sm text-red-700 space-y-1 max-h-32 overflow-y-auto">
                                        @foreach($importErrors as $error)
                                        <li>
                                            @if($error['row'] > 0)
                                            <span class="font-medium">Fila {{ $error['row'] }}:</span>
                                            @endif
                                            {{ $error['message'] }}
                                        </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                        @endif

                        {{-- Preview Table --}}
                        @if(count($importPreview) > 0)
                        <div>
                            <h4 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                Vista Previa ({{ count($importPreview) }} registros)
                            </h4>
                            <div class="border border-slate-200 rounded-xl overflow-hidden">
                                <div class="overflow-x-auto max-h-64">
                                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                                        <thead class="bg-slate-50 sticky top-0">
                                            <tr>
                                                <th class="px-3 py-2 text-left font-semibold text-slate-500">Fila</th>
                                                <th class="px-3 py-2 text-left font-semibold text-slate-500">Tipo</th>
                                                <th class="px-3 py-2 text-left font-semibold text-slate-500">Nombre</th>
                                                <th class="px-3 py-2 text-right font-semibold text-slate-500">Precio</th>
                                                <th class="px-3 py-2 text-center font-semibold text-slate-500">Estado</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-200">
                                            @foreach($importPreview as $row)
                                            <tr class="{{ $row['valid'] ? 'bg-white' : 'bg-red-50' }}">
                                                <td class="px-3 py-2 text-slate-600">{{ $row['row'] }}</td>
                                                <td class="px-3 py-2">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ strtoupper($row['data']['tipo'] ?? '') === 'PADRE' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                                                        {{ $row['data']['tipo'] ?? '-' }}
                                                    </span>
                                                </td>
                                                <td class="px-3 py-2 text-slate-900 max-w-xs truncate">{{ $row['data']['nombre'] ?? '-' }}</td>
                                                <td class="px-3 py-2 text-right text-slate-600">${{ number_format(floatval($row['data']['precio_venta'] ?? 0), 2) }}</td>
                                                <td class="px-3 py-2 text-center">
                                                    @if($row['valid'])
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                        Válido
                                                    </span>
                                                    @else
                                                    <div class="flex flex-col items-center gap-1">
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                                            Error
                                                        </span>
                                                        <span class="text-xs text-red-600 max-w-xs truncate" title="{{ implode(', ', $row['errors']) }}">
                                                            {{ Str::limit(implode(', ', $row['errors']), 50) }}
                                                        </span>
                                                    </div>
                                                    @endif
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            @php
                                $validCount = count(array_filter($importPreview, fn($r) => $r['valid']));
                                $invalidCount = count($importPreview) - $validCount;
                            @endphp
                            <div class="mt-2 flex items-center gap-4 text-sm">
                                <span class="text-green-600">
                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    {{ $validCount }} válidos
                                </span>
                                @if($invalidCount > 0)
                                <span class="text-red-600">
                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    {{ $invalidCount }} con errores
                                </span>
                                @endif
                            </div>
                        </div>
                        @endif

                        {{-- Progress Bar --}}
                        @if($isImporting)
                        <div class="bg-gradient-to-r from-blue-50 to-purple-50 border border-blue-200 rounded-xl p-6" wire:poll.500ms>
                            <div class="text-center mb-4">
                                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gradient-to-r from-[#ff7261] to-[#a855f7] mb-3">
                                    <svg class="w-8 h-8 text-white animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>
                                <h4 class="text-lg font-bold text-slate-800">Importando productos...</h4>
                                <p class="text-slate-500 text-sm mt-1">Por favor espere, no cierre esta ventana</p>
                            </div>
                            
                            {{-- Progress percentage --}}
                            @php
                                $percentage = $importTotal > 0 ? round(($importProgress / $importTotal) * 100) : 0;
                            @endphp
                            <div class="text-center mb-3">
                                <span class="text-4xl font-bold bg-gradient-to-r from-[#ff7261] to-[#a855f7] bg-clip-text text-transparent">{{ $percentage }}%</span>
                            </div>
                            
                            {{-- Progress bar --}}
                            <div class="w-full bg-slate-200 rounded-full h-4 overflow-hidden mb-3">
                                <div 
                                    class="bg-gradient-to-r from-[#ff7261] to-[#a855f7] h-4 rounded-full transition-all duration-300 relative"
                                    style="width: {{ $percentage }}%"
                                >
                                    <div class="absolute inset-0 bg-white/20 animate-pulse"></div>
                                </div>
                            </div>
                            
                            {{-- Counter --}}
                            <div class="text-center text-sm text-slate-600">
                                <span class="font-semibold">{{ $importProgress }}</span> de <span class="font-semibold">{{ $importTotal }}</span> productos procesados
                            </div>
                        </div>
                        @endif

                        {{-- Import Results --}}
                        @if($importProcessed)
                        <div class="bg-slate-50 border border-slate-200 rounded-xl p-4">
                            <div class="flex items-center gap-4">
                                <div class="flex-1">
                                    <h4 class="text-sm font-semibold text-slate-800 mb-1">Resultado de la Importación</h4>
                                    <div class="flex items-center gap-4 text-sm">
                                        <span class="text-green-600">
                                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                            {{ $importSuccessCount }} importados
                                        </span>
                                        @if($importErrorCount > 0)
                                        <span class="text-red-600">
                                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                            {{ $importErrorCount }} con errores
                                        </span>
                                        @endif
                                    </div>
                                </div>
                                <button wire:click="closeImportModal" class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea]">
                                    Cerrar
                                </button>
                            </div>
                        </div>
                        @endif
                    </div>

                    {{-- Footer --}}
                    @if(!$importProcessed)
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
                        <button wire:click="closeImportModal" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">
                            Cancelar
                        </button>
                        @php
                            $canImport = count($importPreview) > 0 && count(array_filter($importPreview, fn($r) => $r['valid'])) > 0;
                            $needsBranch = $needsBranchSelection && !$filterBranch;
                        @endphp
                        <button 
                            wire:click="executeImport" 
                            class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea] disabled:opacity-50 disabled:cursor-not-allowed"
                            {{ !$canImport || $needsBranch ? 'disabled' : '' }}
                        >
                            <span wire:loading.remove wire:target="executeImport">
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                Importar Productos
                            </span>
                            <span wire:loading wire:target="executeImport">
                                <svg class="w-4 h-4 inline mr-1 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                Importando...
                            </span>
                        </button>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

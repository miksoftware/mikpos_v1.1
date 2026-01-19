<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Combos</h1>
            <p class="text-slate-500 mt-1">Gestiona los combos y promociones de productos</p>
        </div>
        @if(auth()->user()->hasPermission('combos.create'))
        <button wire:click="create" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-[#ff7261] to-[#a855f7] hover:from-[#e55a4a] hover:to-[#9333ea] text-white text-sm font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-[1.02] transition-all duration-200">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Nuevo Combo
        </button>
        @endif
    </div>

    {{-- Search and Filters --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
        <div class="flex flex-col lg:flex-row gap-4">
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <input wire:model.live.debounce.300ms="search" type="text" class="block w-full pl-10 pr-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 placeholder-slate-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] transition-all sm:text-sm" placeholder="Buscar por nombre...">
            </div>
            <div class="flex flex-col sm:flex-row gap-3">
                <select wire:model.live="filterStatus" class="px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] sm:text-sm min-w-[140px]">
                    <option value="">Todos los estados</option>
                    <option value="available">Disponibles</option>
                    <option value="1">Activos</option>
                    <option value="0">Inactivos</option>
                </select>
                <select wire:model.live="filterLimitType" class="px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] sm:text-sm min-w-[140px]">
                    <option value="">Todos los tipos</option>
                    <option value="none">Sin límite</option>
                    <option value="time">Por tiempo</option>
                    <option value="quantity">Por cantidad</option>
                    <option value="both">Ambos</option>
                </select>
                @if($search || $filterStatus || $filterLimitType)
                <button wire:click="clearFilters" class="px-3 py-2.5 text-slate-500 hover:text-slate-700 hover:bg-slate-100 rounded-xl transition-colors text-sm font-medium">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Combos Table --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Combo</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold text-slate-500 uppercase">Productos</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-500 uppercase">Precio Original</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-500 uppercase">Precio Combo</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold text-slate-500 uppercase">Ahorro</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold text-slate-500 uppercase">Límite</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold text-slate-500 uppercase">Estado</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($items as $item)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                @if($item->image)
                                <img src="{{ Storage::url($item->image) }}" alt="{{ $item->name }}" class="w-10 h-10 rounded-lg object-cover">
                                @else
                                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-[#ff7261]/20 to-[#a855f7]/20 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-[#ff7261]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                </div>
                                @endif
                                <div>
                                    <div class="font-medium text-slate-900">{{ $item->name }}</div>
                                    @if($item->description)
                                    <div class="text-sm text-slate-500 truncate max-w-xs">{{ Str::limit($item->description, 40) }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium bg-blue-100 text-blue-700">
                                {{ $item->items_count }} productos
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="text-slate-500 line-through">${{ number_format($item->original_price, 2) }}</span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="text-lg font-bold text-[#ff7261]">${{ number_format($item->combo_price, 2) }}</span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($item->getSavingsPercentage() > 0)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-bold bg-green-100 text-green-700">
                                -{{ $item->getSavingsPercentage() }}%
                            </span>
                            <div class="text-xs text-green-600 mt-0.5">${{ number_format($item->getSavings(), 2) }}</div>
                            @else
                            <span class="text-slate-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($item->limit_type === 'none')
                            <span class="text-slate-400 text-sm">Sin límite</span>
                            @elseif($item->limit_type === 'time')
                            <div class="text-sm">
                                <svg class="w-4 h-4 inline text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <span class="text-slate-600">Por tiempo</span>
                            </div>
                            @elseif($item->limit_type === 'quantity')
                            <div class="text-sm">
                                <span class="text-slate-600">{{ $item->current_sales }}/{{ $item->max_sales }}</span>
                            </div>
                            @else
                            <div class="text-sm space-y-0.5">
                                <div><span class="text-slate-600">{{ $item->current_sales }}/{{ $item->max_sales }}</span></div>
                                <div class="text-xs text-slate-400">+ tiempo</div>
                            </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            @php $statusLabel = $item->getStatusLabel(); @endphp
                            @if(auth()->user()->hasPermission('combos.edit'))
                            <div class="flex flex-col items-center gap-1">
                                <button wire:click="toggleStatus({{ $item->id }})" class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 {{ $item->is_active ? 'bg-[#ff7261]' : 'bg-slate-200' }}">
                                    <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200 {{ $item->is_active ? 'translate-x-5' : 'translate-x-0' }}"></span>
                                </button>
                                <span class="text-xs {{ $statusLabel === 'Disponible' ? 'text-green-600' : ($statusLabel === 'Inactivo' ? 'text-slate-500' : ($statusLabel === 'Agotado' ? 'text-red-500' : 'text-amber-500')) }}">
                                    {{ $statusLabel }}
                                </span>
                            </div>
                            @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-sm font-medium 
                                {{ $statusLabel === 'Disponible' ? 'bg-green-100 text-green-700' : '' }}
                                {{ $statusLabel === 'Inactivo' ? 'bg-slate-100 text-slate-600' : '' }}
                                {{ $statusLabel === 'Agotado' ? 'bg-red-100 text-red-700' : '' }}
                                {{ $statusLabel === 'Expirado' ? 'bg-amber-100 text-amber-700' : '' }}
                                {{ $statusLabel === 'Próximamente' ? 'bg-blue-100 text-blue-700' : '' }}
                            ">
                                {{ $statusLabel }}
                            </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-1">
                                @if(auth()->user()->hasPermission('combos.edit'))
                                <button wire:click="edit({{ $item->id }})" class="p-2 text-slate-400 hover:text-[#ff7261] hover:bg-orange-50 rounded-lg transition-colors" title="Editar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </button>
                                @endif
                                @if(auth()->user()->hasPermission('combos.delete'))
                                <button wire:click="confirmDelete({{ $item->id }})" class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Eliminar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center">
                            <svg class="w-12 h-12 text-slate-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                            <p class="text-slate-500">No hay combos registrados</p>
                            @if($search || $filterStatus || $filterLimitType)
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


    {{-- Create/Edit Modal --}}
    @if($isModalOpen)
    <div class="relative z-[100]" x-data="{ 
        comboPrice: @entangle('combo_price'),
        originalPrice: {{ $this->original_price }},
        get savings() { return Math.max(0, this.originalPrice - this.comboPrice); },
        get savingsPercent() { return this.originalPrice > 0 ? ((this.savings / this.originalPrice) * 100).toFixed(1) : 0; }
    }">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="$set('isModalOpen', false)"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-3xl bg-white rounded-2xl shadow-xl">
                    <div class="px-6 py-4 border-b border-slate-200">
                        <h3 class="text-lg font-bold text-slate-900">{{ $itemId ? 'Editar' : 'Nuevo' }} Combo</h3>
                    </div>
                    <div class="px-6 py-4 space-y-6 max-h-[70vh] overflow-y-auto">
                        {{-- Basic Info --}}
                        <div>
                            <h4 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Información Básica
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Nombre del Combo *</label>
                                    <input wire:model="name" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="Ej: Combo Familiar">
                                    @error('name')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Descripción</label>
                                    <textarea wire:model="description" rows="2" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="Descripción del combo"></textarea>
                                </div>
                            </div>
                        </div>

                        {{-- Products Selection --}}
                        <div>
                            <h4 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                                Productos del Combo (mínimo 2)
                            </h4>
                            
                            {{-- Product Search --}}
                            <div class="relative mb-4">
                                <div class="relative">
                                    <input wire:model.live.debounce.300ms="productSearch" 
                                        wire:focus="$set('isProductSearchOpen', true)"
                                        type="text" 
                                        class="w-full px-3 py-2 pl-10 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" 
                                        placeholder="Buscar producto por nombre, SKU o código de barras...">
                                    <svg class="w-5 h-5 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                </div>
                                
                                {{-- Search Results Dropdown --}}
                                @if(count($searchResults) > 0)
                                <div class="absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-lg max-h-60 overflow-y-auto">
                                    @foreach($searchResults as $result)
                                    <button type="button" wire:click="addProduct('{{ $result['type'] }}', {{ $result['id'] }})" class="w-full px-4 py-3 flex items-center gap-3 hover:bg-slate-50 transition-colors text-left border-b border-slate-100 last:border-0">
                                        @if($result['image'])
                                        <img src="{{ Storage::url($result['image']) }}" class="w-10 h-10 rounded-lg object-cover">
                                        @else
                                        <div class="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center">
                                            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                                        </div>
                                        @endif
                                        <div class="flex-1 min-w-0">
                                            <p class="font-medium text-slate-800 truncate">{{ $result['name'] }}</p>
                                            <p class="text-xs text-slate-500">
                                                @if($result['sku'])SKU: {{ $result['sku'] }} · @endif
                                                Stock: {{ $result['stock'] }} {{ $result['unit'] }}
                                            </p>
                                        </div>
                                        <span class="text-sm font-semibold text-[#ff7261]">${{ number_format($result['price'], 2) }}</span>
                                    </button>
                                    @endforeach
                                </div>
                                @endif
                            </div>

                            {{-- Selected Products --}}
                            <div class="space-y-2">
                                @forelse($comboItems as $index => $item)
                                <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl">
                                    @if($item['image'])
                                    <img src="{{ Storage::url($item['image']) }}" class="w-12 h-12 rounded-lg object-cover">
                                    @else
                                    <div class="w-12 h-12 rounded-lg bg-slate-200 flex items-center justify-center">
                                        <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                                    </div>
                                    @endif
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-slate-800 truncate">{{ $item['name'] }}</p>
                                        <p class="text-sm text-slate-500">${{ number_format($item['price'], 2) }} c/u</p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button type="button" wire:click="updateQuantity({{ $index }}, {{ $item['quantity'] - 1 }})" class="w-8 h-8 rounded-lg bg-white border border-slate-200 flex items-center justify-center hover:bg-slate-100 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path></svg>
                                        </button>
                                        <span class="w-8 text-center font-medium">{{ $item['quantity'] }}</span>
                                        <button type="button" wire:click="updateQuantity({{ $index }}, {{ $item['quantity'] + 1 }})" class="w-8 h-8 rounded-lg bg-white border border-slate-200 flex items-center justify-center hover:bg-slate-100 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                        </button>
                                    </div>
                                    <span class="text-sm font-semibold text-slate-700 w-20 text-right">${{ number_format($item['price'] * $item['quantity'], 2) }}</span>
                                    <button type="button" wire:click="removeProduct({{ $index }})" class="p-1.5 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </div>
                                @empty
                                <div class="p-6 text-center text-slate-400 border-2 border-dashed border-slate-200 rounded-xl">
                                    <svg class="w-10 h-10 mx-auto mb-2 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                                    <p>Busca y agrega productos al combo</p>
                                </div>
                                @endforelse
                            </div>
                            @error('comboItems')<span class="text-red-500 text-sm mt-2 block">{{ $message }}</span>@enderror

                            {{-- Price Summary --}}
                            @if(count($comboItems) > 0)
                            <div class="mt-4 p-4 bg-gradient-to-r from-[#ff7261]/10 to-[#a855f7]/10 rounded-xl">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-slate-600">Precio original (suma):</span>
                                    <span class="font-medium text-slate-700">${{ number_format($this->original_price, 2) }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <label class="text-slate-600">Precio del combo: *</label>
                                    <div class="relative w-32">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-500">$</span>
                                        <input wire:model.live="combo_price" x-model.number="comboPrice" type="number" step="0.01" min="0" class="w-full pl-7 pr-3 py-1.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-right font-semibold">
                                    </div>
                                </div>
                                @error('combo_price')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                                <div class="flex justify-between items-center mt-3 pt-3 border-t border-slate-200">
                                    <span class="text-green-600 font-medium">Ahorro para el cliente:</span>
                                    <div class="text-right">
                                        <span class="font-bold text-green-600" x-text="'$' + savings.toFixed(2)"></span>
                                        <span class="text-sm text-green-500 ml-1" x-text="'(' + savingsPercent + '%)'"></span>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>

                        {{-- Limits --}}
                        <div>
                            <h4 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Límites del Combo
                            </h4>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Tipo de límite</label>
                                    <select wire:model.live="limit_type" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                        <option value="none">Sin límite (siempre disponible)</option>
                                        <option value="time">Por tiempo (fecha inicio/fin)</option>
                                        <option value="quantity">Por cantidad (máximo de ventas)</option>
                                        <option value="both">Ambos (tiempo y cantidad)</option>
                                    </select>
                                </div>

                                @if(in_array($limit_type, ['time', 'both']))
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 bg-blue-50 rounded-xl">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1">Fecha inicio</label>
                                        <input wire:model="start_date" type="datetime-local" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                        @error('start_date')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1">Fecha fin</label>
                                        <input wire:model="end_date" type="datetime-local" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                        @error('end_date')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                                @endif

                                @if(in_array($limit_type, ['quantity', 'both']))
                                <div class="p-4 bg-purple-50 rounded-xl">
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Cantidad máxima de ventas *</label>
                                    <input wire:model="max_sales" type="number" min="1" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="Ej: 100">
                                    @error('max_sales')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                                    <p class="text-xs text-slate-500 mt-1">El combo se desactivará automáticamente al alcanzar este número</p>
                                </div>
                                @endif
                            </div>
                        </div>

                        {{-- Image --}}
                        <div>
                            <h4 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                Imagen del Combo
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
                                </div>
                                @endif
                                <div class="flex items-center justify-center w-full">
                                    <label class="flex flex-col items-center justify-center w-full h-28 border-2 border-slate-300 border-dashed rounded-xl cursor-pointer bg-slate-50 hover:bg-slate-100 transition-colors">
                                        <div class="flex flex-col items-center justify-center pt-4 pb-5">
                                            <svg class="w-6 h-6 mb-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                                            <p class="text-sm text-slate-500"><span class="font-semibold">Clic para subir</span></p>
                                            <p class="text-xs text-slate-400">JPG, PNG o WebP (máx. 2MB)</p>
                                        </div>
                                        <input wire:model="image" type="file" class="hidden" accept="image/jpeg,image/png,image/webp">
                                    </label>
                                </div>
                                @error('image')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                            </div>
                        </div>

                        {{-- Status --}}
                        <div class="pt-2">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input wire:model="is_active" type="checkbox" class="w-4 h-4 rounded border-slate-300 text-[#ff7261] focus:ring-[#ff7261]">
                                <span class="text-sm text-slate-700">Combo activo</span>
                            </label>
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
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Eliminar Combo</h3>
                    <p class="text-slate-500 mb-6">¿Estás seguro de eliminar este combo? Esta acción no se puede deshacer.</p>
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
</div>

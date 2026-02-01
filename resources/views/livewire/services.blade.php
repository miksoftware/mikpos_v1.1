<div class="p-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Servicios</h1>
            <p class="text-slate-500 text-sm mt-1">Gestiona los servicios de tu negocio</p>
        </div>
        <button wire:click="create" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea] transition shadow-lg shadow-purple-500/25">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Nuevo Servicio
        </button>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-5 gap-4">
            @if($isSuperAdmin)
            <div>
                <select wire:model.live="selectedBranchId" class="w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                    <option value="">Todas las sucursales</option>
                    @foreach($branches as $branch)
                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="{{ $isSuperAdmin ? 'sm:col-span-2' : 'sm:col-span-2' }}">
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Buscar por nombre, SKU..." class="w-full pl-10 pr-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                </div>
            </div>
            <div>
                <select wire:model.live="categoryFilter" class="w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                    <option value="">Todas las categorías</option>
                    @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <select wire:model.live="statusFilter" class="w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                    <option value="">Todos los estados</option>
                    <option value="1">Activos</option>
                    <option value="0">Inactivos</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Servicio</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">SKU</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Categoría</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Precio</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold text-slate-500 uppercase tracking-wider">Estado</th>
                        <th class="px-6 py-4 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($services as $service)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#ff7261]/10 to-[#a855f7]/10 flex items-center justify-center overflow-hidden flex-shrink-0">
                                    @if($service->image)
                                    <img src="{{ Storage::url($service->image) }}" alt="" class="w-full h-full object-cover">
                                    @else
                                    <svg class="w-6 h-6 text-[#a855f7]/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
                                    @endif
                                </div>
                                <div>
                                    <p class="font-medium text-slate-800">{{ $service->name }}</p>
                                    @if($service->description)
                                    <p class="text-xs text-slate-500 truncate max-w-xs">{{ $service->description }}</p>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm font-mono text-slate-600">{{ $service->sku }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-slate-600">{{ $service->category?->name ?? '-' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm font-semibold text-slate-800">${{ number_format($service->sale_price, 2) }}</span>
                            @if($service->tax)
                            <span class="text-xs text-slate-400 block">{{ $service->price_includes_tax ? 'IVA incluido' : '+ IVA' }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            <button wire:click="toggleStatus({{ $service->id }})" class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors {{ $service->is_active ? 'bg-gradient-to-r from-[#ff7261] to-[#a855f7]' : 'bg-slate-200' }}">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform shadow {{ $service->is_active ? 'translate-x-6' : 'translate-x-1' }}"></span>
                            </button>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button wire:click="edit({{ $service->id }})" class="p-2 text-slate-400 hover:text-[#ff7261] hover:bg-[#ff7261]/10 rounded-lg transition" title="Editar">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                                <button wire:click="confirmDelete({{ $service->id }})" class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition" title="Eliminar">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mb-4">
                                    <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <p class="text-slate-500 font-medium">No se encontraron servicios</p>
                                <p class="text-slate-400 text-sm">Crea un nuevo servicio para comenzar</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($services->hasPages())
        <div class="px-6 py-4 border-t border-slate-200">
            {{ $services->links() }}
        </div>
        @endif
    </div>

    <!-- Create/Edit Modal -->
    @if($isModalOpen)
    <div class="relative z-[100]" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="closeModal"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-xl">
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-slate-900">{{ $serviceId ? 'Editar Servicio' : 'Nuevo Servicio' }}</h3>
                        <button wire:click="closeModal" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <form wire:submit="store">
                        <div class="px-6 py-4 space-y-4 max-h-[70vh] overflow-y-auto">
                            <!-- Branch selector for super_admin -->
                            @if($isSuperAdmin)
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Sucursal <span class="text-red-500">*</span></label>
                                <select wire:model="selectedBranchId" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                    <option value="">Seleccionar sucursal...</option>
                                    @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @endif

                            <!-- Name -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Nombre <span class="text-red-500">*</span></label>
                                <input wire:model="name" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="Nombre del servicio">
                                @error('name') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>

                            <!-- Description -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Descripción</label>
                                <textarea wire:model="description" rows="2" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="Descripción del servicio"></textarea>
                                @error('description') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>

                            <!-- Category & Tax -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Categoría</label>
                                    <select wire:model="category_id" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                        <option value="">Sin categoría</option>
                                        @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Impuesto</label>
                                    <select wire:model="tax_id" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                        <option value="">Sin impuesto</option>
                                        @foreach($taxes as $tax)
                                        <option value="{{ $tax->id }}">{{ $tax->name }} ({{ $tax->value }}%)</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <!-- Prices -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Costo</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">$</span>
                                        <input wire:model="cost" type="number" step="0.01" min="0" class="w-full pl-8 pr-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                    </div>
                                    @error('cost') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Precio de Venta <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">$</span>
                                        <input wire:model="sale_price" type="number" step="0.01" min="0" class="w-full pl-8 pr-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                    </div>
                                    @error('sale_price') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <!-- Price includes tax -->
                            <div class="flex items-center gap-2">
                                <input wire:model="price_includes_tax" type="checkbox" id="price_includes_tax" class="w-4 h-4 text-[#ff7261] border-slate-300 rounded focus:ring-[#ff7261]">
                                <label for="price_includes_tax" class="text-sm text-slate-700">El precio incluye impuesto</label>
                            </div>

                            <!-- Commission -->
                            <div class="p-4 bg-slate-50 rounded-xl space-y-3">
                                <div class="flex items-center gap-2">
                                    <input wire:model.live="has_commission" type="checkbox" id="has_commission" class="w-4 h-4 text-[#ff7261] border-slate-300 rounded focus:ring-[#ff7261]">
                                    <label for="has_commission" class="text-sm font-medium text-slate-700">Tiene comisión</label>
                                </div>
                                @if($has_commission)
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1">Tipo</label>
                                        <select wire:model="commission_type" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                            <option value="fixed">Valor fijo</option>
                                            <option value="percentage">Porcentaje</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1">Valor</label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">{{ $commission_type === 'percentage' ? '%' : '$' }}</span>
                                            <input wire:model="commission_value" type="number" step="0.01" min="0" class="w-full pl-8 pr-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </div>

                            <!-- Image -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Imagen</label>
                                <div class="flex items-center gap-4">
                                    @if($currentImage && !$image)
                                    <img src="{{ Storage::url($currentImage) }}" alt="" class="w-20 h-20 rounded-xl object-cover">
                                    @elseif($image)
                                    <img src="{{ $image->temporaryUrl() }}" alt="" class="w-20 h-20 rounded-xl object-cover">
                                    @endif
                                    <input wire:model="image" type="file" accept="image/*" class="text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-medium file:bg-[#ff7261]/10 file:text-[#ff7261] hover:file:bg-[#ff7261]/20">
                                </div>
                                @error('image') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>

                            <!-- Active -->
                            <div class="flex items-center gap-2">
                                <input wire:model="is_active" type="checkbox" id="is_active" class="w-4 h-4 text-[#ff7261] border-slate-300 rounded focus:ring-[#ff7261]">
                                <label for="is_active" class="text-sm text-slate-700">Servicio activo</label>
                            </div>
                        </div>
                        <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
                            <button type="button" wire:click="closeModal" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">Cancelar</button>
                            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea]">
                                {{ $serviceId ? 'Actualizar' : 'Guardar' }}
                            </button>
                        </div>
                    </form>
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
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Eliminar Servicio</h3>
                    <p class="text-slate-500 mb-6">¿Estás seguro de que deseas eliminar este servicio? Esta acción no se puede deshacer.</p>
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

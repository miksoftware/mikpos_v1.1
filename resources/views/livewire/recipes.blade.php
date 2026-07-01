<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Recetas / Listas de Materiales</h1>
            <p class="text-slate-500 mt-1">Gestiona las recetas para la producción de productos terminados</p>
        </div>
        <div class="flex items-center gap-2">
            @if(auth()->user()->hasPermission('recipes.create'))
            <button wire:click="create" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-[#ff7261] to-[#a855f7] hover:from-[#e55a4a] hover:to-[#9333ea] text-white text-sm font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-[1.02] transition-all duration-200">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Nueva Receta
            </button>
            @endif
        </div>
    </div>

    {{-- Search --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
        <div class="flex flex-col sm:flex-row gap-4">
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <input wire:model.live.debounce.300ms="search" type="text" class="block w-full pl-10 pr-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 placeholder-slate-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] transition-all sm:text-sm" placeholder="Buscar receta por producto o SKU...">
            </div>
            {{-- Sort dropdown --}}
            <div class="flex items-center gap-2">
                <span class="text-sm text-slate-500">Ordenar:</span>
                <select wire:model.live="sortBy" class="px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] sm:text-sm">
                    <option value="created_at">Fecha de creación</option>
                    <option value="yield_quantity">Rendimiento</option>
                </select>
                <button wire:click="sortByColumn('{{ $sortBy }}')" class="p-2.5 border border-slate-200 rounded-xl bg-slate-50 hover:bg-slate-100 transition-colors">
                    @if($sortDirection === 'asc')
                    <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"></path></svg>
                    @else
                    <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h9m5-4v12m0 0l-4-4m4 4l4-4"></path></svg>
                    @endif
                </button>
            </div>
        </div>
    </div>

    {{-- Recipes Table --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Producto (A Fabricar)</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Rendimiento</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold text-slate-500 uppercase">Insumos (Cant.)</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold text-slate-500 uppercase">Estado</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($recipes as $recipe)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div>
                                    <div class="font-medium text-slate-900">{{ $recipe->product->name ?? 'Producto Eliminado' }}</div>
                                    <div class="text-sm text-slate-500 flex gap-2">
                                        <span>SKU: {{ $recipe->product->sku ?? '-' }}</span>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium bg-indigo-100 text-indigo-700">
                                {{ number_format($recipe->yield_quantity, 2) }} {{ $recipe->product->unit->abbreviation ?? 'Und' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium bg-slate-100 text-slate-700">
                                {{ $recipe->ingredients->count() }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if(auth()->user()->hasPermission('recipes.edit'))
                            <button wire:click="toggleStatus({{ $recipe->id }})" class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 {{ $recipe->is_active ? 'bg-[#ff7261]' : 'bg-slate-200' }}">
                                <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow transition duration-200 {{ $recipe->is_active ? 'translate-x-4' : 'translate-x-0' }}"></span>
                            </button>
                            @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $recipe->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                {{ $recipe->is_active ? 'Activa' : 'Inactiva' }}
                            </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                @if(auth()->user()->hasPermission('recipes.edit'))
                                <button wire:click="edit({{ $recipe->id }})" class="p-1.5 text-slate-400 hover:text-[#ff7261] hover:bg-orange-50 rounded-lg transition-colors" title="Editar receta">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </button>
                                @endif
                                @if(auth()->user()->hasPermission('recipes.delete'))
                                <button wire:click="confirmDelete({{ $recipe->id }})" class="p-1.5 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Eliminar receta">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                            <div class="flex flex-col items-center">
                                <svg class="w-12 h-12 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                <p>No hay recetas registradas</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($recipes->hasPages())
        <div class="px-6 py-4 border-t border-slate-200">
            {{ $recipes->links() }}
        </div>
        @endif
    </div>

    {{-- Create/Edit Modal --}}
    @if($isModalOpen)
    <div class="relative z-[100]">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm transition-opacity" wire:click="$set('isModalOpen', false)"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-3xl bg-white rounded-2xl shadow-xl">
                    <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center">
                        <h3 class="text-lg font-bold text-slate-900">{{ $recipeId ? 'Editar' : 'Nueva' }} Receta</h3>
                        <button wire:click="$set('isModalOpen', false)" class="text-slate-400 hover:text-slate-500">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    <div class="px-6 py-4 space-y-6 max-h-[70vh] overflow-y-auto">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-slate-700 mb-1">Producto Terminado a Fabricar *</label>
                                <select wire:model="product_id" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                    <option value="">Seleccionar producto...</option>
                                    @foreach($finishedProducts as $prod)
                                    <option value="{{ $prod->id }}">{{ $prod->name }} (SKU: {{ $prod->sku }})</option>
                                    @endforeach
                                </select>
                                @error('product_id')<span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>@enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Rendimiento (Cantidad Resultante) *</label>
                                <input wire:model="yield_quantity" type="number" step="0.001" min="0.001" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                @error('yield_quantity')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-slate-700 mb-1">Instrucciones de Fabricación</label>
                                <textarea wire:model="instructions" rows="2" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="Opcional. Instrucciones paso a paso."></textarea>
                                @error('instructions')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                            </div>
                        </div>

                        <div class="border-t border-slate-200 pt-6">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-sm font-semibold text-slate-800 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                    Insumos Requeridos (BOM)
                                </h4>
                                <button type="button" wire:click="addIngredient" class="inline-flex items-center text-sm font-medium text-[#ff7261] hover:text-[#e55a4a]">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                                    Agregar Insumo
                                </button>
                            </div>
                            
                            @error('ingredients')
                                <div class="p-3 bg-red-50 text-red-600 rounded-xl mb-4 text-sm">{{ $message }}</div>
                            @enderror

                            <div class="space-y-3">
                                @foreach($ingredients as $index => $ingredient)
                                <div class="flex items-start gap-3 p-3 bg-slate-50 border border-slate-200 rounded-xl relative">
                                    <div class="flex-1">
                                        <select wire:model.live="ingredients.{{ $index }}.product_id" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                            <option value="">Seleccionar insumo...</option>
                                            @foreach($ingredientProducts as $prod)
                                            <option value="{{ $prod->id }}">{{ $prod->name }} (SKU: {{ $prod->sku }})</option>
                                            @endforeach
                                        </select>
                                        @error('ingredients.'.$index.'.product_id')<span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>@enderror
                                    </div>
                                    <div class="w-40 relative">
                                        <input wire:model="ingredients.{{ $index }}.quantity" type="number" step="0.001" min="0.001" placeholder="Cant." class="w-full pl-3 pr-12 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-slate-500 text-sm font-medium">
                                            @php
                                                $selectedProdId = $ingredient['product_id'];
                                                $unitAbbr = '';
                                                if($selectedProdId) {
                                                    $p = $ingredientProducts->firstWhere('id', $selectedProdId);
                                                    if($p && $p->unit) $unitAbbr = $p->unit->abbreviation;
                                                }
                                            @endphp
                                            {{ $unitAbbr }}
                                        </div>
                                        @error('ingredients.'.$index.'.quantity')<span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>@enderror
                                    </div>
                                    <button type="button" wire:click="removeIngredient({{ $index }})" class="p-2 text-slate-400 hover:text-red-500 transition-colors mt-0.5">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </div>
                                @endforeach

                                @if(empty($ingredients))
                                <div class="text-center py-6 text-slate-500 text-sm">
                                    Aún no hay insumos. Agrega uno para componer la receta.
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 rounded-b-2xl flex justify-end gap-3">
                        <button wire:click="$set('isModalOpen', false)" class="px-4 py-2 text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 font-semibold rounded-xl transition-colors">
                            Cancelar
                        </button>
                        <button wire:click="store" class="px-4 py-2 text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] hover:from-[#e55a4a] hover:to-[#9333ea] font-semibold rounded-xl shadow-md hover:shadow-lg transition-all flex items-center">
                            <svg wire:loading wire:target="store" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span wire:loading.remove wire:target="store">Guardar</span>
                            <span wire:loading wire:target="store">Guardando...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Delete Modal --}}
    @if($isDeleteModalOpen)
    <div class="relative z-[100]">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm transition-opacity" wire:click="$set('isDeleteModalOpen', false)"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-md bg-white rounded-2xl shadow-xl p-6 text-center">
                    <div class="w-16 h-16 bg-red-100 text-red-500 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-2">¿Eliminar receta?</h3>
                    <p class="text-slate-500 mb-6">Esta acción no se puede deshacer. Los productos que se hayan fabricado previamente con esta receta no se verán afectados.</p>
                    <div class="flex flex-col sm:flex-row gap-3 justify-center">
                        <button wire:click="$set('isDeleteModalOpen', false)" class="px-6 py-2.5 text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 font-semibold rounded-xl transition-colors">
                            Cancelar
                        </button>
                        <button wire:click="delete" class="px-6 py-2.5 text-white bg-red-500 hover:bg-red-600 font-semibold rounded-xl shadow-md hover:shadow-lg transition-all flex items-center justify-center">
                            <svg wire:loading wire:target="delete" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span wire:loading.remove wire:target="delete">Sí, eliminar</span>
                            <span wire:loading wire:target="delete">Eliminando...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

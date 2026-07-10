<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Nueva Orden de Producción</h1>
            <p class="text-slate-500 mt-1">Fabrica productos terminados descontando insumos automáticamente</p>
        </div>
        <a href="{{ route('production.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-semibold rounded-xl shadow-sm transition-colors">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Volver al Historial
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        {{-- Form & Cart --}}
        <div class="lg:col-span-5 space-y-6">
            {{-- Add Item to Cart --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-[#ff7261]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                    Agregar a Producción
                </h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Receta a Fabricar *</label>
                        <select wire:model.live="selectedRecipeId" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                            <option value="">Seleccione una receta...</option>
                            @foreach($recipes as $recipe)
                            <option value="{{ $recipe->id }}">{{ $recipe->product->name }} (Rend. Base: {{ $recipe->yield_quantity }})</option>
                            @endforeach
                        </select>
                    </div>

                    @if($selectedRecipeId)
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Cantidad *</label>
                            <input wire:model.live.debounce.500ms="quantity_to_produce" type="number" step="0.01" min="0.01" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                        </div>
                        @if($recipeRequiresLocation)
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Ubicación Destino *</label>
                            <select wire:model="selectedLocationId" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                <option value="">Seleccione ubicación...</option>
                                @foreach($locations as $loc)
                                <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                    </div>

                    <button wire:click="addToCart" class="w-full py-2.5 px-4 bg-slate-900 hover:bg-slate-800 text-white font-semibold rounded-xl transition-colors">
                        Agregar Receta
                    </button>
                    @endif
                </div>
            </div>

            {{-- Cart List --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                    <h3 class="text-md font-bold text-slate-800">Lista de Producción</h3>
                    <span class="bg-slate-200 text-slate-700 text-xs font-bold px-2.5 py-1 rounded-full">{{ count($cart) }}</span>
                </div>
                
                @if(count($cart) > 0)
                <div class="divide-y divide-slate-100">
                    @foreach($cart as $item)
                    <div class="p-4 flex items-center justify-between hover:bg-slate-50 transition-colors">
                        <div>
                            <h4 class="font-medium text-slate-900">{{ $item['product_name'] }}</h4>
                            <p class="text-xs text-slate-500 mt-1">
                                Cantidad: <strong>{{ $item['quantity'] }}</strong> 
                                @if($item['location_name'])
                                | Ubicación: <span class="text-slate-700">{{ $item['location_name'] }}</span>
                                @endif
                            </p>
                            <p class="text-xs font-medium text-[#ff7261] mt-1">Costo: ${{ number_format($item['estimated_cost'], 2) }}</p>
                        </div>
                        <button wire:click="removeFromCart('{{ $item['id'] }}')" class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </button>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="p-8 text-center text-slate-500 text-sm">
                    No has agregado ninguna receta a la producción.
                </div>
                @endif
            </div>
            
            @if(count($cart) > 0)
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4">Resumen de Orden</h3>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Notas (Opcional)</label>
                    <textarea wire:model="notes" rows="2" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="Observaciones generales para toda la orden"></textarea>
                </div>

                <div class="space-y-3 mt-4 mb-6 pt-4 border-t border-slate-100">
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">Costo Total Estimado</span>
                        <span class="font-semibold text-slate-800">${{ number_format($globalEstimatedCost, 2) }}</span>
                    </div>
                </div>

                <button wire:click="produce" wire:loading.attr="disabled" class="w-full py-3 px-4 flex justify-center items-center text-white font-semibold rounded-xl shadow-md transition-all {{ $canProduceAll ? 'bg-gradient-to-r from-[#ff7261] to-[#a855f7] hover:from-[#e55a4a] hover:to-[#9333ea] hover:shadow-lg transform hover:scale-[1.02]' : 'bg-slate-300 cursor-not-allowed' }}" {{ !$canProduceAll ? 'disabled' : '' }}>
                    <svg wire:loading wire:target="produce" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <span wire:loading.remove wire:target="produce">Fabricar Todo</span>
                    <span wire:loading wire:target="produce">Procesando...</span>
                </button>
                @if(!$canProduceAll)
                <p class="text-red-500 text-xs text-center mt-3 font-medium flex items-center justify-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    Falta inventario de insumos
                </p>
                @endif
            </div>
            @endif
        </div>

        {{-- Global BOM Details --}}
        <div class="lg:col-span-7">
            @if(count($cart) > 0)
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-slate-800">Insumos Totales Requeridos (BOM Global)</h3>
                    @if($canProduceAll)
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Stock Suficiente
                    </span>
                    @else
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        Stock Insuficiente
                    </span>
                    @endif
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-white">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Insumo</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-slate-500 uppercase">Cant. Requerida</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-slate-500 uppercase">Stock Actual</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-slate-500 uppercase">Estado</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Costo Est.</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @foreach($globalRequiredIngredients as $ing)
                            <tr class="hover:bg-slate-50/50 transition-colors {{ !$ing['is_enough'] ? 'bg-red-50/30' : '' }}">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-slate-900">{{ $ing['name'] }}</div>
                                    <div class="text-xs text-slate-500">SKU: {{ $ing['sku'] ?? 'N/A' }}</div>
                                </td>
                                <td class="px-6 py-4 text-center font-medium text-slate-800">
                                    {{ number_format($ing['required_quantity'], 2) }}
                                </td>
                                <td class="px-6 py-4 text-center text-slate-600">
                                    {{ number_format($ing['available_stock'], 2) }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @if($ing['is_enough'])
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">OK</span>
                                    @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">Faltan {{ number_format($ing['required_quantity'] - $ing['available_stock'], 2) }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right text-slate-700">
                                    ${{ number_format($ing['unit_cost'] * $ing['required_quantity'], 2) }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @else
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-12 text-center h-full flex flex-col justify-center items-center">
                <svg class="w-16 h-16 text-slate-200 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                <h3 class="text-xl font-medium text-slate-600 mb-2">Orden Vacía</h3>
                <p class="text-slate-400 max-w-sm mx-auto">Agrega recetas al carrito para visualizar los insumos totales requeridos y su disponibilidad.</p>
            </div>
            @endif
        </div>
    </div>
</div>

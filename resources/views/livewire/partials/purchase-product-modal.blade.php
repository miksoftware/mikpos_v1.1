{{-- Product Create Modal (from Purchases) --}}
@if($isQuickCreateOpen)
<div class="relative z-[100]" role="dialog" aria-modal="true" x-data="{
    purchasePrice: @entangle('quickPurchasePrice'),
    salePrice: @entangle('quickSalePrice'),
    get margin() {
        if (!this.purchasePrice || this.purchasePrice <= 0) return null;
        return ((this.salePrice - this.purchasePrice) / this.purchasePrice * 100).toFixed(1);
    },
    get hasNegativeMargin() {
        return this.salePrice < this.purchasePrice;
    }
}">
    <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="$set('isQuickCreateOpen', false)"></div>
    <div class="fixed inset-0 z-[101] overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-xl">
                {{-- Header --}}
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-r from-[#ff7261] to-[#a855f7] flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900">Nuevo Producto</h3>
                    </div>
                    <button wire:click="$set('isQuickCreateOpen', false)" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                {{-- Content --}}
                <div class="px-6 py-4 space-y-6 max-h-[70vh] overflow-y-auto">
                    {{-- Información Básica --}}
                    <div>
                        <h4 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                            Información Básica
                        </h4>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Nombre *</label>
                                <input wire:model="quickName" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="Nombre del producto">
                                @error('quickName') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Descripción</label>
                                <textarea wire:model="quickDescription" rows="2" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="Descripción del producto"></textarea>
                            </div>
                        </div>
                    </div>

                    {{-- Clasificación --}}
                    <div>
                        <h4 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                            Clasificación
                        </h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Categoría *</label>
                                <select wire:model.live="quickCategoryId" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                    <option value="">Seleccionar categoría...</option>
                                    @foreach($quickCategories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                                @error('quickCategoryId') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Subcategoría</label>
                                <select wire:model="quickSubcategoryId" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" {{ empty($quickSubcategories) || count($quickSubcategories) === 0 ? 'disabled' : '' }}>
                                    <option value="">Seleccionar subcategoría...</option>
                                    @foreach($quickSubcategories as $subcat)
                                    <option value="{{ $subcat->id }}">{{ $subcat->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Marca</label>
                                <select wire:model="quickBrandId" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                    <option value="">Seleccionar marca...</option>
                                    @foreach($quickBrands as $brand)
                                    <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Unidad Base *</label>
                                <select wire:model="quickUnitId" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                    <option value="">Seleccionar unidad...</option>
                                    @foreach($quickUnits as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->abbreviation }})</option>
                                    @endforeach
                                </select>
                                @error('quickUnitId') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Código de Barras --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Código de Barras</label>
                        <input wire:model="quickBarcode" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="Ej: 7701234567890">
                    </div>

                    {{-- Precios --}}
                    <div>
                        <h4 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Precios
                        </h4>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Precio Compra *</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">$</span>
                                    <input wire:model.live="quickPurchasePrice" x-model.number="purchasePrice" type="number" step="0.01" min="0" class="w-full pl-8 pr-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="0.00">
                                </div>
                                @error('quickPurchasePrice') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Precio Venta *</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">$</span>
                                    <input wire:model.live="quickSalePrice" x-model.number="salePrice" type="number" step="0.01" min="0" class="w-full pl-8 pr-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="0.00">
                                </div>
                                @error('quickSalePrice') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Precio Especial</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-green-600">$</span>
                                    <input wire:model="quickSpecialPrice" type="number" step="0.01" min="0" class="w-full pl-8 pr-3 py-2 border border-green-300 rounded-xl focus:ring-2 focus:ring-green-500/50 focus:border-green-500 bg-green-50" placeholder="0.00">
                                </div>
                            </div>
                        </div>
                        <div class="mt-3 flex flex-wrap items-center gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input wire:model="quickPriceIncludesTax" type="checkbox" class="w-4 h-4 rounded border-slate-300 text-[#ff7261] focus:ring-[#ff7261]">
                                <span class="text-sm text-slate-700">Precio incluye impuesto</span>
                            </label>
                            <div class="flex items-center gap-2">
                                <span class="text-sm text-slate-500">Margen:</span>
                                <template x-if="margin !== null">
                                    <span class="text-sm font-semibold" :class="{ 'text-green-600': margin >= 20, 'text-amber-600': margin >= 10 && margin < 20, 'text-red-500': margin < 10 }" x-text="margin + '%'"></span>
                                </template>
                                <template x-if="margin === null">
                                    <span class="text-sm text-slate-400">-</span>
                                </template>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-sm text-slate-500">Ganancia:</span>
                                <template x-if="purchasePrice > 0">
                                    <span class="text-sm font-semibold" :class="{ 'text-green-600': (salePrice - purchasePrice) > 0, 'text-red-500': (salePrice - purchasePrice) <= 0 }" x-text="'$' + (salePrice - purchasePrice).toFixed(2)"></span>
                                </template>
                                <template x-if="!purchasePrice || purchasePrice <= 0">
                                    <span class="text-sm text-slate-400">-</span>
                                </template>
                            </div>
                        </div>
                        <div x-show="hasNegativeMargin" x-transition class="mt-3 p-3 bg-red-50 border border-red-200 rounded-xl flex items-center gap-2">
                            <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            <span class="text-sm text-red-700">El precio de venta es menor al precio de compra</span>
                        </div>
                    </div>

                    {{-- Impuesto e Inventario --}}
                    <div>
                        <h4 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                            Impuesto e Inventario
                        </h4>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Impuesto</label>
                                <select wire:model="quickTaxId" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                    <option value="">Sin impuesto</option>
                                    @foreach($quickTaxes as $t)
                                    <option value="{{ $t->id }}">{{ $t->name }} ({{ $t->value }}%)</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Stock Mínimo</label>
                                <input wire:model="quickMinStock" type="number" min="0" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="0">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Stock Máximo</label>
                                <input wire:model="quickMaxStock" type="number" min="0" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="Opcional">
                            </div>
                        </div>
                    </div>

                    {{-- Comisión --}}
                    <div>
                        <label class="flex items-center gap-2 cursor-pointer mb-3">
                            <input wire:model.live="quickHasCommission" type="checkbox" class="w-4 h-4 rounded border-slate-300 text-[#ff7261] focus:ring-[#ff7261]">
                            <span class="text-sm font-medium text-slate-700">Tiene comisión por venta</span>
                        </label>
                        @if($quickHasCommission)
                        <div class="grid grid-cols-2 gap-4 pl-6">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Tipo</label>
                                <select wire:model="quickCommissionType" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                    <option value="percentage">Porcentaje (%)</option>
                                    <option value="fixed">Valor Fijo ($)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Valor</label>
                                <input wire:model="quickCommissionValue" type="number" step="0.01" min="0" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="0">
                            </div>
                        </div>
                        @endif
                    </div>

                    {{-- Imagen --}}
                    <div>
                        <h4 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            Imagen
                        </h4>
                        @if($quickImage)
                        <div class="flex items-center gap-4 mb-3">
                            <div class="relative">
                                <img src="{{ $quickImage->temporaryUrl() }}" alt="Preview" class="w-24 h-24 rounded-xl object-cover border border-slate-200">
                                <button type="button" wire:click="$set('quickImage', null)" class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center hover:bg-red-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                            </div>
                        </div>
                        @endif
                        <label class="flex flex-col items-center justify-center w-full h-24 border-2 border-slate-300 border-dashed rounded-xl cursor-pointer bg-slate-50 hover:bg-slate-100 transition-colors">
                            <div class="flex flex-col items-center justify-center py-3">
                                <svg class="w-6 h-6 mb-1 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                                <p class="text-xs text-slate-500"><span class="font-semibold">Clic para subir</span> o arrastra</p>
                                <p class="text-xs text-slate-400">JPG, PNG o WebP (máx. 2MB)</p>
                            </div>
                            <input wire:model="quickImage" type="file" class="hidden" accept="image/jpeg,image/png,image/webp">
                        </label>
                        @error('quickImage') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        <div wire:loading wire:target="quickImage" class="text-sm text-slate-500 flex items-center gap-2 mt-2">
                            <svg class="animate-spin h-4 w-4 text-[#ff7261]" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Subiendo imagen...
                        </div>
                    </div>

                    <div class="p-3 bg-blue-50 border border-blue-200 rounded-xl">
                        <p class="text-xs text-blue-600">El producto se creará con stock en 0 y se asignará a la sucursal actual. El SKU se generará automáticamente. Al completar la compra, el stock se actualizará.</p>
                    </div>
                </div>
                {{-- Footer --}}
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3 rounded-b-2xl">
                    <button wire:click="$set('isQuickCreateOpen', false)" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">Cancelar</button>
                    <button wire:click="storeQuickProduct" class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea]">
                        <span wire:loading.remove wire:target="storeQuickProduct">Crear y Agregar</span>
                        <span wire:loading wire:target="storeQuickProduct">Creando...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

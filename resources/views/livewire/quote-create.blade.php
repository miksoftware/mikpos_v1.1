<div class="h-screen flex flex-col bg-slate-100"
    x-data="{
        showCustomerSearch: false,
        showHelpModal: false,
    }"
    x-on:keydown.window.f7.prevent="showCustomerSearch = true; $nextTick(() => document.getElementById('customer-search-input')?.focus())"
    x-on:keydown.window.f4.prevent="$wire.openGlobalDiscountModal()"
    x-on:keydown.window.f6.prevent="$wire.togglePriceOverride()"
    x-on:close-customer-modal.window="showCustomerSearch = false"
    x-on:focus-product-search.window="$nextTick(() => document.getElementById('product-search-input')?.focus())"
    x-on:focus-barcode-search.window="$nextTick(() => document.getElementById('barcode-search-input')?.focus())"
    x-on:print-quote.window="(e) => { window.open('/quote-receipt/' + e.detail.quoteId, '_blank'); }"
>
    <!-- Header -->
    <header class="h-14 bg-gradient-to-r from-[#1a1225] to-[#2d1f3d] flex items-center justify-between px-4 text-white shadow-md flex-shrink-0">
        <div class="flex items-center gap-4">
            <a href="{{ route('quotes') }}" class="flex items-center gap-2 px-3 py-1.5 rounded-lg hover:bg-white/10 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                <span class="text-sm font-medium">Salir</span>
            </a>
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-[#ff7261] to-[#a855f7] flex items-center justify-center text-sm font-bold">
                    {{ strtoupper(substr(auth()->user()->branch?->name ?? 'C', 0, 1)) }}
                </div>
                <div>
                    <p class="text-sm font-bold leading-tight">Nueva Cotización</p>
                    <p class="text-xs text-slate-300 leading-tight">{{ auth()->user()->branch?->name ?? 'Sin sucursal' }}</p>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <span class="px-2 py-1 bg-orange-500/20 text-orange-300 rounded-md text-xs font-bold border border-orange-500/30">
                COTIZACIÓN
            </span>
            <button @click="showHelpModal = true" class="p-1.5 text-slate-300 hover:bg-white/10 rounded-lg" title="Ayuda">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </button>
            <p class="text-sm font-mono">{{ now()->format('d/m/Y H:i') }}</p>
            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-[#ff7261] to-[#a855f7] flex items-center justify-center text-sm font-bold">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>
        </div>
    </header>

    <!-- Branch selector for super_admin -->
    @if(auth()->user()->isSuperAdmin() && count($availableBranches) > 0)
    <div class="bg-amber-50 border-b border-amber-200 px-4 py-2 flex items-center gap-3">
        <svg class="w-5 h-5 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
        <span class="text-sm font-medium text-amber-800">Sucursal:</span>
        <select wire:model.live="branchId" class="px-3 py-1.5 border border-amber-300 rounded-lg text-sm bg-white focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
            <option value="">Seleccionar sucursal...</option>
            @foreach($availableBranches as $branch)
                <option value="{{ $branch['id'] }}">{{ $branch['name'] }}</option>
            @endforeach
        </select>
        @if(!$branchId)
            <span class="text-xs text-amber-700">Debes seleccionar una sucursal para continuar</span>
        @endif
    </div>
    @endif

    <div class="flex-1 flex overflow-hidden">
        <!-- Left Panel - Cart -->
        <div class="w-1/2 bg-white flex flex-col border-r border-slate-200">
            <!-- Customer Section -->
            <div class="p-4 border-b border-slate-200">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold text-slate-500 uppercase">Cliente</span>
                    <button @click="showCustomerSearch = true; $nextTick(() => document.getElementById('customer-search-input')?.focus())"
                        class="text-xs text-[#a855f7] font-semibold hover:text-[#9333ea]">
                        F7 - Cambiar
                    </button>
                </div>
                <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-[#ff7261] to-[#a855f7] flex items-center justify-center text-white font-bold flex-shrink-0">
                        @if($selectedCustomer)
                            {{ strtoupper(substr($selectedCustomer->customer_type === 'juridico' ? $selectedCustomer->business_name : $selectedCustomer->first_name, 0, 1)) }}
                        @else
                            ?
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        @if($selectedCustomer)
                            <p class="font-semibold text-slate-800 truncate">{{ $selectedCustomer->customer_type === 'juridico' ? $selectedCustomer->business_name : $selectedCustomer->first_name . ' ' . $selectedCustomer->last_name }}</p>
                            <p class="text-xs text-slate-500 truncate">{{ $selectedCustomer->document_number ?? 'Sin documento' }}</p>
                        @else
                            <p class="font-semibold text-slate-500">Sin cliente seleccionado</p>
                            <p class="text-xs text-slate-400">Pulsa F7 para buscar</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Barcode Scanner -->
            <div class="p-4 border-b border-slate-200">
                <div class="relative" x-data="{ value: @entangle('barcodeSearch') }">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6v12m4-12v12m4-12v12m4-12v12m4-12v12"></path></svg>
                    </div>
                    <input
                        id="barcode-search-input"
                        x-model="value"
                        wire:model="barcodeSearch"
                        @keydown.enter.prevent="$wire.searchByBarcode()"
                        x-on:input.debounce.300ms="if (value && value.length >= 8) { $wire.set('barcodeSearch', value); $wire.searchByBarcode() }"
                        type="text"
                        autofocus
                        class="block w-full pl-10 pr-3 py-3 border-2 border-[#ff7261]/30 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm font-medium"
                        placeholder="Escanear código de barras o presiona Enter para buscar..."
                    >
                </div>
            </div>

            <!-- Cart Items -->
            <div class="flex-1 overflow-y-auto custom-scrollbar p-4 space-y-2">
                @if(empty($cart))
                    <div class="text-center py-12">
                        <div class="w-16 h-16 mx-auto rounded-full bg-slate-100 flex items-center justify-center mb-3">
                            <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                        </div>
                        <p class="text-slate-500 font-medium">Carrito vacío</p>
                        <p class="text-xs text-slate-400 mt-1">Escanea o busca productos para agregar</p>
                    </div>
                @else
                    @foreach($cart as $cartKey => $item)
                    <div wire:key="cart-{{ $cartKey }}" class="bg-slate-50 rounded-xl p-3 border border-slate-200 hover:border-[#ff7261]/40 transition">
                        <div class="flex items-start gap-3">
                            <div class="w-12 h-12 rounded-lg bg-white flex items-center justify-center flex-shrink-0 overflow-hidden border border-slate-200">
                                @if($item['image'])
                                    <img src="{{ Storage::url($item['image']) }}" alt="" class="w-full h-full object-cover">
                                @else
                                    <svg class="w-6 h-6 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="flex-1 min-w-0">
                                        <p class="font-semibold text-sm text-slate-800 truncate">{{ $item['name'] }}</p>
                                        <p class="text-xs text-slate-500">{{ $item['sku'] }}</p>
                                    </div>
                                    <button wire:click="removeFromCart('{{ $cartKey }}')" class="p-1 text-red-400 hover:text-red-600 hover:bg-red-50 rounded">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3"></path></svg>
                                    </button>
                                </div>

                                <div class="flex items-center justify-between mt-2">
                                    <div class="flex items-center gap-1">
                                        <button wire:click="decrementQuantity('{{ $cartKey }}')" class="w-7 h-7 rounded-lg bg-white border border-slate-300 flex items-center justify-center hover:bg-slate-100">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path></svg>
                                        </button>
                                        <input type="text"
                                            value="{{ rtrim(rtrim(number_format($item['quantity'], 3), '0'), '.') }}"
                                            wire:change="updateQuantity('{{ $cartKey }}', $event.target.value)"
                                            class="w-14 text-center border border-slate-300 rounded-lg px-1 py-1 text-sm font-semibold">
                                        <button wire:click="incrementQuantity('{{ $cartKey }}')" class="w-7 h-7 rounded-lg bg-white border border-slate-300 flex items-center justify-center hover:bg-slate-100">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                        </button>
                                    </div>
                                    <div class="text-right">
                                        @if($showPriceOverride && !($item['is_combo'] ?? false))
                                            <input type="number" step="0.01" min="0.01"
                                                value="{{ $item['price'] }}"
                                                wire:change="overrideItemPrice('{{ $cartKey }}', $event.target.value)"
                                                class="w-24 text-right border border-amber-400 rounded-lg px-2 py-1 text-sm font-semibold bg-amber-50">
                                        @else
                                            <p class="text-xs text-slate-500">${{ number_format($item['price'], 2) }} c/u</p>
                                            <p class="font-bold text-slate-800">${{ number_format(($item['subtotal'] - $item['discount_amount']) + $item['tax_amount'], 2) }}</p>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 mt-2">
                                    <button wire:click="openDiscountModal('{{ $cartKey }}')"
                                        class="flex items-center gap-1 px-2 py-1 text-xs font-medium {{ $item['discount_amount'] > 0 ? 'text-orange-700 bg-orange-100' : 'text-slate-600 bg-slate-200' }} rounded hover:bg-orange-200 transition">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"></path></svg>
                                        @if($item['discount_amount'] > 0)
                                            -${{ number_format($item['discount_amount'], 0) }}
                                        @else
                                            Descuento
                                        @endif
                                    </button>
                                    @if(!($item['is_service'] ?? false) && !($item['is_combo'] ?? false) && $item['special_price'])
                                    <button wire:click="toggleSpecialPrice('{{ $cartKey }}')"
                                        class="flex items-center gap-1 px-2 py-1 text-xs font-medium {{ $item['using_special_price'] ?? false ? 'text-emerald-700 bg-emerald-100' : 'text-slate-600 bg-slate-200' }} rounded">
                                        ★ Esp.
                                    </button>
                                    @endif
                                    @if($item['price_overridden'] ?? false)
                                    <button wire:click="resetItemPrice('{{ $cartKey }}')" class="flex items-center gap-1 px-2 py-1 text-xs font-medium text-amber-700 bg-amber-100 rounded">
                                        Restaurar
                                    </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                @endif
            </div>

            <!-- Cart Summary -->
            <div class="border-t border-slate-200 p-4 bg-slate-50 space-y-2">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-slate-600">Subtotal</span>
                    <span class="font-medium text-slate-800">${{ number_format($subtotal, 2) }}</span>
                </div>
                @if($this->discountTotal > 0)
                <div class="flex items-center justify-between text-sm">
                    <span class="text-slate-600">Descuentos items</span>
                    <span class="font-medium text-orange-600">-${{ number_format($this->discountTotal, 2) }}</span>
                </div>
                @endif
                @if($taxTotal > 0)
                <div class="flex items-center justify-between text-sm">
                    <span class="text-slate-600">IVA</span>
                    <span class="font-medium text-slate-800">${{ number_format($taxTotal, 2) }}</span>
                </div>
                @endif
                @if($globalDiscountApplied)
                <div class="flex items-center justify-between text-sm">
                    <span class="text-slate-600 flex items-center gap-2">
                        Desc. factura
                        <button wire:click="removeGlobalDiscount" class="text-red-500 hover:text-red-700">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </span>
                    <span class="font-medium text-orange-600">-${{ number_format($globalDiscountAmount, 2) }}</span>
                </div>
                @endif
                <div class="flex items-center justify-between pt-2 border-t border-slate-300">
                    <span class="text-base font-bold text-slate-800">Total</span>
                    <span class="text-2xl font-bold bg-gradient-to-r from-[#ff7261] to-[#a855f7] bg-clip-text text-transparent">${{ number_format($total, 2) }}</span>
                </div>
                <p class="text-xs text-slate-500 text-right">{{ rtrim(rtrim(number_format($itemCount, 3), '0'), '.') }} items</p>

                <div class="flex gap-2 pt-2">
                    <button wire:click="clearCart" class="flex-1 px-3 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">
                        Limpiar
                    </button>
                    <button wire:click="openSaveModal"
                        @disabled(empty($cart) || !$customerId)
                        class="flex-[2] px-4 py-3 text-base font-bold text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea] disabled:opacity-50 disabled:cursor-not-allowed shadow-lg flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Guardar Cotización
                    </button>
                </div>
            </div>
        </div>

        <!-- Right Panel - Products Grid -->
        <div class="w-1/2 flex flex-col bg-slate-50">
            <!-- Product Search -->
            <div class="p-4 bg-white border-b border-slate-200">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                    <input
                        id="product-search-input"
                        wire:model.live.debounce.300ms="productSearch"
                        type="text"
                        class="block w-full pl-10 pr-3 py-2.5 border border-slate-300 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm"
                        placeholder="Buscar productos por nombre o SKU..."
                    >
                </div>

                @if($categories->count() > 0)
                <div class="flex gap-2 mt-3 overflow-x-auto scrollbar-hide pb-1">
                    <button wire:click="selectCategory(null)"
                        class="px-3 py-1.5 text-xs font-semibold rounded-lg whitespace-nowrap {{ !$selectedCategory ? 'bg-gradient-to-r from-[#ff7261] to-[#a855f7] text-white' : 'bg-white text-slate-600 border border-slate-300 hover:bg-slate-50' }}">
                        Todos
                    </button>
                    @foreach($categories as $cat)
                    <button wire:click="selectCategory({{ $cat->id }})"
                        class="px-3 py-1.5 text-xs font-semibold rounded-lg whitespace-nowrap {{ $selectedCategory == $cat->id ? 'bg-gradient-to-r from-[#ff7261] to-[#a855f7] text-white' : 'bg-white text-slate-600 border border-slate-300 hover:bg-slate-50' }}">
                        {{ $cat->name }}
                    </button>
                    @endforeach
                </div>
                @endif
            </div>

            <!-- Products Grid -->
            <div class="flex-1 overflow-y-auto p-4">
                @if(!$branchId)
                    <div class="text-center py-16">
                        <svg class="w-16 h-16 mx-auto text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z"></path></svg>
                        <p class="text-slate-500 font-medium">Selecciona una sucursal</p>
                        <p class="text-xs text-slate-400 mt-1">Para ver los productos disponibles</p>
                    </div>
                @elseif($sellableItems->isEmpty())
                    <div class="text-center py-16">
                        <p class="text-slate-500 font-medium">No hay productos</p>
                        <p class="text-xs text-slate-400 mt-1">{{ $productSearch ? 'Sin resultados para tu búsqueda' : 'Esta sucursal no tiene productos activos' }}</p>
                    </div>
                @else
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 gap-3">
                        @foreach($sellableItems as $item)
                        <button
                            wire:click="@if($item['type'] === 'service') addServiceToCart({{ $item['id'] }}) @elseif($item['type'] === 'combo') addComboToCart({{ $item['id'] }}) @else addToCart({{ $item['id'] }}{{ $item['child_id'] ? ', ' . $item['child_id'] : '' }}) @endif"
                            class="bg-white rounded-xl border border-slate-200 hover:border-[#ff7261] hover:shadow-md transition overflow-hidden text-left relative group">
                            <div class="absolute top-1 right-1 z-10">
                                <span class="px-2 py-0.5 bg-gradient-to-r from-[#ff7261] to-[#a855f7] text-white text-xs font-bold rounded-md shadow">
                                    ${{ number_format($item['price'], 0) }}
                                </span>
                            </div>
                            <div class="absolute top-1 left-1 z-10 flex flex-col gap-1">
                                @if($item['type'] === 'service')
                                    <span class="px-1.5 py-0.5 bg-indigo-500 text-white text-[10px] font-bold rounded">Serv.</span>
                                @elseif($item['type'] === 'combo')
                                    <span class="px-1.5 py-0.5 bg-purple-500 text-white text-[10px] font-bold rounded">Combo</span>
                                @elseif($item['type'] === 'child')
                                    <span class="px-1.5 py-0.5 bg-blue-500 text-white text-[10px] font-bold rounded">Var.</span>
                                @elseif(isset($item['has_variants']) && $item['has_variants'])
                                    <span class="px-1.5 py-0.5 bg-purple-500 text-white text-[10px] font-bold rounded">{{ $item['variant_count'] }} var.</span>
                                @endif
                            </div>
                            <div class="aspect-square bg-slate-100 flex items-center justify-center overflow-hidden">
                                @if($item['image'])
                                    <img src="{{ Storage::url($item['image']) }}" alt="" class="w-full h-full object-cover">
                                @else
                                    <svg class="w-12 h-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                @endif
                            </div>
                            <div class="p-2">
                                <p class="text-xs font-semibold text-slate-800 truncate">{{ $item['name'] }}</p>
                                <div class="flex items-center justify-between mt-1">
                                    <p class="text-[10px] text-slate-500 truncate">{{ $item['brand'] ?? $item['sku'] }}</p>
                                    @if(!is_null($item['stock'] ?? null) && ($item['type'] === 'product' || $item['type'] === 'child'))
                                        <p class="text-[10px] {{ $item['stock'] > 0 ? 'text-emerald-600' : 'text-red-600' }} font-semibold flex-shrink-0">
                                            Stock: {{ rtrim(rtrim(number_format($item['stock'], 3), '0'), '.') }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </button>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Customer Search/Create Modal -->
    <div x-show="showCustomerSearch" x-cloak x-transition.opacity class="relative z-[100]" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" @click="showCustomerSearch = false"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-xl">
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-slate-900">{{ $showCreateCustomer ? 'Crear Cliente' : 'Buscar Cliente' }}</h3>
                        <button @click="showCustomerSearch = false; $wire.closeCreateCustomer()" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    @if(!$showCreateCustomer)
                    <div class="p-6 space-y-4">
                        <div class="flex items-center gap-2">
                            <div class="relative flex-1">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                </div>
                                <input
                                    id="customer-search-input"
                                    wire:model.live.debounce.300ms="customerSearch"
                                    type="text"
                                    class="block w-full pl-10 pr-3 py-2.5 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]"
                                    placeholder="Nombre, documento, razón social..."
                                >
                            </div>
                            <button wire:click="openCreateCustomer" class="px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea]">
                                + Nuevo
                            </button>
                        </div>

                        <div class="space-y-2 max-h-96 overflow-y-auto">
                            @if(strlen(trim($customerSearch)) >= 2 && $customers->count() === 0)
                                <p class="text-center text-slate-500 py-4 text-sm">No se encontraron clientes</p>
                            @elseif(strlen(trim($customerSearch)) < 2)
                                <p class="text-center text-slate-400 py-4 text-sm">Escribe al menos 2 caracteres</p>
                            @endif
                            @foreach($customers as $c)
                            <button wire:click="selectCustomer({{ $c->id }}); $dispatch('close-customer-modal')"
                                class="w-full text-left p-3 hover:bg-slate-50 rounded-lg border border-slate-200 transition flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-[#ff7261] to-[#a855f7] flex items-center justify-center text-white font-bold flex-shrink-0">
                                    {{ strtoupper(substr($c->customer_type === 'juridico' ? $c->business_name : $c->first_name, 0, 1)) }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-slate-800 truncate">{{ $c->customer_type === 'juridico' ? $c->business_name : trim($c->first_name . ' ' . $c->last_name) }}</p>
                                    <p class="text-xs text-slate-500">{{ $c->document_number ?? 'Sin doc' }} {{ $c->phone ? '· ' . $c->phone : '' }}</p>
                                </div>
                            </button>
                            @endforeach
                        </div>
                    </div>
                    @else
                    <!-- Create customer form -->
                    <div class="p-6 space-y-4 max-h-[80vh] overflow-y-auto">
                        <div class="grid grid-cols-2 gap-3">
                            <button wire:click="$set('newCustomerType', 'natural')"
                                class="p-3 rounded-xl border-2 {{ $newCustomerType === 'natural' ? 'border-[#ff7261] bg-orange-50' : 'border-slate-200 hover:border-orange-300' }} transition-all">
                                <span class="font-medium text-sm">Persona Natural</span>
                            </button>
                            <button wire:click="$set('newCustomerType', 'juridico')"
                                class="p-3 rounded-xl border-2 {{ $newCustomerType === 'juridico' ? 'border-[#ff7261] bg-orange-50' : 'border-slate-200 hover:border-orange-300' }} transition-all">
                                <span class="font-medium text-sm">Persona Jurídica</span>
                            </button>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Tipo doc.</label>
                                <select wire:model="newCustomerDocumentType" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                    <option value="">Seleccionar...</option>
                                    @foreach($taxDocuments as $td)
                                        <option value="{{ $td->id }}">{{ $td->abbreviation }} - {{ $td->description }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Documento *</label>
                                <input type="text" wire:model="newCustomerDocument" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                            </div>
                        </div>

                        @if($newCustomerType === 'natural')
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Nombre *</label>
                                <input type="text" wire:model="newCustomerFirstName" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Apellido</label>
                                <input type="text" wire:model="newCustomerLastName" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                            </div>
                        </div>
                        @else
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Razón social *</label>
                            <input type="text" wire:model="newCustomerBusinessName" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                        </div>
                        @endif

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Teléfono</label>
                                <input type="text" wire:model="newCustomerPhone" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                                <input type="email" wire:model="newCustomerEmail" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Departamento *</label>
                                <select wire:model.live="newCustomerDepartmentId" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                    <option value="">Seleccionar...</option>
                                    @foreach($departments as $d)
                                        <option value="{{ $d->id }}">{{ $d->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Municipio *</label>
                                <select wire:model="newCustomerMunicipalityId" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                    <option value="">Seleccionar...</option>
                                    @foreach($newCustomerMunicipalities as $m)
                                        <option value="{{ $m['id'] }}">{{ $m['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
                            <button wire:click="closeCreateCustomer" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">
                                Cancelar
                            </button>
                            <button wire:click="saveNewCustomer" class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea]">
                                Guardar Cliente
                            </button>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Variant Selection Modal -->
    @if($showVariantModal && $variantProduct)
    <div class="relative z-[100]" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="closeVariantModal"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-xl">
                    <div class="px-6 py-4 border-b border-slate-200">
                        <h3 class="text-lg font-bold text-slate-900">Seleccionar Variante</h3>
                        <p class="text-sm text-slate-500">{{ $variantProduct['name'] }}</p>
                    </div>
                    <div class="p-6 space-y-2 max-h-96 overflow-y-auto">
                        <button wire:click="selectVariant(null)" class="w-full text-left p-3 hover:bg-slate-50 rounded-lg border border-slate-200 flex items-center gap-3">
                            <div class="flex-1">
                                <p class="font-semibold text-sm">Producto base</p>
                                <p class="text-xs text-slate-500">{{ $variantProduct['sku'] }}</p>
                            </div>
                            <span class="font-bold text-[#ff7261]">${{ number_format($variantProduct['sale_price'], 0) }}</span>
                        </button>
                        @foreach($variantOptions as $variant)
                        <button wire:click="selectVariant({{ $variant['id'] }})" class="w-full text-left p-3 hover:bg-slate-50 rounded-lg border border-slate-200 flex items-center gap-3">
                            <div class="flex-1">
                                <p class="font-semibold text-sm">{{ $variant['name'] }}</p>
                                <p class="text-xs text-slate-500">{{ $variant['sku'] }}</p>
                            </div>
                            <span class="font-bold text-[#ff7261]">${{ number_format($variant['sale_price'], 0) }}</span>
                        </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Item Discount Modal -->
    @if($showDiscountModal)
    <div class="relative z-[100]" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="closeDiscountModal"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-md bg-white rounded-2xl shadow-xl">
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-slate-900">Aplicar Descuento</h3>
                        <button wire:click="closeDiscountModal" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                    <div class="px-6 py-4 space-y-4">
                        <div class="grid grid-cols-2 gap-3">
                            <button wire:click="$set('discountType', 'percentage')"
                                class="p-3 rounded-xl border-2 {{ $discountType === 'percentage' ? 'border-[#ff7261] bg-orange-50' : 'border-slate-200' }}">
                                <span class="font-medium text-sm">Porcentaje (%)</span>
                            </button>
                            <button wire:click="$set('discountType', 'fixed')"
                                class="p-3 rounded-xl border-2 {{ $discountType === 'fixed' ? 'border-[#ff7261] bg-orange-50' : 'border-slate-200' }}">
                                <span class="font-medium text-sm">Valor fijo ($)</span>
                            </button>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Valor</label>
                            <input type="number" step="0.01" wire:model="discountValue" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Razón (opcional)</label>
                            <input type="text" wire:model="discountReason" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
                        <button wire:click="closeDiscountModal" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl">Cancelar</button>
                        <button wire:click="applyDiscount" class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl">Aplicar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Global Discount Modal -->
    @if($showGlobalDiscountModal)
    <div class="relative z-[100]" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="closeGlobalDiscountModal"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-md bg-white rounded-2xl shadow-xl">
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-slate-900">Descuento Global (F4)</h3>
                        <button wire:click="closeGlobalDiscountModal" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                    <div class="px-6 py-4 space-y-4">
                        <div class="grid grid-cols-2 gap-3">
                            <button wire:click="$set('globalDiscountType', 'percentage')"
                                class="p-3 rounded-xl border-2 {{ $globalDiscountType === 'percentage' ? 'border-[#ff7261] bg-orange-50' : 'border-slate-200' }}">
                                <span class="font-medium text-sm">Porcentaje (%)</span>
                            </button>
                            <button wire:click="$set('globalDiscountType', 'fixed')"
                                class="p-3 rounded-xl border-2 {{ $globalDiscountType === 'fixed' ? 'border-[#ff7261] bg-orange-50' : 'border-slate-200' }}">
                                <span class="font-medium text-sm">Valor fijo ($)</span>
                            </button>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Valor</label>
                            <input type="number" step="0.01" wire:model="globalDiscountValue" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Razón (opcional)</label>
                            <input type="text" wire:model="globalDiscountReason" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
                        <button wire:click="closeGlobalDiscountModal" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl">Cancelar</button>
                        <button wire:click="applyGlobalDiscount" class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl">Aplicar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Weight Modal -->
    @if($showWeightModal && $weightModalProduct)
    <div class="relative z-[100]" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="closeWeightModal"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-sm bg-white rounded-2xl shadow-xl"
                    x-data
                    x-init="$nextTick(() => $refs.weightInput.focus())">
                    <div class="px-6 py-4 border-b border-slate-200">
                        <h3 class="text-lg font-bold text-slate-900">{{ $weightModalProduct['name'] }}</h3>
                        <p class="text-sm text-slate-500">${{ number_format($weightModalProduct['price'], 2) }} / {{ $weightModalProduct['unit'] }}</p>
                    </div>
                    <div class="px-6 py-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Cantidad ({{ $weightModalProduct['unit'] }})</label>
                            <input
                                x-ref="weightInput"
                                type="text"
                                inputmode="decimal"
                                wire:model="weightModalQuantity"
                                @keydown.enter.prevent="$wire.confirmWeightModal()"
                                class="w-full px-3 py-3 border border-slate-300 rounded-xl text-2xl text-center font-bold focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]"
                                placeholder="0.000">
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
                        <button wire:click="closeWeightModal" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl">Cancelar</button>
                        <button wire:click="confirmWeightModal" class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl">Agregar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Save Quote Modal -->
    @if($showSaveModal)
    <div class="relative z-[100]" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="cancelSave"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-md bg-white rounded-2xl shadow-xl">
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-slate-900">Guardar Cotización</h3>
                        <button wire:click="cancelSave" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                    <div class="px-6 py-4 space-y-4">
                        <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 flex gap-2">
                            <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            <p class="text-xs text-amber-800">
                                <strong>Importante:</strong> Una vez guardada, la cotización <strong>no podrá editarse</strong>. Si necesitas cambiar items o cantidades después, deberás crear una nueva cotización.
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Cliente</label>
                            <p class="px-3 py-2 bg-slate-50 rounded-xl text-sm text-slate-700">
                                @if($selectedCustomer)
                                    {{ $selectedCustomer->customer_type === 'juridico' ? $selectedCustomer->business_name : trim($selectedCustomer->first_name . ' ' . $selectedCustomer->last_name) }}
                                @endif
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Válida hasta</label>
                            <input type="date" wire:model="saveValidUntil" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                            <p class="text-xs text-slate-500 mt-1">Después de esta fecha, la cotización aparecerá en rojo en el listado</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Notas (opcional)</label>
                            <textarea wire:model="saveNotes" rows="3" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="Observaciones, condiciones de pago, etc."></textarea>
                        </div>
                        <div class="bg-slate-50 rounded-xl p-3">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-semibold text-slate-700">Total cotización</span>
                                <span class="text-2xl font-bold bg-gradient-to-r from-[#ff7261] to-[#a855f7] bg-clip-text text-transparent">${{ number_format($total, 2) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
                        <button wire:click="cancelSave" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl">Cancelar</button>
                        <button wire:click="confirmSaveQuote" class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl">Guardar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Saved Confirmation Modal -->
    @if($showSavedConfirmModal)
    <div class="relative z-[100]" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-md bg-white rounded-2xl shadow-xl p-6 text-center">
                    <div class="mx-auto w-14 h-14 rounded-full bg-emerald-100 flex items-center justify-center mb-4">
                        <svg class="w-7 h-7 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Cotización guardada</h3>
                    <p class="text-slate-600 mb-3">La cotización fue creada correctamente.</p>
                    <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 mb-6 text-left flex gap-2">
                        <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        <p class="text-xs text-amber-800">
                            <strong>Recuerda:</strong> Esta cotización ya <strong>no se puede editar</strong>. Si el cliente requiere cambios o cantidades distintas, deberás crear una nueva cotización.
                        </p>
                    </div>
                    <div class="flex justify-center gap-3">
                        <button wire:click="closeSavedConfirmModal" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl">Cerrar</button>
                        <button wire:click="printSavedQuote" class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                            Imprimir
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Help Modal -->
    <div x-show="showHelpModal" x-cloak class="relative z-[100]" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" @click="showHelpModal = false"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-md bg-white rounded-2xl shadow-xl">
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-slate-900">Atajos de teclado</h3>
                        <button @click="showHelpModal = false" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                    <div class="px-6 py-4 space-y-2 text-sm">
                        <div class="flex justify-between"><span>Buscar/crear cliente</span><kbd class="px-2 py-0.5 bg-slate-100 rounded font-mono text-xs">F7</kbd></div>
                        <div class="flex justify-between"><span>Descuento global</span><kbd class="px-2 py-0.5 bg-slate-100 rounded font-mono text-xs">F4</kbd></div>
                        <div class="flex justify-between"><span>Modificar precio de items</span><kbd class="px-2 py-0.5 bg-slate-100 rounded font-mono text-xs">F6</kbd></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

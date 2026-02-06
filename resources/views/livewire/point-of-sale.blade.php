<div class="h-screen flex flex-col bg-slate-100" x-data="{ showCustomerSearch: false }" 
    @keydown.f7.window.prevent="showCustomerSearch = true; $nextTick(() => $refs.customerSearchInput?.focus())"
    @keydown.f3.window.prevent="$wire.applyAllSpecialPrices()"
    @close-customer-modal.window="showCustomerSearch = false">
    <!-- Top Header Bar -->
    <header class="h-14 bg-gradient-to-r from-[#1a1225] to-[#2d1f3d] flex items-center justify-between px-4 flex-shrink-0">
        <div class="flex items-center gap-4">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2 text-white hover:text-white/80 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                <span class="text-sm font-medium">Salir</span>
            </a>
            <div class="h-6 w-px bg-white/20"></div>
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-gradient-to-br from-[#ff7261] to-[#a855f7] rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                    </svg>
                </div>
                <span class="text-white font-bold">{{ auth()->user()->branch?->name ?? 'MikPOS' }}</span>
            </div>
        </div>
        <div class="flex items-center gap-4">
            @if($isElectronicInvoicingEnabled)
            <div class="flex items-center gap-2 px-3 py-1.5 bg-green-500/20 rounded-lg" title="Facturación Electrónica Activa">
                <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="text-green-400 text-xs font-medium">FE</span>
            </div>
            @endif
            @if($cashRegister)
            <div class="flex items-center gap-2 px-3 py-1.5 bg-white/10 rounded-lg">
                <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                <span class="text-white text-sm">{{ $cashRegister->name }}</span>
            </div>
            @endif
            <div class="text-white/70 text-sm">{{ now()->format('d/m/Y H:i') }}</div>
            <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center text-white text-sm font-medium">
                {{ substr(auth()->user()->name, 0, 1) }}
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="flex-1 flex overflow-hidden">
        <!-- Left Panel - Cart (50%) -->
        <div class="w-1/2 bg-white flex flex-col border-r border-slate-200">
            @if($needsReconciliation)
            <div class="p-4 bg-amber-50 border-b border-amber-200">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3 text-amber-700">
                        <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="font-medium">Caja no abierta</p>
                            <p class="text-sm text-amber-600">Debes abrir la caja para poder vender</p>
                        </div>
                    </div>
                    @if($cashRegister)
                    <button wire:click="openCashModal" class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea] transition">
                        Abrir Caja
                    </button>
                    @else
                    <a href="{{ route('cash-reconciliations') }}" class="px-4 py-2 text-sm font-medium text-amber-700 bg-amber-100 hover:bg-amber-200 rounded-xl transition">
                        Ir a Arqueos
                    </a>
                    @endif
                </div>
            </div>
            @endif

            <!-- Customer Section -->
            <div class="p-4 border-b border-slate-200">
                <div class="flex items-center justify-between mb-2">
                    <label class="text-sm font-medium text-slate-700">Cliente</label>
                    <div class="flex items-center gap-1">
                        <button @click="showCustomerSearch = true; $nextTick(() => $refs.customerSearchInput?.focus())" class="p-1.5 text-slate-400 hover:text-[#ff7261] hover:bg-slate-100 rounded-lg transition" title="Buscar cliente (F7)">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </button>
                        <span class="text-xs text-slate-400 px-1.5 py-0.5 rounded bg-slate-100">F7</span>
                    </div>
                </div>
                @if($selectedCustomer)
                <div class="flex items-center justify-between p-3 bg-gradient-to-r from-slate-50 to-slate-100 rounded-xl border border-slate-200">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-[#ff7261] to-[#a855f7] flex items-center justify-center text-white font-medium">
                            {{ substr($selectedCustomer->first_name ?? $selectedCustomer->business_name, 0, 1) }}
                        </div>
                        <div>
                            <p class="font-medium text-slate-800">{{ $selectedCustomer->full_name }}</p>
                            <p class="text-xs text-slate-500">{{ $selectedCustomer->document_number }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-1">
                        <button @click="showCustomerSearch = true" class="p-1.5 text-slate-400 hover:text-[#ff7261] hover:bg-slate-100 rounded-lg transition" title="Cambiar cliente">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                            </svg>
                        </button>
                        @if(!$selectedCustomer->is_default)
                        <button wire:click="clearCustomer" class="p-1.5 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition" title="Quitar cliente">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                        @endif
                    </div>
                </div>
                @else
                <button @click="showCustomerSearch = true; $nextTick(() => $refs.customerSearchInput?.focus())" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 border-dashed rounded-xl text-slate-500 hover:border-[#ff7261] hover:text-[#ff7261] transition text-sm text-left flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    Buscar o crear cliente (F7)
                </button>
                @endif
            </div>

            <!-- Barcode Scanner Input -->
            <div class="px-4 py-3 border-b border-slate-200" x-data="{ 
                lastKeyTime: 0,
                inputBuffer: '',
                scannerTimeout: null,
                handleInput(e) {
                    const now = Date.now();
                    const timeDiff = now - this.lastKeyTime;
                    this.lastKeyTime = now;
                    
                    // Clear previous timeout
                    if (this.scannerTimeout) {
                        clearTimeout(this.scannerTimeout);
                    }
                    
                    const value = e.target.value.trim();
                    
                    // Auto-search after 300ms of no typing
                    // Works for any barcode with 3+ characters
                    this.scannerTimeout = setTimeout(() => {
                        if (value.length >= 3) {
                            $wire.searchByBarcode();
                        }
                    }, 300);
                },
                handleEnter(e) {
                    e.preventDefault();
                    $wire.searchByBarcode();
                }
            }" @focus-barcode-search.window="$refs.barcodeInput.focus()">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
                        </svg>
                    </div>
                    <input wire:model.live="barcodeSearch" 
                        type="text" 
                        x-ref="barcodeInput"
                        x-on:input="handleInput($event)"
                        x-on:keydown.enter="handleEnter($event)"
                        class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm"
                        placeholder="Escanear código de barras...">
                </div>
            </div>

            <!-- Cart Items -->
            <div class="flex-1 overflow-y-auto custom-scrollbar p-2">
                @if(count($cart) > 0)
                <div class="space-y-1">
                    @foreach($cart as $key => $item)
                    <div class="bg-slate-50 rounded-lg p-2 border {{ ($item['discount_amount'] ?? 0) > 0 ? 'border-amber-300 bg-amber-50/50' : (($item['using_special_price'] ?? false) ? 'border-green-300 bg-green-50/50' : 'border-slate-100') }} hover:border-slate-200 transition">
                        <div class="flex items-center gap-2">
                            <div class="w-10 h-10 rounded-md bg-white border border-slate-200 flex items-center justify-center overflow-hidden flex-shrink-0 relative">
                                @if($item['image'])
                                <img src="{{ Storage::url($item['image']) }}" alt="" class="w-full h-full object-cover">
                                @else
                                <div class="w-full h-full bg-gradient-to-br from-[#ff7261]/10 to-[#a855f7]/10 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-[#a855f7]/50" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm-1 14H5c-.55 0-1-.45-1-1V7c0-.55.45-1 1-1h14c.55 0 1 .45 1 1v10c0 .55-.45 1-1 1zm-7-7c1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3 1.34 3 3 3z"/>
                                    </svg>
                                </div>
                                @endif
                                @if(($item['discount_amount'] ?? 0) > 0)
                                <div class="absolute -top-1 -right-1 w-4 h-4 bg-amber-500 rounded-full flex items-center justify-center">
                                    <span class="text-[8px] text-white font-bold">%</span>
                                </div>
                                @elseif($item['using_special_price'] ?? false)
                                <div class="absolute -top-1 -right-1 w-4 h-4 bg-green-500 rounded-full flex items-center justify-center">
                                    <svg class="w-2.5 h-2.5 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                                </div>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-slate-800 text-xs truncate">{{ $item['name'] }}</p>
                                <div class="flex items-center gap-1 text-[10px]">
                                    <span class="text-slate-500">{{ $item['sku'] }}</span>
                                    @if(($item['discount_amount'] ?? 0) > 0)
                                    <span class="text-amber-600 font-medium">-${{ number_format($item['discount_amount'], 0) }}</span>
                                    @elseif($item['using_special_price'] ?? false)
                                    <span class="text-slate-400 line-through">${{ number_format($item['original_price'] ?? $item['price'], 0) }}</span>
                                    <span class="text-green-600 font-medium">${{ number_format($item['price'], 0) }}</span>
                                    @else
                                    <span class="text-slate-500">${{ number_format($item['price'], 0) }} c/u</span>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center gap-1">
                                {{-- Discount button --}}
                                <button wire:click="openDiscountModal('{{ $key }}')" class="p-1 rounded transition {{ ($item['discount_amount'] ?? 0) > 0 ? 'text-amber-600 bg-amber-100 hover:bg-amber-200' : 'text-slate-400 hover:text-amber-600 hover:bg-amber-50' }}" title="Aplicar descuento">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                    </svg>
                                </button>
                                @if(!($item['is_service'] ?? false) && ($item['special_price'] ?? null))
                                <button wire:click="toggleSpecialPrice('{{ $key }}')" class="p-1 rounded transition {{ ($item['using_special_price'] ?? false) ? 'text-green-600 bg-green-100 hover:bg-green-200' : 'text-slate-400 hover:text-green-600 hover:bg-green-50' }}" title="{{ ($item['using_special_price'] ?? false) ? 'Usar precio normal' : 'Usar precio especial $' . number_format($item['special_price'], 0) }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </button>
                                @endif
                                <div class="flex items-center bg-white rounded-md border border-slate-200">
                                    <button wire:click="decrementQuantity('{{ $key }}')" class="w-6 h-6 flex items-center justify-center text-slate-500 hover:text-[#ff7261] transition">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                        </svg>
                                    </button>
                                    <input 
                                        type="number" 
                                        step="0.001" 
                                        min="0.001"
                                        value="{{ $item['quantity'] }}"
                                        wire:change="updateQuantity('{{ $key }}', $event.target.value)"
                                        class="w-12 text-center text-xs font-medium border-0 focus:ring-0 p-0"
                                    >
                                    <button wire:click="incrementQuantity('{{ $key }}')" class="w-6 h-6 flex items-center justify-center text-slate-500 hover:text-[#ff7261] transition">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                        </svg>
                                    </button>
                                </div>
                                <button wire:click="removeFromCart('{{ $key }}')" class="p-1 text-slate-400 hover:text-red-500 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                            <span class="text-sm font-bold {{ ($item['discount_amount'] ?? 0) > 0 ? 'text-amber-600' : (($item['using_special_price'] ?? false) ? 'text-green-600' : 'text-[#ff7261]') }} min-w-[70px] text-right">${{ number_format($item['subtotal'] - ($item['discount_amount'] ?? 0) + $item['tax_amount'], 0) }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="h-full flex flex-col items-center justify-center text-slate-400">
                    <div class="w-20 h-20 mb-4 rounded-full bg-gradient-to-br from-[#ff7261]/10 to-[#a855f7]/10 flex items-center justify-center">
                        <svg class="w-10 h-10 text-[#a855f7]/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <p class="text-lg font-medium">Carrito vacío</p>
                    <p class="text-sm">Agrega productos para comenzar</p>
                </div>
                @endif
            </div>

            <!-- Cart Summary & Actions -->
            <div class="border-t border-slate-200 bg-white p-4 space-y-3">
                <div class="space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">Subtotal ({{ $itemCount }} items)</span>
                        <span class="font-medium">${{ number_format($subtotal, 2) }}</span>
                    </div>
                    @if($this->getDiscountTotalProperty() > 0)
                    <div class="flex justify-between text-sm">
                        <span class="text-amber-600">Descuentos</span>
                        <span class="font-medium text-amber-600">-${{ number_format($this->getDiscountTotalProperty(), 2) }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">Impuestos</span>
                        <span class="font-medium">${{ number_format($taxTotal, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-lg pt-2 border-t border-slate-200">
                        <span class="font-bold text-slate-800">Total</span>
                        <span class="font-bold text-[#ff7261]">${{ number_format($total, 2) }}</span>
                    </div>
                </div>
                
                {{-- Special Price Button --}}
                @if(count($cart) > 0)
                <button wire:click="applyAllSpecialPrices" class="w-full mb-2 px-3 py-2 text-sm font-medium text-green-700 bg-green-50 hover:bg-green-100 border border-green-200 rounded-xl transition flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Aplicar Precio Especial a Todo
                    <span class="text-xs px-1.5 py-0.5 rounded bg-green-200 text-green-800">F3</span>
                </button>
                @endif

                <div class="grid grid-cols-3 gap-2">
                    <button wire:click="clearCart" class="px-4 py-3 text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition flex items-center justify-center gap-2" {{ count($cart) === 0 ? 'disabled' : '' }}>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        Limpiar
                    </button>
                    <div x-data="{ showHoldInput: false }" class="relative">
                        <button @click="showHoldInput = !showHoldInput" class="w-full px-4 py-3 text-sm font-medium text-amber-700 bg-amber-100 hover:bg-amber-200 rounded-xl transition flex items-center justify-center gap-2 relative" {{ count($cart) === 0 ? 'disabled' : '' }}>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Espera
                            @if(count($heldOrders) > 0)
                            <span class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs font-bold rounded-full flex items-center justify-center">{{ count($heldOrders) }}</span>
                            @endif
                        </button>
                        <!-- Hold Note Dropdown -->
                        <div x-show="showHoldInput" x-transition @click.away="showHoldInput = false" class="absolute bottom-full left-0 right-0 mb-2 p-3 bg-white rounded-xl shadow-lg border border-slate-200 z-50">
                            <label class="text-xs font-medium text-slate-600 mb-1 block">Nota (opcional)</label>
                            <input wire:model="holdNote" type="text" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500 mb-2" placeholder="Ej: Cliente va a traer efectivo...">
                            <button wire:click="holdOrder" @click="showHoldInput = false" class="w-full px-3 py-2 text-sm font-medium text-white bg-amber-500 hover:bg-amber-600 rounded-lg transition">
                                Guardar en Espera
                            </button>
                        </div>
                    </div>
                    <button wire:click="openPayment" class="px-4 py-3 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] hover:from-[#e55a4a] hover:to-[#9333ea] rounded-xl transition flex items-center justify-center gap-2 disabled:opacity-50" {{ count($cart) === 0 || $needsReconciliation ? 'disabled' : '' }}>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        Cobrar
                    </button>
                </div>
                <!-- View Held Orders Button -->
                @if(count($heldOrders) > 0)
                <button wire:click="showHeldOrders" class="w-full mt-2 px-4 py-2 text-sm font-medium text-amber-700 bg-amber-50 hover:bg-amber-100 border border-amber-200 rounded-xl transition flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    Ver {{ count($heldOrders) }} orden(es) en espera
                </button>
                @endif
            </div>
        </div>

        <!-- Right Panel - Products (50%) -->
        <div class="w-1/2 flex flex-col overflow-hidden bg-slate-50" x-data @focus-product-search.window="$refs.productSearchInput.focus()">
            <div class="p-4 bg-white border-b border-slate-200">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input x-ref="productSearchInput" wire:model.live.debounce.300ms="productSearch" type="text" class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="Buscar productos por nombre, SKU o código...">
                </div>
            </div>

            <div class="px-4 py-3 bg-white border-b border-slate-200">
                <div class="flex items-center gap-2 overflow-x-auto scrollbar-hide pb-1">
                    <button wire:click="selectCategory(null)" class="px-4 py-2 text-sm font-medium rounded-xl whitespace-nowrap transition flex items-center gap-2 {{ !$selectedCategory ? 'bg-gradient-to-r from-[#ff7261] to-[#a855f7] text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                        </svg>
                        Todos
                    </button>
                    @foreach($categories as $category)
                    <button wire:click="selectCategory({{ $category->id }})" class="px-4 py-2 text-sm font-medium rounded-xl whitespace-nowrap transition {{ $selectedCategory === $category->id ? 'bg-gradient-to-r from-[#ff7261] to-[#a855f7] text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                        {{ $category->name }}
                    </button>
                    @endforeach
                </div>            </div>

            <!-- Products Grid -->
            <div class="flex-1 overflow-y-auto p-4">
                @if($sellableItems->count() > 0)
                <div class="grid grid-cols-4 md:grid-cols-5 lg:grid-cols-6 xl:grid-cols-8 gap-2">
                    @foreach($sellableItems as $item)
                    <button wire:click="{{ $item['type'] === 'service' ? 'addServiceToCart(' . $item['id'] . ')' : 'addToCart(' . $item['id'] . ', ' . ($item['child_id'] ?? 'null') . ')' }}" class="bg-white rounded-lg border border-slate-200 hover:border-[#ff7261] hover:shadow-md transition-all duration-200 overflow-hidden group text-left">
                        <div class="aspect-square bg-slate-50 relative overflow-hidden">
                            @if($item['image'])
                            <img src="{{ Storage::url($item['image']) }}" alt="{{ $item['name'] }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200">
                            @else
                            <div class="w-full h-full bg-gradient-to-br from-[#ff7261]/5 to-[#a855f7]/10 flex items-center justify-center">
                                <div class="text-center">
                                    @if($item['type'] === 'service')
                                    <svg class="w-6 h-6 mx-auto text-[#a855f7]/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
                                    @else
                                    <svg class="w-6 h-6 mx-auto text-[#a855f7]/30" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm-1 14H5c-.55 0-1-.45-1-1V7c0-.55.45-1 1-1h14c.55 0 1 .45 1 1v10c0 .55-.45 1-1 1zm-7-7c1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3 1.34 3 3 3z"/>
                                    </svg>
                                    @endif
                                    <span class="text-[9px] text-slate-400 mt-0.5 block">Sin imagen</span>
                                </div>
                            </div>
                            @endif
                            <div class="absolute top-1 right-1 px-1.5 py-0.5 bg-gradient-to-r from-[#ff7261] to-[#a855f7] text-white text-[10px] font-bold rounded shadow">
                                ${{ number_format($item['price'], 0) }}
                            </div>
                            @if($item['type'] === 'service')
                            <div class="absolute top-1 left-1 px-1.5 py-0.5 text-[9px] font-medium rounded bg-indigo-500 text-white">
                                Serv.
                            </div>
                            @else
                            <div class="absolute top-1 left-1 px-1.5 py-0.5 text-[9px] font-medium rounded {{ $item['stock'] <= 5 ? 'bg-red-500 text-white' : 'bg-green-500 text-white' }}">
                                {{ $item['stock'] }} {{ $item['unit'] ?? 'uds' }}
                            </div>
                            @endif
                            @if($item['type'] === 'child')
                            <div class="absolute bottom-1 left-1 px-1 py-0.5 bg-blue-500 text-white text-[8px] font-medium rounded">
                                Var.
                            </div>
                            @elseif(isset($item['has_variants']) && $item['has_variants'])
                            <div class="absolute bottom-1 left-1 px-1 py-0.5 bg-purple-500 text-white text-[8px] font-medium rounded">
                                {{ $item['variant_count'] }} var.
                            </div>
                            @endif
                        </div>
                        <div class="p-1.5 min-h-[52px] flex flex-col">
                            <p class="font-medium text-slate-800 text-[10px] leading-tight mb-0.5 break-words hyphens-auto" title="{{ $item['name'] }}">{{ $item['name'] }}</p>
                            <p class="text-[9px] text-slate-500 truncate mt-auto">{{ $item['brand'] ?? 'Sin marca' }}</p>
                        </div>
                    </button>
                    @endforeach
                </div>
                @else
                <div class="h-full flex flex-col items-center justify-center text-slate-400">
                    <div class="w-24 h-24 mb-4 rounded-full bg-gradient-to-br from-[#ff7261]/10 to-[#a855f7]/10 flex items-center justify-center">
                        <svg class="w-12 h-12 text-[#a855f7]/30" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm-1 14H5c-.55 0-1-.45-1-1V7c0-.55.45-1 1-1h14c.55 0 1 .45 1 1v10c0 .55-.45 1-1 1z"/>
                        </svg>
                    </div>
                    <p class="text-lg font-medium">No hay productos</p>
                    <p class="text-sm">{{ $productSearch ? 'No se encontraron resultados' : 'Selecciona una categoría o busca productos' }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Customer Search Modal (F7) -->
    <div x-show="showCustomerSearch" x-transition class="fixed inset-0 z-[100]" @keydown.escape.window="showCustomerSearch = false; $wire.showCreateCustomer = false">
        <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm z-[100]" @click="showCustomerSearch = false; $wire.showCreateCustomer = false"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-start justify-center p-4 pt-20">
                <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl" @click.away="showCustomerSearch = false; $wire.showCreateCustomer = false">
                    @if(!$showCreateCustomer)
                    {{-- Search View --}}
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-slate-900">Buscar Cliente</h3>
                        <button @click="showCustomerSearch = false" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="p-4">
                        <input x-ref="customerSearchInput" wire:model.live.debounce.300ms="customerSearch" type="text" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="Buscar por nombre, documento o razón social...">
                    </div>
                    <div class="max-h-80 overflow-y-auto">
                        @if(count($customers) > 0)
                            @foreach($customers as $customer)
                            <button wire:click="selectCustomer({{ $customer->id }})" @click="showCustomerSearch = false" class="w-full px-6 py-4 text-left hover:bg-slate-50 flex items-center gap-4 border-b border-slate-100 last:border-0">
                                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-[#ff7261] to-[#a855f7] flex items-center justify-center text-white font-medium text-lg">
                                    {{ substr($customer->first_name ?? $customer->business_name, 0, 1) }}
                                </div>
                                <div class="flex-1">
                                    <p class="font-medium text-slate-800">{{ $customer->full_name }}</p>
                                    <p class="text-sm text-slate-500">{{ $customer->document_number }} · {{ $customer->phone ?? 'Sin teléfono' }}</p>
                                </div>
                            </button>
                            @endforeach
                        @elseif(strlen($customerSearch) >= 2)
                            <div class="px-6 py-8 text-center text-slate-400">
                                <svg class="w-12 h-12 mx-auto mb-2 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                <p class="font-medium">No se encontraron clientes</p>
                            </div>
                        @else
                            <div class="px-6 py-8 text-center text-slate-400">
                                <svg class="w-12 h-12 mx-auto mb-2 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                                <p class="font-medium">Escribe para buscar</p>
                            </div>
                        @endif
                    </div>
                    {{-- Create Customer Button --}}
                    <div class="px-4 py-3 border-t border-slate-200">
                        <button wire:click="openCreateCustomer" class="w-full px-4 py-3 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] hover:from-[#e55a4a] hover:to-[#9333ea] rounded-xl transition flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                            </svg>
                            Crear Nuevo Cliente
                        </button>
                    </div>
                    @else
                    {{-- Create Customer Form --}}
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <button wire:click="closeCreateCustomer" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                </svg>
                            </button>
                            <h3 class="text-lg font-bold text-slate-900">Crear Cliente</h3>
                        </div>
                        <button @click="showCustomerSearch = false; $wire.closeCreateCustomer()" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="p-4 space-y-4 max-h-[60vh] overflow-y-auto">
                        {{-- Customer Type --}}
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Tipo de Cliente</label>
                            <div class="grid grid-cols-2 gap-3">
                                <button type="button" wire:click="$set('newCustomerType', 'natural')" class="p-3 rounded-xl border-2 transition-all flex items-center gap-2 {{ $newCustomerType === 'natural' ? 'border-[#ff7261] bg-[#ff7261]/5' : 'border-slate-200 hover:border-slate-300' }}">
                                    <div class="w-8 h-8 rounded-full {{ $newCustomerType === 'natural' ? 'bg-[#ff7261]/20' : 'bg-slate-100' }} flex items-center justify-center">
                                        <svg class="w-4 h-4 {{ $newCustomerType === 'natural' ? 'text-[#ff7261]' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                    </div>
                                    <span class="text-sm font-medium {{ $newCustomerType === 'natural' ? 'text-[#ff7261]' : 'text-slate-600' }}">Persona Natural</span>
                                </button>
                                <button type="button" wire:click="$set('newCustomerType', 'juridico')" class="p-3 rounded-xl border-2 transition-all flex items-center gap-2 {{ $newCustomerType === 'juridico' ? 'border-[#a855f7] bg-[#a855f7]/5' : 'border-slate-200 hover:border-slate-300' }}">
                                    <div class="w-8 h-8 rounded-full {{ $newCustomerType === 'juridico' ? 'bg-[#a855f7]/20' : 'bg-slate-100' }} flex items-center justify-center">
                                        <svg class="w-4 h-4 {{ $newCustomerType === 'juridico' ? 'text-[#a855f7]' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                        </svg>
                                    </div>
                                    <span class="text-sm font-medium {{ $newCustomerType === 'juridico' ? 'text-[#a855f7]' : 'text-slate-600' }}">Persona Jurídica</span>
                                </button>
                            </div>
                        </div>

                        {{-- Document Type & Number --}}
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Tipo Documento</label>
                                <select wire:model="newCustomerDocumentType" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm">
                                    <option value="">Seleccionar...</option>
                                    @foreach($taxDocuments as $doc)
                                    <option value="{{ $doc->id }}">{{ $doc->abbreviation ?: $doc->description }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Número Documento <span class="text-red-500">*</span></label>
                                <input wire:model="newCustomerDocument" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm" placeholder="Ej: 123456789">
                            </div>
                        </div>

                        {{-- Name Fields (Natural Person) --}}
                        @if($newCustomerType === 'natural')
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Nombres <span class="text-red-500">*</span></label>
                                <input wire:model="newCustomerFirstName" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm" placeholder="Nombres">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Apellidos</label>
                                <input wire:model="newCustomerLastName" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm" placeholder="Apellidos">
                            </div>
                        </div>
                        @else
                        {{-- Business Name (Legal Entity) --}}
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Razón Social <span class="text-red-500">*</span></label>
                            <input wire:model="newCustomerBusinessName" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm" placeholder="Nombre de la empresa">
                        </div>
                        @endif

                        {{-- Contact Info --}}
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Teléfono</label>
                                <input wire:model="newCustomerPhone" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm" placeholder="Ej: 3001234567">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                                <input wire:model="newCustomerEmail" type="email" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm" placeholder="correo@ejemplo.com">
                            </div>
                        </div>
                    </div>
                    {{-- Footer --}}
                    <div class="px-4 py-3 border-t border-slate-200 flex gap-3">
                        <button wire:click="closeCreateCustomer" class="flex-1 px-4 py-3 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">
                            Cancelar
                        </button>
                        <button wire:click="saveNewCustomer" class="flex-1 px-4 py-3 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea]">
                            Guardar Cliente
                        </button>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Variant Selection Modal -->
    @if($showVariantModal && $variantProduct)
    <div class="fixed inset-0 z-[100]">
        <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm z-[100]" wire:click="closeVariantModal"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl">
                    <!-- Header -->
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-slate-900">Seleccionar Variante</h3>
                        <button wire:click="closeVariantModal" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <!-- Product Info -->
                    <div class="px-6 py-3 bg-slate-50 border-b border-slate-200">
                        <div class="flex items-center gap-3">
                            @if($variantProduct['image'])
                            <img src="{{ Storage::url($variantProduct['image']) }}" class="w-12 h-12 rounded-lg object-cover">
                            @else
                            <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-[#ff7261]/10 to-[#a855f7]/10 flex items-center justify-center">
                                <svg class="w-6 h-6 text-[#a855f7]/50" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2z"/>
                                </svg>
                            </div>
                            @endif
                            <div>
                                <p class="font-semibold text-slate-800">{{ $variantProduct['name'] }}</p>
                                <p class="text-xs text-slate-500">{{ $variantProduct['sku'] }} · {{ $variantProduct['brand'] ?? 'Sin marca' }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Options -->
                    <div class="p-4 max-h-80 overflow-y-auto space-y-2">
                        <!-- Parent Product Option -->
                        <button wire:click="selectVariant(null)" class="w-full p-3 rounded-xl border-2 border-slate-200 hover:border-[#ff7261] hover:bg-[#ff7261]/5 transition-all flex items-center justify-between group">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                    </svg>
                                </div>
                                <div class="text-left">
                                    <p class="font-medium text-slate-800 group-hover:text-[#ff7261]">Producto Principal</p>
                                    <p class="text-xs text-slate-500">Stock: {{ $variantProduct['current_stock'] }}</p>
                                </div>
                            </div>
                            <span class="font-bold text-[#ff7261]">${{ number_format($variantProduct['sale_price'], 0) }}</span>
                        </button>
                        
                        <!-- Variant Options -->
                        @foreach($variantOptions as $variant)
                        <button wire:click="selectVariant({{ $variant['id'] }})" class="w-full p-3 rounded-xl border-2 border-slate-200 hover:border-[#a855f7] hover:bg-[#a855f7]/5 transition-all flex items-center justify-between group">
                            <div class="flex items-center gap-3">
                                @if($variant['image'])
                                <img src="{{ Storage::url($variant['image']) }}" class="w-10 h-10 rounded-lg object-cover">
                                @else
                                <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                    </svg>
                                </div>
                                @endif
                                <div class="text-left">
                                    <p class="font-medium text-slate-800 group-hover:text-[#a855f7]">{{ $variant['name'] }}</p>
                                    <p class="text-xs text-slate-500">{{ $variant['sku'] }} · Stock: {{ $variant['current_stock'] }}</p>
                                </div>
                            </div>
                            <span class="font-bold text-[#a855f7]">${{ number_format($variant['sale_price'], 0) }}</span>
                        </button>
                        @endforeach
                    </div>
                    
                    <!-- Footer -->
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200">
                        <button wire:click="closeVariantModal" class="w-full px-4 py-2.5 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Discount Modal -->
    @if($showDiscountModal && $discountCartKey && isset($cart[$discountCartKey]))
    <div class="fixed inset-0 z-[100]">
        <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm z-[100]" wire:click="closeDiscountModal"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl">
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-slate-900">Aplicar Descuento</h3>
                        <button wire:click="closeDiscountModal" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="px-6 py-4 space-y-4">
                        <div class="p-3 bg-slate-50 rounded-xl">
                            <p class="font-medium text-slate-800">{{ $cart[$discountCartKey]['name'] }}</p>
                            <p class="text-sm text-slate-500">Precio: ${{ number_format($cart[$discountCartKey]['price'], 0) }} x {{ $cart[$discountCartKey]['quantity'] }}</p>
                            <p class="text-sm font-medium text-slate-700">Subtotal: ${{ number_format($cart[$discountCartKey]['subtotal'], 0) }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Tipo de descuento</label>
                            <div class="grid grid-cols-2 gap-2">
                                <button wire:click="$set('discountType', 'percentage')" class="px-4 py-2 text-sm font-medium rounded-xl border-2 transition {{ $discountType === 'percentage' ? 'border-amber-500 bg-amber-50 text-amber-700' : 'border-slate-200 text-slate-600 hover:border-amber-300' }}">
                                    Porcentaje (%)
                                </button>
                                <button wire:click="$set('discountType', 'fixed')" class="px-4 py-2 text-sm font-medium rounded-xl border-2 transition {{ $discountType === 'fixed' ? 'border-amber-500 bg-amber-50 text-amber-700' : 'border-slate-200 text-slate-600 hover:border-amber-300' }}">
                                    Valor Fijo ($)
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">{{ $discountType === 'percentage' ? 'Porcentaje' : 'Valor' }}</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">{{ $discountType === 'percentage' ? '%' : '$' }}</span>
                                <input wire:model="discountValue" type="number" step="0.01" min="0" max="{{ $discountType === 'percentage' ? '100' : $cart[$discountCartKey]['subtotal'] }}" class="w-full pl-8 pr-4 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500" placeholder="0">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Razón (opcional)</label>
                            <input wire:model="discountReason" type="text" class="w-full px-4 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500" placeholder="Ej: Cliente frecuente, promoción...">
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
                        <button wire:click="closeDiscountModal" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">
                            Cancelar
                        </button>
                        <button wire:click="applyDiscount" class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-amber-500 to-amber-600 rounded-xl hover:from-amber-600 hover:to-amber-700">
                            Aplicar Descuento
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Payment Modal with Multiple Methods -->
    @if($showPaymentModal)
    <div class="fixed inset-0 z-[100]">
        <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm z-[100]" wire:click="cancelPayment"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-xl bg-white rounded-2xl shadow-2xl">
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-slate-900">Procesar Pago</h3>
                        <button wire:click="cancelPayment" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <div class="p-6 space-y-4">
                        <!-- Total -->
                        <div class="text-center py-4 bg-gradient-to-r from-slate-50 to-slate-100 rounded-xl">
                            <p class="text-sm text-slate-500 mb-1">Total a Pagar</p>
                            <p class="text-4xl font-bold text-[#ff7261]">${{ number_format($total, 2) }}</p>
                        </div>

                        <!-- Payment Methods List -->
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <label class="text-sm font-medium text-slate-700">Métodos de Pago</label>
                                <button wire:click="addPaymentMethod" class="text-xs text-[#ff7261] hover:text-[#e55a4a] font-medium flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    Agregar método
                                </button>
                            </div>
                            
                            <div class="space-y-3">
                                @foreach($payments as $index => $payment)
                                <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl border border-slate-200">
                                    <select wire:model.live="payments.{{ $index }}.method_id" class="flex-1 px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm">
                                        <option value="">Seleccionar método</option>
                                        @foreach($paymentMethods as $method)
                                        <option value="{{ $method->id }}">{{ $method->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">$</span>
                                        <input wire:model.live="payments.{{ $index }}.amount" type="number" step="0.01" min="0" class="w-32 pl-7 pr-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm text-right font-medium" placeholder="0.00">
                                    </div>
                                    @if(count($payments) > 1)
                                    <button wire:click="removePaymentMethod({{ $index }})" class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                    @endif
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Payment Summary -->
                        <div class="p-4 bg-slate-50 rounded-xl space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-500">Total a pagar</span>
                                <span class="font-medium">${{ number_format($total, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-500">Total recibido</span>
                                <span class="font-medium text-green-600">${{ number_format($totalReceived, 2) }}</span>
                            </div>
                            @if($pendingAmount > 0)
                            <div class="flex justify-between text-sm pt-2 border-t border-slate-200">
                                <span class="text-red-600 font-medium">Falta por pagar</span>
                                <span class="font-bold text-red-600">${{ number_format($pendingAmount, 2) }}</span>
                            </div>
                            @elseif($change > 0)
                            <div class="flex justify-between text-sm pt-2 border-t border-slate-200">
                                <span class="text-green-600 font-medium">Cambio</span>
                                <span class="font-bold text-green-600">${{ number_format($change, 2) }}</span>
                            </div>
                            @endif
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex gap-3">
                        <button wire:click="cancelPayment" class="flex-1 px-4 py-3 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">
                            Cancelar
                        </button>
                        <button wire:click="processPayment" class="flex-1 px-4 py-3 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea] disabled:opacity-50" {{ $pendingAmount > 0 ? 'disabled' : '' }}>
                            Confirmar Pago
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Held Orders Modal -->
    @if($showHeldOrdersModal)
    <div class="fixed inset-0 z-[100]">
        <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm z-[100]" wire:click="$set('showHeldOrdersModal', false)"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl">
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">Órdenes en Espera</h3>
                                <p class="text-sm text-slate-500">{{ count($heldOrders) }} orden(es) guardada(s)</p>
                            </div>
                        </div>
                        <button wire:click="$set('showHeldOrdersModal', false)" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <div class="p-6 max-h-[60vh] overflow-y-auto">
                        @if(count($heldOrders) > 0)
                        <div class="space-y-3">
                            @foreach($heldOrders as $index => $order)
                            <div class="bg-slate-50 rounded-xl border border-slate-200 overflow-hidden hover:border-amber-300 transition">
                                <div class="p-4">
                                    <div class="flex items-start justify-between">
                                        <div class="flex items-center gap-3">
                                            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-[#ff7261] to-[#a855f7] flex items-center justify-center text-white font-bold text-lg">
                                                {{ substr($order['customer_name'], 0, 1) }}
                                            </div>
                                            <div>
                                                <p class="font-semibold text-slate-800">{{ $order['customer_name'] }}</p>
                                                <div class="flex items-center gap-2 text-sm text-slate-500">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                    {{ $order['created_at'] }}
                                                    <span class="text-slate-300">•</span>
                                                    {{ $order['item_count'] }} producto(s)
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-xl font-bold text-[#ff7261]">${{ number_format($order['total'], 2) }}</p>
                                        </div>
                                    </div>
                                    @if(!empty($order['note']))
                                    <div class="mt-3 p-2 bg-amber-50 rounded-lg border border-amber-100">
                                        <p class="text-sm text-amber-700">
                                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                                            </svg>
                                            {{ $order['note'] }}
                                        </p>
                                    </div>
                                    @endif
                                </div>
                                <div class="px-4 py-3 bg-white border-t border-slate-200 flex justify-end gap-2">
                                    <button wire:click="deleteHeldOrder({{ $index }})" class="px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50 rounded-lg transition flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                        Eliminar
                                    </button>
                                    <button wire:click="restoreOrder({{ $index }})" class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] hover:from-[#e55a4a] hover:to-[#9333ea] rounded-lg transition flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                        </svg>
                                        Restaurar
                                    </button>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @else
                        <div class="text-center py-12">
                            <div class="w-20 h-20 mx-auto mb-4 rounded-full bg-slate-100 flex items-center justify-center">
                                <svg class="w-10 h-10 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                            </div>
                            <p class="text-lg font-medium text-slate-600">No hay órdenes en espera</p>
                            <p class="text-sm text-slate-400">Las órdenes guardadas aparecerán aquí</p>
                        </div>
                        @endif
                    </div>

                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200">
                        <button wire:click="$set('showHeldOrdersModal', false)" class="w-full px-4 py-3 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Open Cash Register Modal -->
    @if($showOpenCashModal)
    <div class="fixed inset-0 z-[100]">
        <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm z-[100]" wire:click="cancelOpenCash"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl">
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-[#ff7261] to-[#a855f7] flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">Abrir Caja</h3>
                                <p class="text-sm text-slate-500">{{ $cashRegister?->name }}</p>
                            </div>
                        </div>
                        <button wire:click="cancelOpenCash" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Monto Inicial en Caja</label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-lg">$</span>
                                <input wire:model="openingAmount" type="number" step="0.01" min="0" 
                                    class="w-full pl-10 pr-4 py-4 text-2xl font-bold text-center border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]"
                                    placeholder="0.00" autofocus>
                            </div>
                            @error('openingAmount') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Notas (opcional)</label>
                            <textarea wire:model="openingNotes" rows="2" 
                                class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm"
                                placeholder="Observaciones de apertura..."></textarea>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex gap-3">
                        <button wire:click="cancelOpenCash" class="flex-1 px-4 py-3 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">
                            Cancelar
                        </button>
                        <button wire:click="storeOpenCash" class="flex-1 px-4 py-3 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea]">
                            Abrir Caja
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Weight Quantity Modal -->
    @if($showWeightModal && $weightModalProduct)
    <div class="relative z-[100]" role="dialog" aria-modal="true"
        x-data="{ quantity: @entangle('weightModalQuantity') }"
        @keydown.escape.window="$wire.closeWeightModal()"
        @keydown.enter.window="$wire.confirmWeightModal()">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" 
            wire:click="closeWeightModal"></div>
        
        <!-- Modal -->
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-sm bg-white rounded-2xl shadow-xl">
                    <!-- Header -->
                    <div class="px-6 py-4 border-b border-slate-200">
                        <h3 class="text-lg font-bold text-slate-900 text-center">
                            Ingresar Cantidad
                        </h3>
                    </div>
                    
                    <!-- Content -->
                    <div class="px-6 py-6 space-y-4">
                        <!-- Product Info -->
                        <div class="text-center">
                            <p class="font-medium text-slate-800">
                                {{ $weightModalProduct['name'] }}
                            </p>
                            <p class="text-sm text-slate-500">
                                ${{ number_format($weightModalProduct['price'], 2) }} / 
                                {{ $weightModalProduct['unit'] }}
                            </p>
                        </div>
                        
                        <!-- Quantity Input -->
                        <div>
                            <input type="number" 
                                wire:model="weightModalQuantity"
                                x-ref="weightInput"
                                x-init="$nextTick(() => $refs.weightInput.focus())"
                                step="0.001"
                                min="0.001"
                                class="w-full text-center text-3xl font-bold px-4 py-4 
                                    border border-slate-300 rounded-xl 
                                    focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]"
                                placeholder="0.000">
                            <p class="text-center text-lg text-slate-600 mt-2">
                                {{ $weightModalProduct['unit'] }}
                            </p>
                        </div>
                        
                        <!-- Stock Info -->
                        <p class="text-center text-sm text-slate-500">
                            Stock disponible: 
                            {{ number_format($weightModalProduct['stock'], 3) }} 
                            {{ $weightModalProduct['unit'] }}
                        </p>
                    </div>
                    
                    <!-- Footer -->
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 
                        flex justify-center gap-3">
                        <button wire:click="closeWeightModal" 
                            class="px-6 py-2.5 text-sm font-medium text-slate-700 
                                bg-white border border-slate-300 rounded-xl 
                                hover:bg-slate-50">
                            Cancelar
                        </button>
                        <button wire:click="confirmWeightModal" 
                            class="px-6 py-2.5 text-sm font-medium text-white 
                                bg-gradient-to-r from-[#ff7261] to-[#a855f7] 
                                rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea]">
                            Agregar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

@script
<script>
    // Listen for print receipt event
    $wire.on('print-receipt', (event) => {
        const saleId = event.saleId;
        const receiptUrl = `/receipt/${saleId}?print=auto`;
        
        // Open receipt in new window optimized for printing
        const printWindow = window.open(
            receiptUrl,
            'receipt_' + saleId,
            'width=350,height=600,scrollbars=yes,resizable=yes'
        );
        
        // Focus the new window
        if (printWindow) {
            printWindow.focus();
        }
    });
</script>
@endscript
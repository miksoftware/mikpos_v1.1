<div class="h-screen flex flex-col bg-slate-100" x-data="{ showCustomerSearch: false }" @keydown.f7.window.prevent="showCustomerSearch = true; $nextTick(() => $refs.customerSearchInput?.focus())">
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
                <span class="text-white font-bold">MikPOS</span>
            </div>
        </div>
        <div class="flex items-center gap-4">
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
            <div class="p-3 bg-amber-50 border-b border-amber-200">
                <div class="flex items-center gap-2 text-amber-700">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <div class="text-sm">
                        <p class="font-medium">Caja no abierta</p>
                        <a href="{{ route('cash-reconciliations') }}" class="underline text-amber-800">Abrir arqueo de caja</a>
                    </div>
                </div>
            </div>
            @endif

            <!-- Customer Section -->
            <div class="p-4 border-b border-slate-200">
                <div class="flex items-center justify-between mb-2">
                    <label class="text-sm font-medium text-slate-700">Cliente</label>
                    <button @click="showCustomerSearch = true" class="text-xs text-slate-400 hover:text-[#ff7261] px-2 py-1 rounded bg-slate-100 hover:bg-slate-200 transition">F7</button>
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
                    @if(!$selectedCustomer->is_default)
                    <button wire:click="clearCustomer" class="p-1.5 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                    @endif
                </div>
                @else
                <button @click="showCustomerSearch = true" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 border-dashed rounded-xl text-slate-500 hover:border-[#ff7261] hover:text-[#ff7261] transition text-sm text-left">
                    Presiona F7 o haz clic para buscar cliente...
                </button>
                @endif
            </div>

            <!-- Barcode Scanner Input -->
            <div class="px-4 py-3 border-b border-slate-200">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
                        </svg>
                    </div>
                    <input wire:model.live.debounce.100ms="barcodeSearch" type="text" 
                        class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm"
                        placeholder="Escanear código de barras...">
                </div>
            </div>

            <!-- Cart Items -->
            <div class="flex-1 overflow-y-auto custom-scrollbar p-4">
                @if(count($cart) > 0)
                <div class="space-y-2">
                    @foreach($cart as $key => $item)
                    <div class="bg-slate-50 rounded-xl p-3 border border-slate-100 hover:border-slate-200 transition">
                        <div class="flex gap-3">
                            <div class="w-14 h-14 rounded-lg bg-white border border-slate-200 flex items-center justify-center overflow-hidden flex-shrink-0">
                                @if($item['image'])
                                <img src="{{ Storage::url($item['image']) }}" alt="" class="w-full h-full object-cover">
                                @else
                                <div class="w-full h-full bg-gradient-to-br from-[#ff7261]/10 to-[#a855f7]/10 flex items-center justify-center">
                                    <svg class="w-7 h-7 text-[#a855f7]/50" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm-1 14H5c-.55 0-1-.45-1-1V7c0-.55.45-1 1-1h14c.55 0 1 .45 1 1v10c0 .55-.45 1-1 1zm-7-7c1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3 1.34 3 3 3z"/>
                                    </svg>
                                </div>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-slate-800 text-sm truncate">{{ $item['name'] }}</p>
                                <p class="text-xs text-slate-500">{{ $item['sku'] }}</p>
                                <p class="text-sm font-semibold text-[#ff7261] mt-1">${{ number_format($item['price'], 2) }}</p>
                            </div>
                            <div class="flex flex-col items-end gap-2">
                                <button wire:click="removeFromCart('{{ $key }}')" class="p-1 text-slate-400 hover:text-red-500 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                                <div class="flex items-center gap-1 bg-white rounded-lg border border-slate-200">
                                    <button wire:click="decrementQuantity('{{ $key }}')" class="w-7 h-7 flex items-center justify-center text-slate-500 hover:text-[#ff7261] transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                        </svg>
                                    </button>
                                    <span class="w-8 text-center text-sm font-medium">{{ $item['quantity'] }}</span>
                                    <button wire:click="incrementQuantity('{{ $key }}')" class="w-7 h-7 flex items-center justify-center text-slate-500 hover:text-[#ff7261] transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-end mt-2 pt-2 border-t border-slate-200">
                            <span class="text-sm font-semibold text-slate-700">${{ number_format($item['subtotal'], 2) }}</span>
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
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">Impuestos</span>
                        <span class="font-medium">${{ number_format($taxTotal, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-lg pt-2 border-t border-slate-200">
                        <span class="font-bold text-slate-800">Total</span>
                        <span class="font-bold text-[#ff7261]">${{ number_format($total, 2) }}</span>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <button wire:click="clearCart" class="px-4 py-3 text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition flex items-center justify-center gap-2" {{ count($cart) === 0 ? 'disabled' : '' }}>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        Limpiar
                    </button>
                    <button wire:click="openPayment" class="px-4 py-3 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] hover:from-[#e55a4a] hover:to-[#9333ea] rounded-xl transition flex items-center justify-center gap-2 disabled:opacity-50" {{ count($cart) === 0 || $needsReconciliation ? 'disabled' : '' }}>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        Cobrar
                    </button>
                </div>
            </div>
        </div>

        <!-- Right Panel - Products (50%) -->
        <div class="w-1/2 flex flex-col overflow-hidden bg-slate-50">
            <div class="p-4 bg-white border-b border-slate-200">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input wire:model.live.debounce.300ms="productSearch" type="text" class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="Buscar productos por nombre, SKU o código...">
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
                </div>
            </div>

            <!-- Products Grid -->
            <div class="flex-1 overflow-y-auto p-4">
                @if($products->count() > 0)
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                    @foreach($products as $product)
                    <button wire:click="addToCart({{ $product->id }})" class="bg-white rounded-xl border border-slate-200 hover:border-[#ff7261] hover:shadow-lg transition-all duration-200 overflow-hidden group text-left">
                        <div class="aspect-square bg-slate-50 relative overflow-hidden">
                            @if($product->image)
                            <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200">
                            @else
                            <div class="w-full h-full bg-gradient-to-br from-[#ff7261]/5 to-[#a855f7]/10 flex items-center justify-center">
                                <div class="text-center">
                                    <svg class="w-12 h-12 mx-auto text-[#a855f7]/30" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm-1 14H5c-.55 0-1-.45-1-1V7c0-.55.45-1 1-1h14c.55 0 1 .45 1 1v10c0 .55-.45 1-1 1zm-7-7c1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3 1.34 3 3 3z"/>
                                    </svg>
                                    <span class="text-xs text-slate-400 mt-1 block">Sin imagen</span>
                                </div>
                            </div>
                            @endif
                            <div class="absolute top-2 right-2 px-2 py-1 bg-gradient-to-r from-[#ff7261] to-[#a855f7] text-white text-xs font-bold rounded-lg shadow">
                                ${{ number_format($product->sale_price, 0) }}
                            </div>
                            <div class="absolute top-2 left-2 px-2 py-1 text-xs font-medium rounded-lg {{ $product->current_stock <= $product->min_stock ? 'bg-red-500 text-white' : 'bg-green-500 text-white' }}">
                                {{ (int)$product->current_stock }} uds
                            </div>
                        </div>
                        <div class="p-3">
                            <p class="font-medium text-slate-800 text-sm line-clamp-2 leading-tight mb-1">{{ $product->name }}</p>
                            <p class="text-xs text-slate-500">{{ $product->brand?->name ?? 'Sin marca' }}</p>
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
    <div x-show="showCustomerSearch" x-transition class="fixed inset-0 z-[100]" @keydown.escape.window="showCustomerSearch = false">
        <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm z-[100]" @click="showCustomerSearch = false"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-start justify-center p-4 pt-20">
                <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl" @click.away="showCustomerSearch = false">
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
                </div>
            </div>
        </div>
    </div>

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
</div>
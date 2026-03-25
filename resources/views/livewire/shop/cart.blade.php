<div>
    <h1 class="text-2xl font-bold text-slate-900 mb-6">Carrito de compras</h1>

    @if(count($items) === 0)
        {{-- Empty Cart --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center">
            <div class="w-16 h-16 bg-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"></path>
                </svg>
            </div>
            <h2 class="text-lg font-semibold text-slate-900 mb-2">Tu carrito está vacío</h2>
            <p class="text-slate-500 mb-6">Agrega productos desde el catálogo para comenzar tu compra.</p>
            <a href="{{ route('shop.catalog') }}" wire:navigate
                class="inline-flex items-center gap-2 px-6 py-3 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea] transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Ver catálogo
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Cart Items --}}
            <div class="lg:col-span-2 space-y-4">
                @foreach($items as $index => $item)
                    <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5" wire:key="cart-item-{{ $index }}">
                        <div class="flex gap-4">
                            {{-- Image --}}
                            <div class="w-20 h-20 sm:w-24 sm:h-24 bg-slate-100 rounded-xl overflow-hidden flex-shrink-0">
                                @if($item['image'])
                                    <img src="{{ asset('storage/' . $item['image']) }}" alt="{{ $item['name'] }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center">
                                        <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                @endif
                            </div>

                            {{-- Details --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <h3 class="text-sm font-semibold text-slate-900 truncate">{{ $item['name'] }}</h3>
                                        <p class="text-xs text-slate-400 mt-0.5">SKU: {{ $item['sku'] }}</p>
                                    </div>
                                    <button wire:click="removeItem({{ $index }})" wire:confirm="¿Eliminar este producto del carrito?"
                                        class="p-1.5 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors flex-shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>


                                <p class="text-sm font-medium text-slate-700 mt-2">
                                    ${{ number_format($item['unit_price'], 0, ',', '.') }}
                                    @if($item['tax_rate'] > 0)
                                        <span class="text-xs text-slate-400">(IVA {{ $item['tax_rate'] }}% incluido)</span>
                                    @endif
                                </p>

                                <div class="flex items-center justify-between mt-3">
                                    {{-- Quantity Controls --}}
                                    <div class="flex items-center border border-slate-300 rounded-xl overflow-hidden">
                                        <button wire:click="updateQuantity({{ $index }}, {{ max(1, $item['quantity'] - 1) }})"
                                            class="px-2.5 py-1.5 text-slate-600 hover:bg-slate-100 transition-colors disabled:opacity-50"
                                            {{ $item['quantity'] <= 1 ? 'disabled' : '' }}>
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                            </svg>
                                        </button>
                                        <input type="number" value="{{ $item['quantity'] }}" min="1" {{ ($item['manages_inventory'] ?? true) ? 'max=' . (int) $item['max_stock'] : '' }}
                                            wire:change="updateQuantity({{ $index }}, $event.target.value)"
                                            class="w-12 text-center border-x border-slate-300 py-1.5 text-sm font-medium focus:outline-none">
                                        <button wire:click="updateQuantity({{ $index }}, {{ $item['quantity'] + 1 }})"
                                            class="px-2.5 py-1.5 text-slate-600 hover:bg-slate-100 transition-colors disabled:opacity-50"
                                            {{ ($item['manages_inventory'] ?? true) && $item['quantity'] >= (int) $item['max_stock'] ? 'disabled' : '' }}>
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                            </svg>
                                        </button>
                                    </div>

                                    {{-- Item Total --}}
                                    <p class="text-sm font-bold text-slate-900">
                                        ${{ number_format($item['unit_price'] * $item['quantity'], 0, ',', '.') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Order Summary --}}
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl border border-slate-200 p-5 sticky top-24">
                    <h2 class="text-lg font-bold text-slate-900 mb-4">Resumen del pedido</h2>

                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between text-slate-600">
                            <span>Productos ({{ $this->itemCount }})</span>
                            <span>${{ number_format($this->subtotal, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between text-slate-600">
                            <span>IVA incluido</span>
                            <span>${{ number_format($this->taxTotal, 0, ',', '.') }}</span>
                        </div>
                        <hr class="border-slate-200">
                        <div class="flex justify-between text-base font-bold text-slate-900">
                            <span>Total</span>
                            <span>${{ number_format($this->total, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <a href="{{ route('shop.checkout') }}"
                        class="mt-6 w-full flex items-center justify-center gap-2 px-6 py-3 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea] transition-all">
                        Finalizar pedido
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                    </a>

                    <a href="{{ route('shop.catalog') }}" wire:navigate
                        class="mt-3 w-full flex items-center justify-center gap-2 px-6 py-3 text-sm font-medium text-slate-600 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Seguir comprando
                    </a>
                </div>
            </div>
        </div>
    @endif
</div>

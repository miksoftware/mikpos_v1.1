<div>
    {{-- Search & Filters --}}
    <div class="mb-6 space-y-4">
        <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre, SKU o descripción..."
                class="w-full pl-10 pr-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] bg-white">
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <select wire:model.live="category" class="px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] bg-white text-sm">
                <option value="">Todas las categorías</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>

            <select wire:model.live="brand" class="px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] bg-white text-sm">
                <option value="">Todas las marcas</option>
                @foreach($brands as $b)
                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                @endforeach
            </select>

            <select wire:model.live="perPage" class="px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] bg-white text-sm">
                <option value="12">12 por página</option>
                <option value="24">24 por página</option>
                <option value="36">36 por página</option>
                <option value="60">60 por página</option>
            </select>

            @if($search !== '' || $category !== '' || $brand !== '')
                <button wire:click="clearFilters" class="px-3 py-2 text-sm text-slate-500 hover:text-slate-700 hover:bg-slate-100 rounded-xl transition-colors">
                    Limpiar filtros
                </button>
            @endif

            <span class="ml-auto text-sm text-slate-500">
                {{ $products->total() }} {{ $products->total() === 1 ? 'producto' : 'productos' }}
            </span>
        </div>
    </div>

    {{-- Product Grid --}}
    @if($products->count() > 0)
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @foreach($products as $product)
                <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden hover:shadow-md hover:border-slate-300 transition-all group flex flex-col">
                    {{-- Image (clickable to open modal) --}}
                    <div class="aspect-square bg-slate-100 overflow-hidden cursor-pointer" wire:click="openProductModal({{ $product->id }})">
                        <img src="{{ $product->getDisplayImageUrl() }}" alt="{{ $product->name }}"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                    </div>

                    {{-- Info --}}
                    <div class="p-4 space-y-2 flex-1 flex flex-col">
                        <div class="flex items-center gap-2 text-xs text-slate-500">
                            @if($product->category)
                                <span>{{ $product->category->name }}</span>
                            @endif
                            @if($product->category && $product->brand)
                                <span>·</span>
                            @endif
                            @if($product->brand)
                                <span>{{ $product->brand->name }}</span>
                            @endif
                        </div>

                        <h3 class="font-semibold text-slate-900 text-sm leading-tight line-clamp-2 cursor-pointer hover:text-[#ff7261] transition-colors"
                            wire:click="openProductModal({{ $product->id }})">{{ $product->name }}</h3>

                        <p class="text-lg font-bold bg-gradient-to-r from-[#ff7261] to-[#a855f7] bg-clip-text text-transparent">
                            ${{ number_format($product->getSalePriceWithTax(), 0, ',', '.') }}
                        </p>

                        <p class="text-xs {{ $product->current_stock <= $product->min_stock ? 'text-red-500' : 'text-green-600' }}">
                            {{ rtrim(rtrim(number_format($product->current_stock, 3), '0'), '.') }} disponibles
                        </p>

                        {{-- Quick Add to Cart Button --}}
                        <div class="mt-auto pt-2">
                            @auth('customer')
                                <button wire:click="quickAddToCart({{ $product->id }})"
                                    class="w-full flex items-center justify-center gap-1.5 px-3 py-2 text-xs font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea] transition-all">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"></path>
                                    </svg>
                                    Añadir al carrito
                                </button>
                            @endauth
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $products->links() }}
        </div>
    @else
        <div class="text-center py-16">
            <div class="w-16 h-16 bg-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-slate-900 mb-1">No se encontraron productos</h3>
            <p class="text-slate-500 text-sm">Intenta con otros términos de búsqueda o filtros.</p>
            @if($search !== '' || $category !== '' || $brand !== '')
                <button wire:click="clearFilters" class="mt-4 px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea] transition-all">
                    Limpiar filtros
                </button>
            @endif
        </div>
    @endif

    {{-- Product Detail Modal --}}
    @if($showProductModal && $selectedProduct)
        <div class="relative z-[100]" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="closeProductModal"></div>
            <div class="fixed inset-0 z-[101] overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-xl">
                        {{-- Close button --}}
                        <button wire:click="closeProductModal" class="absolute top-4 right-4 p-1.5 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 z-10">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-0">
                            {{-- Image --}}
                            <div class="aspect-square bg-slate-100 rounded-t-2xl sm:rounded-l-2xl sm:rounded-tr-none overflow-hidden">
                                <img src="{{ $selectedProduct->getDisplayImageUrl() }}" alt="{{ $selectedProduct->name }}" class="w-full h-full object-cover">
                            </div>

                            {{-- Info --}}
                            <div class="p-5 space-y-4 flex flex-col">
                                <div class="flex items-center gap-2 text-xs text-slate-500">
                                    @if($selectedProduct->category)
                                        <span class="px-2 py-0.5 bg-slate-100 rounded-lg">{{ $selectedProduct->category->name }}</span>
                                    @endif
                                    @if($selectedProduct->brand)
                                        <span class="px-2 py-0.5 bg-slate-100 rounded-lg">{{ $selectedProduct->brand->name }}</span>
                                    @endif
                                </div>

                                <h2 class="text-lg font-bold text-slate-900">{{ $selectedProduct->name }}</h2>

                                @if($selectedProduct->description)
                                    <p class="text-sm text-slate-600 line-clamp-3">{{ $selectedProduct->description }}</p>
                                @endif

                                <p class="text-2xl font-bold bg-gradient-to-r from-[#ff7261] to-[#a855f7] bg-clip-text text-transparent">
                                    ${{ number_format($selectedProduct->getSalePriceWithTax(), 0, ',', '.') }}
                                </p>

                                <div class="flex items-center gap-3 text-sm">
                                    <span class="{{ $selectedProduct->current_stock <= $selectedProduct->min_stock ? 'text-red-500' : 'text-green-600' }}">
                                        {{ rtrim(rtrim(number_format($selectedProduct->current_stock, 3), '0'), '.') }} disponibles
                                    </span>
                                    @if($selectedProduct->unit)
                                        <span class="text-slate-400">· {{ $selectedProduct->unit->abbreviation }}</span>
                                    @endif
                                </div>

                                {{-- Variants --}}
                                @if($selectedProduct->activeChildren->count() > 0)
                                    <div>
                                        <label class="block text-xs font-medium text-slate-700 mb-1.5">Variante</label>
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach($selectedProduct->activeChildren as $variant)
                                                <button wire:click="$set('selectedVariantId', {{ $variant->id }})"
                                                    class="px-3 py-1.5 text-xs rounded-lg border-2 transition-all
                                                        {{ $selectedVariantId === $variant->id
                                                            ? 'border-[#ff7261] bg-[#ff7261]/5 text-[#ff7261] font-medium'
                                                            : 'border-slate-200 text-slate-700 hover:border-slate-300' }}">
                                                    {{ $variant->name }}
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                {{-- Quantity --}}
                                @auth('customer')
                                    <div>
                                        <label class="block text-xs font-medium text-slate-700 mb-1.5">Cantidad</label>
                                        <div class="flex items-center gap-2">
                                            <div class="flex items-center border border-slate-300 rounded-xl overflow-hidden">
                                                <button wire:click="decrementModalQuantity" class="px-2.5 py-1.5 text-slate-600 hover:bg-slate-100 transition-colors disabled:opacity-50" {{ $modalQuantity <= 1 ? 'disabled' : '' }}>
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path></svg>
                                                </button>
                                                <input type="number" wire:model.live.debounce.300ms="modalQuantity" min="1" max="{{ (int) $this->modalMaxStock }}" class="w-14 text-center border-x border-slate-300 py-1.5 text-sm font-medium focus:outline-none">
                                                <button wire:click="incrementModalQuantity" class="px-2.5 py-1.5 text-slate-600 hover:bg-slate-100 transition-colors disabled:opacity-50" {{ $modalQuantity >= (int) $this->modalMaxStock ? 'disabled' : '' }}>
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                                </button>
                                            </div>
                                            <span class="text-xs text-slate-400">Máx: {{ rtrim(rtrim(number_format($this->modalMaxStock, 3), '0'), '.') }}</span>
                                        </div>
                                    </div>

                                    <div class="mt-auto pt-2">
                                        <button wire:click="addToCartFromModal" class="w-full flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea] transition-all">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"></path>
                                            </svg>
                                            Agregar al carrito
                                        </button>
                                    </div>
                                @endauth

                                <p class="text-xs text-slate-400">SKU: {{ $selectedProduct->sku }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Cart Sidebar --}}
    @auth('customer')
        @if($showCartSidebar)
            <div class="relative z-[90]">
                <div class="fixed inset-0 bg-slate-900/50 z-[90]" wire:click="closeCartSidebar"></div>
                <div class="fixed inset-y-0 right-0 z-[91] w-full max-w-sm bg-white shadow-xl flex flex-col">
                    {{-- Header --}}
                    <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between flex-shrink-0">
                        <h3 class="text-lg font-bold text-slate-900">Carrito ({{ $this->cartCount }})</h3>
                        <button wire:click="closeCartSidebar" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    {{-- Items --}}
                    <div class="flex-1 overflow-y-auto px-5 py-4 space-y-3">
                        @forelse($this->cartItems as $index => $item)
                            <div class="flex gap-3 p-3 bg-slate-50 rounded-xl" wire:key="sidebar-cart-{{ $index }}">
                                <div class="w-14 h-14 bg-slate-200 rounded-lg overflow-hidden flex-shrink-0">
                                    @if($item['image'])
                                        <img src="{{ asset('storage/' . $item['image']) }}" alt="{{ $item['name'] }}" class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center">
                                            <svg class="w-6 h-6 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        </div>
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-xs font-semibold text-slate-900 truncate">{{ $item['name'] }}</h4>
                                    <p class="text-xs text-slate-500">${{ number_format($item['unit_price'], 0, ',', '.') }}</p>
                                    <div class="flex items-center justify-between mt-1.5">
                                        <div class="flex items-center border border-slate-300 rounded-lg overflow-hidden">
                                            <button wire:click="updateCartQuantity({{ $index }}, {{ max(1, $item['quantity'] - 1) }})" class="px-1.5 py-0.5 text-slate-500 hover:bg-slate-100 text-xs">−</button>
                                            <span class="px-2 py-0.5 text-xs font-medium border-x border-slate-300">{{ $item['quantity'] }}</span>
                                            <button wire:click="updateCartQuantity({{ $index }}, {{ $item['quantity'] + 1 }})" class="px-1.5 py-0.5 text-slate-500 hover:bg-slate-100 text-xs">+</button>
                                        </div>
                                        <button wire:click="removeCartItem({{ $index }})" class="p-1 text-slate-400 hover:text-red-500 transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </div>
                                </div>
                                <p class="text-xs font-bold text-slate-900 flex-shrink-0">${{ number_format($item['unit_price'] * $item['quantity'], 0, ',', '.') }}</p>
                            </div>
                        @empty
                            <div class="text-center py-8">
                                <svg class="w-10 h-10 text-slate-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"></path></svg>
                                <p class="text-sm text-slate-500">Tu carrito está vacío</p>
                            </div>
                        @endforelse
                    </div>

                    {{-- Footer --}}
                    @if($this->cartCount > 0)
                        <div class="px-5 py-4 border-t border-slate-200 space-y-3 flex-shrink-0">
                            <div class="flex justify-between text-sm font-bold text-slate-900">
                                <span>Total</span>
                                <span>${{ number_format($this->cartTotal, 0, ',', '.') }}</span>
                            </div>
                            <a href="{{ route('shop.checkout') }}" class="w-full flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea] transition-all">
                                Proceder al pago
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    @endauth
</div>

<div>
    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-sm text-slate-500 mb-6">
        <a href="{{ route('shop.catalog') }}" wire:navigate class="hover:text-slate-700 transition-colors">Catálogo</a>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
        </svg>
        <span class="text-slate-900 font-medium truncate">{{ $product->name }}</span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        {{-- Image --}}
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
            <div class="aspect-[4/3] bg-slate-100">
                <img src="{{ $product->getDisplayImageUrl() }}" alt="{{ $product->name }}"
                    class="w-full h-full object-cover">
            </div>
        </div>

        {{-- Product Info --}}
        <div class="space-y-6">
            {{-- Category & Brand --}}
            <div class="flex items-center gap-2 text-sm text-slate-500">
                @if($product->category)
                    <span class="px-2 py-0.5 bg-slate-100 rounded-lg">{{ $product->category->name }}</span>
                @endif
                @if($product->brand)
                    <span class="px-2 py-0.5 bg-slate-100 rounded-lg">{{ $product->brand->name }}</span>
                @endif
            </div>

            {{-- Name --}}
            <h1 class="text-2xl font-bold text-slate-900">{{ $product->name }}</h1>

            {{-- Description --}}
            @if($product->description)
                <p class="text-slate-600 leading-relaxed">{{ $product->description }}</p>
            @endif

            {{-- Price --}}
            <div>
                @php
                    $displayPrice = $product->getSalePriceWithTax();
                    if ($selectedVariantId) {
                        $selectedVariant = $product->activeChildren->find($selectedVariantId);
                        if ($selectedVariant) {
                            $displayPrice = $selectedVariant->getSalePriceWithTax();
                        }
                    }
                @endphp
                <p class="text-3xl font-bold bg-gradient-to-r from-[#ff7261] to-[#a855f7] bg-clip-text text-transparent">
                    ${{ number_format($displayPrice, 0, ',', '.') }}
                </p>
            </div>

            {{-- Stock & Unit --}}
            <div class="flex items-center gap-4 text-sm">
                @if($product->unit)
                    <span class="text-slate-500">{{ $product->unit->name }} ({{ $product->unit->abbreviation }})</span>
                @endif
                @if($showStockInShop && $product->manages_inventory)
                    <span class="{{ $product->current_stock > 0 ? 'text-green-600' : 'text-red-500' }}">
                        {{ rtrim(rtrim(number_format($product->current_stock, 3), '0'), '.') }} disponible(s)
                    </span>
                @endif
            </div>

            {{-- Variants --}}
            @if($product->activeChildren->count() > 0)
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Variante</label>
                    <div class="flex flex-wrap gap-2">
                        @foreach($product->activeChildren as $variant)
                            <button wire:click="$set('selectedVariantId', {{ $variant->id }})"
                                class="px-4 py-2 text-sm rounded-xl border-2 transition-all
                                    {{ $selectedVariantId === $variant->id
                                        ? 'border-[#ff7261] bg-[#ff7261]/5 text-[#ff7261] font-medium'
                                        : 'border-slate-200 text-slate-700 hover:border-slate-300' }}">
                                {{ $variant->name }}
                                <span class="text-xs {{ $selectedVariantId === $variant->id ? 'text-[#ff7261]/70' : 'text-slate-400' }}">
                                    · ${{ number_format($variant->getSalePriceWithTax(), 0, ',', '.') }}
                                </span>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Quantity Selector --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Cantidad</label>
                <div class="flex items-center gap-3">
                    <div class="flex items-center border border-slate-300 rounded-xl overflow-hidden">
                        <button wire:click="decrementQuantity"
                            class="px-3 py-2 text-slate-600 hover:bg-slate-100 transition-colors disabled:opacity-50"
                            {{ $quantity <= 1 ? 'disabled' : '' }}>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                            </svg>
                        </button>
                        <input type="number" wire:model.live.debounce.300ms="quantity" min="1" {{ $product->manages_inventory ? 'max=' . (int) $this->maxStock : '' }}
                            class="w-16 text-center border-x border-slate-300 py-2 text-sm font-medium focus:outline-none">
                        <button wire:click="incrementQuantity"
                            class="px-3 py-2 text-slate-600 hover:bg-slate-100 transition-colors disabled:opacity-50"
                            {{ $product->manages_inventory && $quantity >= (int) $this->maxStock ? 'disabled' : '' }}>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Add to Cart Button --}}
            <button wire:click="addToCart" wire:loading.attr="disabled"
                class="w-full px-6 py-3 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea] transition-all disabled:opacity-50 flex items-center justify-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"></path>
                </svg>
                <span wire:loading.remove wire:target="addToCart">Agregar al carrito</span>
                <span wire:loading wire:target="addToCart">Agregando...</span>
            </button>

            {{-- SKU --}}
            <p class="text-xs text-slate-400">SKU: {{ $product->sku }}</p>
        </div>
    </div>
</div>

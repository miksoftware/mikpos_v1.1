<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Kardex de Inventario</h1>
            <p class="text-slate-500 mt-1">Control y seguimiento del inventario de productos</p>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-7 gap-4">
        {{-- Total Products --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Total Productos</p>
                    <p class="text-xl font-bold text-slate-800">{{ number_format($totalProducts) }}</p>
                </div>
            </div>
        </div>

        {{-- With Stock --}}
        <button wire:click="$set('stockFilter', 'positive')" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 hover:border-green-300 transition-colors text-left {{ $stockFilter === 'positive' ? 'ring-2 ring-green-500' : '' }}">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-green-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Con Existencias</p>
                    <p class="text-xl font-bold text-green-600">{{ number_format($productsWithStock) }}</p>
                </div>
            </div>
        </button>

        {{-- Zero Stock --}}
        <button wire:click="$set('stockFilter', 'zero')" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 hover:border-amber-300 transition-colors text-left {{ $stockFilter === 'zero' ? 'ring-2 ring-amber-500' : '' }}">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Sin Existencias</p>
                    <p class="text-xl font-bold text-amber-600">{{ number_format($productsZeroStock) }}</p>
                </div>
            </div>
        </button>

        {{-- Negative Stock --}}
        <button wire:click="$set('stockFilter', 'negative')" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 hover:border-red-300 transition-colors text-left {{ $stockFilter === 'negative' ? 'ring-2 ring-red-500' : '' }}">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-red-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Stock Negativo</p>
                    <p class="text-xl font-bold text-red-600">{{ number_format($productsNegativeStock) }}</p>
                </div>
            </div>
        </button>

        {{-- Inventory Value --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-purple-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Valor Inventario</p>
                    <p class="text-lg font-bold text-purple-600">${{ number_format($totalInventoryValue, 0) }}</p>
                </div>
            </div>
        </div>

        {{-- Inventory Cost --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Costo Inventario</p>
                    <p class="text-lg font-bold text-indigo-600">${{ number_format($totalInventoryCost, 0) }}</p>
                </div>
            </div>
        </div>

        {{-- Potential Profit --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 col-span-2 lg:col-span-1">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Ganancia Potencial</p>
                    <p class="text-lg font-bold text-emerald-600">${{ number_format($totalPotentialProfit, 0) }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts Section --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Stock Distribution Pie Chart --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-[#ff7261]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path>
                </svg>
                Distribución de Stock
            </h3>
            <div class="flex items-center justify-center">
                <div class="relative w-48 h-48" x-data="{ 
                    total: {{ $totalProducts ?: 1 }},
                    positive: {{ $productsWithStock }},
                    zero: {{ $productsZeroStock }},
                    negative: {{ $productsNegativeStock }}
                }">
                    <svg viewBox="0 0 36 36" class="w-full h-full">
                        @php
                            $total = $totalProducts ?: 1;
                            $positivePercent = ($productsWithStock / $total) * 100;
                            $zeroPercent = ($productsZeroStock / $total) * 100;
                            $negativePercent = ($productsNegativeStock / $total) * 100;
                            $positiveOffset = 0;
                            $zeroOffset = $positivePercent;
                            $negativeOffset = $positivePercent + $zeroPercent;
                        @endphp
                        <circle cx="18" cy="18" r="15.9" fill="none" stroke="#e2e8f0" stroke-width="3"></circle>
                        @if($productsWithStock > 0)
                        <circle cx="18" cy="18" r="15.9" fill="none" stroke="#22c55e" stroke-width="3" 
                            stroke-dasharray="{{ $positivePercent }} {{ 100 - $positivePercent }}" 
                            stroke-dashoffset="25" class="transition-all duration-500"></circle>
                        @endif
                        @if($productsZeroStock > 0)
                        <circle cx="18" cy="18" r="15.9" fill="none" stroke="#f59e0b" stroke-width="3" 
                            stroke-dasharray="{{ $zeroPercent }} {{ 100 - $zeroPercent }}" 
                            stroke-dashoffset="{{ 25 - $positivePercent }}" class="transition-all duration-500"></circle>
                        @endif
                        @if($productsNegativeStock > 0)
                        <circle cx="18" cy="18" r="15.9" fill="none" stroke="#ef4444" stroke-width="3" 
                            stroke-dasharray="{{ $negativePercent }} {{ 100 - $negativePercent }}" 
                            stroke-dashoffset="{{ 25 - $positivePercent - $zeroPercent }}" class="transition-all duration-500"></circle>
                        @endif
                    </svg>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <div class="text-center">
                            <p class="text-2xl font-bold text-slate-800">{{ $totalProducts }}</p>
                            <p class="text-xs text-slate-500">productos</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex justify-center gap-6 mt-4">
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full bg-green-500"></div>
                    <span class="text-sm text-slate-600">Con stock ({{ $productsWithStock }})</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full bg-amber-500"></div>
                    <span class="text-sm text-slate-600">Sin stock ({{ $productsZeroStock }})</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full bg-red-500"></div>
                    <span class="text-sm text-slate-600">Negativo ({{ $productsNegativeStock }})</span>
                </div>
            </div>
        </div>

        {{-- Stock by Category Bar Chart --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-[#a855f7]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                Stock por Categoría
            </h3>
            <div class="space-y-3">
                @php
                    $maxStock = collect($stockByCategory)->max('total_stock') ?: 1;
                @endphp
                @forelse($stockByCategory as $category)
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-slate-600 truncate">{{ $category['category_name'] }}</span>
                        <span class="font-medium text-slate-800">{{ number_format($category['total_stock']) }} uds</span>
                    </div>
                    <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-full transition-all duration-500" 
                            style="width: {{ ($category['total_stock'] / $maxStock) * 100 }}%"></div>
                    </div>
                </div>
                @empty
                <p class="text-slate-400 text-center py-4">No hay datos disponibles</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Two Column Layout: Top Value & Low Stock --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Top Value Products --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                </svg>
                Top 10 Mayor Valor en Inventario
            </h3>
            <div class="space-y-2">
                @forelse($topValueProducts as $index => $product)
                <div class="flex items-center gap-3 p-2 rounded-lg hover:bg-slate-50 transition-colors">
                    <span class="w-6 h-6 rounded-full bg-gradient-to-r from-[#ff7261] to-[#a855f7] text-white text-xs flex items-center justify-center font-bold">{{ $index + 1 }}</span>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-slate-800 truncate">{{ $product['name'] }}</p>
                        <p class="text-xs text-slate-500">{{ $product['sku'] }} · {{ $product['current_stock'] }} uds</p>
                    </div>
                    <span class="text-sm font-bold text-purple-600">${{ number_format($product['inventory_value'], 0) }}</span>
                </div>
                @empty
                <p class="text-slate-400 text-center py-4">No hay productos con stock</p>
                @endforelse
            </div>
        </div>

        {{-- Low Stock Alert --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                Productos con Stock Bajo
            </h3>
            <div class="space-y-2">
                @forelse($lowStockProducts as $product)
                <div class="flex items-center gap-3 p-2 rounded-lg bg-amber-50 border border-amber-100">
                    <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01"></path>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-slate-800 truncate">{{ $product['name'] }}</p>
                        <p class="text-xs text-slate-500">{{ $product['sku'] }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-bold {{ $product['current_stock'] < 0 ? 'text-red-600' : 'text-amber-600' }}">{{ $product['current_stock'] }}</p>
                        <p class="text-xs text-slate-400">mín: {{ $product['min_stock'] }}</p>
                    </div>
                </div>
                @empty
                <div class="text-center py-4">
                    <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-2">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <p class="text-slate-500 text-sm">No hay productos con stock bajo</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
        <div class="flex flex-col lg:flex-row gap-4">
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <input wire:model.live.debounce.300ms="search" type="text" 
                    class="block w-full pl-10 pr-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 placeholder-slate-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] transition-all sm:text-sm" 
                    placeholder="Buscar por nombre, SKU o código de barras...">
            </div>
            <div class="flex flex-wrap gap-3">
                @if($isSuperAdmin)
                <select wire:model.live="selectedBranchId" class="px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] sm:text-sm min-w-[150px]">
                    <option value="">Todas las sucursales</option>
                    @foreach($branches as $branch)
                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
                @endif
                <select wire:model.live="selectedCategoryId" class="px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] sm:text-sm min-w-[140px]">
                    <option value="">Todas las categorías</option>
                    @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
                <select wire:model.live="selectedBrandId" class="px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] sm:text-sm min-w-[130px]">
                    <option value="">Todas las marcas</option>
                    @foreach($brands as $brand)
                    <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                    @endforeach
                </select>
                <select wire:model.live="stockFilter" class="px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] sm:text-sm min-w-[140px]">
                    <option value="all">Todo el stock</option>
                    <option value="positive">Con existencias</option>
                    <option value="zero">Sin existencias</option>
                    <option value="negative">Stock negativo</option>
                </select>
                @if($search || $stockFilter !== 'all' || $selectedCategoryId || $selectedBrandId || ($isSuperAdmin && $selectedBranchId))
                <button wire:click="clearFilters" class="px-3 py-2.5 text-slate-500 hover:text-slate-700 hover:bg-slate-100 rounded-xl transition-colors text-sm font-medium">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Products Table --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Producto</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Categoría</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Marca</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold text-slate-500 uppercase">Stock</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-500 uppercase">P. Compra</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-500 uppercase">P. Venta</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-500 uppercase">Valor Stock</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-500 uppercase">Ganancia</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold text-slate-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($products as $product)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                @if($product->image)
                                <img src="{{ Storage::url($product->image) }}" class="w-10 h-10 rounded-lg object-cover">
                                @else
                                <div class="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                    </svg>
                                </div>
                                @endif
                                <div>
                                    <p class="font-medium text-slate-800">{{ $product->name }}</p>
                                    <p class="text-sm text-slate-500">{{ $product->sku }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-slate-600">{{ $product->category?->name ?? '-' }}</td>
                        <td class="px-6 py-4 text-slate-600">{{ $product->brand?->name ?? '-' }}</td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-sm font-medium
                                {{ $product->current_stock > 0 ? 'bg-green-100 text-green-700' : '' }}
                                {{ $product->current_stock == 0 ? 'bg-amber-100 text-amber-700' : '' }}
                                {{ $product->current_stock < 0 ? 'bg-red-100 text-red-700' : '' }}
                            ">
                                {{ $product->current_stock }} {{ $product->unit?->abbreviation ?? 'und' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right text-slate-600">${{ number_format($product->purchase_price, 2) }}</td>
                        <td class="px-6 py-4 text-right text-slate-600">${{ number_format($product->sale_price, 2) }}</td>
                        <td class="px-6 py-4 text-right font-medium {{ $product->current_stock > 0 ? 'text-purple-600' : 'text-slate-400' }}">
                            ${{ number_format($product->current_stock * $product->sale_price, 0) }}
                        </td>
                        <td class="px-6 py-4 text-right font-medium {{ $product->current_stock > 0 ? 'text-emerald-600' : 'text-slate-400' }}">
                            @php
                                $profit = ($product->sale_price - $product->purchase_price) * $product->current_stock;
                            @endphp
                            ${{ number_format($profit, 0) }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            <button wire:click="viewProductKardex({{ $product->id }})" class="p-2 text-slate-400 hover:text-[#a855f7] hover:bg-[#a855f7]/10 rounded-lg transition-colors" title="Ver Kardex">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                                </svg>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-6 py-12 text-center">
                            <svg class="w-12 h-12 text-slate-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                            <p class="text-slate-500">No se encontraron productos</p>
                            @if($search || $stockFilter !== 'all' || $selectedCategoryId || $selectedBrandId)
                            <button wire:click="clearFilters" class="mt-2 text-[#ff7261] hover:underline text-sm">Limpiar filtros</button>
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if($products->hasPages())
    <div class="mt-6">
        {{ $products->links() }}
    </div>
    @endif

    {{-- Product Kardex Detail Modal --}}
    @if($isDetailModalOpen && $selectedProduct)
    <div class="relative z-[100]">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="closeDetailModal"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-4xl bg-white rounded-2xl shadow-xl">
                    {{-- Header --}}
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-gradient-to-r from-[#ff7261]/10 to-[#a855f7]/10 rounded-t-2xl">
                        <div class="flex items-center gap-4">
                            @if($selectedProduct->image)
                            <img src="{{ Storage::url($selectedProduct->image) }}" class="w-14 h-14 rounded-xl object-cover">
                            @else
                            <div class="w-14 h-14 rounded-xl bg-slate-100 flex items-center justify-center">
                                <svg class="w-7 h-7 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                </svg>
                            </div>
                            @endif
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">{{ $selectedProduct->name }}</h3>
                                <p class="text-sm text-slate-500">{{ $selectedProduct->sku }} · {{ $selectedProduct->category?->name ?? 'Sin categoría' }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="text-right">
                                <p class="text-sm text-slate-500">Stock Actual</p>
                                <p class="text-2xl font-bold {{ $selectedProduct->current_stock > 0 ? 'text-green-600' : ($selectedProduct->current_stock < 0 ? 'text-red-600' : 'text-amber-600') }}">
                                    {{ $selectedProduct->current_stock }} {{ $selectedProduct->unit?->abbreviation ?? 'und' }}
                                </p>
                            </div>
                            <button wire:click="closeDetailModal" class="p-2 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-white/50">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Content --}}
                    <div class="px-6 py-4 max-h-[60vh] overflow-y-auto">
                        {{-- Date Filters for Movements --}}
                        <div class="flex flex-wrap items-center gap-4 mb-4 p-3 bg-slate-50 rounded-xl">
                            <span class="text-sm font-medium text-slate-600">Filtrar movimientos:</span>
                            <div class="flex items-center gap-2">
                                <label class="text-sm text-slate-500">Desde:</label>
                                <input wire:model.live="dateFrom" type="date" 
                                    class="px-3 py-1.5 border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm">
                            </div>
                            <div class="flex items-center gap-2">
                                <label class="text-sm text-slate-500">Hasta:</label>
                                <input wire:model.live="dateTo" type="date" 
                                    class="px-3 py-1.5 border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm">
                            </div>
                            @if($dateFrom || $dateTo)
                            <button wire:click="$set('dateFrom', null); $set('dateTo', null)" class="text-sm text-slate-500 hover:text-slate-700">
                                Limpiar fechas
                            </button>
                            @endif
                        </div>

                        <h4 class="font-semibold text-slate-800 mb-3 flex items-center gap-2">
                            <svg class="w-5 h-5 text-[#a855f7]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            Movimientos de Inventario
                            @if($dateFrom || $dateTo)
                            <span class="text-xs font-normal text-slate-500">
                                ({{ $dateFrom ? \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') : 'Inicio' }} - {{ $dateTo ? \Carbon\Carbon::parse($dateTo)->format('d/m/Y') : 'Hoy' }})
                            </span>
                            @endif
                        </h4>

                        @if(count($productMovements) > 0)
                        <div class="border border-slate-200 rounded-xl overflow-hidden">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Fecha</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Documento</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 uppercase">Tipo</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 uppercase">Cantidad</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 uppercase">Stock Antes</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 uppercase">Stock Después</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Usuario</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200">
                                    @foreach($productMovements as $movement)
                                    <tr class="hover:bg-slate-50/50">
                                        <td class="px-4 py-3 text-sm text-slate-600">{{ $movement['date'] }}</td>
                                        <td class="px-4 py-3">
                                            <p class="text-sm font-medium text-slate-800">{{ $movement['document'] }}</p>
                                            <p class="text-xs text-slate-500">{{ $movement['document_number'] }}</p>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $movement['type'] === 'in' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                                {{ $movement['type'] === 'in' ? 'Entrada' : 'Salida' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center font-medium {{ $movement['type'] === 'in' ? 'text-green-600' : 'text-red-600' }}">
                                            {{ $movement['type'] === 'in' ? '+' : '-' }}{{ $movement['quantity'] }}
                                        </td>
                                        <td class="px-4 py-3 text-center text-slate-600">{{ $movement['stock_before'] }}</td>
                                        <td class="px-4 py-3 text-center font-medium text-slate-800">{{ $movement['stock_after'] }}</td>
                                        <td class="px-4 py-3 text-sm text-slate-600">{{ $movement['user'] }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <div class="text-center py-8">
                            <svg class="w-12 h-12 text-slate-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            <p class="text-slate-500">No hay movimientos registrados para este producto</p>
                        </div>
                        @endif
                    </div>

                    {{-- Footer --}}
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end rounded-b-2xl">
                        <button wire:click="closeDetailModal" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

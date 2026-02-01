<div class="p-6">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Header -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Productos Vendidos</h1>
            <p class="text-slate-500 text-sm mt-1">Análisis detallado de ventas por producto</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('reports.products-sold.excel', ['start_date' => $startDate, 'end_date' => $endDate, 'branch_id' => $selectedBranchId, 'category_id' => $selectedCategoryId]) }}" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Excel
            </a>
            <a href="{{ route('reports.products-sold.pdf', ['start_date' => $startDate, 'end_date' => $endDate, 'branch_id' => $selectedBranchId, 'category_id' => $selectedCategoryId]) }}" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea] transition shadow-lg shadow-purple-500/25">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                PDF
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-7 gap-4">
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Período</label>
                <select wire:model.live="dateRange" class="w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm">
                    <option value="today">Hoy</option>
                    <option value="yesterday">Ayer</option>
                    <option value="week">Esta semana</option>
                    <option value="month">Este mes</option>
                    <option value="last_month">Mes anterior</option>
                    <option value="quarter">Este trimestre</option>
                    <option value="year">Este año</option>
                    <option value="custom">Personalizado</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Desde</label>
                <input wire:model.live="startDate" type="date" class="w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm" @if($dateRange !== 'custom') disabled @endif>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Hasta</label>
                <input wire:model.live="endDate" type="date" class="w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm" @if($dateRange !== 'custom') disabled @endif>
            </div>
            @if($isSuperAdmin)
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Sucursal</label>
                <select wire:model.live="selectedBranchId" class="w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm">
                    <option value="">Todas</option>
                    @foreach($branches as $branch)
                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Categoría</label>
                <select wire:model.live="selectedCategoryId" class="w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm">
                    <option value="">Todas</option>
                    @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Marca</label>
                <select wire:model.live="selectedBrandId" class="w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm">
                    <option value="">Todas</option>
                    @foreach($brands as $brand)
                    <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Buscar</label>
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Producto, SKU..." class="w-full pl-9 pr-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm">
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500">Ingresos</p>
                    <p class="text-xl font-bold text-slate-800 mt-1">${{ number_format($totalRevenue, 0, ',', '.') }}</p>
                </div>
                <div class="w-10 h-10 bg-gradient-to-br from-green-400 to-green-600 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500">Unidades</p>
                    <p class="text-xl font-bold text-slate-800 mt-1">{{ number_format($totalQuantity, 0, ',', '.') }}</p>
                </div>
                <div class="w-10 h-10 bg-gradient-to-br from-[#ff7261] to-[#a855f7] rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500">Transacciones</p>
                    <p class="text-xl font-bold text-slate-800 mt-1">{{ number_format($totalTransactions, 0, ',', '.') }}</p>
                </div>
                <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-blue-600 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500">Ticket Prom.</p>
                    <p class="text-xl font-bold text-slate-800 mt-1">${{ number_format($averageTicket, 0, ',', '.') }}</p>
                </div>
                <div class="w-10 h-10 bg-gradient-to-br from-amber-400 to-amber-600 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500">Precio Prom.</p>
                    <p class="text-xl font-bold text-slate-800 mt-1">${{ number_format($averageUnitPrice, 0, ',', '.') }}</p>
                </div>
                <div class="w-10 h-10 bg-gradient-to-br from-indigo-400 to-indigo-600 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500">Productos</p>
                    <p class="text-xl font-bold text-slate-800 mt-1">{{ number_format($uniqueProducts, 0, ',', '.') }}</p>
                </div>
                <div class="w-10 h-10 bg-gradient-to-br from-pink-400 to-pink-600 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 1: Trend & Top Products -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <h3 class="text-lg font-semibold text-slate-800 mb-4">Tendencia de Ventas</h3>
            <div class="h-64" id="salesTrendContainer">
                @if(count($salesByDay) > 0)
                <canvas id="salesTrendChart"></canvas>
                @else
                <div class="flex items-center justify-center h-full text-slate-400">
                    <div class="text-center">
                        <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path></svg>
                        <p>No hay datos para mostrar</p>
                    </div>
                </div>
                @endif
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <h3 class="text-lg font-semibold text-slate-800 mb-4">Top 10 Productos</h3>
            <div class="h-64" id="topProductsContainer">
                @if(count($topProducts) > 0)
                <canvas id="topProductsChart"></canvas>
                @else
                <div class="flex items-center justify-center h-full text-slate-400">
                    <div class="text-center">
                        <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                        <p>No hay datos para mostrar</p>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Charts Row 2: Category, Brand, Subcategory -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <h3 class="text-lg font-semibold text-slate-800 mb-4">Por Categoría</h3>
            <div class="h-64" id="categoryContainer">
                @if(count($salesByCategory) > 0)
                <canvas id="categoryChart"></canvas>
                @else
                <div class="flex items-center justify-center h-full text-slate-400">
                    <div class="text-center">
                        <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path></svg>
                        <p>No hay datos para mostrar</p>
                    </div>
                </div>
                @endif
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <h3 class="text-lg font-semibold text-slate-800 mb-4">Por Marca</h3>
            <div class="h-64" id="brandContainer">
                @if(count($salesByBrand) > 0)
                <canvas id="brandChart"></canvas>
                @else
                <div class="flex items-center justify-center h-full text-slate-400">
                    <div class="text-center">
                        <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path></svg>
                        <p>No hay datos para mostrar</p>
                    </div>
                </div>
                @endif
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <h3 class="text-lg font-semibold text-slate-800 mb-4">Por Subcategoría</h3>
            <div class="h-64" id="subcategoryContainer">
                @if(count($salesBySubcategory) > 0)
                <canvas id="subcategoryChart"></canvas>
                @else
                <div class="flex items-center justify-center h-full text-slate-400">
                    <div class="text-center">
                        <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path></svg>
                        <p>No hay datos para mostrar</p>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Charts Row 3: Payment Methods, Hour, Day of Week -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <h3 class="text-lg font-semibold text-slate-800 mb-4">Métodos de Pago</h3>
            <div class="h-64" id="paymentContainer">
                @if(count($salesByPaymentMethod) > 0)
                <canvas id="paymentChart"></canvas>
                @else
                <div class="flex items-center justify-center h-full text-slate-400">
                    <div class="text-center">
                        <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                        <p>No hay datos para mostrar</p>
                    </div>
                </div>
                @endif
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <h3 class="text-lg font-semibold text-slate-800 mb-4">Ventas por Hora</h3>
            <div class="h-64" id="hourContainer">
                @if(count($salesByHour) > 0)
                <canvas id="hourChart"></canvas>
                @else
                <div class="flex items-center justify-center h-full text-slate-400">
                    <div class="text-center">
                        <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <p>No hay datos para mostrar</p>
                    </div>
                </div>
                @endif
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <h3 class="text-lg font-semibold text-slate-800 mb-4">Ventas por Día</h3>
            <div class="h-64" id="dayContainer">
                @if(count($salesByDayOfWeek) > 0)
                <canvas id="dayChart"></canvas>
                @else
                <div class="flex items-center justify-center h-full text-slate-400">
                    <div class="text-center">
                        <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        <p>No hay datos para mostrar</p>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Top Products List -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 mb-6">
        <h3 class="text-lg font-semibold text-slate-800 mb-4">Ranking de Productos</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            @forelse($topProducts as $index => $product)
            <div class="flex items-center gap-4 p-3 rounded-xl {{ $index < 3 ? 'bg-gradient-to-r from-[#ff7261]/5 to-[#a855f7]/5' : 'bg-slate-50' }}">
                <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm {{ $index === 0 ? 'bg-yellow-400 text-yellow-900' : ($index === 1 ? 'bg-slate-300 text-slate-700' : ($index === 2 ? 'bg-amber-600 text-white' : 'bg-slate-200 text-slate-600')) }}">
                    {{ $index + 1 }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-slate-800 truncate text-sm">{{ $product['product_name'] }}</p>
                    <p class="text-xs text-slate-500">{{ $product['product_sku'] }}</p>
                </div>
                <div class="text-right">
                    <p class="font-semibold text-slate-800">{{ number_format($product['total_quantity']) }} uds</p>
                    <p class="text-xs text-slate-500">${{ number_format($product['total_revenue'], 0, ',', '.') }}</p>
                </div>
            </div>
            @empty
            <div class="col-span-2 flex items-center justify-center py-8 text-slate-400">
                <div class="text-center">
                    <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                    <p>No hay datos para mostrar</p>
                </div>
            </div>
            @endforelse
        </div>
    </div>

    <!-- Detailed Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200">
            <h3 class="text-lg font-semibold text-slate-800">Detalle de Ventas</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            <button wire:click="sortByColumn('date')" class="flex items-center gap-1 hover:text-slate-700">
                                Fecha
                                @if($sortBy === 'date')
                                <svg class="w-4 h-4 {{ $sortDirection === 'asc' ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                @endif
                            </button>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Factura</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            <button wire:click="sortByColumn('product')" class="flex items-center gap-1 hover:text-slate-700">
                                Producto
                                @if($sortBy === 'product')
                                <svg class="w-4 h-4 {{ $sortDirection === 'asc' ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                @endif
                            </button>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Cliente</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            <button wire:click="sortByColumn('quantity')" class="flex items-center gap-1 hover:text-slate-700 mx-auto">
                                Cant.
                                @if($sortBy === 'quantity')
                                <svg class="w-4 h-4 {{ $sortDirection === 'asc' ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                @endif
                            </button>
                        </th>
                        <th class="px-6 py-4 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">P. Unit.</th>
                        <th class="px-6 py-4 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            <button wire:click="sortByColumn('total')" class="flex items-center gap-1 hover:text-slate-700 ml-auto">
                                Total
                                @if($sortBy === 'total')
                                <svg class="w-4 h-4 {{ $sortDirection === 'asc' ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                @endif
                            </button>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($items as $item)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-4">
                            <span class="text-sm text-slate-600">{{ $item->sale->created_at->format('d/m/Y H:i') }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm font-mono text-[#a855f7]">{{ $item->sale->invoice_number }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div>
                                <p class="font-medium text-slate-800 text-sm">{{ $item->product_name }}</p>
                                <p class="text-xs text-slate-500">{{ $item->product_sku }}</p>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-slate-600">{{ $item->sale->customer?->full_name ?? 'Consumidor Final' }}</span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-[#a855f7]/10 text-[#a855f7]">
                                {{ $item->quantity }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="text-sm text-slate-600">${{ number_format($item->unit_price, 0, ',', '.') }}</span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="text-sm font-semibold text-slate-800">${{ number_format($item->total, 0, ',', '.') }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mb-4">
                                    <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                                <p class="text-slate-500 font-medium">No se encontraron ventas</p>
                                <p class="text-slate-400 text-sm">Ajusta los filtros para ver resultados</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($items->hasPages())
        <div class="px-6 py-4 border-t border-slate-200">
            {{ $items->links() }}
        </div>
        @endif
    </div>

    <!-- Charts JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const chartColors = [
                'rgba(255,114,97,0.8)',
                'rgba(168,85,247,0.8)',
                'rgba(59,130,246,0.8)',
                'rgba(16,185,129,0.8)',
                'rgba(245,158,11,0.8)',
                'rgba(239,68,68,0.8)',
                'rgba(99,102,241,0.8)',
                'rgba(236,72,153,0.8)',
                'rgba(20,184,166,0.8)',
                'rgba(132,204,22,0.8)'
            ];

            // Sales Trend Chart
            @if(count($salesByDay) > 0)
            const trendCtx = document.getElementById('salesTrendChart');
            if (trendCtx) {
                new Chart(trendCtx, {
                    type: 'line',
                    data: {
                        labels: @json(collect($salesByDay)->pluck('label')),
                        datasets: [{
                            label: 'Ingresos',
                            data: @json(collect($salesByDay)->pluck('revenue')),
                            borderColor: 'rgba(255, 114, 97, 1)',
                            backgroundColor: 'rgba(255, 114, 97, 0.1)',
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: 'rgba(255, 114, 97, 1)',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
                            x: { grid: { display: false } }
                        }
                    }
                });
            }
            @endif

            // Top Products Chart
            @if(count($topProducts) > 0)
            const topCtx = document.getElementById('topProductsChart');
            if (topCtx) {
                new Chart(topCtx, {
                    type: 'bar',
                    data: {
                        labels: @json(collect($topProducts)->pluck('product_name')->map(fn($n) => strlen($n) > 20 ? substr($n, 0, 20) . '...' : $n)),
                        datasets: [{
                            label: 'Cantidad',
                            data: @json(collect($topProducts)->pluck('total_quantity')),
                            backgroundColor: 'rgba(168, 85, 247, 0.8)',
                            borderRadius: 6,
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            x: { beginAtZero: true, grid: { display: false } },
                            y: { grid: { display: false } }
                        }
                    }
                });
            }
            @endif

            // Category Chart (Doughnut)
            @if(count($salesByCategory) > 0)
            const catCtx = document.getElementById('categoryChart');
            if (catCtx) {
                new Chart(catCtx, {
                    type: 'doughnut',
                    data: {
                        labels: @json(collect($salesByCategory)->pluck('category_name')),
                        datasets: [{
                            data: @json(collect($salesByCategory)->pluck('revenue')),
                            backgroundColor: chartColors,
                            borderWidth: 0,
                            hoverOffset: 10
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '60%',
                        plugins: {
                            legend: { position: 'right', labels: { usePointStyle: true, padding: 10, font: { size: 10 } } }
                        }
                    }
                });
            }
            @endif

            // Brand Chart (Pie)
            @if(count($salesByBrand) > 0)
            const brandCtx = document.getElementById('brandChart');
            if (brandCtx) {
                new Chart(brandCtx, {
                    type: 'pie',
                    data: {
                        labels: @json(collect($salesByBrand)->pluck('brand_name')),
                        datasets: [{
                            data: @json(collect($salesByBrand)->pluck('revenue')),
                            backgroundColor: chartColors,
                            borderWidth: 0,
                            hoverOffset: 10
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'right', labels: { usePointStyle: true, padding: 10, font: { size: 10 } } }
                        }
                    }
                });
            }
            @endif

            // Subcategory Chart (Polar Area)
            @if(count($salesBySubcategory) > 0)
            const subCtx = document.getElementById('subcategoryChart');
            if (subCtx) {
                new Chart(subCtx, {
                    type: 'polarArea',
                    data: {
                        labels: @json(collect($salesBySubcategory)->pluck('subcategory_name')),
                        datasets: [{
                            data: @json(collect($salesBySubcategory)->pluck('revenue')),
                            backgroundColor: chartColors.map(c => c.replace('0.8', '0.6')),
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'right', labels: { usePointStyle: true, padding: 8, font: { size: 9 } } }
                        }
                    }
                });
            }
            @endif

            // Payment Methods Chart (Horizontal Bar)
            @if(count($salesByPaymentMethod) > 0)
            const payCtx = document.getElementById('paymentChart');
            if (payCtx) {
                new Chart(payCtx, {
                    type: 'bar',
                    data: {
                        labels: @json(collect($salesByPaymentMethod)->pluck('method_name')),
                        datasets: [{
                            label: 'Monto',
                            data: @json(collect($salesByPaymentMethod)->pluck('total_amount')),
                            backgroundColor: chartColors,
                            borderRadius: 6,
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            x: { beginAtZero: true, grid: { display: false } },
                            y: { grid: { display: false } }
                        }
                    }
                });
            }
            @endif

            // Sales by Hour Chart (Bar)
            @if(count($salesByHour) > 0)
            const hourCtx = document.getElementById('hourChart');
            if (hourCtx) {
                new Chart(hourCtx, {
                    type: 'bar',
                    data: {
                        labels: @json(collect($salesByHour)->pluck('hour')),
                        datasets: [{
                            label: 'Ingresos',
                            data: @json(collect($salesByHour)->pluck('revenue')),
                            backgroundColor: 'rgba(59, 130, 246, 0.7)',
                            borderRadius: 4,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
                            x: { grid: { display: false } }
                        }
                    }
                });
            }
            @endif

            // Sales by Day of Week Chart (Radar)
            @if(count($salesByDayOfWeek) > 0)
            const dayCtx = document.getElementById('dayChart');
            if (dayCtx) {
                new Chart(dayCtx, {
                    type: 'radar',
                    data: {
                        labels: @json(collect($salesByDayOfWeek)->pluck('day')),
                        datasets: [{
                            label: 'Ingresos',
                            data: @json(collect($salesByDayOfWeek)->pluck('revenue')),
                            backgroundColor: 'rgba(168, 85, 247, 0.2)',
                            borderColor: 'rgba(168, 85, 247, 1)',
                            borderWidth: 2,
                            pointBackgroundColor: 'rgba(168, 85, 247, 1)',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            r: {
                                beginAtZero: true,
                                grid: { color: 'rgba(0,0,0,0.05)' },
                                angleLines: { color: 'rgba(0,0,0,0.05)' }
                            }
                        }
                    }
                });
            }
            @endif
        });
    </script>
</div>
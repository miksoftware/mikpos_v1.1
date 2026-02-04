<div class="p-6" wire:ignore.self>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Header -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Comisiones</h1>
            <p class="text-slate-500 text-sm mt-1">Análisis de comisiones por vendedor y producto</p>
        </div>
        <button wire:click="exportPdf" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea] transition shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            Exportar PDF
        </button>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7 gap-4">
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
                <label class="block text-xs font-medium text-slate-500 mb-1">Vendedor</label>
                <select wire:model.live="selectedUserId" class="w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm">
                    <option value="">Todos</option>
                    @foreach($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
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
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500">Total Comisiones</p>
                    <p class="text-xl font-bold text-green-600 mt-1">${{ number_format($totalCommissions, 0, ',', '.') }}</p>
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
                    <p class="text-xs font-medium text-slate-500">Ventas con Comisión</p>
                    <p class="text-xl font-bold text-slate-800 mt-1">${{ number_format($totalSales, 0, ',', '.') }}</p>
                </div>
                <div class="w-10 h-10 bg-gradient-to-br from-[#ff7261] to-[#a855f7] rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
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
                    <p class="text-xs font-medium text-slate-500">Items Vendidos</p>
                    <p class="text-xl font-bold text-slate-800 mt-1">{{ number_format($totalItemsSold, 0, ',', '.') }}</p>
                </div>
                <div class="w-10 h-10 bg-gradient-to-br from-amber-400 to-amber-600 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500">% Comisión Prom.</p>
                    <p class="text-xl font-bold text-slate-800 mt-1">{{ number_format($averageCommissionRate, 2) }}%</p>
                </div>
                <div class="w-10 h-10 bg-gradient-to-br from-indigo-400 to-indigo-600 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 1: Trend & By User -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <h3 class="text-lg font-semibold text-slate-800 mb-4">Tendencia de Comisiones</h3>
            <div class="h-64" wire:ignore>
                <div id="trendEmpty" class="flex items-center justify-center h-full text-slate-400 {{ count($commissionsByDay) > 0 ? 'hidden' : '' }}">
                    <div class="text-center">
                        <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path></svg>
                        <p>No hay datos para mostrar</p>
                    </div>
                </div>
                <canvas id="trendChart" class="{{ count($commissionsByDay) > 0 ? '' : 'hidden' }}"></canvas>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <h3 class="text-lg font-semibold text-slate-800 mb-4">Comisiones por Vendedor</h3>
            <div class="h-64" wire:ignore>
                <div id="userEmpty" class="flex items-center justify-center h-full text-slate-400 {{ count($commissionsByUser) > 0 ? 'hidden' : '' }}">
                    <div class="text-center">
                        <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        <p>No hay datos para mostrar</p>
                    </div>
                </div>
                <canvas id="userChart" class="{{ count($commissionsByUser) > 0 ? '' : 'hidden' }}"></canvas>
            </div>
        </div>
    </div>

    <!-- Charts Row 2: Products & Category -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <h3 class="text-lg font-semibold text-slate-800 mb-4">Top 10 Productos con Comisión</h3>
            <div class="h-64" wire:ignore>
                <div id="productEmpty" class="flex items-center justify-center h-full text-slate-400 {{ count($commissionsByProduct) > 0 ? 'hidden' : '' }}">
                    <div class="text-center">
                        <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                        <p>No hay datos para mostrar</p>
                    </div>
                </div>
                <canvas id="productChart" class="{{ count($commissionsByProduct) > 0 ? '' : 'hidden' }}"></canvas>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <h3 class="text-lg font-semibold text-slate-800 mb-4">Comisiones por Categoría</h3>
            <div class="h-64" wire:ignore>
                <div id="categoryEmpty" class="flex items-center justify-center h-full text-slate-400 {{ count($commissionsByCategory) > 0 ? 'hidden' : '' }}">
                    <div class="text-center">
                        <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path></svg>
                        <p>No hay datos para mostrar</p>
                    </div>
                </div>
                <canvas id="categoryChart" class="{{ count($commissionsByCategory) > 0 ? '' : 'hidden' }}"></canvas>
            </div>
        </div>
    </div>

    <!-- User Ranking with Expandable Detail -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 mb-6">
        <h3 class="text-lg font-semibold text-slate-800 mb-4">Detalle por Vendedor</h3>
        <p class="text-sm text-slate-500 mb-4">Haz clic en un vendedor para ver el detalle de sus ventas con comisión</p>
        
        <div class="space-y-3">
            @forelse($commissionsByUser as $index => $user)
            <div class="border border-slate-200 rounded-xl overflow-hidden">
                {{-- User Header (clickable) --}}
                <button wire:click="toggleUserDetail({{ $user['user_id'] }})" class="w-full flex items-center gap-4 p-4 hover:bg-slate-50 transition {{ $index < 3 ? 'bg-gradient-to-r from-green-50 to-emerald-50' : 'bg-white' }}">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm flex-shrink-0 {{ $index === 0 ? 'bg-yellow-400 text-yellow-900' : ($index === 1 ? 'bg-slate-300 text-slate-700' : ($index === 2 ? 'bg-amber-600 text-white' : 'bg-slate-200 text-slate-600')) }}">
                        {{ $index + 1 }}
                    </div>
                    <div class="flex-1 min-w-0 text-left">
                        <p class="font-medium text-slate-800 truncate">{{ $user['user_name'] }}</p>
                        <p class="text-xs text-slate-500">{{ number_format($user['items']) }} items vendidos</p>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <p class="font-bold text-green-600">${{ number_format($user['commission'], 0, ',', '.') }}</p>
                        <p class="text-xs text-slate-500">${{ number_format($user['sales'], 0, ',', '.') }} ventas</p>
                    </div>
                    <svg class="w-5 h-5 text-slate-400 flex-shrink-0 transition-transform {{ $expandedUserId === $user['user_id'] ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                
                {{-- Expanded Detail --}}
                @if($expandedUserId === $user['user_id'] && count($userSalesDetail) > 0)
                <div class="border-t border-slate-200 bg-slate-50 p-4">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-xs text-slate-500 uppercase">
                                    <th class="px-3 py-2 text-left">Fecha</th>
                                    <th class="px-3 py-2 text-left">Factura</th>
                                    <th class="px-3 py-2 text-left">Producto</th>
                                    <th class="px-3 py-2 text-left">Categoría</th>
                                    <th class="px-3 py-2 text-left">Marca</th>
                                    <th class="px-3 py-2 text-center">Cant.</th>
                                    <th class="px-3 py-2 text-right">Total</th>
                                    <th class="px-3 py-2 text-right">Comisión</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200">
                                @foreach($userSalesDetail as $detail)
                                <tr class="bg-white hover:bg-slate-50">
                                    <td class="px-3 py-2 text-slate-600">{{ $detail['date'] }}</td>
                                    <td class="px-3 py-2 text-slate-800 font-medium">{{ $detail['invoice_number'] }}</td>
                                    <td class="px-3 py-2 text-slate-800">{{ Str::limit($detail['product_name'], 30) }}</td>
                                    <td class="px-3 py-2 text-slate-600">{{ $detail['category'] }}</td>
                                    <td class="px-3 py-2 text-slate-600">{{ $detail['brand'] }}</td>
                                    <td class="px-3 py-2 text-center">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-[#a855f7]/10 text-[#a855f7]">
                                            {{ $detail['quantity'] }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-right text-slate-600">${{ number_format($detail['total'], 0, ',', '.') }}</td>
                                    <td class="px-3 py-2 text-right font-semibold text-green-600">${{ number_format($detail['commission'], 0, ',', '.') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-green-50">
                                <tr>
                                    <td colspan="6" class="px-3 py-2 text-right font-semibold text-slate-700">Total:</td>
                                    <td class="px-3 py-2 text-right font-semibold text-slate-700">${{ number_format($user['sales'], 0, ',', '.') }}</td>
                                    <td class="px-3 py-2 text-right font-bold text-green-600">${{ number_format($user['commission'], 0, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                @endif
            </div>
            @empty
            <div class="flex items-center justify-center py-12 text-slate-400">
                <div class="text-center">
                    <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    <p>No hay datos de comisiones para mostrar</p>
                    <p class="text-sm mt-1">Ajusta los filtros o verifica que haya productos con comisión configurada</p>
                </div>
            </div>
            @endforelse
        </div>
    </div>

    <!-- Top Products List -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
        <h3 class="text-lg font-semibold text-slate-800 mb-4">Detalle de Productos con Comisión</h3>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">#</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Producto</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 uppercase tracking-wider">Cantidad</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Ventas</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Comisión</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($commissionsByProduct as $index => $product)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-medium {{ $index < 3 ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-600' }}">
                                {{ $index + 1 }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div>
                                <p class="font-medium text-slate-800 text-sm">{{ $product['name'] }}</p>
                                <p class="text-xs text-slate-500">{{ $product['sku'] }}</p>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-[#a855f7]/10 text-[#a855f7]">
                                {{ number_format($product['quantity']) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <span class="text-sm text-slate-600">${{ number_format($product['sales'], 0, ',', '.') }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <span class="text-sm font-semibold text-green-600">${{ number_format($product['commission'], 0, ',', '.') }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-4 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mb-4">
                                    <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                    </svg>
                                </div>
                                <p class="text-slate-500 font-medium">No hay productos con comisión</p>
                                <p class="text-slate-400 text-sm">Ajusta los filtros para ver resultados</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Chart Data for JavaScript -->
    <div id="chartData" 
         data-trend='@json($commissionsByDay)'
         data-users='@json($commissionsByUser)'
         data-products='@json($commissionsByProduct)'
         data-categories='@json($commissionsByCategory)'
         class="hidden"></div>

    <!-- Charts JavaScript -->
    <script>
        const chartColors = [
            'rgba(16,185,129,0.8)',
            'rgba(168,85,247,0.8)',
            'rgba(59,130,246,0.8)',
            'rgba(255,114,97,0.8)',
            'rgba(245,158,11,0.8)',
            'rgba(239,68,68,0.8)',
            'rgba(99,102,241,0.8)',
            'rgba(236,72,153,0.8)',
            'rgba(20,184,166,0.8)',
            'rgba(132,204,22,0.8)'
        ];

        let trendChart = null;
        let userChart = null;
        let productChart = null;
        let categoryChart = null;

        function initCharts() {
            const dataEl = document.getElementById('chartData');
            if (!dataEl) return;

            const trendData = JSON.parse(dataEl.dataset.trend || '[]');
            const usersData = JSON.parse(dataEl.dataset.users || '[]');
            const productsData = JSON.parse(dataEl.dataset.products || '[]');
            const categoriesData = JSON.parse(dataEl.dataset.categories || '[]');

            // Destroy existing charts
            if (trendChart) trendChart.destroy();
            if (userChart) userChart.destroy();
            if (productChart) productChart.destroy();
            if (categoryChart) categoryChart.destroy();

            // Trend Chart
            const trendCtx = document.getElementById('trendChart');
            if (trendCtx && trendData.length > 0) {
                trendChart = new Chart(trendCtx, {
                    type: 'line',
                    data: {
                        labels: trendData.map(d => d.label),
                        datasets: [{
                            label: 'Comisiones',
                            data: trendData.map(d => d.commission),
                            borderColor: 'rgba(16, 185, 129, 1)',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: 'rgba(16, 185, 129, 1)',
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

            // User Chart
            const userCtx = document.getElementById('userChart');
            if (userCtx && usersData.length > 0) {
                userChart = new Chart(userCtx, {
                    type: 'bar',
                    data: {
                        labels: usersData.map(d => d.user_name.length > 15 ? d.user_name.substring(0, 15) + '...' : d.user_name),
                        datasets: [{
                            label: 'Comisión',
                            data: usersData.map(d => d.commission),
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

            // Product Chart
            const productCtx = document.getElementById('productChart');
            if (productCtx && productsData.length > 0) {
                productChart = new Chart(productCtx, {
                    type: 'bar',
                    data: {
                        labels: productsData.map(d => d.name.length > 15 ? d.name.substring(0, 15) + '...' : d.name),
                        datasets: [{
                            label: 'Comisión',
                            data: productsData.map(d => d.commission),
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

            // Category Chart
            const catCtx = document.getElementById('categoryChart');
            if (catCtx && categoriesData.length > 0) {
                categoryChart = new Chart(catCtx, {
                    type: 'doughnut',
                    data: {
                        labels: categoriesData.map(d => d.category_name),
                        datasets: [{
                            data: categoriesData.map(d => d.commission),
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
        }

        function updateCharts(data) {
            const trendData = data.trend || [];
            const usersData = data.users || [];
            const productsData = data.products || [];
            const categoriesData = data.categories || [];

            // Destroy existing charts
            if (trendChart) trendChart.destroy();
            if (userChart) userChart.destroy();
            if (productChart) productChart.destroy();
            if (categoryChart) categoryChart.destroy();

            // Toggle empty states
            const trendEmpty = document.getElementById('trendEmpty');
            const trendCanvas = document.getElementById('trendChart');
            const userEmpty = document.getElementById('userEmpty');
            const userCanvas = document.getElementById('userChart');
            const productEmpty = document.getElementById('productEmpty');
            const productCanvas = document.getElementById('productChart');
            const categoryEmpty = document.getElementById('categoryEmpty');
            const categoryCanvas = document.getElementById('categoryChart');

            // Trend Chart
            if (trendData.length > 0) {
                if (trendEmpty) trendEmpty.classList.add('hidden');
                if (trendCanvas) trendCanvas.classList.remove('hidden');
                const trendCtx = document.getElementById('trendChart');
                if (trendCtx) {
                    trendChart = new Chart(trendCtx, {
                        type: 'line',
                        data: {
                            labels: trendData.map(d => d.label),
                            datasets: [{
                                label: 'Comisiones',
                                data: trendData.map(d => d.commission),
                                borderColor: 'rgba(16, 185, 129, 1)',
                                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                fill: true,
                                tension: 0.4,
                                pointBackgroundColor: 'rgba(16, 185, 129, 1)',
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
            } else {
                if (trendEmpty) trendEmpty.classList.remove('hidden');
                if (trendCanvas) trendCanvas.classList.add('hidden');
            }

            // User Chart
            if (usersData.length > 0) {
                if (userEmpty) userEmpty.classList.add('hidden');
                if (userCanvas) userCanvas.classList.remove('hidden');
                const userCtx = document.getElementById('userChart');
                if (userCtx) {
                    userChart = new Chart(userCtx, {
                        type: 'bar',
                        data: {
                            labels: usersData.map(d => d.user_name.length > 15 ? d.user_name.substring(0, 15) + '...' : d.user_name),
                            datasets: [{
                                label: 'Comisión',
                                data: usersData.map(d => d.commission),
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
            } else {
                if (userEmpty) userEmpty.classList.remove('hidden');
                if (userCanvas) userCanvas.classList.add('hidden');
            }

            // Product Chart
            if (productsData.length > 0) {
                if (productEmpty) productEmpty.classList.add('hidden');
                if (productCanvas) productCanvas.classList.remove('hidden');
                const productCtx = document.getElementById('productChart');
                if (productCtx) {
                    productChart = new Chart(productCtx, {
                        type: 'bar',
                        data: {
                            labels: productsData.map(d => d.name.length > 15 ? d.name.substring(0, 15) + '...' : d.name),
                            datasets: [{
                                label: 'Comisión',
                                data: productsData.map(d => d.commission),
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
            } else {
                if (productEmpty) productEmpty.classList.remove('hidden');
                if (productCanvas) productCanvas.classList.add('hidden');
            }

            // Category Chart
            if (categoriesData.length > 0) {
                if (categoryEmpty) categoryEmpty.classList.add('hidden');
                if (categoryCanvas) categoryCanvas.classList.remove('hidden');
                const catCtx = document.getElementById('categoryChart');
                if (catCtx) {
                    categoryChart = new Chart(catCtx, {
                        type: 'doughnut',
                        data: {
                            labels: categoriesData.map(d => d.category_name),
                            datasets: [{
                                data: categoriesData.map(d => d.commission),
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
            } else {
                if (categoryEmpty) categoryEmpty.classList.remove('hidden');
                if (categoryCanvas) categoryCanvas.classList.add('hidden');
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', initCharts);

        // Listen for Livewire events
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('charts-updated', (data) => {
                setTimeout(() => updateCharts(data[0]), 100);
            });
        });
    </script>
</div>

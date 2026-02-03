<div class="p-6">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Welcome Header -->
    <div class="bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-2xl shadow-lg p-8 text-white mb-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-white/80 text-sm font-medium mb-1">{{ now()->format('l, d F Y') }}</p>
                <h1 class="text-3xl lg:text-4xl font-bold mb-2">Bienvenido a {{ $branchName }}</h1>
                <p class="text-white/90">Hola, {{ $userName }} 👋</p>
            </div>
            <div class="mt-4 lg:mt-0 flex flex-wrap gap-3">
                @if(auth()->user()->hasPermission('pos.access'))
                <a href="{{ route('pos') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-white/20 hover:bg-white/30 rounded-xl font-medium transition backdrop-blur-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                    Ir al POS
                </a>
                @endif
                @if(auth()->user()->hasPermission('sales.view'))
                <a href="{{ route('sales') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-white/20 hover:bg-white/30 rounded-xl font-medium transition backdrop-blur-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    Ver Ventas
                </a>
                @endif
            </div>
        </div>
    </div>

    <!-- Today Stats -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-medium text-slate-500 uppercase tracking-wide">Ventas Hoy</span>
                <div class="w-10 h-10 bg-gradient-to-br from-green-400 to-green-600 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-slate-800">${{ number_format($salesToday, 0, ',', '.') }}</p>
            <p class="text-xs text-slate-500 mt-1">{{ $transactionsToday }} transacciones</p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-medium text-slate-500 uppercase tracking-wide">Items Vendidos</span>
                <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-blue-600 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-slate-800">{{ number_format($itemsSoldToday, 0, ',', '.') }}</p>
            <p class="text-xs text-slate-500 mt-1">Ticket prom: ${{ number_format($averageTicketToday, 0, ',', '.') }}</p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-medium text-slate-500 uppercase tracking-wide">Ventas Mes</span>
                <div class="w-10 h-10 bg-gradient-to-br from-[#ff7261] to-[#a855f7] rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-slate-800">${{ number_format($salesMonth, 0, ',', '.') }}</p>
            <p class="text-xs mt-1 {{ $salesGrowth >= 0 ? 'text-green-600' : 'text-red-600' }}">
                <span class="inline-flex items-center">
                    @if($salesGrowth >= 0)
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                    @else
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
                    @endif
                    {{ number_format(abs($salesGrowth), 1) }}% vs mes anterior
                </span>
            </p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-medium text-slate-500 uppercase tracking-wide">Productos</span>
                <div class="w-10 h-10 bg-gradient-to-br from-purple-400 to-purple-600 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-slate-800">{{ number_format($totalProducts, 0, ',', '.') }}</p>
            @if($lowStockProducts > 0)
            <p class="text-xs text-amber-600 mt-1">
                <span class="inline-flex items-center">
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    {{ $lowStockProducts }} con stock bajo
                </span>
            </p>
            @else
            <p class="text-xs text-slate-500 mt-1">{{ $totalCustomers }} clientes</p>
            @endif
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Sales Trend Chart -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <h3 class="text-lg font-semibold text-slate-800 mb-4">Ventas Últimos 7 Días</h3>
            <div class="h-64" wire:ignore>
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

        <!-- Sales by Hour Chart -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <h3 class="text-lg font-semibold text-slate-800 mb-4">Ventas por Hora (Hoy)</h3>
            <div class="h-64" wire:ignore>
                @if(count($salesByHour) > 0)
                <canvas id="salesByHourChart"></canvas>
                @else
                <div class="flex items-center justify-center h-full text-slate-400">
                    <div class="text-center">
                        <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <p>No hay ventas hoy</p>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Bottom Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Top Products -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <h3 class="text-lg font-semibold text-slate-800 mb-4">Top Productos del Mes</h3>
            <div class="space-y-3">
                @forelse($topProducts as $index => $product)
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm {{ $index === 0 ? 'bg-yellow-400 text-yellow-900' : ($index === 1 ? 'bg-slate-300 text-slate-700' : ($index === 2 ? 'bg-amber-600 text-white' : 'bg-slate-100 text-slate-600')) }}">
                        {{ $index + 1 }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-slate-800 text-sm truncate">{{ $product['product_name'] }}</p>
                        <p class="text-xs text-slate-500">{{ number_format($product['quantity']) }} vendidos</p>
                    </div>
                    <span class="text-sm font-semibold text-slate-800">${{ number_format($product['total'], 0, ',', '.') }}</span>
                </div>
                @empty
                <div class="flex items-center justify-center py-8 text-slate-400">
                    <div class="text-center">
                        <svg class="w-10 h-10 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                        <p class="text-sm">Sin ventas este mes</p>
                    </div>
                </div>
                @endforelse
            </div>
        </div>

        <!-- Payment Methods -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <h3 class="text-lg font-semibold text-slate-800 mb-4">Métodos de Pago</h3>
            <div class="h-48" wire:ignore>
                @if(count($salesByPaymentMethod) > 0)
                <canvas id="paymentMethodChart"></canvas>
                @else
                <div class="flex items-center justify-center h-full text-slate-400">
                    <div class="text-center">
                        <svg class="w-10 h-10 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                        <p class="text-sm">Sin datos</p>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <h3 class="text-lg font-semibold text-slate-800 mb-4">Acciones Rápidas</h3>
            <div class="grid grid-cols-2 gap-3">
                @if(auth()->user()->hasPermission('products.create'))
                <a href="{{ route('products') }}" class="flex flex-col items-center gap-2 p-4 rounded-xl bg-slate-50 hover:bg-slate-100 transition group">
                    <div class="w-10 h-10 bg-purple-100 rounded-xl flex items-center justify-center group-hover:bg-purple-200 transition">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                    </div>
                    <span class="text-xs font-medium text-slate-700">Nuevo Producto</span>
                </a>
                @endif

                @if(auth()->user()->hasPermission('customers.create'))
                <a href="{{ route('customers') }}" class="flex flex-col items-center gap-2 p-4 rounded-xl bg-slate-50 hover:bg-slate-100 transition group">
                    <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center group-hover:bg-blue-200 transition">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                    </div>
                    <span class="text-xs font-medium text-slate-700">Nuevo Cliente</span>
                </a>
                @endif

                @if(auth()->user()->hasPermission('purchases.create'))
                <a href="{{ route('purchases.create') }}" class="flex flex-col items-center gap-2 p-4 rounded-xl bg-slate-50 hover:bg-slate-100 transition group">
                    <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center group-hover:bg-green-200 transition">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    </div>
                    <span class="text-xs font-medium text-slate-700">Nueva Compra</span>
                </a>
                @endif

                @if(auth()->user()->hasPermission('reports.view'))
                <a href="{{ route('reports.products-sold') }}" class="flex flex-col items-center gap-2 p-4 rounded-xl bg-slate-50 hover:bg-slate-100 transition group">
                    <div class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center group-hover:bg-amber-200 transition">
                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>
                    <span class="text-xs font-medium text-slate-700">Ver Reportes</span>
                </a>
                @endif
            </div>
        </div>
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
                'rgba(99,102,241,0.8)'
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
                            label: 'Ventas',
                            data: @json(collect($salesByDay)->pluck('total')),
                            borderColor: 'rgba(255, 114, 97, 1)',
                            backgroundColor: 'rgba(255, 114, 97, 0.1)',
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: 'rgba(255, 114, 97, 1)',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 5,
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

            // Sales by Hour Chart
            @if(count($salesByHour) > 0)
            const hourCtx = document.getElementById('salesByHourChart');
            if (hourCtx) {
                new Chart(hourCtx, {
                    type: 'bar',
                    data: {
                        labels: @json(collect($salesByHour)->pluck('hour')),
                        datasets: [{
                            label: 'Ventas',
                            data: @json(collect($salesByHour)->pluck('total')),
                            backgroundColor: 'rgba(168, 85, 247, 0.7)',
                            borderRadius: 6,
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

            // Payment Method Chart
            @if(count($salesByPaymentMethod) > 0)
            const paymentCtx = document.getElementById('paymentMethodChart');
            if (paymentCtx) {
                new Chart(paymentCtx, {
                    type: 'doughnut',
                    data: {
                        labels: @json(collect($salesByPaymentMethod)->pluck('name')),
                        datasets: [{
                            data: @json(collect($salesByPaymentMethod)->pluck('total')),
                            backgroundColor: chartColors,
                            borderWidth: 0,
                            hoverOffset: 10
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '65%',
                        plugins: {
                            legend: { position: 'right', labels: { usePointStyle: true, padding: 12, font: { size: 11 } } }
                        }
                    }
                });
            }
            @endif
        });
    </script>
</div>

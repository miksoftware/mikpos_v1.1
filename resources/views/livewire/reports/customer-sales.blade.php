<div class="p-6">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Header -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Ventas por Cliente</h1>
            <p class="text-slate-500 text-sm mt-1">Análisis de ventas agrupadas por cliente</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
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
                <label class="block text-xs font-medium text-slate-500 mb-1">Buscar</label>
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Nombre, documento..." class="w-full pl-9 pr-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm">
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
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
                    <p class="text-xs font-medium text-slate-500">Clientes</p>
                    <p class="text-xl font-bold text-slate-800 mt-1">{{ number_format($totalCustomers, 0, ',', '.') }}</p>
                </div>
                <div class="w-10 h-10 bg-gradient-to-br from-purple-400 to-purple-600 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500">Ticket Promedio</p>
                    <p class="text-xl font-bold text-slate-800 mt-1">${{ number_format($averageTicket, 0, ',', '.') }}</p>
                </div>
                <div class="w-10 h-10 bg-gradient-to-br from-amber-400 to-amber-600 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500">Prom. por Cliente</p>
                    <p class="text-xl font-bold text-slate-800 mt-1">${{ number_format($averagePerCustomer, 0, ',', '.') }}</p>
                </div>
                <div class="w-10 h-10 bg-gradient-to-br from-rose-400 to-rose-600 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Top 10 Customers -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
            <h3 class="text-sm font-semibold text-slate-700 mb-3">Top 10 Clientes por Ingresos</h3>
            <div style="height: 300px;">
                <canvas id="topCustomersChart" wire:ignore></canvas>
            </div>
        </div>

        <!-- Sales Trend -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
            <h3 class="text-sm font-semibold text-slate-700 mb-3">Tendencia de Ventas</h3>
            <div style="height: 300px;">
                <canvas id="salesTrendChart" wire:ignore></canvas>
            </div>
        </div>
    </div>

    <!-- Sales by Payment Type -->
    @if(count($salesByPaymentType) > 0)
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
            <h3 class="text-sm font-semibold text-slate-700 mb-3">Ventas por Tipo de Pago</h3>
            <div style="height: 250px;">
                <canvas id="paymentTypeChart" wire:ignore></canvas>
            </div>
        </div>
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
            <h3 class="text-sm font-semibold text-slate-700 mb-3">Ranking de Clientes</h3>
            <div class="space-y-3">
                @foreach($topCustomers as $index => $tc)
                <div class="flex items-center gap-3">
                    <div class="w-7 h-7 rounded-full bg-gradient-to-r from-[#ff7261] to-[#a855f7] flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                        {{ $index + 1 }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-slate-800 truncate">{{ $tc['customer_name'] }}</p>
                        <p class="text-xs text-slate-500">{{ $tc['total_transactions'] }} ventas</p>
                    </div>
                    <p class="text-sm font-bold text-slate-800">${{ number_format($tc['total_revenue'], 0, ',', '.') }}</p>
                </div>
                @endforeach
                @if(count($topCustomers) === 0)
                <p class="text-sm text-slate-400 text-center py-4">Sin datos para el período seleccionado</p>
                @endif
            </div>
        </div>
    </div>
    @endif

    <!-- Detailed Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">
                            <button wire:click="sortByColumn('customer_name')" class="flex items-center gap-1 hover:text-slate-700">
                                Cliente
                                @if($sortBy === 'customer_name')
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="{{ $sortDirection === 'asc' ? 'M5.293 9.707l4 4a1 1 0 001.414 0l4-4a1 1 0 10-1.414-1.414L10 11.586 6.707 8.293a1 1 0 00-1.414 1.414z' : 'M14.707 10.293l-4-4a1 1 0 00-1.414 0l-4 4a1 1 0 101.414 1.414L10 8.414l3.293 3.293a1 1 0 001.414-1.414z' }}"></path></svg>
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Documento</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Contacto</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 uppercase">
                            <button wire:click="sortByColumn('total_transactions')" class="flex items-center gap-1 hover:text-slate-700 mx-auto">
                                Ventas
                                @if($sortBy === 'total_transactions')
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="{{ $sortDirection === 'asc' ? 'M5.293 9.707l4 4a1 1 0 001.414 0l4-4a1 1 0 10-1.414-1.414L10 11.586 6.707 8.293a1 1 0 00-1.414 1.414z' : 'M14.707 10.293l-4-4a1 1 0 00-1.414 0l-4 4a1 1 0 101.414 1.414L10 8.414l3.293 3.293a1 1 0 001.414-1.414z' }}"></path></svg>
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">
                            <button wire:click="sortByColumn('avg_ticket')" class="flex items-center gap-1 hover:text-slate-700 ml-auto">
                                Ticket Prom.
                                @if($sortBy === 'avg_ticket')
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="{{ $sortDirection === 'asc' ? 'M5.293 9.707l4 4a1 1 0 001.414 0l4-4a1 1 0 10-1.414-1.414L10 11.586 6.707 8.293a1 1 0 00-1.414 1.414z' : 'M14.707 10.293l-4-4a1 1 0 00-1.414 0l-4 4a1 1 0 101.414 1.414L10 8.414l3.293 3.293a1 1 0 001.414-1.414z' }}"></path></svg>
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">
                            <button wire:click="sortByColumn('total_revenue')" class="flex items-center gap-1 hover:text-slate-700 ml-auto">
                                Total
                                @if($sortBy === 'total_revenue')
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="{{ $sortDirection === 'asc' ? 'M5.293 9.707l4 4a1 1 0 001.414 0l4-4a1 1 0 10-1.414-1.414L10 11.586 6.707 8.293a1 1 0 00-1.414 1.414z' : 'M14.707 10.293l-4-4a1 1 0 00-1.414 0l-4 4a1 1 0 101.414 1.414L10 8.414l3.293 3.293a1 1 0 001.414-1.414z' }}"></path></svg>
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 uppercase">
                            <button wire:click="sortByColumn('last_sale')" class="flex items-center gap-1 hover:text-slate-700 mx-auto">
                                Última Venta
                                @if($sortBy === 'last_sale')
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="{{ $sortDirection === 'asc' ? 'M5.293 9.707l4 4a1 1 0 001.414 0l4-4a1 1 0 10-1.414-1.414L10 11.586 6.707 8.293a1 1 0 00-1.414 1.414z' : 'M14.707 10.293l-4-4a1 1 0 00-1.414 0l-4 4a1 1 0 101.414 1.414L10 8.414l3.293 3.293a1 1 0 001.414-1.414z' }}"></path></svg>
                                @endif
                            </button>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($customers as $customer)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-r from-[#ff7261] to-[#a855f7] flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                                    {{ strtoupper(substr($customer->first_name ?? $customer->business_name ?? '?', 0, 1)) }}
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-slate-800">
                                        {{ $customer->customer_type === 'juridico' && $customer->business_name ? $customer->business_name : trim($customer->first_name . ' ' . $customer->last_name) }}
                                    </p>
                                    <span class="text-xs text-slate-400">{{ $customer->customer_type === 'juridico' ? 'Jurídico' : 'Natural' }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $customer->document_number }}</td>
                        <td class="px-4 py-3">
                            <div class="text-xs text-slate-500">
                                @if($customer->phone)<p>{{ $customer->phone }}</p>@endif
                                @if($customer->email)<p class="truncate max-w-[150px]">{{ $customer->email }}</p>@endif
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                {{ $customer->total_transactions }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right text-sm text-slate-600">${{ number_format($customer->avg_ticket, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right text-sm font-semibold text-slate-800">${{ number_format($customer->total_revenue, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-center text-xs text-slate-500">{{ \Carbon\Carbon::parse($customer->last_sale_date)->format('d/m/Y') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-slate-400">
                            <svg class="w-12 h-12 mx-auto mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <p>No se encontraron ventas por cliente para el período seleccionado</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($customers->hasPages())
        <div class="px-4 py-3 border-t border-slate-200">
            {{ $customers->links() }}
        </div>
        @endif
    </div>

    <!-- Chart.js Scripts -->
    <script>
        document.addEventListener('livewire:navigated', initCustomerSalesCharts);
        document.addEventListener('DOMContentLoaded', initCustomerSalesCharts);

        function initCustomerSalesCharts() {
            // Top Customers Chart
            const topCtx = document.getElementById('topCustomersChart');
            if (topCtx) {
                if (topCtx._chart) topCtx._chart.destroy();
                const topData = @json($topCustomers);
                topCtx._chart = new Chart(topCtx, {
                    type: 'bar',
                    data: {
                        labels: topData.map(d => d.customer_name.length > 20 ? d.customer_name.substring(0, 20) + '...' : d.customer_name),
                        datasets: [{
                            label: 'Ingresos',
                            data: topData.map(d => d.total_revenue),
                            backgroundColor: 'rgba(168, 85, 247, 0.7)',
                            borderColor: 'rgba(168, 85, 247, 1)',
                            borderWidth: 1,
                            borderRadius: 6,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        indexAxis: 'y',
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: ctx => '$' + ctx.raw.toLocaleString('es-CO')
                                }
                            }
                        },
                        scales: {
                            x: {
                                ticks: {
                                    callback: v => '$' + (v / 1000).toFixed(0) + 'k'
                                }
                            }
                        }
                    }
                });
            }

            // Sales Trend Chart
            const trendCtx = document.getElementById('salesTrendChart');
            if (trendCtx) {
                if (trendCtx._chart) trendCtx._chart.destroy();
                const trendData = @json($salesByDay);
                trendCtx._chart = new Chart(trendCtx, {
                    type: 'line',
                    data: {
                        labels: trendData.map(d => d.label),
                        datasets: [{
                            label: 'Ingresos',
                            data: trendData.map(d => d.revenue),
                            borderColor: 'rgba(255, 114, 97, 1)',
                            backgroundColor: 'rgba(255, 114, 97, 0.1)',
                            fill: true,
                            tension: 0.4,
                            yAxisID: 'y',
                        }, {
                            label: 'Clientes',
                            data: trendData.map(d => d.customers),
                            borderColor: 'rgba(168, 85, 247, 1)',
                            backgroundColor: 'rgba(168, 85, 247, 0.1)',
                            fill: false,
                            tension: 0.4,
                            yAxisID: 'y1',
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    label: ctx => {
                                        if (ctx.datasetIndex === 0) return 'Ingresos: $' + ctx.raw.toLocaleString('es-CO');
                                        return 'Clientes: ' + ctx.raw;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                type: 'linear',
                                position: 'left',
                                ticks: { callback: v => '$' + (v / 1000).toFixed(0) + 'k' }
                            },
                            y1: {
                                type: 'linear',
                                position: 'right',
                                grid: { drawOnChartArea: false },
                                ticks: { stepSize: 1 }
                            }
                        }
                    }
                });
            }

            // Payment Type Chart
            const payCtx = document.getElementById('paymentTypeChart');
            if (payCtx) {
                if (payCtx._chart) payCtx._chart.destroy();
                const payData = @json($salesByPaymentType);
                payCtx._chart = new Chart(payCtx, {
                    type: 'doughnut',
                    data: {
                        labels: payData.map(d => d.type),
                        datasets: [{
                            data: payData.map(d => d.total_amount),
                            backgroundColor: ['rgba(34, 197, 94, 0.7)', 'rgba(239, 68, 68, 0.7)', 'rgba(168, 85, 247, 0.7)'],
                            borderWidth: 2,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    label: ctx => ctx.label + ': $' + ctx.raw.toLocaleString('es-CO')
                                }
                            }
                        }
                    }
                });
            }
        }
    </script>
</div>

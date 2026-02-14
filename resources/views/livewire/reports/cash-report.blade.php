<div class="p-6">
    <!-- Header -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Reporte de Cajas</h1>
            <p class="text-slate-500 text-sm mt-1">Arqueos, movimientos e informe consolidado de cajas</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4">
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Período</label>
                <select wire:model.live="dateRange" class="w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm">
                    <option value="today">Hoy</option>
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
                <label class="block text-xs font-medium text-slate-500 mb-1">Caja</label>
                <select wire:model.live="selectedCashRegisterId" class="w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm">
                    <option value="">Todas</option>
                    @foreach($cashRegisters as $register)
                    <option value="{{ $register->id }}">{{ $register->name }}{{ $register->user ? ' - ' . $register->user->name : '' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <button wire:click="clearFilters" class="w-full px-3 py-2.5 text-sm font-medium text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200 transition">
                    Limpiar
                </button>
            </div>
        </div>
    </div>

    <!-- Tab Buttons -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-2 mb-6">
        <div class="flex flex-wrap gap-1">
            @php
                $tabs = [
                    'reconciliations' => 'Arqueos x Fechas',
                    'movements' => 'Movimientos x Fechas',
                    'report' => 'Informe x Fechas',
                ];
            @endphp
            @foreach($tabs as $key => $label)
            <button wire:click="$set('viewMode', '{{ $key }}')"
                class="px-4 py-2.5 text-sm font-medium rounded-xl transition-all {{ $viewMode === $key ? 'bg-gradient-to-r from-[#ff7261] to-[#a855f7] text-white shadow-lg shadow-purple-500/25' : 'text-slate-600 hover:bg-slate-100' }}">
                {{ $label }}
            </button>
            @endforeach
        </div>
    </div>

    <!-- Content -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">

        {{-- Arqueos x Fechas --}}
        @if($viewMode === 'reconciliations')
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Caja</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Apertura</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Cierre</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Abrió</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Cerró</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 uppercase">Estado</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Monto Apertura</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Monto Cierre</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Diferencia</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($detailData as $rec)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-4 py-3 text-sm font-medium text-slate-800">{{ $rec->cashRegister->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $rec->opened_at ? $rec->opened_at->format('d/m/Y H:i') : '-' }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $rec->closed_at ? $rec->closed_at->format('d/m/Y H:i') : '-' }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $rec->openedByUser->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $rec->closedByUser->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-center">
                            <span class="px-2 py-1 rounded-full text-xs font-medium {{ $rec->status === 'open' ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-600' }}">
                                {{ $rec->status === 'open' ? 'Abierta' : 'Cerrada' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-right text-slate-600">${{ number_format($rec->opening_amount, 2) }}</td>
                        <td class="px-4 py-3 text-sm text-right text-slate-600">{{ $rec->closing_amount !== null ? '$' . number_format($rec->closing_amount, 2) : '-' }}</td>
                        <td class="px-4 py-3 text-sm text-right font-semibold {{ $rec->difference !== null ? ((float)$rec->difference >= 0 ? 'text-green-600' : 'text-red-600') : 'text-slate-400' }}">
                            {{ $rec->difference !== null ? '$' . number_format($rec->difference, 2) : '-' }}
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="px-4 py-8 text-center text-slate-400">No hay arqueos para mostrar</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-slate-200">{{ $detailData->links() }}</div>
        @endif

        {{-- Movimientos x Fechas --}}
        @if($viewMode === 'movements')
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Fecha</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Caja</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 uppercase">Tipo</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Concepto</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Usuario</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Notas</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Monto</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($detailData as $mov)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $mov->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 text-sm text-slate-800">{{ $mov->register_name ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-center">
                            <span class="px-2 py-1 rounded-full text-xs font-medium {{ $mov->type === 'income' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                {{ $mov->type === 'income' ? 'Ingreso' : 'Egreso' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-800">{{ $mov->concept }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $mov->user_name ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-slate-500 max-w-xs truncate">{{ $mov->notes ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-right font-semibold {{ $mov->type === 'income' ? 'text-green-600' : 'text-red-600' }}">
                            {{ $mov->type === 'income' ? '+' : '-' }}${{ number_format($mov->amount, 2) }}
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-slate-400">No hay movimientos para mostrar</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-slate-200">{{ $detailData->links() }}</div>
        @endif

        {{-- Informe x Fechas --}}
        @if($viewMode === 'report')
        <div class="p-6">
            <!-- Report Header -->
            <div class="bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl p-4 mb-6 text-white">
                <h2 class="text-lg font-bold">Informe de Cajas por Fechas</h2>
            </div>

            <!-- Report Info -->
            <div class="mb-6 text-sm text-slate-600 space-y-1">
                <p>Descripción de Caja: <span class="font-medium text-slate-800">{{ $reportData['register_name'] ?? '-' }}</span></p>
                <p>Responsable de Caja: <span class="font-medium text-slate-800">{{ $reportData['user_name'] ?? '-' }}</span></p>
                <p>Fecha Desde: <span class="font-medium text-slate-800">{{ $startDate ? \Carbon\Carbon::parse($startDate)->format('d/m/Y') : '-' }}</span></p>
                <p>Fecha Hasta: <span class="font-medium text-slate-800">{{ $endDate ? \Carbon\Carbon::parse($endDate)->format('d/m/Y') : '-' }}</span></p>
                <p>Arqueos en rango: <span class="font-medium text-slate-800">{{ $reportData['reconciliation_count'] ?? 0 }}</span></p>
            </div>

            <!-- Report Table -->
            <div class="border border-slate-200 rounded-xl overflow-hidden">
                <table class="w-full">
                    <tbody class="divide-y divide-slate-200">
                        <tr class="bg-slate-50">
                            <td class="px-6 py-3 text-sm font-bold text-slate-800 uppercase">Total de Ventas</td>
                            <td class="px-6 py-3 text-sm font-bold text-right text-slate-800">${{ number_format($reportData['total_sales'] ?? 0, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-3 text-sm font-bold text-slate-800 uppercase">Total de Ingresos</td>
                            <td class="px-6 py-3 text-sm font-bold text-right text-green-600">${{ number_format($reportData['total_income'] ?? 0, 2) }}</td>
                        </tr>
                        <tr class="bg-slate-50">
                            <td class="px-6 py-3 text-sm font-bold text-slate-800 uppercase">Abonos a Créditos</td>
                            <td class="px-6 py-3 text-sm font-bold text-right text-blue-600">${{ number_format($reportData['credit_payments_received'] ?? 0, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-3 text-sm font-bold text-slate-800 uppercase">Total de Gastos (Egresos + Notas de Crédito)</td>
                            <td class="px-6 py-3 text-sm font-bold text-right text-red-600">${{ number_format($reportData['total_gastos'] ?? 0, 2) }}</td>
                        </tr>
                        <tr class="bg-slate-50">
                            <td class="px-6 py-3 text-sm text-slate-600 pl-10">— Egresos (movimientos)</td>
                            <td class="px-6 py-3 text-sm text-right text-slate-600">${{ number_format($reportData['total_expenses'] ?? 0, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-3 text-sm text-slate-600 pl-10">— Devoluciones</td>
                            <td class="px-6 py-3 text-sm text-right text-slate-600">${{ number_format($reportData['total_refunds'] ?? 0, 2) }}</td>
                        </tr>
                        <tr class="bg-slate-50">
                            <td class="px-6 py-3 text-sm text-slate-600 pl-10">— Notas de Crédito</td>
                            <td class="px-6 py-3 text-sm text-right text-slate-600">${{ number_format($reportData['total_credit_notes'] ?? 0, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-3 text-sm font-bold text-slate-800 uppercase">Total de Impuestos de Ventas IVA</td>
                            <td class="px-6 py-3 text-sm font-bold text-right text-amber-600">${{ number_format($reportData['total_tax'] ?? 0, 2) }}</td>
                        </tr>
                        <tr class="bg-gradient-to-r from-slate-100 to-slate-50">
                            <td class="px-6 py-4 text-sm font-bold text-slate-900 uppercase">Disponible en Caja sin Impuestos</td>
                            <td class="px-6 py-4 text-lg font-bold text-right text-slate-900">${{ number_format($reportData['available_no_tax'] ?? 0, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Sales by Payment Method -->
            @if(!empty($reportData['sales_by_method']))
            <div class="mt-6">
                <h3 class="text-sm font-semibold text-slate-700 mb-3">Ventas por Método de Pago</h3>
                <div class="border border-slate-200 rounded-xl overflow-hidden">
                    <table class="w-full">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Método</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-slate-500 uppercase"># Ventas</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($reportData['sales_by_method'] as $method)
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-6 py-3 text-sm font-medium text-slate-800">{{ $method['method_name'] }}</td>
                                <td class="px-6 py-3 text-sm text-center text-slate-600">{{ $method['sale_count'] }}</td>
                                <td class="px-6 py-3 text-sm text-right font-semibold text-slate-800">${{ number_format($method['total'], 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>
        @endif
    </div>
</div>

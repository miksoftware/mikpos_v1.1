<div class="space-y-6">
    <x-toast />

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Reporte de Devoluciones</h1>
            <p class="text-slate-500 mt-1">Análisis de devoluciones POS y notas crédito</p>
        </div>
        @if(auth()->user()->hasPermission('reports.export'))
        <button wire:click="exportExcel" wire:loading.attr="disabled" wire:target="exportExcel"
            class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-semibold rounded-xl shadow-sm hover:shadow transition-all duration-200 disabled:opacity-50">
            <svg class="w-5 h-5 mr-2 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
            <span wire:loading.remove wire:target="exportExcel">Exportar Excel</span>
            <span wire:loading wire:target="exportExcel">Generando...</span>
        </button>
        @endif
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
        <div class="flex flex-col sm:flex-row gap-3 items-end flex-wrap">
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Período</label>
                <select wire:model.live="dateRange" class="px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] sm:text-sm">
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
            @if($dateRange === 'custom')
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Desde</label>
                <input wire:model.live="startDate" type="date" class="px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] sm:text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Hasta</label>
                <input wire:model.live="endDate" type="date" class="px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] sm:text-sm">
            </div>
            @endif
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Tipo</label>
                <select wire:model.live="filterType" class="px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] sm:text-sm">
                    <option value="all">Todas</option>
                    <option value="refund">Devoluciones POS</option>
                    <option value="credit_note">Notas Crédito</option>
                </select>
            </div>
            @if($isSuperAdmin)
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Sucursal</label>
                <select wire:model.live="selectedBranchId" class="px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] sm:text-sm min-w-[160px]">
                    <option value="">Todas</option>
                    @foreach($branches as $b)
                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-medium text-slate-500 mb-1">Buscar</label>
                <input wire:model.live.debounce.400ms="search" type="text" placeholder="N° dev., factura, cliente..."
                    class="w-full px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] sm:text-sm">
            </div>
            <button wire:click="clearFilters" class="px-3 py-2.5 text-slate-500 hover:text-slate-700 hover:bg-slate-100 rounded-xl transition-colors text-sm font-medium flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                Limpiar
            </button>
        </div>
    </div>

    {{-- KPIs --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-orange-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path></svg>
                </div>
                <span class="text-sm text-slate-500">Total devuelto</span>
            </div>
            <p class="text-2xl font-bold text-orange-600">${{ number_format($grandTotal, 0, ',', '.') }}</p>
            <p class="text-xs text-slate-400 mt-1">{{ $grandCount }} en total</p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z"></path></svg>
                </div>
                <span class="text-sm text-slate-500">Devoluciones POS</span>
            </div>
            <p class="text-2xl font-bold text-blue-600">${{ number_format($totalRefundsAmount, 0, ',', '.') }}</p>
            <p class="text-xs text-slate-400 mt-1">{{ $totalRefundsCount }} devoluciones</p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-purple-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                </div>
                <span class="text-sm text-slate-500">Notas Crédito</span>
            </div>
            <p class="text-2xl font-bold text-purple-600">${{ number_format($totalCreditNotesAmount, 0, ',', '.') }}</p>
            <p class="text-xs text-slate-400 mt-1">{{ $totalCreditNotesCount }} notas DIAN</p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-red-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </div>
                <span class="text-sm text-slate-500">Totales</span>
            </div>
            <p class="text-2xl font-bold text-red-600">${{ number_format($totalTotal, 0, ',', '.') }}</p>
            <p class="text-xs text-slate-400 mt-1">{{ $totalCount }} anuladas completas</p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6"></path></svg>
                </div>
                <span class="text-sm text-slate-500">Parciales</span>
            </div>
            <p class="text-2xl font-bold text-amber-600">${{ number_format($partialTotal, 0, ',', '.') }}</p>
            <p class="text-xs text-slate-400 mt-1">{{ $partialCount }} parciales</p>
        </div>
    </div>

    {{-- Charts --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- By Day --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                Devoluciones por día
            </h3>
            <div class="space-y-2 max-h-[300px] overflow-y-auto">
                @php $maxAmount = collect($byDay)->max('amount') ?: 1; @endphp
                @forelse($byDay as $day)
                <div>
                    <div class="flex justify-between text-xs mb-1">
                        <span class="text-slate-600">{{ $day['label'] }}</span>
                        <div class="flex gap-3">
                            <span class="text-slate-500">{{ $day['count'] }} dev.</span>
                            <span class="text-orange-600 font-medium">${{ number_format($day['amount'], 0) }}</span>
                        </div>
                    </div>
                    <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-orange-400 to-orange-600 rounded-full" style="width: {{ ($day['amount'] / $maxAmount) * 100 }}%"></div>
                    </div>
                </div>
                @empty
                <p class="text-slate-400 text-center py-8">Sin devoluciones en el período</p>
                @endforelse
            </div>
        </div>

        {{-- By Reason --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                Top razones
            </h3>
            <div class="space-y-3 max-h-[300px] overflow-y-auto">
                @php
                    $maxR = collect($byReason)->max('amount') ?: 1;
                    $colors = ['bg-red-500', 'bg-orange-500', 'bg-amber-500', 'bg-purple-500', 'bg-blue-500', 'bg-emerald-500', 'bg-pink-500', 'bg-indigo-500'];
                @endphp
                @forelse($byReason as $i => $r)
                <div class="p-2 rounded-lg bg-slate-50">
                    <div class="flex items-start justify-between mb-1 gap-3">
                        <div class="flex items-start gap-2 flex-1 min-w-0">
                            <div class="w-3 h-3 rounded-full {{ $colors[$i % count($colors)] }} mt-1 flex-shrink-0"></div>
                            <span class="text-sm text-slate-700">{{ $r['reason'] }}</span>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <p class="text-sm font-semibold text-slate-800">${{ number_format($r['amount'], 0) }}</p>
                            <p class="text-xs text-slate-400">{{ $r['count'] }}x</p>
                        </div>
                    </div>
                    <div class="h-1.5 bg-slate-200 rounded-full overflow-hidden">
                        <div class="h-full {{ $colors[$i % count($colors)] }} rounded-full" style="width: {{ ($r['amount'] / $maxR) * 100 }}%"></div>
                    </div>
                </div>
                @empty
                <p class="text-slate-400 text-center py-8">Sin datos</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- By Branch --}}
    @if($isSuperAdmin && count($byBranch) > 0)
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z"></path></svg>
            Por sucursal
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            @foreach($byBranch as $b)
            <div class="p-3 rounded-xl border border-slate-200">
                <p class="text-sm font-semibold text-slate-800 truncate">{{ $b['name'] }}</p>
                <div class="flex items-end justify-between mt-1">
                    <p class="text-xl font-bold text-orange-600">${{ number_format($b['amount'], 0) }}</p>
                    <p class="text-xs text-slate-400">{{ $b['count'] }} dev.</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Detail Table --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200">
            <h3 class="font-semibold text-slate-800 flex items-center gap-2">
                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Detalle ({{ $rows->total() }})
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Fecha</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">N° Doc.</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Tipo</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Factura</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Cliente</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Razón</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 uppercase">Alcance</th>
                        @if($isSuperAdmin)
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Sucursal</th>
                        @endif
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Usuario</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Total</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-100">
                    @forelse($rows as $row)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $row->date->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 text-sm font-semibold text-slate-800">{{ $row->number }}</td>
                        <td class="px-4 py-3">
                            @if($row->kind === 'refund')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">{{ $row->kind_label }}</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-700">{{ $row->kind_label }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $row->sale_invoice ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm">
                            @if($row->customer_name)
                                <p class="font-medium text-slate-700 truncate max-w-[180px]">{{ $row->customer_name }}</p>
                                @if($row->customer_doc)
                                    <p class="text-xs text-slate-400">{{ $row->customer_doc }}</p>
                                @endif
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600 max-w-[200px] truncate" title="{{ $row->reason }}">
                            {{ $row->reason ?: '—' }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($row->type === 'total')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">Total</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">Parcial</span>
                            @endif
                        </td>
                        @if($isSuperAdmin)
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $row->branch_name ?? '—' }}</td>
                        @endif
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $row->user ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-right font-semibold text-orange-600">${{ number_format($row->total, 2) }}</td>
                        <td class="px-4 py-3 text-right">
                            @if($row->receipt_url)
                            <a href="{{ $row->receipt_url }}" target="_blank" class="inline-flex items-center px-2 py-1 text-xs font-medium text-purple-600 hover:text-purple-800 hover:bg-purple-50 rounded-lg">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                            </a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $isSuperAdmin ? 11 : 10 }}" class="px-4 py-12 text-center text-slate-400">
                            <svg class="w-12 h-12 mx-auto text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path></svg>
                            <p class="font-medium">Sin devoluciones</p>
                            <p class="text-sm">No hay devoluciones que coincidan con los filtros</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($rows->hasPages())
        <div class="px-4 py-3 border-t border-slate-200 bg-slate-50">
            {{ $rows->links() }}
        </div>
        @endif
    </div>
</div>

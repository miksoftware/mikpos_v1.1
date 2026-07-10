<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Historial de Producción</h1>
            <p class="text-slate-500 mt-1">Órdenes de producción registradas</p>
        </div>
        <div class="flex items-center gap-2">
            @if(auth()->user()->hasPermission('production.create'))
            <a href="{{ route('production.create') }}" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-[#ff7261] to-[#a855f7] hover:from-[#e55a4a] hover:to-[#9333ea] text-white text-sm font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-[1.02] transition-all duration-200">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Nueva Orden
            </a>
            @endif
        </div>
    </div>

    {{-- Search --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
        <div class="flex flex-col sm:flex-row gap-4">
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <input wire:model.live.debounce.300ms="search" type="text" class="block w-full pl-10 pr-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 placeholder-slate-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] transition-all sm:text-sm" placeholder="Buscar por producto o SKU...">
            </div>
            {{-- Sort dropdown --}}
            <div class="flex items-center gap-2">
                <span class="text-sm text-slate-500">Ordenar:</span>
                <select wire:model.live="sortBy" class="px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] sm:text-sm">
                    <option value="created_at">Fecha</option>
                    <option value="quantity_to_produce">Cantidad Total</option>
                    <option value="total_cost">Costo Total</option>
                </select>
                <button wire:click="sortByColumn('{{ $sortBy }}')" class="p-2.5 border border-slate-200 rounded-xl bg-slate-50 hover:bg-slate-100 transition-colors">
                    @if($sortDirection === 'asc')
                    <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"></path></svg>
                    @else
                    <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h9m5-4v12m0 0l-4-4m4 4l4-4"></path></svg>
                    @endif
                </button>
            </div>
        </div>
    </div>

    {{-- Orders Table --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Fecha</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Productos Fabricados</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold text-slate-500 uppercase">Cantidad Total</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-500 uppercase">Costo Total</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold text-slate-500 uppercase">Estado</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($orders as $order)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                            {{ $order->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-6 py-4">
                            @if($order->items->count() === 1)
                                <div class="font-medium text-slate-900">{{ $order->items->first()->product->name ?? 'Producto Eliminado' }}</div>
                            @else
                                <div class="font-medium text-slate-900">Varias recetas ({{ $order->items->count() }})</div>
                            @endif
                            <div class="text-sm text-slate-500 flex gap-2">
                                <span>Usuario: {{ $order->user->name ?? 'N/A' }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium bg-emerald-100 text-emerald-700">
                                +{{ number_format($order->quantity_to_produce ?? $order->items->sum('quantity_to_produce'), 2) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right font-medium text-slate-700">
                            ${{ number_format($order->total_cost ?? $order->items->sum('total_cost'), 2) }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($order->status === 'completed')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                Completada
                            </span>
                            @elseif($order->status === 'cancelled')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                Cancelada
                            </span>
                            @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">
                                {{ $order->status }}
                            </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button wire:click="viewDetails({{ $order->id }})" class="p-1.5 text-slate-400 hover:text-indigo-500 hover:bg-indigo-50 rounded-lg transition-colors" title="Ver Detalles">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                            <div class="flex flex-col items-center">
                                <svg class="w-12 h-12 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                <p>No hay órdenes de producción registradas</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($orders->hasPages())
        <div class="px-6 py-4 border-t border-slate-200">
            {{ $orders->links() }}
        </div>
        @endif
    </div>

    {{-- View Details Modal --}}
    @if($isViewModalOpen && $selectedOrder)
    <div class="relative z-[100]">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm transition-opacity" wire:click="closeViewModal"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-4xl bg-white rounded-2xl shadow-xl overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex justify-between items-center">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">Detalles de Orden de Producción #{{ $selectedOrder->id }}</h3>
                            <p class="text-sm text-slate-500">{{ $selectedOrder->created_at->format('d/m/Y H:i:s') }}</p>
                        </div>
                        <button wire:click="closeViewModal" class="text-slate-400 hover:text-slate-500">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-6">
                            <div>
                                <div class="text-sm text-slate-500 mb-1">Ítems Fabricados</div>
                                <div class="font-semibold text-slate-800">{{ $selectedOrder->items->count() }}</div>
                            </div>
                            <div>
                                <div class="text-sm text-slate-500 mb-1">Cantidad Total</div>
                                <div class="font-semibold text-emerald-600">+{{ number_format($selectedOrder->items->sum('quantity_to_produce'), 2) }}</div>
                            </div>
                            <div>
                                <div class="text-sm text-slate-500 mb-1">Costo Total</div>
                                <div class="font-semibold text-slate-800">${{ number_format($selectedOrder->items->sum('total_cost'), 2) }}</div>
                            </div>
                            <div>
                                <div class="text-sm text-slate-500 mb-1">Estado</div>
                                <div>
                                    @if($selectedOrder->status === 'completed')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Completada</span>
                                    @elseif($selectedOrder->status === 'cancelled')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">Cancelada</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        @if($selectedOrder->notes)
                        <div class="mb-6 bg-slate-50 p-4 rounded-xl border border-slate-200">
                            <div class="text-sm font-semibold text-slate-700 mb-1">Notas</div>
                            <p class="text-slate-600 text-sm">{{ $selectedOrder->notes }}</p>
                        </div>
                        @endif

                        <h4 class="text-base font-bold text-slate-800 mb-4 border-b pb-2">Detalle de Fabricación</h4>
                        
                        <div class="space-y-6">
                            @foreach($selectedOrder->items as $item)
                            <div class="border border-slate-200 rounded-xl overflow-hidden shadow-sm">
                                <div class="bg-slate-50 px-4 py-3 flex justify-between items-center border-b border-slate-200">
                                    <div>
                                        <h5 class="font-bold text-slate-800">{{ $item->product->name ?? 'N/A' }}</h5>
                                        <p class="text-xs text-slate-500">
                                            @if($item->location)
                                            Destino: <span class="font-semibold text-slate-700">{{ $item->location->name }}</span> | 
                                            @endif
                                            Cantidad Producida: <span class="font-semibold text-emerald-600">+{{ number_format($item->quantity_to_produce, 2) }}</span>
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <span class="block text-sm font-bold text-slate-800">${{ number_format($item->total_cost, 2) }}</span>
                                        <span class="text-xs text-slate-500">Costo Estimado</span>
                                    </div>
                                </div>
                                <table class="min-w-full divide-y divide-slate-200 bg-white">
                                    <thead class="bg-white">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Insumo Consumido</th>
                                            <th class="px-4 py-2 text-right text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Cant.</th>
                                            <th class="px-4 py-2 text-right text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Costo U.</th>
                                            <th class="px-4 py-2 text-right text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach($item->details as $detail)
                                        <tr class="hover:bg-slate-50">
                                            <td class="px-4 py-2 text-sm text-slate-700">{{ $detail->product->name ?? 'N/A' }}</td>
                                            <td class="px-4 py-2 text-sm text-amber-600 font-medium text-right">-{{ number_format($detail->quantity_consumed, 2) }}</td>
                                            <td class="px-4 py-2 text-sm text-slate-600 text-right">${{ number_format($detail->unit_cost_at_time, 2) }}</td>
                                            <td class="px-4 py-2 text-sm text-slate-800 font-medium text-right">${{ number_format($detail->quantity_consumed * $detail->unit_cost_at_time, 2) }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

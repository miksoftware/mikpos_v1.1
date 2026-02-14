<div class="p-6">
    <!-- Header -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Logs de Actividad</h1>
            <p class="text-slate-500 text-sm mt-1">Registro de todas las acciones realizadas en el sistema</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-8 gap-4">
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Período</label>
                <select wire:model.live="dateRange" class="w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm">
                    <option value="today">Hoy</option>
                    <option value="yesterday">Ayer</option>
                    <option value="week">Esta semana</option>
                    <option value="month">Este mes</option>
                    <option value="last_month">Mes anterior</option>
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
                <label class="block text-xs font-medium text-slate-500 mb-1">Usuario</label>
                <select wire:model.live="selectedUserId" class="w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm">
                    <option value="">Todos</option>
                    @foreach($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Módulo</label>
                <select wire:model.live="selectedModule" class="w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm">
                    <option value="">Todos</option>
                    @foreach($modules as $module)
                    <option value="{{ $module }}">{{ ucfirst(str_replace('_', ' ', $module)) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Acción</label>
                <select wire:model.live="selectedAction" class="w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm">
                    <option value="">Todas</option>
                    @foreach($actions as $action)
                    <option value="{{ $action }}">{{ $actionLabels($action) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Buscar</label>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Descripción, usuario..." class="w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm">
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Fecha</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Usuario</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Sucursal</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Módulo</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 uppercase">Acción</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Descripción</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">IP</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 uppercase">Detalle</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($logs as $log)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-4 py-3 text-sm text-slate-600 whitespace-nowrap">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-slate-800">{{ $log->user->name ?? 'Sistema' }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $log->branch->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ ucfirst(str_replace('_', ' ', $log->module)) }}</td>
                        <td class="px-4 py-3 text-sm text-center">
                            <span class="px-2 py-1 rounded-full text-xs font-medium {{ $actionColors($log->action) }}">
                                {{ $actionLabels($log->action) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600 max-w-xs truncate">{{ $log->description }}</td>
                        <td class="px-4 py-3 text-sm text-slate-500 whitespace-nowrap">{{ $log->ip_address ?? '-' }}</td>
                        <td class="px-4 py-3 text-center">
                            <button wire:click="viewDetail({{ $log->id }})" class="p-1.5 text-slate-400 hover:text-[#a855f7] hover:bg-purple-50 rounded-lg transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="px-4 py-8 text-center text-slate-400">No hay registros para mostrar</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-slate-200">{{ $logs->links() }}</div>
    </div>

    <!-- Detail Modal -->
    @if($isDetailModalOpen && $detailLog)
    <div class="relative z-[100]" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="closeDetail"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-xl">
                    <!-- Header -->
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-slate-900">Detalle del Log</h3>
                        <button wire:click="closeDetail" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <!-- Content -->
                    <div class="px-6 py-4 space-y-4 max-h-[70vh] overflow-y-auto">
                        <!-- Info Grid -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs text-slate-500">Fecha</p>
                                <p class="text-sm font-medium text-slate-800">{{ $detailLog['created_at'] }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500">Usuario</p>
                                <p class="text-sm font-medium text-slate-800">{{ $detailLog['user_name'] }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500">Sucursal</p>
                                <p class="text-sm font-medium text-slate-800">{{ $detailLog['branch_name'] }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500">Módulo</p>
                                <p class="text-sm font-medium text-slate-800">{{ ucfirst(str_replace('_', ' ', $detailLog['module'])) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500">Acción</p>
                                <p class="text-sm font-medium text-slate-800">{{ $detailLog['action'] }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500">Modelo</p>
                                <p class="text-sm font-medium text-slate-800">{{ $detailLog['model_type'] }} #{{ $detailLog['model_id'] }}</p>
                            </div>
                            <div class="col-span-2">
                                <p class="text-xs text-slate-500">Descripción</p>
                                <p class="text-sm font-medium text-slate-800">{{ $detailLog['description'] }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500">IP</p>
                                <p class="text-sm font-medium text-slate-800">{{ $detailLog['ip_address'] }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500">User Agent</p>
                                <p class="text-xs text-slate-600 truncate" title="{{ $detailLog['user_agent'] }}">{{ \Illuminate\Support\Str::limit($detailLog['user_agent'], 60) }}</p>
                            </div>
                        </div>

                        <!-- Old Values -->
                        @if(!empty($detailLog['old_values']))
                        <div>
                            <p class="text-xs font-semibold text-slate-500 uppercase mb-2">Valores Anteriores</p>
                            <div class="bg-red-50 border border-red-200 rounded-xl p-3 overflow-x-auto">
                                <table class="w-full text-xs">
                                    <tbody>
                                        @foreach($detailLog['old_values'] as $key => $value)
                                        <tr class="border-b border-red-100 last:border-0">
                                            <td class="py-1.5 pr-3 font-medium text-red-700 whitespace-nowrap">{{ $key }}</td>
                                            <td class="py-1.5 text-red-600 break-all">{{ is_array($value) ? json_encode($value) : $value }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        @endif

                        <!-- New Values -->
                        @if(!empty($detailLog['new_values']))
                        <div>
                            <p class="text-xs font-semibold text-slate-500 uppercase mb-2">Valores Nuevos</p>
                            <div class="bg-green-50 border border-green-200 rounded-xl p-3 overflow-x-auto">
                                <table class="w-full text-xs">
                                    <tbody>
                                        @foreach($detailLog['new_values'] as $key => $value)
                                        <tr class="border-b border-green-100 last:border-0">
                                            <td class="py-1.5 pr-3 font-medium text-green-700 whitespace-nowrap">{{ $key }}</td>
                                            <td class="py-1.5 text-green-600 break-all">{{ is_array($value) ? json_encode($value) : $value }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        @endif
                    </div>
                    <!-- Footer -->
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end">
                        <button wire:click="closeDetail" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

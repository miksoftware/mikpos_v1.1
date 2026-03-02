<div>
    <div class="p-4 sm:p-6 lg:p-8">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Nómina</h1>
                <p class="text-slate-500 text-sm mt-1">Gestión de períodos de nómina</p>
            </div>
            @if(auth()->user()->hasPermission('payrolls.create'))
            <button wire:click="create" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea] transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                Nuevo Período
            </button>
            @endif
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 mb-6">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar..." class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                @if($needsBranchSelection)
                <select wire:model.live="filterBranch" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                    <option value="">Todas las sucursales</option>
                    @foreach($branches as $branch)
                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
                @endif
                <select wire:model.live="filterStatus" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                    <option value="">Todos los estados</option>
                    <option value="borrador">Borrador</option>
                    <option value="calculada">Calculada</option>
                    <option value="aprobada">Aprobada</option>
                    <option value="pagada">Pagada</option>
                </select>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-slate-500 uppercase">Período</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-slate-500 uppercase">Tipo</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-slate-500 uppercase">Sucursal</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-slate-500 uppercase">Fecha Pago</th>
                            <th class="px-4 py-3 text-center text-sm font-semibold text-slate-500 uppercase">Empleados</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-slate-500 uppercase">Total Neto</th>
                            <th class="px-4 py-3 text-center text-sm font-semibold text-slate-500 uppercase">Estado</th>
                            <th class="px-4 py-3 text-center text-sm font-semibold text-slate-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($items as $item)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ $item->period_label }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ ucfirst($item->period_type) }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ $item->branch?->name }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ $item->payment_date->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-sm text-center text-slate-600">{{ $item->details()->count() }}</td>
                            <td class="px-4 py-3 text-sm text-right font-medium text-slate-900">${{ number_format($item->details()->sum('net_pay'), 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full
                                    {{ $item->status === 'borrador' ? 'bg-slate-100 text-slate-700' : '' }}
                                    {{ $item->status === 'calculada' ? 'bg-blue-100 text-blue-700' : '' }}
                                    {{ $item->status === 'aprobada' ? 'bg-amber-100 text-amber-700' : '' }}
                                    {{ $item->status === 'pagada' ? 'bg-green-100 text-green-700' : '' }}
                                ">{{ $item->status_label }}</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <button wire:click="viewDetails({{ $item->id }})" class="p-1.5 text-slate-400 hover:text-blue-600 rounded-lg hover:bg-blue-50" title="Ver detalle">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </button>
                                    @if(in_array($item->status, ['borrador', 'calculada']) && auth()->user()->hasPermission('payrolls.edit'))
                                    <button wire:click="openConfirm('calculate', {{ $item->id }}, 'Calcular Nómina', '¿Desea calcular/recalcular la nómina de este período?')" class="p-1.5 text-slate-400 hover:text-green-600 rounded-lg hover:bg-green-50" title="Calcular">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                    </button>
                                    @endif
                                    @if($item->status === 'calculada' && auth()->user()->hasPermission('payrolls.approve'))
                                    <button wire:click="openConfirm('approve', {{ $item->id }}, 'Aprobar Nómina', '¿Desea aprobar esta nómina? Una vez aprobada no se podrán editar novedades.')" class="p-1.5 text-slate-400 hover:text-amber-600 rounded-lg hover:bg-amber-50" title="Aprobar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </button>
                                    @endif
                                    @if($item->status === 'aprobada' && auth()->user()->hasPermission('payrolls.approve'))
                                    <button wire:click="openConfirm('paid', {{ $item->id }}, 'Marcar como Pagada', '¿Desea marcar esta nómina como pagada? Se descontarán los préstamos activos.')" class="p-1.5 text-slate-400 hover:text-green-600 rounded-lg hover:bg-green-50" title="Marcar pagada">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </button>
                                    @endif
                                    @if($item->status === 'borrador' && auth()->user()->hasPermission('payrolls.delete'))
                                    <button wire:click="confirmDelete({{ $item->id }})" class="p-1.5 text-slate-400 hover:text-red-600 rounded-lg hover:bg-red-50" title="Eliminar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="px-4 py-8 text-center text-slate-500">No se encontraron períodos de nómina</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-slate-200">{{ $items->links() }}</div>
        </div>
    </div>

    <!-- Create Modal -->
    @if($isModalOpen)
    <div class="relative z-[100]" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="$set('isModalOpen', false)"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-xl">
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-slate-900">Nuevo Período de Nómina</h3>
                        <button wire:click="$set('isModalOpen', false)" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="px-6 py-4 space-y-4">
                        @if($needsBranchSelection)
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Sucursal *</label>
                            <select wire:model="branch_id" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                <option value="">Seleccionar...</option>
                                @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                            @error('branch_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        @endif
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Tipo de Período *</label>
                            <select wire:model.live="period_type" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                <option value="mensual">Mensual</option>
                                <option value="quincenal">Quincenal</option>
                                <option value="semanal">Semanal</option>
                            </select>
                            <p class="text-xs text-slate-500 mt-1">Solo se incluirán empleados con esta frecuencia de pago configurada.</p>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Inicio Período *</label>
                                <input type="date" wire:model="period_start" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                @error('period_start') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Fin Período *</label>
                                <input type="date" wire:model="period_end" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                @error('period_end') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Fecha de Pago *</label>
                            <input type="date" wire:model="payment_date" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                            @error('payment_date') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Notas</label>
                            <textarea wire:model="notes" rows="2" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]"></textarea>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
                        <button wire:click="$set('isModalOpen', false)" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">Cancelar</button>
                        <button wire:click="store" class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea]">Crear Nómina</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Detail Modal -->
    @if($isDetailModalOpen && $selectedPayroll)
    <div class="relative z-[100]" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="$set('isDetailModalOpen', false)"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-6xl bg-white rounded-2xl shadow-xl">
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">Detalle Nómina: {{ $selectedPayroll->period_label }}</h3>
                            <p class="text-sm text-slate-500">{{ $selectedPayroll->branch?->name }} — {{ ucfirst($selectedPayroll->period_type) }}</p>
                        </div>
                        <button wire:click="$set('isDetailModalOpen', false)" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="overflow-x-auto max-h-[70vh] overflow-y-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 sticky top-0">
                                <tr>
                                    <th class="px-3 py-2 text-left font-semibold text-slate-500">Empleado</th>
                                    <th class="px-3 py-2 text-right font-semibold text-slate-500">Días</th>
                                    <th class="px-3 py-2 text-right font-semibold text-slate-500">Salario</th>
                                    <th class="px-3 py-2 text-right font-semibold text-slate-500">Aux. Transp.</th>
                                    <th class="px-3 py-2 text-right font-semibold text-slate-500">Extras</th>
                                    <th class="px-3 py-2 text-right font-semibold text-slate-500">Comisiones</th>
                                    <th class="px-3 py-2 text-right font-semibold text-slate-500">Total Dev.</th>
                                    <th class="px-3 py-2 text-right font-semibold text-slate-500">Salud</th>
                                    <th class="px-3 py-2 text-right font-semibold text-slate-500">Pensión</th>
                                    <th class="px-3 py-2 text-right font-semibold text-slate-500">Total Ded.</th>
                                    <th class="px-3 py-2 text-right font-semibold text-green-700">Neto</th>
                                    <th class="px-3 py-2 text-center font-semibold text-slate-500">Acc.</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($payrollDetails as $detail)
                                @php
                                    $totalExtras = (float)$detail->overtime_daytime_value + (float)$detail->overtime_nighttime_value + (float)$detail->overtime_sunday_daytime_value + (float)$detail->overtime_sunday_nighttime_value + (float)$detail->night_surcharge_value + (float)$detail->sunday_holiday_value;
                                @endphp
                                <tr class="hover:bg-slate-50/50">
                                    <td class="px-3 py-2 font-medium text-slate-900">{{ $detail->employee->full_name }}</td>
                                    <td class="px-3 py-2 text-right">{{ number_format($detail->worked_days, 0) }}</td>
                                    <td class="px-3 py-2 text-right">${{ number_format($detail->base_salary_earned, 0, ',', '.') }}</td>
                                    <td class="px-3 py-2 text-right">${{ number_format($detail->transport_allowance_earned, 0, ',', '.') }}</td>
                                    <td class="px-3 py-2 text-right {{ $totalExtras > 0 ? 'text-blue-600 font-medium' : '' }}">${{ number_format($totalExtras, 0, ',', '.') }}</td>
                                    <td class="px-3 py-2 text-right {{ (float)$detail->commissions > 0 ? 'text-purple-600 font-medium' : '' }}">${{ number_format($detail->commissions, 0, ',', '.') }}</td>
                                    <td class="px-3 py-2 text-right font-medium">${{ number_format($detail->total_earned, 0, ',', '.') }}</td>
                                    <td class="px-3 py-2 text-right text-red-600">${{ number_format($detail->health_employee, 0, ',', '.') }}</td>
                                    <td class="px-3 py-2 text-right text-red-600">${{ number_format($detail->pension_employee, 0, ',', '.') }}</td>
                                    <td class="px-3 py-2 text-right text-red-600 font-medium">${{ number_format($detail->total_deductions, 0, ',', '.') }}</td>
                                    <td class="px-3 py-2 text-right font-bold text-green-700">${{ number_format($detail->net_pay, 0, ',', '.') }}</td>
                                    <td class="px-3 py-2 text-center">
                                        @if(in_array($selectedPayroll->status, ['borrador', 'calculada']))
                                        <button wire:click="editNovedad({{ $detail->id }})" class="p-1 text-slate-400 hover:text-amber-600 rounded hover:bg-amber-50" title="Editar novedades">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                        @endif
                                        @if(in_array($selectedPayroll->status, ['calculada', 'aprobada', 'pagada']))
                                        <a href="{{ route('nomina.payslip', $detail->id) }}" target="_blank" class="p-1 text-slate-400 hover:text-blue-600 rounded hover:bg-blue-50 inline-block" title="Imprimir desprendible">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                        </a>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-slate-50 font-bold">
                                <tr>
                                    <td class="px-3 py-2">TOTALES</td>
                                    <td class="px-3 py-2 text-right">{{ number_format($payrollDetails->sum('worked_days'), 0) }}</td>
                                    <td class="px-3 py-2 text-right">${{ number_format($payrollDetails->sum('base_salary_earned'), 0, ',', '.') }}</td>
                                    <td class="px-3 py-2 text-right">${{ number_format($payrollDetails->sum('transport_allowance_earned'), 0, ',', '.') }}</td>
                                    <td class="px-3 py-2 text-right">${{ number_format($payrollDetails->sum(fn($d) => (float)$d->overtime_daytime_value + (float)$d->overtime_nighttime_value + (float)$d->overtime_sunday_daytime_value + (float)$d->overtime_sunday_nighttime_value + (float)$d->night_surcharge_value + (float)$d->sunday_holiday_value), 0, ',', '.') }}</td>
                                    <td class="px-3 py-2 text-right">${{ number_format($payrollDetails->sum('commissions'), 0, ',', '.') }}</td>
                                    <td class="px-3 py-2 text-right">${{ number_format($payrollDetails->sum('total_earned'), 0, ',', '.') }}</td>
                                    <td class="px-3 py-2 text-right text-red-600">${{ number_format($payrollDetails->sum('health_employee'), 0, ',', '.') }}</td>
                                    <td class="px-3 py-2 text-right text-red-600">${{ number_format($payrollDetails->sum('pension_employee'), 0, ',', '.') }}</td>
                                    <td class="px-3 py-2 text-right text-red-600">${{ number_format($payrollDetails->sum('total_deductions'), 0, ',', '.') }}</td>
                                    <td class="px-3 py-2 text-right text-green-700">${{ number_format($payrollDetails->sum('net_pay'), 0, ',', '.') }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Novedad Edit Modal -->
    @if($isNovedadModalOpen)
    <div class="relative z-[110]" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[110]" wire:click="$set('isNovedadModalOpen', false)"></div>
        <div class="fixed inset-0 z-[111] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-xl">
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-slate-900">Novedades: {{ $novedadEmployeeName }}</h3>
                        <button wire:click="$set('isNovedadModalOpen', false)" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="px-6 py-4 space-y-4 max-h-[70vh] overflow-y-auto">
                        <h4 class="text-sm font-semibold text-slate-700">Horas Extras y Recargos</h4>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">H.E. Diurnas</label>
                                <input type="number" wire:model="novedad_overtime_daytime_hours" step="0.5" min="0" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">H.E. Nocturnas</label>
                                <input type="number" wire:model="novedad_overtime_nighttime_hours" step="0.5" min="0" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">H.E. Dom. Diurnas</label>
                                <input type="number" wire:model="novedad_overtime_sunday_daytime_hours" step="0.5" min="0" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">H.E. Dom. Nocturnas</label>
                                <input type="number" wire:model="novedad_overtime_sunday_nighttime_hours" step="0.5" min="0" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Recargo Nocturno</label>
                                <input type="number" wire:model="novedad_night_surcharge_hours" step="0.5" min="0" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Recargo Dom/Fest.</label>
                                <input type="number" wire:model="novedad_sunday_holiday_hours" step="0.5" min="0" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                            </div>
                        </div>

                        <h4 class="text-sm font-semibold text-slate-700 pt-2">Ingresos</h4>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Comisiones (automático)</label>
                                <input type="number" value="{{ $novedadCommissions }}" disabled class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm bg-slate-50 text-slate-500">
                                <p class="text-xs text-slate-400 mt-0.5">Calculadas desde ventas</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Bonificaciones</label>
                                <input type="number" wire:model="novedad_bonuses" step="1" min="0" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Otros Ingresos</label>
                                <input type="number" wire:model="novedad_other_income" step="1" min="0" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                            </div>
                        </div>

                        <h4 class="text-sm font-semibold text-slate-700 pt-2">Deducciones Adicionales</h4>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Cooperativa</label>
                                <input type="number" wire:model="novedad_cooperative_deduction" step="1" min="0" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Libranza</label>
                                <input type="number" wire:model="novedad_libranza_deduction" step="1" min="0" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Otras Deducciones</label>
                                <input type="number" wire:model="novedad_other_deductions" step="1" min="0" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
                        <button wire:click="$set('isNovedadModalOpen', false)" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">Cancelar</button>
                        <button wire:click="saveNovedad" class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea]">Guardar y Recalcular</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Confirm Action Modal -->
    @if($isConfirmModalOpen)
    <div class="relative z-[100]" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="$set('isConfirmModalOpen', false)"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-md bg-white rounded-2xl shadow-xl p-6 text-center">
                    <div class="mx-auto w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">{{ $confirmTitle }}</h3>
                    <p class="text-slate-500 mb-6">{{ $confirmMessage }}</p>
                    <div class="flex justify-center gap-3">
                        <button wire:click="$set('isConfirmModalOpen', false)" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">Cancelar</button>
                        <button wire:click="executeConfirm" class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea]">Confirmar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Delete Modal -->
    @if($isDeleteModalOpen)
    <div class="relative z-[100]" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="$set('isDeleteModalOpen', false)"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-md bg-white rounded-2xl shadow-xl p-6 text-center">
                    <div class="mx-auto w-12 h-12 rounded-full bg-red-100 flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Eliminar Nómina</h3>
                    <p class="text-slate-500 mb-6">¿Está seguro de eliminar este período de nómina?</p>
                    <div class="flex justify-center gap-3">
                        <button wire:click="$set('isDeleteModalOpen', false)" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">Cancelar</button>
                        <button wire:click="delete" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-xl hover:bg-red-700">Eliminar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

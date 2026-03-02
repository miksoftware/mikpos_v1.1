<div>
    <div class="p-4 sm:p-6 lg:p-8">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Empleados</h1>
                <p class="text-slate-500 text-sm mt-1">Gestión de empleados del módulo de nómina</p>
            </div>
            @if(auth()->user()->hasPermission('employees.create'))
            <button wire:click="create" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea] transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                Nuevo Empleado
            </button>
            @endif
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 mb-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar empleado..." class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
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
                    <option value="activo">Activo</option>
                    <option value="vacaciones">Vacaciones</option>
                    <option value="incapacidad">Incapacidad</option>
                    <option value="retirado">Retirado</option>
                </select>
                <select wire:model.live="filterContractType" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                    <option value="">Todos los contratos</option>
                    <option value="indefinido">Indefinido</option>
                    <option value="fijo">Fijo</option>
                    <option value="obra_labor">Obra/Labor</option>
                    <option value="aprendizaje">Aprendizaje</option>
                    <option value="prestacion_servicios">Prestación de Servicios</option>
                </select>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-slate-500 uppercase">Empleado</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-slate-500 uppercase">Documento</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-slate-500 uppercase">Cargo</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-slate-500 uppercase">Contrato</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-slate-500 uppercase">Salario</th>
                            <th class="px-4 py-3 text-center text-sm font-semibold text-slate-500 uppercase">Estado</th>
                            <th class="px-4 py-3 text-center text-sm font-semibold text-slate-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($items as $item)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-900">{{ $item->full_name }}</div>
                                <div class="text-xs text-slate-500">{{ $item->branch?->name }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ $item->document_type }} {{ $item->document_number }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ $item->position }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full
                                    {{ $item->contract_type === 'indefinido' ? 'bg-blue-100 text-blue-700' : '' }}
                                    {{ $item->contract_type === 'fijo' ? 'bg-amber-100 text-amber-700' : '' }}
                                    {{ $item->contract_type === 'obra_labor' ? 'bg-purple-100 text-purple-700' : '' }}
                                    {{ $item->contract_type === 'aprendizaje' ? 'bg-green-100 text-green-700' : '' }}
                                    {{ $item->contract_type === 'prestacion_servicios' ? 'bg-slate-100 text-slate-700' : '' }}
                                ">{{ ucfirst(str_replace('_', ' ', $item->contract_type)) }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-right font-medium text-slate-900">${{ number_format($item->base_salary, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full
                                    {{ $item->status === 'activo' ? 'bg-green-100 text-green-700' : '' }}
                                    {{ $item->status === 'vacaciones' ? 'bg-blue-100 text-blue-700' : '' }}
                                    {{ $item->status === 'incapacidad' ? 'bg-amber-100 text-amber-700' : '' }}
                                    {{ $item->status === 'retirado' ? 'bg-red-100 text-red-700' : '' }}
                                ">{{ ucfirst($item->status) }}</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <button wire:click="show({{ $item->id }})" class="p-1.5 text-slate-400 hover:text-blue-600 rounded-lg hover:bg-blue-50" title="Ver">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </button>
                                    @if(auth()->user()->hasPermission('employees.edit'))
                                    <button wire:click="edit({{ $item->id }})" class="p-1.5 text-slate-400 hover:text-amber-600 rounded-lg hover:bg-amber-50" title="Editar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                    @endif
                                    @if(auth()->user()->hasPermission('employees.delete'))
                                    <button wire:click="confirmDelete({{ $item->id }})" class="p-1.5 text-slate-400 hover:text-red-600 rounded-lg hover:bg-red-50" title="Eliminar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">No se encontraron empleados</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-slate-200">{{ $items->links() }}</div>
        </div>
    </div>

    <!-- Create/Edit Modal -->
    @if($isModalOpen)
    <div class="relative z-[100]" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="$set('isModalOpen', false)"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-3xl bg-white rounded-2xl shadow-xl">
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-slate-900">{{ $itemId ? 'Editar' : 'Nuevo' }} Empleado</h3>
                        <button wire:click="$set('isModalOpen', false)" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="px-6 py-4 space-y-6 max-h-[70vh] overflow-y-auto">
                        <!-- Datos Personales -->
                        <div>
                            <h4 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#a855f7]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                Datos Personales
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Nombres *</label>
                                    <input type="text" wire:model="first_name" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                    @error('first_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Apellidos *</label>
                                    <input type="text" wire:model="last_name" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                    @error('last_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Tipo Documento *</label>
                                    <select wire:model="document_type" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                        <option value="CC">Cédula de Ciudadanía</option>
                                        <option value="CE">Cédula de Extranjería</option>
                                        <option value="PA">Pasaporte</option>
                                        <option value="TI">Tarjeta de Identidad</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Número Documento *</label>
                                    <input type="text" wire:model="document_number" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                    @error('document_number') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                                    <input type="email" wire:model="email" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Teléfono</label>
                                    <input type="text" wire:model="phone" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Fecha Nacimiento</label>
                                    <input type="date" wire:model="birth_date" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Dirección</label>
                                    <input type="text" wire:model="address" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                </div>
                            </div>
                        </div>

                        <!-- Contrato y Salario -->
                        <div>
                            <h4 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#a855f7]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                Contrato y Salario
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Usuario del Sistema</label>
                                    <select wire:model="user_id" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                        <option value="">Sin usuario vinculado</option>
                                        @foreach($users as $u)
                                        <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Fecha Ingreso *</label>
                                    <input type="date" wire:model="hire_date" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                    @error('hire_date') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Cargo *</label>
                                    <input type="text" wire:model="position" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                    @error('position') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Departamento</label>
                                    <input type="text" wire:model="department" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Tipo Contrato *</label>
                                    <select wire:model.live="contract_type" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                        <option value="indefinido">Indefinido</option>
                                        <option value="fijo">Fijo</option>
                                        <option value="obra_labor">Obra/Labor</option>
                                        <option value="aprendizaje">Aprendizaje</option>
                                        <option value="prestacion_servicios">Prestación de Servicios</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Estado</label>
                                    <select wire:model="status" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                        <option value="activo">Activo</option>
                                        <option value="vacaciones">Vacaciones</option>
                                        <option value="incapacidad">Incapacidad</option>
                                        <option value="retirado">Retirado</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Prestación de servicios alert -->
                            @if($contract_type === 'prestacion_servicios')
                            <div class="mt-4 p-3 bg-amber-50 border border-amber-200 rounded-xl">
                                <div class="flex items-start gap-2">
                                    <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <div class="text-sm text-amber-700">
                                        <span class="font-medium">Contratista:</span> La seguridad social (salud, pensión, ARL) es responsabilidad del contratista. No aplica auxilio de transporte ni prestaciones sociales.
                                    </div>
                                </div>
                            </div>
                            @endif

                            <!-- Aprendizaje alert -->
                            @if($contract_type === 'aprendizaje')
                            <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-xl">
                                <div class="flex items-start gap-2">
                                    <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <div class="text-sm text-blue-700">
                                        <span class="font-medium">Aprendiz:</span> El apoyo de sostenimiento en etapa productiva es el 75% del SMMLV (${{ number_format($smmlv * 0.75, 0, ',', '.') }}). Se asignó automáticamente.
                                    </div>
                                </div>
                            </div>
                            @endif

                            <!-- Salary Section -->
                            @if($contract_type !== 'prestacion_servicios')
                            <div class="mt-4 p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-4">
                                <h5 class="text-sm font-semibold text-slate-600">Configuración Salarial</h5>

                                <!-- Tipo de Salario -->
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Tipo de Salario *</label>
                                    <select wire:model.live="salary_type" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" {{ $contract_type === 'aprendizaje' ? 'disabled' : '' }}>
                                        <option value="minimo">Salario Mínimo (SMMLV)</option>
                                        <option value="otro">Salario Diferente</option>
                                        <option value="integral">Integral (mín. 13 SMMLV)</option>
                                    </select>
                                </div>

                                <!-- SALARIO MÍNIMO -->
                                @if($salary_type === 'minimo')
                                <div class="p-3 bg-green-50 border border-green-200 rounded-xl">
                                    <div class="flex items-center gap-2 mb-2">
                                        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        <span class="text-sm font-medium text-green-700">Valores cargados automáticamente (2026)</span>
                                    </div>
                                    <div class="grid grid-cols-2 gap-3 text-sm">
                                        <div>
                                            <span class="text-slate-500">Salario Base:</span>
                                            <span class="font-semibold text-slate-900 ml-1">${{ number_format($smmlv, 0, ',', '.') }}</span>
                                        </div>
                                        <div>
                                            <span class="text-slate-500">Auxilio de Transporte:</span>
                                            <span class="font-semibold text-slate-900 ml-1">${{ number_format($transportAllowanceValue, 0, ',', '.') }}</span>
                                        </div>
                                        <div class="col-span-2 pt-2 border-t border-green-200">
                                            <span class="text-slate-500">Total Devengado:</span>
                                            <span class="font-bold text-green-700 ml-1">${{ number_format($smmlv + $transportAllowanceValue, 0, ',', '.') }}</span>
                                        </div>
                                    </div>
                                </div>
                                @endif

                                <!-- SALARIO DIFERENTE -->
                                @if($salary_type === 'otro')
                                <div class="space-y-3">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1">Salario *</label>
                                        <input type="number" wire:model.live.debounce.500ms="base_salary" step="1" min="0" placeholder="Ingrese el salario..." class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                        @error('base_salary') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>

                                    @if($base_salary && $base_salary > 0)
                                    <!-- Transport allowance checkbox -->
                                    <div class="flex items-start gap-3 p-3 bg-white rounded-xl border border-slate-200">
                                        <input type="checkbox" wire:model.live="transport_included_in_salary" id="transport_included" class="mt-0.5 rounded border-slate-300 text-[#ff7261] focus:ring-[#ff7261]">
                                        <label for="transport_included" class="text-sm text-slate-700">
                                            <span class="font-medium">El auxilio de transporte está incluido en este salario</span>
                                            <p class="text-xs text-slate-500 mt-0.5">Si marca esta opción, se restará ${{ number_format($transportAllowanceValue, 0, ',', '.') }} del salario ingresado para obtener la base real.</p>
                                        </label>
                                    </div>

                                    <!-- Computed breakdown -->
                                    <div class="p-3 bg-blue-50 border border-blue-200 rounded-xl">
                                        <div class="flex items-center gap-2 mb-2">
                                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                            <span class="text-sm font-medium text-blue-700">Desglose Salarial</span>
                                        </div>
                                        <div class="grid grid-cols-2 gap-3 text-sm">
                                            <div>
                                                <span class="text-slate-500">Base Real:</span>
                                                <span class="font-semibold text-slate-900 ml-1">${{ number_format($computed_base_salary, 0, ',', '.') }}</span>
                                            </div>
                                            <div>
                                                <span class="text-slate-500">Auxilio Transporte:</span>
                                                <span class="font-semibold text-slate-900 ml-1">
                                                    @if($computed_transport > 0)
                                                        ${{ number_format($computed_transport, 0, ',', '.') }}
                                                    @else
                                                        <span class="text-slate-400">No aplica (> 2 SMMLV)</span>
                                                    @endif
                                                </span>
                                            </div>
                                        </div>
                                        @if($computed_transport == 0 && !$transport_included_in_salary)
                                        <p class="text-xs text-slate-500 mt-2">El auxilio de transporte solo aplica para salarios hasta 2 SMMLV (${{ number_format($smmlv * 2, 0, ',', '.') }}).</p>
                                        @endif
                                    </div>
                                    @endif
                                </div>
                                @endif

                                <!-- SALARIO INTEGRAL -->
                                @if($salary_type === 'integral')
                                <div class="space-y-3">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1">Salario Integral *</label>
                                        <input type="number" wire:model.live.debounce.500ms="base_salary" step="1" min="0" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                        @error('base_salary') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                        <p class="text-xs text-slate-500 mt-1">Mínimo 13 SMMLV = ${{ number_format($smmlv * 13, 0, ',', '.') }}. Incluye prestaciones sociales (30% factor prestacional).</p>
                                    </div>
                                    <div class="p-3 bg-purple-50 border border-purple-200 rounded-xl text-sm">
                                        <div class="flex items-center gap-2 mb-1">
                                            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            <span class="font-medium text-purple-700">Salario Integral</span>
                                        </div>
                                        <p class="text-purple-600">No aplica auxilio de transporte, cesantías, intereses de cesantías ni prima de servicios. El IBC para seguridad social es el 70% del salario.</p>
                                    </div>
                                </div>
                                @endif
                            </div>
                            @else
                            <!-- Prestación de servicios: solo pedir el valor del contrato -->
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-slate-700 mb-1">Valor del Contrato / Honorarios *</label>
                                <input type="number" wire:model="base_salary" step="1" min="0" placeholder="Valor mensual de honorarios..." class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                @error('base_salary') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                            @endif

                            <!-- ARL Risk Level - hide for prestacion_servicios -->
                            @if($contract_type !== 'prestacion_servicios')
                            <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Nivel Riesgo ARL *</label>
                                    <select wire:model="risk_level" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                        <option value="I">I - Mínimo (0.522%)</option>
                                        <option value="II">II - Bajo (1.044%)</option>
                                        <option value="III">III - Medio (2.436%)</option>
                                        <option value="IV">IV - Alto (4.350%)</option>
                                        <option value="V">V - Máximo (6.960%)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Frecuencia de Pago *</label>
                                    <select wire:model="payment_frequency" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                        <option value="semanal">Semanal</option>
                                        <option value="quincenal">Quincenal</option>
                                        <option value="mensual">Mensual</option>
                                    </select>
                                </div>
                            </div>
                            @endif
                        </div>

                        <!-- Seguridad Social - hide for prestacion_servicios -->
                        @if($contract_type !== 'prestacion_servicios')
                        <div>
                            <h4 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#a855f7]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                Seguridad Social
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">EPS</label>
                                    <input type="text" wire:model="health_fund" placeholder="Ej: Sura, Sanitas..." class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Fondo de Pensiones</label>
                                    <input type="text" wire:model="pension_fund" placeholder="Ej: Porvenir, Protección..." class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Fondo de Cesantías</label>
                                    <input type="text" wire:model="severance_fund" placeholder="Ej: Porvenir, Protección..." class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Caja de Compensación</label>
                                    <input type="text" wire:model="compensation_fund" placeholder="Ej: Comfama, Cafam..." class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Datos Bancarios -->
                        <div>
                            <h4 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#a855f7]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                Datos Bancarios
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Banco</label>
                                    <input type="text" wire:model="bank_name" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Tipo Cuenta</label>
                                    <select wire:model="bank_account_type" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                        <option value="">Seleccionar...</option>
                                        <option value="ahorros">Ahorros</option>
                                        <option value="corriente">Corriente</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Número Cuenta</label>
                                    <input type="text" wire:model="bank_account_number" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
                        <button wire:click="$set('isModalOpen', false)" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">Cancelar</button>
                        <button wire:click="store" class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea]">Guardar</button>
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
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Eliminar Empleado</h3>
                    <p class="text-slate-500 mb-6">¿Está seguro de eliminar este empleado? Esta acción no se puede deshacer.</p>
                    <div class="flex justify-center gap-3">
                        <button wire:click="$set('isDeleteModalOpen', false)" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">Cancelar</button>
                        <button wire:click="delete" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-xl hover:bg-red-700">Eliminar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Show Modal -->
    @if($isShowModalOpen && $showEmployee)
    <div class="relative z-[100]" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="$set('isShowModalOpen', false)"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-xl">
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-slate-900">{{ $showEmployee->full_name }}</h3>
                        <button wire:click="$set('isShowModalOpen', false)" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="px-6 py-4 space-y-4 max-h-[70vh] overflow-y-auto">
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div><span class="text-slate-500">Documento:</span> <span class="font-medium">{{ $showEmployee->document_type }} {{ $showEmployee->document_number }}</span></div>
                            <div><span class="text-slate-500">Sucursal:</span> <span class="font-medium">{{ $showEmployee->branch?->name }}</span></div>
                            <div><span class="text-slate-500">Cargo:</span> <span class="font-medium">{{ $showEmployee->position }}</span></div>
                            <div>
                                <span class="text-slate-500">Contrato:</span>
                                <span class="font-medium">{{ ucfirst(str_replace('_', ' ', $showEmployee->contract_type)) }}</span>
                            </div>
                            <div>
                                <span class="text-slate-500">Tipo Salario:</span>
                                <span class="font-medium">
                                    @if($showEmployee->salary_type === 'minimo') Salario Mínimo
                                    @elseif($showEmployee->salary_type === 'integral') Integral
                                    @else Salario Diferente
                                    @endif
                                </span>
                            </div>
                            <div>
                                <span class="text-slate-500">Salario:</span>
                                <span class="font-medium">${{ number_format($showEmployee->base_salary, 0, ',', '.') }}</span>
                            </div>
                            @if($showEmployee->transport_included_in_salary)
                            <div class="col-span-2 p-2 bg-blue-50 rounded-lg text-xs text-blue-700">
                                El auxilio de transporte está incluido en el salario. Base real: ${{ number_format($showEmployee->real_base_salary, 0, ',', '.') }} + Auxilio: ${{ number_format($transportAllowanceValue, 0, ',', '.') }}
                            </div>
                            @endif
                            <div><span class="text-slate-500">Ingreso:</span> <span class="font-medium">{{ $showEmployee->hire_date?->format('d/m/Y') }}</span></div>
                            @if($showEmployee->contract_type !== 'prestacion_servicios')
                            <div><span class="text-slate-500">ARL Nivel:</span> <span class="font-medium">{{ $showEmployee->risk_level }}</span></div>
                            <div><span class="text-slate-500">EPS:</span> <span class="font-medium">{{ $showEmployee->health_fund ?: '-' }}</span></div>
                            <div><span class="text-slate-500">Pensión:</span> <span class="font-medium">{{ $showEmployee->pension_fund ?: '-' }}</span></div>
                            <div><span class="text-slate-500">Cesantías:</span> <span class="font-medium">{{ $showEmployee->severance_fund ?: '-' }}</span></div>
                            <div><span class="text-slate-500">Caja Compensación:</span> <span class="font-medium">{{ $showEmployee->compensation_fund ?: '-' }}</span></div>
                            @else
                            <div class="col-span-2 p-2 bg-amber-50 rounded-lg text-xs text-amber-700">
                                Contratista: la seguridad social es responsabilidad del contratista.
                            </div>
                            @endif
                            <div><span class="text-slate-500">Banco:</span> <span class="font-medium">{{ $showEmployee->bank_name ?: '-' }} {{ $showEmployee->bank_account_number }}</span></div>
                            <div>
                                <span class="text-slate-500">Estado:</span>
                                <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full
                                    {{ $showEmployee->status === 'activo' ? 'bg-green-100 text-green-700' : '' }}
                                    {{ $showEmployee->status === 'vacaciones' ? 'bg-blue-100 text-blue-700' : '' }}
                                    {{ $showEmployee->status === 'incapacidad' ? 'bg-amber-100 text-amber-700' : '' }}
                                    {{ $showEmployee->status === 'retirado' ? 'bg-red-100 text-red-700' : '' }}
                                ">{{ ucfirst($showEmployee->status) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

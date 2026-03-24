<div class="space-y-6">
    <!-- Header & Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Gestión de Sucursales</h1>
            <p class="text-slate-500 mt-1">Administra las sucursales del sistema</p>
        </div>
        @if(auth()->user()->hasPermission('branches.create'))
        <button wire:click="create" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-[#ff7261] to-[#a855f7] hover:from-[#e55a4a] hover:to-[#9333ea] text-white text-sm font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-[1.02] transition-all duration-200">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Nueva Sucursal
        </button>
        @endif
    </div>

    <!-- Search -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
            <input wire:model.live.debounce.300ms="search" type="text" class="block w-full pl-10 pr-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 placeholder-slate-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] transition-all sm:text-sm" placeholder="Buscar por nombre, código o ciudad...">
        </div>
    </div>

    <!-- Branches Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase tracking-wider">Sucursal</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase tracking-wider">Ubicación</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase tracking-wider">Contacto</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase tracking-wider">Estado</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-200">
                    @forelse($branches as $branch)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                @if($branch->logo)
                                <img src="{{ Storage::url($branch->logo) }}" alt="{{ $branch->name }}" class="w-10 h-10 rounded-xl object-contain border border-slate-200 bg-white flex-shrink-0">
                                @else
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br {{ $branch->is_active ? 'from-[#ff7261]/20 to-[#a855f7]/20' : 'from-slate-100 to-slate-200' }} flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 {{ $branch->is_active ? 'text-[#ff7261]' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                </div>
                                @endif
                                <div class="ml-4">
                                    <div class="text-sm font-semibold text-slate-900">{{ $branch->name }}</div>
                                    <div class="text-sm text-slate-500">{{ $branch->code }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-slate-900">{{ $branch->municipality?->name ?: '-' }}</div>
                            <div class="text-sm text-slate-500">{{ $branch->department?->name ?: '' }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-slate-900">{{ $branch->phone ?: '-' }}</div>
                            <div class="text-sm text-slate-500 truncate max-w-[200px]">{{ $branch->email ?: '' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                @if(auth()->user()->hasPermission('branches.edit'))
                                <button wire:click="toggleStatus({{ $branch->id }})" class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 {{ $branch->is_active ? 'bg-[#ff7261]' : 'bg-slate-200' }}">
                                    <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200 {{ $branch->is_active ? 'translate-x-5' : 'translate-x-0' }}"></span>
                                </button>
                                @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-sm font-medium {{ $branch->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                    {{ $branch->is_active ? 'Activa' : 'Inactiva' }}
                                </span>
                                @endif
                                @if($branch->show_in_pos)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-sm font-medium bg-green-100 text-green-700">POS</span>
                                @endif
                                @if($branch->ecommerce_enabled)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-sm font-medium bg-purple-100 text-purple-700">Tienda</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <div class="flex items-center justify-end gap-1">
                                <button wire:click="view({{ $branch->id }})" class="p-2 text-slate-400 hover:text-[#a855f7] hover:bg-purple-50 rounded-lg transition-colors" title="Ver">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                </button>
                                @if(auth()->user()->hasPermission('branches.edit'))
                                <button wire:click="edit({{ $branch->id }})" class="p-2 text-slate-400 hover:text-[#ff7261] hover:bg-orange-50 rounded-lg transition-colors" title="Editar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </button>
                                @endif
                                @if(auth()->user()->hasPermission('branches.delete'))
                                <button wire:click="confirmDelete({{ $branch->id }})" class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Desactivar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                                </button>
                                @endif
                                @if(str_contains(auth()->user()->email ?? '', 'softwaremik'))
                                <button wire:click="confirmClean({{ $branch->id }})" class="p-2 text-slate-400 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-colors" title="Limpiar sucursal">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center">
                            <svg class="w-12 h-12 mx-auto text-slate-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                            <p class="text-lg font-medium text-slate-900">No hay sucursales</p>
                            <p class="text-slate-500">Comienza creando tu primera sucursal</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($branches->hasPages())
        <div class="px-6 py-4 border-t border-slate-200">
            {{ $branches->links() }}
        </div>
        @endif
    </div>

    <!-- Create/Edit Modal -->
    @if($isModalOpen)
    <div class="relative z-[100]" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="$set('isModalOpen', false)"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-xl">
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-slate-900">{{ $branchId ? 'Editar Sucursal' : 'Nueva Sucursal' }}</h3>
                        <button wire:click="$set('isModalOpen', false)" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                    
                    <div class="px-6 py-4 max-h-[70vh] overflow-y-auto space-y-6">
                        <!-- Basic Info -->
                        <div>
                            <h4 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#ff7261]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Información Básica
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Código *</label>
                                    <input wire:model="code" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] uppercase" placeholder="SUC001" maxlength="10">
                                    @error('code') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Nombre *</label>
                                    <input wire:model="name" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="Sucursal Principal">
                                    @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Logo</label>
                                    <div class="flex items-center gap-4">
                                        @if($existingLogo && !$logo)
                                            <div class="relative">
                                                <img src="{{ Storage::url($existingLogo) }}" alt="Logo" class="w-16 h-16 rounded-xl object-contain border border-slate-200 bg-white">
                                                <button type="button" wire:click="removeLogo" class="absolute -top-2 -right-2 w-5 h-5 bg-red-500 text-white rounded-full flex items-center justify-center text-xs hover:bg-red-600">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                                </button>
                                            </div>
                                        @elseif($logo)
                                            <img src="{{ $logo->temporaryUrl() }}" alt="Preview" class="w-16 h-16 rounded-xl object-contain border border-slate-200 bg-white">
                                        @endif
                                        <div class="flex-1">
                                            <input wire:model="logo" type="file" accept="image/*" class="w-full text-sm text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">
                                            <p class="text-xs text-slate-400 mt-1">JPG, PNG, WebP o SVG. Máx 2MB.</p>
                                            @error('logo') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">CUIT/RUC/NIT</label>
                                    <input wire:model="tax_id" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Nro. Actividad</label>
                                    <input wire:model="activity_number" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                </div>
                            </div>
                        </div>

                        <!-- Location -->
                        <div>
                            <h4 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#a855f7]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
                                Ubicación
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Departamento</label>
                                    <x-searchable-select
                                        wire:model.live="department_id"
                                        :options="$departments"
                                        placeholder="Seleccionar departamento..."
                                        searchPlaceholder="Buscar departamento..."
                                    />
                                </div>
                                <div wire:key="municipality-select-{{ $department_id }}">
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Municipio</label>
                                    @if($department_id && count($municipalities) > 0)
                                        <x-searchable-select
                                            wire:model="municipality_id"
                                            :options="$municipalities"
                                            placeholder="Seleccionar municipio..."
                                            searchPlaceholder="Buscar municipio..."
                                        />
                                    @else
                                        <div class="relative w-full cursor-not-allowed rounded-xl border border-slate-300 bg-slate-50 py-2.5 pl-3 pr-10 text-left text-sm text-slate-400">
                                            {{ $department_id ? 'Cargando municipios...' : 'Esperando departamento...' }}
                                        </div>
                                    @endif
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Dirección</label>
                                    <input wire:model="address" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                </div>
                            </div>
                        </div>

                        <!-- Contact -->
                        <div>
                            <h4 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#ff7261]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                Contacto
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Teléfono</label>
                                    <input wire:model="phone" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                                    <input wire:model="email" type="email" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                    @error('email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Invoice Prefixes -->
                        <div>
                            <h4 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#a855f7]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                Prefijos de Documentos
                            </h4>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Ticket</label>
                                    <input wire:model="ticket_prefix" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="T001-">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Factura</label>
                                    <input wire:model="invoice_prefix" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="F001-">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Recibo</label>
                                    <input wire:model="receipt_prefix" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="R001-">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Nota Crédito</label>
                                    <input wire:model="credit_note_prefix" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="NC001-">
                                </div>
                            </div>
                        </div>

                        <!-- Settings -->
                        <div class="flex flex-wrap gap-6">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input wire:model="show_in_pos" type="checkbox" class="w-4 h-4 rounded border-slate-300 text-[#ff7261] focus:ring-[#ff7261]">
                                <span class="text-sm text-slate-700">Mostrar en POS</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input wire:model="is_active" type="checkbox" class="w-4 h-4 rounded border-slate-300 text-[#ff7261] focus:ring-[#ff7261]">
                                <span class="text-sm text-slate-700">Sucursal Activa</span>
                            </label>
                            @if(str_contains(auth()->user()->email ?? '', 'softwaremik'))
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input wire:model="ecommerce_enabled" type="checkbox" class="w-4 h-4 rounded border-slate-300 text-[#a855f7] focus:ring-[#a855f7]">
                                <span class="text-sm text-slate-700">Tienda en Línea</span>
                            </label>
                            @if($ecommerce_enabled)
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input wire:model="show_stock_in_shop" type="checkbox" class="w-4 h-4 rounded border-slate-300 text-[#a855f7] focus:ring-[#a855f7]">
                                <span class="text-sm text-slate-700">Mostrar cantidad disponible en tienda</span>
                            </label>
                            @endif
                            @endif
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

    <!-- View Modal -->
    @if($isViewModalOpen && $viewingBranch)
    <div class="relative z-[100]" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="$set('isViewModalOpen', false)"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-xl">
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-slate-900">Detalles de Sucursal</h3>
                        <button wire:click="$set('isViewModalOpen', false)" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="flex items-center gap-4">
                            @if($viewingBranch->logo)
                            <img src="{{ Storage::url($viewingBranch->logo) }}" alt="{{ $viewingBranch->name }}" class="w-16 h-16 rounded-xl object-contain border border-slate-200 bg-white">
                            @else
                            <div class="w-16 h-16 rounded-xl bg-gradient-to-br from-[#ff7261]/20 to-[#a855f7]/20 flex items-center justify-center">
                                <svg class="w-8 h-8 text-[#ff7261]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                            </div>
                            @endif
                            <div>
                                <h4 class="text-xl font-bold text-slate-800">{{ $viewingBranch->name }}</h4>
                                <p class="text-slate-500">{{ $viewingBranch->code }}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4 pt-4 border-t border-slate-100">
                            <div>
                                <p class="text-sm text-slate-500 uppercase">Estado</p>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-sm font-medium {{ $viewingBranch->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">{{ $viewingBranch->is_active ? 'Activa' : 'Inactiva' }}</span>
                            </div>
                            <div>
                                <p class="text-sm text-slate-500 uppercase">Usuarios</p>
                                <p class="font-semibold text-slate-800">{{ $viewingBranch->users_count }}</p>
                            </div>
                            @if($viewingBranch->tax_id)
                            <div><p class="text-sm text-slate-500 uppercase">CUIT/RUC</p><p class="font-medium text-slate-800">{{ $viewingBranch->tax_id }}</p></div>
                            @endif
                            @if($viewingBranch->phone)
                            <div><p class="text-sm text-slate-500 uppercase">Teléfono</p><p class="font-medium text-slate-800">{{ $viewingBranch->phone }}</p></div>
                            @endif
                            @if($viewingBranch->email)
                            <div class="col-span-2"><p class="text-sm text-slate-500 uppercase">Email</p><p class="font-medium text-slate-800">{{ $viewingBranch->email }}</p></div>
                            @endif
                            @if($viewingBranch->address)
                            <div class="col-span-2"><p class="text-sm text-slate-500 uppercase">Dirección</p><p class="font-medium text-slate-800">{{ $viewingBranch->address }}{{ $viewingBranch->municipality ? ', ' . $viewingBranch->municipality->name : '' }}{{ $viewingBranch->department ? ' - ' . $viewingBranch->department->name : '' }}</p></div>
                            @endif
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end">
                        <button wire:click="edit({{ $viewingBranch->id }}); $set('isViewModalOpen', false)" class="px-4 py-2 text-sm font-medium text-white bg-[#ff7261] rounded-xl hover:bg-[#e55a4a]">Editar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Delete Confirmation Modal -->
    @if($isDeleteModalOpen)
    <div class="relative z-[100]" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="$set('isDeleteModalOpen', false)"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-md bg-white rounded-2xl shadow-xl p-6 text-center">
                    <div class="mx-auto w-12 h-12 rounded-full bg-red-100 flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Desactivar Sucursal</h3>
                    <p class="text-slate-500 mb-6">¿Estás seguro de que deseas desactivar esta sucursal?</p>
                    <div class="flex justify-center gap-3">
                        <button wire:click="$set('isDeleteModalOpen', false)" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">Cancelar</button>
                        <button wire:click="delete" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-xl hover:bg-red-700">Desactivar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Clean Branch Confirmation Modal -->
    @if($isCleanModalOpen)
    <div class="relative z-[100]" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="$set('isCleanModalOpen', false)"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-md bg-white rounded-2xl shadow-xl p-6 text-center">
                    <div class="mx-auto w-12 h-12 rounded-full bg-amber-100 flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Limpiar Sucursal</h3>
                    <p class="text-slate-500 mb-2">Esta acción eliminará permanentemente:</p>
                    <ul class="text-sm text-slate-600 text-left mb-4 space-y-1 px-4">
                        <li class="flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-red-400 flex-shrink-0"></span> Todas las ventas, facturas y pedidos</li>
                        <li class="flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-red-400 flex-shrink-0"></span> Notas crédito, devoluciones y pagos</li>
                        <li class="flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-red-400 flex-shrink-0"></span> Compras y movimientos de inventario</li>
                        <li class="flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-red-400 flex-shrink-0"></span> Arqueos de caja y movimientos</li>
                        <li class="flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-red-400 flex-shrink-0"></span> Gastos y logs de actividad</li>
                        <li class="flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-amber-400 flex-shrink-0"></span> Stock de productos se reiniciará a 0</li>
                    </ul>
                    <p class="text-sm text-red-600 font-semibold mb-4">Escribe LIMPIAR para confirmar</p>
                    <input type="text" wire:model="cleanConfirmText" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500 text-center text-sm font-mono uppercase mb-4" placeholder="LIMPIAR" autocomplete="off">
                    <div class="flex justify-center gap-3">
                        <button wire:click="$set('isCleanModalOpen', false)" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">Cancelar</button>
                        <button wire:click="cleanBranch" class="px-4 py-2 text-sm font-medium text-white bg-amber-600 rounded-xl hover:bg-amber-700">Limpiar Sucursal</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

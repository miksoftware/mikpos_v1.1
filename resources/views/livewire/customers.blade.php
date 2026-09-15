<div class="space-y-6">
    <!-- Header & Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Clientes</h1>
            <p class="text-slate-500 mt-1">Gestiona los clientes del sistema</p>
        </div>
        @if(auth()->user()->hasPermission('customers.create'))
        <div class="flex items-center gap-2">
            <button wire:click="openImportModal" class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-[1.02] transition-all duration-200">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                Importar Excel/CSV
            </button>
            <button wire:click="create" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-[#ff7261] to-[#a855f7] hover:from-[#e55a4a] hover:to-[#9333ea] text-white text-sm font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-[1.02] transition-all duration-200">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Nuevo Cliente
            </button>
        </div>
        @endif
    </div>

    <!-- Search & Filters -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
        <div class="flex flex-col sm:flex-row gap-4">
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <input wire:model.live.debounce.300ms="search" type="text" class="block w-full pl-10 pr-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 placeholder-slate-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] transition-all sm:text-sm" placeholder="Buscar cliente...">
            </div>
            <select wire:model.live="filterCustomerType" class="px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] sm:text-sm">
                <option value="">Todos los tipos</option>
                <option value="natural">Natural</option>
                <option value="juridico">Jurídico</option>
                <option value="exonerado">Exonerado</option>
            </select>
            <select wire:model.live="filterSeller" class="px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] sm:text-sm">
                <option value="">Todos los vendedores</option>
                @foreach($sellers as $seller)
                <option value="{{ $seller->id }}">{{ $seller->name }}</option>
                @endforeach
            </select>
            @if($needsBranchSelection)
            <select wire:model.live="filterBranch" class="px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] sm:text-sm">
                <option value="">Todas las sucursales</option>
                @foreach($branches as $branch)
                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                @endforeach
            </select>
            @endif
        </div>
    </div>

    <!-- Customers Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Cliente</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Documento</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold text-slate-500 uppercase">Tipo</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Vendedor</th>
                        @if($needsBranchSelection)
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Sucursal</th>
                        @endif
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Contacto</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Ubicación</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold text-slate-500 uppercase">Crédito</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold text-slate-500 uppercase">Estado</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($items as $item)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <div class="h-10 w-10 rounded-full bg-gradient-to-r from-[#ff7261] to-[#a855f7] flex items-center justify-center">
                                        <span class="text-sm font-medium text-white">
                                            {{ strtoupper(substr($item->first_name, 0, 1) . substr($item->last_name, 0, 1)) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-medium text-slate-900">{{ $item->full_name }}</span>
                                        @if($item->is_default)
                                        <span class="inline-flex px-1.5 py-0.5 text-xs font-semibold rounded bg-amber-100 text-amber-800">Por defecto</span>
                                        @endif
                                    </div>
                                    @if($item->business_name && $item->customer_type !== 'juridico')
                                    <div class="text-sm text-slate-500">{{ $item->business_name }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-slate-900">{{ $item->formatted_document }}</div>
                            <div class="text-sm text-slate-500">{{ $item->taxDocument->description }}</div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                @if($item->customer_type === 'natural') bg-blue-100 text-blue-800
                                @elseif($item->customer_type === 'juridico') bg-purple-100 text-purple-800
                                @else bg-green-100 text-green-800 @endif">
                                {{ ucfirst($item->customer_type) }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            @if($item->seller)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-purple-50 text-purple-700 border border-purple-200/60">
                                <svg class="w-3.5 h-3.5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                {{ $item->seller->name }}
                            </span>
                            @else
                            <span class="text-xs text-slate-400 italic">Sin vendedor</span>
                            @endif
                        </td>
                        @if($needsBranchSelection)
                        <td class="px-6 py-4">
                            @if($item->branch)
                            <span class="text-sm text-slate-900">{{ $item->branch->name }}</span>
                            @else
                            <span class="text-sm text-slate-400 italic">Sin sucursal</span>
                            @endif
                        </td>
                        @endif
                        <td class="px-6 py-4">
                            @if($item->phone)
                            <div class="text-sm text-slate-900">{{ $item->phone }}</div>
                            @endif
                            @if($item->email)
                            <div class="text-sm text-slate-500">{{ $item->email }}</div>
                            @endif
                            @if(!$item->phone && !$item->email)
                            <span class="text-sm text-slate-400">Sin contacto</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-slate-900">{{ $item->municipality->name }}</div>
                            <div class="text-sm text-slate-500">{{ $item->department->name }}</div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($item->has_credit)
                            <div class="text-sm font-medium text-green-600">${{ number_format($item->credit_limit, 0) }}</div>
                            <div class="text-xs text-slate-500">Crédito</div>
                            @else
                            <span class="text-sm text-slate-400">Sin crédito</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if(auth()->user()->hasPermission('customers.edit'))
                            <button wire:click="toggleStatus({{ $item->id }})" class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-[#ff7261] focus:ring-offset-2 {{ $item->is_active ? 'bg-[#ff7261]' : 'bg-slate-200' }}">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $item->is_active ? 'translate-x-6' : 'translate-x-1' }}"></span>
                            </button>
                            @else
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $item->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $item->is_active ? 'Activo' : 'Inactivo' }}
                            </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right text-sm font-medium">
                            <div class="flex items-center justify-end gap-1">
                                @if(auth()->user()->hasPermission('customers.edit'))
                                <button wire:click="edit({{ $item->id }})" class="p-2 text-slate-400 hover:text-[#ff7261] hover:bg-orange-50 rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </button>
                                @endif
                                @if(auth()->user()->hasPermission('customers.delete'))
                                <button wire:click="confirmDelete({{ $item->id }})" class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $needsBranchSelection ? 10 : 9 }}" class="px-6 py-12 text-center">
                            <svg class="w-12 h-12 mx-auto text-slate-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                            <p class="text-lg font-medium text-slate-900">No hay clientes</p>
                            <p class="text-slate-500">Comienza creando tu primer cliente</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($items->hasPages())
        <div class="px-6 py-4 border-t border-slate-200">
            {{ $items->links() }}
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
                        <h3 class="text-lg font-bold text-slate-900">{{ $itemId ? 'Editar Cliente' : 'Nuevo Cliente' }}</h3>
                        <button wire:click="$set('isModalOpen', false)" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                    
                    <div class="px-6 py-4 max-h-[70vh] overflow-y-auto space-y-6">
                        <!-- Branch Selection (for super_admin or users without branch) -->
                        @if($needsBranchSelection)
                        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-amber-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                <div class="flex-1">
                                    <h4 class="text-sm font-semibold text-amber-800 mb-1">Selección de Sucursal Requerida</h4>
                                    <p class="text-sm text-amber-700 mb-3">Como administrador general, debes seleccionar la sucursal a la que pertenecerá este cliente.</p>
                                    <select wire:model="branch_id" class="w-full px-3 py-2 border border-amber-300 rounded-xl bg-white focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500">
                                        <option value="">Seleccionar sucursal...</option>
                                        @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('branch_id') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Tipo de Cliente -->
                        <div>
                            <h4 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#ff7261]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                Información del Cliente
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Tipo de Cliente *</label>
                                    <select wire:model.live="customer_type" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                        <option value="natural">Natural</option>
                                        <option value="juridico">Jurídico</option>
                                        <option value="exonerado">Exonerado</option>
                                    </select>
                                    @error('customer_type') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Tipo de Documento *</label>
                                    <select wire:model.live="tax_document_id" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                        <option value="">Seleccionar...</option>
                                        @foreach($taxDocuments as $doc)
                                        <option value="{{ $doc->id }}">{{ $doc->description }}</option>
                                        @endforeach
                                    </select>
                                    @error('tax_document_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                @if($this->isNit)
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Número de NIT y DV *</label>
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1">
                                            <input wire:model.live.debounce.300ms="document_number" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="Ej: 901978671">
                                        </div>
                                        <span class="text-slate-400 font-bold text-lg">-</span>
                                        <div class="w-16">
                                            <input wire:model="dv" type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" class="w-full text-center px-2 py-2 font-mono font-bold text-slate-800 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="0" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 1)" title="Dígito de Verificación (1 solo dígito)">
                                        </div>
                                    </div>
                                    <div class="flex flex-col gap-0.5 mt-1">
                                        @error('document_number') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                        @error('dv') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                                @else
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Número de Documento *</label>
                                    <input wire:model="document_number" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                    @error('document_number') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                @endif
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Nombres *</label>
                                    <input wire:model="first_name" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                    @error('first_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Apellidos *</label>
                                    <input wire:model="last_name" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                    @error('last_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                @if($customer_type === 'juridico')
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Razón Social *</label>
                                    <input wire:model="business_name" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                    @error('business_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                @endif
                            </div>
                        </div>

                        <!-- Contacto -->
                        <div>
                            <h4 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#a855f7]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                Contacto
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Teléfono</label>
                                    <input wire:model="phone" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                    @error('phone') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Correo Electrónico</label>
                                    <input wire:model="email" type="email" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                    @error('email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Ubicación -->
                        <div>
                            <h4 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#ff7261]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
                                Ubicación
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Departamento *</label>
                                    <x-searchable-select
                                        wire:model.live="department_id"
                                        :options="$departments"
                                        placeholder="Seleccionar departamento..."
                                        searchPlaceholder="Buscar departamento..."
                                    />
                                    @error('department_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                <div wire:key="municipality-select-{{ $department_id }}">
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Municipio *</label>
                                    @if($department_id && count($municipalities) > 0)
                                        <x-searchable-select
                                            wire:model="municipality_id"
                                            :options="$municipalities"
                                            placeholder="Seleccionar municipio..."
                                            searchPlaceholder="Buscar municipio..."
                                        />
                                    @else
                                        <div class="relative w-full cursor-not-allowed rounded-xl border border-slate-300 bg-slate-50 py-2.5 pl-3 pr-10 text-left text-sm text-slate-400">
                                            {{ $department_id ? 'Cargando municipios...' : 'Seleccione departamento primero' }}
                                        </div>
                                    @endif
                                    @error('municipality_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Dirección *</label>
                                    <textarea wire:model="address" rows="2" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]"></textarea>
                                    @error('address') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Crédito y Configuración -->
                        <div>
                            <h4 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#a855f7]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Configuración y Vendedor
                            </h4>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Vendedor Asignado (Opcional)</label>
                                    <select wire:model="seller_id" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] text-sm">
                                        <option value="">-- Sin vendedor asignado --</option>
                                        @foreach($sellers as $seller)
                                        <option value="{{ $seller->id }}">{{ $seller->name }} ({{ $seller->email }})</option>
                                        @endforeach
                                    </select>
                                    <p class="text-xs text-slate-500 mt-1">Este vendedor se asignará automáticamente a las ventas y créditos de este cliente.</p>
                                    @error('seller_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <div class="pt-2 border-t border-slate-100">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input wire:model.live="has_credit" type="checkbox" class="w-4 h-4 rounded border-slate-300 text-[#ff7261] focus:ring-[#ff7261]">
                                        <span class="text-sm text-slate-700">El cliente maneja crédito</span>
                                    </label>
                                </div>
                                @if($has_credit)
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1">Límite de Crédito *</label>
                                    <input wire:model="credit_limit" type="number" step="0.01" min="0" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                    @error('credit_limit') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                @endif
                                <div class="pt-2 border-t border-slate-100">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input wire:model="is_default" type="checkbox" class="w-4 h-4 rounded border-slate-300 text-[#ff7261] focus:ring-[#ff7261]">
                                        <span class="text-sm text-slate-700">Marcar como cliente por defecto para esta sucursal (DIAN)</span>
                                    </label>
                                    <p class="text-xs text-slate-500 mt-1 ml-6">Solo puede haber un cliente por defecto por cada sucursal</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
                        <button wire:click="$set('isModalOpen', false)" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">Cancelar</button>
                        <button wire:click="store" class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea]">{{ $itemId ? 'Actualizar' : 'Crear' }}</button>
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
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Eliminar Cliente</h3>
                    <p class="text-slate-500 mb-6">¿Estás seguro de que deseas eliminar este cliente? Esta acción no se puede deshacer.</p>
                    <div class="flex justify-center gap-3">
                        <button wire:click="$set('isDeleteModalOpen', false)" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">Cancelar</button>
                        <button wire:click="delete" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-xl hover:bg-red-700">Eliminar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Import Customers Modal -->
    @if($isImportModalOpen)
    <div class="relative z-[100]" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="closeImportModal"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-xl bg-white rounded-2xl shadow-xl overflow-hidden">
                    <!-- Modal Header -->
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50/50">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center text-emerald-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">Importar Clientes</h3>
                                <p class="text-xs text-slate-500">Carga masiva desde archivo Excel (.xlsx) o CSV</p>
                            </div>
                        </div>
                        <button wire:click="closeImportModal" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-6 space-y-5 max-h-[75vh] overflow-y-auto">
                        <!-- Template Download banner -->
                        <div class="bg-purple-50 border border-purple-200 rounded-xl p-4 flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 text-purple-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                <div>
                                    <h4 class="text-sm font-semibold text-purple-900">Plantilla de Ejemplo</h4>
                                    <p class="text-xs text-purple-700">Descarga la plantilla con el formato exacto y campos de crédito demostrativos.</p>
                                </div>
                            </div>
                            <a href="{{ route('customers.download-template') }}" target="_blank" class="px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white text-xs font-semibold rounded-lg shadow transition-colors flex-shrink-0 inline-flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                Descargar (.xlsx)
                            </a>
                        </div>

                        <!-- Branch Selection if required -->
                        @if($needsBranchSelection)
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Sucursal Objetivo *</label>
                            <select wire:model="importBranchId" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 text-sm">
                                <option value="">Seleccionar sucursal...</option>
                                @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                            @error('importBranchId') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        @endif

                        <!-- File Input -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Archivo Excel o CSV *</label>
                            <div class="relative border-2 border-dashed border-slate-300 hover:border-emerald-500 rounded-2xl p-6 text-center transition-colors bg-slate-50/50">
                                <input type="file" wire:model="importFile" accept=".xlsx,.xls,.csv" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-10 h-10 text-slate-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    @if($importFile)
                                    <p class="text-sm font-semibold text-emerald-600 break-all">{{ $importFile->getClientOriginalName() }}</p>
                                    <p class="text-xs text-slate-400 mt-1">{{ number_format($importFile->getSize() / 1024, 1) }} KB</p>
                                    @else
                                    <p class="text-sm text-slate-600 font-medium">Haz clic o arrastra un archivo aquí</p>
                                    <p class="text-xs text-slate-400 mt-1">Soporta formatos .xlsx, .xls y .csv (Máx. 10MB)</p>
                                    @endif
                                </div>
                            </div>
                            @error('importFile') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <!-- Import info list -->
                        <div class="bg-slate-50 rounded-xl p-4 text-xs text-slate-600 space-y-1.5 border border-slate-200">
                            <span class="font-semibold text-slate-700 block mb-1">Columnas reconocidas en el archivo:</span>
                            <p>• <strong class="text-slate-800">Número Documento</strong> (requerido), <strong class="text-slate-800">Nombres</strong>, <strong class="text-slate-800">Apellidos</strong>, <strong class="text-slate-800">Razón Social</strong>.</p>
                            <p>• <strong class="text-slate-800">Maneja Crédito</strong>: Ingresa <code class="bg-white px-1 py-0.5 rounded border">SI</code> o <code class="bg-white px-1 py-0.5 rounded border">NO</code>.</p>
                            <p>• <strong class="text-slate-800">Monto Límite Crédito</strong>: Límite numérico autorizado (ej. <code class="bg-white px-1 py-0.5 rounded border">5000000</code> o <code class="bg-white px-1 py-0.5 rounded border">$ 5.000.000</code>).</p>
                            <p>• Si el cliente ya existe por número de documento, sus datos serán actualizados.</p>
                        </div>

                        <!-- Processing Results -->
                        @if($importResults)
                        <div class="p-4 rounded-xl space-y-3 {{ !empty($importResults['errors']) ? 'bg-amber-50 border border-amber-200' : 'bg-emerald-50 border border-emerald-200' }}">
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 {{ !empty($importResults['errors']) ? 'text-amber-600' : 'text-emerald-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <span class="font-bold text-sm text-slate-800">Resumen del procesamiento:</span>
                            </div>
                            <div class="flex gap-4 text-xs">
                                <span class="px-2.5 py-1 rounded-lg bg-white border border-slate-200 font-semibold text-emerald-700">Creados: {{ $importResults['created'] }}</span>
                                <span class="px-2.5 py-1 rounded-lg bg-white border border-slate-200 font-semibold text-blue-700">Actualizados: {{ $importResults['updated'] }}</span>
                                <span class="px-2.5 py-1 rounded-lg bg-white border border-slate-200 font-semibold text-red-700">Errores: {{ count($importResults['errors']) }}</span>
                            </div>

                            @if(!empty($importResults['errors']))
                            <div class="mt-2 max-h-36 overflow-y-auto space-y-1 text-xs text-red-600 font-mono bg-white p-2 rounded-lg border border-red-200">
                                @foreach($importResults['errors'] as $err)
                                <div>• {{ $err }}</div>
                                @endforeach
                            </div>
                            @endif
                        </div>
                        @endif
                    </div>

                    <!-- Modal Footer -->
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
                        <button wire:click="closeImportModal" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">Cerrar</button>
                        <button wire:click="importCustomers" wire:loading.attr="disabled" class="inline-flex items-center px-4 py-2 text-sm font-semibold text-white bg-emerald-600 rounded-xl hover:bg-emerald-700 disabled:opacity-50 shadow-md">
                            <span wire:loading wire:target="importCustomers" class="inline-block animate-spin -ml-1 mr-2 h-4 w-4 text-white">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            </span>
                            Procesar Importación
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

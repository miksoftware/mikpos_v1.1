<div class="space-y-6">
    <x-toast />
    
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Medios de Pago</h1>
            <p class="text-slate-500 mt-1">Gestiona los medios de pago del sistema</p>
        </div>
        @if(auth()->user()->hasPermission('payment_methods.create'))
        <button wire:click="create" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-[#ff7261] to-[#a855f7] hover:from-[#e55a4a] hover:to-[#9333ea] text-white text-sm font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-[1.02] transition-all duration-200">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Nuevo Medio de Pago
        </button>
        @endif
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
            <input wire:model.live.debounce.300ms="search" type="text" class="block w-full pl-10 pr-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 placeholder-slate-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] transition-all sm:text-sm" placeholder="Buscar por nombre...">
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Medio de Pago</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Tipo DIAN</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold text-slate-500 uppercase">Estado</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($items as $item)
                    @php
                        $dianType = $dianTypes[$item->dian_code] ?? null;
                    @endphp
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#ff7261]/10 to-[#a855f7]/10 flex items-center justify-center">
                                    @if($item->dian_code == '10')
                                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                    @elseif(in_array($item->dian_code, ['48', '49']))
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                                    @elseif(in_array($item->dian_code, ['47', '42']))
                                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                                    @else
                                    <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    @endif
                                </div>
                                <span class="font-medium text-slate-900">{{ $item->name }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            @if($dianType)
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-100 text-slate-700">
                                    {{ $dianType['name'] }}
                                </span>
                            </div>
                            @else
                            <span class="text-slate-400 text-sm">{{ $item->dian_code }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if(auth()->user()->hasPermission('payment_methods.edit'))
                            <button wire:click="toggleStatus({{ $item->id }})" class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 {{ $item->is_active ? 'bg-[#ff7261]' : 'bg-slate-200' }}">
                                <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200 {{ $item->is_active ? 'translate-x-5' : 'translate-x-0' }}"></span>
                            </button>
                            @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-sm font-medium {{ $item->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">{{ $item->is_active ? 'Activo' : 'Inactivo' }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-1">
                                @if(auth()->user()->hasPermission('payment_methods.edit'))
                                <button wire:click="edit({{ $item->id }})" class="p-2 text-slate-400 hover:text-[#ff7261] hover:bg-orange-50 rounded-lg transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg></button>
                                @endif
                                @if(auth()->user()->hasPermission('payment_methods.delete'))
                                <button wire:click="confirmDelete({{ $item->id }})" class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="px-6 py-12 text-center text-slate-500">No hay medios de pago registrados</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($items->hasPages())<div class="px-6 py-4 border-t border-slate-200">{{ $items->links() }}</div>@endif
    </div>

    <!-- Create/Edit Modal -->
    @if($isModalOpen)
    <div class="relative z-[100]">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="$set('isModalOpen', false)"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-xl">
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-slate-900">{{ $itemId ? 'Editar' : 'Nuevo' }} Medio de Pago</h3>
                        <button wire:click="$set('isModalOpen', false)" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                    <div class="px-6 py-4 space-y-4">
                        <!-- DIAN Type Selector -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Tipo de Pago (DIAN) *</label>
                            <p class="text-xs text-slate-500 mb-3">Selecciona el tipo de pago según la clasificación de la DIAN para facturación electrónica</p>
                            
                            <div class="grid grid-cols-2 gap-2 max-h-64 overflow-y-auto pr-1">
                                @foreach($dianTypes as $code => $type)
                                <label class="cursor-pointer">
                                    <input type="radio" wire:model.live="dian_code" value="{{ $code }}" class="sr-only peer">
                                    <div class="p-3 rounded-xl border-2 transition-all peer-checked:border-[#ff7261] peer-checked:bg-[#ff7261]/5 border-slate-200 hover:border-slate-300 hover:bg-slate-50">
                                        <div class="flex items-start gap-2">
                                            <div class="w-8 h-8 rounded-lg flex-shrink-0 flex items-center justify-center {{ $dian_code == $code ? 'bg-[#ff7261]/20' : 'bg-slate-100' }}">
                                                @if($code == '10')
                                                <svg class="w-4 h-4 {{ $dian_code == $code ? 'text-[#ff7261]' : 'text-emerald-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                                @elseif(in_array($code, ['48', '49']))
                                                <svg class="w-4 h-4 {{ $dian_code == $code ? 'text-[#ff7261]' : 'text-blue-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                                                @elseif(in_array($code, ['47', '42']))
                                                <svg class="w-4 h-4 {{ $dian_code == $code ? 'text-[#ff7261]' : 'text-purple-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                                                @elseif($code == '20')
                                                <svg class="w-4 h-4 {{ $dian_code == $code ? 'text-[#ff7261]' : 'text-slate-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                                @elseif(in_array($code, ['71', '72']))
                                                <svg class="w-4 h-4 {{ $dian_code == $code ? 'text-[#ff7261]' : 'text-amber-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path></svg>
                                                @else
                                                <svg class="w-4 h-4 {{ $dian_code == $code ? 'text-[#ff7261]' : 'text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                @endif
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-slate-800 truncate">{{ $type['name'] }}</p>
                                                <p class="text-xs text-slate-500 line-clamp-2">{{ $type['description'] }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </label>
                                @endforeach
                            </div>
                            @error('dian_code')<span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>@enderror
                        </div>

                        <!-- Name Input -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Nombre del Medio de Pago *</label>
                            <p class="text-xs text-slate-500 mb-2">Nombre personalizado que verán los usuarios (ej: "Nequi", "Efectivo Caja 1")</p>
                            <input wire:model="name" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="Ej: Efectivo, Nequi, Daviplata...">
                            @error('name')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                            
                            @if($dian_code && isset($dianTypes[$dian_code]))
                            <div class="mt-2 p-2 bg-blue-50 rounded-lg">
                                <p class="text-xs text-blue-700">
                                    <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    Sugerencias: {{ implode(', ', $dianTypes[$dian_code]['suggested_names']) }}
                                </p>
                            </div>
                            @endif
                        </div>

                        <!-- Active Toggle -->
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input wire:model="is_active" type="checkbox" class="w-4 h-4 rounded border-slate-300 text-[#ff7261] focus:ring-[#ff7261]">
                            <span class="text-sm text-slate-700">Activo</span>
                        </label>
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
    <div class="relative z-[100]">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="$set('isDeleteModalOpen', false)"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-md bg-white rounded-2xl shadow-xl p-6 text-center">
                    <div class="mx-auto w-12 h-12 rounded-full bg-red-100 flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Eliminar Medio de Pago</h3>
                    <p class="text-slate-500 mb-6">¿Estás seguro? Esta acción no se puede deshacer.</p>
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

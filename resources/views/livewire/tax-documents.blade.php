<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Documentos Tributarios</h1>
            <p class="text-slate-500 mt-1">Tipos de documento de identidad según la DIAN</p>
        </div>
        @if(auth()->user()->hasPermission('tax_documents.create'))
        <button wire:click="create" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-[#ff7261] to-[#a855f7] hover:from-[#e55a4a] hover:to-[#9333ea] text-white text-sm font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-[1.02] transition-all duration-200">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Nuevo Documento
        </button>
        @endif
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
            <input wire:model.live.debounce.300ms="search" type="text" class="block w-full pl-10 pr-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 placeholder-slate-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] transition-all sm:text-sm" placeholder="Buscar por descripción o abreviación...">
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Tipo DIAN</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase">Descripción</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold text-slate-500 uppercase">Abreviación</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold text-slate-500 uppercase">Estado</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($items as $item)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-4">
                            @if(isset($dianTypes[$item->dian_code]))
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
                                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z"></path></svg>
                                    </div>
                                    <span class="text-sm font-medium text-slate-700">{{ $dianTypes[$item->dian_code]['name'] }}</span>
                                </div>
                            @else
                                <span class="font-mono text-sm bg-slate-100 px-2 py-1 rounded">{{ $item->dian_code }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 font-medium text-slate-900">{{ $item->description }}</td>
                        <td class="px-6 py-4 text-center"><span class="inline-flex items-center px-2.5 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-700">{{ $item->abbreviation }}</span></td>
                        <td class="px-6 py-4 text-center">
                            @if(auth()->user()->hasPermission('tax_documents.edit'))
                            <button wire:click="toggleStatus({{ $item->id }})" class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 {{ $item->is_active ? 'bg-[#ff7261]' : 'bg-slate-200' }}">
                                <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200 {{ $item->is_active ? 'translate-x-5' : 'translate-x-0' }}"></span>
                            </button>
                            @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-sm font-medium {{ $item->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">{{ $item->is_active ? 'Activo' : 'Inactivo' }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-1">
                                @if(auth()->user()->hasPermission('tax_documents.edit'))
                                <button wire:click="edit({{ $item->id }})" class="p-2 text-slate-400 hover:text-[#ff7261] hover:bg-orange-50 rounded-lg transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg></button>
                                @endif
                                @if(auth()->user()->hasPermission('tax_documents.delete'))
                                <button wire:click="confirmDelete({{ $item->id }})" class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-6 py-12 text-center text-slate-500">No hay documentos tributarios</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($items->hasPages())<div class="px-6 py-4 border-t border-slate-200">{{ $items->links() }}</div>@endif
    </div>

    {{-- Create/Edit Modal --}}
    @if($isModalOpen)
    <div class="relative z-[100]">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="$set('isModalOpen', false)"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-xl">
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-slate-900">{{ $itemId ? 'Editar' : 'Nuevo' }} Documento Tributario</h3>
                        <button wire:click="$set('isModalOpen', false)" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                    <div class="px-6 py-4 space-y-4">
                        {{-- DIAN Type Selector --}}
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Tipo de Documento DIAN *</label>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 max-h-64 overflow-y-auto p-1">
                                @foreach($dianTypes as $code => $type)
                                <button type="button" wire:click="$set('dian_code', '{{ $code }}')"
                                    class="p-3 rounded-xl border-2 transition-all text-left {{ $dian_code === $code ? 'border-[#ff7261] bg-orange-50' : 'border-slate-200 hover:border-[#ff7261]/50 hover:bg-slate-50' }}">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-bold {{ $dian_code === $code ? 'bg-[#ff7261] text-white' : 'bg-slate-200 text-slate-600' }}">{{ $type['abbreviation'] }}</span>
                                    </div>
                                    <span class="text-sm font-medium text-slate-800 block truncate">{{ $type['name'] }}</span>
                                    <span class="text-xs text-slate-500 line-clamp-2">{{ $type['description'] }}</span>
                                </button>
                                @endforeach
                            </div>
                            @error('dian_code')<span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>@enderror
                        </div>

                        {{-- Selected Type Info --}}
                        @if($dian_code && isset($dianTypes[$dian_code]))
                        <div class="bg-blue-50 border border-blue-200 rounded-xl p-3">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z"></path></svg>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-blue-800">{{ $dianTypes[$dian_code]['name'] }}</p>
                                    <p class="text-xs text-blue-600">{{ $dianTypes[$dian_code]['description'] }}</p>
                                    <p class="text-xs text-blue-500 mt-1">Código DIAN: {{ $dian_code }}</p>
                                </div>
                            </div>
                        </div>
                        @endif

                        {{-- Description --}}
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Descripción Personalizada *</label>
                            <input wire:model="description" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]" placeholder="Ej: Cédula de Ciudadanía">
                            <p class="text-xs text-slate-500 mt-1">Puedes personalizar el nombre que se mostrará en el sistema</p>
                            @error('description')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                        </div>

                        {{-- Abbreviation --}}
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Abreviación *</label>
                            <input wire:model="abbreviation" type="text" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] uppercase" maxlength="20" placeholder="Ej: CC">
                            @error('abbreviation')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                        </div>

                        {{-- Active Status --}}
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

    {{-- Delete Confirmation Modal --}}
    @if($isDeleteModalOpen)
    <div class="relative z-[100]">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="$set('isDeleteModalOpen', false)"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-md bg-white rounded-2xl shadow-xl p-6 text-center">
                    <div class="mx-auto w-12 h-12 rounded-full bg-red-100 flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-2">Eliminar Documento</h3>
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

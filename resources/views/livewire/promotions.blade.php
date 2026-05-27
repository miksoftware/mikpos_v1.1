<div class="space-y-6">
    <x-toast />

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Ofertas y Promociones</h1>
            <p class="text-slate-500 mt-1">Crea y envía campañas de correo a tus clientes</p>
        </div>
        @if(auth()->user()->hasPermission('promotions.create'))
        <button wire:click="create"
            class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-[#ff7261] to-[#a855f7] text-white font-semibold rounded-xl shadow-sm hover:shadow-md hover:opacity-90 transition-all duration-200">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Nueva Campaña
        </button>
        @endif
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-violet-100 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
            </div>
            <div>
                <p class="text-sm text-slate-500">Total campañas</p>
                <p class="text-2xl font-bold text-slate-800">{{ $totalCount }}</p>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-green-100 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div>
                <p class="text-sm text-slate-500">Enviadas</p>
                <p class="text-2xl font-bold text-green-600">{{ $sentCount }}</p>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-100 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
            </div>
            <div>
                <p class="text-sm text-slate-500">Borradores</p>
                <p class="text-2xl font-bold text-amber-600">{{ $draftCount }}</p>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Buscar por asunto..."
                    class="w-full pl-9 pr-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-violet-500/20 focus:border-violet-400 outline-none">
            </div>
            <select wire:model.live="filterStatus"
                class="px-3 py-2.5 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-violet-500/20 focus:border-violet-400 outline-none bg-white">
                <option value="">Todos los estados</option>
                <option value="draft">Borradores</option>
                <option value="sent">Enviadas</option>
            </select>
            @if($search || $filterStatus)
            <button wire:click="$set('search', ''); $set('filterStatus', '')"
                class="px-3 py-2.5 text-sm font-medium text-slate-500 hover:text-slate-700 border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
            @endif
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Campaña</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Estado</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Enviada</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Destinatarios</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Creado por</th>
                        <th class="px-5 py-3.5 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($promotions as $promo)
                    <tr class="hover:bg-slate-50/60 transition-colors">
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                @if($promo->image_path)
                                <img src="{{ Storage::url($promo->image_path) }}" alt="{{ $promo->subject }}"
                                    class="w-10 h-10 rounded-lg object-cover flex-shrink-0 border border-slate-200">
                                @else
                                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-violet-100 to-pink-100 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                @endif
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-slate-900 truncate max-w-xs">{{ $promo->subject }}</p>
                                    <p class="text-xs text-slate-400 mt-0.5">{{ $promo->created_at->format('d/m/Y') }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-4">
                            @if($promo->status === 'sent')
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                Enviada
                            </span>
                            @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                Borrador
                            </span>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-sm text-slate-600">
                            {{ $promo->sent_at ? $promo->sent_at->format('d/m/Y H:i') : '—' }}
                        </td>
                        <td class="px-5 py-4">
                            <span class="text-sm font-semibold text-slate-700">
                                {{ $promo->recipients_count > 0 ? number_format($promo->recipients_count) : '—' }}
                            </span>
                        </td>
                        <td class="px-5 py-4 text-sm text-slate-600">
                            {{ $promo->user->name ?? '—' }}
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex items-center justify-end gap-1">
                                @if(auth()->user()->hasPermission('promotions.send'))
                                <button wire:click="openSendModal({{ $promo->id }})"
                                    class="p-1.5 text-slate-400 hover:text-violet-600 rounded-lg hover:bg-violet-50 transition-colors" title="Enviar campaña">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                    </svg>
                                </button>
                                @endif
                                @if(auth()->user()->hasPermission('promotions.edit') && $promo->status === 'draft')
                                <button wire:click="edit({{ $promo->id }})"
                                    class="p-1.5 text-slate-400 hover:text-blue-600 rounded-lg hover:bg-blue-50 transition-colors" title="Editar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                                @endif
                                @if(auth()->user()->hasPermission('promotions.delete'))
                                <button wire:click="confirmDelete({{ $promo->id }})"
                                    class="p-1.5 text-slate-400 hover:text-red-600 rounded-lg hover:bg-red-50 transition-colors" title="Eliminar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-5 py-14 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <div class="w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center">
                                    <svg class="w-7 h-7 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <p class="text-sm font-medium text-slate-500">No hay campañas creadas</p>
                                <p class="text-xs text-slate-400">Crea tu primera campaña para enviarla a tus clientes</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($promotions->hasPages())
        <div class="px-5 py-4 border-t border-slate-100">
            {{ $promotions->links() }}
        </div>
        @endif
    </div>

    {{-- ═══════════════════════════════════════════════════
         MODAL: Create / Edit
         ═══════════════════════════════════════════════════ --}}
    @if($isModalOpen)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" wire:click="closeModal"></div>
        <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl max-h-[90vh] flex flex-col">

            {{-- Modal header --}}
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between flex-shrink-0">
                <h3 class="text-lg font-bold text-slate-900">
                    {{ $itemId ? 'Editar campaña' : 'Nueva campaña' }}
                </h3>
                <button wire:click="closeModal" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            {{-- Modal body --}}
            <div class="px-6 py-5 overflow-y-auto flex-1 space-y-5">

                {{-- Branch selector (SuperAdmin only) --}}
                @if(auth()->user()->isSuperAdmin())
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                        Sucursal <span class="text-red-500">*</span>
                    </label>
                    <select wire:model="formBranchId"
                        class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-violet-500/20 focus:border-violet-400 outline-none bg-white @error('formBranchId') border-red-400 @enderror">
                        <option value="">Selecciona una sucursal...</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                    @error('formBranchId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                @endif

                {{-- Subject --}}
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                        Asunto del correo <span class="text-red-500">*</span>
                    </label>
                    <input wire:model="subject" type="text" placeholder="Ej: ¡Ofertas especiales de esta semana!"
                        class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-violet-500/20 focus:border-violet-400 outline-none @error('subject') border-red-400 @enderror">
                    @error('subject') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Message --}}
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                        Mensaje <span class="text-red-500">*</span>
                    </label>
                    <textarea wire:model="message" rows="7"
                        placeholder="Escribe el contenido de tu campaña aquí. Puedes usar saltos de línea para organizar el texto."
                        class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-violet-500/20 focus:border-violet-400 outline-none resize-y @error('message') border-red-400 @enderror"></textarea>
                    @error('message') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Image --}}
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Imagen promocional (opcional)</label>
                    @if($existingImagePath && !$image)
                    <div class="mb-3 relative inline-block">
                        <img src="{{ Storage::url($existingImagePath) }}" alt="Imagen actual"
                            class="h-32 rounded-xl object-cover border border-slate-200">
                        <button wire:click="removeExistingImage" type="button"
                            class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center hover:bg-red-600 shadow-sm">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    @endif
                    @if($image)
                    <div class="mb-3">
                        <img src="{{ $image->temporaryUrl() }}" alt="Vista previa"
                            class="h-32 rounded-xl object-cover border border-slate-200">
                    </div>
                    @endif
                    <label class="flex items-center gap-3 px-4 py-3 border-2 border-dashed border-slate-200 rounded-xl cursor-pointer hover:border-violet-400 hover:bg-violet-50/40 transition-all duration-200">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <div>
                            <span class="text-sm font-medium text-slate-600">{{ $image ? 'Cambiar imagen' : 'Subir imagen' }}</span>
                            <p class="text-xs text-slate-400">PNG, JPG, GIF · Máx. 3 MB</p>
                        </div>
                        <input wire:model="image" type="file" accept="image/*" class="sr-only">
                    </label>
                    @error('image') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- CTA Button --}}
                <div class="bg-slate-50 rounded-xl p-4 space-y-3">
                    <p class="text-sm font-semibold text-slate-700">Botón de llamada a acción (opcional)</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">Texto del botón</label>
                            <input wire:model="button_text" type="text" placeholder="Ej: Ver oferta"
                                class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-violet-500/20 focus:border-violet-400 outline-none bg-white">
                            @error('button_text') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">URL del botón</label>
                            <input wire:model="button_url" type="url" placeholder="https://..."
                                class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-violet-500/20 focus:border-violet-400 outline-none bg-white">
                            @error('button_url') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

            </div>

            {{-- Modal footer --}}
            <div class="px-6 py-4 border-t border-slate-200 flex justify-end gap-3 flex-shrink-0">
                <button wire:click="closeModal" type="button"
                    class="px-4 py-2 text-sm font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">
                    Cancelar
                </button>
                <button wire:click="save" type="button" wire:loading.attr="disabled"
                    class="px-5 py-2 text-sm font-semibold text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:opacity-90 transition-opacity disabled:opacity-60 flex items-center gap-2">
                    <svg wire:loading wire:target="save" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ $itemId ? 'Guardar cambios' : 'Crear campaña' }}
                </button>
            </div>

        </div>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════
         MODAL: Send
         ═══════════════════════════════════════════════════ --}}
    @if($isSendModalOpen)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" wire:click="closeSendModal"></div>
        <div class="relative w-full max-w-xl bg-white rounded-2xl shadow-2xl max-h-[90vh] flex flex-col">

            {{-- Modal header --}}
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between flex-shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-violet-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900">Enviar campaña</h3>
                </div>
                <button wire:click="closeSendModal" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            {{-- Modal body --}}
            <div class="px-6 py-5 overflow-y-auto flex-1 space-y-5">

                {{-- Recipient options --}}
                <div>
                    <p class="text-sm font-semibold text-slate-700 mb-3">¿A quién deseas enviar esta campaña?</p>
                    <div class="space-y-2">
                        <label class="flex items-center gap-3 p-3.5 rounded-xl border-2 cursor-pointer transition-all {{ $sendToAll ? 'border-violet-400 bg-violet-50' : 'border-slate-200 hover:border-slate-300' }}">
                            <input wire:model.live="sendToAll" type="radio" value="1" class="text-violet-600 focus:ring-violet-500">
                            <div>
                                <span class="text-sm font-semibold {{ $sendToAll ? 'text-violet-800' : 'text-slate-700' }}">Todos los clientes activos</span>
                                <p class="text-xs {{ $sendToAll ? 'text-violet-500' : 'text-slate-400' }}">Envía a todos los clientes con correo electrónico registrado</p>
                            </div>
                        </label>
                        <label class="flex items-center gap-3 p-3.5 rounded-xl border-2 cursor-pointer transition-all {{ !$sendToAll ? 'border-violet-400 bg-violet-50' : 'border-slate-200 hover:border-slate-300' }}">
                            <input wire:model.live="sendToAll" type="radio" value="0" class="text-violet-600 focus:ring-violet-500">
                            <div>
                                <span class="text-sm font-semibold {{ !$sendToAll ? 'text-violet-800' : 'text-slate-700' }}">Seleccionar clientes</span>
                                <p class="text-xs {{ !$sendToAll ? 'text-violet-500' : 'text-slate-400' }}">Elige manualmente a quiénes enviar la campaña</p>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Customer selector --}}
                @if(!$sendToAll)
                <div class="space-y-3">
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <input wire:model.live.debounce.300ms="customerSearch" type="text"
                            placeholder="Buscar cliente por nombre, documento o correo..."
                            class="w-full pl-9 pr-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-violet-500/20 focus:border-violet-400 outline-none">
                    </div>

                    @if(count($selectedCustomerIds) > 0)
                    <p class="text-xs font-semibold text-violet-700 bg-violet-50 px-3 py-1.5 rounded-lg">
                        {{ count($selectedCustomerIds) }} cliente(s) seleccionado(s)
                    </p>
                    @endif

                    <div class="border border-slate-200 rounded-xl overflow-hidden max-h-64 overflow-y-auto divide-y divide-slate-100">
                        @forelse($sendCustomers as $customer)
                        <label class="flex items-center gap-3 px-4 py-2.5 cursor-pointer hover:bg-slate-50 transition-colors {{ in_array($customer->id, $selectedCustomerIds) ? 'bg-violet-50' : '' }}">
                            <input type="checkbox" wire:model.live="selectedCustomerIds" value="{{ $customer->id }}"
                                class="rounded text-violet-600 focus:ring-violet-500 border-slate-300">
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-slate-800 truncate">{{ $customer->full_name }}</p>
                                <p class="text-xs text-slate-400 truncate">{{ $customer->email }}</p>
                            </div>
                        </label>
                        @empty
                        <div class="px-4 py-6 text-center text-sm text-slate-400">
                            {{ $customerSearch ? 'No se encontraron clientes con ese criterio' : 'Escribe para buscar clientes' }}
                        </div>
                        @endforelse
                    </div>
                </div>
                @endif

                {{-- Warning --}}
                <div class="flex items-start gap-3 p-3.5 bg-amber-50 border border-amber-200 rounded-xl">
                    <svg class="w-4 h-4 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <p class="text-xs text-amber-700">
                        Los correos serán enviados en cola. Solo se envía a clientes activos con correo electrónico registrado.
                    </p>
                </div>

            </div>

            {{-- Modal footer --}}
            <div class="px-6 py-4 border-t border-slate-200 flex justify-end gap-3 flex-shrink-0">
                <button wire:click="closeSendModal" type="button"
                    class="px-4 py-2 text-sm font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">
                    Cancelar
                </button>
                <button wire:click="sendPromotion" type="button" wire:loading.attr="disabled" wire:target="sendPromotion"
                    class="px-5 py-2 text-sm font-semibold text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:opacity-90 transition-opacity disabled:opacity-60 flex items-center gap-2">
                    <svg wire:loading wire:target="sendPromotion" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <svg wire:loading.remove wire:target="sendPromotion" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                    </svg>
                    Confirmar envío
                </button>
            </div>

        </div>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════
         MODAL: Delete confirmation
         ═══════════════════════════════════════════════════ --}}
    @if($isDeleteModalOpen)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
        <div class="relative w-full max-w-sm bg-white rounded-2xl shadow-2xl p-6">
            <div class="flex flex-col items-center text-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-red-100 flex items-center justify-center">
                    <svg class="w-7 h-7 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900">Eliminar campaña</h3>
                    <p class="text-sm text-slate-500 mt-1">Esta acción no se puede deshacer. ¿Confirmas la eliminación?</p>
                </div>
                <div class="flex gap-3 w-full">
                    <button wire:click="$set('isDeleteModalOpen', false)" type="button"
                        class="flex-1 px-4 py-2.5 text-sm font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">
                        Cancelar
                    </button>
                    <button wire:click="delete" type="button"
                        class="flex-1 px-4 py-2.5 text-sm font-semibold text-white bg-red-500 hover:bg-red-600 rounded-xl transition-colors">
                        Eliminar
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>

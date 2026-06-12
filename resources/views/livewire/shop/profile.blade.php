<div class="max-w-3xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900">Perfil</h1>
        <p class="text-slate-500 mt-1">Actualiza tu información para recibir notificaciones y completar compras más rápido.</p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-6">
            <form wire:submit="save" class="space-y-4">
            <div x-data="{ type: @entangle('customer_type') }">
                <label class="block text-sm font-medium text-slate-700 mb-2">Tipo de persona</label>
                <div class="grid grid-cols-2 gap-3">
                    <button type="button" @click="type = 'natural'" wire:click="$set('customer_type', 'natural')"
                        :class="type === 'natural' ? 'border-[#ff7261] bg-orange-50 text-[#ff7261]' : 'border-slate-300 text-slate-600 hover:border-slate-400'"
                        class="px-4 py-2.5 text-sm font-medium border-2 rounded-xl transition-all text-center">
                        Persona Natural
                    </button>
                    <button type="button" @click="type = 'juridico'" wire:click="$set('customer_type', 'juridico')"
                        :class="type === 'juridico' ? 'border-[#ff7261] bg-orange-50 text-[#ff7261]' : 'border-slate-300 text-slate-600 hover:border-slate-400'"
                        class="px-4 py-2.5 text-sm font-medium border-2 rounded-xl transition-all text-center">
                        Persona Jurídica
                    </button>
                </div>
                @error('customer_type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="tax_document_id" class="block text-sm font-medium text-slate-700 mb-1">Tipo de documento</label>
                    <select wire:model="tax_document_id" id="tax_document_id"
                        class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                        <option value="">Seleccionar...</option>
                        @foreach($taxDocuments as $doc)
                            <option value="{{ $doc->id }}">{{ $doc->abbreviation }} - {{ $doc->description }}</option>
                        @endforeach
                    </select>
                    @error('tax_document_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="document_number" class="block text-sm font-medium text-slate-700 mb-1">Número de documento</label>
                    <input type="text" wire:model="document_number" id="document_number" placeholder="Ingrese su número de documento"
                        class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                    @error('document_number') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="first_name" class="block text-sm font-medium text-slate-700 mb-1">Nombre</label>
                    <input type="text" wire:model="first_name" id="first_name" placeholder="Nombre"
                        class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                    @error('first_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="last_name" class="block text-sm font-medium text-slate-700 mb-1">Apellido</label>
                    <input type="text" wire:model="last_name" id="last_name" placeholder="Apellido"
                        class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                    @error('last_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            @if($customer_type === 'juridico')
            <div>
                <label for="business_name" class="block text-sm font-medium text-slate-700 mb-1">Razón social</label>
                <input type="text" wire:model="business_name" id="business_name" placeholder="Razón social de la empresa"
                    class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                @error('business_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            @endif

            <div>
                <label for="phone" class="block text-sm font-medium text-slate-700 mb-1">Teléfono</label>
                <input type="tel" wire:model="phone" id="phone" placeholder="Ej: 573153920724"
                    class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                <p class="text-xs text-slate-500 mt-1">Formato recomendado: indicativo del país + número (solo dígitos). Ej: 573153920724</p>
                @error('phone') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Correo electrónico</label>
                <input type="email" wire:model="email" id="email" placeholder="correo@ejemplo.com"
                    class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="department_id" class="block text-sm font-medium text-slate-700 mb-1">Departamento</label>
                    <select wire:model.live="department_id" id="department_id"
                        class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                        <option value="">Seleccionar...</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                    @error('department_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="municipality_id" class="block text-sm font-medium text-slate-700 mb-1">Municipio</label>
                    <select wire:model="municipality_id" id="municipality_id"
                        class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]"
                        {{ empty($municipalities) ? 'disabled' : '' }}>
                        <option value="">{{ empty($municipalities) ? 'Seleccione departamento' : 'Seleccionar...' }}</option>
                        @foreach($municipalities as $mun)
                            <option value="{{ $mun['id'] }}">{{ $mun['name'] }}</option>
                        @endforeach
                    </select>
                    @error('municipality_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="address" class="block text-sm font-medium text-slate-700 mb-1">Dirección</label>
                <input type="text" wire:model="address" id="address" placeholder="Dirección"
                    class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                @error('address') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="pt-2">
                <button type="submit" wire:loading.attr="disabled" wire:target="save"
                    class="w-full px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea] transition-all disabled:opacity-50">
                    <span wire:loading.remove wire:target="save">Guardar cambios</span>
                    <span wire:loading wire:target="save">Guardando...</span>
                </button>
            </div>
            </form>
        </div>

        <div class="border-t border-slate-200 bg-slate-50 p-6">
            <h2 class="text-lg font-bold text-slate-900">Cambiar contraseña</h2>
            <p class="text-sm text-slate-500 mt-1">Por seguridad, confirma tu contraseña actual.</p>

            <form wire:submit="changePassword" class="mt-4 space-y-4">
                <div>
                    <label for="current_password" class="block text-sm font-medium text-slate-700 mb-1">Contraseña actual</label>
                    <input type="password" wire:model="current_password" id="current_password" placeholder="Tu contraseña actual"
                        class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                    @error('current_password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="new_password" class="block text-sm font-medium text-slate-700 mb-1">Nueva contraseña</label>
                        <input type="password" wire:model="new_password" id="new_password" placeholder="Mínimo 8 caracteres"
                            class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                        @error('new_password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="new_password_confirmation" class="block text-sm font-medium text-slate-700 mb-1">Confirmar nueva contraseña</label>
                        <input type="password" wire:model="new_password_confirmation" id="new_password_confirmation" placeholder="Repita la nueva contraseña"
                            class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                        @error('new_password_confirmation') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" wire:loading.attr="disabled" wire:target="changePassword"
                        class="w-full px-4 py-2.5 text-sm font-medium text-white bg-slate-900 rounded-xl hover:bg-slate-800 transition-all disabled:opacity-50">
                        <span wire:loading.remove wire:target="changePassword">Actualizar contraseña</span>
                        <span wire:loading wire:target="changePassword">Actualizando...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

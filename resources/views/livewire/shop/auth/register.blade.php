<div class="flex items-center justify-center min-h-[calc(100vh-12rem)]">
    <div class="w-full max-w-lg">
        {{-- Header --}}
        <div class="text-center mb-8">
            <div class="w-14 h-14 bg-gradient-to-br from-[#ff7261] to-[#a855f7] rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg">
                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-slate-900">Crear cuenta</h1>
            <p class="text-slate-500 mt-1">Regístrate para comenzar a comprar</p>
        </div>

        {{-- Form Card --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <form wire:submit="register" class="space-y-4">
                {{-- Customer Type --}}
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

                {{-- Tax Document Type --}}
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

                {{-- Document Number --}}
                <div>
                    <label for="document_number" class="block text-sm font-medium text-slate-700 mb-1">Número de documento</label>
                    <input type="text" wire:model="document_number" id="document_number" placeholder="Ingrese su número de documento"
                        class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                    @error('document_number') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Name Fields --}}
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

                {{-- Business Name (only for juridico) --}}
                @if($customer_type === 'juridico')
                <div>
                    <label for="business_name" class="block text-sm font-medium text-slate-700 mb-1">Razón social</label>
                    <input type="text" wire:model="business_name" id="business_name" placeholder="Razón social de la empresa"
                        class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                    @error('business_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                @endif

                {{-- Phone --}}
                <div>
                    <label for="phone" class="block text-sm font-medium text-slate-700 mb-1">Teléfono <span class="text-slate-400">(opcional)</span></label>
                    <input type="tel" wire:model="phone" id="phone" placeholder="Número de teléfono"
                        class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                    @error('phone') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Department & Municipality --}}
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

                {{-- Address --}}
                <div>
                    <label for="address" class="block text-sm font-medium text-slate-700 mb-1">Dirección</label>
                    <input type="text" wire:model="address" id="address" placeholder="Dirección de residencia"
                        class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                    @error('address') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Email --}}
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Correo electrónico</label>
                    <input type="email" wire:model="email" id="email" placeholder="correo@ejemplo.com"
                        class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                    @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Password --}}
                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Contraseña</label>
                    <input type="password" wire:model="password" id="password" placeholder="Mínimo 8 caracteres"
                        class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                    @error('password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Password Confirmation --}}
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-1">Confirmar contraseña</label>
                    <input type="password" wire:model="password_confirmation" id="password_confirmation" placeholder="Repita la contraseña"
                        class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                </div>

                {{-- Submit --}}
                <button type="submit" wire:loading.attr="disabled"
                    class="w-full px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea] transition-all disabled:opacity-50">
                    <span wire:loading.remove wire:target="register">Crear cuenta</span>
                    <span wire:loading wire:target="register">Registrando...</span>
                </button>
            </form>
        </div>

        {{-- Login Link --}}
        <p class="text-center text-sm text-slate-500 mt-6">
            ¿Ya tienes cuenta?
            <a href="{{ route('shop.login') }}" class="font-medium text-[#ff7261] hover:text-[#e55a4a]" wire:navigate>Iniciar sesión</a>
        </p>
    </div>
</div>

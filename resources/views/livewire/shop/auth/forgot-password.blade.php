<div class="flex items-center justify-center min-h-[calc(100vh-12rem)]">
    <div class="w-full max-w-md">
        {{-- Header --}}
        <div class="text-center mb-8">
            <div class="w-14 h-14 bg-gradient-to-br from-[#ff7261] to-[#a855f7] rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg">
                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-slate-900">Recuperar contraseña</h1>
            <p class="text-slate-500 mt-1">
                @if($success)
                    Tu contraseña ha sido actualizada
                @elseif($step === 1)
                    Verifica tu identidad para continuar
                @else
                    Establece tu nueva contraseña
                @endif
            </p>
        </div>

        {{-- Success --}}
        @if($success)
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 text-center">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h2 class="text-lg font-bold text-slate-900 mb-2">Contraseña actualizada</h2>
            <p class="text-sm text-slate-500 mb-6">Tu contraseña ha sido cambiada correctamente. Ya puedes iniciar sesión con tu nueva contraseña.</p>
            <a href="{{ route('shop.login') }}" wire:navigate
                class="inline-flex items-center px-6 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea] transition-all">
                Iniciar sesión
            </a>
        </div>

        {{-- Step 1: Verify Identity --}}
        @elseif($step === 1)
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <form wire:submit="verifyIdentity" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Número de documento</label>
                    <input type="text" wire:model="document_number" placeholder="Ingrese su número de documento" autofocus
                        class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                    @error('document_number') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Correo electrónico</label>
                    <input type="email" wire:model="email" placeholder="correo@ejemplo.com"
                        class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                    @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <button type="submit" wire:loading.attr="disabled"
                    class="w-full px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea] transition-all disabled:opacity-50">
                    <span wire:loading.remove wire:target="verifyIdentity">Verificar identidad</span>
                    <span wire:loading wire:target="verifyIdentity">Verificando...</span>
                </button>
            </form>
        </div>

        {{-- Step 2: New Password --}}
        @else
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <div class="bg-green-50 border border-green-200 rounded-xl p-3 mb-4">
                <p class="text-sm text-green-700 flex items-center gap-2">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    Identidad verificada. Establece tu nueva contraseña.
                </p>
            </div>
            <form wire:submit="resetPassword" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Nueva contraseña</label>
                    <input type="password" wire:model="new_password" placeholder="Mínimo 8 caracteres"
                        class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                    @error('new_password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Confirmar contraseña</label>
                    <input type="password" wire:model="new_password_confirmation" placeholder="Repita la contraseña"
                        class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                </div>

                <button type="submit" wire:loading.attr="disabled"
                    class="w-full px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea] transition-all disabled:opacity-50">
                    <span wire:loading.remove wire:target="resetPassword">Cambiar contraseña</span>
                    <span wire:loading wire:target="resetPassword">Guardando...</span>
                </button>
            </form>
        </div>
        @endif

        {{-- Back to login --}}
        @if(!$success)
        <p class="text-center text-sm text-slate-500 mt-6">
            <a href="{{ route('shop.login') }}" class="font-medium text-[#ff7261] hover:text-[#e55a4a]" wire:navigate>← Volver al inicio de sesión</a>
        </p>
        @endif
    </div>
</div>

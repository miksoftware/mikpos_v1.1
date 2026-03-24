<div class="flex items-center justify-center min-h-[calc(100vh-12rem)]">
    <div class="w-full max-w-md">
        {{-- Header --}}
        <div class="text-center mb-8">
            <div class="w-14 h-14 bg-gradient-to-br from-[#ff7261] to-[#a855f7] rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg">
                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-slate-900">Iniciar sesión</h1>
            <p class="text-slate-500 mt-1">Ingresa a tu cuenta para continuar</p>
        </div>

        {{-- Form Card --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <form wire:submit="login" class="space-y-4">
                {{-- Email --}}
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Correo electrónico</label>
                    <input type="email" wire:model="email" id="email" placeholder="correo@ejemplo.com" autofocus
                        class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                    @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Password --}}
                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Contraseña</label>
                    <input type="password" wire:model="password" id="password" placeholder="Ingrese su contraseña"
                        class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                    @error('password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Submit --}}
                <button type="submit" wire:loading.attr="disabled"
                    class="w-full px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea] transition-all disabled:opacity-50">
                    <span wire:loading.remove wire:target="login">Iniciar sesión</span>
                    <span wire:loading wire:target="login">Ingresando...</span>
                </button>

                {{-- Forgot Password --}}
                <div class="text-center mt-3">
                    <a href="{{ route('shop.forgot-password') }}" class="text-xs text-[#ff7261] hover:text-[#e55a4a]" wire:navigate>¿Olvidaste tu contraseña?</a>
                </div>
            </form>
        </div>

        {{-- Register Link --}}
        <p class="text-center text-sm text-slate-500 mt-6">
            ¿No tienes cuenta?
            <a href="{{ route('shop.register') }}" class="font-medium text-[#ff7261] hover:text-[#e55a4a]" wire:navigate>Crear cuenta</a>
        </p>
    </div>
</div>

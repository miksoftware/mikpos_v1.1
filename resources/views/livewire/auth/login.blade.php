<div x-data="{ loading: @entangle('loading') }">
    <div class="min-h-screen flex items-center justify-center p-6 relative overflow-hidden">
        <!-- Animated background elements -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute -top-40 -right-40 w-80 h-80 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-pulse-slow" style="background-color: #ff7261;"></div>
            <div class="absolute -bottom-40 -left-40 w-80 h-80 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-pulse-slow" style="background-color: #a855f7; animation-delay: 1s;"></div>
            <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-80 h-80 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-pulse-slow" style="background-color: #ff8a7a; animation-delay: 2s;"></div>
        </div>

        <!-- Login Card -->
        <div class="w-full max-w-md relative z-10 animate-slide-up">
            <!-- Glass Card -->
            <div class="backdrop-blur-xl bg-white/10 rounded-3xl shadow-2xl border border-white/20 p-8 md:p-10">
                <!-- Logo & Title -->
                <div class="text-center mb-8">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-[#ff7261] to-[#a855f7] rounded-2xl shadow-lg mb-4">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                        </svg>
                    </div>
                    <h1 class="text-3xl font-bold text-white mb-2">MikPOS</h1>
                    <p class="text-orange-200">Sistema POS Multisucursal</p>
                </div>

                <!-- Login Form -->
                <form wire:submit="login" class="space-y-5">
                    <!-- Email Input -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-orange-100 mb-2">Correo Electrónico</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-orange-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                                </svg>
                            </div>
                            <input wire:model.live="email" type="email" id="email" class="w-full pl-12 pr-4 py-3.5 bg-white/10 border border-white/20 rounded-xl text-white placeholder-orange-300/50 focus:outline-none focus:ring-2 focus:ring-[#ff7261] focus:border-transparent transition-all duration-200 backdrop-blur-sm" placeholder="tu@email.com" autocomplete="email">
                        </div>
                        @error('email') <p class="mt-2 text-sm text-red-300 animate-fade-in">{{ $message }}</p> @enderror
                    </div>

                    <!-- Password Input -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-orange-100 mb-2">Contraseña</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                            </div>
                            <input wire:model.live="password" type="password" id="password" class="w-full pl-12 pr-4 py-3.5 bg-white/10 border border-white/20 rounded-xl text-white placeholder-purple-300/50 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 backdrop-blur-sm" placeholder="••••••••" autocomplete="current-password">
                        </div>
                        @error('password') <p class="mt-2 text-sm text-red-300 animate-fade-in">{{ $message }}</p> @enderror
                    </div>

                    <!-- Remember Me & Forgot Password -->
                    <div class="flex items-center justify-between">
                        <label class="flex items-center cursor-pointer group">
                            <input wire:model="remember" type="checkbox" class="w-4 h-4 rounded border-white/20 bg-white/10 text-purple-600 focus:ring-purple-500 focus:ring-offset-0 transition-colors">
                            <span class="ml-2 text-sm text-orange-100 group-hover:text-white transition-colors">Recordarme</span>
                        </label>
                        <a href="#" class="text-sm text-orange-200 hover:text-white transition-colors">¿Olvidaste tu contraseña?</a>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="w-full py-3.5 px-4 bg-gradient-to-r from-[#ff7261] to-[#a855f7] hover:from-[#e55a4a] hover:to-[#9333ea] text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-[1.02] active:scale-[0.98] transition-all duration-200 flex items-center justify-center">
                        Iniciar Sesión
                    </button>
                </form>

                <!-- Footer -->
                <div class="mt-8 text-center text-sm text-orange-200">
                    <p>© {{ date('Y') }} MikPOS. Sistema POS Multisucursal</p>
                </div>
            </div>

            <!-- Additional Info -->
            <div class="mt-6 text-center text-orange-200 text-sm">
                <p>¿Necesitas ayuda? <a href="#" class="text-white hover:underline">Contacta soporte</a></p>
            </div>
        </div>
    </div>

    <!-- Full Screen Loading Overlay controlled by Alpine -->
    <template x-teleport="body">
        <div 
            x-show="loading" 
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-[9999] flex items-center justify-center"
            style="background: linear-gradient(to bottom right, rgba(26, 18, 37, 0.95), rgba(45, 31, 61, 0.95), rgba(26, 18, 37, 0.95)); backdrop-filter: blur(12px);"
        >
            <div class="flex flex-col items-center justify-center">
                <!-- Animated Logo -->
                <div class="relative mb-8">
                    <div class="w-24 h-24 bg-gradient-to-br from-[#ff7261] to-[#a855f7] rounded-2xl flex items-center justify-center shadow-2xl animate-pulse">
                        <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                        </svg>
                    </div>
                    <!-- Spinning ring -->
                    <div class="absolute -inset-2">
                        <div class="w-full h-full border-4 border-transparent border-t-[#ff7261] border-r-[#a855f7] rounded-2xl animate-spin" style="animation-duration: 1.5s;"></div>
                    </div>
                </div>
                <!-- Text -->
                <h2 class="text-2xl font-bold text-white mb-2">Iniciando sesión</h2>
                <p class="text-orange-200/80">Verificando credenciales...</p>
                <!-- Loading dots -->
                <div class="flex gap-1.5 mt-4">
                    <div class="w-2 h-2 bg-[#ff7261] rounded-full animate-bounce" style="animation-delay: 0ms;"></div>
                    <div class="w-2 h-2 bg-[#ff7261]/80 rounded-full animate-bounce" style="animation-delay: 150ms;"></div>
                    <div class="w-2 h-2 bg-[#a855f7] rounded-full animate-bounce" style="animation-delay: 300ms;"></div>
                </div>
            </div>
        </div>
    </template>
</div>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Facturación Electrónica</h1>
            <p class="text-slate-500 mt-1">Configuración de integración con Factus API</p>
        </div>
        <div class="flex items-center gap-3">
            <!-- Status Badge -->
            @if($is_enabled && $isConfigured)
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-700">
                    <span class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></span>
                    Activo
                </span>
            @else
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-slate-100 text-slate-600">
                    <span class="w-2 h-2 bg-slate-400 rounded-full mr-2"></span>
                    Inactivo
                </span>
            @endif
        </div>
    </div>

    <!-- Main Settings Card -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <!-- Enable/Disable Toggle -->
        <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-slate-800">Habilitar Facturación Electrónica</h3>
                    <p class="text-sm text-slate-500 mt-1">Activa la integración con Factus para emitir facturas electrónicas</p>
                </div>
                <button wire:click="toggleEnabled" 
                    class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 {{ $is_enabled ? 'bg-[#ff7261]' : 'bg-slate-200' }}">
                    <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200 {{ $is_enabled ? 'translate-x-5' : 'translate-x-0' }}"></span>
                </button>
            </div>
        </div>

        <div class="p-6 space-y-6">
            <!-- Environment Selection -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Ambiente</label>
                <div class="grid grid-cols-2 gap-4">
                    <button wire:click="$set('environment', 'sandbox')" type="button"
                        class="p-4 rounded-xl border-2 transition-all {{ $environment === 'sandbox' ? 'border-[#ff7261] bg-orange-50' : 'border-slate-200 hover:border-slate-300' }}">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full {{ $environment === 'sandbox' ? 'bg-orange-100' : 'bg-slate-100' }} flex items-center justify-center">
                                <svg class="w-5 h-5 {{ $environment === 'sandbox' ? 'text-[#ff7261]' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                                </svg>
                            </div>
                            <div class="text-left">
                                <p class="font-medium {{ $environment === 'sandbox' ? 'text-[#ff7261]' : 'text-slate-700' }}">Sandbox</p>
                                <p class="text-xs text-slate-500">Ambiente de pruebas</p>
                            </div>
                        </div>
                    </button>
                    <button wire:click="$set('environment', 'production')" type="button"
                        class="p-4 rounded-xl border-2 transition-all {{ $environment === 'production' ? 'border-green-500 bg-green-50' : 'border-slate-200 hover:border-slate-300' }}">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full {{ $environment === 'production' ? 'bg-green-100' : 'bg-slate-100' }} flex items-center justify-center">
                                <svg class="w-5 h-5 {{ $environment === 'production' ? 'text-green-600' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                </svg>
                            </div>
                            <div class="text-left">
                                <p class="font-medium {{ $environment === 'production' ? 'text-green-600' : 'text-slate-700' }}">Producción</p>
                                <p class="text-xs text-slate-500">Ambiente real DIAN</p>
                            </div>
                        </div>
                    </button>
                </div>
            </div>

            <!-- API URL -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">URL del API</label>
                <input wire:model="api_url" type="url" 
                    class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] bg-slate-50"
                    placeholder="https://api.factus.com.co">
                <p class="text-xs text-slate-500 mt-1">Se configura automáticamente según el ambiente seleccionado</p>
            </div>

            <!-- Credentials Section -->
            <div class="pt-4 border-t border-slate-200">
                <h4 class="text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#a855f7]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                    </svg>
                    Credenciales de API
                </h4>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Client ID -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Client ID</label>
                        <input wire:model="client_id" type="text" 
                            class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]"
                            placeholder="Tu Client ID de Factus">
                    </div>

                    <!-- Client Secret -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Client Secret</label>
                        <div class="relative">
                            <input wire:model="client_secret" 
                                type="{{ $showClientSecret ? 'text' : 'password' }}" 
                                class="w-full px-3 py-2 pr-10 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]"
                                placeholder="Tu Client Secret">
                            <button type="button" wire:click="$toggle('showClientSecret')" 
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                                @if($showClientSecret)
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                                    </svg>
                                @else
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                @endif
                            </button>
                        </div>
                    </div>

                    <!-- Username -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Usuario (Email)</label>
                        <input wire:model="username" type="email" 
                            class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]"
                            placeholder="usuario@empresa.com">
                    </div>

                    <!-- Password -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Contraseña</label>
                        <div class="relative">
                            <input wire:model="password" 
                                type="{{ $showPassword ? 'text' : 'password' }}" 
                                class="w-full px-3 py-2 pr-10 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]"
                                placeholder="Tu contraseña de Factus">
                            <button type="button" wire:click="$toggle('showPassword')" 
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                                @if($showPassword)
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                                    </svg>
                                @else
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                @endif
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Token Status -->
            @if($hasValidToken)
            <div class="p-4 bg-green-50 border border-green-200 rounded-xl">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="font-medium text-green-700">Token Activo</p>
                        <p class="text-sm text-green-600">Expira: {{ $tokenExpiresAt?->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
            </div>
            @endif

            <!-- Test Result -->
            @if($testResult)
            <div class="p-4 rounded-xl {{ $testResult['success'] ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200' }}">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full {{ $testResult['success'] ? 'bg-green-100' : 'bg-red-100' }} flex items-center justify-center">
                        @if($testResult['success'])
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        @else
                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        @endif
                    </div>
                    <div>
                        <p class="font-medium {{ $testResult['success'] ? 'text-green-700' : 'text-red-700' }}">
                            {{ $testResult['success'] ? 'Prueba Exitosa' : 'Error en la Prueba' }}
                        </p>
                        <p class="text-sm {{ $testResult['success'] ? 'text-green-600' : 'text-red-600' }}">{{ $testResult['message'] }}</p>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Actions Footer -->
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row justify-between gap-3">
            <button wire:click="testConnection" wire:loading.attr="disabled"
                class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 disabled:opacity-50">
                <svg wire:loading.remove wire:target="testConnection" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
                <svg wire:loading wire:target="testConnection" class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Probar Conexión
            </button>
            <button wire:click="save" 
                class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea]">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                Guardar Configuración
            </button>
        </div>
    </div>

    <!-- Info Card -->
    <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-2xl border border-blue-200 p-6">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-100 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div>
                <h4 class="font-semibold text-blue-900">¿Cómo obtener las credenciales?</h4>
                <p class="text-sm text-blue-700 mt-1">
                    Para obtener tus credenciales de API, debes registrarte en 
                    <a href="https://factus.com.co" target="_blank" class="underline hover:text-blue-900">Factus</a> 
                    y acceder al panel de desarrolladores. Allí encontrarás tu Client ID, Client Secret y podrás crear tus credenciales de usuario.
                </p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <a href="https://developers.factus.com.co" target="_blank" 
                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-blue-700 bg-blue-100 rounded-lg hover:bg-blue-200">
                        <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                        </svg>
                        Documentación API
                    </a>
                    <a href="https://factus.com.co" target="_blank" 
                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-blue-700 bg-blue-100 rounded-lg hover:bg-blue-200">
                        <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                        </svg>
                        Portal Factus
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

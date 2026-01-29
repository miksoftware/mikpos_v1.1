<div class="w-full max-w-2xl">
    <!-- Logo & Title -->
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-r from-[#ff7261] to-[#a855f7] mb-4">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
            </svg>
        </div>
        <h1 class="text-3xl font-bold text-white">MikPOS</h1>
        <p class="text-slate-400 mt-2">Asistente de Instalación</p>
    </div>

    <!-- Progress Steps -->
    <div class="flex items-center justify-center mb-8">
        @for($i = 1; $i <= $totalSteps; $i++)
        <div class="flex items-center">
            <div class="flex items-center justify-center w-10 h-10 rounded-full {{ $currentStep >= $i ? 'bg-gradient-to-r from-[#ff7261] to-[#a855f7]' : 'bg-slate-700' }} {{ $currentStep === $i ? 'ring-4 ring-purple-500/30' : '' }}">
                @if($currentStep > $i)
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                @else
                <span class="text-white font-semibold">{{ $i }}</span>
                @endif
            </div>
            @if($i < $totalSteps)
            <div class="w-12 h-1 {{ $currentStep > $i ? 'bg-gradient-to-r from-[#ff7261] to-[#a855f7]' : 'bg-slate-700' }}"></div>
            @endif
        </div>
        @endfor
    </div>

    <!-- Main Card -->
    <div class="bg-white rounded-3xl shadow-2xl overflow-hidden">
        <!-- Step 1: Requirements -->
        @if($currentStep === 1)
        <div class="p-8">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-slate-900">Requisitos del Sistema</h2>
                    <p class="text-sm text-slate-500">Verificando requisitos necesarios</p>
                </div>
            </div>

            <div class="space-y-3 max-h-[400px] overflow-y-auto">
                @foreach($requirements as $key => $req)
                <div class="flex items-center justify-between p-3 rounded-xl {{ $req['passed'] ? 'bg-green-50' : 'bg-red-50' }}">
                    <div class="flex items-center gap-3">
                        @if($req['passed'])
                        <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                        @else
                        <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </div>
                        @endif
                        <span class="font-medium {{ $req['passed'] ? 'text-green-700' : 'text-red-700' }}">{{ $req['name'] }}</span>
                    </div>
                    <span class="text-sm {{ $req['passed'] ? 'text-green-600' : 'text-red-600' }}">{{ $req['current'] }}</span>
                </div>
                @endforeach
            </div>

            @if(!$requirementsPassed)
            <div class="mt-4 p-4 bg-amber-50 border border-amber-200 rounded-xl">
                <p class="text-sm text-amber-700">Por favor, corrija los requisitos faltantes antes de continuar.</p>
            </div>
            @endif
        </div>
        @endif

        <!-- Step 2: Database -->
        @if($currentStep === 2)
        <div class="p-8">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-xl bg-purple-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path></svg>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-slate-900">Configuración de Base de Datos</h2>
                    <p class="text-sm text-slate-500">Conexión MySQL</p>
                </div>
            </div>

            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Host</label>
                        <input type="text" wire:model="db_host" class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-purple-500/50 focus:border-purple-500" placeholder="127.0.0.1">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Puerto</label>
                        <input type="text" wire:model="db_port" class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-purple-500/50 focus:border-purple-500" placeholder="3306">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Nombre de la Base de Datos</label>
                    <input type="text" wire:model="db_database" class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-purple-500/50 focus:border-purple-500" placeholder="mikpos">
                    <p class="text-xs text-slate-500 mt-1">Se creará automáticamente si no existe</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Usuario</label>
                    <input type="text" wire:model="db_username" class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-purple-500/50 focus:border-purple-500" placeholder="root">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Contraseña</label>
                    <input type="password" wire:model="db_password" class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-purple-500/50 focus:border-purple-500" placeholder="••••••••">
                </div>

                @if($dbConnectionError)
                <div class="p-4 bg-red-50 border border-red-200 rounded-xl">
                    <p class="text-sm text-red-700">{{ $dbConnectionError }}</p>
                </div>
                @endif

                @if($dbConnectionTested)
                <div class="p-4 bg-green-50 border border-green-200 rounded-xl flex items-center gap-3">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <p class="text-sm text-green-700">Conexión exitosa a la base de datos</p>
                </div>
                @endif

                <button wire:click="testDatabaseConnection" class="w-full px-4 py-2.5 text-sm font-medium text-purple-700 bg-purple-50 border border-purple-200 rounded-xl hover:bg-purple-100 transition-colors">
                    Probar Conexión
                </button>
            </div>
        </div>
        @endif

        <!-- Step 3: Application -->
        @if($currentStep === 3)
        <div class="p-8">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-xl bg-orange-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-slate-900">Configuración de la Aplicación</h2>
                    <p class="text-sm text-slate-500">Datos generales del sistema</p>
                </div>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Nombre de la Aplicación</label>
                    <input type="text" wire:model="app_name" class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-orange-500/50 focus:border-orange-500" placeholder="MikPOS">
                    @error('app_name') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">URL de la Aplicación</label>
                    <input type="url" wire:model="app_url" class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-orange-500/50 focus:border-orange-500" placeholder="https://tudominio.com">
                    @error('app_url') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>
        @endif

        <!-- Step 4: Branch & Admin -->
        @if($currentStep === 4)
        <div class="p-8">
            @if(!$installationComplete)
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-xl bg-green-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-slate-900">Sucursal y Administrador</h2>
                    <p class="text-sm text-slate-500">Configuración inicial del negocio</p>
                </div>
            </div>

            @if($isInstalling)
            <!-- Installation Progress -->
            <div class="space-y-4">
                <div class="text-center py-8">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-purple-100 mb-4">
                        <svg class="w-8 h-8 text-purple-600 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                    <p class="text-lg font-medium text-slate-900">{{ $installationStatus }}</p>
                    <div class="mt-4 w-full bg-slate-200 rounded-full h-2">
                        <div class="bg-gradient-to-r from-[#ff7261] to-[#a855f7] h-2 rounded-full transition-all duration-500" style="width: {{ $installationProgress }}%"></div>
                    </div>
                    <p class="text-sm text-slate-500 mt-2">{{ $installationProgress }}% completado</p>
                </div>
            </div>
            @else
            <div class="space-y-6">
                <!-- Branch Info -->
                <div>
                    <h3 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        Datos de la Sucursal
                    </h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2 sm:col-span-1">
                            <label class="block text-sm font-medium text-slate-700 mb-1">Nombre de la Sucursal *</label>
                            <input type="text" wire:model="branch_name" class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-green-500/50 focus:border-green-500" placeholder="Sucursal Principal">
                            @error('branch_name') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="col-span-2 sm:col-span-1">
                            <label class="block text-sm font-medium text-slate-700 mb-1">Código *</label>
                            <input type="text" wire:model="branch_code" class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-green-500/50 focus:border-green-500" placeholder="SUC001">
                            @error('branch_code') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-1">Dirección</label>
                            <input type="text" wire:model="branch_address" class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-green-500/50 focus:border-green-500" placeholder="Calle 123 #45-67">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Teléfono</label>
                            <input type="text" wire:model="branch_phone" class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-green-500/50 focus:border-green-500" placeholder="+57 300 123 4567">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">NIT</label>
                            <input type="text" wire:model="branch_nit" class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-green-500/50 focus:border-green-500" placeholder="900.123.456-7">
                        </div>
                    </div>
                </div>

                <!-- Admin Info -->
                <div class="pt-4 border-t border-slate-200">
                    <h3 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        Usuario Administrador
                    </h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-1">Nombre Completo *</label>
                            <input type="text" wire:model="admin_name" class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-green-500/50 focus:border-green-500" placeholder="Juan Pérez">
                            @error('admin_name') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-1">Email *</label>
                            <input type="email" wire:model="admin_email" class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-green-500/50 focus:border-green-500" placeholder="admin@empresa.com">
                            @error('admin_email') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Contraseña *</label>
                            <input type="password" wire:model="admin_password" class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-green-500/50 focus:border-green-500" placeholder="••••••••">
                            @error('admin_password') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Confirmar Contraseña *</label>
                            <input type="password" wire:model="admin_password_confirmation" class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-green-500/50 focus:border-green-500" placeholder="••••••••">
                        </div>
                    </div>
                </div>

                @if($installationError)
                <div class="p-4 bg-red-50 border border-red-200 rounded-xl">
                    <p class="text-sm text-red-700">{{ $installationError }}</p>
                </div>
                @endif
            </div>
            @endif
            @else
            <!-- Installation Complete -->
            <div class="text-center py-8">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-green-100 mb-6">
                    <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <h2 class="text-2xl font-bold text-slate-900 mb-2">¡Instalación Completada!</h2>
                <p class="text-slate-500 mb-6">MikPOS ha sido instalado correctamente y está listo para usar.</p>
                
                <div class="p-4 bg-slate-50 rounded-xl mb-6 text-left">
                    <p class="text-sm font-medium text-slate-700 mb-2">Credenciales de acceso:</p>
                    <p class="text-sm text-slate-600">Email: <span class="font-mono">{{ $admin_email }}</span></p>
                    <p class="text-sm text-slate-600">Contraseña: <span class="font-mono">••••••••</span> (la que configuraste)</p>
                </div>

                <a href="/login" class="inline-flex items-center gap-2 px-6 py-3 text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl font-medium hover:from-[#e55a4a] hover:to-[#9333ea] transition-all">
                    Ir al Login
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
                </a>
            </div>
            @endif
        </div>
        @endif

        <!-- Footer Navigation -->
        @if(!$installationComplete && !$isInstalling)
        <div class="px-8 py-4 bg-slate-50 border-t border-slate-200 flex justify-between">
            @if($currentStep > 1)
            <button wire:click="previousStep" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition-colors">
                Anterior
            </button>
            @else
            <div></div>
            @endif

            @if($currentStep < $totalSteps)
            <button wire:click="nextStep" class="px-6 py-2 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea] transition-all">
                Siguiente
            </button>
            @else
            <button wire:click="install" class="px-6 py-2 text-sm font-medium text-white bg-gradient-to-r from-green-500 to-emerald-500 rounded-xl hover:from-green-600 hover:to-emerald-600 transition-all">
                Instalar MikPOS
            </button>
            @endif
        </div>
        @endif
    </div>

    <!-- Step Labels -->
    <div class="flex justify-center mt-6 gap-8 text-sm text-slate-400">
        <span class="{{ $currentStep === 1 ? 'text-white font-medium' : '' }}">Requisitos</span>
        <span class="{{ $currentStep === 2 ? 'text-white font-medium' : '' }}">Base de Datos</span>
        <span class="{{ $currentStep === 3 ? 'text-white font-medium' : '' }}">Aplicación</span>
        <span class="{{ $currentStep === 4 ? 'text-white font-medium' : '' }}">Finalizar</span>
    </div>
</div>

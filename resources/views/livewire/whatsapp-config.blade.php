<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Configuración de WhatsApp</h1>
            <p class="text-slate-500 mt-1">Configuración para el envío de mensajes por WhatsApp desde la API oficial de Meta</p>
        </div>
        <div class="flex items-center gap-3">
            <!-- Status Badge -->
            @if($is_active)
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
                    <h3 class="text-lg font-semibold text-slate-800">Habilitar Integración de WhatsApp</h3>
                    <p class="text-sm text-slate-500 mt-1">Activa la integración para enviar notificaciones vía WhatsApp</p>
                </div>
                <button wire:click="toggleActive" 
                    class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 {{ $is_active ? 'bg-green-500' : 'bg-slate-200' }}">
                    <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200 {{ $is_active ? 'translate-x-5' : 'translate-x-0' }}"></span>
                </button>
            </div>
        </div>

        <div class="p-6 space-y-6">
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3">
                <p class="text-sm font-medium text-amber-800">Esta configuración es por sucursal.</p>
                <p class="text-xs text-amber-700 mt-1">Solo se permite una configuración de WhatsApp por sucursal, incluso si tu empresa tiene varias sucursales.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Sucursal</label>
                <select wire:model.live="branch_id"
                    @disabled(!$canSelectBranch)
                    class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] {{ !$canSelectBranch ? 'bg-slate-100 cursor-not-allowed' : '' }}">
                    <option value="">Selecciona una sucursal</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch['id'] }}">{{ $branch['name'] }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-slate-500 mt-1">
                    {{ $canSelectBranch ? 'Selecciona la sucursal a la que deseas asignar esta configuración.' : 'Esta configuración está asociada a tu sucursal actual.' }}
                </p>
                @error('branch_id') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- Phone Number ID -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Identificador de número de teléfono (Phone Number ID)</label>
                    <input wire:model="phone_number_id" type="text" 
                        class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]"
                        placeholder="Ej. 1195853100276211">
                    @error('phone_number_id') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <!-- WABA ID -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Identificador de cuenta de WhatsApp Business (WABA ID)</label>
                    <input wire:model="waba_id" type="text" 
                        class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]"
                        placeholder="Tu WABA ID">
                    @error('waba_id') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <!-- Phone Number Oficial -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Número de teléfono oficial</label>
                    <input wire:model="phone_number_oficial" type="text" 
                        class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]"
                        placeholder="Ej. 573153920724">
                    @error('phone_number_oficial') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <!-- API Version -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Versión de la API</label>
                    <input wire:model="api_version" type="text" 
                        class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]"
                        placeholder="Ej. v25.0">
                    @error('api_version') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- Token Permanente -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Token de Acceso Permanente</label>
                <textarea wire:model="token_permanente" rows="4"
                    class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]"
                    placeholder="EAAWIi1Lix9..."></textarea>
                <p class="text-xs text-slate-500 mt-1">Este token debe ser generado desde el panel de desarrolladores de Meta y configurado para no expirar.</p>
                @error('token_permanente') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>

            <div class="pt-4 border-t border-slate-200 space-y-4">
                <div>
                    <h4 class="text-sm font-semibold text-slate-700">Mensaje de prueba</h4>
                    <p class="text-xs text-slate-500 mt-1">Usa esta prueba para enviar un template directamente desde tu sistema al destinatario habilitado en Meta.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Destinatario de prueba</label>
                        <input wire:model="test_recipient" type="text"
                            class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]"
                            placeholder="Ej. 573153920724">
                        <p class="text-xs text-slate-500 mt-1">Debe estar en formato internacional con indicativo de pais.</p>
                        @error('test_recipient') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Template</label>
                        <input wire:model="test_template_name" type="text"
                            class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]"
                            placeholder="hello_world">
                        <p class="text-xs text-slate-500 mt-1">Para la primera prueba en Meta normalmente funciona `hello_world`.</p>
                        @error('test_template_name') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Idioma del template</label>
                        <input wire:model="test_template_language" type="text"
                            class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]"
                            placeholder="en_US">
                        @error('test_template_language') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Parámetros del body en JSON (opcional)</label>
                        <textarea wire:model="test_template_parameters" rows="4"
                            class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]"
                            placeholder='[{"type":"text","text":"John Doe"},{"type":"text","text":"123456"}]'></textarea>
                        <p class="text-xs text-slate-500 mt-1">Déjalo vacío si el template no necesita variables. Si tu template tiene placeholders, aquí puedes enviar los parámetros del body.</p>
                        @error('test_template_parameters') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs text-slate-600">Endpoint que usará el sistema:</p>
                    <code class="block mt-2 text-xs text-slate-800 break-all">https://graph.facebook.com/{{ $api_version ?: 'v25.0' }}/{{ $phone_number_id ?: 'PHONE_NUMBER_ID' }}/messages</code>
                </div>

                <div class="flex justify-end">
                    <button wire:click="sendTestMessage" class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#25D366] text-white font-medium rounded-xl hover:bg-[#1faa55] transition-colors">
                        <svg wire:loading.remove wire:target="sendTestMessage" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h8m-4-4l4 4-4 4M3 12h5"></path>
                        </svg>
                        <svg wire:loading wire:target="sendTestMessage" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="sendTestMessage">Enviar Prueba</span>
                        <span wire:loading wire:target="sendTestMessage">Enviando...</span>
                    </button>
                </div>

                @if($testResult)
                    <div class="rounded-xl border p-4 {{ $testResult['success'] ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50' }}">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="text-sm font-semibold {{ $testResult['success'] ? 'text-green-700' : 'text-red-700' }}">
                                    {{ $testResult['success'] ? 'Envio exitoso' : 'Envio fallido' }}
                                </p>
                                <p class="text-xs mt-1 {{ $testResult['success'] ? 'text-green-700' : 'text-red-700' }}">
                                    {{ $testResult['message'] }}
                                </p>
                            </div>
                        </div>

                        @if(!empty($testResult['payload']))
                            <div class="mt-4">
                                <p class="text-xs font-medium text-slate-700 mb-2">Payload enviado</p>
                                <pre class="text-xs bg-white/80 border border-slate-200 rounded-lg p-3 overflow-auto">{{ json_encode($testResult['payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                            </div>
                        @endif

                        @if(!empty($testResult['response']))
                            <div class="mt-4">
                                <p class="text-xs font-medium text-slate-700 mb-2">Respuesta de Meta</p>
                                <pre class="text-xs bg-white/80 border border-slate-200 rounded-lg p-3 overflow-auto">{{ json_encode($testResult['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Save Button -->
            <div class="flex justify-end pt-4 border-t border-slate-200">
                <button wire:click="save" class="inline-flex items-center gap-2 px-6 py-2.5 bg-slate-900 text-white font-medium rounded-xl hover:bg-slate-800 transition-colors">
                    <svg wire:loading.remove wire:target="save" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <svg wire:loading wire:target="save" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span wire:loading.remove wire:target="save">Guardar Configuración</span>
                    <span wire:loading wire:target="save">Guardando...</span>
                </button>
            </div>
        </div>
    </div>
</div>

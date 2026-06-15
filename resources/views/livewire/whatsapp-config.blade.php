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

                <!-- Template Name -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Template por defecto (nombre interno)</label>
                    <input wire:model="template_name" type="text"
                        class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]"
                        placeholder="Ej. mikpos">
                    <p class="text-xs text-slate-500 mt-1">Debe coincidir exactamente con el nombre del template en Meta (no el título visible).</p>
                    @error('template_name') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <!-- Template Language -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Idioma del template por defecto</label>
                    <input wire:model="template_language" type="text"
                        class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]"
                        placeholder="Ej. es_CO">
                    <p class="text-xs text-slate-500 mt-1">Ejemplo recomendado: <span class="font-medium">es_CO</span>. Debe existir como traducción de esa plantilla en Meta.</p>
                    @error('template_language') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
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

            <div class="rounded-2xl border border-blue-200 bg-blue-50 p-5 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h4 class="text-sm font-semibold text-blue-900">Webhook de WhatsApp</h4>
                        <p class="text-xs text-blue-700 mt-1">Configura estos datos en Meta para recibir estados reales de entrega y mensajes entrantes.</p>
                    </div>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $webhookAppSecretConfigured ? 'bg-green-100 text-green-700 border border-green-200' : 'bg-amber-100 text-amber-700 border border-amber-200' }}">
                        {{ $webhookAppSecretConfigured ? 'Firma activa' : 'Firma opcional pendiente' }}
                    </span>
                </div>

                <div class="grid grid-cols-1 gap-4">
                    <div class="rounded-xl border border-blue-200 bg-white p-4">
                        <p class="text-xs font-medium text-slate-700">URL de devolución de llamada</p>
                        <code class="block mt-2 text-xs text-slate-800 break-all">{{ $webhookUrl }}</code>
                    </div>

                    <div class="rounded-xl border border-blue-200 bg-white p-4">
                        <p class="text-xs font-medium text-slate-700">Token de verificación</p>
                        <code class="block mt-2 text-xs text-slate-800 break-all">{{ $webhookVerifyToken }}</code>
                    </div>
                </div>

                <div class="rounded-xl border border-blue-200 bg-white p-4 text-xs text-slate-700 space-y-2">
                    <p><span class="font-semibold">Campo a suscribir en Meta:</span> <code>messages</code></p>
                    <p><span class="font-semibold">Requisito:</span> la URL debe ser publica y con <code>https</code>. Si <code>APP_URL</code> apunta a local, cambia ese valor en produccion.</p>
                    <p><span class="font-semibold">Seguridad:</span> si configuras <code>WHATSAPP_WEBHOOK_APP_SECRET</code> en el servidor, tambien se validara la firma <code>X-Hub-Signature-256</code>.</p>
                </div>
            </div>

            <div class="rounded-2xl border border-violet-200 bg-violet-50 p-5 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h4 class="text-sm font-semibold text-violet-900">Diagnóstico de Meta</h4>
                        <p class="text-xs text-violet-700 mt-1">Consulta si el WABA tiene apps suscritas, si el `phone_number_id` existe en ese WABA y qué estado devuelve Meta para el número.</p>
                    </div>
                    <button wire:click="runMetaDiagnostic" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-violet-700 rounded-xl hover:bg-violet-800 transition-colors">
                        <svg wire:loading.remove wire:target="runMetaDiagnostic" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6m3 6V7m3 10v-3m3 7H6a2 2 0 01-2-2V5a2 2 0 012-2h7.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <svg wire:loading wire:target="runMetaDiagnostic" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="runMetaDiagnostic">Consultar Meta</span>
                        <span wire:loading wire:target="runMetaDiagnostic">Consultando...</span>
                    </button>
                </div>

                @if($metaDiagnostic)
                    <div class="rounded-xl border p-4 {{ !empty($metaDiagnostic['success']) ? 'border-green-200 bg-green-50' : 'border-amber-200 bg-amber-50' }}">
                        @if(!empty($metaDiagnostic['summary']))
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 text-xs text-slate-700">
                                <div class="space-y-2">
                                    <p><span class="font-semibold">Apps suscritas al WABA:</span> {{ $metaDiagnostic['summary']['subscriptions_count'] ?? 'N/D' }}</p>
                                    <p><span class="font-semibold">Nombres de apps:</span> {{ !empty($metaDiagnostic['summary']['subscription_names']) ? implode(', ', $metaDiagnostic['summary']['subscription_names']) : 'N/D' }}</p>
                                    <p><span class="font-semibold">Phone ID existe en el WABA:</span> {{ !empty($metaDiagnostic['summary']['phone_exists_in_waba']) ? 'Si' : 'No' }}</p>
                                </div>
                                <div class="space-y-2">
                                    <p><span class="font-semibold">Estado del numero:</span> {{ $metaDiagnostic['summary']['phone_status'] ?? 'N/D' }}</p>
                                    <p><span class="font-semibold">Quality rating:</span> {{ $metaDiagnostic['summary']['phone_quality_rating'] ?? 'N/D' }}</p>
                                    <p><span class="font-semibold">Code verification:</span> {{ $metaDiagnostic['summary']['code_verification_status'] ?? 'N/D' }}</p>
                                    <p><span class="font-semibold">Name status:</span> {{ $metaDiagnostic['summary']['name_status'] ?? 'N/D' }}</p>
                                    <p><span class="font-semibold">Verified name:</span> {{ $metaDiagnostic['summary']['verified_name'] ?? 'N/D' }}</p>
                                </div>
                            </div>
                        @endif

                        @if(!empty($metaDiagnostic['message']))
                            <p class="text-sm text-red-700">{{ $metaDiagnostic['message'] }}</p>
                        @endif

                        @if(!empty($metaDiagnostic['responses']))
                            <div class="mt-4 space-y-3">
                                <div>
                                    <p class="text-xs font-medium text-slate-700 mb-2">Respuesta `subscribed_apps`</p>
                                    <pre class="text-xs bg-white/80 border border-slate-200 rounded-lg p-3 overflow-auto">{{ json_encode($metaDiagnostic['responses']['subscribed_apps'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-slate-700 mb-2">Respuesta `/{WABA_ID}/phone_numbers`</p>
                                    <pre class="text-xs bg-white/80 border border-slate-200 rounded-lg p-3 overflow-auto">{{ json_encode($metaDiagnostic['responses']['waba_phone_numbers'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-slate-700 mb-2">Respuesta `/{PHONE_NUMBER_ID}`</p>
                                    <pre class="text-xs bg-white/80 border border-slate-200 rounded-lg p-3 overflow-auto">{{ json_encode($metaDiagnostic['responses']['phone_number'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
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
                                    {{ $testResult['success'] ? 'Solicitud aceptada por Meta' : 'Envio fallido' }}
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

            <div wire:poll.15s class="pt-4 border-t border-slate-200 space-y-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h4 class="text-sm font-semibold text-slate-700">Trazabilidad del webhook</h4>
                        <p class="text-xs text-slate-500 mt-1">Aqui veras aceptaciones de Meta, cambios de estado y mensajes entrantes del numero configurado.</p>
                    </div>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                        {{ $recentLogs->count() }} registro(s)
                    </span>
                </div>

                @if($recentLogs->isEmpty())
                    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-5 text-sm text-slate-600">
                        Todavia no hay eventos para esta sucursal. Cuando Meta envie el webhook o aceptes un mensaje, apareceran aqui.
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($recentLogs as $log)
                            <details class="rounded-xl border border-slate-200 bg-white p-4">
                                <summary class="list-none cursor-pointer">
                                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                                        <div class="space-y-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold
                                                    @if($log->status === 'failed') bg-red-100 text-red-700
                                                    @elseif($log->status === 'delivered' || $log->status === 'read') bg-green-100 text-green-700
                                                    @elseif($log->status === 'sent' || $log->status === 'accepted') bg-blue-100 text-blue-700
                                                    @else bg-slate-100 text-slate-700 @endif">
                                                    {{ strtoupper($log->status ?: 'SIN ESTADO') }}
                                                </span>
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $log->direction === 'inbound' ? 'bg-violet-100 text-violet-700' : 'bg-amber-100 text-amber-700' }}">
                                                    {{ $log->direction === 'inbound' ? 'Entrante' : 'Saliente' }}
                                                </span>
                                                @if($log->event_type)
                                                    <span class="text-xs text-slate-500">{{ $log->event_type }}</span>
                                                @endif
                                            </div>
                                            <p class="text-sm font-medium text-slate-800">{{ $log->contact_phone ?: 'Sin numero' }}</p>
                                            <p class="text-xs text-slate-500">
                                                {{ $log->message_body ?: 'Sin resumen disponible' }}
                                            </p>
                                        </div>

                                        <div class="text-xs text-slate-500 lg:text-right">
                                            <p>Ultimo cambio: {{ optional($log->last_status_at)->format('Y-m-d H:i:s') ?: 'N/D' }}</p>
                                            <p>ID: {{ $log->message_id ?: 'Sin message_id' }}</p>
                                        </div>
                                    </div>
                                </summary>

                                <div class="mt-4 grid grid-cols-1 lg:grid-cols-2 gap-4">
                                    <div class="space-y-2 text-xs text-slate-700">
                                        <p><span class="font-semibold">Tipo:</span> {{ $log->message_type ?: 'N/D' }}</p>
                                        <p><span class="font-semibold">Template:</span> {{ $log->template_name ?: 'N/D' }}</p>
                                        <p><span class="font-semibold">Idioma:</span> {{ $log->template_language ?: 'N/D' }}</p>
                                        <p><span class="font-semibold">Phone Number ID:</span> {{ $log->phone_number_id ?: 'N/D' }}</p>
                                        <p><span class="font-semibold">Numero oficial:</span> {{ $log->display_phone_number ?: 'N/D' }}</p>
                                        @if($log->error_message)
                                            <p class="text-red-600"><span class="font-semibold">Error:</span> {{ $log->error_message }}</p>
                                        @endif
                                    </div>

                                    <div class="space-y-2 text-xs text-slate-700">
                                        <p><span class="font-semibold">Aceptado:</span> {{ optional($log->accepted_at)->format('Y-m-d H:i:s') ?: 'N/D' }}</p>
                                        <p><span class="font-semibold">Enviado:</span> {{ optional($log->sent_at)->format('Y-m-d H:i:s') ?: 'N/D' }}</p>
                                        <p><span class="font-semibold">Entregado:</span> {{ optional($log->delivered_at)->format('Y-m-d H:i:s') ?: 'N/D' }}</p>
                                        <p><span class="font-semibold">Leido:</span> {{ optional($log->read_at)->format('Y-m-d H:i:s') ?: 'N/D' }}</p>
                                        <p><span class="font-semibold">Fallido:</span> {{ optional($log->failed_at)->format('Y-m-d H:i:s') ?: 'N/D' }}</p>
                                        <p><span class="font-semibold">Webhook recibido:</span> {{ optional($log->webhook_received_at)->format('Y-m-d H:i:s') ?: 'N/D' }}</p>
                                    </div>
                                </div>

                                @if($log->send_payload || $log->send_response || $log->webhook_payload)
                                    <div class="mt-4 space-y-3">
                                        @if($log->send_payload)
                                            <div>
                                                <p class="text-xs font-medium text-slate-700 mb-2">Payload enviado</p>
                                                <pre class="text-xs bg-slate-50 border border-slate-200 rounded-lg p-3 overflow-auto">{{ json_encode($log->send_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                            </div>
                                        @endif
                                        @if($log->send_response)
                                            <div>
                                                <p class="text-xs font-medium text-slate-700 mb-2">Respuesta de la API</p>
                                                <pre class="text-xs bg-slate-50 border border-slate-200 rounded-lg p-3 overflow-auto">{{ json_encode($log->send_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                            </div>
                                        @endif
                                        @if($log->webhook_payload)
                                            <div>
                                                <p class="text-xs font-medium text-slate-700 mb-2">Payload del webhook</p>
                                                <pre class="text-xs bg-slate-50 border border-slate-200 rounded-lg p-3 overflow-auto">{{ json_encode($log->webhook_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </details>
                        @endforeach
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

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Evo WhatsApp</h1>
            <p class="text-slate-500 mt-1">Configuración para el envío de mensajes mediante Evolution GO</p>
        </div>
        <div class="flex items-center gap-3">
            <!-- Status Badge -->
            @if($status === 'connected')
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-700">
                    <span class="w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                    Conectado
                </span>
            @elseif($status === 'connecting')
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-amber-100 text-amber-700">
                    <span class="w-2 h-2 bg-amber-500 rounded-full mr-2 animate-pulse"></span>
                    Conectando / Esperando QR
                </span>
            @else
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-slate-100 text-slate-600">
                    <span class="w-2 h-2 bg-slate-400 rounded-full mr-2"></span>
                    Desconectado
                </span>
            @endif
        </div>
    </div>

    <!-- Main Settings Card -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden" wire:poll.5s="checkStatus">
        <!-- Enable/Disable Toggle -->
        <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-slate-800">Habilitar Integración</h3>
                    <p class="text-sm text-slate-500 mt-1">Activa el uso de Evo WhatsApp para esta sucursal</p>
                </div>
                <button wire:click="toggleActive" 
                    class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 {{ $is_active ? 'bg-green-500' : 'bg-slate-200' }}">
                    <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200 {{ $is_active ? 'translate-x-5' : 'translate-x-0' }}"></span>
                </button>
            </div>
        </div>

        <div class="p-6 space-y-6">
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
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Info Section -->
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Nombre de la Instancia</label>
                        <input wire:model="instance_name" type="text" readonly
                            class="w-full px-3 py-2 bg-slate-100 border border-slate-300 rounded-xl text-slate-600">
                    </div>
                    
                    @if($instance_token)
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Token de la Instancia (API Key)</label>
                            <input wire:model="instance_token" type="text" readonly
                                class="w-full px-3 py-2 bg-slate-100 border border-slate-300 rounded-xl text-slate-600">
                        </div>
                    @endif

                    <div class="flex flex-wrap gap-3 mt-4">
                        @if($status === 'disconnected' || $status === 'close')
                            <button wire:click="createInstance" class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#25D366] text-white font-medium rounded-xl hover:bg-[#1faa55] transition-colors">
                                <span wire:loading.remove wire:target="createInstance">Crear Instancia</span>
                                <span wire:loading wire:target="createInstance">Conectando...</span>
                            </button>
                            @if($instance_token)
                            <button wire:click="connectInstance" class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 text-white font-medium rounded-xl hover:bg-blue-700 transition-colors">
                                <span wire:loading.remove wire:target="connectInstance">Obtener QR / Conectar</span>
                                <span wire:loading wire:target="connectInstance">Obteniendo...</span>
                            </button>
                            @endif
                        @elseif($status === 'connecting')
                            <button wire:click="connectInstance" class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 text-white font-medium rounded-xl hover:bg-blue-700 transition-colors">
                                <span wire:loading.remove wire:target="connectInstance">Refrescar QR</span>
                                <span wire:loading wire:target="connectInstance">Refrescando...</span>
                            </button>
                        @endif
                        
                        @if($instance_token && $status !== 'disconnected')
                            <button wire:click="deleteInstance" class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-500 text-white font-medium rounded-xl hover:bg-red-600 transition-colors"
                                onclick="confirm('¿Estás seguro de eliminar esta instancia?') || event.stopImmediatePropagation()">
                                Eliminar Instancia
                            </button>
                        @endif
                    </div>
                </div>

                <!-- QR Section -->
                <div class="flex flex-col items-center justify-center border-2 border-dashed border-slate-300 rounded-2xl p-6 bg-slate-50 min-h-[300px]">
                    @if($qr_code && $status !== 'connected')
                        <h4 class="text-lg font-semibold text-slate-700 mb-4">Escanea el código QR</h4>
                        <img src="{{ $qr_code }}" alt="QR Code" class="w-64 h-64 border p-2 bg-white rounded-xl shadow-sm">
                        <p class="text-sm text-slate-500 mt-4 text-center">Abre WhatsApp en tu teléfono > Dispositivos vinculados > Vincular un dispositivo</p>
                    @elseif($status === 'connected')
                        <div class="text-center">
                            <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <h4 class="text-lg font-semibold text-slate-800">WhatsApp Conectado</h4>
                            <p class="text-sm text-slate-500 mt-2">Tu instancia está lista para enviar y recibir mensajes.</p>
                        </div>
                    @else
                        <div class="text-center text-slate-400">
                            <svg class="w-16 h-16 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
                            </svg>
                            <p>Crea una instancia para obtener el código QR</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Global Configuration Form (Super Admin Only) -->
            @if(auth()->user()->isSuperAdmin())
                <div class="mt-8 pt-6 border-t border-slate-200">
                    <h4 class="text-lg font-semibold text-slate-800 mb-1">Configuración Global del Servidor</h4>
                    <p class="text-sm text-slate-500 mb-4">Estos parámetros se guardarán automáticamente en tu archivo de entorno (.env).</p>
                    <form wire:submit="saveGlobalSettings" class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-slate-50 p-4 rounded-xl border border-slate-200">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">URL del Servidor (Server URL)</label>
                            <input wire:model="server_url" type="url" placeholder="Ej. https://tu-evolution-api.com" required
                                class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                            @error('server_url') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Global API Key</label>
                            <input wire:model="global_api_key" type="password" placeholder="Tu API Key Global" required
                                class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                            @error('global_api_key') <span class="text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div class="md:col-span-2">
                            <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 bg-slate-800 text-white font-medium rounded-xl hover:bg-slate-700 transition-colors">
                                <span wire:loading.remove wire:target="saveGlobalSettings">Guardar en Configuración (.env)</span>
                                <span wire:loading wire:target="saveGlobalSettings">Guardando...</span>
                            </button>
                        </div>
                    </form>
                </div>
            @elseif(empty($global_api_key))
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 mt-8">
                    <p class="text-sm font-medium text-amber-800">Falta la configuración global</p>
                    <p class="text-xs text-amber-700 mt-1">Por favor, comunícate con el Administrador Principal para que configure la URL y API Key del servidor.</p>
                </div>
            @endif
        </div>
    </div>
</div>

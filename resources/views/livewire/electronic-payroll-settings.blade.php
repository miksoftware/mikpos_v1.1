<div>
    <div class="p-4 sm:p-6 lg:p-8 max-w-5xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-900">Configuración de Nómina Electrónica</h1>
            <p class="text-slate-500 text-sm mt-1">Parámetros de conexión y rangos de numeración con la DIAN / Factus V2</p>
        </div>

        @if(!$isFactusConfigured)
        <div class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-2xl flex items-start gap-3">
            <svg class="w-6 h-6 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <div>
                <h3 class="font-bold text-amber-900 text-sm">Credenciales de Factus no configuradas</h3>
                <p class="text-amber-700 text-xs mt-1">
                    La Nómina Electrónica comparte las credenciales de la API de Factus con el módulo de Facturación Electrónica. Por favor ve a 
                    <a href="{{ route('billing.settings') }}" class="font-bold underline text-amber-900">Configuración ➔ Facturación Electrónica</a> e ingresa tus credenciales (Client ID, Client Secret, Usuario y Contraseña).
                </p>
            </div>
        </div>
        @endif

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-6">
            <form wire:submit="save" class="space-y-6">
                <!-- Habilitar servicio -->
                <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-200">
                    <div>
                        <h3 class="font-semibold text-slate-900 text-sm">Habilitar Nómina Electrónica</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Permite la transmisión oficial de desprendibles y notas de ajuste a la DIAN a través de Factus.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" wire:model="is_enabled" class="sr-only peer" @if(!$isFactusConfigured) disabled @endif>
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-[#ff7261] peer-checked:to-[#a855f7]"></div>
                    </label>
                </div>

                <!-- Ambiente -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Ambiente de Operación</label>
                        <select wire:model="environment" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                            <option value="sandbox">Pruebas / Sandbox (api-sandbox.factus.com.co)</option>
                            <option value="production">Producción (api.factus.com.co)</option>
                        </select>
                        @error('environment') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex items-end">
                        <button type="button" wire:click="loadNumberingRanges" class="w-full px-4 py-2 text-sm font-medium text-slate-700 bg-slate-100 rounded-xl hover:bg-slate-200 transition-colors flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            Sincronizar Rangos desde Factus
                        </button>
                    </div>
                </div>

                @if($factusErrorMessage)
                <div class="p-3 bg-red-50 border border-red-200 rounded-xl text-red-700 text-xs">
                    Error al consultar Factus: {{ $factusErrorMessage }}
                </div>
                @endif

                <!-- Rangos de Numeración -->
                <div class="border-t border-slate-200 pt-6 space-y-4">
                    <h3 class="font-semibold text-slate-900 text-sm flex items-center gap-2">
                        <svg class="w-4 h-4 text-[#a855f7]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/></svg>
                        Rangos de Numeración Habilitados en Factus
                    </h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Rango para Nómina Electrónica *</label>
                            <select wire:model="payroll_numbering_range_id" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                <option value="">Seleccionar rango...</option>
                                @foreach($numberingRanges as $range)
                                <option value="{{ $range['id'] }}">
                                    {{ $range['document'] ?? 'Nómina' }} - Prefijo: {{ $range['prefix'] ?? 'Sin prefijo' }} (Del {{ $range['from'] ?? 1 }} al {{ $range['to'] ?? '' }})
                                </option>
                                @endforeach
                            </select>
                            <p class="text-xs text-slate-500 mt-1">Sincroniza y selecciona la resolución habilitada para la emisión de Nómina.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Rango para Notas de Ajuste de Nómina</label>
                            <select wire:model="adjustment_numbering_range_id" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261]">
                                <option value="">Seleccionar rango (Opcional)...</option>
                                @foreach($numberingRanges as $range)
                                <option value="{{ $range['id'] }}">
                                    {{ $range['document'] ?? 'Nota Ajuste' }} - Prefijo: {{ $range['prefix'] ?? 'Sin prefijo' }} (Del {{ $range['from'] ?? 1 }} al {{ $range['to'] ?? '' }})
                                </option>
                                @endforeach
                            </select>
                            <p class="text-xs text-slate-500 mt-1">Requerido si usas un rango diferente para reemplazar o eliminar nóminas.</p>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                @if(auth()->user()->hasPermission('electronic_payroll.edit'))
                <div class="flex justify-end pt-4 border-t border-slate-200">
                    <button type="submit" class="px-6 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea] transition-all">
                        Guardar Configuración
                    </button>
                </div>
                @endif
            </form>
        </div>
    </div>
</div>

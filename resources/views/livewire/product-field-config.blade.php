<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Configuración de Campos de Producto</h1>
            <p class="text-slate-500 mt-1">Define qué campos son visibles y requeridos para productos y variantes</p>
        </div>
    </div>

    {{-- Branch Selector --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
            <div class="flex-1">
                <label class="block text-sm font-medium text-slate-700 mb-2">Configuración para:</label>
                <select wire:model.live="branchId" class="w-full sm:w-64 px-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] transition-all text-sm">
                    <option value="">🌐 Global (todas las sucursales)</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}">🏪 {{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center gap-2">
                @if($hasChanges)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-amber-100 text-amber-700">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        Cambios sin guardar
                    </span>
                @endif
            </div>
        </div>
    </div>

    {{-- Presets Section --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <h2 class="text-lg font-semibold text-slate-800 mb-4">Presets de Tipo de Negocio</h2>
        <p class="text-sm text-slate-500 mb-4">Selecciona un preset para configurar rápidamente los campos según tu tipo de negocio</p>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
            @foreach($availablePresets as $key => $preset)
                <button 
                    wire:click="applyPreset('{{ $key }}')"
                    @if(!auth()->user()->hasPermission('product_field_config.edit')) disabled @endif
                    class="relative p-4 rounded-xl border-2 transition-all duration-200 text-left
                        {{ $selectedPreset === $key 
                            ? 'border-[#ff7261] bg-orange-50 ring-2 ring-[#ff7261]/20' 
                            : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50' }}
                        @if(!auth()->user()->hasPermission('product_field_config.edit')) opacity-50 cursor-not-allowed @endif"
                >
                    @if($selectedPreset === $key)
                        <div class="absolute top-2 right-2">
                            <svg class="w-5 h-5 text-[#ff7261]" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                    @endif
                    <div class="text-2xl mb-2">
                        @switch($key)
                            @case('pharmacy') 💊 @break
                            @case('cellphones') 📱 @break
                            @case('clothing') 👕 @break
                            @case('jewelry') 💎 @break
                            @default 🏪
                        @endswitch
                    </div>
                    <h3 class="font-semibold text-slate-800">{{ $preset['name'] }}</h3>
                    <p class="text-xs text-slate-500 mt-1">{{ $preset['description'] }}</p>
                </button>
            @endforeach
        </div>
    </div>

    {{-- Field Configuration Table --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-800">Configuración de Campos</h2>
            @if(auth()->user()->hasPermission('product_field_config.edit'))
                <button 
                    wire:click="resetToDefaults"
                    class="text-sm text-slate-500 hover:text-slate-700 transition-colors"
                >
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Restablecer valores por defecto
                </button>
            @endif
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-slate-500 uppercase" rowspan="2">Campo</th>
                        <th class="px-3 py-2 text-center text-sm font-semibold text-blue-600 uppercase border-b border-slate-200" colspan="2">
                            <div class="flex items-center justify-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                                Producto (Padre)
                            </div>
                        </th>
                        <th class="px-3 py-2 text-center text-sm font-semibold text-purple-600 uppercase border-b border-slate-200" colspan="2">
                            <div class="flex items-center justify-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                Variante (Hijo)
                            </div>
                        </th>
                    </tr>
                    <tr class="bg-slate-50">
                        <th class="px-3 py-2 text-center text-xs font-medium text-slate-500 uppercase">Visible</th>
                        <th class="px-3 py-2 text-center text-xs font-medium text-slate-500 uppercase">Requerido</th>
                        <th class="px-3 py-2 text-center text-xs font-medium text-slate-500 uppercase">Visible</th>
                        <th class="px-3 py-2 text-center text-xs font-medium text-slate-500 uppercase">Requerido</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach($configurableFields as $fieldName => $config)
                        @php
                            $settings = $fieldSettings[$fieldName] ?? [];
                            $parentVisible = $settings['parent_visible'] ?? false;
                            $parentRequired = $settings['parent_required'] ?? false;
                            $childVisible = $settings['child_visible'] ?? false;
                            $childRequired = $settings['child_required'] ?? false;
                        @endphp
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <span class="font-medium text-slate-900">{{ $config['label'] }}</span>
                                <span class="text-xs text-slate-400 block">{{ $fieldName }}</span>
                            </td>
                            {{-- Parent Visible --}}
                            <td class="px-3 py-4 text-center">
                                @if(auth()->user()->hasPermission('product_field_config.edit'))
                                    <button 
                                        wire:click="toggleSetting('{{ $fieldName }}', 'parent_visible')"
                                        class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 {{ $parentVisible ? 'bg-blue-500' : 'bg-slate-200' }}"
                                    >
                                        <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200 {{ $parentVisible ? 'translate-x-5' : 'translate-x-0' }}"></span>
                                    </button>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-sm font-medium {{ $parentVisible ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-500' }}">
                                        {{ $parentVisible ? 'Sí' : 'No' }}
                                    </span>
                                @endif
                            </td>
                            {{-- Parent Required --}}
                            <td class="px-3 py-4 text-center">
                                @if(auth()->user()->hasPermission('product_field_config.edit'))
                                    <button 
                                        wire:click="toggleSetting('{{ $fieldName }}', 'parent_required')"
                                        @if(!$parentVisible) disabled @endif
                                        class="relative inline-flex h-6 w-11 flex-shrink-0 rounded-full border-2 border-transparent transition-colors duration-200 
                                            {{ $parentRequired ? 'bg-blue-700' : 'bg-slate-200' }}
                                            {{ !$parentVisible ? 'opacity-30 cursor-not-allowed' : 'cursor-pointer' }}"
                                    >
                                        <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200 {{ $parentRequired ? 'translate-x-5' : 'translate-x-0' }}"></span>
                                    </button>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-sm font-medium {{ $parentRequired ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-500' }}">
                                        {{ $parentRequired ? 'Sí' : 'No' }}
                                    </span>
                                @endif
                            </td>
                            {{-- Child Visible --}}
                            <td class="px-3 py-4 text-center">
                                @if(auth()->user()->hasPermission('product_field_config.edit'))
                                    <button 
                                        wire:click="toggleSetting('{{ $fieldName }}', 'child_visible')"
                                        class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 {{ $childVisible ? 'bg-purple-500' : 'bg-slate-200' }}"
                                    >
                                        <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200 {{ $childVisible ? 'translate-x-5' : 'translate-x-0' }}"></span>
                                    </button>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-sm font-medium {{ $childVisible ? 'bg-purple-100 text-purple-700' : 'bg-slate-100 text-slate-500' }}">
                                        {{ $childVisible ? 'Sí' : 'No' }}
                                    </span>
                                @endif
                            </td>
                            {{-- Child Required --}}
                            <td class="px-3 py-4 text-center">
                                @if(auth()->user()->hasPermission('product_field_config.edit'))
                                    <button 
                                        wire:click="toggleSetting('{{ $fieldName }}', 'child_required')"
                                        @if(!$childVisible) disabled @endif
                                        class="relative inline-flex h-6 w-11 flex-shrink-0 rounded-full border-2 border-transparent transition-colors duration-200 
                                            {{ $childRequired ? 'bg-purple-700' : 'bg-slate-200' }}
                                            {{ !$childVisible ? 'opacity-30 cursor-not-allowed' : 'cursor-pointer' }}"
                                    >
                                        <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200 {{ $childRequired ? 'translate-x-5' : 'translate-x-0' }}"></span>
                                    </button>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-sm font-medium {{ $childRequired ? 'bg-purple-100 text-purple-700' : 'bg-slate-100 text-slate-500' }}">
                                        {{ $childRequired ? 'Sí' : 'No' }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Save Button --}}
    @if(auth()->user()->hasPermission('product_field_config.edit'))
        <div class="flex justify-end gap-3">
            <button 
                wire:click="saveSettings"
                @if(!$hasChanges) disabled @endif
                class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-[#ff7261] to-[#a855f7] hover:from-[#e55a4a] hover:to-[#9333ea] text-white text-sm font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-[1.02] transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none disabled:hover:shadow-lg"
            >
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                Guardar Configuración
            </button>
        </div>
    @endif

    {{-- Help Section --}}
    <div class="bg-blue-50 rounded-2xl border border-blue-200 p-6">
        <div class="flex items-start gap-4">
            <div class="flex-shrink-0">
                <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div>
                <h3 class="font-semibold text-blue-800 mb-2">¿Cómo funciona?</h3>
                <ul class="text-sm text-blue-700 space-y-1">
                    <li>• <strong>Producto (Padre):</strong> Campos que aparecen al crear/editar un producto principal</li>
                    <li>• <strong>Variante (Hijo):</strong> Campos que aparecen al crear/editar una variante del producto</li>
                    <li>• <strong>Visible:</strong> El campo aparecerá en el formulario correspondiente</li>
                    <li>• <strong>Requerido:</strong> El campo será obligatorio (solo si está visible)</li>
                    <li>• Puedes configurar diferentes campos para cada sucursal o usar una configuración global</li>
                    <li>• Los presets te permiten configurar rápidamente según tu tipo de negocio</li>
                </ul>
            </div>
        </div>
    </div>
</div>

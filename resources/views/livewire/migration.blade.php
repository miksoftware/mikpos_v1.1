<div class="p-4 sm:p-6 lg:p-8" @if($isRunning) wire:poll.2s="pollStatus" @endif>
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900">Migración de Datos</h1>
        <p class="text-slate-500 mt-1">Importar datos desde el sistema anterior mediante archivo SQL</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left: Upload & File List -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Upload Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <h3 class="text-sm font-semibold text-slate-900 mb-4">Subir Archivo SQL</h3>

                <form wire:submit="uploadFile" class="space-y-4">
                    <div class="border-2 border-dashed border-slate-300 rounded-xl p-4 text-center hover:border-[#a855f7] transition-colors cursor-pointer"
                         x-data="{ dragging: false }"
                         x-on:dragover.prevent="dragging = true"
                         x-on:dragleave="dragging = false"
                         x-on:drop.prevent="dragging = false"
                         :class="dragging ? 'border-[#a855f7] bg-purple-50' : ''">
                        <input type="file" wire:model="sqlFile" accept=".sql" class="hidden" id="sqlFileInput">
                        <label for="sqlFileInput" class="cursor-pointer">
                            <svg class="w-8 h-8 text-slate-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                            </svg>
                            <p class="text-xs text-slate-500">Clic o arrastra un archivo .sql</p>
                            <p class="text-xs text-slate-400 mt-1">Máximo 100MB</p>
                        </label>
                    </div>

                    <div wire:loading wire:target="sqlFile" class="text-center">
                        <div class="flex items-center justify-center gap-2 text-sm text-[#a855f7]">
                            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Cargando archivo...
                        </div>
                    </div>

                    @if($sqlFile)
                    <div class="flex items-center gap-2 p-2 bg-green-50 border border-green-200 rounded-xl">
                        <svg class="w-4 h-4 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="text-xs text-green-700 truncate">{{ $sqlFile->getClientOriginalName() }}</span>
                    </div>
                    @endif

                    @error('sqlFile')
                    <p class="text-xs text-red-600">{{ $message }}</p>
                    @enderror

                    <button type="submit" @if(!$sqlFile) disabled @endif
                        class="w-full px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea] disabled:opacity-50 disabled:cursor-not-allowed transition-all">
                        <span wire:loading.remove wire:target="uploadFile">Subir Archivo</span>
                        <span wire:loading wire:target="uploadFile">Subiendo...</span>
                    </button>
                </form>
            </div>

            <!-- Uploaded Files -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-200">
                    <h3 class="text-sm font-semibold text-slate-900">Archivos Disponibles</h3>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse($uploadedFiles as $file)
                    <div class="px-4 py-3 flex items-center gap-3 {{ $selectedFile === $file['name'] ? 'bg-purple-50 border-l-4 border-[#a855f7]' : 'hover:bg-slate-50' }} transition-colors cursor-pointer"
                         wire:click="$set('selectedFile', '{{ $file['name'] }}')">
                        <div class="flex-shrink-0">
                            <svg class="w-5 h-5 {{ $selectedFile === $file['name'] ? 'text-[#a855f7]' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-slate-700 truncate">{{ $file['name'] }}</p>
                            <p class="text-xs text-slate-400">{{ $file['size'] }} MB &middot; {{ $file['date'] }}</p>
                        </div>
                        <button wire:click.stop="deleteFile('{{ $file['name'] }}')"
                            wire:confirm="¿Eliminar este archivo?"
                            class="p-1 text-slate-400 hover:text-red-500 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </div>
                    @empty
                    <div class="px-4 py-6 text-center">
                        <p class="text-sm text-slate-400">No hay archivos subidos</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Right: Migration Control & Output -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Migration Control -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <h3 class="text-sm font-semibold text-slate-900 mb-4">Ejecutar Migración</h3>

                @if($selectedFile)
                <div class="space-y-4">
                    <div class="p-3 bg-slate-50 rounded-xl">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-[#a855f7]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <div>
                                <p class="text-sm font-medium text-slate-700">{{ $selectedFile }}</p>
                                <p class="text-xs text-slate-400">Archivo seleccionado para migración</p>
                            </div>
                        </div>
                    </div>

                    <!-- Warning -->
                    <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl">
                        <div class="flex gap-2">
                            <svg class="w-4 h-4 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                            <div>
                                <p class="text-xs font-medium text-amber-800">Este proceso limpiará los datos existentes y los reemplazará con los del archivo SQL.</p>
                                <p class="text-xs text-amber-700 mt-1">Se recomienda hacer una copia de seguridad antes de continuar.</p>
                            </div>
                        </div>
                    </div>

                    @if(!$isRunning && !$isComplete && !$hasError)
                    <button wire:click="startMigration"
                        wire:confirm="¿Estás seguro? Esto limpiará los datos actuales e importará los del archivo SQL."
                        class="w-full px-4 py-3 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea] transition-all flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Iniciar Migración
                    </button>
                    @elseif(!$isRunning && ($isComplete || $hasError))
                    <button wire:click="startMigration"
                        wire:confirm="¿Re-ejecutar la migración? Esto limpiará todos los datos importados y los reemplazará con los del archivo SQL."
                        class="w-full px-4 py-3 text-sm font-medium text-white bg-gradient-to-r from-[#ff7261] to-[#a855f7] rounded-xl hover:from-[#e55a4a] hover:to-[#9333ea] transition-all flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Re-ejecutar Migración
                    </button>
                    @endif
                </div>
                @else
                <div class="text-center py-8">
                    <svg class="w-12 h-12 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                    </svg>
                    <p class="text-sm text-slate-400">Sube un archivo SQL y selecciónalo para iniciar</p>
                </div>
                @endif
            </div>

            <!-- Status & Output -->
            @if($isRunning || $isComplete || $hasError)
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-slate-900">Resultado de Migración</h3>
                    @if($isRunning)
                    <div class="flex items-center gap-2 text-sm text-[#a855f7]">
                        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Ejecutando...
                    </div>
                    @elseif($isComplete)
                    <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-green-700 bg-green-100 rounded-full">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Completado
                    </span>
                    @elseif($hasError)
                    <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-red-700 bg-red-100 rounded-full">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        Error
                    </span>
                    @endif
                </div>
                <div class="p-4">
                    <pre class="text-xs font-mono text-slate-700 bg-slate-900 text-green-400 p-4 rounded-xl overflow-x-auto max-h-[500px] overflow-y-auto whitespace-pre-wrap">{{ $output ?: 'Esperando salida...' }}</pre>
                </div>

                <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
                    @if($isRunning)
                    <button wire:click="cancelMigration"
                        wire:confirm="¿Cancelar la migración en curso?"
                        class="px-4 py-2 text-sm font-medium text-red-700 bg-white border border-red-300 rounded-xl hover:bg-red-50">
                        Cancelar
                    </button>
                    @else
                    <button wire:click="resetMigration" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">
                        Cerrar
                    </button>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

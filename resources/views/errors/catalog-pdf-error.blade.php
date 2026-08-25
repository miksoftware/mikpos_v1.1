<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error al Generar Catálogo PDF - MikPOS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex items-center justify-center p-4 sm:p-6">
    <div class="max-w-3xl w-full bg-slate-900 border border-slate-800 rounded-3xl shadow-2xl overflow-hidden p-6 sm:p-8">
        
        <!-- Header -->
        <div class="flex items-start gap-4 mb-6">
            <div class="w-12 h-12 rounded-2xl bg-rose-500/10 border border-rose-500/20 flex items-center justify-center text-rose-400 flex-shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20">Error 500</span>
                    <span class="text-xs text-slate-400 font-medium">Diagnóstico de Servidor</span>
                </div>
                <h1 class="text-xl sm:text-2xl font-bold text-white mt-1">Error al Generar Catálogo PDF</h1>
                <p class="text-sm text-slate-400 mt-1">El servidor encontró una dificultad técnica al intentar procesar y renderizar el documento PDF.</p>
            </div>
        </div>

        <!-- Exception Message Box -->
        <div class="bg-rose-950/30 border border-rose-900/50 rounded-2xl p-4 mb-6">
            <div class="text-xs font-semibold text-rose-400 uppercase tracking-wider mb-1">Mensaje del Error</div>
            <div class="text-sm font-mono text-rose-200 break-words">{{ $exception->getMessage() ?: 'Error desconocido durante la generación del PDF' }}</div>
            <div class="text-xs text-rose-300/70 mt-2 font-mono">
                {{ class_basename($exception) }} en <span class="text-slate-300">{{ $exception->getFile() }}:{{ $exception->getLine() }}</span>
            </div>
        </div>

        <!-- Diagnostics Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
            <div class="bg-slate-800/60 border border-slate-700/50 rounded-xl p-3">
                <div class="text-xs text-slate-400">Versión PHP</div>
                <div class="text-sm font-bold text-slate-200 mt-0.5">{{ $diagnostics['php_version'] ?? PHP_VERSION }}</div>
            </div>
            <div class="bg-slate-800/60 border border-slate-700/50 rounded-xl p-3">
                <div class="text-xs text-slate-400">Límite Memoria</div>
                <div class="text-sm font-bold text-slate-200 mt-0.5">{{ $diagnostics['memory_limit'] ?? ini_get('memory_limit') }}</div>
            </div>
            <div class="bg-slate-800/60 border border-slate-700/50 rounded-xl p-3">
                <div class="text-xs text-slate-400">Tiempo Máximo</div>
                <div class="text-sm font-bold text-slate-200 mt-0.5">{{ $diagnostics['max_execution_time'] ?? ini_get('max_execution_time') }}s</div>
            </div>
            <div class="bg-slate-800/60 border border-slate-700/50 rounded-xl p-3">
                <div class="text-xs text-slate-400">Permiso Fuentes</div>
                <div class="text-sm font-bold mt-0.5 {{ !empty($diagnostics['storage_fonts_writable']) ? 'text-emerald-400' : 'text-amber-400' }}">
                    {{ !empty($diagnostics['storage_fonts_writable']) ? '✓ Escribible' : '⚠ Revisar' }}
                </div>
            </div>
        </div>

        <!-- Stack Trace Collapsible -->
        <details class="group bg-slate-950/60 border border-slate-800 rounded-2xl mb-6">
            <summary class="flex items-center justify-between p-4 cursor-pointer text-xs font-semibold text-slate-300 hover:text-white select-none">
                <span>Ver Traza Completa del Error (Stack Trace)</span>
                <svg class="w-4 h-4 transition-transform group-open:rotate-180 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </summary>
            <div class="px-4 pb-4">
                <pre id="stack-trace-content" class="text-[11px] font-mono text-slate-400 bg-slate-900/90 p-3 rounded-xl overflow-x-auto max-h-64 border border-slate-800/80 whitespace-pre-wrap leading-relaxed">{{ $exception->getTraceAsString() }}</pre>
            </div>
        </details>

        <!-- Actions -->
        <div class="flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-slate-800">
            <div class="flex items-center gap-2">
                <a href="{{ url()->previous() ?: route('ecommerce-orders.index') }}" 
                    class="px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold rounded-xl transition-all inline-flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span>Regresar</span>
                </a>
                <button onclick="location.reload()" 
                    class="px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold rounded-xl transition-all inline-flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <span>Reintentar</span>
                </button>
            </div>

            <button id="copy-btn" onclick="copyErrorDetails()"
                class="px-4 py-2.5 bg-purple-600 hover:bg-purple-500 text-white text-xs font-semibold rounded-xl transition-all shadow-sm inline-flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                </svg>
                <span id="copy-btn-text">Copiar Diagnóstico</span>
            </button>
        </div>
    </div>

    <script>
        function copyErrorDetails() {
            const data = {
                error_class: "{{ addslashes(get_class($exception)) }}",
                message: "{{ addslashes($exception->getMessage()) }}",
                file: "{{ addslashes($exception->getFile()) }}:{{ $exception->getLine() }}",
                diagnostics: @json($diagnostics ?? []),
                trace: document.getElementById('stack-trace-content').innerText
            };

            const text = "=== ERROR GENERANDO CATÁLOGO PDF ===\n" +
                "Clase: " + data.error_class + "\n" +
                "Mensaje: " + data.message + "\n" +
                "Archivo: " + data.file + "\n\n" +
                "=== DIAGNÓSTICO DEL SERVIDOR ===\n" +
                JSON.stringify(data.diagnostics, null, 2) + "\n\n" +
                "=== STACK TRACE ===\n" +
                data.trace;

            navigator.clipboard.writeText(text).then(() => {
                const btnText = document.getElementById('copy-btn-text');
                const old = btnText.innerText;
                btnText.innerText = "¡Copiado al Portapapeles!";
                setTimeout(() => { btnText.innerText = old; }, 2500);
            });
        }
    </script>
</body>
</html>

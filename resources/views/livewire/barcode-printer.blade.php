<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Impresión de Códigos de Barras</h1>
            <p class="text-slate-500 mt-1">Genera y personaliza etiquetas para tus productos</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('products') }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-semibold rounded-xl shadow-sm hover:shadow transition-all duration-200">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Volver a Productos
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left Column: Search and Selection --}}
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4">Buscar Productos</h3>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                    <input wire:model.live.debounce.300ms="search" type="text" class="block w-full pl-10 pr-3 py-2.5 border border-slate-200 rounded-xl bg-slate-50 placeholder-slate-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-[#ff7261]/50 focus:border-[#ff7261] transition-all sm:text-sm" placeholder="Nombre, SKU o código...">
                </div>

                {{-- Search Results --}}
                @if(count($searchResults) > 0)
                <div class="mt-4 border border-slate-100 rounded-xl overflow-hidden divide-y divide-slate-100 shadow-sm">
                    @foreach($searchResults as $result)
                    <button wire:click="addToPrintList({{ json_encode($result) }})" class="w-full flex items-center gap-3 p-3 text-left hover:bg-slate-50 transition-colors group">
                        <div class="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center group-hover:bg-white transition-colors">
                            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-slate-800 truncate">{{ $result['name'] }}</p>
                            <p class="text-xs text-slate-500 truncate">SKU: {{ $result['sku'] ?? '-' }} | Barcode: {{ $result['barcode'] }}</p>
                        </div>
                        <svg class="w-5 h-5 text-slate-300 group-hover:text-[#ff7261] transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    </button>
                    @endforeach
                </div>
                @elseif(strlen($search) >= 2)
                <div class="mt-4 p-4 text-center text-slate-500 bg-slate-50 rounded-xl border border-dashed border-slate-200">
                    No se encontraron resultados
                </div>
                @endif
            </div>

            <div class="bg-amber-50 border border-amber-200 rounded-2xl p-6">
                <div class="flex items-start gap-3">
                    <svg class="w-6 h-6 text-amber-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <div>
                        <h4 class="text-sm font-bold text-amber-800 mb-1">Información de Impresión</h4>
                        <p class="text-xs text-amber-700 leading-relaxed">
                            Asegúrate de configurar tu impresora térmica con el tamaño de papel correcto (aprox. 30x15mm por etiqueta). Las etiquetas incluirán el nombre, el precio y el código de barras.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Column: Print List --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden flex flex-col h-full min-h-[500px]">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <h3 class="text-lg font-bold text-slate-800">Cola de Impresión</h3>
                    @if(count($printList) > 0)
                    <button wire:click="clearList" class="text-sm text-red-500 hover:text-red-700 font-medium">Vaciar lista</button>
                    @endif
                </div>

                <div class="flex-1 overflow-y-auto p-6">
                    @if(count($printList) > 0)
                    <div class="space-y-3">
                        @foreach($printList as $id => $item)
                        <div class="flex items-center gap-4 p-4 bg-white border border-slate-100 rounded-2xl shadow-sm hover:shadow-md transition-shadow">
                            <div class="flex-1 min-w-0">
                                <h4 class="text-sm font-bold text-slate-800 truncate">{{ $item['name'] }}</h4>
                                <div class="flex items-center gap-3 mt-1">
                                    <span class="text-xs font-mono text-slate-500">Barcode: {{ $item['barcode'] }}</span>
                                    <span class="text-xs font-bold text-[#ff7261]">${{ number_format($item['price'], 2) }}</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="flex items-center border border-slate-200 rounded-xl overflow-hidden bg-slate-50">
                                    <button wire:click="updateQuantity('{{ $id }}', {{ $item['quantity'] - 1 }})" class="px-3 py-1.5 hover:bg-slate-200 text-slate-600 transition-colors">-</button>
                                    <input type="number" wire:change="updateQuantity('{{ $id }}', $event.target.value)" value="{{ $item['quantity'] }}" class="w-16 text-center bg-transparent border-none focus:ring-0 text-sm font-bold text-slate-800">
                                    <button wire:click="updateQuantity('{{ $id }}', {{ $item['quantity'] + 1 }})" class="px-3 py-1.5 hover:bg-slate-200 text-slate-600 transition-colors">+</button>
                                </div>
                                <button wire:click="removeFromPrintList('{{ $id }}')" class="p-2 text-slate-400 hover:text-red-500 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="flex flex-col items-center justify-center h-full py-12 text-slate-400">
                        <div class="w-20 h-20 rounded-full bg-slate-50 flex items-center justify-center mb-4">
                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                        </div>
                        <p class="text-lg font-medium">La cola de impresión está vacía</p>
                        <p class="text-sm">Busca productos a la izquierda para agregarlos aquí</p>
                    </div>
                    @endif
                </div>

                <div class="px-6 py-6 bg-slate-50 border-t border-slate-100">
                    <div class="flex items-center justify-between mb-4">
                        <div class="text-sm text-slate-600">
                            Total de etiquetas: <span class="font-bold text-slate-900">{{ collect($printList)->sum('quantity') }}</span>
                        </div>
                    </div>
                    <button 
                        wire:click="print" 
                        {{ empty($printList) ? 'disabled' : '' }}
                        class="w-full flex items-center justify-center gap-2 px-6 py-4 bg-gradient-to-r from-[#ff7261] to-[#a855f7] hover:from-[#e55a4a] hover:to-[#9333ea] text-white font-bold rounded-2xl shadow-lg hover:shadow-xl transform hover:scale-[1.01] transition-all duration-200 disabled:opacity-50 disabled:scale-100 disabled:shadow-none"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                        IMPRIMIR ETIQUETAS
                    </button>
                </div>
            </div>
        </div>
    </div>

    @script
    <script>
        $wire.on('open-print-window', (event) => {
            const printWindow = window.open(
                event.url,
                'barcode_printer',
                'width=800,height=600,scrollbars=yes,resizable=yes'
            );
            
            if (printWindow) {
                printWindow.focus();
            }
        });
    </script>
    @endscript
</div>

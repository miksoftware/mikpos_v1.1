<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Formatos de Impresión</h1>
        <p class="text-slate-500 mt-1">Configura el formato de impresión para cada tipo de documento</p>
    </div>

    @foreach($settings as $setting)
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200">
            <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                <svg class="w-5 h-5 text-[#a855f7]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                </svg>
                {{ $setting->display_name }}
            </h3>
        </div>

        <div class="px-6 py-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- 80mm Format Card -->
                <div class="relative">
                    <button
                        wire:click="selectFormat('{{ $setting->document_type }}', '80mm')"
                        class="w-full text-left p-5 rounded-xl border-2 transition-all duration-200 {{ ($formats[$setting->document_type] ?? '80mm') === '80mm' ? 'border-[#ff7261] bg-gradient-to-br from-[#ff7261]/5 to-[#a855f7]/5 shadow-md' : 'border-slate-200 hover:border-[#ff7261]/50 hover:shadow-sm' }}">
                        @if(($formats[$setting->document_type] ?? '80mm') === '80mm')
                        <div class="absolute top-3 right-3">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-gradient-to-r from-[#ff7261] to-[#a855f7] text-white">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                Activo
                            </span>
                        </div>
                        @endif
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-20 h-28 bg-white border border-slate-300 rounded shadow-sm p-1.5 text-[5px] leading-tight font-mono overflow-hidden">
                                <div class="text-center border-b border-dashed border-slate-300 pb-1 mb-1">
                                    <div class="font-bold text-[6px]">MI NEGOCIO</div>
                                    <div class="text-[4px] text-slate-400">NIT: 000000</div>
                                </div>
                                <div class="text-center border-b border-dashed border-slate-300 pb-1 mb-1">
                                    <div class="text-[4px] bg-black text-white inline-block px-1">POS</div>
                                    <div class="font-bold text-[5px]">POS-001</div>
                                </div>
                                <div class="mb-1">
                                    <div class="text-[4px] font-bold">Producto 1</div>
                                    <div class="flex justify-between text-[4px]"><span>2 x $10,000</span><span>$20,000</span></div>
                                </div>
                                <div class="border-t border-slate-300 pt-0.5">
                                    <div class="flex justify-between font-bold text-[5px]"><span>TOTAL</span><span>$20,000</span></div>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="font-bold text-slate-900 text-base">Tirilla 80mm</h4>
                                <p class="text-sm text-slate-500 mt-1">Formato térmico estándar para impresoras POS de 80mm.</p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600">72mm ancho</span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600">Courier New</span>
                                </div>
                            </div>
                        </div>
                    </button>
                    <button wire:click="showPreview('{{ $setting->document_type }}', '80mm')" class="mt-2 w-full text-center text-sm text-[#a855f7] hover:text-[#9333ea] font-medium py-1.5 rounded-lg hover:bg-[#a855f7]/5 transition-colors">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                        Vista previa
                    </button>
                </div>

                <!-- Letter Format Card -->
                <div class="relative">
                    <button
                        wire:click="selectFormat('{{ $setting->document_type }}', 'letter')"
                        class="w-full text-left p-5 rounded-xl border-2 transition-all duration-200 {{ ($formats[$setting->document_type] ?? '80mm') === 'letter' ? 'border-[#ff7261] bg-gradient-to-br from-[#ff7261]/5 to-[#a855f7]/5 shadow-md' : 'border-slate-200 hover:border-[#ff7261]/50 hover:shadow-sm' }}">
                        @if(($formats[$setting->document_type] ?? '80mm') === 'letter')
                        <div class="absolute top-3 right-3">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-gradient-to-r from-[#ff7261] to-[#a855f7] text-white">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                Activo
                            </span>
                        </div>
                        @endif
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-20 h-28 bg-white border border-slate-300 rounded shadow-sm p-2 text-[4px] leading-tight font-sans overflow-hidden">
                                <div class="flex justify-between items-start mb-1">
                                    <div class="font-bold text-[7px]">FACTURA</div>
                                    <div class="text-right">
                                        <div class="text-[4px] text-blue-600 font-bold">F001-0001</div>
                                        <div class="text-[3px] text-slate-400">12/11/2025</div>
                                    </div>
                                </div>
                                <div class="flex gap-1 mb-1 text-[3px]">
                                    <div class="flex-1"><span class="font-bold">Negocio</span><br>Mi Negocio</div>
                                    <div class="flex-1"><span class="font-bold">Cliente</span><br>Juan Pérez</div>
                                    <div class="flex-1"><span class="font-bold">Venta</span><br>POS</div>
                                </div>
                                <table class="w-full mb-0.5" style="border-collapse: collapse;">
                                    <tr><th class="text-left bg-slate-100 px-0.5 text-[3px]">#</th><th class="text-left bg-slate-100 px-0.5 text-[3px]">Desc</th><th class="text-right bg-slate-100 px-0.5 text-[3px]">Total</th></tr>
                                    <tr><td class="px-0.5 text-[3px]">1</td><td class="px-0.5 text-[3px]">Producto</td><td class="text-right px-0.5 text-[3px]">$20,000</td></tr>
                                </table>
                                <div class="text-right font-bold text-[5px] text-blue-600">TOTAL: $20,000</div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="font-bold text-slate-900 text-base">Tamaño Carta</h4>
                                <p class="text-sm text-slate-500 mt-1">Formato factura tamaño carta. Ideal para impresoras láser.</p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600">8.5" x 11"</span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600">Arial</span>
                                </div>
                            </div>
                        </div>
                    </button>
                    <button wire:click="showPreview('{{ $setting->document_type }}', 'letter')" class="mt-2 w-full text-center text-sm text-[#a855f7] hover:text-[#9333ea] font-medium py-1.5 rounded-lg hover:bg-[#a855f7]/5 transition-colors">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                        Vista previa
                    </button>
                </div>
            </div>

            <!-- Letter Options (only when letter is selected) -->
            @if(($formats[$setting->document_type] ?? '80mm') === 'letter')
            <div class="mt-6 pt-6 border-t border-slate-200">
                <h4 class="text-sm font-bold text-slate-700 mb-4 flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#a855f7]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
                    Secciones visibles en formato carta
                </h4>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    @php
                        $optionsList = [
                            'show_business' => ['label' => 'Datos del negocio', 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
                            'show_customer' => ['label' => 'Datos del cliente', 'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                            'show_sale_info' => ['label' => 'Info. de venta', 'icon' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                            'show_payment_info' => ['label' => 'Info. de pago', 'icon' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z'],
                            'show_amount_words' => ['label' => 'Monto en letras', 'icon' => 'M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129'],
                            'show_footer' => ['label' => 'Pie de página', 'icon' => 'M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z'],
                        ];
                        $currentOptions = $letterOptions[$setting->document_type] ?? \App\Models\PrintFormatSetting::DEFAULT_LETTER_OPTIONS;
                    @endphp
                    @foreach($optionsList as $key => $opt)
                    <button
                        wire:click="toggleLetterOption('{{ $setting->document_type }}', '{{ $key }}')"
                        class="flex items-center gap-3 p-3 rounded-xl border transition-all duration-200 text-left {{ ($currentOptions[$key] ?? true) ? 'border-green-300 bg-green-50' : 'border-slate-200 bg-slate-50 opacity-60' }}">
                        <div class="flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center {{ ($currentOptions[$key] ?? true) ? 'bg-green-100' : 'bg-slate-200' }}">
                            <svg class="w-4 h-4 {{ ($currentOptions[$key] ?? true) ? 'text-green-600' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $opt['icon'] }}"></path></svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <span class="text-sm font-medium {{ ($currentOptions[$key] ?? true) ? 'text-green-800' : 'text-slate-500' }}">{{ $opt['label'] }}</span>
                        </div>
                        <div class="flex-shrink-0">
                            @if($currentOptions[$key] ?? true)
                            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            @else
                            <svg class="w-5 h-5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            @endif
                        </div>
                    </button>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
    @endforeach

    <!-- Preview Modal -->
    @if($previewFormat)
    <div class="relative z-[100]" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm z-[100]" wire:click="closePreview"></div>
        <div class="fixed inset-0 z-[101] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full {{ $previewFormat === 'letter' ? 'max-w-3xl' : 'max-w-sm' }} bg-white rounded-2xl shadow-xl">
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-slate-900">
                            Vista Previa - {{ $previewFormat === '80mm' ? 'Tirilla 80mm' : 'Tamaño Carta' }}
                        </h3>
                        <button wire:click="closePreview" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                    <div class="p-6 overflow-y-auto max-h-[75vh] bg-slate-100 flex justify-center">
                        @if($previewFormat === '80mm')
                        <!-- 80mm Preview -->
                        <div style="width: 300px; font-family: 'Courier New', monospace; font-size: 11px; line-height: 1.4; background: #fff; padding: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                            <div style="text-align: center; padding-bottom: 8px; border-bottom: 1px dashed #000; margin-bottom: 8px;">
                                <div style="font-size: 16px; font-weight: bold; text-transform: uppercase;">DROGUERÍA EL PUNTO DE TU SALUD</div>
                                <div style="font-size: 10px;"><p>NIT: 700128834</p><p>CR 28E 122-14</p><p>Tel: 3016 966163</p></div>
                            </div>
                            <div style="text-align: center; padding: 6px 0; border-bottom: 1px dashed #000; margin-bottom: 6px;">
                                <span style="display: inline-block; padding: 1px 6px; background: #000; color: #fff; font-size: 9px; font-weight: bold;">DOCUMENTO POS</span>
                                <div style="font-size: 13px; font-weight: bold;">POS-20260224-0001</div>
                                <div style="font-size: 10px;">24/02/2026 - 10:30:00</div>
                            </div>
                            <div style="padding: 6px 0; border-bottom: 1px dashed #000; margin-bottom: 6px;">
                                <div style="font-size: 10px; font-weight: bold;">CLIENTE</div>
                                <div style="font-weight: bold;">Consumidor Final</div>
                            </div>
                            <div style="margin-bottom: 6px;">
                                <div style="font-size: 10px; font-weight: bold;">DETALLE</div>
                                <div style="display: flex; justify-content: space-between; font-size: 9px; font-weight: bold; padding-bottom: 3px; border-bottom: 1px solid #000; margin-bottom: 4px;"><span>Producto</span><span>Total</span></div>
                                <div style="margin-bottom: 4px; padding-bottom: 3px; border-bottom: 1px dotted #999;">
                                    <div style="font-weight: bold; font-size: 10px;">BEVEDOL EXTRA FUERTE X60</div>
                                    <div style="display: flex; justify-content: space-between; font-size: 10px;"><span>1 x $63,900</span><span style="font-weight: bold;">$63,900</span></div>
                                </div>
                                <div style="margin-bottom: 4px;">
                                    <div style="font-weight: bold; font-size: 10px;">MIXYTELY SOBRE UNIDAD</div>
                                    <div style="display: flex; justify-content: space-between; font-size: 10px;"><span>4 x $12,000</span><span style="font-weight: bold;">$48,000</span></div>
                                </div>
                            </div>
                            <div style="border-top: 1px solid #000; padding-top: 6px;">
                                <div style="display: flex; justify-content: space-between; font-size: 11px; margin-bottom: 2px;"><span>Subtotal</span><span>$111,900</span></div>
                                <div style="display: flex; justify-content: space-between; font-size: 15px; font-weight: bold; padding-top: 4px; border-top: 1px dashed #000; margin-top: 4px;"><span>TOTAL</span><span>$111,900</span></div>
                            </div>
                            <div style="text-align: center; padding-top: 8px; border-top: 1px dashed #000; margin-top: 6px;">
                                <div style="font-size: 14px; font-weight: bold;">¡Gracias por su compra!</div>
                            </div>
                        </div>
                        @else
                        <!-- Letter Preview -->
                        @php $opts = $letterOptions[$previewDocumentType] ?? \App\Models\PrintFormatSetting::DEFAULT_LETTER_OPTIONS; @endphp
                        <div style="width: 100%; max-width: 680px; font-family: Arial, sans-serif; font-size: 12px; line-height: 1.5; background: #fff; padding: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                            <!-- Header -->
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 12px; border-bottom: 2px solid #333; margin-bottom: 12px;">
                                <div style="font-size: 28px; font-weight: bold; letter-spacing: 2px;">FACTURA</div>
                                <div style="text-align: right;">
                                    <div style="font-size: 14px; font-weight: bold; color: #0066cc;">F0001-00000545</div>
                                    <div style="font-size: 11px; color: #555;">24/02/2026</div>
                                </div>
                            </div>
                            <!-- 3-column info row -->
                            <div style="display: flex; gap: 20px; margin-bottom: 15px;">
                                @if($opts['show_business'] ?? true)
                                <div style="flex: 1;">
                                    <div style="font-size: 10px; color: #777; text-transform: uppercase; font-weight: bold; border-bottom: 1px solid #eee; padding-bottom: 2px; margin-bottom: 4px;">Negocio</div>
                                    <div style="font-size: 11px; color: #333; line-height: 1.6;">
                                        <strong>DROGUERÍA EL PUNTO DE TU SALUD</strong><br>
                                        NIT: 700128834<br>
                                        CR 28E 122-14, Tercer Milenio<br>
                                        Tel: 3016 966163
                                    </div>
                                </div>
                                @endif
                                @if($opts['show_customer'] ?? true)
                                <div style="flex: 1;">
                                    <div style="font-size: 10px; color: #777; text-transform: uppercase; font-weight: bold; border-bottom: 1px solid #eee; padding-bottom: 2px; margin-bottom: 4px;">Cliente</div>
                                    <div style="font-size: 11px; color: #333; line-height: 1.6;">
                                        <strong>ANGIE KARINA GOMEZ</strong><br>
                                        CC: 1006208593<br>
                                        Tel: 3154 083002<br>
                                        Cra 15 #45-20, Cúcuta<br>
                                        angiek@gmail.com
                                    </div>
                                </div>
                                @endif
                                @if($opts['show_sale_info'] ?? true)
                                <div style="flex: 1;">
                                    <div style="font-size: 10px; color: #777; text-transform: uppercase; font-weight: bold; border-bottom: 1px solid #eee; padding-bottom: 2px; margin-bottom: 4px;">Información de Venta</div>
                                    <div style="font-size: 11px; color: #333; line-height: 1.6;">
                                        <strong>Tipo:</strong> Documento POS<br>
                                        <strong>Fecha:</strong> 24/02/2026 11:21<br>
                                        <strong>Vendedor:</strong> Admin
                                    </div>
                                </div>
                                @endif
                            </div>
                            <!-- Items Table -->
                            <table style="width: 100%; border-collapse: collapse; margin-bottom: 15px;">
                                <thead>
                                    <tr>
                                        <th style="background: #f0f0f0; border: 1px solid #ccc; padding: 6px 8px; font-size: 10px; font-weight: bold; text-align: center; width: 30px;">#</th>
                                        <th style="background: #f0f0f0; border: 1px solid #ccc; padding: 6px 8px; font-size: 10px; font-weight: bold; text-align: left;">DESCRIPCIÓN</th>
                                        <th style="background: #f0f0f0; border: 1px solid #ccc; padding: 6px 8px; font-size: 10px; font-weight: bold; text-align: center; width: 70px;">CANT.</th>
                                        <th style="background: #f0f0f0; border: 1px solid #ccc; padding: 6px 8px; font-size: 10px; font-weight: bold; text-align: right; width: 90px;">PRECIO</th>
                                        <th style="background: #f0f0f0; border: 1px solid #ccc; padding: 6px 8px; font-size: 10px; font-weight: bold; text-align: right; width: 100px;">TOTAL</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td style="border: 1px solid #ddd; padding: 6px 8px; font-size: 11px; text-align: center;">1</td>
                                        <td style="border: 1px solid #ddd; padding: 6px 8px; font-size: 11px;">BEVEDOL EXTRA FUERTE X60 ABBOTT</td>
                                        <td style="border: 1px solid #ddd; padding: 6px 8px; font-size: 11px; text-align: center;">1</td>
                                        <td style="border: 1px solid #ddd; padding: 6px 8px; font-size: 11px; text-align: right;">$63,900.00</td>
                                        <td style="border: 1px solid #ddd; padding: 6px 8px; font-size: 11px; text-align: right;">$63,900.00</td>
                                    </tr>
                                    <tr style="background: #fafafa;">
                                        <td style="border: 1px solid #ddd; padding: 6px 8px; font-size: 11px; text-align: center;">2</td>
                                        <td style="border: 1px solid #ddd; padding: 6px 8px; font-size: 11px;">MIXYTELY SOBRE UNIDAD LOCOFARMA</td>
                                        <td style="border: 1px solid #ddd; padding: 6px 8px; font-size: 11px; text-align: center;">4</td>
                                        <td style="border: 1px solid #ddd; padding: 6px 8px; font-size: 11px; text-align: right;">$12,000.00</td>
                                        <td style="border: 1px solid #ddd; padding: 6px 8px; font-size: 11px; text-align: right;">$48,000.00</td>
                                    </tr>
                                </tbody>
                            </table>
                            <!-- Totals -->
                            <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                                @if($opts['show_payment_info'] ?? true)
                                <div style="font-size: 11px; color: #444; line-height: 1.8;">
                                    <strong>Condición de pago:</strong> CONTADO<br>
                                    <strong>Emitida:</strong> 24/02/2026 11:21:00<br>
                                    <strong>Forma de pago:</strong> Efectivo: $111,900.00
                                </div>
                                @else
                                <div></div>
                                @endif
                                <div style="width: 260px; background: #f8f8f8; border: 1px solid #ddd; padding: 10px 15px;">
                                    <div style="display: flex; justify-content: space-between; font-size: 11px; padding: 3px 0; color: #444;"><span>Subtotal:</span><span>$111,900.00</span></div>
                                    <div style="display: flex; justify-content: space-between; font-size: 16px; font-weight: bold; color: #0066cc; border-top: 2px solid #333; margin-top: 6px; padding-top: 8px;"><span>TOTAL:</span><span>$111,900.00</span></div>
                                </div>
                            </div>
                            @if($opts['show_amount_words'] ?? true)
                            <div style="font-size: 10px; color: #555; font-style: italic; margin-bottom: 12px; padding: 6px 10px; background: #f8f8f8; border-left: 3px solid #ccc;">
                                Monto en letras: CIENTO ONCE MIL NOVECIENTOS CON 00/100
                            </div>
                            @endif
                            @if($opts['show_footer'] ?? true)
                            <div style="text-align: center; padding-top: 12px; border-top: 1px solid #ddd; font-size: 11px; color: #777;">
                                <div style="font-size: 14px; font-weight: bold; color: #333;">Gracias por su preferencia</div>
                            </div>
                            @endif
                        </div>
                        @endif
                    </div>
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end">
                        <button wire:click="closePreview" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

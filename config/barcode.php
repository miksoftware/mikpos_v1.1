<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Etiqueta de código de barras (SAT TT448 / ZPL)
    |--------------------------------------------------------------------------
    */

    'label_width_mm' => (int) env('BARCODE_LABEL_WIDTH_MM', 33),
    'label_height_mm' => (int) env('BARCODE_LABEL_HEIGHT_MM', 22),
    'dpi' => (int) env('BARCODE_PRINTER_DPI', 203),

    /*
    | Impresora por red (Ethernet). Ej: 192.168.1.50
    | Puerto RAW estándar: 9100
    */
    'printer_host' => env('BARCODE_PRINTER_HOST'),
    'printer_port' => (int) env('BARCODE_PRINTER_PORT', 9100),

    /*
    | Impresora USB en Windows (nombre exacto en Panel de control).
    | Ej: SAT TT448-2 USE (ZPL)
    */
    'printer_windows_name' => env('BARCODE_PRINTER_WINDOWS_NAME', 'SAT TT448-2 USE (ZPL)'),

    /*
    | Agente local (tools/barcode-print-agent.php) para imprimir desde
    | navegador cuando la app está en la nube pero la impresora es USB local.
    */
    'local_agent_url' => env('BARCODE_LOCAL_AGENT_URL', 'http://127.0.0.1:9311'),

];

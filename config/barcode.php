<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Etiqueta de código de barras
    |--------------------------------------------------------------------------
    |
    | Dimensiones de cada etiqueta física (ancho x alto en milímetros).
    | Ajusta estos valores según el rollo o pliego que uses en tu impresora.
    |
    */
    'label_width_mm' => (int) env('BARCODE_LABEL_WIDTH_MM', 33),
    'label_height_mm' => (int) env('BARCODE_LABEL_HEIGHT_MM', 22),

];

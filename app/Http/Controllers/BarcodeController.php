<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BarcodeController extends Controller
{
    public function print(Request $request)
    {
        $data = session()->get('barcode_print_data');

        if (!$data) {
            return "No hay datos para imprimir. Por favor, regresa y selecciona productos.";
        }

        return view('barcodes.print', compact('data'));
    }
}

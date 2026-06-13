<?php

namespace App\Http\Controllers;

use App\Services\BarcodeLabelPrinter;
use App\Services\BarcodeZplService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BarcodeController extends Controller
{
    public function print(Request $request, BarcodeZplService $zplService)
    {
        $data = session()->get('barcode_print_data');

        if (! $data) {
            return 'No hay datos para imprimir. Por favor, regresa y selecciona productos.';
        }

        $zpl = $zplService->generate($data);
        $autoPrint = $request->boolean('print');

        return view('barcodes.print', [
            'data' => $data,
            'zpl' => $zpl,
            'autoPrint' => $autoPrint,
            'previewUrl' => $this->labelaryPreviewUrl($zplService, $zpl),
            'labelCount' => collect($data)->sum('quantity'),
        ]);
    }

    public function zpl(BarcodeZplService $zplService): Response
    {
        $data = session()->get('barcode_print_data');

        if (! $data) {
            return response()->json(['success' => false, 'message' => 'Sin datos'], 422);
        }

        $zpl = $zplService->generate($data);

        return response()->json([
            'success' => true,
            'zpl' => $zpl,
            'count' => collect($data)->sum('quantity'),
            'preview_url' => $this->labelaryPreviewUrl($zplService, $zpl),
        ]);
    }

    public function send(Request $request, BarcodeZplService $zplService, BarcodeLabelPrinter $printer): Response
    {
        $data = session()->get('barcode_print_data');

        if (! $data) {
            return response()->json(['success' => false, 'message' => 'Sin datos'], 422);
        }

        $zpl = $request->string('zpl')->toString() ?: $zplService->generate($data);

        return response()->json($printer->send($zpl));
    }

    public function download(BarcodeZplService $zplService): Response
    {
        $data = session()->get('barcode_print_data');

        if (! $data) {
            return response('Sin datos', 422);
        }

        $zpl = $zplService->generate($data);

        return response($zpl, 200, [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="etiquetas.zpl"',
        ]);
    }

    private function labelaryPreviewUrl(BarcodeZplService $zplService, string $zpl): string
    {
        $width = $zplService->labelWidthInches();
        $height = $zplService->labelHeightInches();
        $firstLabel = $this->firstLabelZpl($zpl);

        return 'https://api.labelary.com/v1/printers/8dpmm/labels/'
            . $width . 'x' . $height . '/0/'
            . rawurlencode($firstLabel);
    }

    private function firstLabelZpl(string $zpl): string
    {
        if (preg_match('/\^XA[\s\S]*?\^XZ/', $zpl, $matches)) {
            return $matches[0];
        }

        return $zpl;
    }
}

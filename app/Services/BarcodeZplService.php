<?php

namespace App\Services;

class BarcodeZplService
{
    private int $dpi;

    private int $labelWidthMm;

    private int $labelHeightMm;

    public function __construct()
    {
        $this->dpi = (int) config('barcode.dpi', 203);
        $this->labelWidthMm = (int) config('barcode.label_width_mm', 33);
        $this->labelHeightMm = (int) config('barcode.label_height_mm', 22);
    }

    public function mmToDots(float $mm): int
    {
        return (int) round($mm * $this->dpi / 25.4);
    }

    public function generate(array $items): string
    {
        $zpl = '';

        foreach ($items as $item) {
            $quantity = max(1, (int) ($item['quantity'] ?? 1));

            for ($i = 0; $i < $quantity; $i++) {
                $zpl .= $this->generateLabel(
                    (string) ($item['barcode'] ?? ''),
                    (float) ($item['price'] ?? 0),
                );
            }
        }

        return $zpl;
    }

    public function generateLabel(string $barcode, float $price): string
    {
        $barcode = $this->sanitize($barcode);
        $priceText = $this->sanitize('$' . number_format($price, 0, '', ''));

        $width = $this->mmToDots($this->labelWidthMm);
        $height = $this->mmToDots($this->labelHeightMm);
        $marginX = $this->mmToDots(1.5);
        $contentWidth = max(1, $width - ($marginX * 2));
        $moduleWidth = $this->recommendedModuleWidth($barcode);
        $barcodeWidth = min($contentWidth, $this->estimatedCode128WidthDots($barcode, $moduleWidth));
        $barcodeX = max($marginX, (int) floor(($width - $barcodeWidth) / 2));

        $priceY = $this->mmToDots(1.2);
        $barcodeY = $this->mmToDots(5.6);
        $barcodeHeight = $this->mmToDots(8.7);
        $barcodeTextY = $barcodeY + $barcodeHeight + $this->mmToDots(0.6);

        $priceFont = max(20, $this->mmToDots(3.2));
        $barcodeTextFont = max(16, $this->mmToDots(2.2));

        return implode("\n", [
            '^XA',
            '^CI28',
            "^PW{$width}",
            "^LL{$height}",
            '^LH0,0',
            '^LT0',
            '^MD15',
            "^FO{$marginX},{$priceY}^A0N,{$priceFont},{$priceFont}^FB{$contentWidth},1,0,C,0^FD{$priceText}^FS",
            "^FO{$barcodeX},{$barcodeY}^BY{$moduleWidth},2,{$barcodeHeight}^BCN,{$barcodeHeight},N,N,N^FD{$barcode}^FS",
            "^FO{$marginX},{$barcodeTextY}^A0N,{$barcodeTextFont},{$barcodeTextFont}^FB{$contentWidth},1,0,C,0^FD{$barcode}^FS",
            '^XZ',
            '',
        ]);
    }

    public function labelWidthInches(): float
    {
        return round($this->labelWidthMm / 25.4, 1);
    }

    public function labelHeightInches(): float
    {
        return round($this->labelHeightMm / 25.4, 1);
    }

    private function sanitize(string $value): string
    {
        return str_replace(['^', '~', '\\'], '', trim($value));
    }

    private function recommendedModuleWidth(string $barcode): int
    {
        $length = strlen($barcode);

        if ($length <= 8) {
            return 3;
        }

        if ($length <= 12) {
            return 2;
        }

        return 1;
    }

    private function estimatedCode128WidthDots(string $barcode, int $moduleWidth): int
    {
        $length = max(1, strlen($barcode));
        $modules = 35 + ($length * 11);

        return $modules * $moduleWidth;
    }
}

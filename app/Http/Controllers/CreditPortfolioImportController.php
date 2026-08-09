<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class CreditPortfolioImportController extends Controller
{
    /**
     * Download sample Excel template for credit portfolio import.
     */
    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Plantilla Cartera');

        // Header Styling
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2563EB'], // Blue theme for credit portfolio
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '1D4ED8'],
                ],
            ],
        ];

        $dataStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'E2E8F0'],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];

        // Headers
        $headers = [
            'Documento Cliente',
            'Nombre Cliente',
            'Número Factura',
            'Monto Total Crédito',
            'Monto Pagado',
            'Fecha Factura',
            'Fecha Vencimiento',
            'Observaciones / Notas',
            'Sucursal',
        ];

        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }
        $sheet->getStyle('A1:I1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(28);

        // Sample Data Rows
        $samples = [
            [
                '1098765432',
                'Juan Carlos Pérez',
                'FACT-10045',
                2500000,
                500000,
                '01/07/2026',
                '01/08/2026',
                'Factura saldo sistema anterior',
                '',
            ],
            [
                '900123456-7',
                'Distribuidora Nacional SAS',
                'FACT-10088',
                8200000,
                0,
                '10/07/2026',
                '10/08/2026',
                'Cartera migrada',
                '',
            ],
        ];

        $rowNum = 2;
        foreach ($samples as $sample) {
            $c = 'A';
            foreach ($sample as $value) {
                $sheet->setCellValue($c . $rowNum, $value);
                $c++;
            }
            $sheet->getStyle('A' . $rowNum . ':I' . $rowNum)->applyFromArray($dataStyle);
            $sheet->getStyle('D' . $rowNum)->getNumberFormat()->setFormatCode('$#,##0');
            $sheet->getStyle('E' . $rowNum)->getNumberFormat()->setFormatCode('$#,##0');
            $sheet->getRowDimension($rowNum)->setRowHeight(22);
            $rowNum++;
        }

        // Auto-size columns
        foreach (range('A', 'I') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'plantilla_importacion_cartera.xlsx';

        $tempFile = tempnam(sys_get_temp_dir(), 'port_tpl_');
        $writer->save($tempFile);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}

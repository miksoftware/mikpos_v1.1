<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class CustomerImportController extends Controller
{
    /**
     * Download sample Excel template for customer import.
     */
    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Plantilla Clientes');

        // Header Styling
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'A855F7'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '9333EA'],
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
            'Tipo Cliente',
            'Tipo Documento',
            'Número Documento',
            'Nombres',
            'Apellidos',
            'Razón Social',
            'Teléfono',
            'Correo Electrónico',
            'Departamento',
            'Municipio',
            'Dirección',
            'Maneja Crédito',
            'Monto Límite Crédito',
            'Sucursal',
        ];

        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }
        $sheet->getStyle('A1:N1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(28);

        // Sample Data Rows
        $samples = [
            [
                'Natural',
                'CC',
                '1098765432',
                'Juan Carlos',
                'Pérez Gómez',
                '',
                '3101234567',
                'juan.perez@example.com',
                'Valle del Cauca',
                'Cali',
                'Calle 15 # 4-25',
                'SI',
                5000000,
                '',
            ],
            [
                'Juridico',
                'NIT',
                '901234567-1',
                'Distribuidora',
                'Nacional SAS',
                'Distribuidora Nacional SAS',
                '6025551234',
                'contacto@distribuidoranacional.com',
                'Valle del Cauca',
                'Cali',
                'Carrera 10 # 20-30',
                'SI',
                15000000,
                '',
            ],
            [
                'Natural',
                'CC',
                '1012345678',
                'Maria Camila',
                'Torres Lopez',
                '',
                '3209876543',
                'maria.torres@example.com',
                'Valle del Cauca',
                'Cali',
                'Av 6N # 18-12',
                'NO',
                0,
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
            $sheet->getStyle('A' . $rowNum . ':N' . $rowNum)->applyFromArray($dataStyle);
            $sheet->getStyle('M' . $rowNum)->getNumberFormat()->setFormatCode('$#,##0');
            $sheet->getRowDimension($rowNum)->setRowHeight(22);
            $rowNum++;
        }

        // Auto-size columns
        foreach (range('A', 'N') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'plantilla_importacion_clientes.xlsx';

        $tempFile = tempnam(sys_get_temp_dir(), 'cust_tpl_');
        $writer->save($tempFile);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}

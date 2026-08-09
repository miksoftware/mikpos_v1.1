<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Municipality;
use App\Models\Sale;
use App\Models\TaxDocument;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Carbon\Carbon;

class CreditPortfolioImportService
{
    /**
     * Import external credit portfolio invoices from an Excel or CSV file.
     *
     * @param string $filePath
     * @param int|null $defaultBranchId
     * @return array
     */
    public function import(string $filePath, ?int $defaultBranchId = null): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray(null, true, true, true);

        if (empty($rows) || count($rows) < 2) {
            return [
                'created' => 0,
                'updated' => 0,
                'errors' => ['El archivo está vacío o no contiene datos.'],
            ];
        }

        // Get header row and normalize columns
        $headerRow = array_shift($rows); // Row 1
        $columnMap = $this->mapHeaders($headerRow);

        if (!isset($columnMap['customer_doc'])) {
            return [
                'created' => 0,
                'updated' => 0,
                'errors' => ['No se encontró la columna requerida "Documento Cliente" o "cedula" / "nit" en la cabecera.'],
            ];
        }

        // Pre-load reference collections for fast lookup
        $taxDocuments = TaxDocument::where('is_active', true)->get();
        $defaultTaxDoc = $taxDocuments->firstWhere('dian_code', '3') 
            ?? $taxDocuments->firstWhere('abbreviation', 'CC') 
            ?? $taxDocuments->first();

        $departments = Department::where('is_active', true)->with('activeMunicipalities')->get();
        $defaultDepartment = $departments->firstWhere('dian_code', '76') 
            ?? $departments->firstWhere('name', 'Valle del Cauca') 
            ?? $departments->first();
        
        $defaultMunicipality = $defaultDepartment?->activeMunicipalities->first();

        $branches = Branch::where('is_active', true)->get();
        $fallbackBranchId = $defaultBranchId ?: ($branches->first()?->id ?? 1);
        $authUserId = auth()->id() ?: 1;

        $createdCount = 0;
        $updatedCount = 0;
        $errors = [];
        $rowIndex = 2; // Data starts at row 2 in Excel

        foreach ($rows as $row) {
            $customerDoc = trim((string) $this->getCellValue($row, $columnMap, 'customer_doc'));

            // Skip empty rows
            if ($customerDoc === '') {
                $rowIndex++;
                continue;
            }

            $customerName = trim((string) $this->getCellValue($row, $columnMap, 'customer_name'));
            $invoiceNumber = trim((string) $this->getCellValue($row, $columnMap, 'invoice_number'));
            
            $totalRaw = $this->getCellValue($row, $columnMap, 'total');
            $paidRaw = $this->getCellValue($row, $columnMap, 'paid_amount');
            
            $totalAmount = $this->parseNumber($totalRaw);
            $paidAmount = $this->parseNumber($paidRaw);

            if ($totalAmount <= 0) {
                $errors[] = "Fila {$rowIndex}: El monto del crédito debe ser mayor a 0 para el cliente doc '{$customerDoc}'.";
                $rowIndex++;
                continue;
            }

            // Resolve Branch
            $branchRaw = trim((string) $this->getCellValue($row, $columnMap, 'branch'));
            $branchId = $this->findBranchId($branchRaw, $branches) ?: $fallbackBranchId;

            // Resolve dates
            $today = now()->format('Y-m-d');
            $invoiceDateRaw = $this->getCellValue($row, $columnMap, 'invoice_date');
            $dueDateRaw = $this->getCellValue($row, $columnMap, 'due_date');

            $invoiceDate = $this->parseDate($invoiceDateRaw, $today);
            $defaultDueDate = Carbon::parse($invoiceDate)->addDays(30)->format('Y-m-d');
            $dueDate = $this->parseDate($dueDateRaw, $defaultDueDate);

            $notes = trim((string) $this->getCellValue($row, $columnMap, 'notes')) ?: 'Cartera migrada / Saldo inicial';

            try {
                DB::beginTransaction();

                // Find or auto-create customer
                $customer = Customer::where('document_number', $customerDoc)->first();

                if (!$customer) {
                    if ($customerName === '') {
                        DB::rollBack();
                        $errors[] = "Fila {$rowIndex}: El cliente con documento '{$customerDoc}' no existe en el sistema. Incluya su nombre en la columna 'Nombre Cliente' para crearlo automáticamente.";
                        $rowIndex++;
                        continue;
                    }

                    // Auto-create customer
                    $isJuridico = preg_match('/(sas|s\.a\.s|ltda|inc|corp|s\.a|euyu)/i', $customerName);
                    $first = $customerName;
                    $last = '.';
                    $biz = null;

                    if ($isJuridico) {
                        $biz = $customerName;
                    } elseif (str_contains($customerName, ' ')) {
                        $parts = explode(' ', $customerName, 2);
                        $first = $parts[0];
                        $last = $parts[1];
                    }

                    $customer = Customer::create([
                        'branch_id' => $branchId,
                        'customer_type' => $isJuridico ? 'juridico' : 'natural',
                        'tax_document_id' => $defaultTaxDoc?->id ?: 1,
                        'document_number' => $customerDoc,
                        'first_name' => $first,
                        'last_name' => $last,
                        'business_name' => $biz,
                        'department_id' => $defaultDepartment?->id ?: 1,
                        'municipality_id' => $defaultMunicipality?->id ?: 1,
                        'address' => 'Sin dirección',
                        'has_credit' => true,
                        'credit_limit' => max(5000000, $totalAmount),
                        'is_active' => true,
                    ]);

                    ActivityLogService::logCreate('customers', $customer, "Cliente '{$customer->full_name}' creado automáticamente al importar cartera");
                }

                // If invoice number is empty, generate next sequential invoice number
                if ($invoiceNumber === '') {
                    $invoiceNumber = Sale::generateInvoiceNumber($branchId);
                }

                // Check if this invoice number already exists for this customer or in sales
                $existingSale = Sale::where('invoice_number', $invoiceNumber)->first();
                $isNewSale = !$existingSale;

                $paymentStatus = 'pending';
                if ($paidAmount >= $totalAmount - 0.01) {
                    $paymentStatus = 'paid';
                    $paidAmount = $totalAmount;
                } elseif ($paidAmount > 0) {
                    $paymentStatus = 'partial';
                }

                $sale = Sale::updateOrCreate(
                    ['invoice_number' => $invoiceNumber],
                    [
                        'branch_id' => $branchId,
                        'customer_id' => $customer->id,
                        'user_id' => $authUserId,
                        'subtotal' => $totalAmount,
                        'tax_total' => 0,
                        'discount' => 0,
                        'total' => $totalAmount,
                        'status' => 'completed',
                        'payment_type' => 'credit',
                        'payment_status' => $paymentStatus,
                        'credit_amount' => $totalAmount,
                        'paid_amount' => $paidAmount,
                        'payment_due_date' => $dueDate,
                        'notes' => $notes,
                        'source' => 'cartera_importada',
                        'created_at' => $invoiceDate . ' ' . now()->format('H:i:s'),
                    ]
                );

                if ($isNewSale) {
                    $createdCount++;
                    ActivityLogService::logCreate('sales', $sale, "Factura de cartera #{$sale->invoice_number} importada para cliente '{$customer->full_name}' por $" . number_format($totalAmount, 2));
                } else {
                    $updatedCount++;
                    ActivityLogService::logUpdate('sales', $sale, $existingSale->toArray(), "Factura de cartera #{$sale->invoice_number} actualizada para cliente '{$customer->full_name}'");
                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                $errors[] = "Fila {$rowIndex} (Doc: {$customerDoc}): " . $e->getMessage();
            }

            $rowIndex++;
        }

        return [
            'created' => $createdCount,
            'updated' => $updatedCount,
            'errors' => $errors,
        ];
    }

    private function mapHeaders(array $headerRow): array
    {
        $map = [];
        foreach ($headerRow as $colKey => $colValue) {
            if (!$colValue) continue;

            $normalized = $this->normalizeString((string)$colValue);

            if (in_array($normalized, ['documentocliente', 'cedula', 'nit', 'documento', 'documentnumber', 'numdoc', 'doccliente'])) {
                $map['customer_doc'] = $colKey;
            } elseif (in_array($normalized, ['nombrecliente', 'cliente', 'customername', 'nombre', 'razonsocial', 'nombres'])) {
                $map['customer_name'] = $colKey;
            } elseif (in_array($normalized, ['numerofactura', 'factura', 'numfactura', 'invoicenumber', 'documentonum', 'nrofactura', 'nofactura'])) {
                $map['invoice_number'] = $colKey;
            } elseif (in_array($normalized, ['montototalcredito', 'montototal', 'total', 'montocredito', 'saldoinicial', 'monto', 'creditamount', 'valorfactura', 'montofactura', 'valor', 'saldo', 'saldocredito', 'valorcredito'])) {
                $map['total'] = $colKey;
            } elseif (in_array($normalized, ['montopagado', 'pagado', 'abono', 'paidamount', 'abonoinicial', 'valorpagado'])) {
                $map['paid_amount'] = $colKey;
            } elseif (in_array($normalized, ['fechafactura', 'fecha', 'date', 'invoicedate', 'fechaemision'])) {
                $map['invoice_date'] = $colKey;
            } elseif (in_array($normalized, ['fechavencimiento', 'vencimiento', 'duedate', 'fechavenc'])) {
                $map['due_date'] = $colKey;
            } elseif (in_array($normalized, ['observaciones', 'notas', 'concepto', 'notes', 'detalle'])) {
                $map['notes'] = $colKey;
            } elseif (in_array($normalized, ['sucursal', 'branch', 'sede'])) {
                $map['branch'] = $colKey;
            }
        }

        return $map;
    }

    private function getCellValue(array $row, array $columnMap, string $field)
    {
        if (!isset($columnMap[$field])) {
            return null;
        }
        $colKey = $columnMap[$field];
        return $row[$colKey] ?? null;
    }

    private function findBranchId(string $value, $branches): ?int
    {
        if ($value === '') return null;
        $valClean = $this->normalizeString($value);
        $valRaw = strtolower(trim($value));

        $branch = $branches->first(function ($b) use ($valClean, $valRaw) {
            return $this->normalizeString($b->name) === $valClean
                || (string)$b->id === $valRaw;
        });

        return $branch?->id;
    }

    private function normalizeString(string $value): string
    {
        $clean = strtolower(trim($value));
        $clean = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ñ', 'ä', 'ë', 'ï', 'ö', 'ü', 'à', 'è', 'ì', 'ò', 'ù'],
            ['a', 'e', 'i', 'o', 'u', 'n', 'a', 'e', 'i', 'o', 'u', 'a', 'e', 'i', 'o', 'u'],
            $clean
        );
        return preg_replace('/[^a-z0-9]/', '', $clean);
    }

    private function parseNumber($value): float
    {
        if ($value === null || $value === '') return 0.0;
        if (is_int($value) || is_float($value)) return (float)$value;

        $str = trim((string)$value);
        $clean = preg_replace('/[^\d\.\,]/', '', $str);
        if ($clean === '') return 0.0;

        $dotCount = substr_count($clean, '.');
        $commaCount = substr_count($clean, ',');

        if ($dotCount > 0 && $commaCount > 0) {
            $lastDotPos = strrpos($clean, '.');
            $lastCommaPos = strrpos($clean, ',');
            if ($lastCommaPos > $lastDotPos) {
                $clean = str_replace('.', '', $clean);
                $clean = str_replace(',', '.', $clean);
            } else {
                $clean = str_replace(',', '', $clean);
            }
        } elseif ($dotCount > 1) {
            $clean = str_replace('.', '', $clean);
        } elseif ($commaCount > 1) {
            $clean = str_replace(',', '', $clean);
        } elseif ($commaCount === 1 && $dotCount === 0) {
            $parts = explode(',', $clean);
            if (strlen($parts[1]) === 3 && strlen($parts[0]) <= 3) {
                $clean = str_replace(',', '', $clean);
            } else {
                $clean = str_replace(',', '.', $clean);
            }
        } elseif ($dotCount === 1 && $commaCount === 0) {
            $parts = explode('.', $clean);
            if (strlen($parts[1]) === 3 && strlen($parts[0]) <= 3) {
                $clean = str_replace('.', '', $clean);
            }
        }

        return (float)$clean;
    }

    private function parseDate($value, string $defaultDate): string
    {
        if ($value === null || $value === '') return $defaultDate;

        if (is_numeric($value)) {
            try {
                return ExcelDate::excelToDateTimeObject($value)->format('Y-m-d');
            } catch (\Exception $e) {
                return $defaultDate;
            }
        }

        $str = trim((string)$value);

        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $str, $matches)) {
            return sprintf('%04d-%02d-%02d', $matches[3], $matches[2], $matches[1]);
        } elseif (preg_match('/^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})$/', $str, $matches)) {
            return sprintf('%04d-%02d-%02d', $matches[1], $matches[2], $matches[3]);
        }

        try {
            return Carbon::parse($str)->format('Y-m-d');
        } catch (\Exception $e) {
            return $defaultDate;
        }
    }
}

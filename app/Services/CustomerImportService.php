<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Municipality;
use App\Models\TaxDocument;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CustomerImportService
{
    /**
     * Import customers from an Excel or CSV file.
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

        if (!isset($columnMap['document_number'])) {
            return [
                'created' => 0,
                'updated' => 0,
                'errors' => ['No se encontró la columna requerida "Número Documento" o "documento" en la cabecera.'],
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

        $createdCount = 0;
        $updatedCount = 0;
        $errors = [];
        $rowIndex = 2; // Data starts at row 2 in Excel

        foreach ($rows as $row) {
            // Extract row data using mapped column indices
            $docNumberRaw = $this->getCellValue($row, $columnMap, 'document_number');
            $docNumber = trim((string) $docNumberRaw);

            // Skip empty rows
            if ($docNumber === '') {
                $rowIndex++;
                continue;
            }

            $firstName = trim((string) $this->getCellValue($row, $columnMap, 'first_name'));
            $lastName = trim((string) $this->getCellValue($row, $columnMap, 'last_name'));
            $businessName = trim((string) $this->getCellValue($row, $columnMap, 'business_name'));
            $customerTypeRaw = strtolower(trim((string) $this->getCellValue($row, $columnMap, 'customer_type')));

            // Determine customer type
            $customerType = 'natural';
            if (in_array($customerTypeRaw, ['juridico', 'jurídico', 'empresa', 'nit'])) {
                $customerType = 'juridico';
            } elseif (in_array($customerTypeRaw, ['exonerado', 'exonera'])) {
                $customerType = 'exonerado';
            }

            // Require name or business_name
            if ($firstName === '' && $businessName === '') {
                $errors[] = "Fila {$rowIndex}: Se requiere Nombre o Razón Social para el documento '{$docNumber}'.";
                $rowIndex++;
                continue;
            }

            if ($customerType === 'juridico' && $businessName === '') {
                $businessName = trim($firstName . ' ' . $lastName);
            }

            if ($customerType === 'natural' && $firstName === '') {
                $firstName = $businessName;
            }

            if ($lastName === '') {
                $lastName = '.';
            }

            // Match Tax Document
            $taxDocRaw = trim((string) $this->getCellValue($row, $columnMap, 'tax_document'));
            $taxDoc = $this->findTaxDocument($taxDocRaw, $taxDocuments) ?: $defaultTaxDoc;

            // Match Department & Municipality
            $deptRaw = trim((string) $this->getCellValue($row, $columnMap, 'department'));
            $muniRaw = trim((string) $this->getCellValue($row, $columnMap, 'municipality'));
            
            $matchedDepartment = $this->findDepartment($deptRaw, $departments) ?: $defaultDepartment;
            $matchedMunicipality = $this->findMunicipality($muniRaw, $matchedDepartment) 
                ?: ($matchedDepartment?->activeMunicipalities->first() ?: $defaultMunicipality);

            // Match Branch
            $branchRaw = trim((string) $this->getCellValue($row, $columnMap, 'branch'));
            $branchId = $this->findBranchId($branchRaw, $branches) ?: $fallbackBranchId;

            // Contact & Location
            $phone = trim((string) $this->getCellValue($row, $columnMap, 'phone')) ?: null;
            $email = trim((string) $this->getCellValue($row, $columnMap, 'email')) ?: null;
            $address = trim((string) $this->getCellValue($row, $columnMap, 'address')) ?: 'Sin dirección';

            // Credit Configuration
            $hasCreditRaw = trim((string) $this->getCellValue($row, $columnMap, 'has_credit'));
            $creditLimitRaw = $this->getCellValue($row, $columnMap, 'credit_limit');

            $hasCredit = $this->parseBoolean($hasCreditRaw);
            $creditLimit = $this->parseNumber($creditLimitRaw);

            // If credit limit is provided (>0) but has_credit was not specified or false, enable credit
            if ($creditLimit > 0 && ($hasCreditRaw === '' || $hasCredit)) {
                $hasCredit = true;
            }

            if (!$hasCredit) {
                $creditLimit = null;
            }

            try {
                DB::beginTransaction();

                $existing = Customer::where('document_number', $docNumber)->first();
                $isNew = !$existing;

                $customer = Customer::updateOrCreate(
                    ['document_number' => $docNumber],
                    [
                        'branch_id' => $branchId,
                        'customer_type' => $customerType,
                        'tax_document_id' => $taxDoc?->id ?: $defaultTaxDoc->id,
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'business_name' => $businessName ?: null,
                        'phone' => $phone,
                        'email' => $email,
                        'department_id' => $matchedDepartment?->id ?: $defaultDepartment?->id,
                        'municipality_id' => $matchedMunicipality?->id ?: $defaultMunicipality?->id,
                        'address' => $address,
                        'has_credit' => $hasCredit,
                        'credit_limit' => $creditLimit,
                        'is_active' => true,
                    ]
                );

                if ($isNew) {
                    $createdCount++;
                    ActivityLogService::logCreate('customers', $customer, "Cliente '{$customer->full_name}' importado desde Excel/CSV");
                } else {
                    $updatedCount++;
                    ActivityLogService::logUpdate('customers', $customer, $existing->toArray(), "Cliente '{$customer->full_name}' actualizado desde Excel/CSV");
                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                $errors[] = "Fila {$rowIndex} (Doc: {$docNumber}): " . $e->getMessage();
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

            $normalized = strtolower(trim((string) $colValue));
            // Basic ASCII normalization for header matching
            $normalized = str_replace(
                ['á', 'é', 'í', 'ó', 'ú', 'ñ', 'Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ'],
                ['a', 'e', 'i', 'o', 'u', 'n', 'a', 'e', 'i', 'o', 'u', 'n'],
                $normalized
            );
            $normalized = preg_replace('/[^a-z0-9_]/', '_', $normalized);
            $normalized = trim(preg_replace('/_+/', '_', $normalized), '_');

            if (in_array($normalized, ['tipo_cliente', 'tipocliente', 'tipo', 'cliente_tipo'])) {
                $map['customer_type'] = $colKey;
            } elseif (in_array($normalized, ['tipo_documento', 'tipodocumento', 'documento_tipo', 'tipo_doc', 'tax_document'])) {
                $map['tax_document'] = $colKey;
            } elseif (in_array($normalized, ['numero_documento', 'numerodocumento', 'documento', 'cedula', 'nit', 'document_number', 'num_doc'])) {
                $map['document_number'] = $colKey;
            } elseif (in_array($normalized, ['nombres', 'nombre', 'first_name'])) {
                $map['first_name'] = $colKey;
            } elseif (in_array($normalized, ['apellidos', 'apellido', 'last_name'])) {
                $map['last_name'] = $colKey;
            } elseif (in_array($normalized, ['razon_social', 'razonsocial', 'empresa', 'business_name'])) {
                $map['business_name'] = $colKey;
            } elseif (in_array($normalized, ['telefono', 'celular', 'phone', 'tel'])) {
                $map['phone'] = $colKey;
            } elseif (in_array($normalized, ['email', 'correo', 'correo_electronico', 'email_address'])) {
                $map['email'] = $colKey;
            } elseif (in_array($normalized, ['departamento', 'department', 'depto'])) {
                $map['department'] = $colKey;
            } elseif (in_array($normalized, ['municipio', 'ciudad', 'municipality', 'muni'])) {
                $map['municipality'] = $colKey;
            } elseif (in_array($normalized, ['direccion', 'address', 'dir'])) {
                $map['address'] = $colKey;
            } elseif (in_array($normalized, ['maneja_credito', 'manejacredito', 'credito', 'has_credit', 'tiene_credito', 'aplica_credito'])) {
                $map['has_credit'] = $colKey;
            } elseif (in_array($normalized, ['monto_credito', 'montocredito', 'limite_credito', 'limitecredito', 'credito_monto', 'credit_limit', 'monto_limite_credito'])) {
                $map['credit_limit'] = $colKey;
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

    private function findTaxDocument(string $value, $taxDocuments)
    {
        if ($value === '') return null;
        $valClean = $this->normalizeString($value);
        $valRawUpper = strtoupper(trim($value));

        return $taxDocuments->first(function ($doc) use ($valClean, $valRawUpper) {
            return strtoupper($doc->abbreviation) === $valRawUpper
                || (string)$doc->dian_code === $valRawUpper
                || $this->normalizeString($doc->abbreviation) === $valClean
                || $this->normalizeString($doc->description) === $valClean;
        });
    }

    private function findDepartment(string $value, $departments)
    {
        if ($value === '') return null;
        $valClean = $this->normalizeString($value);
        $valRaw = strtolower(trim($value));

        return $departments->first(function ($dept) use ($valClean, $valRaw) {
            $deptNorm = $this->normalizeString($dept->name);
            return $deptNorm === $valClean
                || (string)$dept->dian_code === $valRaw
                || str_contains($deptNorm, $valClean)
                || str_contains($valClean, $deptNorm);
        });
    }

    private function findMunicipality(string $value, ?Department $department)
    {
        if ($value === '' || !$department) return null;
        $valClean = $this->normalizeString($value);
        $valRaw = strtolower(trim($value));

        return $department->activeMunicipalities->first(function ($muni) use ($valClean, $valRaw) {
            $muniNorm = $this->normalizeString($muni->name);
            return $muniNorm === $valClean
                || (string)$muni->dian_code === $valRaw
                || str_contains($muniNorm, $valClean)
                || str_contains($valClean, $muniNorm);
        });
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

    private function parseBoolean($value): bool
    {
        if (is_bool($value)) return $value;
        $valClean = strtolower(trim((string)$value));
        return in_array($valClean, ['1', 'si', 'sí', 'true', 'yes', 's', 'y', 'x']);
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
                // E.g. 15.000.000,50 -> dot is thousand separator, comma is decimal
                $clean = str_replace('.', '', $clean);
                $clean = str_replace(',', '.', $clean);
            } else {
                // E.g. 15,000,000.50 -> comma is thousand separator, dot is decimal
                $clean = str_replace(',', '', $clean);
            }
        } elseif ($dotCount > 1) {
            // E.g. 15.000.000 -> dots are thousand separators
            $clean = str_replace('.', '', $clean);
        } elseif ($commaCount > 1) {
            // E.g. 15,000,000 -> commas are thousand separators
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
}

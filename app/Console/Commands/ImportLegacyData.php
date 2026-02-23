<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use App\Models\Tax;
use App\Models\Brand;
use App\Models\ProductModel;
use App\Models\Presentation;
use App\Models\Color;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Supplier;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Municipality;
use App\Models\Product;
use App\Models\ProductChild;
use App\Models\ProductBarcode;
use App\Models\Service;
use App\Models\Combo;
use App\Models\ComboItem;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\CreditPayment;
use App\Models\Refund;
use App\Models\RefundItem;
use App\Models\PaymentMethod;
use App\Models\Unit;
use App\Models\TaxDocument;
use App\Models\User;
use App\Models\Role;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Hash;

class ImportLegacyData extends Command
{
    protected $signature = 'migration:import
        {file : Path to the SQL dump file (relative to storage/ or absolute)}
        {--branch=1 : Branch ID to assign data to}
        {--clean : Clean existing business data before importing}
        {--force : Skip confirmation prompts}';

    protected $description = 'Import data from legacy POS system SQL dump';

    // ID mapping arrays
    private array $taxMap = [];
    private array $brandMap = [];
    private array $modelMap = [];
    private array $presentationMap = [];
    private array $colorMap = [];
    private array $categoryMap = [];
    private array $subcategoryMap = [];
    private array $supplierMap = [];
    private array $customerMap = [];
    private array $productMap = [];
    private array $productChildMap = [];
    private array $serviceMap = [];
    private array $comboMap = [];
    private array $purchaseMap = [];
    private array $saleMap = [];
    private array $saleItemMap = [];
    private array $paymentMethodMap = [];
    private array $userMap = [];
    private array $departmentMap = [];

    private int $branchId;
    private int $userId;
    private array $stats = [];
    private array $warnings = [];

    public function handle(): int
    {
        set_time_limit(0);
        ini_set('memory_limit', '1G');

        $file = $this->argument('file');
        $this->branchId = (int) $this->option('branch');
        $this->userId = 1; // Default admin user

        // Resolve file path
        if (!str_starts_with($file, '/') && !preg_match('/^[A-Za-z]:[\\\\\\/]/', $file)) {
            $file = storage_path($file);
        }

        if (!File::exists($file)) {
            $this->error("File not found: {$file}");
            return 1;
        }

        $this->info("📂 Loading SQL file: {$file}");
        $sql = File::get($file);
        $sizeMB = round(strlen($sql) / 1024 / 1024, 2);
        $this->info("   File size: {$sizeMB} MB");

        if (empty($sql)) {
            $this->error('SQL file is empty.');
            return 1;
        }

        if (!$this->option('force') && !$this->confirm("Import data to branch {$this->branchId}?")) {
            return 1;
        }

        // Clean if requested
        if ($this->option('clean')) {
            $this->info('🧹 Cleaning existing business data...');
            $this->call('migration:clean', ['--force' => true]);
            $this->newLine();
        }

        $this->info('🚀 Starting migration...');
        $this->newLine();

        try {
            DB::beginTransaction();

            $this->buildPaymentMethodMap($sql);
            $this->buildDepartmentMap($sql);

            $steps = [
                ['migrateUsers', 'Usuarios'],
                ['migrateTaxes', 'Impuestos'],
                ['migrateBrands', 'Marcas'],
                ['migrateModels', 'Modelos'],
                ['migratePresentations', 'Presentaciones'],
                ['migrateColors', 'Colores'],
                ['migrateCategories', 'Categorías'],
                ['migrateSubcategories', 'Subcategorías'],
                ['migrateSuppliers', 'Proveedores'],
                ['migrateCustomers', 'Clientes'],
                ['migrateProducts', 'Productos'],
                ['migrateServices', 'Servicios'],
                ['migrateProductChildren', 'Variantes Hijo'],
                ['migrateCombos', 'Combos'],
                ['migratePurchases', 'Compras'],
                ['migratePurchasePayments', 'Abonos Compras'],
                ['migrateSales', 'Ventas'],
                ['migrateSalePayments', 'Pagos Ventas'],
                ['migrateCreditPayments', 'Abonos Créditos'],
                ['migrateRefunds', 'Devoluciones'],
            ];

            $total = count($steps);
            $current = 0;

            foreach ($steps as [$method, $label]) {
                $current++;
                $this->info("[{$current}/{$total}] {$label}...");
                if (function_exists('ob_flush')) { @ob_flush(); }
                @flush();
                $this->$method($sql);
            }

            $this->newLine();
            $this->info('📊 Mapas de relaciones construidos:');
            $maps = [
                'Usuarios' => count($this->userMap),
                'Impuestos' => count($this->taxMap),
                'Marcas' => count($this->brandMap),
                'Modelos' => count($this->modelMap),
                'Presentaciones' => count($this->presentationMap),
                'Colores' => count($this->colorMap),
                'Categorías' => count($this->categoryMap),
                'Subcategorías' => count($this->subcategoryMap),
                'Proveedores' => count($this->supplierMap),
                'Clientes' => count($this->customerMap),
                'Productos' => count($this->productMap),
                'Variantes' => count($this->productChildMap),
                'Servicios' => count($this->serviceMap),
                'Combos' => count($this->comboMap),
                'Compras' => count($this->purchaseMap),
                'Ventas' => count($this->saleMap),
                'Métodos pago' => count($this->paymentMethodMap),
            ];
            foreach ($maps as $name => $size) {
                $this->info("  {$name}: {$size} mapeados");
            }

            $this->newLine();

            DB::commit();

            // Log activity
            ActivityLogService::log('migration', 'create', 'Migración de datos completada desde sistema anterior');

            $this->info('✅ ¡Migración completada exitosamente!');
            $this->newLine();
            $this->showStats();

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->newLine(2);
            $this->error('❌ Error: ' . $e->getMessage());
            $this->error('   Todos los cambios fueron revertidos.');
            $this->newLine();
            $this->error('Archivo: ' . $e->getFile() . ':' . $e->getLine());
            return 1;
        }
    }

    private function showStats(): void
    {
        $this->table(
            ['Entidad', 'Cantidad'],
            collect($this->stats)->map(fn($v, $k) => [$k, $v])->values()->toArray()
        );

        if (!empty($this->warnings)) {
            $this->newLine();
            $this->warn('⚠️  Advertencias de relaciones no encontradas:');
            $this->table(
                ['Entidad', 'Campo', 'No encontrados'],
                collect($this->warnings)->map(fn($v, $k) => [...explode('|', $k), $v])->values()->toArray()
            );
        }
    }

    private function addWarning(string $entity, string $field, string $value): void
    {
        $key = "{$entity}|{$field}";
        $this->warnings[$key] = ($this->warnings[$key] ?? 0) + 1;
    }

    private function normalizeKey($value): string
    {
        return strtolower(trim((string) $value));
    }

    private function addLog(string $type, string $message): void
    {
        $this->info("  {$message}");
        if (function_exists('ob_flush')) { @ob_flush(); }
        @flush();
    }

    private function logTableColumns(array $rows, string $tableName): void
    {
        if (empty($rows)) {
            $this->warn("  ⚠️  No se encontraron filas para '{$tableName}'");
            return;
        }
        $columns = array_keys($rows[0]);
        $this->info("  → Columnas de '{$tableName}': " . implode(', ', $columns));
    }

    // ─── SQL Parser ───

    private function parseInserts(string $sql, string $tableName): array
    {
        $rows = [];
        $tbl = preg_quote($tableName, '/');
        $tablePattern = '(?:`?[\\w]+`?\\.)?`?' . $tbl . '`?';

        $insertPattern = '/INSERT\s+INTO\s+' . $tablePattern . '\s*\(([^)]+)\)\s*VALUES\s*/is';
        $offset = 0;

        while (preg_match($insertPattern, $sql, $m, PREG_OFFSET_CAPTURE, $offset)) {
            // Extract columns from THIS specific INSERT statement
            $columnsRaw = $m[1][0];
            $columns = array_map(function ($col) {
                return strtolower(trim($col, " `\t\n\r"));
            }, explode(',', $columnsRaw));

            $startPos = $m[0][1] + strlen($m[0][0]);

            $endPos = $this->findStatementEnd($sql, $startPos);
            if ($endPos === false) break;

            $valuesBlock = substr($sql, $startPos, $endPos - $startPos);
            $offset = $endPos + 1;

            $tuples = $this->parseValueTuples($valuesBlock);
            foreach ($tuples as $tuple) {
                $values = $this->parseRowValues($tuple);
                if (count($values) === count($columns)) {
                    $rows[] = array_combine($columns, $values);
                }
            }
        }

        return $rows;
    }

    /**
     * Find the semicolon that ends an INSERT statement, respecting strings.
     */
    private function findStatementEnd(string $sql, int $start): int|false
    {
        $len = strlen($sql);
        $inString = false;
        $escapeNext = false;
        $stringChar = '';

        for ($i = $start; $i < $len; $i++) {
            $char = $sql[$i];

            if ($escapeNext) { $escapeNext = false; continue; }
            if ($char === '\\') { $escapeNext = true; continue; }

            if ($inString) {
                if ($char === $stringChar) {
                    if (isset($sql[$i + 1]) && $sql[$i + 1] === $stringChar) { $i++; continue; }
                    $inString = false;
                }
                continue;
            }

            if ($char === '\'' || $char === '"') { $inString = true; $stringChar = $char; continue; }
            if ($char === ';') return $i;
        }

        return $len; // If no semicolon found, use end of string
    }

    private function parseValueTuples(string $block): array
    {
        $tuples = [];
        $depth = 0;
        $current = '';
        $inString = false;
        $escapeNext = false;
        $stringChar = '';

        for ($i = 0; $i < strlen($block); $i++) {
            $char = $block[$i];

            if ($escapeNext) { $current .= $char; $escapeNext = false; continue; }
            if ($char === '\\') { $current .= $char; $escapeNext = true; continue; }

            if ($inString) {
                $current .= $char;
                if ($char === $stringChar) {
                    if (isset($block[$i + 1]) && $block[$i + 1] === $stringChar) {
                        $current .= $block[$i + 1]; $i++; continue;
                    }
                    $inString = false;
                }
                continue;
            }

            if ($char === '\'' || $char === '"') { $inString = true; $stringChar = $char; $current .= $char; continue; }
            if ($char === '(') { $depth++; if ($depth === 1) { $current = ''; continue; } }
            if ($char === ')') { $depth--; if ($depth === 0) { $tuples[] = $current; $current = ''; continue; } }
            if ($depth > 0) { $current .= $char; }
        }

        return $tuples;
    }

    private function parseRowValues(string $tuple): array
    {
        $values = [];
        $current = '';
        $inString = false;
        $escapeNext = false;
        $stringChar = '';

        for ($i = 0; $i < strlen($tuple); $i++) {
            $char = $tuple[$i];

            if ($escapeNext) { $current .= $char; $escapeNext = false; continue; }
            if ($char === '\\') { $escapeNext = true; continue; }

            if ($inString) {
                if ($char === $stringChar) {
                    if (isset($tuple[$i + 1]) && $tuple[$i + 1] === $stringChar) { $current .= $stringChar; $i++; continue; }
                    $inString = false; continue;
                }
                $current .= $char; continue;
            }

            if ($char === '\'' || $char === '"') { $inString = true; $stringChar = $char; continue; }
            if ($char === ',') { $values[] = $this->cleanValue(trim($current)); $current = ''; continue; }
            $current .= $char;
        }

        $values[] = $this->cleanValue(trim($current));
        return $values;
    }

    private function cleanValue(string $value): ?string
    {
        return strtoupper($value) === 'NULL' ? null : $value;
    }

    private function cleanBarcode(?string $barcode): ?string
    {
        if (empty($barcode)) return null;
        $barcode = trim($barcode);
        if (empty($barcode)) return null;
        if (preg_match('/[eE][+\-]?\d/', $barcode)) return null;
        if (str_contains($barcode, ',')) return null;
        return $barcode;
    }

    // ─── Payment Method Map ───

    private function buildPaymentMethodMap(string $sql): void
    {
        $oldMethods = $this->parseInserts($sql, 'mediospagos');
        $newMethods = PaymentMethod::all();

        foreach ($oldMethods as $old) {
            $oldName = strtolower(trim($old['mediopago'] ?? ''));
            $oldId = (int) ($old['codmediopago'] ?? 0);
            $matched = $newMethods->first(function ($m) use ($oldName) {
                $newName = strtolower($m->name);
                return (str_contains($newName, 'efectivo') && str_contains($oldName, 'efectivo'))
                    || (str_contains($newName, 'débito') && (str_contains($oldName, 'debito') || str_contains($oldName, 'débito')))
                    || (str_contains($newName, 'crédito') && (str_contains($oldName, 'credito') || str_contains($oldName, 'crédito')))
                    || (str_contains($newName, 'transferencia') && str_contains($oldName, 'transferencia'))
                    || (str_contains($newName, 'nequi') && str_contains($oldName, 'nequi'))
                    || (str_contains($newName, 'daviplata') && str_contains($oldName, 'daviplata'));
            });
            if ($matched) {
                $this->paymentMethodMap[$oldId] = $matched->id;
            } else {
                // Try exact name match as fallback
                $exactMatch = $newMethods->first(fn($m) => strtolower($m->name) === $oldName);
                if ($exactMatch) {
                    $this->paymentMethodMap[$oldId] = $exactMatch->id;
                }
            }
        }

        $defaultMethod = PaymentMethod::where('is_active', true)
            ->where(fn($q) => $q->where('name', 'like', '%efectivo%')->orWhere('name', 'like', '%cash%'))
            ->first() ?? PaymentMethod::first();

        if ($defaultMethod) {
            $this->paymentMethodMap[0] = $defaultMethod->id;
        }
    }

    private function getPaymentMethodId(int $oldId): int
    {
        return $this->paymentMethodMap[$oldId] ?? $this->paymentMethodMap[0] ?? 1;
    }

    // ─── Department Map (provincias → departments) ───

    private function buildDepartmentMap(string $sql): void
    {
        $rows = $this->parseInserts($sql, 'provincias');
        $departments = Department::all();

        foreach ($rows as $row) {
            $oldId = (int) ($row['id_provincia'] ?? 0);
            $oldName = strtolower(trim($row['provincia'] ?? ''));
            if (empty($oldName) || $oldId === 0) continue;

            $matched = $departments->first(function ($dept) use ($oldName) {
                return strtolower($dept->name) === $oldName;
            });

            if ($matched) {
                $this->departmentMap[$oldId] = $matched->id;
            }
        }
    }

    private function resolveDepartmentAndMunicipality(?string $oldProvinciaId): array
    {
        $oldId = (int) ($oldProvinciaId ?? 0);
        $departmentId = $this->departmentMap[$oldId] ?? null;

        if ($departmentId) {
            $municipality = Municipality::where('department_id', $departmentId)->first();
            return [$departmentId, $municipality->id ?? null];
        }

        $defaultDept = Department::first();
        $defaultMuni = $defaultDept ? Municipality::where('department_id', $defaultDept->id)->first() : Municipality::first();
        return [$defaultDept->id ?? 1, $defaultMuni->id ?? 1];
    }

    // ─── Step 0: Users ───

    private function migrateUsers(string $sql): void
    {
        $rows = $this->parseInserts($sql, 'usuarios');
        $this->logTableColumns($rows, 'usuarios');
        $count = 0;

        // Map old roles to MikPOS roles
        $roleMapping = [
            'administrador(a) general' => 'super_admin',
            'administrador(a) sucursal' => 'branch_admin',
            'cajero(a)' => 'cashier',
        ];

        foreach ($rows as $row) {
            $oldCodigo = (int) ($row['codigo'] ?? 0);
            $name = trim($row['nombres'] ?? '');
            $email = strtolower(trim($row['email'] ?? ''));
            if (empty($name)) continue;

            // Check if email already exists (e.g., the super_admin created during install)
            $existingUser = User::where('email', $email)->first();
            if ($existingUser) {
                $this->userMap[$oldCodigo] = $existingUser->id;
                $this->addLog('info', "  → Usuario '{$name}' ya existe (ID {$existingUser->id}), mapeado");
                continue;
            }

            $oldNivel = strtolower(trim($row['nivel'] ?? ''));
            $roleName = $roleMapping[$oldNivel] ?? 'cashier';
            $isActive = ((int) ($row['status'] ?? 0)) === 1;

            $user = new User();
            $user->name = $name;
            $user->email = $email;
            $user->branch_id = $this->branchId;
            $user->phone = trim($row['telefono'] ?? '') ?: null;
            $user->is_active = $isActive;
            // Set a placeholder password - users will need to reconfigure
            $user->password = 'changeme123';
            $user->save();

            // Assign role
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $user->roles()->attach($role->id, ['branch_id' => $roleName === 'super_admin' ? null : $this->branchId]);
            }

            $this->userMap[$oldCodigo] = $user->id;
            $count++;
        }

        // Map user ID 0 to the default admin (fallback)
        $this->userMap[0] = $this->userId;

        $this->stats['Usuarios'] = $count;
        $this->addLog('info', "Usuarios: {$count} creados, " . count($this->userMap) . " mapeados");
    }

    private function resolveUserId(?string $oldCodigo): int
    {
        $code = (int) ($oldCodigo ?? 0);
        return $this->userMap[$code] ?? $this->userId;
    }

    // ─── Step 1: Taxes ───
    private function migrateTaxes(string $sql): void
    {
        $rows = $this->parseInserts($sql, 'impuestos');
        $count = 0;

        foreach ($rows as $row) {
            $value = (float) ($row['valorimpuesto'] ?? 0);
            $name = trim($row['nomimpuesto'] ?? 'IVA');

            $tax = Tax::where('value', $value)->first();
            if (!$tax) {
                $tax = Tax::create(['name' => $name, 'value' => $value, 'is_active' => true]);
                $count++;
            }
            $this->taxMap[(int) $row['codimpuesto']] = $tax->id;
        }

        $this->stats['Impuestos'] = $count . ' creados, ' . count($this->taxMap) . ' mapeados';
        $this->addLog('info', "Impuestos: {$count} creados, " . count($this->taxMap) . " mapeados");
    }

    private function resolveTaxId(?string $ivaProducto): ?int
    {
        if ($ivaProducto === null || $ivaProducto === '') return null;
        return Tax::where('value', (float) $ivaProducto)->first()?->id;
    }

    // ─── Step 2: Brands ───
    private function migrateBrands(string $sql): void
    {
        $rows = $this->parseInserts($sql, 'marcas');
        $count = 0;
        foreach ($rows as $row) {
            $name = trim($row['nommarca'] ?? '');
            if (empty($name)) continue;
            $brand = Brand::firstOrCreate(['name' => $name], ['is_active' => true]);
            $this->brandMap[(int) $row['codmarca']] = $brand->id;
            if ($brand->wasRecentlyCreated) $count++;
        }
        $this->stats['Marcas'] = $count;
        $this->addLog('info', "Marcas: {$count} creadas");
    }

    // ─── Step 3: Models ───
    private function migrateModels(string $sql): void
    {
        $rows = $this->parseInserts($sql, 'modelos');
        $count = 0;
        foreach ($rows as $row) {
            $name = trim($row['nommodelo'] ?? '');
            if (empty($name)) continue;
            $oldBrandId = (int) ($row['codmarca'] ?? 0);
            $brandId = $oldBrandId ? ($this->brandMap[$oldBrandId] ?? null) : null;
            $model = ProductModel::firstOrCreate(['name' => $name, 'brand_id' => $brandId], ['is_active' => true]);
            $this->modelMap[(int) $row['codmodelo']] = $model->id;
            if ($model->wasRecentlyCreated) $count++;
        }
        $this->stats['Modelos'] = $count;
        $this->addLog('info', "Modelos: {$count} creados");
    }

    // ─── Step 4: Presentations ───
    private function migratePresentations(string $sql): void
    {
        $rows = $this->parseInserts($sql, 'presentaciones');
        $count = 0;
        foreach ($rows as $row) {
            $name = trim($row['nompresentacion'] ?? '');
            if (empty($name)) continue;
            $pres = Presentation::firstOrCreate(['name' => $name], ['is_active' => true]);
            $this->presentationMap[(int) $row['codpresentacion']] = $pres->id;
            if ($pres->wasRecentlyCreated) $count++;
        }
        $this->stats['Presentaciones'] = $count;
        $this->addLog('info', "Presentaciones: {$count} creadas");
    }

    // ─── Step 5: Colors ───
    private function migrateColors(string $sql): void
    {
        $rows = $this->parseInserts($sql, 'colores');
        $count = 0;
        foreach ($rows as $row) {
            $name = trim($row['nomcolor'] ?? '');
            if (empty($name)) continue;
            $color = Color::firstOrCreate(['name' => $name], ['is_active' => true]);
            $this->colorMap[(int) $row['codcolor']] = $color->id;
            if ($color->wasRecentlyCreated) $count++;
        }
        $this->stats['Colores'] = $count;
        $this->addLog('info', "Colores: {$count} creados");
    }

    // ─── Step 6: Categories ───
    private function migrateCategories(string $sql): void
    {
        $rows = $this->parseInserts($sql, 'familias');
        $this->logTableColumns($rows, 'familias');
        $count = 0;
        foreach ($rows as $row) {
            $name = trim($row['nomfamilia'] ?? '');
            if (empty($name)) continue;
            $cat = Category::firstOrCreate(['name' => $name], ['is_active' => true]);
            $this->categoryMap[(int) $row['codfamilia']] = $cat->id;
            if ($cat->wasRecentlyCreated) $count++;
        }
        $this->stats['Categorías'] = $count;
        $this->addLog('info', "Categorías: {$count} creadas");
    }

    // ─── Step 7: Subcategories ───
    private function migrateSubcategories(string $sql): void
    {
        $rows = $this->parseInserts($sql, 'subfamilias');
        $count = 0;
        foreach ($rows as $row) {
            $name = trim($row['nomsubfamilia'] ?? '');
            if (empty($name)) continue;
            $oldCatId = (int) ($row['codfamilia'] ?? 0);
            $categoryId = $oldCatId ? ($this->categoryMap[$oldCatId] ?? null) : null;
            if (!$categoryId) {
                $this->addWarning('Subcategorías', 'codfamilia', (string) $oldCatId);
                continue;
            }
            $sub = Subcategory::firstOrCreate(['name' => $name, 'category_id' => $categoryId], ['is_active' => true]);
            $this->subcategoryMap[(int) $row['codsubfamilia']] = $sub->id;
            if ($sub->wasRecentlyCreated) $count++;
        }
        $this->stats['Subcategorías'] = $count;
        $this->addLog('info', "Subcategorías: {$count} creadas");
    }

    // ─── Step 8: Suppliers ───
    private function migrateSuppliers(string $sql): void
    {
        $rows = $this->parseInserts($sql, 'proveedores');
        $this->logTableColumns($rows, 'proveedores');
        $count = 0;
        $ccDoc = TaxDocument::where('abbreviation', 'CC')->first();
        $nitDoc = TaxDocument::where('abbreviation', 'NIT')->first();
        $defaultDocId = $nitDoc->id ?? $ccDoc->id ?? null;

        foreach ($rows as $row) {
            $name = trim($row['nomproveedor'] ?? '');
            if (empty($name)) continue;

            [$deptId, $muniId] = $this->resolveDepartmentAndMunicipality($row['id_provincia'] ?? null);

            $supplier = Supplier::create([
                'tax_document_id' => $defaultDocId,
                'document_number' => trim($row['cuitproveedor'] ?? '') ?: null,
                'name' => $name,
                'phone' => trim($row['tlfproveedor'] ?? '') ?: null,
                'email' => trim($row['emailproveedor'] ?? '') ?: null,
                'department_id' => $deptId,
                'municipality_id' => $muniId,
                'address' => trim($row['direcproveedor'] ?? '') ?: null,
                'salesperson_name' => trim($row['vendedor'] ?? '') ?: null,
                'salesperson_phone' => trim($row['tlfvendedor'] ?? '') ?: null,
                'is_active' => true,
            ]);
            $this->supplierMap[$this->normalizeKey($row['codproveedor'])] = $supplier->id;
            $count++;
        }
        $this->stats['Proveedores'] = $count;
        $this->addLog('info', "Proveedores: {$count} migrados");
    }

    // ─── Step 9: Customers ───
    private function migrateCustomers(string $sql): void
    {
        $rows = $this->parseInserts($sql, 'clientes');
        $this->logTableColumns($rows, 'clientes');
        $count = 0;
        $ccDoc = TaxDocument::where('abbreviation', 'CC')->first();
        $nitDoc = TaxDocument::where('abbreviation', 'NIT')->first();

        foreach ($rows as $row) {
            $customerType = strtolower(trim($row['tipocliente'] ?? 'natural'));
            $isJuridico = str_contains($customerType, 'jurid');
            $docTypeId = $isJuridico ? ($nitDoc->id ?? $ccDoc->id ?? null) : ($ccDoc->id ?? null);
            $nomCliente = trim($row['nomcliente'] ?? '');
            $razonCliente = trim($row['razoncliente'] ?? '');
            $firstName = ''; $lastName = ''; $businessName = '';

            if ($isJuridico) {
                $businessName = $razonCliente ?: $nomCliente;
                $firstName = $nomCliente;
            } else {
                $parts = explode(' ', $nomCliente, 2);
                $firstName = $parts[0] ?? '';
                $lastName = $parts[1] ?? '';
            }
            if (empty($firstName) && empty($businessName)) continue;

            [$deptId, $muniId] = $this->resolveDepartmentAndMunicipality($row['id_provincia'] ?? null);

            $creditLimit = (float) ($row['limitecredito'] ?? 0);
            $customer = Customer::create([
                'branch_id' => $this->branchId,
                'customer_type' => $isJuridico ? 'juridico' : 'natural',
                'tax_document_id' => $docTypeId,
                'document_number' => trim($row['dnicliente'] ?? '') ?: null,
                'first_name' => $firstName, 'last_name' => $lastName,
                'business_name' => $businessName ?: null,
                'phone' => trim($row['tlfcliente'] ?? '') ?: null,
                'email' => trim($row['emailcliente'] ?? '') ?: null,
                'department_id' => $deptId,
                'municipality_id' => $muniId,
                'address' => trim($row['direccliente'] ?? '') ?: null,
                'has_credit' => $creditLimit > 0, 'credit_limit' => $creditLimit,
                'is_active' => true,
            ]);
            $this->customerMap[$this->normalizeKey($row['codcliente'])] = $customer->id;
            $count++;
        }
        $this->stats['Clientes'] = $count;
        $this->addLog('info', "Clientes: {$count} migrados");
    }

    // ─── Step 10: Products ───
    private function migrateProducts(string $sql): void
    {
        $rows = $this->parseInserts($sql, 'productos');
        $this->addLog('info', "  → Filas parseadas de 'productos': " . count($rows));
        $count = 0; $barcodeCount = 0; $skipped = 0;
        $defaultUnit = Unit::firstOrCreate(['abbreviation' => 'UND'], ['name' => 'Unidad', 'is_active' => true]);

        // Old system has flat products (no tipo_producto/usa_inventario columns).
        // All rows are regular products — no services or child variants.
        foreach ($rows as $row) {
            $name = trim($row['producto'] ?? '');
            if (empty($name)) { $skipped++; continue; }

            $oldCatId = (int) ($row['codfamilia'] ?? 0);
            $categoryId = $oldCatId ? ($this->categoryMap[$oldCatId] ?? null) : null;
            if ($oldCatId && !$categoryId) $this->addWarning('Productos', 'codfamilia', (string) $oldCatId);

            $oldSubcatId = (int) ($row['codsubfamilia'] ?? 0);
            $subcategoryId = $oldSubcatId ? ($this->subcategoryMap[$oldSubcatId] ?? null) : null;
            if ($oldSubcatId && !$subcategoryId) $this->addWarning('Productos', 'codsubfamilia', (string) $oldSubcatId);

            $oldBrandId = (int) ($row['codmarca'] ?? 0);
            $brandId = $oldBrandId ? ($this->brandMap[$oldBrandId] ?? null) : null;
            if ($oldBrandId && !$brandId) $this->addWarning('Productos', 'codmarca', (string) $oldBrandId);

            $oldModelId = (int) ($row['codmodelo'] ?? 0);
            $modelId = $oldModelId ? ($this->modelMap[$oldModelId] ?? null) : null;

            $oldPresId = (int) ($row['codpresentacion'] ?? 0);
            $presentationId = $oldPresId ? ($this->presentationMap[$oldPresId] ?? null) : null;

            $oldColorId = (int) ($row['codcolor'] ?? 0);
            $colorId = $oldColorId ? ($this->colorMap[$oldColorId] ?? null) : null;
            $taxId = $this->resolveTaxId($row['ivaproducto'] ?? null);

            // Old system has no tipo_comision/comision_venta columns — no commissions
            $product = new Product([
                'branch_id' => $this->branchId, 'name' => $name,
                'description' => trim($row['descripcion'] ?? '') ?: null,
                'category_id' => $categoryId, 'subcategory_id' => $subcategoryId,
                'brand_id' => $brandId, 'unit_id' => $defaultUnit->id, 'tax_id' => $taxId,
                'presentation_id' => $presentationId, 'color_id' => $colorId, 'product_model_id' => $modelId,
                'weight' => is_numeric($row['peso'] ?? null) ? (float) $row['peso'] : null,
                'imei' => (trim($row['imei'] ?? '') !== '' && strtoupper(trim($row['imei'] ?? '')) !== 'NO') ? trim($row['imei']) : null,
                'purchase_price' => (float) ($row['preciocompra'] ?? 0),
                'sale_price' => (float) ($row['precioxpublico'] ?? 0),
                'price_includes_tax' => false,
                'min_stock' => is_numeric($row['stockminimo'] ?? null) ? (float) $row['stockminimo'] : 0,
                'max_stock' => is_numeric($row['stockoptimo'] ?? null) ? (float) $row['stockoptimo'] : 0,
                'current_stock' => (float) ($row['existencia'] ?? 0),
                'is_active' => true, 'has_commission' => false,
                'commission_type' => null,
                'commission_value' => 0,
            ]);
            $product->save();
            $product->generateSku();
            $product->save();

            $barcode = $this->cleanBarcode($row['codigobarra'] ?? '');
            if ($barcode && ProductChild::where('barcode', $barcode)->exists()) $barcode = null;

            $child = ProductChild::create([
                'product_id' => $product->id, 'unit_quantity' => 1,
                'sku' => $product->sku . '-01', 'barcode' => $barcode, 'name' => $name,
                'presentation_id' => $presentationId, 'color_id' => $colorId, 'product_model_id' => $modelId,
                'sale_price' => (float) ($row['precioxpublico'] ?? 0), 'price_includes_tax' => false,
                'is_active' => true, 'has_commission' => false,
                'commission_type' => null,
                'commission_value' => 0,
            ]);

            $this->productMap[(int) $row['idproducto']] = $product->id;
            $this->productChildMap[(int) $row['idproducto']] = $child->id;

            if ($barcode && !ProductBarcode::where('barcode', $barcode)->exists()) {
                ProductBarcode::firstOrCreate(['barcode' => $barcode], [
                    'product_id' => $product->id, 'product_child_id' => $child->id, 'is_primary' => true,
                ]);
                $barcodeCount++;
            }
            $count++;
        }
        $this->stats['Productos'] = $count;
        $this->stats['Códigos de barra'] = $barcodeCount;
        $this->addLog('info', "Productos: {$count} migrados, {$barcodeCount} códigos de barra, {$skipped} omitidos");
    }

    // ─── Step 11: Services ───
    // Old system has no service distinction (no usa_inventario column).
    // All productos rows are regular products, so nothing to migrate here.
    private function migrateServices(string $sql): void
    {
        $this->stats['Servicios'] = 0;
        $this->addLog('info', "Servicios: 0 (sistema anterior no distingue servicios)");
    }

    // ─── Step 12: Product Children ───
    // Old system has flat products (no tipo_producto/producto_padre_id columns).
    // No child/variant products exist, so nothing to migrate here.
    // Each product already gets a default child variant in migrateProducts().
    private function migrateProductChildren(string $sql): void
    {
        $this->stats['Variantes hijo'] = 0;
        $this->addLog('info', "Variantes hijo: 0 (sistema anterior no tiene variantes)");
    }

    // ─── Step 13: Combos ───
    private function migrateCombos(string $sql): void
    {
        $comboRows = $this->parseInserts($sql, 'combos');
        $itemRows = $this->parseInserts($sql, 'combosxproductos');
        $count = 0;

        foreach ($comboRows as $row) {
            $name = trim($row['nomcombo'] ?? '');
            if (empty($name)) continue;

            $combo = Combo::create([
                'branch_id' => $this->branchId, 'name' => $name,
                'combo_price' => (float) ($row['precioxpublico'] ?? 0),
                'original_price' => (float) ($row['precioxpublico'] ?? 0),
                'limit_type' => 'none', 'is_active' => true,
            ]);

            $oldCode = $this->normalizeKey($row['codcombo']);
            $this->comboMap[$oldCode] = $combo->id;

            $comboItems = array_filter($itemRows, fn($i) => $this->normalizeKey($i['codcombo']) === $oldCode);
            $originalTotal = 0;

            foreach ($comboItems as $item) {
                $oldProductId = (int) ($item['idproducto'] ?? 0);
                $productId = $this->productMap[$oldProductId] ?? null;
                $childId = $this->productChildMap[$oldProductId] ?? null;
                if (!$productId) continue;

                $qty = (int) ($item['cantidad'] ?? 1);
                $child = $childId ? ProductChild::find($childId) : null;
                $unitPrice = $child ? (float) $child->sale_price : 0;

                ComboItem::create([
                    'combo_id' => $combo->id, 'product_id' => $productId,
                    'product_child_id' => $childId, 'quantity' => $qty, 'unit_price' => $unitPrice,
                ]);
                $originalTotal += $unitPrice * $qty;
            }

            if ($originalTotal > 0) {
                $combo->update(['original_price' => $originalTotal]);
            }
            $count++;
        }

        $this->stats['Combos'] = $count;
        $this->addLog('info', "Combos: {$count} migrados");
    }

    // ─── Step 14: Purchases ───
    private function migratePurchases(string $sql): void
    {
        $purchaseRows = $this->parseInserts($sql, 'compras');
        $detailRows = $this->parseInserts($sql, 'detallecompras');
        $this->logTableColumns($purchaseRows, 'compras');
        $this->logTableColumns($detailRows, 'detallecompras');
        $count = 0;

        foreach ($purchaseRows as $row) {
            $oldSupplierCode = $this->normalizeKey($row['codproveedor'] ?? '');
            $supplierId = $oldSupplierCode ? ($this->supplierMap[$oldSupplierCode] ?? null) : null;
            if (!$supplierId) {
                $this->addWarning('Compras', 'codproveedor', $oldSupplierCode);
                continue;
            }

            $tipoCompra = strtolower(trim($row['tipocompra'] ?? 'contado'));
            $isCredit = str_contains($tipoCompra, 'credito') || str_contains($tipoCompra, 'crédito');
            $total = (float) ($row['totalpago'] ?? 0);
            $creditPaid = (float) ($row['creditopagado'] ?? 0);
            $status = strtolower(trim($row['statuscompra'] ?? ''));

            $paymentStatus = 'paid';
            if ($isCredit) {
                if ($creditPaid >= $total) $paymentStatus = 'paid';
                elseif ($creditPaid > 0) $paymentStatus = 'partial';
                else $paymentStatus = 'pending';
            }

            $purchaseDate = null;
            try {
                $purchaseDate = !empty($row['fechaemision']) ? \Carbon\Carbon::parse($row['fechaemision']) : now();
            } catch (\Exception $e) { $purchaseDate = now(); }

            $purchase = Purchase::create([
                'purchase_number' => trim($row['codcompra']),
                'supplier_id' => $supplierId, 'branch_id' => $this->branchId,
                'user_id' => $this->resolveUserId($row['codigo'] ?? null),
                'supplier_invoice' => trim($row['codfactura'] ?? '') ?: null,
                'purchase_date' => $purchaseDate,
                'subtotal' => (float) ($row['subtotal'] ?? 0),
                'tax_amount' => (float) ($row['totaliva'] ?? 0),
                'discount_amount' => (float) ($row['totaldescuento'] ?? 0),
                'total' => $total,
                'status' => $status === 'anulada' ? 'cancelled' : 'completed',
                'payment_status' => $paymentStatus,
                'payment_type' => $isCredit ? 'credit' : 'cash',
                'credit_amount' => $isCredit ? $total : 0,
                'paid_amount' => $isCredit ? $creditPaid : $total,
                'notes' => trim($row['observaciones'] ?? '') ?: null,
                'created_at' => $purchaseDate,
            ]);

            $oldCode = $this->normalizeKey($row['codcompra']);
            $this->purchaseMap[$oldCode] = $purchase->id;

            $items = array_filter($detailRows, fn($d) => $this->normalizeKey($d['codcompra']) === $oldCode);
            foreach ($items as $item) {
                $oldProductId = (int) ($item['idproducto'] ?? 0);
                $productId = $this->productMap[$oldProductId] ?? null;
                if (!$productId) {
                    $this->addWarning('Detalle compras', 'idproducto', (string) $oldProductId);
                    continue;
                }

                $qty = (float) ($item['cantidad'] ?? 1);
                $unitCost = (float) ($item['preciocompra'] ?? 0);
                $taxRate = (float) ($item['ivaproducto'] ?? 0);
                $subtotal = $qty * $unitCost;
                $taxAmount = $subtotal * ($taxRate / 100);
                $discountAmount = (float) ($item['totaldescuentoc'] ?? 0);

                PurchaseItem::create([
                    'purchase_id' => $purchase->id, 'product_id' => $productId,
                    'quantity' => (int) $qty, 'unit_cost' => $unitCost,
                    'tax_rate' => $taxRate, 'tax_amount' => $taxAmount,
                    'discount' => $discountAmount, 'subtotal' => $subtotal,
                    'total' => $subtotal + $taxAmount - $discountAmount,
                ]);
            }
            $count++;
        }

        $this->stats['Compras'] = $count;
        $this->addLog('info', "Compras: {$count} migradas");
    }

    // ─── Step 15: Purchase Credit Payments ───
    private function migratePurchasePayments(string $sql): void
    {
        $rows = $this->parseInserts($sql, 'abonoscreditoscompras');
        $count = 0;
        $updatedPurchases = [];

        foreach ($rows as $row) {
            $oldPurchaseCode = $this->normalizeKey($row['codcompra'] ?? '');
            $purchaseId = $this->purchaseMap[$oldPurchaseCode] ?? null;
            if (!$purchaseId) continue;

            $amount = (float) ($row['montoabono'] ?? 0);
            if ($amount <= 0) continue;

            $paymentDate = null;
            try {
                $paymentDate = !empty($row['fechaabono']) ? \Carbon\Carbon::parse($row['fechaabono']) : now();
            } catch (\Exception $e) { $paymentDate = now(); }

            $purchase = Purchase::find($purchaseId);
            $supplierId = $purchase->supplier_id ?? null;
            $paymentMethodId = $this->getPaymentMethodId((int) ($row['formaabono'] ?? 0));

            CreditPayment::create([
                'payment_number' => trim($row['codabono'] ?? '') ?: CreditPayment::generatePaymentNumber(),
                'credit_type' => 'payable', 'purchase_id' => $purchaseId,
                'supplier_id' => $supplierId, 'branch_id' => $this->branchId,
                'user_id' => $this->userId, 'payment_method_id' => $paymentMethodId,
                'amount' => $amount, 'affects_cash' => false, 'created_at' => $paymentDate,
            ]);
            $updatedPurchases[$purchaseId] = true;
            $count++;
        }

        // For credit purchases with paid_amount > 0 but no/insufficient CreditPayment records,
        // create a CreditPayment to match the paid_amount from the old system
        $creditPurchasesWithPaid = Purchase::where('payment_type', 'credit')
            ->where('branch_id', $this->branchId)
            ->where('paid_amount', '>', 0)
            ->get();

        foreach ($creditPurchasesWithPaid as $purchase) {
            $existingPayments = (float) CreditPayment::where('purchase_id', $purchase->id)->sum('amount');
            $paidFromOldSystem = (float) $purchase->paid_amount;
            $difference = $paidFromOldSystem - $existingPayments;

            if ($difference > 0.01) {
                $defaultPaymentMethod = PaymentMethod::first();
                CreditPayment::create([
                    'payment_number' => CreditPayment::generatePaymentNumber(),
                    'credit_type' => 'payable',
                    'purchase_id' => $purchase->id,
                    'supplier_id' => $purchase->supplier_id,
                    'branch_id' => $this->branchId,
                    'user_id' => $this->userId,
                    'payment_method_id' => $defaultPaymentMethod->id ?? 1,
                    'amount' => $difference,
                    'affects_cash' => false,
                    'notes' => 'Abono importado del sistema anterior',
                    'created_at' => $purchase->created_at,
                ]);
                $updatedPurchases[$purchase->id] = true;
                $count++;
            }
        }

        // Update paid_amount and payment_status for purchases that received payments
        foreach (array_keys($updatedPurchases) as $purchaseId) {
            $purchase = Purchase::find($purchaseId);
            if (!$purchase || $purchase->payment_type !== 'credit') continue;

            $totalPaid = (float) CreditPayment::where('purchase_id', $purchaseId)->sum('amount');
            $creditAmount = (float) $purchase->credit_amount;
            $newStatus = $totalPaid >= $creditAmount ? 'paid' : ($totalPaid > 0 ? 'partial' : 'pending');

            $purchase->update([
                'paid_amount' => $totalPaid,
                'payment_status' => $newStatus,
            ]);
        }

        $this->stats['Abonos compras'] = $count;
        $this->addLog('info', "Abonos compras: {$count} migrados");
    }

    // ─── Step 16: Sales ───
    private function migrateSales(string $sql): void
    {
        $saleRows = $this->parseInserts($sql, 'ventas');
        $detailRows = $this->parseInserts($sql, 'detalleventas');
        $this->logTableColumns($saleRows, 'ventas');
        $this->logTableColumns($detailRows, 'detalleventas');
        $count = 0;

        foreach ($saleRows as $row) {
            $oldCustomerCode = $this->normalizeKey($row['codcliente'] ?? '');
            $customerId = $oldCustomerCode ? ($this->customerMap[$oldCustomerCode] ?? null) : null;
            if ($oldCustomerCode && !$customerId) $this->addWarning('Ventas', 'codcliente', $oldCustomerCode);

            $tipoPago = strtolower(trim($row['tipopago'] ?? 'contado'));
            $isCredit = str_contains($tipoPago, 'credito') || str_contains($tipoPago, 'crédito');
            $total = (float) ($row['totalpago'] ?? 0);
            $creditPaid = (float) ($row['creditopagado'] ?? 0);
            $statusVenta = strtolower(trim($row['statusventa'] ?? ''));

            $paymentStatus = 'paid';
            if ($isCredit) {
                if ($creditPaid >= $total) $paymentStatus = 'paid';
                elseif ($creditPaid > 0) $paymentStatus = 'partial';
                else $paymentStatus = 'pending';
            }

            $saleDate = null;
            try {
                $saleDate = !empty($row['fechaventa']) ? \Carbon\Carbon::parse($row['fechaventa']) : now();
            } catch (\Exception $e) { $saleDate = now(); }

            $sale = Sale::create([
                'branch_id' => $this->branchId,
                'cash_reconciliation_id' => null,
                'customer_id' => $customerId,
                'user_id' => $this->resolveUserId($row['codigo'] ?? null),
                'invoice_number' => trim($row['codventa']),
                'subtotal' => (float) ($row['subtotal'] ?? 0),
                'tax_total' => (float) ($row['totaliva'] ?? 0),
                'discount' => (float) ($row['totaldescuento'] ?? 0),
                'total' => $total,
                'status' => $statusVenta === 'anulada' ? 'cancelled' : 'completed',
                'payment_type' => $isCredit ? 'credit' : 'cash',
                'payment_status' => $paymentStatus,
                'credit_amount' => $isCredit ? $total : 0,
                'paid_amount' => $isCredit ? $creditPaid : $total,
                'notes' => trim($row['observaciones'] ?? '') ?: null,
                'is_electronic' => false,
                'created_at' => $saleDate, 'updated_at' => $saleDate,
            ]);

            $oldCode = $this->normalizeKey($row['codventa']);
            $this->saleMap[$oldCode] = $sale->id;

            $items = array_filter($detailRows, fn($d) => $this->normalizeKey($d['codventa']) === $oldCode);
            foreach ($items as $item) {
                $oldProductId = (int) ($item['idproducto'] ?? 0);
                $productId = $this->productMap[$oldProductId] ?? null;
                $childId = $this->productChildMap[$oldProductId] ?? null;
                $serviceId = $this->serviceMap[$oldProductId] ?? null;

                if (!$productId && !$serviceId) {
                    $this->addWarning('Detalle ventas', 'idproducto', (string) $oldProductId);
                }

                $qty = (float) ($item['cantidad'] ?? 1);
                $unitPrice = (float) ($item['precioventa'] ?? 0);
                $taxRate = (float) ($item['ivaproducto'] ?? 0);
                $subtotal = $qty * $unitPrice;
                $taxAmount = (float) ($item['subtotalimpuestos'] ?? 0);
                $discountAmount = (float) ($item['totaldescuentov'] ?? 0);
                $totalItem = (float) ($item['valorneto'] ?? ($subtotal + $taxAmount - $discountAmount));

                $saleItem = SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $serviceId ? null : $productId,
                    'product_child_id' => $serviceId ? null : $childId,
                    'service_id' => $serviceId,
                    'product_name' => trim($item['producto'] ?? ''),
                    'product_sku' => trim($item['codproducto'] ?? ''),
                    'unit_price' => $unitPrice, 'quantity' => $qty,
                    'tax_rate' => $taxRate, 'tax_amount' => $taxAmount,
                    'subtotal' => $subtotal, 'discount_amount' => $discountAmount,
                    'total' => $totalItem,
                ]);

                $oldDetailId = (int) ($item['coddetalleventa'] ?? 0);
                if ($oldDetailId > 0) {
                    $this->saleItemMap[$oldDetailId] = $saleItem->id;
                }
            }
            $count++;
        }

        // Mark customers with credit sales as has_credit = true
        $creditCustomerIds = Sale::where('payment_type', 'credit')
            ->where('branch_id', $this->branchId)
            ->whereNotNull('customer_id')
            ->pluck('customer_id')
            ->unique();

        Customer::whereIn('id', $creditCustomerIds)
            ->where('has_credit', false)
            ->update(['has_credit' => true]);

        $this->stats['Ventas'] = $count;
        $this->addLog('info', "Ventas: {$count} migradas");
    }

    // ─── Step 17: Sale Payments ───
    private function migrateSalePayments(string $sql): void
    {
        $rows = $this->parseInserts($sql, 'mediospagoxventas');
        $count = 0;

        foreach ($rows as $row) {
            $oldSaleCode = $this->normalizeKey($row['codventa'] ?? '');
            $saleId = $this->saleMap[$oldSaleCode] ?? null;
            if (!$saleId) continue;

            $amount = (float) ($row['montopagado'] ?? 0);
            if ($amount <= 0) continue;

            $paymentMethodId = $this->getPaymentMethodId((int) ($row['codmediopago'] ?? 0));

            SalePayment::create([
                'sale_id' => $saleId, 'payment_method_id' => $paymentMethodId, 'amount' => $amount,
            ]);
            $count++;
        }

        $this->stats['Pagos ventas'] = $count;
        $this->addLog('info', "Pagos de ventas: {$count} migrados");
    }

    // ─── Step 18: Credit Payments (Sales) ───
    private function migrateCreditPayments(string $sql): void
    {
        $rows = $this->parseInserts($sql, 'abonoscreditosventas');
        $count = 0;
        $updatedSales = [];

        foreach ($rows as $row) {
            $oldSaleCode = $this->normalizeKey($row['codventa'] ?? '');
            $saleId = $this->saleMap[$oldSaleCode] ?? null;
            if (!$saleId) continue;

            $amount = (float) ($row['montoabono'] ?? 0);
            if ($amount <= 0) continue;

            $sale = Sale::find($saleId);
            $customerId = $sale->customer_id ?? null;
            $paymentMethodId = $this->getPaymentMethodId((int) ($row['formaabono'] ?? 0));

            $paymentDate = null;
            try {
                $paymentDate = !empty($row['fechaabono']) ? \Carbon\Carbon::parse($row['fechaabono']) : now();
            } catch (\Exception $e) { $paymentDate = now(); }

            CreditPayment::create([
                'payment_number' => trim($row['codabono'] ?? '') ?: CreditPayment::generatePaymentNumber(),
                'credit_type' => 'receivable', 'sale_id' => $saleId,
                'customer_id' => $customerId, 'branch_id' => $this->branchId,
                'user_id' => $this->userId, 'payment_method_id' => $paymentMethodId,
                'amount' => $amount, 'affects_cash' => false, 'created_at' => $paymentDate,
            ]);
            $updatedSales[$saleId] = true;
            $count++;
        }

        // For credit sales with paid_amount > 0 but no CreditPayment records,
        // create a CreditPayment to match the paid_amount from the old system
        $creditSalesWithPaid = Sale::where('payment_type', 'credit')
            ->where('branch_id', $this->branchId)
            ->where('paid_amount', '>', 0)
            ->get();

        foreach ($creditSalesWithPaid as $sale) {
            $existingPayments = (float) CreditPayment::where('sale_id', $sale->id)->sum('amount');
            $paidFromOldSystem = (float) $sale->paid_amount;
            $difference = $paidFromOldSystem - $existingPayments;

            if ($difference > 0.01) {
                // Create a balancing CreditPayment for the untracked amount
                $defaultPaymentMethod = PaymentMethod::first();
                CreditPayment::create([
                    'payment_number' => CreditPayment::generatePaymentNumber(),
                    'credit_type' => 'receivable',
                    'sale_id' => $sale->id,
                    'customer_id' => $sale->customer_id,
                    'branch_id' => $this->branchId,
                    'user_id' => $this->userId,
                    'payment_method_id' => $defaultPaymentMethod->id ?? 1,
                    'amount' => $difference,
                    'affects_cash' => false,
                    'notes' => 'Abono importado del sistema anterior',
                    'created_at' => $sale->created_at,
                ]);
                $updatedSales[$sale->id] = true;
                $count++;
            }
        }

        // Update paid_amount and payment_status for sales that received payments
        foreach (array_keys($updatedSales) as $saleId) {
            $sale = Sale::find($saleId);
            if (!$sale || $sale->payment_type !== 'credit') continue;

            $totalPaid = (float) CreditPayment::where('sale_id', $saleId)->sum('amount');
            $creditAmount = (float) $sale->credit_amount;
            $newStatus = $totalPaid >= $creditAmount ? 'paid' : ($totalPaid > 0 ? 'partial' : 'pending');

            $sale->update([
                'paid_amount' => $totalPaid,
                'payment_status' => $newStatus,
            ]);
        }

        // Mark customers with credit sales as has_credit = true
        $creditCustomerIds = Sale::where('payment_type', 'credit')
            ->where('branch_id', $this->branchId)
            ->whereNotNull('customer_id')
            ->pluck('customer_id')
            ->unique();

        Customer::whereIn('id', $creditCustomerIds)
            ->where('has_credit', false)
            ->update(['has_credit' => true]);

        $this->stats['Abonos créditos'] = $count;
        $this->addLog('info', "Abonos créditos ventas: {$count} migrados");
    }

    // ─── Step 19: Refunds (from notascredito) ───
    private function migrateRefunds(string $sql): void
    {
        $notaRows = $this->parseInserts($sql, 'notascredito');
        $detailRows = $this->parseInserts($sql, 'detallenotas');
        $count = 0;

        foreach ($notaRows as $row) {
            $oldSaleCode = $this->normalizeKey($row['facturaventa'] ?? '');
            $saleId = $this->saleMap[$oldSaleCode] ?? null;
            if (!$saleId) {
                $this->addWarning('Devoluciones', 'facturaventa', $oldSaleCode);
                continue;
            }

            $total = (float) ($row['totalpago'] ?? 0);
            if ($total <= 0) continue;

            $refundDate = null;
            try {
                $refundDate = !empty($row['fechanota']) ? \Carbon\Carbon::parse($row['fechanota']) : now();
            } catch (\Exception $e) { $refundDate = now(); }

            $refund = Refund::create([
                'sale_id' => $saleId, 'branch_id' => $this->branchId,
                'user_id' => $this->resolveUserId($row['codigo'] ?? null),
                'number' => trim($row['codnota'] ?? '') ?: Refund::generateNumber($this->branchId),
                'type' => 'total', 'reason' => 'Migrado del sistema anterior',
                'subtotal' => (float) ($row['subtotal'] ?? 0),
                'tax_total' => (float) ($row['totaliva'] ?? 0),
                'total' => $total, 'status' => 'completed',
                'created_at' => $refundDate, 'updated_at' => $refundDate,
            ]);

            $oldNotaCode = $this->normalizeKey($row['codnota']);
            $items = array_filter($detailRows, fn($d) => $this->normalizeKey($d['codnota']) === $oldNotaCode);
            $hasItems = false;

            foreach ($items as $item) {
                $oldProductId = (int) ($item['idproducto'] ?? 0);
                $productId = $this->productMap[$oldProductId] ?? null;

                $qty = (float) ($item['cantidad'] ?? 1);
                $unitPrice = (float) ($item['precioventa'] ?? 0);
                $taxRate = (float) ($item['ivaproducto'] ?? 0);
                $subtotal = $qty * $unitPrice;
                $taxAmount = (float) ($item['subtotalimpuestos'] ?? 0);
                $totalItem = (float) ($item['valorneto'] ?? ($subtotal + $taxAmount));

                RefundItem::create([
                    'refund_id' => $refund->id, 'product_id' => $productId,
                    'product_name' => trim($item['producto'] ?? ''),
                    'product_sku' => trim($item['codproducto'] ?? ''),
                    'unit_price' => $unitPrice, 'quantity' => $qty, 'original_quantity' => $qty,
                    'tax_rate' => $taxRate, 'tax_amount' => $taxAmount,
                    'subtotal' => $subtotal, 'total' => $totalItem,
                ]);
                $hasItems = true;
            }

            if ($hasItems) {
                $sale = Sale::find($saleId);
                $saleTotal = $sale ? (float) $sale->total : 0;
                if ($total < $saleTotal && $saleTotal > 0) {
                    $refund->update(['type' => 'partial']);
                }
            }
            $count++;
        }

        $this->stats['Devoluciones'] = $count;
        $this->addLog('info', "Devoluciones: {$count} migradas");
    }
}

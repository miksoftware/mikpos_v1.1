<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SeedPending extends Command
{
    protected $signature = 'db:seed-pending {--force : Force the operation to run in production}';
    protected $description = 'Run pending seeders that have not been executed yet';

    /**
     * List of seeders to track (in order of execution).
     * Add new seeders here when created.
     */
    protected array $trackedSeeders = [
        'RolesAndPermissionsSeeder',
        'DepartmentSeeder',
        'MunicipalitySeeder',
        'TaxDocumentsSeeder',
        'PaymentMethodsSeeder',
        'SystemDocumentsSeeder',
        'ProductCatalogPermissionsSeeder',
        'CustomerModuleSeeder',
        'SupplierModuleSeeder',
        'ProductsModuleSeeder',
        'CombosModuleSeeder',
        'PurchasesModuleSeeder',
        'CashRegistersModuleSeeder',
        'CashReconciliationsModuleSeeder',
        'InventoryAdjustmentsModuleSeeder',
        'InventoryTransfersModuleSeeder',
        'BillingSettingsModuleSeeder',
        'SalesModuleSeeder',
        'ServicesModuleSeeder',
        'ReportsModuleSeeder',
        'CommissionsReportPermissionSeeder',
        'KardexReportPermissionSeeder',
        'SalesBookReportPermissionSeeder',
        // Add new seeders here
    ];

    public function handle(): int
    {
        if (!$this->option('force') && app()->environment('production')) {
            if (!$this->confirm('Are you sure you want to run seeders in production?')) {
                return 1;
            }
        }

        // Ensure seeder_history table exists
        if (!Schema::hasTable('seeder_history')) {
            $this->error('Table seeder_history does not exist. Run migrations first.');
            return 1;
        }

        $executedSeeders = DB::table('seeder_history')->pluck('seeder')->toArray();
        $pendingSeeders = array_diff($this->trackedSeeders, $executedSeeders);

        if (empty($pendingSeeders)) {
            $this->info('No pending seeders to run.');
            return 0;
        }

        $batch = (DB::table('seeder_history')->max('batch') ?? 0) + 1;

        $this->info('Running ' . count($pendingSeeders) . ' pending seeder(s)...');
        $this->newLine();

        foreach ($pendingSeeders as $seederName) {
            $seederClass = "Database\\Seeders\\{$seederName}";

            if (!class_exists($seederClass)) {
                $this->warn("⚠ Seeder class not found: {$seederName}");
                continue;
            }

            $this->info("▶ Running: {$seederName}");

            try {
                $seeder = new $seederClass();
                $seeder->run();

                DB::table('seeder_history')->insert([
                    'seeder' => $seederName,
                    'batch' => $batch,
                    'executed_at' => now(),
                ]);

                $this->info("  ✓ Completed: {$seederName}");
            } catch (\Exception $e) {
                $this->error("  ✗ Failed: {$seederName}");
                $this->error("    Error: " . $e->getMessage());
                return 1;
            }
        }

        $this->newLine();
        $this->info('✅ All pending seeders executed successfully!');

        return 0;
    }
}

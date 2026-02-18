<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SeedMarkExecuted extends Command
{
    protected $signature = 'db:seed-mark-executed {--all : Mark all tracked seeders as executed}';
    protected $description = 'Mark seeders as already executed (for initial setup)';

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
        'WeightUnitsSeeder',
        'ProfitLossReportPermissionSeeder',
        'CreditsModuleSeeder',
        'CreditsReportPermissionSeeder',
        'CashReconciliationEditPermissionSeeder',
        'RefundSystemDocumentSeeder',
        'PurchasesReportPermissionSeeder',
        'CashReportPermissionSeeder',
        'MigrationModuleSeeder',
    ];

    public function handle(): int
    {
        if (!Schema::hasTable('seeder_history')) {
            $this->error('Table seeder_history does not exist. Run migrations first.');
            return 1;
        }

        if (!$this->option('all')) {
            $this->info('Use --all to mark all existing seeders as executed.');
            $this->info('This is useful after initial setup to avoid re-running seeders.');
            return 0;
        }

        $executedSeeders = DB::table('seeder_history')->pluck('seeder')->toArray();
        $toMark = array_diff($this->trackedSeeders, $executedSeeders);

        if (empty($toMark)) {
            $this->info('All seeders are already marked as executed.');
            return 0;
        }

        $this->info('Marking ' . count($toMark) . ' seeder(s) as executed...');

        foreach ($toMark as $seederName) {
            DB::table('seeder_history')->insert([
                'seeder' => $seederName,
                'batch' => 0, // Batch 0 = initial/manual mark
                'executed_at' => now(),
            ]);
            $this->info("  ✓ Marked: {$seederName}");
        }

        $this->newLine();
        $this->info('✅ Done! Future deploys will only run new seeders.');

        return 0;
    }
}

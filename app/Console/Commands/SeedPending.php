<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SeedPending extends Command
{
    protected $signature = 'db:seed-pending {--force : Force the operation to run in production}';
    protected $description = 'Run pending seeders that have not been executed yet using automatic discovery';

    /**
     * Priority seeders that must run first if pending.
     */
    protected array $prioritySeeders = [
        'RolesAndPermissionsSeeder',
        'DepartmentSeeder',
        'MunicipalitySeeder',
        'TaxDocumentsSeeder',
        'PaymentMethodsSeeder',
        'SystemDocumentsSeeder',
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

        // 1. Auto-discover all seeders in database/seeders/
        $allSeeders = $this->discoverSeeders();

        $executedSeeders = DB::table('seeder_history')->pluck('seeder')->toArray();
        $pendingSeeders = array_values(array_diff($allSeeders, $executedSeeders));

        if (empty($pendingSeeders)) {
            $this->info('No pending seeders to run.');
        } else {
            $batch = (DB::table('seeder_history')->max('batch') ?? 0) + 1;

            $this->info('Found ' . count($pendingSeeders) . ' pending seeder(s) via automatic discovery:');
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
        }

        // 2. Automatically sync all permission & module seeders (idempotent loop)
        $this->syncPermissionsAndModules();

        return 0;
    }

    /**
     * Automatically discover all Seeder classes in database/seeders directory.
     */
    private function discoverSeeders(): array
    {
        $files = glob(database_path('seeders/*Seeder.php'));
        $discovered = [];

        foreach ($files as $file) {
            $name = basename($file, '.php');
            if ($name === 'DatabaseSeeder') {
                continue;
            }
            $discovered[] = $name;
        }

        // Order priority seeders first, followed by the rest alphabetically
        $ordered = [];
        foreach ($this->prioritySeeders as $p) {
            if (in_array($p, $discovered)) {
                $ordered[] = $p;
            }
        }

        $remaining = array_diff($discovered, $ordered);
        sort($remaining);

        return array_merge($ordered, $remaining);
    }

    /**
     * Run all Module and Permission seeders to ensure no permissions are missing.
     * All of these use firstOrCreate and syncWithoutDetaching, making them 100% safe.
     */
    private function syncPermissionsAndModules(): void
    {
        $this->info('🔄 Verificando y sincronizando permisos y módulos en la base de datos...');

        $files = glob(database_path('seeders/*Seeder.php'));
        $count = 0;

        foreach ($files as $file) {
            $name = basename($file, '.php');
            if ($name === 'DatabaseSeeder') {
                continue;
            }

            // Target seeders related to permissions, modules, or documents
            if (
                str_ends_with($name, 'PermissionSeeder') ||
                str_ends_with($name, 'PermissionsSeeder') ||
                str_ends_with($name, 'ModuleSeeder') ||
                str_ends_with($name, 'RoleSeeder') ||
                $name === 'RolesAndPermissionsSeeder'
            ) {
                $class = "Database\\Seeders\\{$name}";
                if (class_exists($class)) {
                    try {
                        $seeder = new $class();
                        $seeder->run();
                        $count++;
                    } catch (\Throwable $e) {
                        $this->warn("  ⚠ Advertencia en {$name}: " . $e->getMessage());
                    }
                }
            }
        }

        $this->info("✓ {$count} archivos de módulos y permisos verificados e integrados correctamente.");
    }
}

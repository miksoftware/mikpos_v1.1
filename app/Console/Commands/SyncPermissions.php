<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SyncPermissions extends Command
{
    protected $signature = 'permissions:sync {--force : Force the operation in production}';
    protected $description = 'Scan all permission and module seeders in database/seeders and sync any missing permissions in the database';

    public function handle(): int
    {
        $this->info('🔍 Escaneando seeders de permisos y módulos en database/seeders/...');

        $files = glob(database_path('seeders/*Seeder.php'));
        $syncedCount = 0;

        foreach ($files as $file) {
            $name = basename($file, '.php');
            if ($name === 'DatabaseSeeder') {
                continue;
            }

            // Target all seeders that define permissions, modules, or roles
            if (
                str_ends_with($name, 'PermissionSeeder') ||
                str_ends_with($name, 'PermissionsSeeder') ||
                str_ends_with($name, 'ModuleSeeder') ||
                str_ends_with($name, 'RoleSeeder') ||
                $name === 'RolesAndPermissionsSeeder'
            ) {
                $seederClass = "Database\\Seeders\\{$name}";

                if (class_exists($seederClass)) {
                    $this->line("  ▶ Sincronizando: <comment>{$name}</comment>");
                    try {
                        $seeder = new $seederClass();
                        $seeder->run();
                        $syncedCount++;
                    } catch (\Throwable $e) {
                        $this->warn("    ⚠ Error en {$name}: " . $e->getMessage());
                    }
                }
            }
        }

        $this->newLine();
        $this->info("✅ ¡Sincronización completada! Se procesaron {$syncedCount} archivos de permisos y módulos.");

        return 0;
    }
}

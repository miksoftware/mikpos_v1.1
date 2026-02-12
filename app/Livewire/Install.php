<?php

namespace App\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Component;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

#[Layout('layouts.install')]
class Install extends Component
{
    public $currentStep = 1;
    public $totalSteps = 4;
    
    // Step 1: Requirements
    public $requirements = [];
    public $requirementsPassed = false;
    
    // Step 2: Database
    public $db_host = '127.0.0.1';
    public $db_port = '3306';
    public $db_database = 'mikpos';
    public $db_username = 'root';
    public $db_password = '';
    public $dbConnectionTested = false;
    public $dbConnectionError = '';
    
    // Step 3: Application
    public $app_name = 'MikPOS';
    public $app_url = '';
    
    // Step 4: Branch & Admin
    public $branch_name = '';
    public $branch_code = 'SUC001';
    public $branch_address = '';
    public $branch_phone = '';
    public $branch_email = '';
    public $branch_nit = '';
    
    public $admin_name = '';
    public $admin_email = '';
    public $admin_password = '';
    public $admin_password_confirmation = '';
    
    // Installation progress
    public $isInstalling = false;
    public $installationProgress = 0;
    public $installationStatus = '';
    public $installationError = '';
    public $installationComplete = false;

    public function mount()
    {
        // Check if already installed
        if ($this->isInstalled()) {
            return redirect('/login');
        }
        
        $this->app_url = url('/');
        $this->checkRequirements();
    }

    protected function isInstalled(): bool
    {
        $lockFile = storage_path('installed.lock');
        return File::exists($lockFile);
    }

    protected function checkRequirements(): void
    {
        $this->requirements = [];
        
        $checks = [
            ['name' => 'PHP >= 8.2', 'passed' => version_compare(PHP_VERSION, '8.2.0', '>='), 'current' => PHP_VERSION],
            ['name' => 'PDO Extension', 'passed' => extension_loaded('pdo'), 'current' => extension_loaded('pdo') ? 'Instalado' : 'No instalado'],
            ['name' => 'PDO MySQL Extension', 'passed' => extension_loaded('pdo_mysql'), 'current' => extension_loaded('pdo_mysql') ? 'Instalado' : 'No instalado'],
            ['name' => 'Mbstring Extension', 'passed' => extension_loaded('mbstring'), 'current' => extension_loaded('mbstring') ? 'Instalado' : 'No instalado'],
            ['name' => 'OpenSSL Extension', 'passed' => extension_loaded('openssl'), 'current' => extension_loaded('openssl') ? 'Instalado' : 'No instalado'],
            ['name' => 'Tokenizer Extension', 'passed' => extension_loaded('tokenizer'), 'current' => extension_loaded('tokenizer') ? 'Instalado' : 'No instalado'],
            ['name' => 'JSON Extension', 'passed' => extension_loaded('json'), 'current' => extension_loaded('json') ? 'Instalado' : 'No instalado'],
            ['name' => 'cURL Extension', 'passed' => extension_loaded('curl'), 'current' => extension_loaded('curl') ? 'Instalado' : 'No instalado'],
            ['name' => 'Fileinfo Extension', 'passed' => extension_loaded('fileinfo'), 'current' => extension_loaded('fileinfo') ? 'Instalado' : 'No instalado'],
            ['name' => 'Directorio storage/ escribible', 'passed' => is_writable(storage_path()), 'current' => is_writable(storage_path()) ? 'Escribible' : 'No escribible'],
            ['name' => 'Directorio bootstrap/cache/ escribible', 'passed' => is_writable(base_path('bootstrap/cache')), 'current' => is_writable(base_path('bootstrap/cache')) ? 'Escribible' : 'No escribible'],
            ['name' => 'Archivo .env escribible', 'passed' => is_writable(base_path('.env')) || is_writable(base_path()), 'current' => (is_writable(base_path('.env')) || is_writable(base_path())) ? 'Escribible' : 'No escribible'],
        ];

        foreach ($checks as $check) {
            $this->requirements[] = [
                'name' => $check['name'],
                'passed' => (bool) $check['passed'],
                'current' => $check['current'],
            ];
        }

        $this->requirementsPassed = collect($this->requirements)->every(fn($req) => $req['passed'] === true);
    }

    public function testDatabaseConnection()
    {
        $this->dbConnectionTested = false;
        $this->dbConnectionError = '';

        try {
            $pdo = new \PDO(
                "mysql:host={$this->db_host};port={$this->db_port}",
                $this->db_username,
                $this->db_password,
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );

            // Try to create database if not exists
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$this->db_database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            
            // Test connection to the database
            $pdo->exec("USE `{$this->db_database}`");

            $this->dbConnectionTested = true;
            $this->dispatch('notify', message: 'Conexión exitosa a la base de datos', type: 'success');
        } catch (\PDOException $e) {
            $this->dbConnectionError = $e->getMessage();
            $this->dispatch('notify', message: 'Error de conexión: ' . $e->getMessage(), type: 'error');
        }
    }

    public function nextStep()
    {
        if ($this->currentStep === 1) {
            // Recheck requirements before advancing
            $this->checkRequirements();
            
            if (!$this->requirementsPassed) {
                $failed = collect($this->requirements)->filter(fn($req) => !$req['passed'])->pluck('name')->implode(', ');
                $this->dispatch('notify', message: 'Requisitos faltantes: ' . ($failed ?: 'Verificación fallida'), type: 'error');
                return;
            }
        }

        if ($this->currentStep === 2) {
            if (!$this->dbConnectionTested) {
                $this->dispatch('notify', message: 'Debe probar la conexión a la base de datos', type: 'error');
                return;
            }
        }

        if ($this->currentStep === 3) {
            $this->validate([
                'app_name' => 'required|min:2',
                'app_url' => 'required|url',
            ], [
                'app_name.required' => 'El nombre de la aplicación es requerido',
                'app_url.required' => 'La URL de la aplicación es requerida',
                'app_url.url' => 'La URL debe ser válida',
            ]);
        }

        if ($this->currentStep < $this->totalSteps) {
            $this->currentStep++;
        }
    }

    public function previousStep()
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    public function install()
    {
        $this->validate([
            'branch_name' => 'required|min:2',
            'branch_code' => 'required|min:2',
            'admin_name' => 'required|min:2',
            'admin_email' => 'required|email',
            'admin_password' => 'required|min:8|confirmed',
        ], [
            'branch_name.required' => 'El nombre de la sucursal es requerido',
            'branch_code.required' => 'El código de la sucursal es requerido',
            'admin_name.required' => 'El nombre del administrador es requerido',
            'admin_email.required' => 'El email del administrador es requerido',
            'admin_email.email' => 'El email debe ser válido',
            'admin_password.required' => 'La contraseña es requerida',
            'admin_password.min' => 'La contraseña debe tener al menos 8 caracteres',
            'admin_password.confirmed' => 'Las contraseñas no coinciden',
        ]);

        $this->isInstalling = true;
        $this->installationProgress = 0;
        $this->installationError = '';

        try {
            // Step 1: Update .env file
            $this->updateStatus('Configurando archivo .env...', 10);
            $this->updateEnvFile();

            // Step 2: Clear config cache
            $this->updateStatus('Limpiando caché...', 20);
            Artisan::call('config:clear');
            
            // Reconnect to database with new config
            config([
                'database.connections.mysql.host' => $this->db_host,
                'database.connections.mysql.port' => $this->db_port,
                'database.connections.mysql.database' => $this->db_database,
                'database.connections.mysql.username' => $this->db_username,
                'database.connections.mysql.password' => $this->db_password,
            ]);
            DB::purge('mysql');
            DB::reconnect('mysql');

            // Step 3: Run migrations
            $this->updateStatus('Ejecutando migraciones...', 30);
            Artisan::call('migrate', ['--force' => true]);

            // Step 4: Run essential seeders
            $this->updateStatus('Creando roles y permisos...', 40);
            Artisan::call('db:seed', ['--class' => 'RolesAndPermissionsSeeder', '--force' => true]);

            $this->updateStatus('Cargando departamentos...', 50);
            Artisan::call('db:seed', ['--class' => 'DepartmentSeeder', '--force' => true]);

            $this->updateStatus('Cargando municipios...', 60);
            Artisan::call('db:seed', ['--class' => 'MunicipalitySeeder', '--force' => true]);

            $this->updateStatus('Configurando métodos de pago...', 70);
            Artisan::call('db:seed', ['--class' => 'PaymentMethodsSeeder', '--force' => true]);

            $this->updateStatus('Configurando documentos tributarios...', 72);
            Artisan::call('db:seed', ['--class' => 'TaxDocumentsSeeder', '--force' => true]);

            $this->updateStatus('Configurando permisos de catálogo...', 74);
            Artisan::call('db:seed', ['--class' => 'ProductCatalogPermissionsSeeder', '--force' => true]);

            $this->updateStatus('Configurando permisos de clientes...', 75);
            Artisan::call('db:seed', ['--class' => 'CustomerModuleSeeder', '--force' => true]);

            $this->updateStatus('Configurando permisos de proveedores...', 76);
            Artisan::call('db:seed', ['--class' => 'SupplierModuleSeeder', '--force' => true]);

            $this->updateStatus('Configurando permisos de productos...', 77);
            Artisan::call('db:seed', ['--class' => 'ProductsModuleSeeder', '--force' => true]);

            $this->updateStatus('Configurando permisos de combos...', 78);
            Artisan::call('db:seed', ['--class' => 'CombosModuleSeeder', '--force' => true]);

            $this->updateStatus('Configurando permisos de compras...', 79);
            Artisan::call('db:seed', ['--class' => 'PurchasesModuleSeeder', '--force' => true]);

            $this->updateStatus('Configurando permisos de inventario...', 80);
            Artisan::call('db:seed', ['--class' => 'InventoryAdjustmentsModuleSeeder', '--force' => true]);
            Artisan::call('db:seed', ['--class' => 'InventoryTransfersModuleSeeder', '--force' => true]);

            $this->updateStatus('Configurando permisos de cajas...', 82);
            Artisan::call('db:seed', ['--class' => 'CashRegistersModuleSeeder', '--force' => true]);
            Artisan::call('db:seed', ['--class' => 'CashReconciliationsModuleSeeder', '--force' => true]);

            $this->updateStatus('Configurando permisos de ventas...', 84);
            Artisan::call('db:seed', ['--class' => 'SalesModuleSeeder', '--force' => true]);

            $this->updateStatus('Configurando permisos de facturación...', 85);
            Artisan::call('db:seed', ['--class' => 'BillingSettingsModuleSeeder', '--force' => true]);

            $this->updateStatus('Configurando documentos del sistema...', 87);
            Artisan::call('db:seed', ['--class' => 'SystemDocumentsSeeder', '--force' => true]);

            $this->updateStatus('Configurando permisos de servicios...', 88);
            Artisan::call('db:seed', ['--class' => 'ServicesModuleSeeder', '--force' => true]);

            $this->updateStatus('Configurando permisos de reportes...', 89);
            Artisan::call('db:seed', ['--class' => 'ReportsModuleSeeder', '--force' => true]);
            Artisan::call('db:seed', ['--class' => 'CommissionsReportPermissionSeeder', '--force' => true]);
            Artisan::call('db:seed', ['--class' => 'KardexReportPermissionSeeder', '--force' => true]);
            Artisan::call('db:seed', ['--class' => 'SalesBookReportPermissionSeeder', '--force' => true]);

            $this->updateStatus('Configurando unidades de peso...', 90);
            Artisan::call('db:seed', ['--class' => 'WeightUnitsSeeder', '--force' => true]);

            $this->updateStatus('Configurando reporte P&G...', 91);
            Artisan::call('db:seed', ['--class' => 'ProfitLossReportPermissionSeeder', '--force' => true]);

            // Step 5: Create branch
            $this->updateStatus('Creando sucursal...', 92);
            $branch = \App\Models\Branch::create([
                'code' => $this->branch_code,
                'name' => $this->branch_name,
                'address' => $this->branch_address ?: null,
                'phone' => $this->branch_phone ?: null,
                'email' => $this->branch_email ?: null,
                'nit' => $this->branch_nit ?: null,
                'ticket_prefix' => 'T001-',
                'invoice_prefix' => 'F001-',
                'show_in_pos' => true,
                'is_active' => true,
            ]);

            // Step 6: Create super admin
            $this->updateStatus('Creando usuario administrador...', 94);
            $superAdminRole = \App\Models\Role::where('name', 'super_admin')->first();
            
            $admin = \App\Models\User::create([
                'name' => $this->admin_name,
                'email' => $this->admin_email,
                'password' => Hash::make($this->admin_password),
                'branch_id' => null,
                'is_active' => true,
            ]);
            $admin->roles()->attach($superAdminRole->id, ['branch_id' => null]);

            // Step 7: Create storage link
            $this->updateStatus('Creando enlace de almacenamiento...', 96);
            if (!File::exists(public_path('storage'))) {
                Artisan::call('storage:link');
            }

            // Step 8: Create lock file
            $this->updateStatus('Finalizando instalación...', 98);
            File::put(storage_path('installed.lock'), now()->toDateTimeString());

            // Clear all caches
            $this->updateStatus('Limpiando cachés...', 100);
            Artisan::call('config:clear');
            Artisan::call('cache:clear');
            Artisan::call('view:clear');

            $this->installationComplete = true;
            $this->dispatch('notify', message: 'Instalación completada exitosamente', type: 'success');

        } catch (\Exception $e) {
            $this->installationError = $e->getMessage();
            $this->dispatch('notify', message: 'Error en la instalación: ' . $e->getMessage(), type: 'error');
        }

        $this->isInstalling = false;
    }

    protected function updateStatus(string $status, int $progress): void
    {
        $this->installationStatus = $status;
        $this->installationProgress = $progress;
    }

    protected function updateEnvFile(): void
    {
        $envPath = base_path('.env');
        $envContent = File::get($envPath);

        // Generate app key if not exists
        $appKey = config('app.key');
        if (empty($appKey)) {
            $appKey = 'base64:' . base64_encode(random_bytes(32));
        }

        $replacements = [
            'APP_NAME' => $this->app_name,
            'APP_URL' => $this->app_url,
            'APP_KEY' => $appKey,
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $this->db_host,
            'DB_PORT' => $this->db_port,
            'DB_DATABASE' => $this->db_database,
            'DB_USERNAME' => $this->db_username,
            'DB_PASSWORD' => $this->db_password,
        ];

        foreach ($replacements as $key => $value) {
            // Handle values with spaces or special characters
            $escapedValue = $value;
            if (preg_match('/\s|#|"/', $value)) {
                $escapedValue = '"' . addslashes($value) . '"';
            }

            if (preg_match("/^{$key}=.*/m", $envContent)) {
                $envContent = preg_replace("/^{$key}=.*/m", "{$key}={$escapedValue}", $envContent);
            } else {
                $envContent .= "\n{$key}={$escapedValue}";
            }
        }

        File::put($envPath, $envContent);
    }

    public function render()
    {
        return view('livewire.install');
    }
}

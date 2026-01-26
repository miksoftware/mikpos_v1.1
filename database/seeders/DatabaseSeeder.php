<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // First, seed roles and permissions
        $this->call(RolesAndPermissionsSeeder::class);

        // Seed Colombian geographic data
        $this->call(DepartmentSeeder::class);
        $this->call(MunicipalitySeeder::class);

        // Seed DIAN configuration data
        $this->call(PaymentMethodsSeeder::class);
        $this->call(TaxDocumentsSeeder::class);

        // Create test branches
        $mainBranch = Branch::create([
            'code' => 'SUC001',
            'name' => 'Sucursal Principal',
            'tax_id' => '20-12345678-9',
            'province' => 'Buenos Aires',
            'city' => 'Capital Federal',
            'address' => 'Av. Principal #123',
            'phone' => '+54 11 1234-5678',
            'email' => 'principal@mikpos.com',
            'ticket_prefix' => 'T001-',
            'invoice_prefix' => 'F001-',
            'show_in_pos' => true,
            'is_active' => true,
        ]);

        $secondBranch = Branch::create([
            'code' => 'SUC002',
            'name' => 'Sucursal Norte',
            'province' => 'Buenos Aires',
            'city' => 'Vicente López',
            'address' => 'Av. del Libertador #456',
            'phone' => '+54 11 8765-4321',
            'email' => 'norte@mikpos.com',
            'ticket_prefix' => 'T002-',
            'invoice_prefix' => 'F002-',
            'show_in_pos' => true,
            'is_active' => true,
        ]);

        // Get roles
        $superAdminRole = Role::where('name', 'super_admin')->first();
        $branchAdminRole = Role::where('name', 'branch_admin')->first();
        $cashierRole = Role::where('name', 'cashier')->first();

        // Create super admin
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@mikpos.com',
            'password' => bcrypt('password'),
            'branch_id' => null,
            'is_active' => true,
        ]);
        $superAdmin->roles()->attach($superAdminRole->id, ['branch_id' => null]);

        // Create branch admin for main branch
        $branchAdmin = User::create([
            'name' => 'Admin Sucursal Principal',
            'email' => 'branch@mikpos.com',
            'password' => bcrypt('password'),
            'branch_id' => $mainBranch->id,
            'is_active' => true,
        ]);
        $branchAdmin->roles()->attach($branchAdminRole->id, ['branch_id' => $mainBranch->id]);

        // Create cashier
        $cashier = User::create([
            'name' => 'Cajero Demo',
            'email' => 'cajero@mikpos.com',
            'password' => bcrypt('password'),
            'branch_id' => $mainBranch->id,
            'is_active' => true,
        ]);
        $cashier->roles()->attach($cashierRole->id, ['branch_id' => $mainBranch->id]);
    }
}

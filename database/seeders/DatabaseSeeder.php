<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create a test branch
        $branch = \App\Models\Branch::create([
            'name' => 'Sucursal Principal',
            'code' => 'SUC001',
            'address' => 'Calle Principal #123',
            'phone' => '+1234567890',
            'email' => 'principal@mikpos.com',
            'is_active' => true,
        ]);

        // Create super admin (no branch required)
        User::create([
            'name' => 'Super Admin',
            'email' => 'admin@mikpos.com',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
            'branch_id' => null,
            'is_active' => true,
        ]);

        // Create branch admin
        User::create([
            'name' => 'Admin Sucursal',
            'email' => 'branch@mikpos.com',
            'password' => bcrypt('password'),
            'role' => 'branch_admin',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);

        // Create cashier user
        User::create([
            'name' => 'Cajero Demo',
            'email' => 'cajero@mikpos.com',
            'password' => bcrypt('password'),
            'role' => 'cashier',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
    }
}

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

        // Create admin user
        User::create([
            'name' => 'Admin MikPOS',
            'email' => 'admin@mikpos.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
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

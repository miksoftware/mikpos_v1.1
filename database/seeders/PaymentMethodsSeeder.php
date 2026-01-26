<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodsSeeder extends Seeder
{
    /**
     * Seed payment methods with DIAN codes for Colombia.
     * These codes are required for electronic invoicing (Factus/DIAN).
     */
    public function run(): void
    {
        $paymentMethods = [
            // Efectivo - Código DIAN: 10
            ['dian_code' => '10', 'name' => 'Efectivo', 'is_active' => true],
            
            // Transferencias Bancarias - Código DIAN: 47
            ['dian_code' => '47', 'name' => 'Nequi', 'is_active' => true],
            ['dian_code' => '47', 'name' => 'Daviplata', 'is_active' => true],
            ['dian_code' => '47', 'name' => 'PSE', 'is_active' => true],
            ['dian_code' => '47', 'name' => 'Transferencia Bancolombia', 'is_active' => true],
            
            // Tarjetas - Códigos DIAN: 48 (Crédito), 49 (Débito)
            ['dian_code' => '48', 'name' => 'Tarjeta Crédito', 'is_active' => true],
            ['dian_code' => '49', 'name' => 'Tarjeta Débito', 'is_active' => true],
            
            // Consignación - Código DIAN: 42
            ['dian_code' => '42', 'name' => 'Consignación Bancaria', 'is_active' => true],
            
            // Bonos/Vales - Códigos DIAN: 71, 72
            ['dian_code' => '71', 'name' => 'Bono Regalo', 'is_active' => false],
            ['dian_code' => '72', 'name' => 'Vale', 'is_active' => false],
        ];

        foreach ($paymentMethods as $method) {
            PaymentMethod::firstOrCreate(
                ['name' => $method['name']],
                $method
            );
        }
    }
}

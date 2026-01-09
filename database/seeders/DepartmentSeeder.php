<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['name' => 'Amazonas', 'dian_code' => '91'],
            ['name' => 'Antioquia', 'dian_code' => '05'],
            ['name' => 'Arauca', 'dian_code' => '81'],
            ['name' => 'Atlántico', 'dian_code' => '08'],
            ['name' => 'Bogotá D.C.', 'dian_code' => '11'],
            ['name' => 'Bolívar', 'dian_code' => '13'],
            ['name' => 'Boyacá', 'dian_code' => '15'],
            ['name' => 'Caldas', 'dian_code' => '17'],
            ['name' => 'Caquetá', 'dian_code' => '18'],
            ['name' => 'Casanare', 'dian_code' => '85'],
            ['name' => 'Cauca', 'dian_code' => '19'],
            ['name' => 'Cesar', 'dian_code' => '20'],
            ['name' => 'Chocó', 'dian_code' => '27'],
            ['name' => 'Córdoba', 'dian_code' => '23'],
            ['name' => 'Cundinamarca', 'dian_code' => '25'],
            ['name' => 'Guainía', 'dian_code' => '94'],
            ['name' => 'Guaviare', 'dian_code' => '95'],
            ['name' => 'Huila', 'dian_code' => '41'],
            ['name' => 'La Guajira', 'dian_code' => '44'],
            ['name' => 'Magdalena', 'dian_code' => '47'],
            ['name' => 'Meta', 'dian_code' => '50'],
            ['name' => 'Nariño', 'dian_code' => '52'],
            ['name' => 'Norte de Santander', 'dian_code' => '54'],
            ['name' => 'Putumayo', 'dian_code' => '86'],
            ['name' => 'Quindío', 'dian_code' => '63'],
            ['name' => 'Risaralda', 'dian_code' => '66'],
            ['name' => 'San Andrés y Providencia', 'dian_code' => '88'],
            ['name' => 'Santander', 'dian_code' => '68'],
            ['name' => 'Sucre', 'dian_code' => '70'],
            ['name' => 'Tolima', 'dian_code' => '73'],
            ['name' => 'Valle del Cauca', 'dian_code' => '76'],
            ['name' => 'Vaupés', 'dian_code' => '97'],
            ['name' => 'Vichada', 'dian_code' => '99'],
        ];

        foreach ($departments as $department) {
            Department::create([
                'name' => $department['name'],
                'dian_code' => $department['dian_code'],
                'is_active' => true,
            ]);
        }
    }
}
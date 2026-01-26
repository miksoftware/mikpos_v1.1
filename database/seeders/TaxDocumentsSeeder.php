<?php

namespace Database\Seeders;

use App\Models\TaxDocument;
use Illuminate\Database\Seeder;

class TaxDocumentsSeeder extends Seeder
{
    /**
     * Seed tax document types with DIAN IDs for Colombia.
     * These IDs are required for electronic invoicing (Factus/DIAN).
     * 
     * Reference: Factus API - IDs de tipos de documentos de identidad
     */
    public function run(): void
    {
        $taxDocuments = [
            ['dian_code' => '1', 'description' => 'Registro Civil', 'abbreviation' => 'RC', 'is_active' => true],
            ['dian_code' => '2', 'description' => 'Tarjeta de Identidad', 'abbreviation' => 'TI', 'is_active' => true],
            ['dian_code' => '3', 'description' => 'Cédula de Ciudadanía', 'abbreviation' => 'CC', 'is_active' => true],
            ['dian_code' => '4', 'description' => 'Tarjeta de Extranjería', 'abbreviation' => 'TE', 'is_active' => true],
            ['dian_code' => '5', 'description' => 'Cédula de Extranjería', 'abbreviation' => 'CE', 'is_active' => true],
            ['dian_code' => '6', 'description' => 'NIT', 'abbreviation' => 'NIT', 'is_active' => true],
            ['dian_code' => '7', 'description' => 'Pasaporte', 'abbreviation' => 'PA', 'is_active' => true],
            ['dian_code' => '8', 'description' => 'Documento de Identificación Extranjero', 'abbreviation' => 'DIE', 'is_active' => true],
            ['dian_code' => '9', 'description' => 'PEP (Permiso Especial de Permanencia)', 'abbreviation' => 'PEP', 'is_active' => true],
            ['dian_code' => '10', 'description' => 'NIT de Otro País', 'abbreviation' => 'NIT-E', 'is_active' => false],
            ['dian_code' => '11', 'description' => 'NUIP', 'abbreviation' => 'NUIP', 'is_active' => false],
        ];

        foreach ($taxDocuments as $document) {
            TaxDocument::updateOrCreate(
                ['dian_code' => $document['dian_code']],
                $document
            );
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class WeightUnitsSeeder extends Seeder
{
    /**
     * Mark existing units as weight units based on their abbreviation.
     * 
     * This seeder updates units with common weight-related abbreviations
     * to have is_weight_unit = true, enabling the POS weight quantity modal.
     */
    public function run(): void
    {
        // Weight unit abbreviations to mark (case-insensitive)
        $weightAbbreviations = [
            'KG',
            'KL',
            'KILO',
            'KILOS',
            'KILOGRAMO',
            'KILOGRAMOS',
            'LB',
            'LBS',
            'LIBRA',
            'LIBRAS',
            'GR',
            'G',
            'GRAMO',
            'GRAMOS',
            'OZ',
            'ONZA',
            'ONZAS',
            'MG',
            'MILIGRAMO',
            'MILIGRAMOS',
        ];

        // Update existing units that match weight abbreviations
        foreach ($weightAbbreviations as $abbreviation) {
            Unit::whereRaw('UPPER(abbreviation) = ?', [strtoupper($abbreviation)])
                ->update(['is_weight_unit' => true]);
        }
    }
}

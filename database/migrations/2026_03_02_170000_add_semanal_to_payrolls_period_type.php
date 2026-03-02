<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $currentType = DB::selectOne("SHOW COLUMNS FROM payrolls WHERE Field = 'period_type'");
        if ($currentType && !str_contains($currentType->Type, 'semanal')) {
            DB::statement("ALTER TABLE payrolls MODIFY COLUMN period_type ENUM('mensual','quincenal','semanal') NOT NULL DEFAULT 'mensual'");
        }
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE payrolls MODIFY COLUMN period_type ENUM('mensual','quincenal') NOT NULL DEFAULT 'mensual'");
    }
};

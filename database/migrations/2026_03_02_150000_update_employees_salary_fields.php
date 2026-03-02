<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add transport_included_in_salary if missing
        if (!Schema::hasColumn('employees', 'transport_included_in_salary')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->boolean('transport_included_in_salary')->default(false)->after('transport_allowance');
            });
        }

        // Update salary_type enum to new values if needed
        // Check current enum values
        $currentType = DB::selectOne("SHOW COLUMNS FROM employees WHERE Field = 'salary_type'");
        if ($currentType && !str_contains($currentType->Type, 'minimo')) {
            // Old enum was 'ordinario','integral' — change to 'minimo','otro','integral'
            DB::statement("ALTER TABLE employees MODIFY COLUMN salary_type ENUM('minimo','otro','integral') NOT NULL DEFAULT 'minimo'");
            // Convert old 'ordinario' values to 'otro'
            DB::table('employees')->where('salary_type', 'ordinario')->update(['salary_type' => 'otro']);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('employees', 'transport_included_in_salary')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropColumn('transport_included_in_salary');
            });
        }
    }
};

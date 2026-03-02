<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('employees', 'payment_frequency')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->enum('payment_frequency', ['semanal', 'quincenal', 'mensual'])->default('mensual')->after('risk_level');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('employees', 'payment_frequency')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropColumn('payment_frequency');
            });
        }
    }
};

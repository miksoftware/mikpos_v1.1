<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('tax_id')->constrained()->nullOnDelete();
            $table->foreignId('municipality_id')->nullable()->after('department_id')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropForeign(['municipality_id']);
            $table->dropForeign(['department_id']);
            $table->dropColumn(['municipality_id', 'department_id']);
        });
    }
};

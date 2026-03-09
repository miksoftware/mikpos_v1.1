<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('global_discount_type')->nullable()->after('discount');
            $table->decimal('global_discount_value', 12, 2)->default(0)->after('global_discount_type');
            $table->decimal('global_discount_amount', 12, 2)->default(0)->after('global_discount_value');
            $table->string('global_discount_reason')->nullable()->after('global_discount_amount');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['global_discount_type', 'global_discount_value', 'global_discount_amount', 'global_discount_reason']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->string('discount_type', 20)->default('percentage')->after('discount'); // percentage or fixed
            $table->decimal('discount_type_value', 12, 2)->default(0)->after('discount_type'); // raw value entered by user
        });
    }

    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropColumn(['discount_type', 'discount_type_value']);
        });
    }
};

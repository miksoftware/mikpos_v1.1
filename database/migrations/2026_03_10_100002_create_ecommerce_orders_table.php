<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecommerce_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('shipping_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('shipping_municipality_id')->nullable()->constrained('municipalities')->nullOnDelete();
            $table->string('shipping_address')->nullable();
            $table->string('shipping_phone')->nullable();
            $table->text('customer_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_orders');
    }
};

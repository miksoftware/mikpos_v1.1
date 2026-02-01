<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('sku', 50)->unique()->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tax_id')->nullable()->constrained('taxes')->nullOnDelete();
            $table->string('image')->nullable();
            $table->decimal('cost', 12, 2)->default(0);
            $table->decimal('sale_price', 12, 2)->default(0);
            $table->boolean('price_includes_tax')->default(true);
            $table->boolean('is_active')->default(true);
            $table->boolean('has_commission')->default(false);
            $table->enum('commission_type', ['fixed', 'percentage'])->default('fixed');
            $table->decimal('commission_value', 10, 2)->default(0);
            $table->timestamps();

            $table->index('name');
            $table->index('branch_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};

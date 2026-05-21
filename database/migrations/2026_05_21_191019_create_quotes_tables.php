<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Quotes table
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('quote_number')->unique();
            $table->date('valid_until')->nullable();

            // Totals
            $table->decimal('subtotal', 12, 2);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total', 12, 2);

            // Global discount
            $table->string('global_discount_type')->nullable();
            $table->decimal('global_discount_value', 12, 2)->default(0);
            $table->decimal('global_discount_amount', 12, 2)->default(0);
            $table->string('global_discount_reason')->nullable();

            // Status: draft | converted | cancelled
            $table->enum('status', ['draft', 'converted', 'cancelled'])->default('draft');

            // Conversion tracking
            $table->foreignId('converted_to_sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->timestamp('converted_at')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'created_at']);
            $table->index('status');
            $table->index('valid_until');
        });

        // Quote items table (mirror of sale_items but without inventory references)
        Schema::create('quote_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_child_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('combo_id')->nullable()->constrained()->nullOnDelete();

            $table->string('product_name');
            $table->string('product_sku')->nullable();

            $table->decimal('unit_price', 12, 2);
            $table->decimal('quantity', 12, 3);

            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2);

            // Item discount
            $table->string('discount_type', 20)->nullable();
            $table->decimal('discount_type_value', 10, 2)->nullable();
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->string('discount_reason')->nullable();

            $table->decimal('total', 12, 2);

            $table->timestamps();

            $table->index('quote_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_items');
        Schema::dropIfExists('quotes');
    }
};

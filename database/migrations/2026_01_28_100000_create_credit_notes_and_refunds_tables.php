<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Credit Notes table (for electronic invoices - DIAN)
        Schema::create('credit_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('number')->unique(); // Internal number
            $table->enum('type', ['partial', 'total'])->default('total');
            $table->string('correction_concept_code')->default('2'); // DIAN correction concept
            $table->text('reason'); // Reason for credit note
            $table->decimal('subtotal', 12, 2);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            // DIAN fields
            $table->string('cufe')->nullable(); // DIAN unique code
            $table->text('qr_code')->nullable(); // QR code base64
            $table->string('dian_public_url')->nullable();
            $table->string('dian_number')->nullable(); // DIAN number
            $table->timestamp('dian_validated_at')->nullable();
            $table->json('dian_response')->nullable();
            $table->string('reference_code')->nullable();
            $table->enum('status', ['pending', 'validated', 'rejected'])->default('pending');
            $table->timestamps();
            
            $table->index(['sale_id', 'created_at']);
            $table->index('branch_id');
        });

        // Credit Note Items
        Schema::create('credit_note_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credit_note_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('product_name');
            $table->string('product_sku')->nullable();
            $table->decimal('unit_price', 12, 2);
            $table->integer('quantity'); // Quantity being credited
            $table->integer('original_quantity'); // Original quantity in sale
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2);
            $table->decimal('total', 12, 2);
            $table->timestamps();
        });

        // Refunds table (for POS sales - no DIAN)
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cash_reconciliation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number')->unique(); // Internal number DEV-YYYYMMDD-XXXX
            $table->enum('type', ['partial', 'total'])->default('total');
            $table->text('reason');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->enum('status', ['completed', 'cancelled'])->default('completed');
            $table->timestamps();
            
            $table->index(['sale_id', 'created_at']);
            $table->index('branch_id');
        });

        // Refund Items
        Schema::create('refund_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('refund_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('product_name');
            $table->string('product_sku')->nullable();
            $table->decimal('unit_price', 12, 2);
            $table->integer('quantity'); // Quantity being refunded
            $table->integer('original_quantity'); // Original quantity in sale
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2);
            $table->decimal('total', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refund_items');
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('credit_note_items');
        Schema::dropIfExists('credit_notes');
    }
};

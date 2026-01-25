<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla principal de compras
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->string('purchase_number')->unique(); // Número de compra interno
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // Usuario que registra
            
            $table->string('supplier_invoice')->nullable(); // Número de factura del proveedor
            $table->date('purchase_date');
            $table->date('due_date')->nullable(); // Fecha de vencimiento si es a crédito
            
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            
            $table->enum('status', ['draft', 'completed', 'cancelled'])->default('draft');
            $table->enum('payment_status', ['pending', 'partial', 'paid'])->default('pending');
            
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Detalle de productos comprados
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            
            $table->integer('quantity');
            $table->decimal('unit_cost', 12, 2); // Costo unitario de compra
            $table->decimal('tax_rate', 5, 2)->default(0); // Porcentaje de impuesto
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2); // quantity * unit_cost
            $table->decimal('total', 12, 2); // subtotal + tax - discount
            
            $table->timestamps();
            
            $table->index(['purchase_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases');
    }
};

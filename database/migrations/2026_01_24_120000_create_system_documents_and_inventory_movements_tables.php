<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // System documents configuration table
        Schema::create('system_documents', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique(); // Internal code: purchase, initial_stock, adjustment, transfer, sale
            $table->string('name'); // Display name: Compra, Stock Inicial, etc.
            $table->string('prefix', 10); // Document prefix: CMP, STI, AJU, TRA, VTA
            $table->unsignedBigInteger('next_number')->default(1); // Next consecutive number
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Inventory movements table - tracks all stock changes
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('system_document_id')->constrained()->restrictOnDelete();
            $table->string('document_number')->unique(); // Generated: STI-00001, CMP-00001
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            $table->enum('movement_type', ['in', 'out']); // in = entrada, out = salida
            $table->integer('quantity'); // Always positive, type determines direction
            $table->integer('stock_before'); // Stock before movement
            $table->integer('stock_after'); // Stock after movement
            
            $table->decimal('unit_cost', 12, 2)->nullable(); // Cost per unit (for purchases/initial)
            $table->decimal('total_cost', 12, 2)->nullable(); // Total cost
            
            // Reference to source document (polymorphic)
            $table->string('reference_type')->nullable(); // App\Models\Purchase, etc.
            $table->unsignedBigInteger('reference_id')->nullable();
            
            $table->text('notes')->nullable();
            $table->date('movement_date');
            $table->timestamps();
            
            $table->index(['product_id', 'movement_date']);
            $table->index(['system_document_id', 'document_number']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('system_documents');
    }
};

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
        Schema::create('product_barcodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('product_child_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('barcode', 50);
            $table->string('description')->nullable(); // e.g., "Presentación anterior", "Caja x12"
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            // Ensure barcode is unique across all products
            $table->unique('barcode');
            
            // Index for faster lookups
            $table->index(['product_id', 'is_primary']);
            $table->index(['product_child_id', 'is_primary']);
        });

        // Migrate existing barcodes from products table
        $products = DB::table('products')->whereNotNull('barcode')->where('barcode', '!=', '')->get();
        foreach ($products as $product) {
            DB::table('product_barcodes')->insert([
                'product_id' => $product->id,
                'product_child_id' => null,
                'barcode' => $product->barcode,
                'description' => 'Código principal',
                'is_primary' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Migrate existing barcodes from product_children table
        $children = DB::table('product_children')->whereNotNull('barcode')->where('barcode', '!=', '')->get();
        foreach ($children as $child) {
            // Check if barcode already exists (avoid duplicates)
            $exists = DB::table('product_barcodes')->where('barcode', $child->barcode)->exists();
            if (!$exists) {
                DB::table('product_barcodes')->insert([
                    'product_id' => null,
                    'product_child_id' => $child->id,
                    'barcode' => $child->barcode,
                    'description' => 'Código principal',
                    'is_primary' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_barcodes');
    }
};

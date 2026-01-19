<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Agregar código de barras al producto padre
        Schema::table('products', function (Blueprint $table) {
            $table->string('barcode', 100)->unique()->nullable()->after('sku');
        });

        // Modificar product_children: quitar campos de inventario y precio de compra
        // Agregar unit_quantity para indicar cuántas unidades del padre consume
        Schema::table('product_children', function (Blueprint $table) {
            // Agregar campo de cantidad de unidades del padre que consume esta variante
            $table->decimal('unit_quantity', 10, 3)->default(1)->after('product_id');
            
            // Quitar campos que ya no aplican al hijo
            $table->dropColumn([
                'purchase_price',
                'min_stock',
                'max_stock',
                'current_stock',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('barcode');
        });

        Schema::table('product_children', function (Blueprint $table) {
            $table->dropColumn('unit_quantity');
            
            // Restaurar campos eliminados
            $table->decimal('purchase_price', 12, 2)->default(0);
            $table->integer('min_stock')->default(0);
            $table->integer('max_stock')->nullable();
            $table->integer('current_stock')->default(0);
        });
    }
};

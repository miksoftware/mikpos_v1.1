<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create the new production_order_items table
        Schema::create('production_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recipe_id')->constrained();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('location_id')->nullable()->constrained();
            $table->decimal('quantity_to_produce', 10, 3);
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->timestamps();
        });

        // 2. Add production_order_item_id to details
        Schema::table('production_order_details', function (Blueprint $table) {
            $table->foreignId('production_order_item_id')->nullable()->after('production_order_id')->constrained('production_order_items')->nullOnDelete();
        });

        // 3. Migrate existing data
        $orders = DB::table('production_orders')->get();
        foreach ($orders as $order) {
            $itemId = DB::table('production_order_items')->insertGetId([
                'production_order_id' => $order->id,
                'recipe_id' => $order->recipe_id,
                'product_id' => $order->product_id,
                'quantity_to_produce' => $order->quantity_to_produce,
                'total_cost' => $order->total_cost,
                'created_at' => $order->created_at,
                'updated_at' => $order->updated_at,
            ]);

            DB::table('production_order_details')
                ->where('production_order_id', $order->id)
                ->update(['production_order_item_id' => $itemId]);
        }

        // 4. Drop columns from production_orders
        Schema::table('production_orders', function (Blueprint $table) {
            // Drop foreign keys first if any (Laravel convention assumes foreign keys)
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['product_id']);
                $table->dropForeign(['recipe_id']);
            }
            $table->dropColumn(['product_id', 'recipe_id', 'quantity_to_produce', 'total_cost']);
        });
    }

    public function down(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->constrained();
            $table->foreignId('recipe_id')->nullable()->constrained();
            $table->decimal('quantity_to_produce', 10, 3)->default(0);
            $table->decimal('total_cost', 15, 2)->default(0);
        });

        $items = DB::table('production_order_items')->get();
        foreach ($items as $item) {
            DB::table('production_orders')
                ->where('id', $item->production_order_id)
                ->update([
                    'product_id' => $item->product_id,
                    'recipe_id' => $item->recipe_id,
                    'quantity_to_produce' => $item->quantity_to_produce,
                    'total_cost' => $item->total_cost,
                ]);
        }

        Schema::table('production_order_details', function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['production_order_item_id']);
            }
            $table->dropColumn('production_order_item_id');
        });

        Schema::dropIfExists('production_order_items');
    }
};

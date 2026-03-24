<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Get branch IDs with ecommerce enabled
        $ecommerceBranchIds = DB::table('branches')
            ->where('ecommerce_enabled', true)
            ->pluck('id');

        if ($ecommerceBranchIds->isNotEmpty()) {
            // Set show_in_shop = true for all products in ecommerce branches
            DB::table('products')
                ->whereIn('branch_id', $ecommerceBranchIds)
                ->update(['show_in_shop' => true]);

            // Set show_in_shop = true for all children of products in ecommerce branches
            $productIds = DB::table('products')
                ->whereIn('branch_id', $ecommerceBranchIds)
                ->pluck('id');

            DB::table('product_children')
                ->whereIn('product_id', $productIds)
                ->update(['show_in_shop' => true]);
        }
    }

    public function down(): void
    {
        // No rollback needed
    }
};

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
        DB::table('modules')
            ->whereIn('name', ['locations', 'location_transfers'])
            ->update(['order' => 0]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverting to the old order (approximate) if needed
        DB::table('modules')
            ->where('name', 'locations')
            ->update(['order' => 36]);
            
        DB::table('modules')
            ->where('name', 'location_transfers')
            ->update(['order' => 37]);
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_field_settings', function (Blueprint $table) {
            // Rename existing columns to be for parent product
            $table->renameColumn('is_visible', 'parent_visible');
            $table->renameColumn('is_required', 'parent_required');
        });

        Schema::table('product_field_settings', function (Blueprint $table) {
            // Add columns for child/variant product
            $table->boolean('child_visible')->default(true)->after('parent_required');
            $table->boolean('child_required')->default(false)->after('child_visible');
        });
    }

    public function down(): void
    {
        Schema::table('product_field_settings', function (Blueprint $table) {
            $table->dropColumn(['child_visible', 'child_required']);
        });

        Schema::table('product_field_settings', function (Blueprint $table) {
            $table->renameColumn('parent_visible', 'is_visible');
            $table->renameColumn('parent_required', 'is_required');
        });
    }
};

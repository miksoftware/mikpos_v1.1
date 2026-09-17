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
        Schema::create('ecommerce_settings', function (Blueprint $table) {
            $table->id();
            $table->string('store_mode')->default('unified'); // 'unified' or 'independent'
            $table->foreignId('default_branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->boolean('allow_branch_switching')->default(true);
            $table->boolean('auto_sync_customers')->default(true);
            $table->json('additional_settings')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecommerce_settings');
    }
};

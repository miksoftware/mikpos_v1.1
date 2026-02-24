<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('print_format_settings', function (Blueprint $table) {
            $table->id();
            $table->string('document_type')->unique(); // pos, refund, purchase, etc.
            $table->string('display_name');
            $table->string('format')->default('80mm'); // 80mm, letter
            $table->timestamps();
        });

        // Insert default POS format
        DB::table('print_format_settings')->insert([
            'document_type' => 'pos',
            'display_name' => 'Recibo POS',
            'format' => '80mm',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('print_format_settings');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('credit_payments', function (Blueprint $table) {
            $table->string('receipt_number')->nullable()->after('payment_number');
            $table->index('receipt_number');
        });

        // Populate existing historical records with their payment_number
        DB::table('credit_payments')
            ->whereNull('receipt_number')
            ->update(['receipt_number' => DB::raw('payment_number')]);
    }

    public function down(): void
    {
        Schema::table('credit_payments', function (Blueprint $table) {
            $table->dropIndex(['receipt_number']);
            $table->dropColumn('receipt_number');
        });
    }
};

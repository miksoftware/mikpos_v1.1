<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            // Payment type: cash or credit
            $table->enum('payment_type', ['cash', 'credit'])->default('cash')->after('payment_status');
            
            // For cash payments
            $table->foreignId('payment_method_id')->nullable()->after('payment_type')->constrained()->nullOnDelete();
            
            // For credit payments
            $table->decimal('credit_amount', 12, 2)->nullable()->after('payment_method_id'); // Total credit amount
            $table->decimal('paid_amount', 12, 2)->default(0)->after('credit_amount'); // Amount already paid
            $table->foreignId('partial_payment_method_id')->nullable()->after('paid_amount'); // Payment method for partial payment
            $table->date('payment_due_date')->nullable()->after('partial_payment_method_id'); // Invoice payment due date
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropForeign(['payment_method_id']);
            $table->dropColumn([
                'payment_type',
                'payment_method_id',
                'credit_amount',
                'paid_amount',
                'partial_payment_method_id',
                'payment_due_date',
            ]);
        });
    }
};

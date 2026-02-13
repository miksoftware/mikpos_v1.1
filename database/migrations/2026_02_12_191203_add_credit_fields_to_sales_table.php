<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->enum('payment_type', ['cash', 'credit'])->default('cash')->after('status');
            $table->enum('payment_status', ['paid', 'partial', 'pending'])->default('paid')->after('payment_type');
            $table->decimal('credit_amount', 12, 2)->nullable()->after('payment_status');
            $table->decimal('paid_amount', 12, 2)->default(0)->after('credit_amount');
            $table->date('payment_due_date')->nullable()->after('paid_amount');

            $table->index('payment_type');
            $table->index('payment_status');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['payment_type']);
            $table->dropIndex(['payment_status']);
            $table->dropColumn(['payment_type', 'payment_status', 'credit_amount', 'paid_amount', 'payment_due_date']);
        });
    }
};

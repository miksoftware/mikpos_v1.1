<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->string('contact_type')->nullable()->after('payment_method_id'); // 'customer' or 'supplier'
            $table->unsignedBigInteger('contact_id')->nullable()->after('contact_type');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn(['contact_type', 'contact_id']);
        });
    }
};

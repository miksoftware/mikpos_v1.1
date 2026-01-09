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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->enum('customer_type', ['natural', 'juridico', 'exonerado']);
            $table->foreignId('tax_document_id')->constrained('tax_documents');
            $table->string('document_number')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('business_name')->nullable(); // Only for juridico
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->foreignId('department_id')->constrained('departments');
            $table->foreignId('municipality_id')->constrained('municipalities');
            $table->text('address');
            $table->boolean('has_credit')->default(false);
            $table->decimal('credit_limit', 15, 2)->nullable(); // Only if has_credit = true
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['customer_type', 'is_active']);
            $table->index(['department_id', 'municipality_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
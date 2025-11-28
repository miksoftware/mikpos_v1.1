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
        // Create branches table first (required for foreign key in users)
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name');
            $table->string('tax_id', 25)->nullable()->comment('CUIT/RUC/NIT');
            $table->string('province')->nullable();
            $table->string('city')->nullable();
            $table->text('address')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email', 120)->nullable();
            // Invoice/receipt numbering
            $table->string('ticket_prefix', 30)->nullable();
            $table->string('invoice_prefix', 15)->nullable();
            $table->string('receipt_prefix', 30)->nullable();
            $table->string('credit_note_prefix', 15)->nullable();
            // Fiscal data
            $table->string('activity_number', 25)->nullable();
            $table->date('authorization_date')->nullable();
            $table->text('receipt_header')->nullable()->comment('Header text for receipts');
            // Settings
            $table->boolean('show_in_pos')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Now create users table with foreign key to branches
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained()->onDelete('set null');
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('phone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('avatar')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('branches');
    }
};

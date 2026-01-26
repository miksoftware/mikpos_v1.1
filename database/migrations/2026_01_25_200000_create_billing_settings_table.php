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
        Schema::create('billing_settings', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->default('factus'); // Provider name (factus, etc.)
            $table->boolean('is_enabled')->default(false);
            $table->string('environment')->default('sandbox'); // sandbox or production
            $table->string('api_url')->nullable();
            $table->string('client_id')->nullable();
            $table->text('client_secret')->nullable(); // Encrypted
            $table->string('username')->nullable(); // API username/email
            $table->text('password')->nullable(); // Encrypted
            $table->text('access_token')->nullable(); // Current access token (encrypted)
            $table->text('refresh_token')->nullable(); // Refresh token (encrypted)
            $table->timestamp('token_expires_at')->nullable();
            $table->json('additional_settings')->nullable(); // For any extra provider-specific settings
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_settings');
    }
};

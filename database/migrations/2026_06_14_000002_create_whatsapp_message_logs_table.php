<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_message_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('whatsapp_config_id')->nullable()->constrained('whatsapp_configs')->nullOnDelete();
            $table->string('message_id')->nullable()->unique();
            $table->string('phone_number_id')->nullable()->index();
            $table->string('display_phone_number')->nullable();
            $table->string('contact_phone')->nullable()->index();
            $table->string('direction', 20)->default('outbound');
            $table->string('event_type', 50)->nullable();
            $table->string('message_type', 50)->nullable();
            $table->string('status', 50)->nullable()->index();
            $table->string('template_name')->nullable();
            $table->string('template_language', 20)->nullable();
            $table->text('message_body')->nullable();
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->json('send_payload')->nullable();
            $table->json('send_response')->nullable();
            $table->json('webhook_payload')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('last_status_at')->nullable();
            $table->timestamp('webhook_received_at')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_message_logs');
    }
};

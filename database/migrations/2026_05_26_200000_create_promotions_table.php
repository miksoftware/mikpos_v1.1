<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('subject');
            $table->longText('message');
            $table->string('image_path')->nullable();
            $table->string('button_text')->nullable();
            $table->string('button_url')->nullable();
            $table->enum('status', ['draft', 'sent'])->default('draft');
            $table->timestamp('sent_at')->nullable();
            $table->integer('recipients_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_configs', function (Blueprint $table) {
            $table->string('template_name')->default('mikpos')->after('phone_number_oficial');
            $table->string('template_language')->default('es_CO')->after('template_name');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_configs', function (Blueprint $table) {
            $table->dropColumn(['template_name', 'template_language']);
        });
    }
};


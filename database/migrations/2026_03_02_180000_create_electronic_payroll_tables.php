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
        if (!Schema::hasTable('electronic_payroll_settings')) {
            Schema::create('electronic_payroll_settings', function (Blueprint $table) {
                $table->id();
                $table->boolean('is_enabled')->default(false);
                $table->string('environment')->default('sandbox'); // 'sandbox' or 'production'
                $table->string('api_url')->nullable();
                $table->string('payroll_numbering_range_id')->nullable();
                $table->string('payroll_numbering_range_prefix')->nullable();
                $table->string('adjustment_numbering_range_id')->nullable();
                $table->string('adjustment_numbering_range_prefix')->nullable();
                $table->json('additional_settings')->nullable();
                $table->timestamps();
            });
        }

        Schema::table('employees', function (Blueprint $table) {
            if (!Schema::hasColumn('employees', 'dian_worker_type_code')) {
                $table->string('dian_worker_type_code')->default('01')->after('position'); // 01: Dependiente
            }
            if (!Schema::hasColumn('employees', 'dian_worker_subtype_code')) {
                $table->string('dian_worker_subtype_code')->default('00')->after('dian_worker_type_code'); // 00: No aplica
            }
            if (!Schema::hasColumn('employees', 'dian_municipality_code')) {
                $table->string('dian_municipality_code')->nullable()->after('dian_worker_subtype_code');
            }
            if (!Schema::hasColumn('employees', 'dian_high_risk')) {
                $table->boolean('dian_high_risk')->default(false)->after('dian_municipality_code');
            }
        });

        Schema::table('payroll_details', function (Blueprint $table) {
            if (!Schema::hasColumn('payroll_details', 'dian_status')) {
                $table->enum('dian_status', ['sin_emitir', 'validado', 'rechazado', 'reemplazado', 'eliminado'])
                    ->default('sin_emitir')
                    ->after('net_pay');
            }
            if (!Schema::hasColumn('payroll_details', 'cune')) {
                $table->string('cune')->nullable()->after('dian_status');
            }
            if (!Schema::hasColumn('payroll_details', 'electronic_number')) {
                $table->string('electronic_number')->nullable()->after('cune');
            }
            if (!Schema::hasColumn('payroll_details', 'dian_response')) {
                $table->json('dian_response')->nullable()->after('electronic_number');
            }
            if (!Schema::hasColumn('payroll_details', 'xml_url')) {
                $table->text('xml_url')->nullable()->after('dian_response');
            }
            if (!Schema::hasColumn('payroll_details', 'pdf_url')) {
                $table->text('pdf_url')->nullable()->after('xml_url');
            }
            if (!Schema::hasColumn('payroll_details', 'qr_code')) {
                $table->text('qr_code')->nullable()->after('pdf_url');
            }
            if (!Schema::hasColumn('payroll_details', 'reference_cune')) {
                $table->string('reference_cune')->nullable()->after('qr_code');
            }
            if (!Schema::hasColumn('payroll_details', 'transmitted_at')) {
                $table->timestamp('transmitted_at')->nullable()->after('reference_cune');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('electronic_payroll_settings');

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'dian_worker_type_code',
                'dian_worker_subtype_code',
                'dian_municipality_code',
                'dian_high_risk',
            ]);
        });

        Schema::table('payroll_details', function (Blueprint $table) {
            $table->dropColumn([
                'dian_status',
                'cune',
                'electronic_number',
                'dian_response',
                'xml_url',
                'pdf_url',
                'qr_code',
                'reference_cune',
                'transmitted_at',
            ]);
        });
    }
};

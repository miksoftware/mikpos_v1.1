<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();

            // Personal info
            $table->string('first_name');
            $table->string('last_name');
            $table->enum('document_type', ['CC', 'CE', 'PA', 'TI']);
            $table->string('document_number')->unique();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->date('birth_date')->nullable();

            // Contract
            $table->date('hire_date');
            $table->string('position');
            $table->string('department')->nullable();
            $table->enum('contract_type', ['indefinido', 'fijo', 'obra_labor', 'aprendizaje', 'prestacion_servicios']);
            $table->enum('salary_type', ['minimo', 'otro', 'integral'])->default('minimo');
            $table->decimal('base_salary', 12, 2);
            $table->boolean('transport_allowance')->default(true);
            $table->boolean('transport_included_in_salary')->default(false);
            $table->enum('risk_level', ['I', 'II', 'III', 'IV', 'V'])->default('I');

            // Social security
            $table->string('health_fund')->nullable();
            $table->string('pension_fund')->nullable();
            $table->string('severance_fund')->nullable();
            $table->string('compensation_fund')->nullable();

            // Bank
            $table->string('bank_name')->nullable();
            $table->enum('bank_account_type', ['ahorros', 'corriente'])->nullable();
            $table->string('bank_account_number')->nullable();

            // Status
            $table->enum('status', ['activo', 'vacaciones', 'incapacidad', 'retirado'])->default('activo');
            $table->date('termination_date')->nullable();
            $table->string('termination_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->enum('period_type', ['mensual', 'quincenal']);
            $table->date('period_start');
            $table->date('period_end');
            $table->date('payment_date');
            $table->enum('status', ['borrador', 'calculada', 'aprobada', 'pagada'])->default('borrador');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('payroll_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();

            // Worked days
            $table->decimal('worked_days', 5, 2)->default(0);

            // Devengados
            $table->decimal('base_salary_earned', 12, 2)->default(0);
            $table->decimal('transport_allowance_earned', 12, 2)->default(0);
            $table->decimal('overtime_daytime_hours', 8, 2)->default(0);
            $table->decimal('overtime_daytime_value', 12, 2)->default(0);
            $table->decimal('overtime_nighttime_hours', 8, 2)->default(0);
            $table->decimal('overtime_nighttime_value', 12, 2)->default(0);
            $table->decimal('overtime_sunday_daytime_hours', 8, 2)->default(0);
            $table->decimal('overtime_sunday_daytime_value', 12, 2)->default(0);
            $table->decimal('overtime_sunday_nighttime_hours', 8, 2)->default(0);
            $table->decimal('overtime_sunday_nighttime_value', 12, 2)->default(0);
            $table->decimal('night_surcharge_hours', 8, 2)->default(0);
            $table->decimal('night_surcharge_value', 12, 2)->default(0);
            $table->decimal('sunday_holiday_hours', 8, 2)->default(0);
            $table->decimal('sunday_holiday_value', 12, 2)->default(0);
            $table->decimal('commissions', 12, 2)->default(0);
            $table->decimal('bonuses', 12, 2)->default(0);
            $table->decimal('disability_days', 5, 2)->default(0);
            $table->decimal('disability_value', 12, 2)->default(0);
            $table->decimal('vacation_days', 5, 2)->default(0);
            $table->decimal('vacation_value', 12, 2)->default(0);
            $table->decimal('other_income', 12, 2)->default(0);
            $table->decimal('total_earned', 12, 2)->default(0);

            // Deducciones empleado
            $table->decimal('health_employee', 12, 2)->default(0);
            $table->decimal('pension_employee', 12, 2)->default(0);
            $table->decimal('solidarity_fund', 12, 2)->default(0);
            $table->decimal('income_tax_withholding', 12, 2)->default(0);
            $table->decimal('loan_deduction', 12, 2)->default(0);
            $table->decimal('cooperative_deduction', 12, 2)->default(0);
            $table->decimal('libranza_deduction', 12, 2)->default(0);
            $table->decimal('other_deductions', 12, 2)->default(0);
            $table->decimal('total_deductions', 12, 2)->default(0);

            // Aportes empleador
            $table->decimal('health_employer', 12, 2)->default(0);
            $table->decimal('pension_employer', 12, 2)->default(0);
            $table->decimal('arl_employer', 12, 2)->default(0);
            $table->decimal('sena_employer', 12, 2)->default(0);
            $table->decimal('icbf_employer', 12, 2)->default(0);
            $table->decimal('compensation_fund_employer', 12, 2)->default(0);

            // Provisiones
            $table->decimal('severance_provision', 12, 2)->default(0);
            $table->decimal('severance_interest_provision', 12, 2)->default(0);
            $table->decimal('service_bonus_provision', 12, 2)->default(0);
            $table->decimal('vacation_provision', 12, 2)->default(0);

            // Neto
            $table->decimal('net_pay', 12, 2)->default(0);

            $table->timestamps();
        });

        Schema::create('employee_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('concept');
            $table->decimal('total_amount', 12, 2);
            $table->decimal('monthly_deduction', 12, 2);
            $table->decimal('remaining_balance', 12, 2);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->enum('status', ['activo', 'pagado', 'cancelado'])->default('activo');
            $table->timestamps();
        });

        Schema::create('payroll_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payroll_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', [
                'incapacidad_eps', 'incapacidad_arl', 'accidente_trabajo',
                'vacaciones', 'permiso_remunerado', 'permiso_no_remunerado',
                'licencia_maternidad', 'licencia_paternidad', 'ausencia_injustificada',
            ]);
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('days');
            $table->text('description')->nullable();
            $table->string('supporting_document')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_adjustments');
        Schema::dropIfExists('employee_loans');
        Schema::dropIfExists('payroll_details');
        Schema::dropIfExists('payrolls');
        Schema::dropIfExists('employees');
    }
};

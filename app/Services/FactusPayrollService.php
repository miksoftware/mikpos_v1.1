<?php

namespace App\Services;

use App\Models\BillingSetting;
use App\Models\ElectronicPayrollSetting;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollDetail;
use App\Models\PayrollAdjustment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class FactusPayrollService
{
    protected BillingSetting $billingSettings;
    protected ElectronicPayrollSetting $payrollSettings;
    protected string $baseUrl;

    public function __construct()
    {
        $this->billingSettings = BillingSetting::getSettings();
        $this->payrollSettings = ElectronicPayrollSetting::getSettings();
        $this->baseUrl = $this->payrollSettings->api_url 
            ?: ($this->billingSettings->api_url ?: $this->billingSettings->getDefaultApiUrl());
    }

    /**
     * Check if electronic payroll is enabled and configured.
     */
    public function isEnabled(): bool
    {
        return $this->payrollSettings->is_enabled && $this->billingSettings->isConfigured();
    }

    /**
     * Get valid access token, reusing Factus authentication from BillingSetting.
     */
    protected function getAccessToken(): string
    {
        if (!$this->billingSettings->isConfigured()) {
            throw new Exception('Facturas/Nómina electrónica no configurada. Configure las credenciales de Factus primero.');
        }

        if (!$this->billingSettings->isTokenExpired() && $this->billingSettings->access_token) {
            return $this->billingSettings->access_token;
        }

        // Try refresh
        if ($this->billingSettings->refresh_token) {
            try {
                return $this->refreshToken();
            } catch (Exception $e) {
                Log::warning('Failed to refresh Factus token for payroll: ' . $e->getMessage());
            }
        }

        return $this->authenticate();
    }

    protected function authenticate(): string
    {
        $response = Http::asForm()->post($this->baseUrl . '/oauth/token', [
            'grant_type' => 'password',
            'client_id' => $this->billingSettings->client_id,
            'client_secret' => $this->billingSettings->client_secret,
            'username' => $this->billingSettings->username,
            'password' => $this->billingSettings->password,
        ]);

        if (!$response->successful()) {
            $error = $response->json();
            throw new Exception('Error de autenticación Factus: ' . ($error['message'] ?? 'Credenciales inválidas'));
        }

        $data = $response->json();
        
        $this->billingSettings->update([
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'] ?? null,
            'token_expires_at' => isset($data['expires_in']) 
                ? now()->addSeconds($data['expires_in']) 
                : null,
        ]);

        return $data['access_token'];
    }

    protected function refreshToken(): string
    {
        $response = Http::asForm()->post($this->baseUrl . '/oauth/token', [
            'grant_type' => 'refresh_token',
            'client_id' => $this->billingSettings->client_id,
            'client_secret' => $this->billingSettings->client_secret,
            'refresh_token' => $this->billingSettings->refresh_token,
        ]);

        if (!$response->successful()) {
            throw new Exception('Error al refrescar token de Factus');
        }

        $data = $response->json();

        $this->billingSettings->update([
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'] ?? $this->billingSettings->refresh_token,
            'token_expires_at' => isset($data['expires_in']) 
                ? now()->addSeconds($data['expires_in']) 
                : null,
        ]);

        return $data['access_token'];
    }

    /**
     * Get active numbering ranges from Factus.
     */
    public function getNumberingRanges(): array
    {
        $token = $this->getAccessToken();
        $response = Http::withToken($token)
            ->acceptJson()
            ->get($this->baseUrl . '/v1/numbering-ranges');

        if (!$response->successful()) {
            Log::error('Error fetching Factus numbering ranges', ['body' => $response->body()]);
            return [];
        }

        $data = $response->json();
        return $data['data'] ?? ($data['data']['data'] ?? []);
    }

    /**
     * Transmit single payroll detail to Factus.
     */
    public function createPayroll(PayrollDetail $detail): array
    {
        if (!$this->isEnabled()) {
            throw new Exception('La Nómina Electrónica no está habilitada o configurada.');
        }

        $token = $this->getAccessToken();
        $payload = $this->buildPayrollPayload($detail);

        Log::info('Sending Factus payroll payload', ['payroll_detail_id' => $detail->id, 'payload' => $payload]);

        $response = Http::withToken($token)
            ->acceptJson()
            ->post($this->baseUrl . '/v1/payrolls', $payload);

        $responseData = $response->json();
        Log::info('Factus payroll response', ['payroll_detail_id' => $detail->id, 'response' => $responseData]);

        if (!$response->successful()) {
            $errorMessage = $responseData['message'] ?? 'Error desconocido al validar nómina electrónica';
            if (isset($responseData['errors']) && is_array($responseData['errors'])) {
                $errorMessage .= ': ' . implode(', ', array_map(fn($e) => is_array($e) ? implode(';', $e) : $e, $responseData['errors']));
            }
            $detail->update([
                'dian_status' => 'rechazado',
                'dian_response' => $responseData,
            ]);
            throw new Exception($errorMessage);
        }

        $data = $responseData['data'] ?? $responseData;
        $cune = $data['cune'] ?? $data['cune_full'] ?? null;
        $number = $data['number'] ?? $data['bill_number'] ?? null;
        $xmlUrl = $data['xml_url'] ?? $data['xml'] ?? null;
        $pdfUrl = $data['pdf_url'] ?? $data['pdf'] ?? null;
        $qrCode = $data['qr'] ?? $data['qr_code'] ?? null;

        $detail->update([
            'dian_status' => 'validado',
            'cune' => $cune,
            'electronic_number' => $number,
            'dian_response' => $responseData,
            'xml_url' => $xmlUrl,
            'pdf_url' => $pdfUrl,
            'qr_code' => $qrCode,
            'transmitted_at' => now(),
        ]);

        return $responseData;
    }

    /**
     * Transmit Adjustment Note (Replacement) to Factus.
     */
    public function createAdjustmentReplacement(PayrollDetail $detail, string $reason): array
    {
        if (!$this->isEnabled()) {
            throw new Exception('La Nómina Electrónica no está habilitada.');
        }

        if (empty($detail->cune) || empty($detail->electronic_number)) {
            throw new Exception('No se puede reemplazar una nómina que no ha sido validada previamente por la DIAN.');
        }

        $token = $this->getAccessToken();
        $payload = $this->buildPayrollPayload($detail);
        $payload['reference_cune'] = $detail->cune;
        $payload['payroll_number'] = $detail->electronic_number;
        $payload['adjustment_type_code'] = '1'; // 1: Reemplazo
        $payload['notes'] = $reason;

        if ($this->payrollSettings->adjustment_numbering_range_id) {
            $payload['numbering_range_id'] = (int) $this->payrollSettings->adjustment_numbering_range_id;
        }

        Log::info('Sending Factus payroll replacement payload', ['payroll_detail_id' => $detail->id, 'payload' => $payload]);

        $response = Http::withToken($token)
            ->acceptJson()
            ->post($this->baseUrl . '/v1/payroll-adjustments', $payload);

        $responseData = $response->json();

        if (!$response->successful()) {
            $errorMessage = $responseData['message'] ?? 'Error al enviar nota de reemplazo de nómina';
            throw new Exception($errorMessage);
        }

        $data = $responseData['data'] ?? $responseData;
        $cune = $data['cune'] ?? null;
        $number = $data['number'] ?? null;

        $detail->update([
            'dian_status' => 'reemplazado',
            'reference_cune' => $detail->cune,
            'cune' => $cune ?: $detail->cune,
            'electronic_number' => $number ?: $detail->electronic_number,
            'dian_response' => $responseData,
            'transmitted_at' => now(),
        ]);

        return $responseData;
    }

    /**
     * Transmit Adjustment Note (Elimination) to Factus.
     */
    public function createAdjustmentElimination(PayrollDetail $detail, string $reason): array
    {
        if (!$this->isEnabled()) {
            throw new Exception('La Nómina Electrónica no está habilitada.');
        }

        if (empty($detail->electronic_number)) {
            throw new Exception('No se puede eliminar una nómina que no ha sido validada por la DIAN.');
        }

        $token = $this->getAccessToken();
        $payload = [
            'payroll_number' => $detail->electronic_number,
            'reference_code' => 'ELIM-' . $detail->id . '-' . time(),
        ];

        if ($this->payrollSettings->adjustment_numbering_range_id) {
            $payload['numbering_range_id'] = $this->payrollSettings->adjustment_numbering_range_id;
        }

        Log::info('Sending Factus payroll elimination payload', ['payroll_detail_id' => $detail->id, 'payload' => $payload]);

        $response = Http::withToken($token)
            ->acceptJson()
            ->post($this->baseUrl . '/v1/payroll-adjustments', $payload);

        $responseData = $response->json();

        if (!$response->successful()) {
            $errorMessage = $responseData['message'] ?? 'Error al eliminar nómina electrónica';
            throw new Exception($errorMessage);
        }

        $detail->update([
            'dian_status' => 'eliminado',
            'dian_response' => $responseData,
            'transmitted_at' => now(),
        ]);

        return $responseData;
    }

    /**
     * Build Factus V2 API JSON payload from PayrollDetail model.
     */
    protected function buildPayrollPayload(PayrollDetail $detail): array
    {
        $payroll = $detail->payroll;
        $employee = $detail->employee;

        // Determine period code
        $periodCode = match ($payroll->period_type) {
            'semanal' => '1',
            'quincenal' => '4',
            'mensual' => '5',
            default => '5',
        };

        // Determine payment method code (default 10 = efectivo)
        $paymentMethodCode = '10';

        // Worker document type code mapping for DIAN
        $docTypeCode = match ($employee->document_type) {
            'CC' => '13',
            'CE' => '22',
            'PA' => '41',
            'TI' => '12',
            default => '13',
        };

        // Worker contract type code mapping
        $contractTypeCode = match ($employee->contract_type) {
            'indefinido' => '1',
            'fijo' => '2',
            'obra_labor' => '3',
            'aprendizaje' => '4',
            'prestacion_servicios' => '5',
            default => '1',
        };

        // Separate names
        $firstNames = explode(' ', trim($employee->first_name));
        $firstName = $firstNames[0];
        $otherNames = count($firstNames) > 1 ? implode(' ', array_slice($firstNames, 1)) : '';

        $lastNames = explode(' ', trim($employee->last_name));
        $firstSurname = $lastNames[0];
        $secondSurname = count($lastNames) > 1 ? implode(' ', array_slice($lastNames, 1)) : '';

        $payload = [
            'settlement_period' => [
                'month' => (int) $payroll->period_start->format('m'),
                'year' => (int) $payroll->period_start->format('Y'),
                'payroll_period_code' => $periodCode,
            ],
            'payment' => [
                'payment_method_code' => $paymentMethodCode,
                'payment_date' => $payroll->payment_date->format('Y-m-d'),
            ],
            'worker' => [
                'identification_document_code' => $docTypeCode,
                'identification_number' => (string) $employee->document_number,
                'first_name' => $firstName,
                'other_names' => $otherNames,
                'first_surname' => $firstSurname,
                'second_surname' => $secondSurname,
                'address' => $employee->address ?: 'Dirección Principal',
                'country_code' => 'CO',
                'municipality_code' => $employee->dian_municipality_code ?: ($employee->branch?->municipality_code ?? '05001'),
                'has_integral_salary' => $employee->isIntegralSalary(),
                'has_high_risk' => (bool) $employee->dian_high_risk,
                'worker_type_code' => $employee->dian_worker_type_code ?: '01',
                'worker_subtype' => $employee->dian_worker_subtype_code ?: '00',
                'contract_type' => $contractTypeCode,
                'salary' => number_format((float) $employee->base_salary, 2, '.', ''),
                'entry_date' => $employee->hire_date->format('Y-m-d'),
                'days_worked' => (string) (int) $detail->worked_days,
            ],
            'accruals' => [
                'suel' => [
                    'accrual_type_code' => '1',
                    'amount' => number_format((float) $detail->base_salary_earned, 2, '.', ''),
                ],
            ],
            'deductions' => [
                'salu' => [
                    'percentage' => '4',
                    'amount' => number_format((float) $detail->health_employee, 2, '.', ''),
                ],
                'pens' => [
                    'percentage' => '4',
                    'amount' => number_format((float) $detail->pension_employee, 2, '.', ''),
                ],
            ],
        ];

        // Numbering range
        if ($this->payrollSettings->payroll_numbering_range_id) {
            $payload['numbering_range_id'] = (int) $this->payrollSettings->payroll_numbering_range_id;
        }

        // Quincenal half
        if ($periodCode === '4') {
            $payload['settlement_period']['pay_period_half'] = $payroll->period_start->day <= 15 ? '1' : '2';
        }

        // Bank info if transfer
        if ($employee->bank_account_number) {
            $payload['payment']['bank_name'] = $employee->bank_name ?: 'BANCO';
            $payload['payment']['account_type'] = $employee->bank_account_type === 'corriente' ? '2' : '1';
            $payload['payment']['account_number'] = $employee->bank_account_number;
            $payload['payment']['payment_method_code'] = '47'; // Transferencia
        }

        // Termination date
        if ($employee->termination_date) {
            $payload['worker']['retirement_date'] = $employee->termination_date->format('Y-m-d');
        }

        // Accruals: Transport allowance
        if ((float) $detail->transport_allowance_earned > 0) {
            $payload['accruals']['tra'] = [[
                'amount' => number_format((float) $detail->transport_allowance_earned, 2, '.', ''),
                'accrual_type_code' => '1',
            ]];
        }

        // Accruals: Overtime & Surcharges
        $horasExtras = [];
        if ((float) $detail->overtime_daytime_hours > 0) {
            $horasExtras[] = [
                'quantity' => (int) $detail->overtime_daytime_hours,
                'percentage' => '25.00',
                'amount' => number_format((float) $detail->overtime_daytime_value, 2, '.', ''),
                'accrual_type_code' => '1', // Extra diurna
            ];
        }
        if ((float) $detail->overtime_nighttime_hours > 0) {
            $horasExtras[] = [
                'quantity' => (int) $detail->overtime_nighttime_hours,
                'percentage' => '75.00',
                'amount' => number_format((float) $detail->overtime_nighttime_value, 2, '.', ''),
                'accrual_type_code' => '2', // Extra nocturna
            ];
        }
        if ((float) $detail->overtime_sunday_daytime_hours > 0) {
            $horasExtras[] = [
                'quantity' => (int) $detail->overtime_sunday_daytime_hours,
                'percentage' => '100.00',
                'amount' => number_format((float) $detail->overtime_sunday_daytime_value, 2, '.', ''),
                'accrual_type_code' => '3', // Extra dominical diurna
            ];
        }
        if ((float) $detail->overtime_sunday_nighttime_hours > 0) {
            $horasExtras[] = [
                'quantity' => (int) $detail->overtime_sunday_nighttime_hours,
                'percentage' => '150.00',
                'amount' => number_format((float) $detail->overtime_sunday_nighttime_value, 2, '.', ''),
                'accrual_type_code' => '4', // Extra dominical nocturna
            ];
        }
        if ((float) $detail->night_surcharge_hours > 0) {
            $horasExtras[] = [
                'quantity' => (int) $detail->night_surcharge_hours,
                'percentage' => '35.00',
                'amount' => number_format((float) $detail->night_surcharge_value, 2, '.', ''),
                'accrual_type_code' => '5', // Recargo nocturno
            ];
        }
        if ((float) $detail->sunday_holiday_hours > 0) {
            $horasExtras[] = [
                'quantity' => (int) $detail->sunday_holiday_hours,
                'percentage' => '75.00',
                'amount' => number_format((float) $detail->sunday_holiday_value, 2, '.', ''),
                'accrual_type_code' => '6', // Recargo dominical
            ];
        }
        if (!empty($horasExtras)) {
            $payload['accruals']['hora'] = $horasExtras;
        }

        // Accruals: Commissions
        if ((float) $detail->commissions > 0) {
            $payload['accruals']['comi'] = [[
                'amount' => number_format((float) $detail->commissions, 2, '.', ''),
                'accrual_type_code' => '1',
            ]];
        }

        // Accruals: Bonuses
        $bonificaciones = [];
        if ((float) $detail->bonuses > 0) {
            $bonificaciones[] = [
                'amount' => number_format((float) $detail->bonuses, 2, '.', ''),
                'accrual_type_code' => '1', // Salarial
            ];
        }
        if ((float) $detail->non_salary_bonuses > 0) {
            $bonificaciones[] = [
                'amount' => number_format((float) $detail->non_salary_bonuses, 2, '.', ''),
                'accrual_type_code' => '2', // No salarial
            ];
        }
        if (!empty($bonificaciones)) {
            $payload['accruals']['boni'] = $bonificaciones;
        }

        // Accruals: Vacations
        if ((float) $detail->vacation_value > 0) {
            $payload['accruals']['vaca'] = [[
                'quantity' => (int) $detail->vacation_days,
                'amount' => number_format((float) $detail->vacation_value, 2, '.', ''),
                'accrual_type_code' => '1',
            ]];
        }

        // Accruals: Disabilities
        if ((float) $detail->disability_value > 0) {
            $payload['accruals']['inca'] = [[
                'quantity' => (int) $detail->disability_days,
                'amount' => number_format((float) $detail->disability_value, 2, '.', ''),
                'accrual_type_code' => '1', // General / EPS
            ]];
        }

        // Deductions: Solidarity fund
        if ((float) $detail->solidarity_fund > 0) {
            $payload['deductions']['dedu'] = [
                'percentage' => number_format(((float) $detail->solidarity_fund / ((float) $detail->total_earned ?: 1)) * 100, 2, '.', ''),
                'amount' => number_format((float) $detail->solidarity_fund, 2, '.', ''),
                'deduction_type_code' => '1',
            ];
        }

        // Deductions: Loans / Libranzas
        if ((float) $detail->loan_deduction > 0) {
            $payload['deductions']['libr'] = [[
                'amount' => number_format((float) $detail->loan_deduction, 2, '.', ''),
                'description' => 'Préstamo a empleado',
                'deduction_type_code' => '1',
            ]];
        }

        // Deductions: Advances
        if ((float) $detail->advance_deduction > 0) {
            $payload['deductions']['anti'] = [[
                'amount' => number_format((float) $detail->advance_deduction, 2, '.', ''),
                'deduction_type_code' => '1',
            ]];
        }

        // Deductions: Other deductions
        if ((float) $detail->other_deductions > 0) {
            $payload['deductions']['otra'] = [[
                'amount' => number_format((float) $detail->other_deductions, 2, '.', ''),
                'deduction_type_code' => '1',
            ]];
        }

        return $payload;
    }
}

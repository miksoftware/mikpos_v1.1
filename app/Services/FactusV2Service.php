<?php

namespace App\Services;

use App\Models\BillingSetting;
use App\Models\Sale;
use App\Models\CreditNote;
use App\Models\Customer;
use App\Models\Branch;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class FactusV2Service
{
    protected BillingSetting $settings;
    protected string $baseUrl;

    public function __construct()
    {
        $this->settings = BillingSetting::getSettings();
        $this->baseUrl = $this->settings->api_url ?: $this->settings->getDefaultApiUrl();
    }

    /**
     * Check if electronic invoicing is enabled and configured.
     */
    public function isEnabled(): bool
    {
        return $this->settings->is_enabled && $this->settings->isConfigured();
    }

    /**
     * Get valid access token, refreshing if necessary.
     */
    protected function getAccessToken(): ?string
    {
        if (!$this->settings->isConfigured()) {
            throw new Exception('Facturación electrónica no configurada');
        }

        // Check if token is valid
        if (!$this->settings->isTokenExpired() && $this->settings->access_token) {
            return $this->settings->access_token;
        }

        // Try to refresh token
        if ($this->settings->refresh_token) {
            try {
                return $this->refreshToken();
            } catch (Exception $e) {
                Log::warning('Failed to refresh Factus token: ' . $e->getMessage());
            }
        }

        // Get new token
        return $this->authenticate();
    }

    /**
     * Authenticate with Factus API.
     */
    protected function authenticate(): string
    {
        $response = Http::asForm()->post($this->baseUrl . '/oauth/token', [
            'grant_type' => 'password',
            'client_id' => $this->settings->client_id,
            'client_secret' => $this->settings->client_secret,
            'username' => $this->settings->username,
            'password' => $this->settings->password,
        ]);

        if (!$response->successful()) {
            $error = $response->json();
            throw new Exception('Error de autenticación Factus: ' . ($error['message'] ?? 'Credenciales inválidas'));
        }

        $data = $response->json();
        
        $this->settings->update([
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'] ?? null,
            'token_expires_at' => isset($data['expires_in']) 
                ? now()->addSeconds($data['expires_in']) 
                : null,
        ]);

        return $data['access_token'];
    }

    /**
     * Refresh access token.
     */
    protected function refreshToken(): string
    {
        $response = Http::asForm()->post($this->baseUrl . '/oauth/token', [
            'grant_type' => 'refresh_token',
            'client_id' => $this->settings->client_id,
            'client_secret' => $this->settings->client_secret,
            'refresh_token' => $this->settings->refresh_token,
        ]);

        if (!$response->successful()) {
            throw new Exception('Error al refrescar token');
        }

        $data = $response->json();
        
        $this->settings->update([
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'] ?? $this->settings->refresh_token,
            'token_expires_at' => isset($data['expires_in']) 
                ? now()->addSeconds($data['expires_in']) 
                : null,
        ]);

        return $data['access_token'];
    }

    /**
     * Create and validate electronic invoice with DIAN.
     */
    public function createInvoice(Sale $sale): array
    {
        if (!$this->isEnabled()) {
            throw new Exception('Facturación electrónica no está habilitada');
        }

        $token = $this->getAccessToken();
        
        // Load relationships
        $sale->load(['customer.taxDocument', 'customer.municipality', 'items.product', 'payments.paymentMethod', 'branch.municipality']);
        
        // Build invoice payload
        $payload = $this->buildInvoicePayload($sale);
        
        Log::info('Factus invoice payload', ['sale_id' => $sale->id, 'payload' => $payload]);

        $response = Http::withToken($token)
            ->acceptJson()
            ->post($this->baseUrl . '/v2/bills/validate', $payload);

        $responseData = $response->json();
        
        Log::info('Factus invoice response', ['sale_id' => $sale->id, 'response' => $responseData]);

        if (!$response->successful()) {
            // Save error response to sale for debugging
            // Mark as electronic since billing was enabled, but with error (no cufe)
            $sale->update([
                'is_electronic' => true, // It's electronic type, just failed
                'dian_response' => $responseData,
            ]);
            
            $errorMessage = $responseData['message'] ?? 'Error al crear factura electrónica';
            if (isset($responseData['errors'])) {
                $errorMessage .= ': ' . json_encode($responseData['errors']);
            }
            throw new Exception($errorMessage);
        }

        // Update sale with DIAN response
        $billData = $responseData['data'] ?? [];
        
        $sale->update([
            'is_electronic' => true,
            'cufe' => $billData['cufe'] ?? null,
            'qr_code' => $billData['links']['qr'] ?? null,
            'dian_public_url' => $billData['links']['public_url'] ?? null,
            'dian_number' => $billData['number'] ?? null,
            'dian_validated_at' => isset($billData['is_validated']) && $billData['is_validated'] ? now() : null,
            'dian_response' => $responseData,
        ]);

        return $responseData;
    }

    protected function buildInvoicePayload(Sale $sale): array
    {
        $customer = $sale->customer;
        $branch = $sale->branch;
        
        // Generate unique reference code
        $referenceCode = 'POS-' . $sale->id . '-' . time();
        
        // Update sale with reference code
        $sale->update(['reference_code' => $referenceCode]);

        // Determine payment form (1 = contado, 2 = crédito)
        $paymentForm = $sale->payment_type === 'credit' ? '2' : '1';
        
        // Get primary payment method code - must be string and valid DIAN code
        $primaryPayment = $sale->payments->first();
        $dianCode = $primaryPayment?->paymentMethod?->dian_code;
        
        Log::info('Payment method debug', [
            'sale_id' => $sale->id,
            'payment_method_id' => $primaryPayment?->payment_method_id,
            'payment_method_name' => $primaryPayment?->paymentMethod?->name,
            'dian_code_raw' => $dianCode,
        ]);
        
        // If no DIAN code, try to determine based on payment method name
        if (empty($dianCode)) {
            $methodName = strtolower($primaryPayment?->paymentMethod?->name ?? '');
            if (str_contains($methodName, 'efectivo') || str_contains($methodName, 'cash')) {
                $dianCode = '10';
            } elseif (str_contains($methodName, 'nequi') || str_contains($methodName, 'daviplata') || str_contains($methodName, 'pse') || str_contains($methodName, 'transferencia')) {
                $dianCode = '47';
            } elseif (str_contains($methodName, 'crédito') || str_contains($methodName, 'credito')) {
                $dianCode = '48';
            } elseif (str_contains($methodName, 'débito') || str_contains($methodName, 'debito')) {
                $dianCode = '49';
            } else {
                $dianCode = '10'; // Default to cash
            }
        }
        
        $paymentMethodCode = (string) $dianCode;

        $paymentDetail = [
            'payment_form' => $paymentForm,
            'payment_method_code' => $paymentMethodCode,
            'reference_code' => 'PAGO-' . $sale->id,
            'amount' => number_format($sale->total, 2, '.', ''),
        ];
        
        if ($paymentForm === '2') {
            $paymentDetail['due_date'] = $sale->payment_due_date 
                ? \Carbon\Carbon::parse($sale->payment_due_date)->format('Y-m-d') 
                : now()->format('Y-m-d');
        }

        $payload = [
            'document' => '01', // Factura electrónica de venta
            'reference_code' => $referenceCode,
            'observation' => $sale->notes ?? '',
            'payment_details' => [
                $paymentDetail
            ],
            'cash_rounding_amount' => '0.00',
        ];

        // Customer data
        $payload['customer'] = $this->buildCustomerData($customer);

        // Items
        $payload['items'] = $this->buildItemsData($sale);

        return $payload;
    }

    protected function buildCustomerData(Customer $customer): array
    {
        $dianCode = $customer->taxDocument?->dian_code ?? '13';
        
        // Map old v1 DIAN codes to v2 DIAN codes if necessary
        $docMapping = [
            '1' => '11', // Registro Civil
            '2' => '12', // Tarjeta de Identidad
            '3' => '13', // Cédula de Ciudadanía
            '4' => '21', // Tarjeta de Extranjería
            '5' => '22', // Cédula de Extranjería
            '6' => '31', // NIT
            '7' => '41', // Pasaporte
            '8' => '42', // Documento Identificación Extranjero
        ];
        
        if (isset($docMapping[$dianCode])) {
            $dianCode = $docMapping[$dianCode];
        }

        $data = [
            'identification' => $customer->document_number,
            'identification_document_code' => (string) $dianCode,
            'legal_organization_code' => $customer->customer_type === 'juridico' ? '1' : '2',
            'tribute_code' => 'ZZ', // ZZ - No aplica
            'country_code' => 'CO', // Colombia
        ];

        // Add DV for NIT
        if ($dianCode === '31') {
            $data['dv'] = (string) $this->calculateDV($customer->document_number);
        }

        // Names based on customer type
        if ($customer->customer_type === 'juridico') {
            $data['company'] = $customer->business_name ?? '';
            $data['trade_name'] = $customer->business_name ?? '';
            $data['names'] = $customer->business_name ?? '';
        } else {
            $name = trim($customer->first_name . ' ' . $customer->last_name);
            $data['company'] = $name;
            $data['trade_name'] = $name;
            $data['names'] = $name;
        }

        // Optional fields
        if ($customer->address) {
            $data['address'] = $customer->address;
        }
        if ($customer->email) {
            $data['email'] = $customer->email;
        }
        if ($customer->phone) {
            $data['phone'] = $customer->phone;
        }
        if ($customer->municipality_id) {
            $data['municipality_code'] = (string) ($customer->municipality?->dian_code ?? '11001');
        } else {
            // Default to Bogota if empty, because it's usually required by API if missing
            $data['municipality_code'] = '11001'; 
        }

        return $data;
    }

    protected function buildItemsData(Sale $sale): array
    {
        $items = [];

        $availableItems = $sale->items()->where('is_unavailable', false)->get();

        foreach ($availableItems as $item) {
            $taxRate = (float) $item->tax_rate;
            // For v2, if standard invoice, price should be base (tax excluded if tax is added in taxes array).
            // Let's assume unit_price is base price without tax.
            $basePrice = $item->unit_price;
            
            $itemData = [
                'code_reference' => $item->product_sku ?? (string) $item->product_id,
                'name' => $item->product_name,
                'quantity' => number_format((float) $item->quantity, 2, '.', ''),
                'discount_rate' => '0.00',
                'price' => number_format($basePrice, 2, '.', ''),
                'unit_measure_code' => '94', // 94 = unidad
                'standard_code' => '999', // Estándar de adopción del contribuyente
                'taxes' => [
                    [
                        'code' => '01', // IVA
                        'rate' => number_format($taxRate, 2, '.', '')
                    ]
                ]
            ];

            $items[] = $itemData;
        }

        return $items;
    }

    /**
     * Calculate verification digit (DV) for NIT.
     */
    protected function calculateDV(string $nit): int
    {
        $nit = preg_replace('/[^0-9]/', '', $nit);
        $primes = [3, 7, 13, 17, 19, 23, 29, 37, 41, 43, 47, 53, 59, 67, 71];
        
        $sum = 0;
        $nitLength = strlen($nit);
        
        for ($i = 0; $i < $nitLength; $i++) {
            $sum += (int) $nit[$nitLength - 1 - $i] * $primes[$i];
        }
        
        $remainder = $sum % 11;
        
        if ($remainder > 1) {
            return 11 - $remainder;
        }
        
        return $remainder;
    }

    /**
     * Get invoice PDF from Factus.
     */
    public function getInvoicePdf(Sale $sale): ?string
    {
        if (!$sale->dian_number || !$this->isEnabled()) {
            return null;
        }

        try {
            $token = $this->getAccessToken();
            
            $response = Http::withToken($token)
                ->acceptJson()
                ->get($this->baseUrl . '/v2/bills/' . $sale->dian_number . '/download-pdf');

            if ($response->successful()) {
                $data = $response->json();
                return $data['data']['pdf_base_64_encoded'] ?? null;
            }
        } catch (Exception $e) {
            Log::error('Error getting Factus PDF: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Get invoice status from Factus.
     */
    public function getInvoiceStatus(Sale $sale): ?array
    {
        if (!$sale->reference_code || !$this->isEnabled()) {
            return null;
        }

        try {
            $token = $this->getAccessToken();
            
            $response = Http::withToken($token)
                ->acceptJson()
                ->get($this->baseUrl . '/v2/bills/reference/' . $sale->reference_code);

            if ($response->successful()) {
                return $response->json();
            }
        } catch (Exception $e) {
            Log::error('Error getting Factus invoice status: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Create and validate credit note with DIAN.
     */
    public function createCreditNote(CreditNote $creditNote): array
    {
        if (!$this->isEnabled()) {
            throw new Exception('Facturación electrónica no está habilitada');
        }

        $token = $this->getAccessToken();
        
        // Load relationships
        $creditNote->load(['sale.customer.taxDocument', 'sale.customer.municipality', 'sale.branch.municipality', 'items']);
        
        // Build credit note payload
        $payload = $this->buildCreditNotePayload($creditNote);
        
        Log::info('Factus credit note payload', ['credit_note_id' => $creditNote->id, 'payload' => $payload]);

        $response = Http::withToken($token)
            ->acceptJson()
            ->post($this->baseUrl . '/v2/credit-notes/validate', $payload);

        $responseData = $response->json();
        
        Log::info('Factus credit note response', ['credit_note_id' => $creditNote->id, 'response' => $responseData]);

        if (!$response->successful()) {
            $creditNote->update([
                'status' => 'rejected',
                'dian_response' => $responseData,
            ]);
            
            $errorMessage = $responseData['message'] ?? 'Error al crear nota crédito';
            if (isset($responseData['errors'])) {
                $errorMessage .= ': ' . json_encode($responseData['errors']);
            }
            throw new Exception($errorMessage);
        }

        // Update credit note with DIAN response
        $noteData = $responseData['data'] ?? [];
        
        $creditNote->update([
            'status' => 'validated',
            'cufe' => $noteData['cude'] ?? $noteData['cufe'] ?? null,
            'qr_code' => $noteData['links']['qr'] ?? null,
            'dian_public_url' => $noteData['links']['public_url'] ?? null,
            'dian_number' => $noteData['number'] ?? null,
            'dian_validated_at' => now(),
            'dian_response' => $responseData,
        ]);

        return $responseData;
    }

    /**
     * Build the credit note payload for Factus API.
     */
    protected function buildCreditNotePayload(CreditNote $creditNote): array
    {
        $sale = $creditNote->sale;
        $customer = $sale->customer;
        $branch = $sale->branch;
        
        // Generate unique reference code
        $referenceCode = 'NC-' . $creditNote->id . '-' . time();
        $creditNote->update(['reference_code' => $referenceCode]);

        $payload = [
            'document' => '91', // Nota crédito electrónica
            'reference_code' => $referenceCode,
            'observation' => $creditNote->reason,
            'payment_details' => [
                [
                    'payment_form' => '1',
                    'payment_method_code' => '10', // Default to cash for credit notes
                    'reference_code' => 'PAGO-NC-' . $creditNote->id,
                    'amount' => number_format($creditNote->total, 2, '.', ''),
                ]
            ],
            // Reference to original invoice
            'numbering_range_id' => $this->getCreditNoteNumberingRangeId(),
            'bill_number' => $sale->dian_number,
            'correction_concept_code' => $creditNote->correction_concept_code,
        ];

        // Add establishment info if branch has municipality
        // Factus API might not require establishment but if so, the v2 format is just `establishment` object.
        // We will omit it for now as the required data is usually minimal.

        // Customer data (same as original invoice)
        $payload['customer'] = $this->buildCustomerData($customer);

        // Items from credit note
        $payload['items'] = $this->buildCreditNoteItemsData($creditNote);

        return $payload;
    }

    /**
     * Build items data for credit note.
     */
    protected function buildCreditNoteItemsData(CreditNote $creditNote): array
    {
        $items = [];

        foreach ($creditNote->items as $item) {
            $taxRate = (float) $item->tax_rate;
            $basePrice = $item->unit_price;
            
            $itemData = [
                'code_reference' => $item->product_sku ?? (string) $item->product_id,
                'name' => $item->product_name,
                'quantity' => number_format((float) $item->quantity, 2, '.', ''),
                'discount_rate' => '0.00',
                'price' => number_format($basePrice, 2, '.', ''),
                'unit_measure_code' => '94',
                'standard_code' => '999',
                'taxes' => [
                    [
                        'code' => '01',
                        'rate' => number_format($taxRate, 2, '.', '')
                    ]
                ]
            ];

            $items[] = $itemData;
        }

        return $items;
    }

    /**
     * Get credit note PDF from Factus.
     */
    public function getCreditNotePdf(CreditNote $creditNote): ?string
    {
        if (!$creditNote->dian_number || !$this->isEnabled()) {
            return null;
        }

        try {
            $token = $this->getAccessToken();
            
            $response = Http::withToken($token)
                ->acceptJson()
                ->get($this->baseUrl . '/v2/credit-notes/' . $creditNote->dian_number . '/download-pdf');

            if ($response->successful()) {
                $data = $response->json();
                return $data['data']['pdf_base_64_encoded'] ?? null;
            }
        } catch (Exception $e) {
            Log::error('Error getting Factus credit note PDF: ' . $e->getMessage());
        }

        return null;
    }

    protected function getCreditNoteNumberingRangeId()
    {
        return \Illuminate\Support\Facades\Cache::remember('factus_credit_note_range_id', 3600, function () {
            $token = $this->getAccessToken();
            $response = \Illuminate\Support\Facades\Http::withToken($token)->get($this->baseUrl . '/v2/numbering-ranges');
            if ($response->successful()) {
                $ranges = $response->json()['data']['data'] ?? [];
                foreach ($ranges as $range) {
                    // Look for active credit note range
                    if ($range['is_active'] && stripos($range['document'], 'crédito') !== false) {
                        return $range['id'];
                    }
                }
            }
            throw new \Exception("No se encontró un rango de numeración activo para Notas Crédito en Factus.");
        });
    }
}

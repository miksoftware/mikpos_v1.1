<?php

namespace App\Services;

use App\Models\BillingSetting;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\Branch;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class FactusService
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
            ->post($this->baseUrl . '/v1/bills/validate', $payload);

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
        $billData = $responseData['data']['bill'] ?? [];
        
        $sale->update([
            'is_electronic' => true,
            'cufe' => $billData['cufe'] ?? null,
            'qr_code' => $billData['qr_image'] ?? null, // Use qr_image (base64) instead of qr (URL)
            'dian_public_url' => $billData['public_url'] ?? null, // URL to view/download PDF
            'dian_number' => $billData['number'] ?? null,
            'dian_validated_at' => isset($billData['validated']) ? now() : null,
            'dian_response' => $responseData,
        ]);

        return $responseData;
    }

    /**
     * Build the invoice payload for Factus API.
     */
    protected function buildInvoicePayload(Sale $sale): array
    {
        $customer = $sale->customer;
        $branch = $sale->branch;
        
        // Generate unique reference code
        $referenceCode = 'POS-' . $sale->id . '-' . time();
        
        // Update sale with reference code
        $sale->update(['reference_code' => $referenceCode]);

        // Determine payment form (1 = contado, 2 = crédito)
        $paymentForm = '1'; // Default: contado
        
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

        $payload = [
            'document' => '01', // Factura electrónica de venta
            'reference_code' => $referenceCode,
            'observation' => $sale->notes ?? '',
            'payment_method_code' => $paymentMethodCode,
            'payment_form' => $paymentForm, // Must be string, not array
        ];

        // Add establishment info if branch has municipality
        if ($branch->municipality_id) {
            $payload['establishment'] = [
                'name' => $branch->name,
                'address' => $branch->address ?? '',
                'phone_number' => $branch->phone ?? '',
                'email' => $branch->email ?? '',
                'municipality_id' => $branch->municipality_id,
            ];
        }

        // Customer data
        $payload['customer'] = $this->buildCustomerData($customer);

        // Items
        $payload['items'] = $this->buildItemsData($sale);

        return $payload;
    }

    /**
     * Build customer data for Factus API.
     */
    protected function buildCustomerData(Customer $customer): array
    {
        $data = [
            'identification' => $customer->document_number,
            'identification_document_id' => (int) ($customer->taxDocument?->dian_code ?? 3), // Default: CC
            'legal_organization_id' => $customer->customer_type === 'juridico' ? 1 : 2, // 1: Persona Jurídica, 2: Persona Natural
            'tribute_id' => 21, // ZZ - No aplica (for most retail customers)
        ];

        // Add DV for NIT
        if ($customer->taxDocument?->dian_code === '6') {
            $data['dv'] = $this->calculateDV($customer->document_number);
        }

        // Names based on customer type
        if ($customer->customer_type === 'juridico') {
            $data['company'] = $customer->business_name ?? '';
            $data['trade_name'] = $customer->business_name ?? '';
        } else {
            $data['names'] = trim($customer->first_name . ' ' . $customer->last_name);
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
            $data['municipality_id'] = $customer->municipality_id;
        }

        return $data;
    }

    /**
     * Build items data for Factus API.
     */
    protected function buildItemsData(Sale $sale): array
    {
        $items = [];

        foreach ($sale->items as $item) {
            // unit_price is the base price (without tax)
            // Factus expects price with tax included
            $taxRate = (float) $item->tax_rate;
            $unitPriceWithTax = $item->unit_price * (1 + ($taxRate / 100));
            
            $items[] = [
                'code_reference' => $item->product_sku ?? (string) $item->product_id,
                'name' => $item->product_name,
                'quantity' => (int) $item->quantity,
                'discount_rate' => 0, // No discount at item level for now
                'price' => round($unitPriceWithTax, 2), // Unit price with tax included
                'tax_rate' => number_format($taxRate, 2, '.', ''),
                'unit_measure_id' => 70, // 70 = unidad (94 is the code, 70 is the ID)
                'standard_code_id' => 1, // Estándar de adopción del contribuyente
                'is_excluded' => $taxRate == 0 ? 1 : 0,
                'tribute_id' => 1, // IVA
                'withholding_taxes' => [],
            ];
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
                ->get($this->baseUrl . '/v1/bills/' . $sale->dian_number . '/download-pdf');

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
                ->get($this->baseUrl . '/v1/bills/reference/' . $sale->reference_code);

            if ($response->successful()) {
                return $response->json();
            }
        } catch (Exception $e) {
            Log::error('Error getting Factus invoice status: ' . $e->getMessage());
        }

        return null;
    }
}

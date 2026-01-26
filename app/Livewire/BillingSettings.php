<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\BillingSetting;
use App\Services\ActivityLogService;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Http;

#[Layout('layouts.app')]
class BillingSettings extends Component
{
    public $is_enabled = false;
    public $environment = 'sandbox';
    public $api_url = '';
    public $client_id = '';
    public $client_secret = '';
    public $username = '';
    public $password = '';
    
    public $showPassword = false;
    public $showClientSecret = false;
    public $testResult = null;
    public $isTesting = false;

    public function mount()
    {
        $settings = BillingSetting::getSettings();
        
        $this->is_enabled = $settings->is_enabled;
        $this->environment = $settings->environment;
        $this->api_url = $settings->api_url ?? $settings->getDefaultApiUrl();
        $this->client_id = $settings->client_id ?? '';
        $this->client_secret = $settings->client_secret ?? '';
        $this->username = $settings->username ?? '';
        $this->password = $settings->password ?? '';
    }

    public function updatedEnvironment($value)
    {
        // Update API URL when environment changes
        $settings = new BillingSetting(['provider' => 'factus', 'environment' => $value]);
        $this->api_url = $settings->getDefaultApiUrl();
    }

    public function save()
    {
        $this->validate([
            'environment' => 'required|in:sandbox,production',
            'api_url' => 'nullable|url',
            'client_id' => 'nullable|string|max:255',
            'client_secret' => 'nullable|string',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string',
        ]);

        $settings = BillingSetting::getSettings();
        $oldValues = $settings->toArray();

        $settings->update([
            'is_enabled' => $this->is_enabled,
            'environment' => $this->environment,
            'api_url' => $this->api_url ?: $settings->getDefaultApiUrl(),
            'client_id' => $this->client_id ?: null,
            'client_secret' => $this->client_secret ?: null,
            'username' => $this->username ?: null,
            'password' => $this->password ?: null,
        ]);

        ActivityLogService::logUpdate('billing_settings', $settings, $oldValues, "Configuración de facturación electrónica actualizada");

        $this->dispatch('notify', message: 'Configuración guardada correctamente', type: 'success');
    }

    public function toggleEnabled()
    {
        $this->is_enabled = !$this->is_enabled;
        $this->save();
    }

    public function testConnection()
    {
        $this->isTesting = true;
        $this->testResult = null;

        if (empty($this->client_id) || empty($this->client_secret) || empty($this->username) || empty($this->password)) {
            $this->testResult = [
                'success' => false,
                'message' => 'Por favor complete todos los campos de credenciales antes de probar la conexión.',
            ];
            $this->isTesting = false;
            return;
        }

        try {
            $baseUrl = $this->api_url ?: (new BillingSetting(['provider' => 'factus', 'environment' => $this->environment]))->getDefaultApiUrl();
            
            // Factus uses OAuth 2.0 password grant
            $response = Http::asForm()->post($baseUrl . '/oauth/token', [
                'grant_type' => 'password',
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret,
                'username' => $this->username,
                'password' => $this->password,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Save the tokens
                $settings = BillingSetting::getSettings();
                $settings->update([
                    'access_token' => $data['access_token'] ?? null,
                    'refresh_token' => $data['refresh_token'] ?? null,
                    'token_expires_at' => isset($data['expires_in']) 
                        ? now()->addSeconds($data['expires_in']) 
                        : null,
                ]);

                $this->testResult = [
                    'success' => true,
                    'message' => 'Conexión exitosa. Token obtenido correctamente.',
                ];
            } else {
                $error = $response->json();
                $this->testResult = [
                    'success' => false,
                    'message' => 'Error de autenticación: ' . ($error['message'] ?? $error['error_description'] ?? 'Credenciales inválidas'),
                ];
            }
        } catch (\Exception $e) {
            $this->testResult = [
                'success' => false,
                'message' => 'Error de conexión: ' . $e->getMessage(),
            ];
        }

        $this->isTesting = false;
    }

    public function render()
    {
        $settings = BillingSetting::getSettings();
        
        return view('livewire.billing-settings', [
            'hasValidToken' => !$settings->isTokenExpired() && $settings->access_token,
            'tokenExpiresAt' => $settings->token_expires_at,
            'isConfigured' => $settings->isConfigured(),
        ]);
    }
}

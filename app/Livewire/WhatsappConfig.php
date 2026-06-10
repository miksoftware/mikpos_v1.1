<?php

namespace App\Livewire;

use App\Models\WhatsappConfig as WhatsappConfigModel;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Http;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Throwable;

#[Layout('layouts.app')]
class WhatsappConfig extends Component
{
    public $branch_id;
    public $phone_number_id = '';
    public $waba_id = '';
    public $token_permanente = '';
    public $api_version = 'v25.0';
    public $phone_number_oficial = '';
    public $is_active = true;
    public $test_recipient = '';
    public $test_template_name = 'hello_world';
    public $test_template_language = 'en_US';
    public $test_template_parameters = '';
    public $testResult = null;

    public function mount()
    {
        $this->branch_id = auth()->user()->branch_id;
        $config = WhatsappConfigModel::where('branch_id', $this->branch_id)->first();

        if ($config) {
            $this->phone_number_id = $config->phone_number_id;
            $this->waba_id = $config->waba_id;
            $this->token_permanente = $config->token_permanente;
            $this->api_version = $config->api_version;
            $this->phone_number_oficial = $config->phone_number_oficial;
            $this->is_active = $config->is_active;
        }
    }

    public function save()
    {
        $this->validate([
            'phone_number_id' => 'required|string|max:255',
            'waba_id' => 'required|string|max:255',
            'token_permanente' => 'required|string',
            'api_version' => 'required|string|max:50',
            'phone_number_oficial' => 'required|string|max:255',
            'is_active' => 'boolean',
        ]);

        $config = WhatsappConfigModel::firstOrNew(['branch_id' => $this->branch_id]);
        
        $oldValues = $config->exists ? $config->toArray() : [];

        $config->phone_number_id = $this->phone_number_id;
        $config->waba_id = $this->waba_id;
        $config->token_permanente = $this->token_permanente;
        $config->api_version = $this->api_version;
        $config->phone_number_oficial = $this->phone_number_oficial;
        $config->is_active = $this->is_active;
        $config->save();

        ActivityLogService::logUpdate('whatsapp_config', $config, $oldValues, "Configuración de WhatsApp actualizada");

        $this->dispatch('notify', message: 'Configuración de WhatsApp guardada correctamente', type: 'success');
    }

    public function toggleActive()
    {
        $this->is_active = !$this->is_active;
        $this->save();
    }

    public function sendTestMessage()
    {
        $this->validate([
            'phone_number_id' => 'required|string|max:255',
            'token_permanente' => 'required|string',
            'api_version' => 'required|string|max:50',
            'test_recipient' => 'required|string|max:30',
            'test_template_name' => 'required|string|max:255',
            'test_template_language' => 'required|string|max:20',
            'test_template_parameters' => 'nullable|string',
        ]);

        if (!$this->is_active) {
            $this->dispatch('notify', message: 'Activa primero la integración de WhatsApp para enviar pruebas', type: 'error');
            return;
        }

        try {
            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $this->sanitizePhoneNumber($this->test_recipient),
                'type' => 'template',
                'template' => [
                    'name' => trim($this->test_template_name),
                    'language' => [
                        'code' => trim($this->test_template_language),
                    ],
                ],
            ];

            $parameters = $this->parseTemplateParameters();
            if (!empty($parameters)) {
                $payload['template']['components'] = [
                    [
                        'type' => 'body',
                        'parameters' => $parameters,
                    ],
                ];
            }

            $response = Http::withToken(trim($this->token_permanente))
                ->acceptJson()
                ->post($this->getMessagesEndpoint(), $payload);

            $responseData = $response->json();

            if (!$response->successful()) {
                $errorMessage = data_get($responseData, 'error.message')
                    ?? data_get($responseData, 'message')
                    ?? 'Meta no acepto el mensaje de prueba.';

                $this->testResult = [
                    'success' => false,
                    'message' => $errorMessage,
                    'response' => $responseData,
                    'payload' => $payload,
                ];

                $this->dispatch('notify', message: $errorMessage, type: 'error');
                return;
            }

            ActivityLogService::log(
                'whatsapp_config',
                'send_test',
                'Mensaje de prueba de WhatsApp enviado',
                null,
                null,
                [
                    'to' => $payload['to'],
                    'template' => $payload['template']['name'],
                    'response' => $responseData,
                ]
            );

            $this->testResult = [
                'success' => true,
                'message' => 'Mensaje de prueba enviado correctamente.',
                'response' => $responseData,
                'payload' => $payload,
            ];

            $this->dispatch('notify', message: 'Mensaje de prueba enviado correctamente', type: 'success');
        } catch (Throwable $e) {
            $this->testResult = [
                'success' => false,
                'message' => $e->getMessage(),
            ];

            $this->dispatch('notify', message: $e->getMessage(), type: 'error');
        }
    }

    protected function getMessagesEndpoint(): string
    {
        return sprintf(
            'https://graph.facebook.com/%s/%s/messages',
            trim($this->api_version),
            trim($this->phone_number_id)
        );
    }

    protected function sanitizePhoneNumber(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? '';
    }

    protected function parseTemplateParameters(): array
    {
        $rawParameters = trim($this->test_template_parameters);

        if ($rawParameters === '') {
            return [];
        }

        $decoded = json_decode($rawParameters, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            throw new \RuntimeException('Los parametros del template deben ser un JSON valido.');
        }

        return $decoded;
    }

    public function render()
    {
        return view('livewire.whatsapp-config');
    }
}

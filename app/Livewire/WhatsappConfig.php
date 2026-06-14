<?php

namespace App\Livewire;

use App\Models\Branch;
use App\Models\WhatsappConfig as WhatsappConfigModel;
use App\Models\WhatsappMessageLog;
use App\Services\ActivityLogService;
use App\Services\WhatsappMessageLogService;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Throwable;

#[Layout('layouts.app')]
class WhatsappConfig extends Component
{
    public $branch_id;
    public $branches = [];
    public $canSelectBranch = false;
    public $phone_number_id = '';
    public $waba_id = '';
    public $token_permanente = '';
    public $api_version = 'v25.0';
    public $phone_number_oficial = '';
    public $template_name = 'mikpos';
    public $template_language = 'es_CO';
    public $is_active = true;
    public $test_recipient = '';
    public $test_template_name = 'hello_world';
    public $test_template_language = 'en_US';
    public $test_template_parameters = '';
    public $testResult = null;

    public function mount()
    {
        $user = auth()->user();

        $branchQuery = Branch::query()
            ->where('is_active', true)
            ->orderBy('name');

        if ($user->isSuperAdmin()) {
            $this->canSelectBranch = true;
        } else {
            $branchQuery->where('id', $user->branch_id);
        }

        $this->branches = $branchQuery
            ->get(['id', 'name'])
            ->map(fn (Branch $branch) => [
                'id' => $branch->id,
                'name' => $branch->name,
            ])
            ->all();

        $this->branch_id = $user->branch_id ?: ($this->branches[0]['id'] ?? null);
        $this->loadConfigForSelectedBranch();
    }

    public function updatedBranchId()
    {
        $this->loadConfigForSelectedBranch();
    }

    public function save()
    {
        $this->validate($this->rules());
        $this->ensureCanAccessSelectedBranch();

        $config = WhatsappConfigModel::firstOrNew(['branch_id' => $this->branch_id]);

        $oldValues = $config->exists ? $config->toArray() : [];

        $config->branch_id = $this->branch_id;
        $config->phone_number_id = $this->phone_number_id;
        $config->waba_id = $this->waba_id;
        $config->token_permanente = $this->token_permanente;
        $config->api_version = $this->api_version;
        $config->phone_number_oficial = $this->phone_number_oficial;
        $config->template_name = $this->template_name;
        $config->template_language = $this->template_language;
        $config->is_active = $this->is_active;
        $config->save();

        ActivityLogService::logUpdate('whatsapp_config', $config, $oldValues, 'Configuración de WhatsApp actualizada');

        $this->dispatch('notify', message: 'Configuración de WhatsApp guardada correctamente', type: 'success');
    }

    public function toggleActive()
    {
        $this->is_active = !$this->is_active;
        $this->save();
    }

    public function sendTestMessage()
    {
        $this->validate(array_merge($this->rules(), [
            'test_recipient' => 'required|string|max:30',
            'test_template_name' => 'required|string|max:255',
            'test_template_language' => 'required|string|max:20',
            'test_template_parameters' => 'nullable|string',
        ]));
        $this->ensureCanAccessSelectedBranch();

        if (!$this->is_active) {
            $this->dispatch('notify', message: 'Activa primero la integración de WhatsApp para enviar pruebas', type: 'error');
            return;
        }

        try {
            // #region debug-point C:test-send-entry
            $this->dbg('test-send-entry', 'C', [
                'branch_id' => $this->branch_id,
                'phone_number_id' => $this->phone_number_id,
                'recipient_raw' => $this->test_recipient,
                'recipient_sanitized' => $this->sanitizePhoneNumber($this->test_recipient),
                'template_name' => trim($this->test_template_name),
                'template_language' => trim($this->test_template_language),
            ], '[DEBUG] test send entry');
            // #endregion

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

            // #region debug-point C:test-send-response
            $this->dbg('test-send-response', 'C', [
                'ok' => $response->successful(),
                'status_code' => $response->status(),
                'response' => $responseData,
            ], '[DEBUG] test send response');
            // #endregion

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
                    'branch_id' => $this->branch_id,
                    'to' => $payload['to'],
                    'template' => $payload['template']['name'],
                    'response' => $responseData,
                ]
            );

            WhatsappMessageLogService::recordAcceptedOutbound(
                $this->getConfigSnapshotModel(),
                $payload,
                $responseData
            );

            $this->testResult = [
                'success' => true,
                'message' => 'Meta acepto el mensaje de prueba. Revisa abajo los estados del webhook para confirmar entrega.',
                'response' => $responseData,
                'payload' => $payload,
            ];

            $this->dispatch('notify', message: 'Meta acepto el mensaje de prueba. Esperando estados del webhook.', type: 'success');
        } catch (Throwable $e) {
            $this->testResult = [
                'success' => false,
                'message' => $e->getMessage(),
            ];

            $this->dispatch('notify', message: $e->getMessage(), type: 'error');
        }
    }

    protected function rules(): array
    {
        return [
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')],
            'phone_number_id' => 'required|string|max:255',
            'waba_id' => 'required|string|max:255',
            'token_permanente' => 'required|string',
            'api_version' => 'required|string|max:50',
            'phone_number_oficial' => 'required|string|max:255',
            'template_name' => 'required|string|max:255',
            'template_language' => 'required|string|max:20',
            'is_active' => 'boolean',
        ];
    }

    protected function ensureCanAccessSelectedBranch(): void
    {
        $user = auth()->user();

        if (!$this->branch_id) {
            throw new \RuntimeException('Debes seleccionar una sucursal.');
        }

        if (!$user->canAccessBranch((int) $this->branch_id)) {
            throw new \RuntimeException('No tienes permiso para configurar esta sucursal.');
        }
    }

    protected function loadConfigForSelectedBranch(): void
    {
        $this->resetConfigFields();

        if (!$this->branch_id) {
            return;
        }

        $config = WhatsappConfigModel::where('branch_id', $this->branch_id)->first();

        if ($config) {
            $this->phone_number_id = $config->phone_number_id;
            $this->waba_id = $config->waba_id;
            $this->token_permanente = $config->token_permanente;
            $this->api_version = $config->api_version;
            $this->phone_number_oficial = $config->phone_number_oficial;
            $this->template_name = $config->template_name ?: 'mikpos';
            $this->template_language = $config->template_language ?: 'es_CO';
            $this->is_active = $config->is_active;
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

    protected function getConfigSnapshotModel(): WhatsappConfigModel
    {
        $config = WhatsappConfigModel::where('branch_id', $this->branch_id)->first();

        if ($config) {
            return $config;
        }

        $snapshot = new WhatsappConfigModel();
        $snapshot->branch_id = $this->branch_id;
        $snapshot->phone_number_id = $this->phone_number_id;
        $snapshot->waba_id = $this->waba_id;
        $snapshot->token_permanente = $this->token_permanente;
        $snapshot->api_version = $this->api_version;
        $snapshot->phone_number_oficial = $this->phone_number_oficial;
        $snapshot->template_name = $this->template_name;
        $snapshot->template_language = $this->template_language;
        $snapshot->is_active = $this->is_active;

        return $snapshot;
    }

    protected function resetConfigFields(): void
    {
        $this->phone_number_id = '';
        $this->waba_id = '';
        $this->token_permanente = '';
        $this->api_version = 'v25.0';
        $this->phone_number_oficial = '';
        $this->template_name = 'mikpos';
        $this->template_language = 'es_CO';
        $this->is_active = true;
        $this->testResult = null;
    }

    // #region debug-point dbg-helper
    protected function dbg(string $pointId, string $hypothesisId, array $data, string $msg): void
    {
        try {
            $envPath = base_path('.dbg/whatsapp-accepted-only.env');
            $debugUrl = null;
            $sessionId = 'whatsapp-accepted-only';
            if (is_file($envPath)) {
                $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
                foreach ($lines as $line) {
                    if (str_starts_with($line, 'DEBUG_SERVER_URL=')) {
                        $debugUrl = trim(substr($line, strlen('DEBUG_SERVER_URL=')));
                    }
                    if (str_starts_with($line, 'DEBUG_SESSION_ID=')) {
                        $sessionId = trim(substr($line, strlen('DEBUG_SESSION_ID=')));
                    }
                }
            }
            if (!$debugUrl) {
                return;
            }

            Http::timeout(1)->asJson()->post($debugUrl, [
                'sessionId' => $sessionId,
                'runId' => 'pre-fix',
                'hypothesisId' => $hypothesisId,
                'pointId' => $pointId,
                'location' => 'WhatsappConfig',
                'msg' => $msg,
                'ts' => round(microtime(true) * 1000),
                'data' => $data,
            ]);
        } catch (\Throwable $e) {
        }
    }
    // #endregion

    public function render()
    {
        $recentLogs = collect();

        if ($this->branch_id) {
            $recentLogs = WhatsappMessageLog::query()
                ->where('branch_id', $this->branch_id)
                ->orderByDesc('last_status_at')
                ->orderByDesc('id')
                ->limit(20)
                ->get();
        }

        return view('livewire.whatsapp-config', [
            'recentLogs' => $recentLogs,
            'webhookUrl' => route('whatsapp.webhook.receive'),
            'webhookVerifyToken' => (string) config('services.whatsapp.webhook_verify_token'),
            'webhookAppSecretConfigured' => (string) config('services.whatsapp.webhook_app_secret') !== '',
        ]);
    }
}

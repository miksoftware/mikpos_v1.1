<?php

namespace App\Livewire;

use App\Models\Branch;
use App\Models\EvoWhatsappConfig as EvoWhatsappConfigModel;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Throwable;

#[Layout('layouts.app')]
class EvoWhatsappConfig extends Component
{
    public $branch_id;
    public $branches = [];
    public $canSelectBranch = false;
    
    public $instance_name = '';
    public $instance_token = '';
    public $status = 'disconnected';
    public $is_active = true;
    public $qr_code = null;
    
    public $server_url;
    public $global_api_key;

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
        
        $this->server_url = rtrim(config('services.evo_whatsapp.server_url'), '/');
        $this->global_api_key = config('services.evo_whatsapp.global_api_key');

        $this->loadConfigForSelectedBranch();
    }

    public function updatedBranchId()
    {
        $this->loadConfigForSelectedBranch();
    }

    public function saveGlobalSettings()
    {
        if (!auth()->user()->isSuperAdmin()) {
            $this->dispatch('notify', message: 'No tienes permisos para modificar la configuración global.', type: 'error');
            return;
        }

        $this->validate([
            'server_url' => 'required|url',
            'global_api_key' => 'required|string',
        ]);

        $this->updateEnv([
            'EVO_WHATSAPP_SERVER_URL' => $this->server_url,
            'EVO_WHATSAPP_GLOBAL_API_KEY' => $this->global_api_key,
        ]);

        $this->dispatch('notify', message: 'Configuración global guardada correctamente.', type: 'success');
    }

    protected function updateEnv(array $values)
    {
        $envFile = app()->environmentFilePath();
        $str = file_get_contents($envFile);

        if (count($values) > 0) {
            foreach ($values as $envKey => $envValue) {
                $str .= "\n"; // Ensure new lines at the end before adding
                $keyPosition = strpos($str, "{$envKey}=");
                $endOfLinePosition = strpos($str, "\n", $keyPosition);
                $oldLine = substr($str, $keyPosition, $endOfLinePosition - $keyPosition);

                // If key doesn't exist, append it
                if (!$keyPosition || !$endOfLinePosition || !$oldLine) {
                    $str .= "{$envKey}={$envValue}\n";
                } else {
                    $str = str_replace($oldLine, "{$envKey}={$envValue}", $str);
                }
            }
        }

        $str = substr($str, 0, -1);
        if (!file_put_contents($envFile, $str)) {
            return false;
        }
        return true;
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
        $this->instance_name = '';
        $this->instance_token = '';
        $this->status = 'disconnected';
        $this->is_active = true;
        $this->qr_code = null;

        if (!$this->branch_id) {
            return;
        }

        $config = EvoWhatsappConfigModel::where('branch_id', $this->branch_id)->first();

        if ($config) {
            $this->instance_name = $config->instance_name;
            $this->instance_token = $config->instance_token;
            $this->status = $config->status;
            $this->is_active = $config->is_active;
            
            $this->checkStatus();
        } else {
            $this->instance_name = 'branch_' . $this->branch_id . '_' . uniqid();
        }
    }
    
    public function toggleActive()
    {
        $this->ensureCanAccessSelectedBranch();
        
        $this->is_active = !$this->is_active;
        
        $config = EvoWhatsappConfigModel::firstOrNew(['branch_id' => $this->branch_id]);
        $config->is_active = $this->is_active;
        if(!$config->exists){
            $config->instance_name = $this->instance_name;
        }
        $config->save();
        
        $this->dispatch('notify', message: 'Configuración actualizada', type: 'success');
    }

    public function createInstance()
    {
        $this->ensureCanAccessSelectedBranch();
        
        if (empty($this->global_api_key)) {
            $this->dispatch('notify', message: 'API Key global no configurada en el sistema.', type: 'error');
            return;
        }

        try {
            $rawToken = uniqid('evo_');
            $response = Http::withHeaders([
                'apikey' => $this->global_api_key,
                'Content-Type' => 'application/json',
            ])->post($this->server_url . '/instance/create', [
                'name' => $this->instance_name,
                'token' => $rawToken,
                'qrcode' => true
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                $config = EvoWhatsappConfigModel::firstOrNew(['branch_id' => $this->branch_id]);
                $config->instance_name = $data['instance']['name'] ?? $data['instance']['instanceName'] ?? $this->instance_name;
                
                // CRITICAL: We must store the raw token we generated, not the hash returned by Evolution GO
                $config->instance_token = $rawToken;
                
                $config->status = $data['instance']['status'] ?? 'connecting';
                $config->save();

                $this->instance_token = $config->instance_token;
                $this->status = $config->status;
                
                if (isset($data['qrcode']['base64'])) {
                    $this->qr_code = $data['qrcode']['base64'];
                } else {
                    $this->connectInstance();
                }
                
                $this->dispatch('notify', message: 'Instancia creada exitosamente', type: 'success');
            } else {
                $errBody = $response->body();
                $errJson = json_decode($errBody, true);
                $msg = $errJson['message'] ?? $errJson['response']['message'] ?? $errBody;
                if (is_array($msg)) $msg = json_encode($msg);
                
                if (stripos($msg, 'already exists') !== false || $response->status() === 409 || $response->status() === 400) {
                    $this->dispatch('notify', message: 'La instancia ya existe en el servidor. Haz clic en "Obtener QR / Conectar" para conectarla.', type: 'warning');
                } else {
                    $this->dispatch('notify', message: 'Error al crear instancia: ' . substr($msg, 0, 100), type: 'error');
                }
            }
        } catch (Throwable $e) {
            $this->dispatch('notify', message: 'Error de conexión: ' . $e->getMessage(), type: 'error');
        }
    }
    
    public function connectInstance()
    {
        $this->ensureCanAccessSelectedBranch();
        
        try {
            $tryEndpoints = [
                ['method' => 'get', 'url' => '/instance/connect/' . $this->instance_name, 'header' => $this->global_api_key, 'body' => []],
                ['method' => 'get', 'url' => '/instance/qr/' . $this->instance_name, 'header' => $this->global_api_key, 'body' => []],
                ['method' => 'get', 'url' => '/instance/qr', 'header' => $this->instance_token, 'body' => []],
                ['method' => 'post', 'url' => '/instance/connect', 'header' => $this->global_api_key, 'body' => ['instance' => $this->instance_name]],
                ['method' => 'post', 'url' => '/instance/connect', 'header' => $this->instance_token, 'body' => ['instance' => $this->instance_name]],
            ];

            $success = false;
            $lastError = '';

            foreach ($tryEndpoints as $ep) {
                $req = Http::withHeaders(['apikey' => $ep['header'], 'Content-Type' => 'application/json']);
                if ($ep['method'] === 'get') {
                    $response = $req->get($this->server_url . $ep['url']);
                } else {
                    $response = $req->post($this->server_url . $ep['url'], $ep['body']);
                }

                if ($response->successful()) {
                    $data = $response->json();
                    $b64 = $data['base64'] ?? $data['qrcode']['base64'] ?? $data['qrcode'] ?? $data['code'] ?? null;
                    if ($b64 && is_string($b64) && strlen($b64) > 50) {
                        // Ensure it has data URI prefix
                        if (strpos($b64, 'data:image') !== 0) {
                            $b64 = 'data:image/png;base64,' . str_replace('data:image/png;base64,', '', $b64);
                        }
                        $this->qr_code = $b64;
                        $this->status = 'connecting';
                        $success = true;
                        break;
                    }
                } else {
                    $lastError = $response->status() . ' - ' . substr($response->body(), 0, 50);
                }
            }

            if (!$success) {
                $this->dispatch('notify', message: 'Error al obtener QR: ' . $lastError, type: 'error');
            }
        } catch (Throwable $e) {
            $this->dispatch('notify', message: 'Error de conexión: ' . $e->getMessage(), type: 'error');
        }
    }

    public function checkStatus()
    {
        if (empty($this->instance_name)) return;
        
        try {
            $endpoints = [
                ['url' => '/instance/connectionState/' . $this->instance_name, 'header' => $this->global_api_key],
                ['url' => '/instance/status', 'header' => $this->instance_token],
                ['url' => '/instance/status/' . $this->instance_name, 'header' => $this->global_api_key],
            ];
            
            $data = null;
            foreach ($endpoints as $ep) {
                $response = Http::withHeaders(['apikey' => $ep['header']])->get($this->server_url . $ep['url']);
                if ($response->successful()) {
                    $data = $response->json();
                    if(isset($data['instance']['state']) || isset($data['state'])) {
                        break;
                    }
                }
            }

            if ($data) {
                $newStatus = $data['instance']['state'] ?? $data['state'] ?? 'disconnected';
                
                if ($newStatus === 'open') {
                    $this->status = 'connected';
                    $this->qr_code = null;
                } else {
                    $this->status = $newStatus;
                }
                
                $config = EvoWhatsappConfigModel::where('branch_id', $this->branch_id)->first();
                if ($config && $config->status !== $this->status) {
                    $config->status = $this->status;
                    $config->save();
                }
            }
        } catch (Throwable $e) {
            // Silently fail status check to avoid spamming errors
        }
    }
    
    public function logoutInstance()
    {
        $this->ensureCanAccessSelectedBranch();
        
        try {
            // In Evolution GO, logout is DELETE /instance/logout using the instance token
            $response = Http::withHeaders([
                'apikey' => $this->instance_token,
            ])->delete($this->server_url . '/instance/logout');

            if (!$response->successful()) {
                // Fallback for v1
                $response = Http::withHeaders([
                    'apikey' => $this->global_api_key,
                ])->delete($this->server_url . '/instance/logout/' . $this->instance_name);
            }

            if ($response->successful()) {
                $this->status = 'disconnected';
                $this->qr_code = null;
                
                $config = EvoWhatsappConfigModel::where('branch_id', $this->branch_id)->first();
                if ($config) {
                    $config->status = 'disconnected';
                    $config->save();
                }
                
                $this->dispatch('notify', message: 'Sesión cerrada exitosamente', type: 'success');
            } else {
                $this->dispatch('notify', message: 'Error al cerrar sesión', type: 'error');
            }
        } catch (Throwable $e) {
            $this->dispatch('notify', message: 'Error de conexión', type: 'error');
        }
    }

    public function deleteInstance()
    {
        $this->ensureCanAccessSelectedBranch();
        
        try {
            // Try to logout first (Evolution GO often requires this)
            Http::withHeaders(['apikey' => $this->global_api_key])->delete($this->server_url . '/instance/logout/' . $this->instance_name);
            Http::withHeaders(['apikey' => $this->instance_token])->delete($this->server_url . '/instance/logout');

            // Try to delete instance
            $res = Http::withHeaders(['apikey' => $this->global_api_key])->delete($this->server_url . '/instance/delete/' . $this->instance_name);
            if (!$res->successful()) {
                $res = Http::withHeaders(['apikey' => $this->instance_token])->delete($this->server_url . '/instance/delete/' . $this->instance_name);
            }

            // Regardless of API success (to prevent getting stuck), we delete locally
            $config = EvoWhatsappConfigModel::where('branch_id', $this->branch_id)->first();
            if ($config) {
                $config->delete();
            }
            
            $this->instance_name = 'branch_' . $this->branch_id . '_' . uniqid();
            $this->instance_token = '';
            $this->status = 'disconnected';
            $this->qr_code = null;
            
            if ($res->successful() || $res->status() == 404) {
                $this->dispatch('notify', message: 'Instancia eliminada exitosamente', type: 'success');
            } else {
                $this->dispatch('notify', message: 'Eliminada localmente. Error en API: ' . $res->status(), type: 'warning');
            }
            
        } catch (Throwable $e) {
            $this->dispatch('notify', message: 'Error de conexión: ' . $e->getMessage(), type: 'error');
        }
    }

    public function render()
    {
        return view('livewire.evo-whatsapp-config');
    }
}

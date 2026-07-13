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
            $response = Http::withHeaders([
                'apikey' => $this->global_api_key,
                'Content-Type' => 'application/json',
            ])->post($this->server_url . '/instance/create', [
                'instanceName' => $this->instance_name,
                'token' => uniqid('evo_'),
                'qrcode' => true
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                $config = EvoWhatsappConfigModel::firstOrNew(['branch_id' => $this->branch_id]);
                $config->instance_name = $data['instance']['instanceName'] ?? $this->instance_name;
                $config->instance_token = $data['hash']['apikey'] ?? $this->global_api_key;
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
                $this->dispatch('notify', message: 'Error al crear instancia: ' . $response->body(), type: 'error');
            }
        } catch (Throwable $e) {
            $this->dispatch('notify', message: 'Error de conexión: ' . $e->getMessage(), type: 'error');
        }
    }
    
    public function connectInstance()
    {
        $this->ensureCanAccessSelectedBranch();
        
        try {
            $response = Http::withHeaders([
                'apikey' => $this->global_api_key,
            ])->get($this->server_url . '/instance/connect/' . $this->instance_name);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['base64'])) {
                    $this->qr_code = $data['base64'];
                    $this->status = 'connecting';
                }
            } else {
                $this->dispatch('notify', message: 'Error al obtener QR', type: 'error');
            }
        } catch (Throwable $e) {
            $this->dispatch('notify', message: 'Error de conexión', type: 'error');
        }
    }

    public function checkStatus()
    {
        if (empty($this->instance_name)) return;
        
        try {
            $response = Http::withHeaders([
                'apikey' => $this->global_api_key,
            ])->get($this->server_url . '/instance/connectionState/' . $this->instance_name);

            if ($response->successful()) {
                $data = $response->json();
                $newStatus = $data['instance']['state'] ?? 'disconnected';
                
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
    
    public function deleteInstance()
    {
        $this->ensureCanAccessSelectedBranch();
        
        try {
            $response = Http::withHeaders([
                'apikey' => $this->global_api_key,
            ])->delete($this->server_url . '/instance/delete/' . $this->instance_name);

            if ($response->successful()) {
                EvoWhatsappConfigModel::where('branch_id', $this->branch_id)->delete();
                $this->loadConfigForSelectedBranch();
                $this->dispatch('notify', message: 'Instancia eliminada exitosamente', type: 'success');
            } else {
                $this->dispatch('notify', message: 'Error al eliminar instancia', type: 'error');
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

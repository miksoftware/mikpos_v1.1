<?php

namespace App\Livewire;

use App\Mail\PromotionMail;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Promotion;
use App\Models\WhatsappConfig;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Promotions extends Component
{
    use WithPagination, WithFileUploads;

    // Filters
    public string $search = '';
    public string $filterStatus = '';

    // Create / Edit modal
    public bool $isModalOpen = false;
    public ?int $itemId = null;
    public ?int $formBranchId = null;
    public string $campaignChannel = 'email';
    public string $subject = '';
    public string $message = '';
    public string $button_text = '';
    public string $button_url = '';
    public $image = null;
    public ?string $existingImagePath = null;

    // Send modal
    public bool $isSendModalOpen = false;
    public ?int $sendingPromoId = null;
    public bool $sendToAll = true;
    public string $customerSearch = '';
    public array $selectedCustomerIds = [];
    public string $sendChannel = 'email';
    public string $whatsappTemplateName = 'mikpos';
    public string $whatsappTemplateLanguage = 'es_CO';

    // Delete modal
    public bool $isDeleteModalOpen = false;
    public ?int $deleteId = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    // ─── Create / Edit ───────────────────────────────────────────────────────

    public function create(): void
    {
        if (!auth()->user()->hasPermission('promotions.create')) {
            $this->dispatch('notify', message: 'No tienes permiso para crear campañas', type: 'error');
            return;
        }
        $this->resetForm();
        $this->campaignChannel = 'email';
        if (auth()->user()->isSuperAdmin()) {
            $this->formBranchId = Branch::where('is_active', true)->value('id');
        }
        $this->isModalOpen = true;
    }

    public function edit(int $id): void
    {
        if (!auth()->user()->hasPermission('promotions.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso para editar campañas', type: 'error');
            return;
        }
        $promo = Promotion::findOrFail($id);
        $this->itemId = $promo->id;
        $this->formBranchId = $promo->branch_id;
        $this->campaignChannel = $promo->channel ?: 'email';
        $this->subject = $promo->subject;
        $this->message = $promo->message;
        $this->button_text = $promo->button_text ?? '';
        $this->button_url = $promo->button_url ?? '';
        $this->existingImagePath = $promo->image_path;
        $this->image = null;
        $this->isModalOpen = true;
    }

    public function updatedCampaignChannel(): void
    {
        $this->resetValidation();
    }

    public function save(): void
    {
        $isNew = !$this->itemId;

        if ($isNew && !auth()->user()->hasPermission('promotions.create')) {
            $this->dispatch('notify', message: 'Sin permiso', type: 'error');
            return;
        }
        if (!$isNew && !auth()->user()->hasPermission('promotions.edit')) {
            $this->dispatch('notify', message: 'Sin permiso', type: 'error');
            return;
        }

        $rules = [
            'campaignChannel' => 'required|in:email,whatsapp',
            'image' => 'nullable|image|max:3072',
            ...(auth()->user()->isSuperAdmin() ? ['formBranchId' => 'required|exists:branches,id'] : []),
        ];

        if ($this->campaignChannel === 'email') {
            $rules = array_merge($rules, [
                'subject' => 'required|min:3|max:255',
                'message' => 'required|min:5',
                'button_text' => 'nullable|max:100',
                'button_url' => 'nullable|url|max:500',
            ]);
        }

        $this->validate($rules, [
            'formBranchId.required' => 'Debes seleccionar una sucursal.',
            'formBranchId.exists' => 'La sucursal seleccionada no es válida.',
        ]);

        // Handle image upload
        $imagePath = $this->existingImagePath;
        if ($this->image) {
            if ($this->existingImagePath) {
                Storage::disk('public')->delete($this->existingImagePath);
            }
            $imagePath = $this->image->store('promotions', 'public');
        }

        $data = [
            'channel' => $this->campaignChannel,
            'subject' => $this->campaignChannel === 'email'
                ? $this->subject
                : ('Campaña WhatsApp ' . now()->format('d/m/Y H:i')),
            'message' => $this->campaignChannel === 'email'
                ? $this->message
                : 'Plantilla WhatsApp: mikpos',
            'image_path' => $this->campaignChannel === 'email' ? $imagePath : null,
            'button_text' => $this->campaignChannel === 'email' ? ($this->button_text ?: null) : null,
            'button_url' => $this->campaignChannel === 'email' ? ($this->button_url ?: null) : null,
        ];

        if (!$isNew) {
            $promo = Promotion::findOrFail($this->itemId);
            $old = $promo->toArray();
            $promo->update($data);
            ActivityLogService::logUpdate('promotions', $promo, $old, "Campaña '{$promo->subject}' actualizada");
            $this->dispatch('notify', message: 'Campaña actualizada correctamente', type: 'success');
        } else {
            $branchId = auth()->user()->isSuperAdmin()
                ? $this->formBranchId
                : auth()->user()->branch_id;
            $promo = Promotion::create(array_merge($data, [
                'branch_id' => $branchId,
                'user_id'   => auth()->id(),
                'status'    => 'draft',
            ]));
            ActivityLogService::logCreate('promotions', $promo, "Campaña '{$promo->subject}' creada");
            $this->dispatch('notify', message: 'Campaña creada correctamente', type: 'success');
        }

        $this->isModalOpen = false;
        $this->resetForm();
    }

    public function removeExistingImage(): void
    {
        if ($this->itemId && $this->existingImagePath) {
            Storage::disk('public')->delete($this->existingImagePath);
            Promotion::findOrFail($this->itemId)->update(['image_path' => null]);
            $this->existingImagePath = null;
        }
        $this->image = null;
    }

    public function closeModal(): void
    {
        $this->isModalOpen = false;
        $this->resetForm();
    }

    // ─── Delete ──────────────────────────────────────────────────────────────

    public function confirmDelete(int $id): void
    {
        if (!auth()->user()->hasPermission('promotions.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso para eliminar campañas', type: 'error');
            return;
        }
        $this->deleteId = $id;
        $this->isDeleteModalOpen = true;
    }

    public function delete(): void
    {
        if (!auth()->user()->hasPermission('promotions.delete')) {
            $this->dispatch('notify', message: 'Sin permiso', type: 'error');
            return;
        }
        $promo = Promotion::findOrFail($this->deleteId);
        if ($promo->image_path) {
            Storage::disk('public')->delete($promo->image_path);
        }
        ActivityLogService::logDelete('promotions', $promo, "Campaña '{$promo->subject}' eliminada");
        $promo->delete();
        $this->isDeleteModalOpen = false;
        $this->deleteId = null;
        $this->dispatch('notify', message: 'Campaña eliminada', type: 'success');
    }

    // ─── Send ─────────────────────────────────────────────────────────────────

    public function openSendModal(int $id): void
    {
        if (!auth()->user()->hasPermission('promotions.send')) {
            $this->dispatch('notify', message: 'No tienes permiso para enviar campañas', type: 'error');
            return;
        }
        $this->sendingPromoId = $id;
        $promo = Promotion::find($id);
        $this->sendChannel = ($promo?->channel === 'whatsapp') ? 'whatsapp' : 'email';
        $this->sendToAll = true;
        $this->customerSearch = '';
        $this->selectedCustomerIds = [];
        $this->isSendModalOpen = true;

        // #region debug-point openSendModal
        $this->dbg('openSendModal', 'H3', [
            'sendingPromoId' => $this->sendingPromoId,
            'sendChannel' => $this->sendChannel,
            'sendToAll' => $this->sendToAll,
            'promo_exists' => (bool) $promo,
            'promo_branch_id' => $promo?->branch_id,
            'promo_channel' => $promo?->channel,
            'user_id' => auth()->id(),
            'user_is_super_admin' => auth()->user()?->isSuperAdmin(),
            'user_branch_id' => auth()->user()?->branch_id,
        ]);
        // #endregion debug-point openSendModal
    }

    public function updatedSendChannel(): void
    {
        $this->customerSearch = '';
        $this->selectedCustomerIds = [];

        // #region debug-point updatedSendChannel
        $this->dbg('updatedSendChannel', 'H1', [
            'sendChannel' => $this->sendChannel,
            'sendingPromoId' => $this->sendingPromoId,
            'sendToAll' => $this->sendToAll,
        ]);
        // #endregion debug-point updatedSendChannel
    }

    public function closeSendModal(): void
    {
        $this->isSendModalOpen = false;
        $this->sendingPromoId = null;
        $this->sendChannel = 'email';
        $this->selectedCustomerIds = [];
        $this->customerSearch = '';
    }

    public function sendPromotion(): void
    {
        if (!auth()->user()->hasPermission('promotions.send')) {
            $this->dispatch('notify', message: 'Sin permiso', type: 'error');
            return;
        }

        $promo = Promotion::with('branch.municipality')->findOrFail($this->sendingPromoId);
        $channelLabel = $this->sendChannel === 'whatsapp' ? 'WhatsApp' : 'correo';

        if (!$this->sendToAll && empty($this->selectedCustomerIds)) {
            $this->dispatch('notify', message: 'Selecciona al menos un cliente', type: 'error');
            return;
        }

        $customersQuery = Customer::where('is_active', true)
            ->where('branch_id', $promo->branch_id);

        if ($this->sendChannel === 'whatsapp') {
            $customersQuery->whereNotNull('phone')
                ->where('phone', '!=', '');
        } else {
            $customersQuery->whereNotNull('email')
                ->where('email', '!=', '');
        }

        if (!$this->sendToAll) {
            $customersQuery->whereIn('id', $this->selectedCustomerIds);
        }

        $customers = $customersQuery->get();

        if ($customers->isEmpty()) {
            $emptyMessage = $this->sendChannel === 'whatsapp'
                ? 'No hay clientes activos en la sucursal de la campaña para este envío'
                : 'No hay clientes con correo electrónico registrado';
            $this->dispatch('notify', message: $emptyMessage, type: 'error');
            return;
        }

        try {
            ['count' => $count, 'lastError' => $lastError] = $this->sendChannel === 'whatsapp'
                ? $this->sendWhatsappPromotion($promo, $customers)
                : $this->sendEmailPromotion($promo, $customers);
        } catch (\Throwable $e) {
            $this->dispatch('notify', message: $e->getMessage(), type: 'error');
            return;
        }

        $sentChannel = $this->sendChannel;

        if ($count === 0) {
            $defaultError = $sentChannel === 'whatsapp'
                ? 'Meta no acepto la solicitud.'
                : 'No se pudo completar el envio.';
            $this->dispatch('notify', message: 'No se pudo completar el envio: ' . ($lastError ?: $defaultError), type: 'error');
            return;
        }

        $old = $promo->toArray();
        $promo->update([
            'status'           => 'sent',
            'sent_at'          => now(),
            'recipients_count' => $promo->recipients_count + $count,
        ]);

        ActivityLogService::logUpdate(
            'promotions',
            $promo,
            $old,
            "Campaña '{$promo->subject}' enviada por {$channelLabel} a {$count} cliente(s)"
        );

        $this->isSendModalOpen = false;
        $this->sendingPromoId = null;
        $this->sendChannel = 'email';
        $this->selectedCustomerIds = [];

        if ($sentChannel === 'whatsapp') {
            $message = "Solicitud aceptada por Meta para {$count} cliente(s). La entrega final depende del estado de WhatsApp.";
            $type = $lastError ? 'warning' : 'success';

            if ($lastError) {
                $message .= " Ultimo detalle: {$lastError}";
            }

            $this->dispatch('notify', message: $message, type: $type);
            return;
        }

        if ($lastError) {
            $this->dispatch('notify', message: "Campaña enviada por {$channelLabel} a {$count} cliente(s). Ultimo detalle: {$lastError}", type: 'warning');
            return;
        }

        $this->dispatch('notify', message: "Campaña enviada por {$channelLabel} a {$count} cliente(s) correctamente", type: 'success');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function resetForm(): void
    {
        $this->itemId = null;
        $this->formBranchId = null;
        $this->campaignChannel = 'email';
        $this->subject = '';
        $this->message = '';
        $this->button_text = '';
        $this->button_url = '';
        $this->image = null;
        $this->existingImagePath = null;
        $this->resetValidation();
    }

    protected function buildSendCustomersQuery(Promotion $promo)
    {
        $query = Customer::where('is_active', true);

        $query->where('branch_id', $promo->branch_id);

        if ($this->sendChannel === 'whatsapp') {
        } else {
            $query->whereNotNull('email')
                ->where('email', '!=', '');
        }

        if ($this->customerSearch) {
            $term = $this->customerSearch;
            $query->where(function ($q) use ($term) {
                $q->where('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%")
                    ->orWhere('business_name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%")
                    ->orWhere('document_number', 'like', "%{$term}%");
            });
        }

        // #region debug-point buildSendCustomersQuery
        $this->dbg('buildSendCustomersQuery', 'H2', [
            'sendChannel' => $this->sendChannel,
            'sendToAll' => $this->sendToAll,
            'customerSearch' => $this->customerSearch,
            'promo_id' => $promo->id ?? null,
            'promo_branch_id' => $promo->branch_id ?? null,
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings(),
        ]);
        // #endregion debug-point buildSendCustomersQuery

        return $query;
    }

    protected function sendEmailPromotion(Promotion $promo, $customers): array
    {
        $count = 0;
        $lastError = null;

        foreach ($customers as $customer) {
            try {
                Mail::to($customer->email)->send(new PromotionMail($promo, $customer));
                $count++;
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
                Log::error('PromotionMail error', [
                    'customer_id' => $customer->id,
                    'email' => $customer->email,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        return compact('count', 'lastError');
    }

    protected function sendWhatsappPromotion(Promotion $promo, $customers): array
    {
        $config = WhatsappConfig::where('branch_id', $promo->branch_id)
            ->where('is_active', true)
            ->first();

        if (!$config) {
            throw new \RuntimeException('La sucursal de esta campaña no tiene una configuración de WhatsApp activa.');
        }

        $count = 0;
        $lastError = null;

        $templateName = trim((string) ($config->template_name ?: $this->whatsappTemplateName));
        $templateLanguage = trim((string) ($config->template_language ?: $this->whatsappTemplateLanguage));

        foreach ($customers as $customer) {
            try {
                $to = $this->sanitizePhoneNumber($customer->phone);
                if ($to === '') {
                    $lastError = 'Hay clientes sin teléfono registrado.';
                    continue;
                }

                $response = Http::withToken(trim($config->token_permanente))
                    ->acceptJson()
                    ->post(sprintf(
                        'https://graph.facebook.com/%s/%s/messages',
                        trim($config->api_version),
                        trim($config->phone_number_id)
                    ), [
                        'messaging_product' => 'whatsapp',
                        'to' => $to,
                        'type' => 'template',
                        'template' => [
                            'name' => $templateName,
                            'language' => [
                                'code' => $templateLanguage,
                            ],
                        ],
                    ]);

                if (!$response->successful()) {
                    $responseData = $response->json();
                    $lastError = data_get($responseData, 'error.message')
                        ?? data_get($responseData, 'message')
                        ?? 'Meta no aceptó el envío por WhatsApp.';

                    Log::error('PromotionWhatsapp error', [
                        'promotion_id' => $promo->id,
                        'customer_id' => $customer->id,
                        'phone' => $customer->phone,
                        'response' => $responseData,
                    ]);
                    continue;
                }

                Log::info('PromotionWhatsapp accepted', [
                    'promotion_id' => $promo->id,
                    'customer_id' => $customer->id,
                    'phone' => $customer->phone,
                    'to' => $to,
                    'response' => $response->json(),
                ]);

                $count++;
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
                Log::error('PromotionWhatsapp exception', [
                    'promotion_id' => $promo->id,
                    'customer_id' => $customer->id,
                    'phone' => $customer->phone,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        return compact('count', 'lastError');
    }

    protected function sanitizePhoneNumber(?string $phone): string
    {
        return preg_replace('/\D+/', '', (string) $phone) ?? '';
    }

    // #region debug-point dbg-helper
    protected function dbg(string $pointId, string $hypothesisId, array $data): void
    {
        try {
            $envPath = base_path('.dbg/promotion-customer-search.env');
            $debugUrl = null;
            if (is_file($envPath)) {
                $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
                foreach ($lines as $line) {
                    if (str_starts_with($line, 'DEBUG_SERVER_URL=')) {
                        $debugUrl = trim(substr($line, strlen('DEBUG_SERVER_URL=')));
                        break;
                    }
                }
            }
            if (!$debugUrl) {
                return;
            }

            Http::timeout(1)->asJson()->post($debugUrl, [
                'sessionId' => 'promotion-customer-search',
                'hypothesisId' => $hypothesisId,
                'runId' => 'pre',
                'pointId' => $pointId,
                'ts' => now()->toISOString(),
                'data' => $data,
            ]);
        } catch (\Throwable $e) {
        }
    }
    // #endregion debug-point dbg-helper

    // ─── Render ───────────────────────────────────────────────────────────────

    public function render()
    {
        $query = Promotion::with('user');

        if (!auth()->user()->isSuperAdmin()) {
            $query->where('branch_id', auth()->user()->branch_id);
        }

        if ($this->search) {
            $query->where('subject', 'like', '%' . $this->search . '%');
        }

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        $promotions = $query->latest()->paginate(10);

        $sendingPromotion = $this->sendingPromoId ? Promotion::find($this->sendingPromoId) : null;
        $activeWhatsappConfig = null;
        if ($sendingPromotion) {
            $activeWhatsappConfig = WhatsappConfig::where('branch_id', $sendingPromotion->branch_id)
                ->where('is_active', true)
                ->first();
        }

        // Customers for send modal (only loaded when modal is open)
        $sendCustomers = collect();
        if ($this->isSendModalOpen && !$this->sendToAll) {
            $sendingPromotion = Promotion::find($this->sendingPromoId);
            if ($sendingPromotion) {
                $queryForDebug = $this->buildSendCustomersQuery($sendingPromotion)
                    ->orderBy('first_name')
                    ->limit(100);

                $sendCustomers = $queryForDebug->get();

                // #region debug-point render-sendCustomers
                $this->dbg('render-sendCustomers', 'H5', [
                    'isSendModalOpen' => $this->isSendModalOpen,
                    'sendToAll' => $this->sendToAll,
                    'sendChannel' => $this->sendChannel,
                    'customerSearch' => $this->customerSearch,
                    'sendingPromoId' => $this->sendingPromoId,
                    'promo_branch_id' => $sendingPromotion->branch_id,
                    'result_count' => $sendCustomers->count(),
                    'result_ids' => $sendCustomers->pluck('id')->take(10)->values()->all(),
                ]);
                // #endregion debug-point render-sendCustomers
            }
        }

        // Stats
        $baseQuery = Promotion::query();
        if (!auth()->user()->isSuperAdmin()) {
            $baseQuery->where('branch_id', auth()->user()->branch_id);
        }
        $totalCount = (clone $baseQuery)->count();
        $sentCount  = (clone $baseQuery)->where('status', 'sent')->count();
        $draftCount = (clone $baseQuery)->where('status', 'draft')->count();

        $branches = auth()->user()->isSuperAdmin()
            ? Branch::where('is_active', true)->orderBy('name')->get()
            : collect();

        return view('livewire.promotions', [
            'promotions'    => $promotions,
            'sendCustomers' => $sendCustomers,
            'totalCount'    => $totalCount,
            'sentCount'     => $sentCount,
            'draftCount'    => $draftCount,
            'branches'      => $branches,
            'sendingPromotion' => $sendingPromotion,
            'activeWhatsappConfig' => $activeWhatsappConfig,
        ]);
    }
}

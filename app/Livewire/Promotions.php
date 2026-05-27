<?php

namespace App\Livewire;

use App\Mail\PromotionMail;
use App\Models\Customer;
use App\Models\Promotion;
use App\Services\ActivityLogService;
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
        $this->subject = $promo->subject;
        $this->message = $promo->message;
        $this->button_text = $promo->button_text ?? '';
        $this->button_url = $promo->button_url ?? '';
        $this->existingImagePath = $promo->image_path;
        $this->image = null;
        $this->isModalOpen = true;
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

        $this->validate([
            'subject'     => 'required|min:3|max:255',
            'message'     => 'required|min:5',
            'image'       => 'nullable|image|max:3072',
            'button_text' => 'nullable|max:100',
            'button_url'  => 'nullable|url|max:500',
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
            'subject'     => $this->subject,
            'message'     => $this->message,
            'image_path'  => $imagePath,
            'button_text' => $this->button_text ?: null,
            'button_url'  => $this->button_url ?: null,
        ];

        if (!$isNew) {
            $promo = Promotion::findOrFail($this->itemId);
            $old = $promo->toArray();
            $promo->update($data);
            ActivityLogService::logUpdate('promotions', $promo, $old, "Campaña '{$promo->subject}' actualizada");
            $this->dispatch('notify', message: 'Campaña actualizada correctamente', type: 'success');
        } else {
            $promo = Promotion::create(array_merge($data, [
                'branch_id' => auth()->user()->branch_id,
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
        $this->sendToAll = true;
        $this->customerSearch = '';
        $this->selectedCustomerIds = [];
        $this->isSendModalOpen = true;
    }

    public function closeSendModal(): void
    {
        $this->isSendModalOpen = false;
        $this->sendingPromoId = null;
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

        $query = Customer::whereNotNull('email')
            ->where('email', '!=', '')
            ->where('is_active', true);

        if (!auth()->user()->isSuperAdmin()) {
            $query->where('branch_id', auth()->user()->branch_id);
        }

        if (!$this->sendToAll) {
            if (empty($this->selectedCustomerIds)) {
                $this->dispatch('notify', message: 'Selecciona al menos un cliente', type: 'error');
                return;
            }
            $query->whereIn('id', $this->selectedCustomerIds);
        }

        $customers = $query->get();

        if ($customers->isEmpty()) {
            $this->dispatch('notify', message: 'No hay clientes con correo electrónico registrado', type: 'error');
            return;
        }

        $count = 0;
        foreach ($customers as $customer) {
            try {
                Mail::to($customer->email)->queue(new PromotionMail($promo, $customer));
                $count++;
            } catch (\Exception $e) {
                // continue with remaining customers
            }
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
            "Campaña '{$promo->subject}' enviada a {$count} cliente(s)"
        );

        $this->isSendModalOpen = false;
        $this->sendingPromoId = null;
        $this->selectedCustomerIds = [];

        $this->dispatch('notify', message: "Campaña enviada a {$count} cliente(s) correctamente", type: 'success');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function resetForm(): void
    {
        $this->itemId = null;
        $this->subject = '';
        $this->message = '';
        $this->button_text = '';
        $this->button_url = '';
        $this->image = null;
        $this->existingImagePath = null;
        $this->resetValidation();
    }

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

        // Customers for send modal (only loaded when modal is open)
        $sendCustomers = collect();
        if ($this->isSendModalOpen && !$this->sendToAll) {
            $cQuery = Customer::whereNotNull('email')
                ->where('email', '!=', '')
                ->where('is_active', true);

            if (!auth()->user()->isSuperAdmin()) {
                $cQuery->where('branch_id', auth()->user()->branch_id);
            }

            if ($this->customerSearch) {
                $term = $this->customerSearch;
                $cQuery->where(function ($q) use ($term) {
                    $q->where('first_name', 'like', "%{$term}%")
                      ->orWhere('last_name', 'like', "%{$term}%")
                      ->orWhere('business_name', 'like', "%{$term}%")
                      ->orWhere('email', 'like', "%{$term}%")
                      ->orWhere('document_number', 'like', "%{$term}%");
                });
            }

            $sendCustomers = $cQuery->orderBy('first_name')->limit(100)->get();
        }

        // Stats
        $baseQuery = Promotion::query();
        if (!auth()->user()->isSuperAdmin()) {
            $baseQuery->where('branch_id', auth()->user()->branch_id);
        }
        $totalCount = (clone $baseQuery)->count();
        $sentCount  = (clone $baseQuery)->where('status', 'sent')->count();
        $draftCount = (clone $baseQuery)->where('status', 'draft')->count();

        return view('livewire.promotions', [
            'promotions'    => $promotions,
            'sendCustomers' => $sendCustomers,
            'totalCount'    => $totalCount,
            'sentCount'     => $sentCount,
            'draftCount'    => $draftCount,
        ]);
    }
}

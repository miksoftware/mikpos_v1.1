<?php

namespace App\Livewire;

use App\Models\Branch;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\ActivityLogService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Purchases extends Component
{
    use WithPagination;

    public string $search = '';
    public ?string $filterStatus = null;
    public ?string $filterSupplier = null;
    public ?string $filterBranch = null;
    public ?string $dateFrom = null;
    public ?string $dateTo = null;

    public bool $isDeleteModalOpen = false;
    public bool $isViewModalOpen = false;
    public bool $isEditConfirmModalOpen = false;
    public ?int $itemIdToDelete = null;
    public ?int $itemIdToEdit = null;
    public ?Purchase $viewingPurchase = null;

    public $suppliers = [];
    
    // Branch control
    public bool $needsBranchSelection = false;
    public $branches = [];

    public function mount()
    {
        $this->suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        
        $user = auth()->user();
        $this->needsBranchSelection = $user->isSuperAdmin() || !$user->branch_id;
        
        if ($this->needsBranchSelection) {
            $this->branches = Branch::where('is_active', true)->orderBy('name')->get();
        }
    }

    public function render()
    {
        $user = auth()->user();
        
        $query = Purchase::query()
            ->with(['supplier', 'user', 'branch', 'paymentMethod'])
            ->withCount('items');

        // Apply branch filter
        if ($this->needsBranchSelection) {
            if ($this->filterBranch) {
                $query->where('branch_id', $this->filterBranch);
            }
        } else {
            $query->where('branch_id', $user->branch_id);
        }

        $items = $query
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('purchase_number', 'like', "%{$this->search}%")
                        ->orWhere('supplier_invoice', 'like', "%{$this->search}%")
                        ->orWhereHas('supplier', fn($sq) => $sq->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterSupplier, fn($q) => $q->where('supplier_id', $this->filterSupplier))
            ->when($this->dateFrom, fn($q) => $q->whereDate('purchase_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('purchase_date', '<=', $this->dateTo))
            ->latest()
            ->paginate(10);

        return view('livewire.purchases', [
            'items' => $items,
        ]);
    }

    public function viewPurchase(int $id)
    {
        $this->viewingPurchase = Purchase::with(['supplier', 'user', 'branch', 'items.product.unit', 'paymentMethod', 'partialPaymentMethod'])->find($id);
        $this->isViewModalOpen = true;
    }

    public function continuePurchase(int $id)
    {
        if (!auth()->user()->hasPermission('purchases.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        return $this->redirect(route('purchases.edit', $id), navigate: true);
    }

    public function confirmEditCompleted(int $id)
    {
        if (!auth()->user()->hasPermission('purchases.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $this->itemIdToEdit = $id;
        $this->isEditConfirmModalOpen = true;
    }

    public function editCompleted()
    {
        return $this->redirect(route('purchases.edit', $this->itemIdToEdit), navigate: true);
    }

    public function confirmDelete(int $id)
    {
        if (!auth()->user()->hasPermission('purchases.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $purchase = Purchase::find($id);
        if ($purchase && $purchase->status === 'completed') {
            $this->dispatch('notify', message: 'No se puede eliminar una compra completada', type: 'error');
            return;
        }

        $this->itemIdToDelete = $id;
        $this->isDeleteModalOpen = true;
    }

    public function delete()
    {
        if (!auth()->user()->hasPermission('purchases.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $purchase = Purchase::find($this->itemIdToDelete);
        if (!$purchase) {
            $this->dispatch('notify', message: 'Compra no encontrada', type: 'error');
            $this->isDeleteModalOpen = false;
            return;
        }

        if ($purchase->status === 'completed') {
            $this->dispatch('notify', message: 'No se puede eliminar una compra completada', type: 'error');
            $this->isDeleteModalOpen = false;
            return;
        }

        ActivityLogService::logDelete('purchases', $purchase, "Compra '{$purchase->purchase_number}' eliminada");
        $purchase->delete();

        $this->isDeleteModalOpen = false;
        $this->dispatch('notify', message: 'Compra eliminada');
    }

    public function completePurchase(int $id)
    {
        if (!auth()->user()->hasPermission('purchases.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $purchase = Purchase::with('items.product')->find($id);
        if (!$purchase) {
            return;
        }

        if ($purchase->complete()) {
            ActivityLogService::logUpdate('purchases', $purchase, [], "Compra '{$purchase->purchase_number}' completada");
            $this->dispatch('notify', message: 'Compra completada y stock actualizado');
        } else {
            $this->dispatch('notify', message: 'No se pudo completar la compra', type: 'error');
        }
    }

    public function cancelPurchase(int $id)
    {
        if (!auth()->user()->hasPermission('purchases.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $purchase = Purchase::with('items.product')->find($id);
        if (!$purchase) {
            return;
        }

        $oldStatus = $purchase->status;
        if ($purchase->cancel()) {
            ActivityLogService::logUpdate('purchases', $purchase, ['status' => $oldStatus], "Compra '{$purchase->purchase_number}' cancelada");
            $this->dispatch('notify', message: 'Compra cancelada');
        } else {
            $this->dispatch('notify', message: 'No se pudo cancelar la compra', type: 'error');
        }
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->filterStatus = null;
        $this->filterSupplier = null;
        $this->filterBranch = null;
        $this->dateFrom = null;
        $this->dateTo = null;
        $this->resetPage();
    }
}

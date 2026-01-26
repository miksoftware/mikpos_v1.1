<?php

namespace App\Livewire;

use App\Models\Branch;
use App\Models\PaymentMethod;
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
    public ?string $filterPaymentType = null;
    public ?string $filterPaymentStatus = null;
    public ?string $dateFrom = null;
    public ?string $dateTo = null;

    public bool $isDeleteModalOpen = false;
    public bool $isViewModalOpen = false;
    public bool $isEditConfirmModalOpen = false;
    public bool $isPaymentModalOpen = false;
    public ?int $itemIdToDelete = null;
    public ?int $itemIdToEdit = null;
    public ?Purchase $viewingPurchase = null;
    public ?Purchase $payingPurchase = null;

    // Payment form
    public float $paymentAmount = 0;
    public ?int $paymentMethodId = null;
    public string $paymentNotes = '';

    public $suppliers = [];
    public $paymentMethods = [];
    
    // Branch control
    public bool $needsBranchSelection = false;
    public $branches = [];

    public function mount()
    {
        $this->suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        $this->paymentMethods = PaymentMethod::where('is_active', true)->orderBy('name')->get();
        
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
            ->when($this->filterPaymentType, fn($q) => $q->where('payment_type', $this->filterPaymentType))
            ->when($this->filterPaymentStatus, fn($q) => $q->where('payment_status', $this->filterPaymentStatus))
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
        $this->filterPaymentType = null;
        $this->filterPaymentStatus = null;
        $this->dateFrom = null;
        $this->dateTo = null;
        $this->resetPage();
    }

    // Payment methods
    public function openPaymentModal(int $id)
    {
        if (!auth()->user()->hasPermission('purchases.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $this->payingPurchase = Purchase::with(['supplier', 'partialPaymentMethod'])->find($id);
        
        if (!$this->payingPurchase) {
            $this->dispatch('notify', message: 'Compra no encontrada', type: 'error');
            return;
        }

        if ($this->payingPurchase->payment_type !== 'credit') {
            $this->dispatch('notify', message: 'Esta compra no es a crédito', type: 'error');
            return;
        }

        if ($this->payingPurchase->payment_status === 'paid') {
            $this->dispatch('notify', message: 'Esta compra ya está pagada', type: 'error');
            return;
        }

        $this->resetPaymentForm();
        $this->isPaymentModalOpen = true;
    }

    public function registerPayment()
    {
        if (!auth()->user()->hasPermission('purchases.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $this->validate([
            'paymentAmount' => 'required|numeric|min:0.01',
            'paymentMethodId' => 'required|exists:payment_methods,id',
        ], [
            'paymentAmount.required' => 'El monto es obligatorio',
            'paymentAmount.min' => 'El monto debe ser mayor a 0',
            'paymentMethodId.required' => 'Selecciona un método de pago',
        ]);

        $purchase = Purchase::find($this->payingPurchase->id);
        if (!$purchase) {
            $this->dispatch('notify', message: 'Compra no encontrada', type: 'error');
            $this->isPaymentModalOpen = false;
            return;
        }

        $pendingAmount = $purchase->credit_amount - $purchase->paid_amount;
        
        if ($this->paymentAmount > $pendingAmount) {
            $this->dispatch('notify', message: 'El monto excede el saldo pendiente', type: 'error');
            return;
        }

        $oldData = $purchase->toArray();
        $newPaidAmount = $purchase->paid_amount + $this->paymentAmount;

        // Determine new payment status
        $newPaymentStatus = 'partial';
        if ($newPaidAmount >= $purchase->credit_amount) {
            $newPaymentStatus = 'paid';
        }

        $purchase->update([
            'paid_amount' => $newPaidAmount,
            'partial_payment_method_id' => $this->paymentMethodId,
            'payment_status' => $newPaymentStatus,
        ]);

        $paymentMethodName = PaymentMethod::find($this->paymentMethodId)?->name ?? 'N/A';
        ActivityLogService::logUpdate(
            'purchases',
            $purchase,
            $oldData,
            "Pago registrado en compra '{$purchase->purchase_number}': $" . number_format($this->paymentAmount, 2) . " via {$paymentMethodName}"
        );

        $this->isPaymentModalOpen = false;
        $this->dispatch('notify', message: 'Pago registrado correctamente');
    }

    public function markAsPaid(int $id)
    {
        if (!auth()->user()->hasPermission('purchases.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $purchase = Purchase::find($id);
        if (!$purchase || $purchase->payment_type !== 'credit') {
            $this->dispatch('notify', message: 'Compra no válida', type: 'error');
            return;
        }

        $oldData = $purchase->toArray();
        $purchase->update([
            'paid_amount' => $purchase->credit_amount,
            'payment_status' => 'paid',
        ]);

        ActivityLogService::logUpdate(
            'purchases',
            $purchase,
            $oldData,
            "Compra '{$purchase->purchase_number}' marcada como pagada"
        );

        $this->dispatch('notify', message: 'Compra marcada como pagada');
    }

    private function resetPaymentForm()
    {
        $this->paymentAmount = 0;
        $this->paymentMethodId = null;
        $this->paymentNotes = '';
    }
}

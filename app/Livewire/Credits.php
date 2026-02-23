<?php

namespace App\Livewire;

use App\Models\Branch;
use App\Models\CashMovement;
use App\Models\CashReconciliation;
use App\Models\CreditPayment;
use App\Models\PaymentMethod;
use App\Models\Purchase;
use App\Models\Sale;
use App\Services\ActivityLogService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Credits extends Component
{
    use WithPagination;

    // Filters
    public string $search = '';
    public string $filterType = ''; // receivable, payable
    public string $filterStatus = ''; // pending, partial, paid
    public ?int $filterBranch = null;

    // Payment modal
    public bool $isPaymentModalOpen = false;
    public ?int $paymentReferenceId = null;
    public ?string $paymentReferenceType = null; // 'purchase' or 'sale'
    public ?string $paymentEntityName = null;
    public ?string $paymentCreditType = null;
    public float $paymentTotal = 0;
    public float $paymentPaid = 0;
    public float $paymentRemaining = 0;
    public float $paymentAmount = 0;
    public ?int $paymentMethodId = null;
    public bool $paymentAffectsCash = false;
    public string $paymentNotes = '';
    public bool $paymentMarkComplete = false;

    // History modal
    public bool $isHistoryModalOpen = false;
    public ?int $historyReferenceId = null;
    public ?string $historyReferenceType = null;
    public $historyPayments = [];
    public ?string $historyEntityName = null;

    // Branch control
    public bool $needsBranchSelection = false;
    public $branches = [];
    public $paymentMethods = [];

    public function mount()
    {
        $user = auth()->user();
        $this->needsBranchSelection = $user->isSuperAdmin() || !$user->branch_id;

        if ($this->needsBranchSelection) {
            $this->branches = Branch::where('is_active', true)->orderBy('name')->get();
        }

        $this->paymentMethods = PaymentMethod::where('is_active', true)->orderBy('name')->get();
    }

    public function render()
    {
        $user = auth()->user();
        $branchId = $this->needsBranchSelection ? $this->filterBranch : $user->branch_id;

        $purchaseItems = collect();
        $saleItems = collect();
        $purchaseTotals = ['total_debt' => 0, 'total_paid' => 0, 'total_remaining' => 0, 'count' => 0];
        $saleTotals = ['total_debt' => 0, 'total_paid' => 0, 'total_remaining' => 0, 'count' => 0];

        $showPurchases = $this->filterType !== 'receivable';
        $showSales = $this->filterType !== 'payable';

        // Credit Purchases (Cuentas por Pagar)
        if ($showPurchases) {
            $pQuery = Purchase::query()
                ->with(['supplier', 'branch'])
                ->where('purchases.payment_type', 'credit')
                ->where('purchases.status', 'completed');

            if ($branchId) {
                $pQuery->where('purchases.branch_id', $branchId);
            } elseif (!$user->isSuperAdmin()) {
                $pQuery->where('purchases.branch_id', $user->branch_id);
            }

            if ($this->filterStatus) {
                $pQuery->where('purchases.payment_status', $this->filterStatus);
            } else {
                $pQuery->whereIn('purchases.payment_status', ['pending', 'partial']);
            }

            if (trim($this->search)) {
                $search = trim($this->search);
                $pQuery->where(function ($q) use ($search) {
                    $q->where('purchases.purchase_number', 'like', "%{$search}%")
                        ->orWhereHas('supplier', function ($sq) use ($search) {
                            $sq->where('name', 'like', "%{$search}%");
                        });
                });
            }

            $purchaseItems = $pQuery->orderByDesc('purchases.created_at')->get();

            // Purchase totals
            $ptQuery = Purchase::query()
                ->where('purchases.payment_type', 'credit')
                ->where('purchases.status', 'completed')
                ->whereIn('purchases.payment_status', ['pending', 'partial']);
            if ($branchId) {
                $ptQuery->where('purchases.branch_id', $branchId);
            } elseif (!$user->isSuperAdmin()) {
                $ptQuery->where('purchases.branch_id', $user->branch_id);
            }
            $purchaseTotals = [
                'total_debt' => (float) $ptQuery->sum('credit_amount'),
                'total_paid' => (float) $ptQuery->sum('paid_amount'),
                'total_remaining' => (float) $ptQuery->sum('credit_amount') - (float) $ptQuery->sum('paid_amount'),
                'count' => $ptQuery->count(),
            ];
        }

        // Credit Sales (Cuentas por Cobrar)
        if ($showSales) {
            $sQuery = Sale::query()
                ->with(['customer', 'branch'])
                ->where('sales.payment_type', 'credit')
                ->where('sales.status', 'completed');

            if ($branchId) {
                $sQuery->where('sales.branch_id', $branchId);
            } elseif (!$user->isSuperAdmin()) {
                $sQuery->where('sales.branch_id', $user->branch_id);
            }

            if ($this->filterStatus) {
                $sQuery->where('sales.payment_status', $this->filterStatus);
            } else {
                $sQuery->whereIn('sales.payment_status', ['pending', 'partial']);
            }

            if (trim($this->search)) {
                $search = trim($this->search);
                $sQuery->where(function ($q) use ($search) {
                    $q->where('sales.invoice_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($cq) use ($search) {
                            $cq->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('business_name', 'like', "%{$search}%")
                                ->orWhere('document_number', 'like', "%{$search}%")
                                ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
                        });
                });
            }

            $saleItems = $sQuery->orderByDesc('sales.created_at')->get();

            // Sale totals
            $stQuery = Sale::query()
                ->where('sales.payment_type', 'credit')
                ->where('sales.status', 'completed')
                ->whereIn('sales.payment_status', ['pending', 'partial']);
            if ($branchId) {
                $stQuery->where('sales.branch_id', $branchId);
            } elseif (!$user->isSuperAdmin()) {
                $stQuery->where('sales.branch_id', $user->branch_id);
            }
            $saleTotals = [
                'total_debt' => (float) $stQuery->sum('credit_amount'),
                'total_paid' => (float) $stQuery->sum('paid_amount'),
                'total_remaining' => (float) $stQuery->sum('credit_amount') - (float) $stQuery->sum('paid_amount'),
                'count' => $stQuery->count(),
            ];
        }

        // Merge into unified collection with type indicator
        $items = collect();
        foreach ($purchaseItems as $p) {
            $items->push((object) [
                'record_type' => 'purchase',
                'id' => $p->id,
                'document_number' => $p->purchase_number,
                'extra_doc' => $p->supplier_invoice,
                'entity_name' => $p->supplier->name ?? '-',
                'branch_name' => $p->branch->name ?? '',
                'date' => $p->purchase_date,
                'due_date' => $p->payment_due_date,
                'credit_amount' => (float) $p->credit_amount,
                'paid_amount' => (float) $p->paid_amount,
                'payment_status' => $p->payment_status,
            ]);
        }
        foreach ($saleItems as $s) {
            $items->push((object) [
                'record_type' => 'sale',
                'id' => $s->id,
                'document_number' => $s->invoice_number,
                'extra_doc' => null,
                'entity_name' => $s->customer ? $s->customer->full_name : 'Cliente',
                'branch_name' => $s->branch->name ?? '',
                'date' => $s->created_at,
                'due_date' => $s->payment_due_date,
                'credit_amount' => (float) $s->credit_amount,
                'paid_amount' => (float) $s->paid_amount,
                'payment_status' => $s->payment_status,
            ]);
        }

        // Sort by date descending
        $items = $items->sortByDesc('date')->values();

        // Combined totals
        $totals = [
            'payable_remaining' => $purchaseTotals['total_remaining'],
            'receivable_remaining' => $saleTotals['total_remaining'],
            'payable_count' => $purchaseTotals['count'],
            'receivable_count' => $saleTotals['count'],
        ];

        return view('livewire.credits', [
            'items' => $items,
            'totals' => $totals,
        ]);
    }

    public function openPaymentModal(int $id, string $type)
    {
        if (!auth()->user()->hasPermission('credits.pay')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $this->paymentReferenceId = $id;
        $this->paymentReferenceType = $type;

        if ($type === 'purchase') {
            $purchase = Purchase::with('supplier')->find($id);
            if (!$purchase) {
                $this->dispatch('notify', message: 'Compra no encontrada', type: 'error');
                return;
            }
            $this->paymentEntityName = ($purchase->supplier->name ?? 'Proveedor') . ' — Compra: ' . $purchase->purchase_number;
            $this->paymentCreditType = 'payable';
            $this->paymentTotal = (float) $purchase->credit_amount;
            $this->paymentPaid = (float) $purchase->paid_amount;
        } else {
            $sale = Sale::with('customer')->find($id);
            if (!$sale) {
                $this->dispatch('notify', message: 'Venta no encontrada', type: 'error');
                return;
            }
            $customerName = $sale->customer ? $sale->customer->full_name : 'Cliente';
            $this->paymentEntityName = $customerName . ' — Factura: ' . $sale->invoice_number;
            $this->paymentCreditType = 'receivable';
            $this->paymentTotal = (float) $sale->credit_amount;
            $this->paymentPaid = (float) $sale->paid_amount;
        }

        $this->paymentRemaining = $this->paymentTotal - $this->paymentPaid;
        $this->paymentAmount = 0;
        $this->paymentMethodId = null;
        $this->paymentAffectsCash = false;
        $this->paymentNotes = '';
        $this->paymentMarkComplete = false;
        $this->isPaymentModalOpen = true;
    }

    public function storePayment()
    {
        if (!auth()->user()->hasPermission('credits.pay')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $rules = ['paymentMethodId' => 'required|exists:payment_methods,id'];
        $messages = [
            'paymentMethodId.required' => 'Selecciona un método de pago',
            'paymentAmount.required' => 'El monto es obligatorio',
            'paymentAmount.min' => 'El monto debe ser mayor a 0',
        ];
        if (!$this->paymentMarkComplete) {
            $rules['paymentAmount'] = 'required|numeric|min:0.01';
        }
        $this->validate($rules, $messages);

        $user = auth()->user();

        if ($this->paymentReferenceType === 'purchase') {
            $record = Purchase::find($this->paymentReferenceId);
            if (!$record) {
                $this->dispatch('notify', message: 'Compra no encontrada', type: 'error');
                $this->isPaymentModalOpen = false;
                return;
            }
            $remaining = (float) $record->credit_amount - (float) $record->paid_amount;
            $entityName = $record->supplier->name ?? 'Proveedor';
            $docNumber = $record->purchase_number;
            $branchId = $this->needsBranchSelection ? ($this->filterBranch ?? $record->branch_id) : $user->branch_id;
        } else {
            $record = Sale::with('customer')->find($this->paymentReferenceId);
            if (!$record) {
                $this->dispatch('notify', message: 'Venta no encontrada', type: 'error');
                $this->isPaymentModalOpen = false;
                return;
            }
            $remaining = (float) $record->credit_amount - (float) $record->paid_amount;
            $entityName = $record->customer ? $record->customer->full_name : 'Cliente';
            $docNumber = $record->invoice_number;
            $branchId = $this->needsBranchSelection ? ($this->filterBranch ?? $record->branch_id) : $user->branch_id;
        }

        $amount = $this->paymentMarkComplete ? $remaining : (float) $this->paymentAmount;

        if ($amount > $remaining) {
            $this->dispatch('notify', message: 'El monto excede el saldo pendiente ($' . number_format($remaining, 2) . ')', type: 'error');
            return;
        }

        // Check for open cash reconciliation if affects cash
        $cashReconciliationId = null;
        if ($this->paymentAffectsCash) {
            $cashReconciliation = $this->findOpenReconciliation($user);
            if (!$cashReconciliation) {
                $this->dispatch('notify', message: 'No hay una caja abierta para registrar el movimiento', type: 'error');
                return;
            }
            $cashReconciliationId = $cashReconciliation->id;
        }

        $creditType = $this->paymentReferenceType === 'purchase' ? 'payable' : 'receivable';

        $creditPayment = CreditPayment::create([
            'payment_number' => CreditPayment::generatePaymentNumber(),
            'credit_type' => $creditType,
            'purchase_id' => $this->paymentReferenceType === 'purchase' ? $record->id : null,
            'sale_id' => $this->paymentReferenceType === 'sale' ? $record->id : null,
            'customer_id' => $this->paymentReferenceType === 'sale' ? $record->customer_id : null,
            'supplier_id' => $this->paymentReferenceType === 'purchase' ? $record->supplier_id : null,
            'branch_id' => $branchId,
            'user_id' => $user->id,
            'payment_method_id' => $this->paymentMethodId,
            'cash_reconciliation_id' => $cashReconciliationId,
            'amount' => $amount,
            'affects_cash' => $this->paymentAffectsCash,
            'notes' => $this->paymentNotes ?: null,
        ]);

        // Update record paid_amount and status
        $newPaidAmount = (float) $record->paid_amount + $amount;
        $creditAmountField = $this->paymentReferenceType === 'purchase' ? 'credit_amount' : 'credit_amount';
        $newStatus = $newPaidAmount >= (float) $record->$creditAmountField ? 'paid' : 'partial';

        $record->update([
            'paid_amount' => $newPaidAmount,
            'payment_status' => $newStatus,
        ]);

        // If affects cash, create cash movement
        if ($this->paymentAffectsCash && $cashReconciliationId) {
            $movementType = $creditType === 'payable' ? 'expense' : 'income';
            $conceptPrefix = $creditType === 'payable'
                ? "Pago crédito proveedor: {$entityName}"
                : "Cobro crédito cliente: {$entityName}";

            CashMovement::create([
                'cash_reconciliation_id' => $cashReconciliationId,
                'user_id' => $user->id,
                'type' => $movementType,
                'amount' => $amount,
                'concept' => "{$conceptPrefix} - {$docNumber}",
                'notes' => $this->paymentNotes ?: null,
            ]);
        }

        $typeLabel = $creditType === 'payable' ? 'Proveedor' : 'Cliente';
        ActivityLogService::logCreate(
            'credit_payments',
            $creditPayment,
            "Pago de crédito #{$creditPayment->payment_number} - {$typeLabel}: {$entityName} - $" . number_format($amount, 2)
        );

        $this->isPaymentModalOpen = false;
        $statusLabel = $newStatus === 'paid' ? 'Crédito pagado completamente' : 'Abono registrado correctamente';
        $this->dispatch('notify', message: $statusLabel, type: 'success');
    }

    public function viewHistory(int $id, string $type)
    {
        $this->historyReferenceId = $id;
        $this->historyReferenceType = $type;

        if ($type === 'purchase') {
            $record = Purchase::with('supplier')->find($id);
            $this->historyEntityName = ($record?->supplier->name ?? 'Proveedor') . ' — Compra: ' . ($record?->purchase_number ?? '');
            $this->historyPayments = CreditPayment::with(['user', 'paymentMethod'])
                ->where('purchase_id', $id)
                ->orderByDesc('created_at')
                ->get();
        } else {
            $record = Sale::with('customer')->find($id);
            $customerName = $record?->customer ? $record->customer->full_name : 'Cliente';
            $this->historyEntityName = $customerName . ' — Factura: ' . ($record?->invoice_number ?? '');
            $this->historyPayments = CreditPayment::with(['user', 'paymentMethod'])
                ->where('sale_id', $id)
                ->orderByDesc('created_at')
                ->get();
        }

        $this->isHistoryModalOpen = true;
    }

    private function findOpenReconciliation($user): ?CashReconciliation
    {
        $cashRegister = \App\Models\CashRegister::where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        if ($cashRegister) {
            return CashReconciliation::where('cash_register_id', $cashRegister->id)
                ->where('status', 'open')
                ->first();
        }

        $branchId = $user->branch_id;
        if ($branchId) {
            return CashReconciliation::where('branch_id', $branchId)
                ->where('status', 'open')
                ->first();
        }

        return null;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterType()
    {
        $this->resetPage();
    }

    public function updatingFilterStatus()
    {
        $this->resetPage();
    }
}

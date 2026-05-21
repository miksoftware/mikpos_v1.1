<?php

namespace App\Livewire;

use App\Models\Branch;
use App\Models\CashMovement;
use App\Models\CashReconciliation;
use App\Models\CreditPayment;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Supplier;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\DB;
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
    public array $paymentLines = [];
    public bool $paymentAffectsCash = false;
    public string $paymentNotes = '';
    public bool $paymentMarkComplete = false;

    // History modal
    public bool $isHistoryModalOpen = false;
    public ?int $historyReferenceId = null;
    public ?string $historyReferenceType = null;
    public $historyPayments = [];
    public ?string $historyEntityName = null;

    // Bulk payment modal (one payment, multiple invoices)
    public bool $isBulkModalOpen = false;
    public string $bulkType = 'receivable'; // 'receivable' (customer) or 'payable' (supplier)
    public ?int $bulkEntityId = null;       // customer_id or supplier_id
    public string $bulkEntitySearch = '';
    public array $bulkEntityResults = [];
    public ?array $bulkSelectedEntity = null; // ['id', 'name']
    public array $bulkInvoices = [];          // each: ['id','document_number','date','total','paid','remaining','allocated','lines'=>[...]]
    public bool $bulkAffectsCash = false;
    public string $bulkNotes = '';

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
        $this->paymentLines = [['payment_method_id' => '', 'amount' => 0]];
        $this->paymentAffectsCash = false;
        $this->paymentNotes = '';
        $this->paymentMarkComplete = false;
        $this->isPaymentModalOpen = true;
    }

    public function addPaymentLine()
    {
        $this->paymentLines[] = ['payment_method_id' => '', 'amount' => 0];
    }

    public function removePaymentLine(int $index)
    {
        if (count($this->paymentLines) > 1) {
            unset($this->paymentLines[$index]);
            $this->paymentLines = array_values($this->paymentLines);
        }
    }

    public function getPaymentLinesTotalProperty(): float
    {
        return collect($this->paymentLines)->sum(fn($line) => (float) ($line['amount'] ?? 0));
    }

    public function storePayment()
    {
        if (!auth()->user()->hasPermission('credits.pay')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        // Validate payment lines
        if (!$this->paymentMarkComplete) {
            foreach ($this->paymentLines as $i => $line) {
                if (empty($line['payment_method_id'])) {
                    $this->dispatch('notify', message: 'Selecciona un método de pago en la línea ' . ($i + 1), type: 'error');
                    return;
                }
                if ((float) ($line['amount'] ?? 0) <= 0) {
                    $this->dispatch('notify', message: 'El monto debe ser mayor a 0 en la línea ' . ($i + 1), type: 'error');
                    return;
                }
            }
        } else {
            // Mark complete: need at least one payment method
            $hasMethod = false;
            foreach ($this->paymentLines as $line) {
                if (!empty($line['payment_method_id'])) {
                    $hasMethod = true;
                    break;
                }
            }
            if (!$hasMethod) {
                $this->dispatch('notify', message: 'Selecciona al menos un método de pago', type: 'error');
                return;
            }
        }

        $user = auth()->user();

        if ($this->paymentReferenceType === 'purchase') {
            $record = Purchase::with('supplier')->find($this->paymentReferenceId);
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

        // Calculate total amount from lines
        if ($this->paymentMarkComplete) {
            // When marking complete with multiple methods, distribute remaining across lines with amounts
            // If only one line, use the full remaining
            $totalFromLines = collect($this->paymentLines)->sum(fn($l) => (float) ($l['amount'] ?? 0));
            if ($totalFromLines > 0) {
                $totalAmount = $totalFromLines;
            } else {
                $totalAmount = $remaining;
            }
        } else {
            $totalAmount = collect($this->paymentLines)->sum(fn($l) => (float) ($l['amount'] ?? 0));
        }

        if ($totalAmount > $remaining + 0.01) {
            $this->dispatch('notify', message: 'El monto total ($' . number_format($totalAmount, 2) . ') excede el saldo pendiente ($' . number_format($remaining, 2) . ')', type: 'error');
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
        $paymentNumber = CreditPayment::generatePaymentNumber();
        $lastCreditPayment = null;

        // Create one CreditPayment per payment line
        $linesToProcess = $this->paymentLines;

        // If mark complete with single line and no amount, set the full remaining
        if ($this->paymentMarkComplete && count($linesToProcess) === 1 && (float) ($linesToProcess[0]['amount'] ?? 0) <= 0) {
            $linesToProcess[0]['amount'] = $remaining;
        }

        foreach ($linesToProcess as $i => $line) {
            $lineAmount = (float) ($line['amount'] ?? 0);
            if ($lineAmount <= 0) {
                continue;
            }

            $linePaymentNumber = count($linesToProcess) > 1 && $i > 0
                ? CreditPayment::generatePaymentNumber()
                : $paymentNumber;

            $lastCreditPayment = CreditPayment::create([
                'payment_number' => $linePaymentNumber,
                'credit_type' => $creditType,
                'purchase_id' => $this->paymentReferenceType === 'purchase' ? $record->id : null,
                'sale_id' => $this->paymentReferenceType === 'sale' ? $record->id : null,
                'customer_id' => $this->paymentReferenceType === 'sale' ? $record->customer_id : null,
                'supplier_id' => $this->paymentReferenceType === 'purchase' ? $record->supplier_id : null,
                'branch_id' => $branchId,
                'user_id' => $user->id,
                'payment_method_id' => $line['payment_method_id'],
                'cash_reconciliation_id' => $cashReconciliationId,
                'amount' => $lineAmount,
                'affects_cash' => $this->paymentAffectsCash,
                'notes' => $this->paymentNotes ?: null,
            ]);

            // If affects cash, create cash movement per line
            if ($this->paymentAffectsCash && $cashReconciliationId) {
                $movementType = $creditType === 'payable' ? 'expense' : 'income';
                $conceptPrefix = $creditType === 'payable'
                    ? "Pago crédito proveedor: {$entityName}"
                    : "Cobro crédito cliente: {$entityName}";

                $methodName = PaymentMethod::find($line['payment_method_id'])?->name ?? '';

                CashMovement::create([
                    'cash_reconciliation_id' => $cashReconciliationId,
                    'user_id' => $user->id,
                    'type' => $movementType,
                    'amount' => $lineAmount,
                    'concept' => "{$conceptPrefix} - {$docNumber} ({$methodName})",
                    'notes' => $this->paymentNotes ?: null,
                ]);
            }

            $typeLabel = $creditType === 'payable' ? 'Proveedor' : 'Cliente';
            ActivityLogService::logCreate(
                'credit_payments',
                $lastCreditPayment,
                "Pago de crédito #{$linePaymentNumber} - {$typeLabel}: {$entityName} - $" . number_format($lineAmount, 2)
            );
        }

        // Update record paid_amount and status
        $newPaidAmount = (float) $record->paid_amount + $totalAmount;
        $newStatus = $newPaidAmount >= (float) $record->credit_amount ? 'paid' : 'partial';

        $record->update([
            'paid_amount' => $newPaidAmount,
            'payment_status' => $newStatus,
        ]);

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

    // ========================================================================
    // BULK PAYMENT METHODS
    // One payment from a customer/supplier applied to multiple invoices.
    // Each invoice can have its own breakdown of payment methods.
    // ========================================================================

    /**
     * Open the bulk payment modal for a given type ('receivable' = customer, 'payable' = supplier).
     */
    public function openBulkPaymentModal(string $type = 'receivable'): void
    {
        if (!auth()->user()->hasPermission('credits.pay')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $this->bulkType = in_array($type, ['receivable', 'payable']) ? $type : 'receivable';
        $this->bulkEntityId = null;
        $this->bulkEntitySearch = '';
        $this->bulkEntityResults = [];
        $this->bulkSelectedEntity = null;
        $this->bulkInvoices = [];
        $this->bulkAffectsCash = false;
        $this->bulkNotes = '';
        $this->isBulkModalOpen = true;
    }

    public function closeBulkPaymentModal(): void
    {
        $this->isBulkModalOpen = false;
    }

    public function setBulkType(string $type): void
    {
        if (!in_array($type, ['receivable', 'payable'])) {
            return;
        }
        $this->bulkType = $type;
        $this->bulkEntityId = null;
        $this->bulkEntitySearch = '';
        $this->bulkEntityResults = [];
        $this->bulkSelectedEntity = null;
        $this->bulkInvoices = [];
    }

    /**
     * Triggered when bulkEntitySearch changes — fetch matching entities (customers or suppliers)
     * limited to those that have at least one pending/partial credit invoice.
     */
    public function updatedBulkEntitySearch(): void
    {
        $term = trim($this->bulkEntitySearch);
        if (strlen($term) < 2) {
            $this->bulkEntityResults = [];
            return;
        }

        $user = auth()->user();
        $branchId = $this->needsBranchSelection ? $this->filterBranch : $user->branch_id;

        if ($this->bulkType === 'receivable') {
            $customers = Customer::query()
                ->where('is_active', true)
                ->where(function ($q) use ($term) {
                    $q->where('first_name', 'like', "%{$term}%")
                      ->orWhere('last_name', 'like', "%{$term}%")
                      ->orWhere('business_name', 'like', "%{$term}%")
                      ->orWhere('document_number', 'like', "%{$term}%")
                      ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$term}%"]);
                })
                ->whereHas('sales', function ($q) use ($branchId, $user) {
                    $q->where('payment_type', 'credit')
                      ->where('status', 'completed')
                      ->whereIn('payment_status', ['pending', 'partial']);
                    if ($branchId) {
                        $q->where('branch_id', $branchId);
                    } elseif (!$user->isSuperAdmin()) {
                        $q->where('branch_id', $user->branch_id);
                    }
                })
                ->limit(10)
                ->get();

            $this->bulkEntityResults = $customers->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->full_name,
                'doc' => $c->document_number,
            ])->toArray();
        } else {
            // For suppliers we use whereExists since Supplier model may not define a `purchases` relation.
            $suppliers = Supplier::query()
                ->where('name', 'like', "%{$term}%")
                ->whereExists(function ($q) use ($branchId, $user) {
                    $q->select(DB::raw(1))
                      ->from('purchases')
                      ->whereColumn('purchases.supplier_id', 'suppliers.id')
                      ->where('purchases.payment_type', 'credit')
                      ->where('purchases.status', 'completed')
                      ->whereIn('purchases.payment_status', ['pending', 'partial']);
                    if ($branchId) {
                        $q->where('purchases.branch_id', $branchId);
                    } elseif (!$user->isSuperAdmin()) {
                        $q->where('purchases.branch_id', $user->branch_id);
                    }
                })
                ->limit(10)
                ->get();

            $this->bulkEntityResults = $suppliers->map(fn($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'doc' => $s->document_number ?? null,
            ])->toArray();
        }
    }

    /**
     * Select an entity (customer or supplier) and load its pending invoices.
     */
    public function selectBulkEntity(int $entityId): void
    {
        $user = auth()->user();
        $branchId = $this->needsBranchSelection ? $this->filterBranch : $user->branch_id;

        if ($this->bulkType === 'receivable') {
            $customer = Customer::find($entityId);
            if (!$customer) return;

            $this->bulkSelectedEntity = ['id' => $customer->id, 'name' => $customer->full_name];

            $salesQuery = Sale::with('branch')
                ->where('customer_id', $customer->id)
                ->where('payment_type', 'credit')
                ->where('status', 'completed')
                ->whereIn('payment_status', ['pending', 'partial']);

            if ($branchId) {
                $salesQuery->where('branch_id', $branchId);
            } elseif (!$user->isSuperAdmin()) {
                $salesQuery->where('branch_id', $user->branch_id);
            }

            $sales = $salesQuery->orderBy('created_at')->get();

            $this->bulkInvoices = $sales->map(function ($sale) {
                $remaining = (float) $sale->credit_amount - (float) $sale->paid_amount;
                return [
                    'record_type' => 'sale',
                    'id' => $sale->id,
                    'document_number' => $sale->invoice_number,
                    'date' => $sale->created_at->format('d/m/Y'),
                    'branch_id' => $sale->branch_id,
                    'branch_name' => $sale->branch->name ?? '',
                    'total' => (float) $sale->credit_amount,
                    'paid' => (float) $sale->paid_amount,
                    'remaining' => $remaining,
                    'allocated' => 0,
                    'lines' => [['payment_method_id' => '', 'amount' => 0]],
                ];
            })->toArray();
        } else {
            $supplier = Supplier::find($entityId);
            if (!$supplier) return;

            $this->bulkSelectedEntity = ['id' => $supplier->id, 'name' => $supplier->name];

            $purchasesQuery = Purchase::with('branch')
                ->where('supplier_id', $supplier->id)
                ->where('payment_type', 'credit')
                ->where('status', 'completed')
                ->whereIn('payment_status', ['pending', 'partial']);

            if ($branchId) {
                $purchasesQuery->where('branch_id', $branchId);
            } elseif (!$user->isSuperAdmin()) {
                $purchasesQuery->where('branch_id', $user->branch_id);
            }

            $purchases = $purchasesQuery->orderBy('created_at')->get();

            $this->bulkInvoices = $purchases->map(function ($p) {
                $remaining = (float) $p->credit_amount - (float) $p->paid_amount;
                return [
                    'record_type' => 'purchase',
                    'id' => $p->id,
                    'document_number' => $p->purchase_number,
                    'date' => $p->purchase_date?->format('d/m/Y') ?? $p->created_at->format('d/m/Y'),
                    'branch_id' => $p->branch_id,
                    'branch_name' => $p->branch->name ?? '',
                    'total' => (float) $p->credit_amount,
                    'paid' => (float) $p->paid_amount,
                    'remaining' => $remaining,
                    'allocated' => 0,
                    'lines' => [['payment_method_id' => '', 'amount' => 0]],
                ];
            })->toArray();
        }

        $this->bulkEntityId = $entityId;
        $this->bulkEntitySearch = '';
        $this->bulkEntityResults = [];
    }

    public function clearBulkEntity(): void
    {
        $this->bulkEntityId = null;
        $this->bulkSelectedEntity = null;
        $this->bulkInvoices = [];
    }

    public function addBulkPaymentLine(int $invoiceIndex): void
    {
        if (!isset($this->bulkInvoices[$invoiceIndex])) return;
        $this->bulkInvoices[$invoiceIndex]['lines'][] = ['payment_method_id' => '', 'amount' => 0];
    }

    public function removeBulkPaymentLine(int $invoiceIndex, int $lineIndex): void
    {
        if (!isset($this->bulkInvoices[$invoiceIndex]['lines'][$lineIndex])) return;
        if (count($this->bulkInvoices[$invoiceIndex]['lines']) <= 1) return;

        unset($this->bulkInvoices[$invoiceIndex]['lines'][$lineIndex]);
        $this->bulkInvoices[$invoiceIndex]['lines'] = array_values($this->bulkInvoices[$invoiceIndex]['lines']);
        $this->recalcBulkAllocated($invoiceIndex);
    }

    /**
     * Recalculate the allocated amount for an invoice when its line amounts change.
     * Livewire calls updatedBulkInvoices on every nested update; we extract the
     * invoice index from the dotted key (e.g. "0.lines.1.amount").
     */
    public function updatedBulkInvoices($value, $key): void
    {
        if (preg_match('/^(\d+)\.lines\./', $key, $m)) {
            $this->recalcBulkAllocated((int) $m[1]);
        }
    }

    protected function recalcBulkAllocated(int $invoiceIndex): void
    {
        if (!isset($this->bulkInvoices[$invoiceIndex])) return;

        $allocated = collect($this->bulkInvoices[$invoiceIndex]['lines'])
            ->sum(fn($l) => (float) ($l['amount'] ?? 0));

        $this->bulkInvoices[$invoiceIndex]['allocated'] = round($allocated, 2);
    }

    public function getBulkGrandTotalProperty(): float
    {
        return collect($this->bulkInvoices)->sum(fn($i) => (float) ($i['allocated'] ?? 0));
    }

    /**
     * Process the bulk payment: create one CreditPayment per invoice/method line,
     * update each invoice, and create cash movements if affects_cash is true.
     */
    public function storeBulkPayment(): void
    {
        if (!auth()->user()->hasPermission('credits.pay')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        if (!$this->bulkSelectedEntity) {
            $this->dispatch('notify', message: 'Selecciona un cliente o proveedor', type: 'error');
            return;
        }

        if (empty($this->bulkInvoices)) {
            $this->dispatch('notify', message: 'No hay facturas para pagar', type: 'error');
            return;
        }

        // Recalculate all allocated amounts to be safe
        foreach (array_keys($this->bulkInvoices) as $idx) {
            $this->recalcBulkAllocated($idx);
        }

        // Validate: at least one invoice must have an allocation > 0
        $hasAllocation = collect($this->bulkInvoices)->contains(fn($i) => (float) $i['allocated'] > 0);
        if (!$hasAllocation) {
            $this->dispatch('notify', message: 'Asigna un monto a al menos una factura', type: 'error');
            return;
        }

        // Validate each invoice that has allocation
        foreach ($this->bulkInvoices as $inv) {
            $allocated = (float) $inv['allocated'];
            if ($allocated <= 0) continue;

            if ($allocated > $inv['remaining'] + 0.01) {
                $this->dispatch('notify',
                    message: "El monto asignado a {$inv['document_number']} excede el saldo pendiente",
                    type: 'error');
                return;
            }

            foreach ($inv['lines'] as $line) {
                $amt = (float) ($line['amount'] ?? 0);
                if ($amt > 0 && empty($line['payment_method_id'])) {
                    $this->dispatch('notify',
                        message: "Selecciona un método de pago en la factura {$inv['document_number']}",
                        type: 'error');
                    return;
                }
            }
        }

        // Resolve cash reconciliation if affects cash
        $cashReconciliationId = null;
        if ($this->bulkAffectsCash) {
            $reconciliation = $this->findOpenReconciliation(auth()->user());
            if (!$reconciliation) {
                $this->dispatch('notify', message: 'No hay una caja abierta para registrar el movimiento', type: 'error');
                return;
            }
            $cashReconciliationId = $reconciliation->id;
        }

        $user = auth()->user();
        $entityName = $this->bulkSelectedEntity['name'];
        $totalProcessed = 0;
        $invoicesAffected = 0;

        try {
            DB::beginTransaction();

            foreach ($this->bulkInvoices as $inv) {
                $allocated = (float) $inv['allocated'];
                if ($allocated <= 0) continue;

                $record = $inv['record_type'] === 'sale'
                    ? Sale::find($inv['id'])
                    : Purchase::find($inv['id']);

                if (!$record) continue;

                $currentRemaining = (float) $record->credit_amount - (float) $record->paid_amount;
                if ($allocated > $currentRemaining + 0.01) {
                    throw new \Exception("El saldo de {$inv['document_number']} cambió. Recarga e intenta de nuevo.");
                }

                $invoiceTotal = 0;

                foreach ($inv['lines'] as $line) {
                    $lineAmount = (float) ($line['amount'] ?? 0);
                    if ($lineAmount <= 0) continue;

                    $paymentNumber = CreditPayment::generatePaymentNumber();
                    $creditType = $inv['record_type'] === 'sale' ? 'receivable' : 'payable';

                    $cp = CreditPayment::create([
                        'payment_number' => $paymentNumber,
                        'credit_type' => $creditType,
                        'purchase_id' => $inv['record_type'] === 'purchase' ? $record->id : null,
                        'sale_id' => $inv['record_type'] === 'sale' ? $record->id : null,
                        'customer_id' => $inv['record_type'] === 'sale' ? $record->customer_id : null,
                        'supplier_id' => $inv['record_type'] === 'purchase' ? $record->supplier_id : null,
                        'branch_id' => $record->branch_id,
                        'user_id' => $user->id,
                        'payment_method_id' => $line['payment_method_id'],
                        'cash_reconciliation_id' => $cashReconciliationId,
                        'amount' => $lineAmount,
                        'affects_cash' => $this->bulkAffectsCash,
                        'notes' => $this->bulkNotes ?: null,
                    ]);

                    if ($this->bulkAffectsCash && $cashReconciliationId) {
                        $movementType = $creditType === 'payable' ? 'expense' : 'income';
                        $conceptPrefix = $creditType === 'payable'
                            ? "Pago crédito proveedor: {$entityName}"
                            : "Cobro crédito cliente: {$entityName}";
                        $methodName = PaymentMethod::find($line['payment_method_id'])?->name ?? '';

                        CashMovement::create([
                            'cash_reconciliation_id' => $cashReconciliationId,
                            'user_id' => $user->id,
                            'type' => $movementType,
                            'amount' => $lineAmount,
                            'concept' => "{$conceptPrefix} - {$inv['document_number']} ({$methodName})",
                            'notes' => $this->bulkNotes ?: null,
                        ]);
                    }

                    $typeLabel = $creditType === 'payable' ? 'Proveedor' : 'Cliente';
                    ActivityLogService::logCreate(
                        'credit_payments',
                        $cp,
                        "Pago múltiple #{$paymentNumber} - {$typeLabel}: {$entityName} - {$inv['document_number']} - $" . number_format($lineAmount, 2)
                    );

                    $invoiceTotal += $lineAmount;
                }

                if ($invoiceTotal > 0) {
                    $newPaid = (float) $record->paid_amount + $invoiceTotal;
                    $newStatus = $newPaid >= (float) $record->credit_amount - 0.01 ? 'paid' : 'partial';
                    $record->update([
                        'paid_amount' => $newPaid,
                        'payment_status' => $newStatus,
                    ]);

                    $totalProcessed += $invoiceTotal;
                    $invoicesAffected++;
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('notify', message: 'Error: ' . $e->getMessage(), type: 'error');
            return;
        }

        $this->isBulkModalOpen = false;
        $this->dispatch('notify',
            message: "Pago múltiple registrado: $" . number_format($totalProcessed, 2) . " a {$invoicesAffected} factura(s)",
            type: 'success');
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

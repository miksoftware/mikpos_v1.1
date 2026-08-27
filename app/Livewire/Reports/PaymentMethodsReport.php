<?php

namespace App\Livewire\Reports;

use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\CreditPayment;
use App\Models\Expense;
use App\Models\PaymentMethod;
use App\Models\Payroll;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class PaymentMethodsReport extends Component
{
    use WithPagination;

    // Filters
    public string $dateRange = 'month';
    public ?string $startDate = null;
    public ?string $endDate = null;
    public ?int $selectedBranchId = null;
    public ?int $selectedCashRegisterId = null;
    public ?int $selectedPaymentMethodId = null;
    public ?int $selectedUserId = null;
    public string $flowType = 'all'; // all, income, expense
    public string $conceptType = 'all'; // all, sales, receivables, expenses, payrolls, payables, purchases
    public string $cashAffectation = 'all'; // all, with_cash, without_cash

    // View mode
    public string $viewMode = 'summary'; // summary, detail, by_user

    public function mount()
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');

        $user = auth()->user();
        if (!$user->isSuperAdmin() && $user->branch_id) {
            $this->selectedBranchId = $user->branch_id;
        }
    }

    public function updatedDateRange($value)
    {
        switch ($value) {
            case 'today':
                $this->startDate = now()->format('Y-m-d');
                $this->endDate = now()->format('Y-m-d');
                break;
            case 'week':
                $this->startDate = now()->startOfWeek()->format('Y-m-d');
                $this->endDate = now()->format('Y-m-d');
                break;
            case 'month':
                $this->startDate = now()->startOfMonth()->format('Y-m-d');
                $this->endDate = now()->format('Y-m-d');
                break;
            case 'last_month':
                $this->startDate = now()->subMonth()->startOfMonth()->format('Y-m-d');
                $this->endDate = now()->subMonth()->endOfMonth()->format('Y-m-d');
                break;
            case 'quarter':
                $this->startDate = now()->startOfQuarter()->format('Y-m-d');
                $this->endDate = now()->format('Y-m-d');
                break;
            case 'year':
                $this->startDate = now()->startOfYear()->format('Y-m-d');
                $this->endDate = now()->format('Y-m-d');
                break;
            case 'custom':
                break;
        }
        $this->resetPage();
    }

    public function updatedFlowType()
    {
        $this->resetPage();
    }

    public function updatedConceptType()
    {
        $this->resetPage();
    }

    public function updatedCashAffectation()
    {
        $this->resetPage();
    }

    public function updatedSelectedBranchId()
    {
        $this->resetPage();
    }

    public function updatedSelectedCashRegisterId()
    {
        $this->resetPage();
    }

    public function updatedSelectedPaymentMethodId()
    {
        $this->resetPage();
    }

    public function updatedSelectedUserId()
    {
        $this->resetPage();
    }

    public function updatedStartDate()
    {
        $this->resetPage();
    }

    public function updatedEndDate()
    {
        $this->resetPage();
    }

    public function updatedViewMode()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->dateRange = 'month';
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
        $this->selectedCashRegisterId = null;
        $this->selectedPaymentMethodId = null;
        $this->selectedUserId = null;
        $this->flowType = 'all';
        $this->conceptType = 'all';
        $this->cashAffectation = 'all';

        $user = auth()->user();
        if (!$user->isSuperAdmin() && $user->branch_id) {
            $this->selectedBranchId = $user->branch_id;
        } else {
            $this->selectedBranchId = null;
        }

        $this->resetPage();
    }

    private function shouldIncludeConcept(string $flow, string $concept): bool
    {
        if ($this->flowType !== 'all' && $this->flowType !== $flow) {
            return false;
        }
        if ($this->conceptType !== 'all' && $this->conceptType !== $concept) {
            return false;
        }
        return true;
    }

    private function getSalesQuery()
    {
        $query = SalePayment::join('sales', 'sale_payments.sale_id', '=', 'sales.id')
            ->join('payment_methods', 'sale_payments.payment_method_id', '=', 'payment_methods.id')
            ->join('users', 'sales.user_id', '=', 'users.id')
            ->leftJoin('branches', 'sales.branch_id', '=', 'branches.id')
            ->leftJoin('customers', 'sales.customer_id', '=', 'customers.id')
            ->where('sales.status', 'completed')
            ->whereDate('sales.created_at', '>=', $this->startDate)
            ->whereDate('sales.created_at', '<=', $this->endDate);

        if ($this->selectedBranchId) {
            $query->where('sales.branch_id', $this->selectedBranchId);
        } elseif (!auth()->user()->isSuperAdmin() && auth()->user()->branch_id) {
            $query->where('sales.branch_id', auth()->user()->branch_id);
        }

        if ($this->selectedCashRegisterId) {
            $query->whereHas('sale.cashReconciliation', function ($q) {
                $q->where('cash_register_id', $this->selectedCashRegisterId);
            });
        }

        if ($this->selectedPaymentMethodId) {
            $query->where('sale_payments.payment_method_id', $this->selectedPaymentMethodId);
        }

        if ($this->selectedUserId) {
            $query->where('sales.user_id', $this->selectedUserId);
        }

        if ($this->cashAffectation === 'with_cash') {
            $query->whereNotNull('sales.cash_reconciliation_id');
        } elseif ($this->cashAffectation === 'without_cash') {
            $query->whereNull('sales.cash_reconciliation_id');
        }

        return $query;
    }

    private function getReceivablesQuery()
    {
        $query = CreditPayment::join('payment_methods', 'credit_payments.payment_method_id', '=', 'payment_methods.id')
            ->join('users', 'credit_payments.user_id', '=', 'users.id')
            ->leftJoin('branches', 'credit_payments.branch_id', '=', 'branches.id')
            ->leftJoin('sales', 'credit_payments.sale_id', '=', 'sales.id')
            ->leftJoin('customers', function ($join) {
                $join->on('credit_payments.customer_id', '=', 'customers.id')
                    ->orWhere(function ($q) {
                        $q->whereNull('credit_payments.customer_id')
                            ->whereColumn('sales.customer_id', 'customers.id');
                    });
            })
            ->where('credit_payments.credit_type', 'receivable')
            ->whereDate('credit_payments.created_at', '>=', $this->startDate)
            ->whereDate('credit_payments.created_at', '<=', $this->endDate);

        if ($this->selectedBranchId) {
            $query->where('credit_payments.branch_id', $this->selectedBranchId);
        } elseif (!auth()->user()->isSuperAdmin() && auth()->user()->branch_id) {
            $query->where('credit_payments.branch_id', auth()->user()->branch_id);
        }

        if ($this->selectedCashRegisterId) {
            $query->whereHas('cashReconciliation', function ($q) {
                $q->where('cash_register_id', $this->selectedCashRegisterId);
            });
        }

        if ($this->selectedPaymentMethodId) {
            $query->where('credit_payments.payment_method_id', $this->selectedPaymentMethodId);
        }

        if ($this->selectedUserId) {
            $query->where('credit_payments.user_id', $this->selectedUserId);
        }

        if ($this->cashAffectation === 'with_cash') {
            $query->where('credit_payments.affects_cash', true);
        } elseif ($this->cashAffectation === 'without_cash') {
            $query->where('credit_payments.affects_cash', false);
        }

        return $query;
    }

    private function getPayablesQuery()
    {
        $query = CreditPayment::join('payment_methods', 'credit_payments.payment_method_id', '=', 'payment_methods.id')
            ->join('users', 'credit_payments.user_id', '=', 'users.id')
            ->leftJoin('branches', 'credit_payments.branch_id', '=', 'branches.id')
            ->leftJoin('purchases', 'credit_payments.purchase_id', '=', 'purchases.id')
            ->leftJoin('suppliers', function ($join) {
                $join->on('credit_payments.supplier_id', '=', 'suppliers.id')
                    ->orWhere(function ($q) {
                        $q->whereNull('credit_payments.supplier_id')
                            ->whereColumn('purchases.supplier_id', 'suppliers.id');
                    });
            })
            ->where('credit_payments.credit_type', 'payable')
            ->whereDate('credit_payments.created_at', '>=', $this->startDate)
            ->whereDate('credit_payments.created_at', '<=', $this->endDate);

        if ($this->selectedBranchId) {
            $query->where('credit_payments.branch_id', $this->selectedBranchId);
        } elseif (!auth()->user()->isSuperAdmin() && auth()->user()->branch_id) {
            $query->where('credit_payments.branch_id', auth()->user()->branch_id);
        }

        if ($this->selectedCashRegisterId) {
            $query->whereHas('cashReconciliation', function ($q) {
                $q->where('cash_register_id', $this->selectedCashRegisterId);
            });
        }

        if ($this->selectedPaymentMethodId) {
            $query->where('credit_payments.payment_method_id', $this->selectedPaymentMethodId);
        }

        if ($this->selectedUserId) {
            $query->where('credit_payments.user_id', $this->selectedUserId);
        }

        if ($this->cashAffectation === 'with_cash') {
            $query->where('credit_payments.affects_cash', true);
        } elseif ($this->cashAffectation === 'without_cash') {
            $query->where('credit_payments.affects_cash', false);
        }

        return $query;
    }

    private function getExpensesQuery()
    {
        $query = Expense::join('payment_methods', 'expenses.payment_method_id', '=', 'payment_methods.id')
            ->join('users', 'expenses.user_id', '=', 'users.id')
            ->leftJoin('branches', 'expenses.branch_id', '=', 'branches.id')
            ->leftJoin('customers', function ($join) {
                $join->on('expenses.contact_id', '=', 'customers.id')
                    ->where('expenses.contact_type', '=', 'customer');
            })
            ->leftJoin('suppliers', function ($join) {
                $join->on('expenses.contact_id', '=', 'suppliers.id')
                    ->where('expenses.contact_type', '=', 'supplier');
            })
            ->whereDate('expenses.expense_date', '>=', $this->startDate)
            ->whereDate('expenses.expense_date', '<=', $this->endDate);

        if ($this->selectedBranchId) {
            $query->where('expenses.branch_id', $this->selectedBranchId);
        } elseif (!auth()->user()->isSuperAdmin() && auth()->user()->branch_id) {
            $query->where('expenses.branch_id', auth()->user()->branch_id);
        }

        if ($this->selectedPaymentMethodId) {
            $query->where('expenses.payment_method_id', $this->selectedPaymentMethodId);
        }

        if ($this->selectedUserId) {
            $query->where('expenses.user_id', $this->selectedUserId);
        }

        return $query;
    }

    private function getPurchasesQuery()
    {
        $query = Purchase::join('payment_methods', 'purchases.payment_method_id', '=', 'payment_methods.id')
            ->join('users', 'purchases.user_id', '=', 'users.id')
            ->leftJoin('branches', 'purchases.branch_id', '=', 'branches.id')
            ->leftJoin('suppliers', 'purchases.supplier_id', '=', 'suppliers.id')
            ->where('purchases.payment_type', 'cash')
            ->whereDate('purchases.purchase_date', '>=', $this->startDate)
            ->whereDate('purchases.purchase_date', '<=', $this->endDate);

        if ($this->selectedBranchId) {
            $query->where('purchases.branch_id', $this->selectedBranchId);
        } elseif (!auth()->user()->isSuperAdmin() && auth()->user()->branch_id) {
            $query->where('purchases.branch_id', auth()->user()->branch_id);
        }

        if ($this->selectedPaymentMethodId) {
            $query->where('purchases.payment_method_id', $this->selectedPaymentMethodId);
        }

        if ($this->selectedUserId) {
            $query->where('purchases.user_id', $this->selectedUserId);
        }

        return $query;
    }

    private function getPayrollRecords()
    {
        if (!$this->shouldIncludeConcept('expense', 'payrolls')) {
            return collect();
        }

        $query = Payroll::with(['creator', 'branch'])
            ->where('status', 'pagada')
            ->whereDate('payment_date', '>=', $this->startDate)
            ->whereDate('payment_date', '<=', $this->endDate);

        if ($this->selectedBranchId) {
            $query->where('branch_id', $this->selectedBranchId);
        } elseif (!auth()->user()->isSuperAdmin() && auth()->user()->branch_id) {
            $query->where('branch_id', auth()->user()->branch_id);
        }

        if ($this->selectedUserId) {
            $query->where('created_by', $this->selectedUserId);
        }

        $payrolls = $query->get();
        $allMethods = PaymentMethod::all()->keyBy('id');
        $items = collect();

        foreach ($payrolls as $payroll) {
            $details = $payroll->payment_details;
            if (is_array($details) && count($details) > 0) {
                foreach ($details as $p) {
                    $mId = $p['method_id'] ?? null;
                    $amt = (float) ($p['amount'] ?? 0);
                    if ($amt <= 0 || !$mId) continue;
                    if ($this->selectedPaymentMethodId && $this->selectedPaymentMethodId != $mId) continue;

                    $targetRecId = $p['target_cash_reconciliation_id'] ?? null;
                    if ($this->selectedCashRegisterId && $targetRecId) {
                        $rec = \App\Models\CashReconciliation::find($targetRecId);
                        if (!$rec || $rec->cash_register_id != $this->selectedCashRegisterId) continue;
                    } elseif ($this->selectedCashRegisterId && !$targetRecId) {
                        continue;
                    }

                    $affectsCash = !empty($targetRecId);
                    if ($this->cashAffectation === 'with_cash' && !$affectsCash) continue;
                    if ($this->cashAffectation === 'without_cash' && $affectsCash) continue;

                    $methodName = $allMethods->get($mId)?->name ?? 'Nómina';
                    $items->push((object) [
                        'payroll_id' => $payroll->id,
                        'payment_method_id' => $mId,
                        'payment_method_name' => $methodName,
                        'amount' => $amt,
                        'payment_date' => $payroll->payment_date ? $payroll->payment_date->format('Y-m-d H:i:s') : $payroll->created_at->format('Y-m-d H:i:s'),
                        'document_number' => 'NOM-' . str_pad($payroll->id, 5, '0', STR_PAD_LEFT),
                        'period_label' => $payroll->period_label,
                        'user_name' => $payroll->creator?->name ?? 'Sistema',
                        'user_id' => $payroll->created_by,
                        'branch_name' => $payroll->branch?->name ?? '-',
                        'affects_cash' => $affectsCash ? 1 : 0,
                    ]);
                }
            }
        }

        return $items;
    }

    private function getSummaryData(): array
    {
        $allMethods = PaymentMethod::where('is_active', true)->orderBy('name')->get()->keyBy('id');
        $methodsMap = [];

        foreach ($allMethods as $id => $pm) {
            $methodsMap[$id] = [
                'id' => $id,
                'name' => $pm->name,
                // Incomes
                'sales_total' => 0.0,
                'sales_count' => 0,
                'sales_docs' => [],
                'receivables_total' => 0.0,
                'receivables_count' => 0,
                'receivables_docs' => [],
                // Expenses
                'expenses_total' => 0.0,
                'expenses_count' => 0,
                'expenses_docs' => [],
                'payrolls_total' => 0.0,
                'payrolls_count' => 0,
                'payrolls_docs' => [],
                'payables_total' => 0.0,
                'payables_count' => 0,
                'payables_docs' => [],
                'purchases_total' => 0.0,
                'purchases_count' => 0,
                'purchases_docs' => [],
                // Totals
                'total_income' => 0.0,
                'total_expense' => 0.0,
                'total' => 0.0, // Gross income or net depending on filter
                'net_total' => 0.0,
                'transaction_count' => 0,
            ];
        }

        // 1. Sales
        if ($this->shouldIncludeConcept('income', 'sales')) {
            $sales = (clone $this->getSalesQuery())
                ->select([
                    'sale_payments.payment_method_id',
                    'sales.invoice_number as doc',
                    'sale_payments.amount',
                    'sales.created_at as date',
                    DB::raw("COALESCE(CASE WHEN customers.customer_type = 'juridico' AND customers.business_name IS NOT NULL AND customers.business_name != '' THEN customers.business_name ELSE TRIM(CONCAT(COALESCE(customers.first_name, ''), ' ', COALESCE(customers.last_name, ''))) END, 'Cliente General') as third")
                ])->get();

            foreach ($sales as $s) {
                $mId = $s->payment_method_id;
                if (!isset($methodsMap[$mId])) {
                    $methodsMap[$mId] = $this->initMethodMap($mId, 'Método #' . $mId);
                }
                $methodsMap[$mId]['sales_total'] += (float) $s->amount;
                $methodsMap[$mId]['sales_count']++;
                if (count($methodsMap[$mId]['sales_docs']) < 15) {
                    $methodsMap[$mId]['sales_docs'][] = [
                        'doc' => $s->doc,
                        'amount' => (float) $s->amount,
                        'third' => $s->third,
                        'date' => (string) $s->date,
                    ];
                }
            }
        }

        // 2. Receivables
        if ($this->shouldIncludeConcept('income', 'receivables')) {
            $receivables = (clone $this->getReceivablesQuery())
                ->select([
                    'credit_payments.payment_method_id',
                    'credit_payments.payment_number as doc',
                    'credit_payments.amount',
                    'credit_payments.created_at as date',
                    DB::raw("COALESCE(CASE WHEN customers.customer_type = 'juridico' AND customers.business_name IS NOT NULL AND customers.business_name != '' THEN customers.business_name ELSE TRIM(CONCAT(COALESCE(customers.first_name, ''), ' ', COALESCE(customers.last_name, ''))) END, 'Cliente General') as third")
                ])->get();

            foreach ($receivables as $r) {
                $mId = $r->payment_method_id;
                if (!isset($methodsMap[$mId])) {
                    $methodsMap[$mId] = $this->initMethodMap($mId, 'Método #' . $mId);
                }
                $methodsMap[$mId]['receivables_total'] += (float) $r->amount;
                $methodsMap[$mId]['receivables_count']++;
                if (count($methodsMap[$mId]['receivables_docs']) < 15) {
                    $methodsMap[$mId]['receivables_docs'][] = [
                        'doc' => $r->doc,
                        'amount' => (float) $r->amount,
                        'third' => $r->third,
                        'date' => (string) $r->date,
                    ];
                }
            }
        }

        // 3. Expenses
        if ($this->shouldIncludeConcept('expense', 'expenses')) {
            $expenses = (clone $this->getExpensesQuery())
                ->select([
                    'expenses.id',
                    'expenses.payment_method_id',
                    'expenses.amount',
                    'expenses.description',
                    'expenses.expense_date as date',
                    DB::raw("COALESCE(suppliers.name, CASE WHEN customers.customer_type = 'juridico' AND customers.business_name IS NOT NULL AND customers.business_name != '' THEN customers.business_name ELSE TRIM(CONCAT(COALESCE(customers.first_name, ''), ' ', COALESCE(customers.last_name, ''))) END, expenses.description) as third")
                ])->get();

            foreach ($expenses as $e) {
                $mId = $e->payment_method_id;
                if (!isset($methodsMap[$mId])) {
                    $methodsMap[$mId] = $this->initMethodMap($mId, 'Método #' . $mId);
                }
                $methodsMap[$mId]['expenses_total'] += (float) $e->amount;
                $methodsMap[$mId]['expenses_count']++;
                if (count($methodsMap[$mId]['expenses_docs']) < 15) {
                    $methodsMap[$mId]['expenses_docs'][] = [
                        'doc' => 'GST-' . str_pad($e->id, 5, '0', STR_PAD_LEFT),
                        'amount' => (float) $e->amount,
                        'third' => $e->third,
                        'date' => (string) $e->date,
                    ];
                }
            }
        }

        // 4. Payrolls
        $payrollRecords = $this->getPayrollRecords();
        foreach ($payrollRecords as $pay) {
            $mId = $pay->payment_method_id;
            if (!isset($methodsMap[$mId])) {
                $methodsMap[$mId] = $this->initMethodMap($mId, $pay->payment_method_name);
            }
            $methodsMap[$mId]['payrolls_total'] += (float) $pay->amount;
            $methodsMap[$mId]['payrolls_count']++;
            if (count($methodsMap[$mId]['payrolls_docs']) < 15) {
                $methodsMap[$mId]['payrolls_docs'][] = [
                    'doc' => $pay->document_number,
                    'amount' => (float) $pay->amount,
                    'third' => 'Nómina: ' . $pay->period_label,
                    'date' => (string) $pay->payment_date,
                ];
            }
        }

        // 5. Payables (Credit Payments to Suppliers)
        if ($this->shouldIncludeConcept('expense', 'payables')) {
            $payables = (clone $this->getPayablesQuery())
                ->select([
                    'credit_payments.payment_method_id',
                    'credit_payments.payment_number as doc',
                    'credit_payments.amount',
                    'credit_payments.created_at as date',
                    DB::raw("COALESCE(suppliers.name, 'Proveedor') as third")
                ])->get();

            foreach ($payables as $p) {
                $mId = $p->payment_method_id;
                if (!isset($methodsMap[$mId])) {
                    $methodsMap[$mId] = $this->initMethodMap($mId, 'Método #' . $mId);
                }
                $methodsMap[$mId]['payables_total'] += (float) $p->amount;
                $methodsMap[$mId]['payables_count']++;
                if (count($methodsMap[$mId]['payables_docs']) < 15) {
                    $methodsMap[$mId]['payables_docs'][] = [
                        'doc' => $p->doc,
                        'amount' => (float) $p->amount,
                        'third' => $p->third,
                        'date' => (string) $p->date,
                    ];
                }
            }
        }

        // 6. Purchases (Cash)
        if ($this->shouldIncludeConcept('expense', 'purchases')) {
            $purchases = (clone $this->getPurchasesQuery())
                ->select([
                    'purchases.payment_method_id',
                    'purchases.purchase_number as doc',
                    DB::raw("COALESCE(purchases.paid_amount, purchases.total) as amount"),
                    'purchases.purchase_date as date',
                    DB::raw("COALESCE(suppliers.name, 'Proveedor') as third")
                ])->get();

            foreach ($purchases as $pur) {
                $mId = $pur->payment_method_id;
                if (!isset($methodsMap[$mId])) {
                    $methodsMap[$mId] = $this->initMethodMap($mId, 'Método #' . $mId);
                }
                $methodsMap[$mId]['purchases_total'] += (float) $pur->amount;
                $methodsMap[$mId]['purchases_count']++;
                if (count($methodsMap[$mId]['purchases_docs']) < 15) {
                    $methodsMap[$mId]['purchases_docs'][] = [
                        'doc' => $pur->doc,
                        'amount' => (float) $pur->amount,
                        'third' => $pur->third,
                        'date' => (string) $pur->date,
                    ];
                }
            }
        }

        // Compute totals per method
        $items = collect();
        foreach ($methodsMap as $m) {
            $inc = $m['sales_total'] + $m['receivables_total'];
            $exp = $m['expenses_total'] + $m['payrolls_total'] + $m['payables_total'] + $m['purchases_total'];
            $txCount = $m['sales_count'] + $m['receivables_count'] + $m['expenses_count'] + $m['payrolls_count'] + $m['payables_count'] + $m['purchases_count'];

            if ($txCount === 0 && empty($this->selectedPaymentMethodId)) {
                continue;
            }

            $m['total_income'] = $inc;
            $m['total_expense'] = $exp;
            $m['net_total'] = $inc - $exp;
            $m['total'] = $inc; // Default display total is income
            $m['transaction_count'] = $txCount;

            $items->push((object) $m);
        }

        $items = $items->sortByDesc('total_income')->values();

        $grandTotalIncome = (float) $items->sum('total_income');
        $grandTotalExpense = (float) $items->sum('total_expense');
        $grandNetTotal = $grandTotalIncome - $grandTotalExpense;
        $totalTransactions = (int) $items->sum('transaction_count');

        return [
            'items' => $items,
            'grandTotalIncome' => $grandTotalIncome,
            'grandTotalExpense' => $grandTotalExpense,
            'grandNetTotal' => $grandNetTotal,
            'totalTransactions' => $totalTransactions,
            'totalSales' => (float) $items->sum('sales_total'),
            'totalReceivables' => (float) $items->sum('receivables_total'),
            'totalExpenses' => (float) $items->sum('expenses_total'),
            'totalPayrolls' => (float) $items->sum('payrolls_total'),
            'totalPayables' => (float) $items->sum('payables_total'),
            'totalPurchases' => (float) $items->sum('purchases_total'),
            'methodsCount' => $items->count(),
        ];
    }

    private function initMethodMap(int $id, string $name): array
    {
        return [
            'id' => $id,
            'name' => $name,
            'sales_total' => 0.0,
            'sales_count' => 0,
            'sales_docs' => [],
            'receivables_total' => 0.0,
            'receivables_count' => 0,
            'receivables_docs' => [],
            'expenses_total' => 0.0,
            'expenses_count' => 0,
            'expenses_docs' => [],
            'payrolls_total' => 0.0,
            'payrolls_count' => 0,
            'payrolls_docs' => [],
            'payables_total' => 0.0,
            'payables_count' => 0,
            'payables_docs' => [],
            'purchases_total' => 0.0,
            'purchases_count' => 0,
            'purchases_docs' => [],
            'total_income' => 0.0,
            'total_expense' => 0.0,
            'total' => 0.0,
            'net_total' => 0.0,
            'transaction_count' => 0,
        ];
    }

    private function getDetailData()
    {
        $customerSql = "COALESCE(CASE WHEN customers.customer_type = 'juridico' AND customers.business_name IS NOT NULL AND customers.business_name != '' THEN customers.business_name ELSE TRIM(CONCAT(COALESCE(customers.first_name, ''), ' ', COALESCE(customers.last_name, ''))) END, 'Cliente General')";

        $queries = [];

        // 1. Sales
        if ($this->shouldIncludeConcept('income', 'sales')) {
            $queries[] = (clone $this->getSalesQuery())
                ->select([
                    DB::raw("'income' as flow_type"),
                    DB::raw("'sales' as concept_type"),
                    DB::raw("'Venta POS' as operation_label"),
                    'sales.invoice_number as document_number',
                    DB::raw("{$customerSql} as third_party_name"),
                    'sales.created_at as payment_date',
                    'payment_methods.name as payment_method_name',
                    'users.name as user_name',
                    'branches.name as branch_name',
                    'sale_payments.amount as amount',
                    DB::raw("(CASE WHEN sales.cash_reconciliation_id IS NOT NULL THEN 1 ELSE 0 END) as affects_cash")
                ]);
        }

        // 2. Receivables
        if ($this->shouldIncludeConcept('income', 'receivables')) {
            $queries[] = (clone $this->getReceivablesQuery())
                ->select([
                    DB::raw("'income' as flow_type"),
                    DB::raw("'receivables' as concept_type"),
                    DB::raw("'Cobro Cartera' as operation_label"),
                    'credit_payments.payment_number as document_number',
                    DB::raw("{$customerSql} as third_party_name"),
                    'credit_payments.created_at as payment_date',
                    'payment_methods.name as payment_method_name',
                    'users.name as user_name',
                    'branches.name as branch_name',
                    'credit_payments.amount as amount',
                    DB::raw("(CASE WHEN credit_payments.affects_cash = 1 THEN 1 ELSE 0 END) as affects_cash")
                ]);
        }

        // 3. Expenses
        if ($this->shouldIncludeConcept('expense', 'expenses')) {
            $queries[] = (clone $this->getExpensesQuery())
                ->select([
                    DB::raw("'expense' as flow_type"),
                    DB::raw("'expenses' as concept_type"),
                    DB::raw("'Gasto' as operation_label"),
                    DB::raw("CONCAT('GST-', LPAD(expenses.id, 5, '0')) as document_number"),
                    DB::raw("COALESCE(suppliers.name, {$customerSql}, expenses.description) as third_party_name"),
                    'expenses.expense_date as payment_date',
                    'payment_methods.name as payment_method_name',
                    'users.name as user_name',
                    'branches.name as branch_name',
                    'expenses.amount as amount',
                    DB::raw("0 as affects_cash")
                ]);
        }

        // 4. Payables (Proveedores)
        if ($this->shouldIncludeConcept('expense', 'payables')) {
            $queries[] = (clone $this->getPayablesQuery())
                ->select([
                    DB::raw("'expense' as flow_type"),
                    DB::raw("'payables' as concept_type"),
                    DB::raw("'Pago Proveedor' as operation_label"),
                    'credit_payments.payment_number as document_number',
                    DB::raw("COALESCE(suppliers.name, 'Proveedor') as third_party_name"),
                    'credit_payments.created_at as payment_date',
                    'payment_methods.name as payment_method_name',
                    'users.name as user_name',
                    'branches.name as branch_name',
                    'credit_payments.amount as amount',
                    DB::raw("(CASE WHEN credit_payments.affects_cash = 1 THEN 1 ELSE 0 END) as affects_cash")
                ]);
        }

        // 5. Purchases (Compras de Contado)
        if ($this->shouldIncludeConcept('expense', 'purchases')) {
            $queries[] = (clone $this->getPurchasesQuery())
                ->select([
                    DB::raw("'expense' as flow_type"),
                    DB::raw("'purchases' as concept_type"),
                    DB::raw("'Compra Contado' as operation_label"),
                    'purchases.purchase_number as document_number',
                    DB::raw("COALESCE(suppliers.name, 'Proveedor') as third_party_name"),
                    'purchases.purchase_date as payment_date',
                    'payment_methods.name as payment_method_name',
                    'users.name as user_name',
                    'branches.name as branch_name',
                    DB::raw("COALESCE(purchases.paid_amount, purchases.total) as amount"),
                    DB::raw("0 as affects_cash")
                ]);
        }

        if (count($queries) > 0) {
            $mainQuery = array_shift($queries);
            foreach ($queries as $q) {
                $mainQuery = $mainQuery->unionAll($q);
            }

            return DB::query()->fromSub($mainQuery, 'combined_payments')
                ->orderByDesc('payment_date')
                ->paginate(20);
        }

        return DB::query()->fromSub(
            SalePayment::join('sales', 'sale_payments.sale_id', '=', 'sales.id')->whereRaw('1=0')->select([DB::raw("'income' as flow_type")]),
            'combined_payments'
        )->paginate(20);
    }

    private function getByUserData(): array
    {
        $grouped = [];

        // 1. Sales by user
        if ($this->shouldIncludeConcept('income', 'sales')) {
            $salesByUser = (clone $this->getSalesQuery())
                ->select(
                    'users.id as user_id',
                    'users.name as user_name',
                    'payment_methods.name as payment_method_name',
                    DB::raw('SUM(sale_payments.amount) as total_amount'),
                    DB::raw('COUNT(DISTINCT sales.id) as tx_count')
                )
                ->groupBy('users.id', 'users.name', 'payment_methods.name')
                ->get();

            foreach ($salesByUser as $item) {
                $key = $item->user_name . '|' . $item->payment_method_name;
                $grouped[$key] = [
                    'user_id' => $item->user_id,
                    'user_name' => $item->user_name,
                    'payment_method_name' => $item->payment_method_name,
                    'income_total' => (float) $item->total_amount,
                    'expense_total' => 0.0,
                    'transaction_count' => (int) $item->tx_count,
                    'net_total' => (float) $item->total_amount,
                ];
            }
        }

        // 2. Receivables by user
        if ($this->shouldIncludeConcept('income', 'receivables')) {
            $recByUser = (clone $this->getReceivablesQuery())
                ->select(
                    'users.id as user_id',
                    'users.name as user_name',
                    'payment_methods.name as payment_method_name',
                    DB::raw('SUM(credit_payments.amount) as total_amount'),
                    DB::raw('COUNT(DISTINCT credit_payments.id) as tx_count')
                )
                ->groupBy('users.id', 'users.name', 'payment_methods.name')
                ->get();

            foreach ($recByUser as $item) {
                $key = $item->user_name . '|' . $item->payment_method_name;
                if (isset($grouped[$key])) {
                    $grouped[$key]['income_total'] += (float) $item->total_amount;
                    $grouped[$key]['transaction_count'] += (int) $item->tx_count;
                    $grouped[$key]['net_total'] += (float) $item->total_amount;
                } else {
                    $grouped[$key] = [
                        'user_id' => $item->user_id,
                        'user_name' => $item->user_name,
                        'payment_method_name' => $item->payment_method_name,
                        'income_total' => (float) $item->total_amount,
                        'expense_total' => 0.0,
                        'transaction_count' => (int) $item->tx_count,
                        'net_total' => (float) $item->total_amount,
                    ];
                }
            }
        }

        // 3. Expenses by user
        if ($this->shouldIncludeConcept('expense', 'expenses')) {
            $expByUser = (clone $this->getExpensesQuery())
                ->select(
                    'users.id as user_id',
                    'users.name as user_name',
                    'payment_methods.name as payment_method_name',
                    DB::raw('SUM(expenses.amount) as total_amount'),
                    DB::raw('COUNT(DISTINCT expenses.id) as tx_count')
                )
                ->groupBy('users.id', 'users.name', 'payment_methods.name')
                ->get();

            foreach ($expByUser as $item) {
                $key = $item->user_name . '|' . $item->payment_method_name;
                if (isset($grouped[$key])) {
                    $grouped[$key]['expense_total'] += (float) $item->total_amount;
                    $grouped[$key]['transaction_count'] += (int) $item->tx_count;
                    $grouped[$key]['net_total'] -= (float) $item->total_amount;
                } else {
                    $grouped[$key] = [
                        'user_id' => $item->user_id,
                        'user_name' => $item->user_name,
                        'payment_method_name' => $item->payment_method_name,
                        'income_total' => 0.0,
                        'expense_total' => (float) $item->total_amount,
                        'transaction_count' => (int) $item->tx_count,
                        'net_total' => -(float) $item->total_amount,
                    ];
                }
            }
        }

        return collect($grouped)->groupBy('user_name')->toArray();
    }

    public function render()
    {
        $user = auth()->user();
        $isSuperAdmin = $user->isSuperAdmin();

        $branches = $isSuperAdmin ? Branch::orderBy('name')->get() : collect();

        $cashRegistersQuery = CashRegister::active()->orderBy('name');
        if ($this->selectedBranchId) {
            $cashRegistersQuery->where('branch_id', $this->selectedBranchId);
        } elseif (!$isSuperAdmin && $user->branch_id) {
            $cashRegistersQuery->where('branch_id', $user->branch_id);
        }
        $cashRegisters = $cashRegistersQuery->with('user')->get();

        $paymentMethods = PaymentMethod::where('is_active', true)->orderBy('name')->get();

        $usersQuery = User::where('is_active', true)->orderBy('name');
        if ($this->selectedBranchId) {
            $usersQuery->where('branch_id', $this->selectedBranchId);
        } elseif (!$isSuperAdmin && $user->branch_id) {
            $usersQuery->where('branch_id', $user->branch_id);
        }
        $users = $usersQuery->get();

        $summary = $this->getSummaryData();
        $detailData = $this->viewMode === 'detail' ? $this->getDetailData() : null;
        $byUserData = $this->viewMode === 'by_user' ? $this->getByUserData() : [];

        return view('livewire.reports.payment-methods-report', compact(
            'isSuperAdmin',
            'branches',
            'cashRegisters',
            'paymentMethods',
            'users',
            'summary',
            'detailData',
            'byUserData'
        ));
    }
}

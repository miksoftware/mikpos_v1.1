<?php

namespace App\Livewire\Reports;

use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\CreditPayment;
use App\Models\PaymentMethod;
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
    public string $operationType = 'all'; // all, sales, credits
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

    public function updatedOperationType()
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
        $this->operationType = 'all';
        $this->cashAffectation = 'all';

        $user = auth()->user();
        if (!$user->isSuperAdmin() && $user->branch_id) {
            $this->selectedBranchId = $user->branch_id;
        } else {
            $this->selectedBranchId = null;
        }

        $this->resetPage();
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

    private function getCreditsQuery()
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

    private function getSummaryData(): array
    {
        $salesData = collect();
        $creditsData = collect();

        if ($this->operationType === 'all' || $this->operationType === 'sales') {
            $salesData = (clone $this->getSalesQuery())
                ->select(
                    'payment_methods.id',
                    'payment_methods.name',
                    DB::raw('SUM(sale_payments.amount) as sales_total'),
                    DB::raw('COUNT(DISTINCT sales.id) as sales_count')
                )
                ->groupBy('payment_methods.id', 'payment_methods.name')
                ->get()
                ->keyBy('id');
        }

        if ($this->operationType === 'all' || $this->operationType === 'credits') {
            $creditsData = (clone $this->getCreditsQuery())
                ->select(
                    'payment_methods.id',
                    'payment_methods.name',
                    DB::raw('SUM(credit_payments.amount) as credits_total'),
                    DB::raw('COUNT(DISTINCT credit_payments.id) as credits_count')
                )
                ->groupBy('payment_methods.id', 'payment_methods.name')
                ->get()
                ->keyBy('id');
        }

        $allMethodIds = $salesData->keys()->merge($creditsData->keys())->unique();
        $items = collect();

        foreach ($allMethodIds as $methodId) {
            $sale = $salesData->get($methodId);
            $credit = $creditsData->get($methodId);

            $name = $sale->name ?? $credit->name ?? 'Desconocido';
            $salesTotal = (float) ($sale->sales_total ?? 0);
            $salesCount = (int) ($sale->sales_count ?? 0);
            $creditsTotal = (float) ($credit->credits_total ?? 0);
            $creditsCount = (int) ($credit->credits_count ?? 0);
            $total = $salesTotal + $creditsTotal;
            $totalCount = $salesCount + $creditsCount;

            $items->push((object) [
                'id' => $methodId,
                'name' => $name,
                'sales_total' => $salesTotal,
                'sales_count' => $salesCount,
                'credits_total' => $creditsTotal,
                'credits_count' => $creditsCount,
                'total' => $total,
                'transaction_count' => $totalCount,
            ]);
        }

        $items = $items->sortByDesc('total')->values();

        $grandTotal = (float) $items->sum('total');
        $totalSales = (float) $items->sum('sales_total');
        $totalCredits = (float) $items->sum('credits_total');
        $transactionCount = (int) $items->sum('transaction_count');

        return [
            'items' => $items,
            'grandTotal' => $grandTotal,
            'totalSales' => $totalSales,
            'totalCredits' => $totalCredits,
            'transactionCount' => $transactionCount,
            'methodsCount' => $items->count(),
        ];
    }

    private function getDetailData()
    {
        $customerSql = "COALESCE(CASE WHEN customers.customer_type = 'juridico' AND customers.business_name IS NOT NULL AND customers.business_name != '' THEN customers.business_name ELSE TRIM(CONCAT(COALESCE(customers.first_name, ''), ' ', COALESCE(customers.last_name, ''))) END, 'Cliente General')";

        $salesQuery = null;
        $creditsQuery = null;

        if ($this->operationType === 'all' || $this->operationType === 'sales') {
            $salesQuery = (clone $this->getSalesQuery())
                ->select([
                    DB::raw("'sale' as operation_type"),
                    'sales.id as operation_id',
                    'sales.invoice_number as document_number',
                    DB::raw("'Venta POS' as operation_label"),
                    DB::raw("{$customerSql} as customer_name"),
                    'sales.created_at as payment_date',
                    'payment_methods.name as payment_method_name',
                    'users.name as user_name',
                    'branches.name as branch_name',
                    'sales.total as operation_total',
                    'sale_payments.amount as amount',
                    DB::raw("(CASE WHEN sales.cash_reconciliation_id IS NOT NULL THEN 1 ELSE 0 END) as affects_cash")
                ]);
        }

        if ($this->operationType === 'all' || $this->operationType === 'credits') {
            $creditsQuery = (clone $this->getCreditsQuery())
                ->select([
                    DB::raw("'credit' as operation_type"),
                    'credit_payments.id as operation_id',
                    'credit_payments.payment_number as document_number',
                    DB::raw("'Cobro Cartera' as operation_label"),
                    DB::raw("{$customerSql} as customer_name"),
                    'credit_payments.created_at as payment_date',
                    'payment_methods.name as payment_method_name',
                    'users.name as user_name',
                    'branches.name as branch_name',
                    DB::raw("COALESCE(sales.total, credit_payments.amount) as operation_total"),
                    'credit_payments.amount as amount',
                    DB::raw("(CASE WHEN credit_payments.affects_cash = 1 THEN 1 ELSE 0 END) as affects_cash")
                ]);
        }

        if ($salesQuery && $creditsQuery) {
            $unionQuery = $salesQuery->unionAll($creditsQuery);
            return DB::query()->fromSub($unionQuery, 'combined_payments')
                ->orderByDesc('payment_date')
                ->paginate(20);
        } elseif ($salesQuery) {
            return DB::query()->fromSub($salesQuery, 'combined_payments')
                ->orderByDesc('payment_date')
                ->paginate(20);
        } elseif ($creditsQuery) {
            return DB::query()->fromSub($creditsQuery, 'combined_payments')
                ->orderByDesc('payment_date')
                ->paginate(20);
        }

        return DB::query()->fromSub(
            SalePayment::join('sales', 'sale_payments.sale_id', '=', 'sales.id')->whereRaw('1=0')->select([DB::raw("'sale' as operation_type")]),
            'combined_payments'
        )->paginate(20);
    }

    private function getByUserData(): array
    {
        $salesData = collect();
        $creditsData = collect();

        if ($this->operationType === 'all' || $this->operationType === 'sales') {
            $salesData = (clone $this->getSalesQuery())
                ->select(
                    'users.id as user_id',
                    'users.name as user_name',
                    'payment_methods.name as payment_method_name',
                    DB::raw('SUM(sale_payments.amount) as sales_total'),
                    DB::raw('COUNT(DISTINCT sales.id) as sales_count')
                )
                ->groupBy('users.id', 'users.name', 'payment_methods.name')
                ->get();
        }

        if ($this->operationType === 'all' || $this->operationType === 'credits') {
            $creditsData = (clone $this->getCreditsQuery())
                ->select(
                    'users.id as user_id',
                    'users.name as user_name',
                    'payment_methods.name as payment_method_name',
                    DB::raw('SUM(credit_payments.amount) as credits_total'),
                    DB::raw('COUNT(DISTINCT credit_payments.id) as credits_count')
                )
                ->groupBy('users.id', 'users.name', 'payment_methods.name')
                ->get();
        }

        $grouped = [];

        foreach ($salesData as $item) {
            $key = $item->user_name . '|' . $item->payment_method_name;
            $grouped[$key] = [
                'user_id' => $item->user_id,
                'user_name' => $item->user_name,
                'payment_method_name' => $item->payment_method_name,
                'sales_total' => (float) $item->sales_total,
                'sales_count' => (int) $item->sales_count,
                'credits_total' => 0.0,
                'credits_count' => 0,
                'total' => (float) $item->sales_total,
                'transaction_count' => (int) $item->sales_count,
            ];
        }

        foreach ($creditsData as $item) {
            $key = $item->user_name . '|' . $item->payment_method_name;
            if (isset($grouped[$key])) {
                $grouped[$key]['credits_total'] += (float) $item->credits_total;
                $grouped[$key]['credits_count'] += (int) $item->credits_count;
                $grouped[$key]['total'] += (float) $item->credits_total;
                $grouped[$key]['transaction_count'] += (int) $item->credits_count;
            } else {
                $grouped[$key] = [
                    'user_id' => $item->user_id,
                    'user_name' => $item->user_name,
                    'payment_method_name' => $item->payment_method_name,
                    'sales_total' => 0.0,
                    'sales_count' => 0,
                    'credits_total' => (float) $item->credits_total,
                    'credits_count' => (int) $item->credits_count,
                    'total' => (float) $item->credits_total,
                    'transaction_count' => (int) $item->credits_count,
                ];
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

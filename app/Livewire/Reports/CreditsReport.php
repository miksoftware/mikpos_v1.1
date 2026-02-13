<?php

namespace App\Livewire\Reports;

use App\Models\Branch;
use App\Models\CreditPayment;
use App\Models\Customer;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Supplier;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class CreditsReport extends Component
{
    use WithPagination;

    // Filters
    public string $dateRange = 'all';
    public ?string $startDate = null;
    public ?string $endDate = null;
    public ?int $selectedBranchId = null;
    public string $creditType = ''; // payable, receivable
    public string $paymentStatus = ''; // pending, partial, paid
    public string $search = '';
    public string $viewMode = 'summary'; // summary, by_customer, by_supplier, by_date, payments

    // Summary data
    public array $summary = [];
    public array $payableSummary = [];
    public array $receivableSummary = [];
    public array $trendData = [];
    public array $topDebtors = [];
    public array $topCreditors = [];
    public array $paymentsByMethod = [];
    public array $agingPayable = [];
    public array $agingReceivable = [];
    public array $monthlyPayments = [];

    public function mount()
    {
        $this->startDate = now()->startOfYear()->format('Y-m-d');
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
            case 'all':
                $this->startDate = null;
                $this->endDate = null;
                break;
            case 'custom':
                break;
        }
    }

    public function updatedViewMode()
    {
        $this->resetPage();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    private function applyBranchFilter($query, string $table)
    {
        if ($this->selectedBranchId) {
            $query->where("{$table}.branch_id", $this->selectedBranchId);
        } elseif (!auth()->user()->isSuperAdmin()) {
            $query->where("{$table}.branch_id", auth()->user()->branch_id);
        }
        return $query;
    }

    private function applyDateFilter($query, string $table)
    {
        if ($this->startDate) {
            $query->whereDate("{$table}.created_at", '>=', $this->startDate);
        }
        if ($this->endDate) {
            $query->whereDate("{$table}.created_at", '<=', $this->endDate);
        }
        return $query;
    }

    private function calculateSummary()
    {
        // Payable summary (purchases)
        $pQuery = Purchase::where('purchases.payment_type', 'credit')
            ->where('purchases.status', 'completed');
        $this->applyBranchFilter($pQuery, 'purchases');
        $this->applyDateFilter($pQuery, 'purchases');

        $pData = (clone $pQuery)->selectRaw('
            COUNT(*) as count,
            COALESCE(SUM(purchases.credit_amount), 0) as total_credit,
            COALESCE(SUM(purchases.paid_amount), 0) as total_paid
        ')->first();

        $this->payableSummary = [
            'count' => $pData->count ?? 0,
            'total_credit' => (float) ($pData->total_credit ?? 0),
            'total_paid' => (float) ($pData->total_paid ?? 0),
            'total_remaining' => (float) ($pData->total_credit ?? 0) - (float) ($pData->total_paid ?? 0),
            'paid_count' => (clone $pQuery)->where('purchases.payment_status', 'paid')->count(),
            'partial_count' => (clone $pQuery)->where('purchases.payment_status', 'partial')->count(),
            'pending_count' => (clone $pQuery)->where('purchases.payment_status', 'pending')->count(),
        ];

        // Receivable summary (sales)
        $sQuery = Sale::where('sales.payment_type', 'credit')
            ->where('sales.status', 'completed');
        $this->applyBranchFilter($sQuery, 'sales');
        $this->applyDateFilter($sQuery, 'sales');

        $sData = (clone $sQuery)->selectRaw('
            COUNT(*) as count,
            COALESCE(SUM(sales.credit_amount), 0) as total_credit,
            COALESCE(SUM(sales.paid_amount), 0) as total_paid
        ')->first();

        $this->receivableSummary = [
            'count' => $sData->count ?? 0,
            'total_credit' => (float) ($sData->total_credit ?? 0),
            'total_paid' => (float) ($sData->total_paid ?? 0),
            'total_remaining' => (float) ($sData->total_credit ?? 0) - (float) ($sData->total_paid ?? 0),
            'paid_count' => (clone $sQuery)->where('sales.payment_status', 'paid')->count(),
            'partial_count' => (clone $sQuery)->where('sales.payment_status', 'partial')->count(),
            'pending_count' => (clone $sQuery)->where('sales.payment_status', 'pending')->count(),
        ];

        // Combined summary
        $this->summary = [
            'total_payable' => $this->payableSummary['total_remaining'],
            'total_receivable' => $this->receivableSummary['total_remaining'],
            'net_balance' => $this->receivableSummary['total_remaining'] - $this->payableSummary['total_remaining'],
            'total_credits' => $this->payableSummary['count'] + $this->receivableSummary['count'],
            'total_payments_made' => $this->payableSummary['total_paid'] + $this->receivableSummary['total_paid'],
        ];
    }

    private function loadChartData()
    {
        // Top debtors (customers who owe us)
        $debtorsQuery = Sale::where('sales.payment_type', 'credit')
            ->where('sales.status', 'completed')
            ->whereIn('sales.payment_status', ['pending', 'partial'])
            ->whereNotNull('sales.customer_id')
            ->join('customers', 'sales.customer_id', '=', 'customers.id');
        $this->applyBranchFilter($debtorsQuery, 'sales');
        $this->applyDateFilter($debtorsQuery, 'sales');

        $this->topDebtors = $debtorsQuery
            ->select(
                'customers.id',
                DB::raw("CASE WHEN customers.customer_type = 'juridico' THEN customers.business_name ELSE CONCAT(customers.first_name, ' ', customers.last_name) END as customer_name"),
                DB::raw('COUNT(*) as credit_count'),
                DB::raw('SUM(sales.credit_amount) as total_credit'),
                DB::raw('SUM(sales.paid_amount) as total_paid'),
                DB::raw('SUM(sales.credit_amount - sales.paid_amount) as total_remaining')
            )
            ->groupBy('customers.id', 'customers.customer_type', 'customers.business_name', 'customers.first_name', 'customers.last_name')
            ->orderByDesc('total_remaining')
            ->limit(10)
            ->get()
            ->map(fn($d) => [
                'name' => $d->customer_name,
                'count' => $d->credit_count,
                'total' => round($d->total_credit, 2),
                'paid' => round($d->total_paid, 2),
                'remaining' => round($d->total_remaining, 2),
            ])
            ->toArray();

        // Top creditors (suppliers we owe)
        $creditorsQuery = Purchase::where('purchases.payment_type', 'credit')
            ->where('purchases.status', 'completed')
            ->whereIn('purchases.payment_status', ['pending', 'partial'])
            ->join('suppliers', 'purchases.supplier_id', '=', 'suppliers.id');
        $this->applyBranchFilter($creditorsQuery, 'purchases');
        $this->applyDateFilter($creditorsQuery, 'purchases');

        $this->topCreditors = $creditorsQuery
            ->select(
                'suppliers.id',
                'suppliers.name as supplier_name',
                DB::raw('COUNT(*) as credit_count'),
                DB::raw('SUM(purchases.credit_amount) as total_credit'),
                DB::raw('SUM(purchases.paid_amount) as total_paid'),
                DB::raw('SUM(purchases.credit_amount - purchases.paid_amount) as total_remaining')
            )
            ->groupBy('suppliers.id', 'suppliers.name')
            ->orderByDesc('total_remaining')
            ->limit(10)
            ->get()
            ->map(fn($c) => [
                'name' => $c->supplier_name,
                'count' => $c->credit_count,
                'total' => round($c->total_credit, 2),
                'paid' => round($c->total_paid, 2),
                'remaining' => round($c->total_remaining, 2),
            ])
            ->toArray();

        // Payments by method
        $pmQuery = CreditPayment::join('payment_methods', 'credit_payments.payment_method_id', '=', 'payment_methods.id');
        $this->applyBranchFilter($pmQuery, 'credit_payments');
        if ($this->startDate) {
            $pmQuery->whereDate('credit_payments.created_at', '>=', $this->startDate);
        }
        if ($this->endDate) {
            $pmQuery->whereDate('credit_payments.created_at', '<=', $this->endDate);
        }

        $this->paymentsByMethod = $pmQuery
            ->select(
                'payment_methods.name',
                DB::raw('SUM(credit_payments.amount) as total'),
                DB::raw('COUNT(*) as count'),
                DB::raw("SUM(CASE WHEN credit_payments.credit_type = 'payable' THEN credit_payments.amount ELSE 0 END) as payable_total"),
                DB::raw("SUM(CASE WHEN credit_payments.credit_type = 'receivable' THEN credit_payments.amount ELSE 0 END) as receivable_total")
            )
            ->groupBy('payment_methods.id', 'payment_methods.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn($p) => [
                'name' => $p->name,
                'total' => round($p->total, 2),
                'count' => $p->count,
                'payable' => round($p->payable_total, 2),
                'receivable' => round($p->receivable_total, 2),
            ])
            ->toArray();

        // Aging analysis - Payable (how old are unpaid debts)
        $this->agingPayable = $this->calculateAging('purchase');
        $this->agingReceivable = $this->calculateAging('sale');

        // Monthly payments trend
        $monthlyQuery = CreditPayment::query();
        $this->applyBranchFilter($monthlyQuery, 'credit_payments');
        if ($this->startDate) {
            $monthlyQuery->whereDate('credit_payments.created_at', '>=', $this->startDate);
        }
        if ($this->endDate) {
            $monthlyQuery->whereDate('credit_payments.created_at', '<=', $this->endDate);
        }

        $this->monthlyPayments = $monthlyQuery
            ->select(
                DB::raw("DATE_FORMAT(credit_payments.created_at, '%Y-%m') as month"),
                DB::raw("SUM(CASE WHEN credit_payments.credit_type = 'payable' THEN credit_payments.amount ELSE 0 END) as payable"),
                DB::raw("SUM(CASE WHEN credit_payments.credit_type = 'receivable' THEN credit_payments.amount ELSE 0 END) as receivable"),
                DB::raw('SUM(credit_payments.amount) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(fn($m) => [
                'label' => Carbon::createFromFormat('Y-m', $m->month)->translatedFormat('M Y'),
                'payable' => round($m->payable, 2),
                'receivable' => round($m->receivable, 2),
                'total' => round($m->total, 2),
                'count' => $m->count,
            ])
            ->toArray();
    }

    private function calculateAging(string $type): array
    {
        $now = now();
        $ranges = [
            ['label' => '0-30 días', 'min' => 0, 'max' => 30],
            ['label' => '31-60 días', 'min' => 31, 'max' => 60],
            ['label' => '61-90 días', 'min' => 61, 'max' => 90],
            ['label' => '90+ días', 'min' => 91, 'max' => 9999],
        ];

        $result = [];

        if ($type === 'purchase') {
            $query = Purchase::where('purchases.payment_type', 'credit')
                ->where('purchases.status', 'completed')
                ->whereIn('purchases.payment_status', ['pending', 'partial']);
            $this->applyBranchFilter($query, 'purchases');
            $items = $query->get();

            foreach ($ranges as $range) {
                $filtered = $items->filter(function ($item) use ($now, $range) {
                    $days = $item->created_at->diffInDays($now);
                    return $days >= $range['min'] && $days <= $range['max'];
                });
                $result[] = [
                    'label' => $range['label'],
                    'count' => $filtered->count(),
                    'amount' => round($filtered->sum(fn($i) => (float) $i->credit_amount - (float) $i->paid_amount), 2),
                ];
            }
        } else {
            $query = Sale::where('sales.payment_type', 'credit')
                ->where('sales.status', 'completed')
                ->whereIn('sales.payment_status', ['pending', 'partial']);
            $this->applyBranchFilter($query, 'sales');
            $items = $query->get();

            foreach ($ranges as $range) {
                $filtered = $items->filter(function ($item) use ($now, $range) {
                    $days = $item->created_at->diffInDays($now);
                    return $days >= $range['min'] && $days <= $range['max'];
                });
                $result[] = [
                    'label' => $range['label'],
                    'count' => $filtered->count(),
                    'amount' => round($filtered->sum(fn($i) => (float) $i->credit_amount - (float) $i->paid_amount), 2),
                ];
            }
        }

        return $result;
    }

    private function getDetailData()
    {
        if ($this->viewMode === 'by_customer') {
            return $this->getByCustomerData();
        } elseif ($this->viewMode === 'by_supplier') {
            return $this->getBySupplierData();
        } elseif ($this->viewMode === 'by_date') {
            return $this->getByDateData();
        } elseif ($this->viewMode === 'payments') {
            return $this->getPaymentsData();
        }
        return collect();
    }

    private function getByCustomerData()
    {
        $query = Sale::where('sales.payment_type', 'credit')
            ->where('sales.status', 'completed')
            ->join('customers', 'sales.customer_id', '=', 'customers.id');
        $this->applyBranchFilter($query, 'sales');
        $this->applyDateFilter($query, 'sales');

        if ($this->paymentStatus) {
            $query->where('sales.payment_status', $this->paymentStatus);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('customers.first_name', 'like', "%{$this->search}%")
                    ->orWhere('customers.last_name', 'like', "%{$this->search}%")
                    ->orWhere('customers.business_name', 'like', "%{$this->search}%")
                    ->orWhere('customers.document_number', 'like', "%{$this->search}%")
                    ->orWhere('sales.invoice_number', 'like', "%{$this->search}%");
            });
        }

        return $query->select(
                'sales.*',
                DB::raw("CASE WHEN customers.customer_type = 'juridico' THEN customers.business_name ELSE CONCAT(customers.first_name, ' ', customers.last_name) END as customer_name"),
                'customers.document_number'
            )
            ->orderByDesc('sales.created_at')
            ->paginate(15);
    }

    private function getBySupplierData()
    {
        $query = Purchase::where('purchases.payment_type', 'credit')
            ->where('purchases.status', 'completed')
            ->join('suppliers', 'purchases.supplier_id', '=', 'suppliers.id');
        $this->applyBranchFilter($query, 'purchases');
        $this->applyDateFilter($query, 'purchases');

        if ($this->paymentStatus) {
            $query->where('purchases.payment_status', $this->paymentStatus);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('suppliers.name', 'like', "%{$this->search}%")
                    ->orWhere('purchases.purchase_number', 'like', "%{$this->search}%");
            });
        }

        return $query->select('purchases.*', 'suppliers.name as supplier_name')
            ->orderByDesc('purchases.created_at')
            ->paginate(15);
    }

    private function getByDateData()
    {
        // Unified credits by date
        $purchases = Purchase::where('purchases.payment_type', 'credit')
            ->where('purchases.status', 'completed')
            ->leftJoin('suppliers', 'purchases.supplier_id', '=', 'suppliers.id');
        $this->applyBranchFilter($purchases, 'purchases');
        $this->applyDateFilter($purchases, 'purchases');

        if ($this->paymentStatus) {
            $purchases->where('purchases.payment_status', $this->paymentStatus);
        }
        if ($this->creditType === 'receivable') {
            $purchases->whereRaw('1 = 0'); // exclude
        }

        $pItems = $purchases->select(
            DB::raw("'purchase' as record_type"),
            'purchases.id',
            'purchases.purchase_number as doc_number',
            'suppliers.name as entity_name',
            'purchases.created_at',
            'purchases.credit_amount',
            'purchases.paid_amount',
            'purchases.payment_status'
        )->get();

        $sales = Sale::where('sales.payment_type', 'credit')
            ->where('sales.status', 'completed')
            ->leftJoin('customers', 'sales.customer_id', '=', 'customers.id');
        $this->applyBranchFilter($sales, 'sales');
        $this->applyDateFilter($sales, 'sales');

        if ($this->paymentStatus) {
            $sales->where('sales.payment_status', $this->paymentStatus);
        }
        if ($this->creditType === 'payable') {
            $sales->whereRaw('1 = 0'); // exclude
        }

        $sItems = $sales->select(
            DB::raw("'sale' as record_type"),
            'sales.id',
            'sales.invoice_number as doc_number',
            DB::raw("CASE WHEN customers.customer_type = 'juridico' THEN customers.business_name ELSE CONCAT(customers.first_name, ' ', customers.last_name) END as entity_name"),
            'sales.created_at',
            'sales.credit_amount',
            'sales.paid_amount',
            'sales.payment_status'
        )->get();

        if ($this->search) {
            $search = mb_strtolower($this->search);
            $pItems = $pItems->filter(fn($i) => str_contains(mb_strtolower($i->doc_number . $i->entity_name), $search));
            $sItems = $sItems->filter(fn($i) => str_contains(mb_strtolower($i->doc_number . $i->entity_name), $search));
        }

        return $pItems->concat($sItems)->sortByDesc('created_at')->values();
    }

    private function getPaymentsData()
    {
        $query = CreditPayment::with(['user', 'paymentMethod', 'purchase.supplier', 'sale.customer']);
        $this->applyBranchFilter($query, 'credit_payments');

        if ($this->startDate) {
            $query->whereDate('credit_payments.created_at', '>=', $this->startDate);
        }
        if ($this->endDate) {
            $query->whereDate('credit_payments.created_at', '<=', $this->endDate);
        }

        if ($this->creditType) {
            $query->where('credit_payments.credit_type', $this->creditType);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('credit_payments.payment_number', 'like', "%{$this->search}%")
                    ->orWhereHas('purchase.supplier', function ($sq) {
                        $sq->where('name', 'like', "%{$this->search}%");
                    })
                    ->orWhereHas('sale.customer', function ($cq) {
                        $cq->where('first_name', 'like', "%{$this->search}%")
                            ->orWhere('last_name', 'like', "%{$this->search}%")
                            ->orWhere('business_name', 'like', "%{$this->search}%");
                    });
            });
        }

        return $query->orderByDesc('credit_payments.created_at')->paginate(20);
    }

    public function clearFilters()
    {
        $this->dateRange = 'all';
        $this->startDate = null;
        $this->endDate = null;
        $this->creditType = '';
        $this->paymentStatus = '';
        $this->search = '';
        $this->viewMode = 'summary';
        if (auth()->user()->isSuperAdmin()) {
            $this->selectedBranchId = null;
        }
        $this->resetPage();
    }

    public function render()
    {
        $this->calculateSummary();
        $this->loadChartData();

        $branches = auth()->user()->isSuperAdmin()
            ? Branch::where('is_active', true)->orderBy('name')->get()
            : collect();

        $detailData = $this->viewMode !== 'summary' ? $this->getDetailData() : collect();

        return view('livewire.reports.credits-report', [
            'branches' => $branches,
            'isSuperAdmin' => auth()->user()->isSuperAdmin(),
            'detailData' => $detailData,
        ]);
    }
}

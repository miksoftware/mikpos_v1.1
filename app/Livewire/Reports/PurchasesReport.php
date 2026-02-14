<?php

namespace App\Livewire\Reports;

use App\Models\Branch;
use App\Models\CreditPayment;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class PurchasesReport extends Component
{
    use WithPagination;

    // Filters
    public string $dateRange = 'month';
    public ?string $startDate = null;
    public ?string $endDate = null;
    public ?int $selectedBranchId = null;
    public ?int $selectedSupplierId = null;
    public string $paymentType = '';
    public string $paymentStatus = '';
    public string $search = '';

    // View mode tabs
    public string $viewMode = 'by_supplier';

    // Summary
    public array $summary = [];
    public array $chartBySupplier = [];
    public array $chartByDate = [];
    public array $chartPaymentType = [];

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

    public function updatedViewMode() { $this->resetPage(); }
    public function updatedSearch() { $this->resetPage(); }
    public function updatedSelectedSupplierId() { $this->resetPage(); }
    public function updatedPaymentType() { $this->resetPage(); }
    public function updatedPaymentStatus() { $this->resetPage(); }
    public function updatedSelectedBranchId() { $this->resetPage(); }

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

    private function applyCommonFilters($query)
    {
        if ($this->paymentType) {
            $query->where('purchases.payment_type', $this->paymentType);
        }
        if ($this->paymentStatus) {
            $query->where('purchases.payment_status', $this->paymentStatus);
        }
        if ($this->selectedSupplierId) {
            $query->where('purchases.supplier_id', $this->selectedSupplierId);
        }
        return $query;
    }

    private function baseQuery()
    {
        $query = Purchase::where('purchases.status', 'completed');
        $this->applyBranchFilter($query, 'purchases');
        $this->applyDateFilter($query, 'purchases');
        $this->applyCommonFilters($query);
        return $query;
    }

    private function calculateSummary()
    {
        $query = $this->baseQuery();

        $data = (clone $query)->selectRaw('
            COUNT(*) as total_count,
            COALESCE(SUM(purchases.total), 0) as total_amount,
            COALESCE(SUM(CASE WHEN purchases.payment_type = \'cash\' THEN purchases.total ELSE 0 END), 0) as cash_total,
            COALESCE(SUM(CASE WHEN purchases.payment_type = \'credit\' THEN purchases.total ELSE 0 END), 0) as credit_total,
            COALESCE(SUM(purchases.paid_amount), 0) as total_paid,
            COALESCE(SUM(purchases.credit_amount - purchases.paid_amount), 0) as total_pending
        ')->first();

        $this->summary = [
            'total_count' => $data->total_count ?? 0,
            'total_amount' => round((float) ($data->total_amount ?? 0), 2),
            'cash_total' => round((float) ($data->cash_total ?? 0), 2),
            'credit_total' => round((float) ($data->credit_total ?? 0), 2),
            'total_paid' => round((float) ($data->total_paid ?? 0), 2),
            'total_pending' => round((float) ($data->total_pending ?? 0), 2),
        ];
    }

    private function loadChartData()
    {
        // Chart: purchases by supplier (top 10)
        $supplierQuery = (clone $this->baseQuery())
            ->join('suppliers', 'purchases.supplier_id', '=', 'suppliers.id')
            ->select('suppliers.name', DB::raw('SUM(purchases.total) as total'))
            ->groupBy('suppliers.id', 'suppliers.name')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $this->chartBySupplier = $supplierQuery->map(fn($s) => [
            'label' => $s->name,
            'value' => round((float) $s->total, 2),
        ])->toArray();

        // Chart: purchases by date
        $dateQuery = (clone $this->baseQuery())
            ->select(
                DB::raw("DATE_FORMAT(purchases.created_at, '%Y-%m-%d') as date"),
                DB::raw('SUM(purchases.total) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $this->chartByDate = $dateQuery->map(fn($d) => [
            'label' => $d->date,
            'value' => round((float) $d->total, 2),
            'count' => $d->count,
        ])->toArray();

        // Chart: payment type distribution
        $typeQuery = (clone $this->baseQuery())
            ->select(
                'purchases.payment_type',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(purchases.total) as total')
            )
            ->groupBy('purchases.payment_type')
            ->get();

        $this->chartPaymentType = $typeQuery->map(fn($t) => [
            'label' => $t->payment_type === 'cash' ? 'Contado' : 'Crédito',
            'value' => round((float) $t->total, 2),
            'count' => $t->count,
        ])->toArray();
    }

    private function getBySupplier()
    {
        $query = $this->baseQuery()
            ->join('suppliers', 'purchases.supplier_id', '=', 'suppliers.id')
            ->select(
                'suppliers.id',
                'suppliers.name as supplier_name',
                DB::raw('COUNT(*) as purchase_count'),
                DB::raw('SUM(purchases.total) as total_amount'),
                DB::raw('SUM(purchases.tax_amount) as total_tax'),
                DB::raw('SUM(purchases.subtotal) as total_subtotal')
            )
            ->groupBy('suppliers.id', 'suppliers.name');

        if ($this->search) {
            $query->where('suppliers.name', 'like', "%{$this->search}%");
        }

        return $query->orderByDesc('total_amount')->paginate(15);
    }

    private function getByDate()
    {
        $query = $this->baseQuery()
            ->leftJoin('suppliers', 'purchases.supplier_id', '=', 'suppliers.id')
            ->select(
                'purchases.*',
                'suppliers.name as supplier_name'
            );

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('purchases.purchase_number', 'like', "%{$this->search}%")
                    ->orWhere('suppliers.name', 'like', "%{$this->search}%");
            });
        }

        return $query->orderByDesc('purchases.created_at')->paginate(15);
    }

    private function getPaymentsByDate()
    {
        $query = CreditPayment::where('credit_payments.credit_type', 'payable')
            ->join('purchases', 'credit_payments.purchase_id', '=', 'purchases.id')
            ->leftJoin('suppliers', 'credit_payments.supplier_id', '=', 'suppliers.id')
            ->leftJoin('payment_methods', 'credit_payments.payment_method_id', '=', 'payment_methods.id');

        $this->applyBranchFilter($query, 'credit_payments');

        if ($this->startDate) {
            $query->whereDate('credit_payments.created_at', '>=', $this->startDate);
        }
        if ($this->endDate) {
            $query->whereDate('credit_payments.created_at', '<=', $this->endDate);
        }
        if ($this->selectedSupplierId) {
            $query->where('credit_payments.supplier_id', $this->selectedSupplierId);
        }
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('credit_payments.payment_number', 'like', "%{$this->search}%")
                    ->orWhere('suppliers.name', 'like', "%{$this->search}%")
                    ->orWhere('purchases.purchase_number', 'like', "%{$this->search}%");
            });
        }

        return $query->select(
            'credit_payments.*',
            'suppliers.name as supplier_name',
            'purchases.purchase_number',
            'payment_methods.name as payment_method_name'
        )->orderByDesc('credit_payments.created_at')->paginate(15);
    }

    private function getCreditsBySupplier()
    {
        $query = Purchase::where('purchases.status', 'completed')
            ->where('purchases.payment_type', 'credit')
            ->join('suppliers', 'purchases.supplier_id', '=', 'suppliers.id');

        $this->applyBranchFilter($query, 'purchases');
        $this->applyDateFilter($query, 'purchases');

        if ($this->selectedSupplierId) {
            $query->where('purchases.supplier_id', $this->selectedSupplierId);
        }
        if ($this->paymentStatus) {
            $query->where('purchases.payment_status', $this->paymentStatus);
        }
        if ($this->search) {
            $query->where('suppliers.name', 'like', "%{$this->search}%");
        }

        return $query->select(
            'suppliers.id',
            'suppliers.name as supplier_name',
            DB::raw('COUNT(*) as credit_count'),
            DB::raw('SUM(purchases.credit_amount) as total_credit'),
            DB::raw('SUM(purchases.paid_amount) as total_paid'),
            DB::raw('SUM(purchases.credit_amount - purchases.paid_amount) as total_pending')
        )
            ->groupBy('suppliers.id', 'suppliers.name')
            ->orderByDesc('total_pending')
            ->paginate(15);
    }

    private function getCreditsByDate()
    {
        $query = Purchase::where('purchases.status', 'completed')
            ->where('purchases.payment_type', 'credit')
            ->leftJoin('suppliers', 'purchases.supplier_id', '=', 'suppliers.id');

        $this->applyBranchFilter($query, 'purchases');
        $this->applyDateFilter($query, 'purchases');

        if ($this->selectedSupplierId) {
            $query->where('purchases.supplier_id', $this->selectedSupplierId);
        }
        if ($this->paymentStatus) {
            $query->where('purchases.payment_status', $this->paymentStatus);
        }
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('purchases.purchase_number', 'like', "%{$this->search}%")
                    ->orWhere('suppliers.name', 'like', "%{$this->search}%");
            });
        }

        return $query->select(
            'purchases.*',
            'suppliers.name as supplier_name'
        )->orderByDesc('purchases.created_at')->paginate(15);
    }

    private function getDetailsBySupplier()
    {
        $query = PurchaseItem::join('purchases', 'purchase_items.purchase_id', '=', 'purchases.id')
            ->join('suppliers', 'purchases.supplier_id', '=', 'suppliers.id')
            ->leftJoin('products', 'purchase_items.product_id', '=', 'products.id')
            ->where('purchases.status', 'completed');

        $this->applyBranchFilter($query, 'purchases');
        $this->applyDateFilter($query, 'purchases');

        if ($this->selectedSupplierId) {
            $query->where('purchases.supplier_id', $this->selectedSupplierId);
        }
        if ($this->paymentType) {
            $query->where('purchases.payment_type', $this->paymentType);
        }
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('products.name', 'like', "%{$this->search}%")
                    ->orWhere('suppliers.name', 'like', "%{$this->search}%")
                    ->orWhere('purchases.purchase_number', 'like', "%{$this->search}%");
            });
        }

        return $query->select(
            'purchase_items.*',
            'purchases.purchase_number',
            'purchases.created_at as purchase_date',
            'suppliers.name as supplier_name',
            'products.name as product_name',
            'products.sku'
        )->orderBy('suppliers.name')->orderByDesc('purchases.created_at')->paginate(20);
    }

    private function getDetailsByDate()
    {
        $query = PurchaseItem::join('purchases', 'purchase_items.purchase_id', '=', 'purchases.id')
            ->leftJoin('suppliers', 'purchases.supplier_id', '=', 'suppliers.id')
            ->leftJoin('products', 'purchase_items.product_id', '=', 'products.id')
            ->where('purchases.status', 'completed');

        $this->applyBranchFilter($query, 'purchases');
        $this->applyDateFilter($query, 'purchases');

        if ($this->selectedSupplierId) {
            $query->where('purchases.supplier_id', $this->selectedSupplierId);
        }
        if ($this->paymentType) {
            $query->where('purchases.payment_type', $this->paymentType);
        }
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('products.name', 'like', "%{$this->search}%")
                    ->orWhere('suppliers.name', 'like', "%{$this->search}%")
                    ->orWhere('purchases.purchase_number', 'like', "%{$this->search}%");
            });
        }

        return $query->select(
            'purchase_items.*',
            'purchases.purchase_number',
            'purchases.created_at as purchase_date',
            'suppliers.name as supplier_name',
            'products.name as product_name',
            'products.sku'
        )->orderByDesc('purchases.created_at')->paginate(20);
    }

    public function clearFilters()
    {
        $this->dateRange = 'month';
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
        $this->selectedSupplierId = null;
        $this->paymentType = '';
        $this->paymentStatus = '';
        $this->search = '';
        $this->viewMode = 'by_supplier';
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

        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();

        $detailData = match ($this->viewMode) {
            'by_supplier' => $this->getBySupplier(),
            'by_date' => $this->getByDate(),
            'payments_by_date' => $this->getPaymentsByDate(),
            'credits_by_supplier' => $this->getCreditsBySupplier(),
            'credits_by_date' => $this->getCreditsByDate(),
            'details_by_supplier' => $this->getDetailsBySupplier(),
            'details_by_date' => $this->getDetailsByDate(),
            default => collect(),
        };

        return view('livewire.reports.purchases-report', [
            'branches' => $branches,
            'suppliers' => $suppliers,
            'isSuperAdmin' => auth()->user()->isSuperAdmin(),
            'detailData' => $detailData,
        ]);
    }
}

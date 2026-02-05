<?php

namespace App\Livewire\Reports;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Sale;
use App\Models\Branch;
use App\Models\User;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\CashRegister;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Carbon\Carbon;

#[Layout('layouts.app')]
class SalesBook extends Component
{
    use WithPagination;

    // Filters
    public string $dateRange = 'month';
    public ?string $startDate = null;
    public ?string $endDate = null;
    public ?int $selectedBranchId = null;
    public ?int $selectedUserId = null;
    public ?int $selectedCustomerId = null;
    public ?int $selectedPaymentMethodId = null;
    public ?int $selectedCashRegisterId = null;
    public string $search = '';
    public string $statusFilter = 'all'; // all, completed, cancelled

    // Summary stats
    public float $totalSales = 0;
    public float $totalSubtotal = 0;
    public float $totalTax = 0;
    public float $totalDiscount = 0;
    public int $totalTransactions = 0;
    public float $averageTicket = 0;
    public float $totalProfit = 0;

    // Chart data
    public array $salesByDay = [];
    public array $salesByPaymentMethod = [];
    public array $salesByUser = [];
    public array $salesByCashRegister = [];
    public array $salesByHour = [];

    // Detail modal
    public ?int $selectedSaleId = null;
    public ?Sale $selectedSale = null;
    public bool $isDetailModalOpen = false;

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
            case 'yesterday':
                $this->startDate = now()->subDay()->format('Y-m-d');
                $this->endDate = now()->subDay()->format('Y-m-d');
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

    private function getBaseQuery()
    {
        $query = Sale::query();

        if ($this->startDate) {
            $query->whereDate('sales.created_at', '>=', $this->startDate);
        }
        if ($this->endDate) {
            $query->whereDate('sales.created_at', '<=', $this->endDate);
        }

        if ($this->selectedBranchId) {
            $query->where('sales.branch_id', $this->selectedBranchId);
        } elseif (!auth()->user()->isSuperAdmin()) {
            $query->where('sales.branch_id', auth()->user()->branch_id);
        }

        if ($this->selectedUserId) {
            $query->where('sales.user_id', $this->selectedUserId);
        }

        if ($this->selectedCustomerId) {
            $query->where('sales.customer_id', $this->selectedCustomerId);
        }

        if ($this->selectedPaymentMethodId) {
            $query->whereHas('payments', function ($q) {
                $q->where('payment_method_id', $this->selectedPaymentMethodId);
            });
        }

        if ($this->selectedCashRegisterId) {
            $query->whereHas('cashReconciliation', function ($q) {
                $q->where('cash_register_id', $this->selectedCashRegisterId);
            });
        }

        if ($this->statusFilter !== 'all') {
            $query->where('sales.status', $this->statusFilter);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('sales.invoice_number', 'like', "%{$this->search}%")
                  ->orWhere('sales.dian_number', 'like', "%{$this->search}%")
                  ->orWhereHas('customer', function ($cq) {
                      $cq->where('name', 'like', "%{$this->search}%")
                        ->orWhere('business_name', 'like', "%{$this->search}%")
                        ->orWhere('document_number', 'like', "%{$this->search}%");
                  });
            });
        }

        return $query;
    }

    private function calculateSummary()
    {
        $query = $this->getBaseQuery()->where('sales.status', 'completed');

        $summary = $query->selectRaw('
            COUNT(*) as total_transactions,
            COALESCE(SUM(sales.subtotal), 0) as total_subtotal,
            COALESCE(SUM(sales.tax_total), 0) as total_tax,
            COALESCE(SUM(sales.discount), 0) as total_discount,
            COALESCE(SUM(sales.total), 0) as total_sales
        ')->first();

        $this->totalTransactions = $summary->total_transactions ?? 0;
        $this->totalSubtotal = (float) ($summary->total_subtotal ?? 0);
        $this->totalTax = (float) ($summary->total_tax ?? 0);
        $this->totalDiscount = (float) ($summary->total_discount ?? 0);
        $this->totalSales = (float) ($summary->total_sales ?? 0);
        $this->averageTicket = $this->totalTransactions > 0 
            ? $this->totalSales / $this->totalTransactions 
            : 0;

        // Calculate profit from sale items
        $this->totalProfit = $this->calculateProfit();
    }

    private function calculateProfit(): float
    {
        $query = $this->getBaseQuery()
            ->where('sales.status', 'completed')
            ->with(['items.product']);

        $profit = 0;
        foreach ($query->get() as $sale) {
            foreach ($sale->items as $item) {
                if ($item->product) {
                    $cost = $item->product->purchase_price * $item->quantity;
                    $revenue = $item->subtotal;
                    $profit += ($revenue - $cost);
                }
            }
        }

        return $profit;
    }

    private function loadChartData()
    {
        $baseQuery = $this->getBaseQuery()->where('sales.status', 'completed');

        // Sales by day
        $this->salesByDay = (clone $baseQuery)
            ->select(
                DB::raw("DATE(sales.created_at) as sale_date"),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(sales.total) as total')
            )
            ->groupBy('sale_date')
            ->orderBy('sale_date')
            ->get()
            ->map(function ($item) {
                return [
                    'label' => Carbon::parse($item->sale_date)->format('d M'),
                    'count' => $item->count,
                    'total' => round($item->total, 2),
                ];
            })
            ->toArray();

        // Sales by payment method
        $this->salesByPaymentMethod = DB::table('sale_payments')
            ->join('sales', 'sale_payments.sale_id', '=', 'sales.id')
            ->join('payment_methods', 'sale_payments.payment_method_id', '=', 'payment_methods.id')
            ->where('sales.status', 'completed')
            ->whereDate('sales.created_at', '>=', $this->startDate)
            ->whereDate('sales.created_at', '<=', $this->endDate)
            ->when($this->selectedBranchId, fn($q) => $q->where('sales.branch_id', $this->selectedBranchId))
            ->when(!auth()->user()->isSuperAdmin() && !$this->selectedBranchId, 
                fn($q) => $q->where('sales.branch_id', auth()->user()->branch_id))
            ->select(
                'payment_methods.name',
                DB::raw('COUNT(DISTINCT sales.id) as count'),
                DB::raw('SUM(sale_payments.amount) as total')
            )
            ->groupBy('payment_methods.id', 'payment_methods.name')
            ->orderByDesc('total')
            ->get()
            ->toArray();

        // Sales by user
        $this->salesByUser = (clone $baseQuery)
            ->join('users', 'sales.user_id', '=', 'users.id')
            ->select(
                'users.id',
                'users.name',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(sales.total) as total')
            )
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->toArray();

        // Sales by cash register
        $this->salesByCashRegister = (clone $baseQuery)
            ->join('cash_reconciliations', 'sales.cash_reconciliation_id', '=', 'cash_reconciliations.id')
            ->join('cash_registers', 'cash_reconciliations.cash_register_id', '=', 'cash_registers.id')
            ->select(
                'cash_registers.id',
                'cash_registers.name',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(sales.total) as total')
            )
            ->groupBy('cash_registers.id', 'cash_registers.name')
            ->orderByDesc('total')
            ->get()
            ->toArray();

        // Sales by hour
        $this->salesByHour = (clone $baseQuery)
            ->select(
                DB::raw("HOUR(sales.created_at) as hour"),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(sales.total) as total')
            )
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->map(function ($item) {
                return [
                    'hour' => str_pad($item->hour, 2, '0', STR_PAD_LEFT) . ':00',
                    'count' => $item->count,
                    'total' => round($item->total, 2),
                ];
            })
            ->toArray();
    }

    public function viewSaleDetail(int $saleId)
    {
        $this->selectedSaleId = $saleId;
        $this->selectedSale = Sale::with([
            'customer',
            'user',
            'branch',
            'items.product',
            'items.service',
            'payments.paymentMethod',
            'cashReconciliation.cashRegister'
        ])->find($saleId);
        $this->isDetailModalOpen = true;
    }

    public function closeDetailModal()
    {
        $this->isDetailModalOpen = false;
        $this->selectedSaleId = null;
        $this->selectedSale = null;
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->dateRange = 'month';
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
        $this->selectedUserId = null;
        $this->selectedCustomerId = null;
        $this->selectedPaymentMethodId = null;
        $this->selectedCashRegisterId = null;
        $this->statusFilter = 'all';
        if (auth()->user()->isSuperAdmin()) {
            $this->selectedBranchId = null;
        }
        $this->resetPage();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $user = auth()->user();
        $isSuperAdmin = $user->isSuperAdmin();

        $this->calculateSummary();
        $this->loadChartData();

        $sales = $this->getBaseQuery()
            ->with(['customer', 'user', 'branch', 'payments.paymentMethod'])
            ->orderByDesc('created_at')
            ->paginate(15);

        $branches = $isSuperAdmin ? Branch::where('is_active', true)->orderBy('name')->get() : collect();
        $users = User::whereHas('roles')->orderBy('name')->get();
        $customers = Customer::orderBy('first_name')->limit(100)->get();
        $paymentMethods = PaymentMethod::where('is_active', true)->orderBy('name')->get();
        
        $cashRegistersQuery = CashRegister::where('is_active', true);
        if ($this->selectedBranchId) {
            $cashRegistersQuery->where('branch_id', $this->selectedBranchId);
        } elseif (!$isSuperAdmin) {
            $cashRegistersQuery->where('branch_id', $user->branch_id);
        }
        $cashRegisters = $cashRegistersQuery->orderBy('name')->get();

        return view('livewire.reports.sales-book', [
            'sales' => $sales,
            'branches' => $branches,
            'users' => $users,
            'customers' => $customers,
            'paymentMethods' => $paymentMethods,
            'cashRegisters' => $cashRegisters,
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }
}

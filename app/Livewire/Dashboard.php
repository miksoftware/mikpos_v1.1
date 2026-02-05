<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Sale;
use App\Models\Product;
use App\Models\Customer;
use App\Models\CashReconciliation;
use App\Models\SaleItem;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

#[Layout('layouts.app')]
class Dashboard extends Component
{
    // Today stats
    public float $salesToday = 0;
    public int $transactionsToday = 0;
    public int $itemsSoldToday = 0;
    public float $averageTicketToday = 0;

    // Month stats
    public float $salesMonth = 0;
    public int $transactionsMonth = 0;
    public float $salesGrowth = 0;

    // General stats
    public int $totalProducts = 0;
    public int $totalCustomers = 0;
    public int $lowStockProducts = 0;
    public bool $hasOpenCashRegister = false;

    // Chart data
    public array $salesByDay = [];
    public array $salesByHour = [];
    public array $topProducts = [];
    public array $salesByPaymentMethod = [];

    public function mount()
    {
        $this->loadStats();
        $this->loadChartData();
    }

    private function getBaseQuery()
    {
        $query = Sale::where('status', 'completed');
        
        $user = auth()->user();
        if (!$user->isSuperAdmin()) {
            // Non-super admin users only see their own sales
            $query->where('user_id', $user->id);
        }

        return $query;
    }

    private function loadStats()
    {
        $user = auth()->user();
        $branchId = $user->isSuperAdmin() ? null : $user->branch_id;

        // Today stats
        $todayQuery = $this->getBaseQuery()->whereDate('created_at', today());
        $this->salesToday = $todayQuery->sum('total');
        $this->transactionsToday = $todayQuery->count();
        $this->itemsSoldToday = SaleItem::whereIn('sale_id', $todayQuery->pluck('id'))->sum('quantity');
        $this->averageTicketToday = $this->transactionsToday > 0 
            ? $this->salesToday / $this->transactionsToday 
            : 0;

        // Month stats
        $monthQuery = $this->getBaseQuery()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);
        $this->salesMonth = $monthQuery->sum('total');
        $this->transactionsMonth = $monthQuery->count();

        // Last month for growth calculation
        $lastMonthSales = $this->getBaseQuery()
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->sum('total');
        
        $this->salesGrowth = $lastMonthSales > 0 
            ? (($this->salesMonth - $lastMonthSales) / $lastMonthSales) * 100 
            : 0;

        // Products count
        $productsQuery = Product::where('is_active', true);
        if ($branchId) {
            $productsQuery->where('branch_id', $branchId);
        }
        $this->totalProducts = $productsQuery->count();

        // Low stock products
        $lowStockQuery = Product::where('is_active', true)
            ->whereColumn('current_stock', '<=', 'min_stock')
            ->where('min_stock', '>', 0);
        if ($branchId) {
            $lowStockQuery->where('branch_id', $branchId);
        }
        $this->lowStockProducts = $lowStockQuery->count();

        // Customers count
        $customersQuery = Customer::query();
        if ($branchId) {
            $customersQuery->where('branch_id', $branchId);
        }
        $this->totalCustomers = $customersQuery->count();

        // Check open cash register
        if ($branchId) {
            $this->hasOpenCashRegister = CashReconciliation::whereHas('cashRegister', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })->where('status', 'open')->exists();
        }
    }

    private function loadChartData()
    {
        // Sales by day (last 7 days)
        $this->salesByDay = $this->getBaseQuery()
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'label' => Carbon::parse($item->date)->format('D d'),
                    'total' => round($item->total, 2),
                    'count' => $item->count,
                ];
            })
            ->toArray();

        // Sales by hour (today)
        $this->salesByHour = $this->getBaseQuery()
            ->whereDate('created_at', today())
            ->select(
                DB::raw('HOUR(created_at) as hour'),
                DB::raw('SUM(total) as total')
            )
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->map(function ($item) {
                return [
                    'hour' => sprintf('%02d:00', $item->hour),
                    'total' => round($item->total, 2),
                ];
            })
            ->toArray();

        // Top 5 products (this month)
        $user = auth()->user();
        $topProductsQuery = SaleItem::query()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sales.status', 'completed')
            ->whereMonth('sales.created_at', now()->month)
            ->whereYear('sales.created_at', now()->year);
        
        if (!$user->isSuperAdmin()) {
            // Non-super admin users only see their own sales
            $topProductsQuery->where('sales.user_id', $user->id);
        }

        $this->topProducts = $topProductsQuery
            ->select(
                'sale_items.product_name',
                DB::raw('SUM(sale_items.quantity) as quantity'),
                DB::raw('SUM(sale_items.total) as total')
            )
            ->groupBy('sale_items.product_name')
            ->orderByDesc('quantity')
            ->limit(5)
            ->get()
            ->toArray();

        // Sales by payment method (this month)
        $paymentQuery = DB::table('sale_payments')
            ->join('sales', 'sale_payments.sale_id', '=', 'sales.id')
            ->join('payment_methods', 'sale_payments.payment_method_id', '=', 'payment_methods.id')
            ->where('sales.status', 'completed')
            ->whereMonth('sales.created_at', now()->month)
            ->whereYear('sales.created_at', now()->year);

        if (!$user->isSuperAdmin()) {
            // Non-super admin users only see their own sales
            $paymentQuery->where('sales.user_id', $user->id);
        }

        $this->salesByPaymentMethod = $paymentQuery
            ->select(
                'payment_methods.name',
                DB::raw('SUM(sale_payments.amount) as total')
            )
            ->groupBy('payment_methods.id', 'payment_methods.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn($item) => ['name' => $item->name, 'total' => round($item->total, 2)])
            ->toArray();
    }

    public function render()
    {
        $user = auth()->user();
        $isSuperAdmin = $user->isSuperAdmin();
        $branchName = $isSuperAdmin ? 'MikPOS' : ($user->branch?->name ?? 'MikPOS');

        return view('livewire.dashboard', [
            'isSuperAdmin' => $isSuperAdmin,
            'branchName' => $branchName,
            'userName' => $user->name,
        ]);
    }
}

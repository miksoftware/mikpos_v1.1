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
    // Today stats (visible to all)
    public float $salesToday = 0;
    public int $transactionsToday = 0;
    public int $itemsSoldToday = 0;
    public float $averageTicketToday = 0;

    // Month stats (admin only)
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

    // Role-based visibility
    public bool $isAdmin = false;

    // Cashier/seller personal stats
    public int $mySalesCountToday = 0;
    public float $myBestSaleToday = 0;
    public int $mySalesCountMonth = 0;
    public float $mySalesMonth = 0;

    public function mount()
    {
        $user = auth()->user();
        $roleName = $user->roles->first()?->name ?? '';
        $this->isAdmin = in_array($roleName, ['super_admin', 'branch_admin']);

        $this->loadStats();

        if ($this->isAdmin) {
            $this->loadChartData();
        } else {
            $this->loadPersonalStats();
            $this->loadPersonalChartData();
        }
    }

    private function getBaseQuery()
    {
        $query = Sale::where('sales.status', 'completed');

        $user = auth()->user();
        if (!$user->isSuperAdmin()) {
            $query->where('sales.user_id', $user->id);
        }

        return $query;
    }

    private function loadStats()
    {
        $user = auth()->user();
        $branchId = $user->isSuperAdmin() ? null : $user->branch_id;

        // Today stats (visible to all)
        $todayQuery = $this->getBaseQuery()->whereDate('sales.created_at', today());
        $this->salesToday = (clone $todayQuery)->sum('total');
        $this->transactionsToday = (clone $todayQuery)->count();
        $this->itemsSoldToday = (int) SaleItem::whereIn('sale_id', (clone $todayQuery)->pluck('sales.id'))->sum('quantity');
        $this->averageTicketToday = $this->transactionsToday > 0
            ? $this->salesToday / $this->transactionsToday
            : 0;

        if ($this->isAdmin) {
            // Month stats (admin only)
            $monthQuery = $this->getBaseQuery()
                ->whereMonth('sales.created_at', now()->month)
                ->whereYear('sales.created_at', now()->year);
            $this->salesMonth = $monthQuery->sum('total');
            $this->transactionsMonth = $monthQuery->count();

            // Last month for growth calculation
            $lastMonthSales = $this->getBaseQuery()
                ->whereMonth('sales.created_at', now()->subMonth()->month)
                ->whereYear('sales.created_at', now()->subMonth()->year)
                ->sum('total');

            $this->salesGrowth = $lastMonthSales > 0
                ? (($this->salesMonth - $lastMonthSales) / $lastMonthSales) * 100
                : 0;

            // Products count (admin only)
            $productsQuery = Product::where('is_active', true);
            if ($branchId) {
                $productsQuery->where('branch_id', $branchId);
            }
            $this->totalProducts = $productsQuery->count();

            // Low stock products (admin only)
            $lowStockQuery = Product::where('is_active', true)
                ->whereColumn('current_stock', '<=', 'min_stock')
                ->where('min_stock', '>', 0);
            if ($branchId) {
                $lowStockQuery->where('branch_id', $branchId);
            }
            $this->lowStockProducts = $lowStockQuery->count();

            // Customers count (admin only)
            $customersQuery = Customer::query();
            if ($branchId) {
                $customersQuery->where('branch_id', $branchId);
            }
            $this->totalCustomers = $customersQuery->count();
        }

        // Check open cash register (visible to all)
        if ($branchId) {
            $this->hasOpenCashRegister = CashReconciliation::whereHas('cashRegister', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })->where('status', 'open')->exists();
        }
    }

    private function loadPersonalStats()
    {
        $userId = auth()->id();

        // Personal sales count today
        $this->mySalesCountToday = Sale::where('status', 'completed')
            ->where('user_id', $userId)
            ->whereDate('created_at', today())
            ->count();

        // Best sale today
        $this->myBestSaleToday = (float) Sale::where('status', 'completed')
            ->where('user_id', $userId)
            ->whereDate('created_at', today())
            ->max('total') ?? 0;

        // Personal month stats
        $this->mySalesCountMonth = Sale::where('status', 'completed')
            ->where('user_id', $userId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $this->mySalesMonth = (float) Sale::where('status', 'completed')
            ->where('user_id', $userId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total');
    }

    private function loadPersonalChartData()
    {
        $userId = auth()->id();

        // Sales by hour (today) - personal
        $this->salesByHour = Sale::where('status', 'completed')
            ->where('user_id', $userId)
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

        // Top 5 products sold by this user today
        $this->topProducts = SaleItem::query()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sales.status', 'completed')
            ->where('sales.user_id', $userId)
            ->whereDate('sales.created_at', today())
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
    }

    private function loadChartData()
    {
        // Sales by day (last 7 days)
        $this->salesByDay = $this->getBaseQuery()
            ->where('sales.created_at', '>=', now()->subDays(6)->startOfDay())
            ->select(
                DB::raw('DATE(sales.created_at) as date'),
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
            ->whereDate('sales.created_at', today())
            ->select(
                DB::raw('HOUR(sales.created_at) as hour'),
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



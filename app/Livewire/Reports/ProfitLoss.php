<?php

namespace App\Livewire\Reports;

use App\Models\Branch;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\Purchase;
use App\Models\CashMovement;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ProfitLoss extends Component
{
    // Filters
    public string $dateRange = 'month';
    public ?string $startDate = null;
    public ?string $endDate = null;
    public ?int $selectedBranchId = null;

    // Summary
    public float $totalRevenue = 0;
    public float $totalCost = 0;
    public float $grossProfit = 0;
    public float $grossMargin = 0;
    public float $totalExpenses = 0;
    public float $netProfit = 0;
    public float $netMargin = 0;
    public int $totalTransactions = 0;
    public float $totalTax = 0;
    public float $totalDiscount = 0;
    public float $totalPurchases = 0;

    // Chart data
    public array $profitByDay = [];
    public array $revenueByCategory = [];
    public array $profitByCategory = [];
    public array $expenseBreakdown = [];
    public array $monthlyComparison = [];
    public array $topProfitableProducts = [];
    public array $topLossProducts = [];
    public array $revenueByPaymentMethod = [];

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
    }

    private function applyBranchFilter($query, string $table = 'sales')
    {
        if ($this->selectedBranchId) {
            $query->where("{$table}.branch_id", $this->selectedBranchId);
        } elseif (!auth()->user()->isSuperAdmin()) {
            $query->where("{$table}.branch_id", auth()->user()->branch_id);
        }
        return $query;
    }

    private function calculateSummary()
    {
        // Revenue from completed sales
        $salesQuery = Sale::where('sales.status', 'completed')
            ->whereDate('sales.created_at', '>=', $this->startDate)
            ->whereDate('sales.created_at', '<=', $this->endDate);
        $this->applyBranchFilter($salesQuery);

        $salesSummary = (clone $salesQuery)->selectRaw('
            COUNT(*) as transactions,
            COALESCE(SUM(sales.subtotal), 0) as subtotal,
            COALESCE(SUM(sales.tax_total), 0) as tax,
            COALESCE(SUM(sales.discount), 0) as discount,
            COALESCE(SUM(sales.total), 0) as revenue
        ')->first();

        $this->totalTransactions = $salesSummary->transactions ?? 0;
        $this->totalRevenue = (float) ($salesSummary->revenue ?? 0);
        $this->totalTax = (float) ($salesSummary->tax ?? 0);
        $this->totalDiscount = (float) ($salesSummary->discount ?? 0);

        // Cost of goods sold from sale items
        $this->totalCost = 0;
        $sales = (clone $salesQuery)->with('items.product')->get();
        foreach ($sales as $sale) {
            foreach ($sale->items as $item) {
                if ($item->product) {
                    $this->totalCost += $item->product->purchase_price * (float) $item->quantity;
                }
            }
        }

        // Purchases total
        $purchasesQuery = Purchase::whereDate('purchases.created_at', '>=', $this->startDate)
            ->whereDate('purchases.created_at', '<=', $this->endDate);
        $this->applyBranchFilter($purchasesQuery, 'purchases');
        $this->totalPurchases = (float) $purchasesQuery->sum('total');

        // Cash expenses (egresos from cash movements)
        $expensesQuery = CashMovement::where('cash_movements.type', 'expense')
            ->whereDate('cash_movements.created_at', '>=', $this->startDate)
            ->whereDate('cash_movements.created_at', '<=', $this->endDate);
        if ($this->selectedBranchId) {
            $expensesQuery->whereHas('reconciliation', fn($q) => $q->where('branch_id', $this->selectedBranchId));
        } elseif (!auth()->user()->isSuperAdmin()) {
            $expensesQuery->whereHas('reconciliation', fn($q) => $q->where('branch_id', auth()->user()->branch_id));
        }
        $this->totalExpenses = (float) $expensesQuery->sum('amount');

        // Gross profit = Revenue - Cost of goods sold
        $this->grossProfit = $this->totalRevenue - $this->totalCost;
        $this->grossMargin = $this->totalRevenue > 0 ? ($this->grossProfit / $this->totalRevenue) * 100 : 0;

        // Net profit = Gross profit - Expenses
        $this->netProfit = $this->grossProfit - $this->totalExpenses;
        $this->netMargin = $this->totalRevenue > 0 ? ($this->netProfit / $this->totalRevenue) * 100 : 0;
    }

    private function loadChartData()
    {
        // Profit by day
        $salesByDay = Sale::where('sales.status', 'completed')
            ->whereDate('sales.created_at', '>=', $this->startDate)
            ->whereDate('sales.created_at', '<=', $this->endDate);
        $this->applyBranchFilter($salesByDay);

        $dailySales = (clone $salesByDay)
            ->select(DB::raw("DATE(sales.created_at) as sale_date"), DB::raw('SUM(sales.total) as revenue'))
            ->groupBy('sale_date')
            ->orderBy('sale_date')
            ->get()
            ->keyBy('sale_date');

        // Calculate daily cost
        $dailyCost = [];
        $salesWithItems = (clone $salesByDay)->with('items.product')->get();
        foreach ($salesWithItems as $sale) {
            $date = $sale->created_at->format('Y-m-d');
            if (!isset($dailyCost[$date])) $dailyCost[$date] = 0;
            foreach ($sale->items as $item) {
                if ($item->product) {
                    $dailyCost[$date] += $item->product->purchase_price * (float) $item->quantity;
                }
            }
        }

        $this->profitByDay = [];
        foreach ($dailySales as $date => $day) {
            $cost = $dailyCost[$date] ?? 0;
            $this->profitByDay[] = [
                'label' => Carbon::parse($date)->format('d M'),
                'revenue' => round($day->revenue, 2),
                'cost' => round($cost, 2),
                'profit' => round($day->revenue - $cost, 2),
            ];
        }

        // Revenue & profit by category
        $categoryData = SaleItem::join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->leftJoin('products', 'sale_items.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->where('sales.status', 'completed')
            ->whereDate('sales.created_at', '>=', $this->startDate)
            ->whereDate('sales.created_at', '<=', $this->endDate);
        $this->applyBranchFilter($categoryData);

        $catResults = (clone $categoryData)
            ->select(
                DB::raw("COALESCE(categories.name, 'Sin categoría') as category_name"),
                DB::raw('SUM(sale_items.subtotal) as revenue'),
                DB::raw('SUM(sale_items.quantity * products.purchase_price) as cost')
            )
            ->groupBy('categories.name')
            ->orderByDesc('revenue')
            ->get();

        $this->revenueByCategory = $catResults->map(fn($c) => [
            'name' => $c->category_name,
            'revenue' => round($c->revenue, 2),
            'cost' => round($c->cost ?? 0, 2),
            'profit' => round($c->revenue - ($c->cost ?? 0), 2),
        ])->toArray();

        // Revenue by payment method
        $paymentData = SalePayment::join('sales', 'sale_payments.sale_id', '=', 'sales.id')
            ->join('payment_methods', 'sale_payments.payment_method_id', '=', 'payment_methods.id')
            ->where('sales.status', 'completed')
            ->whereDate('sales.created_at', '>=', $this->startDate)
            ->whereDate('sales.created_at', '<=', $this->endDate);
        $this->applyBranchFilter($paymentData);

        $this->revenueByPaymentMethod = (clone $paymentData)
            ->select('payment_methods.name', DB::raw('SUM(sale_payments.amount) as total'), DB::raw('COUNT(DISTINCT sales.id) as count'))
            ->groupBy('payment_methods.id', 'payment_methods.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn($p) => ['name' => $p->name, 'total' => round($p->total, 2), 'count' => $p->count])
            ->toArray();

        // Top profitable products
        $productProfits = SaleItem::join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->where('sales.status', 'completed')
            ->whereDate('sales.created_at', '>=', $this->startDate)
            ->whereDate('sales.created_at', '<=', $this->endDate);
        $this->applyBranchFilter($productProfits);

        $allProducts = (clone $productProfits)
            ->select(
                'products.name',
                'products.sku',
                DB::raw('SUM(sale_items.quantity) as qty'),
                DB::raw('SUM(sale_items.subtotal) as revenue'),
                DB::raw('SUM(sale_items.quantity * products.purchase_price) as cost')
            )
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->get()
            ->map(fn($p) => [
                'name' => $p->name,
                'sku' => $p->sku,
                'qty' => $p->qty,
                'revenue' => round($p->revenue, 2),
                'cost' => round($p->cost, 2),
                'profit' => round($p->revenue - $p->cost, 2),
                'margin' => $p->revenue > 0 ? round((($p->revenue - $p->cost) / $p->revenue) * 100, 1) : 0,
            ]);

        $this->topProfitableProducts = $allProducts->sortByDesc('profit')->take(10)->values()->toArray();
        $this->topLossProducts = $allProducts->filter(fn($p) => $p['profit'] < 0)->sortBy('profit')->take(10)->values()->toArray();

        // Expense breakdown
        $expQuery = CashMovement::where('cash_movements.type', 'expense')
            ->whereDate('cash_movements.created_at', '>=', $this->startDate)
            ->whereDate('cash_movements.created_at', '<=', $this->endDate);
        if ($this->selectedBranchId) {
            $expQuery->whereHas('reconciliation', fn($q) => $q->where('branch_id', $this->selectedBranchId));
        } elseif (!auth()->user()->isSuperAdmin()) {
            $expQuery->whereHas('reconciliation', fn($q) => $q->where('branch_id', auth()->user()->branch_id));
        }
        $this->expenseBreakdown = $expQuery
            ->select('concept', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('concept')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn($e) => ['concept' => $e->concept, 'total' => round($e->total, 2), 'count' => $e->count])
            ->toArray();
    }

    public function exportExcel()
    {
        if (!auth()->user()->hasPermission('reports.export')) {
            $this->dispatch('notify', message: 'No tienes permiso para exportar', type: 'error');
            return;
        }

        return redirect()->route('reports.profit-loss.excel', [
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'branch_id' => $this->selectedBranchId,
        ]);
    }

    public function clearFilters()
    {
        $this->dateRange = 'month';
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
        if (auth()->user()->isSuperAdmin()) {
            $this->selectedBranchId = null;
        }
    }

    public function render()
    {
        $this->calculateSummary();
        $this->loadChartData();

        $branches = auth()->user()->isSuperAdmin()
            ? Branch::where('is_active', true)->orderBy('name')->get()
            : collect();

        return view('livewire.reports.profit-loss', [
            'branches' => $branches,
            'isSuperAdmin' => auth()->user()->isSuperAdmin(),
        ]);
    }
}

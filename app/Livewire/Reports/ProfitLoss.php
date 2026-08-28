<?php

namespace App\Livewire\Reports;

use App\Models\Branch;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\Purchase;
use App\Models\CashMovement;
use App\Models\Expense;
use App\Models\Category;
use App\Models\Refund;
use App\Models\RefundItem;
use App\Models\CreditNote;
use App\Models\CreditNoteItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ProfitLoss extends Component
{
    // Mode
    public string $viewMode = 'standard'; // 'standard' | 'versus'

    // Standard Filters
    public string $dateRange = 'month';
    public ?string $startDate = null;
    public ?string $endDate = null;
    public ?int $selectedBranchId = null;

    // Versus Filters
    public string $versusPreset = 'mom'; // 'mom' (Mes anterior), 'yoy' (Año anterior), 'custom'
    public string $monthA = '';
    public string $monthB = '';
    public ?string $startDateA = null;
    public ?string $endDateA = null;
    public ?string $startDateB = null;
    public ?string $endDateB = null;
    public string $labelA = '';
    public string $labelB = '';

    // Standard Summary
    public float $totalRevenue = 0;
    public float $totalCost = 0;
    public float $grossProfit = 0;
    public float $grossMargin = 0;
    public float $totalExpenses = 0;
    public float $totalCashExpenses = 0;
    public float $totalModuleExpenses = 0;
    public float $totalPayrollExpenses = 0;
    public float $totalCashIncome = 0;
    public float $netProfit = 0;
    public float $netMargin = 0;
    public int $totalTransactions = 0;
    public float $totalTax = 0;
    public float $totalDiscount = 0;
    public float $totalPurchases = 0;
    public float $rawRevenue = 0;
    public float $totalRefunds = 0;
    public float $totalRefundsCost = 0;
    public int $totalRefundsCount = 0;

    // Standard Chart data
    public array $profitByDay = [];
    public array $revenueByCategory = [];
    public array $profitByCategory = [];
    public array $expenseBreakdown = [];
    public array $monthlyComparison = [];
    public array $topProfitableProducts = [];
    public array $topLossProducts = [];
    public array $revenueByPaymentMethod = [];

    // Versus Comparison Data
    public array $versusSummary = [];
    public array $versusDaily = [];
    public array $versusCategories = [];
    public array $versusPaymentMethods = [];
    public array $versusExpenses = [];
    public array $versusTopGrowthProducts = [];
    public array $versusTopDropProducts = [];

    public function mount()
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');

        // Initialize Versus periods
        $this->initVersusPeriods();

        $user = auth()->user();
        if (!$user->isSuperAdmin() && $user->branch_id) {
            $this->selectedBranchId = $user->branch_id;
        }
    }

    public function setViewMode(string $mode)
    {
        $this->viewMode = $mode;
        if ($mode === 'versus' && empty($this->startDateA)) {
            $this->initVersusPeriods();
        }
    }

    public function initVersusPeriods()
    {
        $this->monthA = now()->format('Y-m');
        $this->monthB = now()->subMonth()->format('Y-m');
        $this->applyVersusPreset();
    }

    public function updatedVersusPreset($value)
    {
        $this->applyVersusPreset();
    }

    public function updatedMonthA($value)
    {
        if ($this->versusPreset === 'custom' && $value) {
            $date = Carbon::createFromFormat('Y-m', $value);
            $this->startDateA = $date->copy()->startOfMonth()->format('Y-m-d');
            $this->endDateA = ($value === now()->format('Y-m')) ? now()->format('Y-m-d') : $date->copy()->endOfMonth()->format('Y-m-d');
            $this->labelA = $date->translatedFormat('F Y');
        }
    }

    public function updatedMonthB($value)
    {
        if ($this->versusPreset === 'custom' && $value) {
            $date = Carbon::createFromFormat('Y-m', $value);
            $this->startDateB = $date->copy()->startOfMonth()->format('Y-m-d');
            $this->endDateB = ($value === now()->format('Y-m')) ? now()->format('Y-m-d') : $date->copy()->endOfMonth()->format('Y-m-d');
            $this->labelB = $date->translatedFormat('F Y');
        }
    }

    public function applyVersusPreset()
    {
        switch ($this->versusPreset) {
            case 'mom':
                $this->monthA = now()->format('Y-m');
                $this->monthB = now()->subMonth()->format('Y-m');
                $this->startDateA = now()->startOfMonth()->format('Y-m-d');
                $this->endDateA = now()->format('Y-m-d');
                $this->startDateB = now()->subMonth()->startOfMonth()->format('Y-m-d');
                $this->endDateB = now()->subMonth()->endOfMonth()->format('Y-m-d');
                $this->labelA = Carbon::parse($this->startDateA)->translatedFormat('F Y');
                $this->labelB = Carbon::parse($this->startDateB)->translatedFormat('F Y');
                break;

            case 'yoy':
                $this->monthA = now()->format('Y-m');
                $this->monthB = now()->subYear()->format('Y-m');
                $this->startDateA = now()->startOfMonth()->format('Y-m-d');
                $this->endDateA = now()->format('Y-m-d');
                $this->startDateB = now()->subYear()->startOfMonth()->format('Y-m-d');
                $this->endDateB = now()->subYear()->endOfMonth()->format('Y-m-d');
                $this->labelA = Carbon::parse($this->startDateA)->translatedFormat('F Y');
                $this->labelB = Carbon::parse($this->startDateB)->translatedFormat('F Y');
                break;

            case 'custom':
                if (empty($this->monthA)) $this->monthA = now()->format('Y-m');
                if (empty($this->monthB)) $this->monthB = now()->subMonth()->format('Y-m');
                $dateA = Carbon::createFromFormat('Y-m', $this->monthA);
                $dateB = Carbon::createFromFormat('Y-m', $this->monthB);
                $this->startDateA = $dateA->copy()->startOfMonth()->format('Y-m-d');
                $this->endDateA = ($this->monthA === now()->format('Y-m')) ? now()->format('Y-m-d') : $dateA->copy()->endOfMonth()->format('Y-m-d');
                $this->startDateB = $dateB->copy()->startOfMonth()->format('Y-m-d');
                $this->endDateB = ($this->monthB === now()->format('Y-m')) ? now()->format('Y-m-d') : $dateB->copy()->endOfMonth()->format('Y-m-d');
                $this->labelA = $dateA->translatedFormat('F Y');
                $this->labelB = $dateB->translatedFormat('F Y');
                break;
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

    private function applySupervisorSalesFilter($query, string $reconciliationColumn = 'sales.cash_reconciliation_id')
    {
        $user = auth()->user();
        if (!$user->isSupervisor()) return $query;

        $registerIds = $user->getSupervisorCashRegisterIds();
        if (empty($registerIds)) {
            $query->whereRaw('0 = 1');
        } else {
            $reconciliationIds = \App\Models\CashReconciliation::whereIn('cash_register_id', $registerIds)->pluck('id');
            $query->whereIn($reconciliationColumn, $reconciliationIds);
        }
        return $query;
    }

    /**
     * Compute full metrics for an arbitrary period.
     */
    private function computePeriodMetrics(string $start, string $end): array
    {
        $salesQuery = Sale::where('sales.status', 'completed')
            ->whereDate('sales.created_at', '>=', $start)
            ->whereDate('sales.created_at', '<=', $end);
        $this->applyBranchFilter($salesQuery);
        $this->applySupervisorSalesFilter($salesQuery);

        $salesSummary = (clone $salesQuery)->selectRaw('
            COUNT(*) as transactions,
            COALESCE(SUM(sales.subtotal), 0) as subtotal,
            COALESCE(SUM(sales.tax_total), 0) as tax,
            COALESCE(SUM(sales.discount), 0) as discount,
            COALESCE(SUM(sales.total), 0) as revenue
        ')->first();

        $transactions = (int) ($salesSummary->transactions ?? 0);
        $revenue = (float) ($salesSummary->revenue ?? 0);
        $tax = (float) ($salesSummary->tax ?? 0);
        $discount = (float) ($salesSummary->discount ?? 0);

        // COGS and daily maps
        $cost = 0;
        $dailySales = [];
        $dailyCost = [];
        $salesWithItems = (clone $salesQuery)->with('items.product')->get();
        foreach ($salesWithItems as $sale) {
            $dayNum = (int) $sale->created_at->format('j');
            $saleCost = 0;
            foreach ($sale->items as $item) {
                if ($item->product) {
                    $itemCost = $item->unit_cost * (float) $item->quantity;
                    $cost += $itemCost;
                    $saleCost += $itemCost;
                }
            }
            $dailySales[$dayNum] = ($dailySales[$dayNum] ?? 0) + (float) $sale->total;
            $dailyCost[$dayNum] = ($dailyCost[$dayNum] ?? 0) + $saleCost;
        }

        // Refunds & Credit Notes
        $refundsQuery = Refund::query()
            ->where('refunds.status', 'completed')
            ->whereDate('refunds.created_at', '>=', $start)
            ->whereDate('refunds.created_at', '<=', $end)
            ->whereHas('sale', fn($q) => $q->where('sales.status', 'completed'));
        if ($this->selectedBranchId) {
            $refundsQuery->where('refunds.branch_id', $this->selectedBranchId);
        } elseif (!auth()->user()->isSuperAdmin()) {
            $refundsQuery->where('refunds.branch_id', auth()->user()->branch_id);
        }
        if (auth()->user()->isSupervisor()) {
            $supervisorRegisterIds = auth()->user()->getSupervisorCashRegisterIds();
            if (empty($supervisorRegisterIds)) {
                $refundsQuery->whereRaw('0 = 1');
            } else {
                $reconciliationIds = \App\Models\CashReconciliation::whereIn('cash_register_id', $supervisorRegisterIds)->pluck('id');
                $refundsQuery->whereHas('sale', fn($q) => $q->whereIn('cash_reconciliation_id', $reconciliationIds));
            }
        }
        $refundsAgg = (clone $refundsQuery)->selectRaw('COUNT(*) as count, COALESCE(SUM(refunds.total), 0) as total')->first();
        $refundAmount = (float) ($refundsAgg->total ?? 0);
        $refundCount = (int) ($refundsAgg->count ?? 0);

        $refundCost = 0;
        $refundIds = (clone $refundsQuery)->pluck('refunds.id');
        if ($refundIds->isNotEmpty()) {
            $refundCost = (float) RefundItem::join('sale_items', 'refund_items.sale_item_id', '=', 'sale_items.id')
                ->whereIn('refund_items.refund_id', $refundIds)
                ->sum(DB::raw('refund_items.quantity * sale_items.unit_cost'));
        }

        $creditNotesQuery = CreditNote::query()
            ->whereIn('credit_notes.status', ['pending', 'validated'])
            ->whereDate('credit_notes.created_at', '>=', $start)
            ->whereDate('credit_notes.created_at', '<=', $end)
            ->whereHas('sale', fn($q) => $q->where('sales.status', 'completed'));
        if ($this->selectedBranchId) {
            $creditNotesQuery->where('credit_notes.branch_id', $this->selectedBranchId);
        } elseif (!auth()->user()->isSuperAdmin()) {
            $creditNotesQuery->where('credit_notes.branch_id', auth()->user()->branch_id);
        }
        if (auth()->user()->isSupervisor()) {
            $supervisorRegisterIds = auth()->user()->getSupervisorCashRegisterIds();
            if (empty($supervisorRegisterIds)) {
                $creditNotesQuery->whereRaw('0 = 1');
            } else {
                $reconciliationIds = \App\Models\CashReconciliation::whereIn('cash_register_id', $supervisorRegisterIds)->pluck('id');
                $creditNotesQuery->whereHas('sale', fn($q) => $q->whereIn('cash_reconciliation_id', $reconciliationIds));
            }
        }
        $cnAgg = (clone $creditNotesQuery)->selectRaw('COUNT(*) as count, COALESCE(SUM(credit_notes.total), 0) as total')->first();
        $cnAmount = (float) ($cnAgg->total ?? 0);
        $cnCount = (int) ($cnAgg->count ?? 0);

        $cnCost = 0;
        $cnIds = (clone $creditNotesQuery)->pluck('credit_notes.id');
        if ($cnIds->isNotEmpty()) {
            $cnCost = (float) CreditNoteItem::join('sale_items', 'credit_note_items.sale_item_id', '=', 'sale_items.id')
                ->whereIn('credit_note_items.credit_note_id', $cnIds)
                ->sum(DB::raw('credit_note_items.quantity * sale_items.unit_cost'));
        }

        $totalRefunds = round($refundAmount + $cnAmount, 2);
        $totalRefundsCost = round($refundCost + $cnCost, 2);
        $totalRefundsCount = $refundCount + $cnCount;

        $rawRevenue = $revenue;
        $realRevenue = max(0, $revenue - $totalRefunds);
        $realCost = max(0, $cost - $totalRefundsCost);

        // Purchases
        $purchasesQuery = Purchase::whereDate('purchases.created_at', '>=', $start)
            ->whereDate('purchases.created_at', '<=', $end);
        $this->applyBranchFilter($purchasesQuery, 'purchases');
        $totalPurchases = (float) $purchasesQuery->sum('total');

        // Cash Incomes
        $cashIncomeQuery = CashMovement::where('cash_movements.type', 'income')
            ->whereDate('cash_movements.created_at', '>=', $start)
            ->whereDate('cash_movements.created_at', '<=', $end);
        if ($this->selectedBranchId) {
            $cashIncomeQuery->whereHas('reconciliation', fn($q) => $q->where('branch_id', $this->selectedBranchId));
        } elseif (!auth()->user()->isSuperAdmin()) {
            $cashIncomeQuery->whereHas('reconciliation', fn($q) => $q->where('branch_id', auth()->user()->branch_id));
        }
        $totalCashIncome = (float) $cashIncomeQuery->sum('amount');

        // Cash Expenses
        $cashExpQuery = CashMovement::where('cash_movements.type', 'expense')
            ->whereDate('cash_movements.created_at', '>=', $start)
            ->whereDate('cash_movements.created_at', '<=', $end)
            ->where(function ($q) {
                $q->where('cash_movements.concept', 'not like', 'Devolución %')
                  ->where('cash_movements.concept', 'not like', 'Nota Crédito %');
            });
        if ($this->selectedBranchId) {
            $cashExpQuery->whereHas('reconciliation', fn($q) => $q->where('branch_id', $this->selectedBranchId));
        } elseif (!auth()->user()->isSuperAdmin()) {
            $cashExpQuery->whereHas('reconciliation', fn($q) => $q->where('branch_id', auth()->user()->branch_id));
        }
        $totalCashExpenses = (float) $cashExpQuery->sum('amount');

        // Module Expenses
        $moduleExpQuery = Expense::whereDate('expenses.expense_date', '>=', $start)
            ->whereDate('expenses.expense_date', '<=', $end);
        if ($this->selectedBranchId) {
            $moduleExpQuery->where('expenses.branch_id', $this->selectedBranchId);
        } elseif (!auth()->user()->isSuperAdmin()) {
            $moduleExpQuery->where('expenses.branch_id', auth()->user()->branch_id);
        }
        $totalModuleExpenses = (float) $moduleExpQuery->sum('amount');

        // Payroll
        $payrollExpQuery = \App\Models\PayrollDetail::join('payrolls', 'payroll_details.payroll_id', '=', 'payrolls.id')
            ->where('payrolls.status', 'pagada')
            ->whereDate('payrolls.payment_date', '>=', $start)
            ->whereDate('payrolls.payment_date', '<=', $end);
        if ($this->selectedBranchId) {
            $payrollExpQuery->where('payrolls.branch_id', $this->selectedBranchId);
        } elseif (!auth()->user()->isSuperAdmin()) {
            $payrollExpQuery->where('payrolls.branch_id', auth()->user()->branch_id);
        }
        $totalPayrollExpenses = (float) $payrollExpQuery->sum('payroll_details.net_pay');

        $totalExpenses = $totalCashExpenses + $totalModuleExpenses;
        $grossProfit = $realRevenue + $totalCashIncome - $realCost;
        $totalIncome = $realRevenue + $totalCashIncome;
        $grossMargin = $totalIncome > 0 ? ($grossProfit / $totalIncome) * 100 : 0;
        $netProfit = $grossProfit - $totalExpenses - $totalPayrollExpenses;
        $netMargin = $totalIncome > 0 ? ($netProfit / $totalIncome) * 100 : 0;

        // Categories
        $catData = SaleItem::join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->leftJoin('products', 'sale_items.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->where('sales.status', 'completed')
            ->whereDate('sales.created_at', '>=', $start)
            ->whereDate('sales.created_at', '<=', $end);
        $this->applyBranchFilter($catData);
        $this->applySupervisorSalesFilter($catData);

        $categories = (clone $catData)
            ->select(
                DB::raw("COALESCE(categories.name, 'Sin categoría') as name"),
                DB::raw('SUM(sale_items.subtotal) as revenue'),
                DB::raw('SUM(sale_items.quantity * sale_items.unit_cost) as cost')
            )
            ->groupBy('categories.name')
            ->orderByDesc('revenue')
            ->get()
            ->map(fn($c) => [
                'name' => $c->name,
                'revenue' => round((float) $c->revenue, 2),
                'cost' => round((float) ($c->cost ?? 0), 2),
                'profit' => round((float) $c->revenue - (float) ($c->cost ?? 0), 2),
            ])
            ->keyBy('name')
            ->toArray();

        // Payment Methods
        $pmData = SalePayment::join('sales', 'sale_payments.sale_id', '=', 'sales.id')
            ->join('payment_methods', 'sale_payments.payment_method_id', '=', 'payment_methods.id')
            ->where('sales.status', 'completed')
            ->whereDate('sales.created_at', '>=', $start)
            ->whereDate('sales.created_at', '<=', $end);
        $this->applyBranchFilter($pmData);
        $this->applySupervisorSalesFilter($pmData);

        $paymentMethods = (clone $pmData)
            ->select('payment_methods.name', DB::raw('SUM(sale_payments.amount) as total'), DB::raw('COUNT(DISTINCT sales.id) as count'))
            ->groupBy('payment_methods.id', 'payment_methods.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn($p) => ['name' => $p->name, 'total' => round((float) $p->total, 2), 'count' => $p->count])
            ->keyBy('name')
            ->toArray();

        // Products
        $pData = SaleItem::join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->where('sales.status', 'completed')
            ->whereDate('sales.created_at', '>=', $start)
            ->whereDate('sales.created_at', '<=', $end);
        $this->applyBranchFilter($pData);
        $this->applySupervisorSalesFilter($pData);

        $products = (clone $pData)
            ->select(
                'products.id',
                'products.name',
                'products.sku',
                DB::raw('SUM(sale_items.quantity) as qty'),
                DB::raw('SUM(sale_items.subtotal) as revenue'),
                DB::raw('SUM(sale_items.quantity * sale_items.unit_cost) as cost')
            )
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'qty' => (float) $p->qty,
                'revenue' => round((float) $p->revenue, 2),
                'cost' => round((float) $p->cost, 2),
                'profit' => round((float) $p->revenue - (float) $p->cost, 2),
                'margin' => $p->revenue > 0 ? round(((($p->revenue - $p->cost) / $p->revenue) * 100), 1) : 0,
            ])
            ->keyBy('id')
            ->toArray();

        return [
            'rawRevenue' => $rawRevenue,
            'totalRevenue' => $realRevenue,
            'totalCost' => $realCost,
            'totalTax' => $tax,
            'totalDiscount' => $discount,
            'totalTransactions' => $transactions,
            'totalRefunds' => $totalRefunds,
            'totalRefundsCost' => $totalRefundsCost,
            'totalRefundsCount' => $totalRefundsCount,
            'totalCashIncome' => $totalCashIncome,
            'totalCashExpenses' => $totalCashExpenses,
            'totalModuleExpenses' => $totalModuleExpenses,
            'totalPayrollExpenses' => $totalPayrollExpenses,
            'totalExpenses' => $totalExpenses,
            'grossProfit' => $grossProfit,
            'grossMargin' => $grossMargin,
            'netProfit' => $netProfit,
            'netMargin' => $netMargin,
            'totalPurchases' => $totalPurchases,
            'dailySales' => $dailySales,
            'dailyCost' => $dailyCost,
            'categories' => $categories,
            'paymentMethods' => $paymentMethods,
            'products' => $products,
        ];
    }

    private function calculateSummary()
    {
        $metrics = $this->computePeriodMetrics($this->startDate, $this->endDate);

        $this->rawRevenue = $metrics['rawRevenue'];
        $this->totalRevenue = $metrics['totalRevenue'];
        $this->totalCost = $metrics['totalCost'];
        $this->totalTax = $metrics['totalTax'];
        $this->totalDiscount = $metrics['totalDiscount'];
        $this->totalTransactions = $metrics['totalTransactions'];
        $this->totalRefunds = $metrics['totalRefunds'];
        $this->totalRefundsCost = $metrics['totalRefundsCost'];
        $this->totalRefundsCount = $metrics['totalRefundsCount'];
        $this->totalCashIncome = $metrics['totalCashIncome'];
        $this->totalCashExpenses = $metrics['totalCashExpenses'];
        $this->totalModuleExpenses = $metrics['totalModuleExpenses'];
        $this->totalPayrollExpenses = $metrics['totalPayrollExpenses'];
        $this->totalExpenses = $metrics['totalExpenses'];
        $this->grossProfit = $metrics['grossProfit'];
        $this->grossMargin = $metrics['grossMargin'];
        $this->netProfit = $metrics['netProfit'];
        $this->netMargin = $metrics['netMargin'];
        $this->totalPurchases = $metrics['totalPurchases'];
    }

    private function loadChartData()
    {
        // Profit by day
        $salesByDay = Sale::where('sales.status', 'completed')
            ->whereDate('sales.created_at', '>=', $this->startDate)
            ->whereDate('sales.created_at', '<=', $this->endDate);
        $this->applyBranchFilter($salesByDay);
        $this->applySupervisorSalesFilter($salesByDay);

        $dailySales = (clone $salesByDay)
            ->select(DB::raw("DATE(sales.created_at) as sale_date"), DB::raw('SUM(sales.total) as revenue'))
            ->groupBy('sale_date')
            ->orderBy('sale_date')
            ->get()
            ->keyBy('sale_date');

        $dailyCost = [];
        $salesWithItems = (clone $salesByDay)->with('items.product')->get();
        foreach ($salesWithItems as $sale) {
            $date = $sale->created_at->format('Y-m-d');
            if (!isset($dailyCost[$date])) $dailyCost[$date] = 0;
            foreach ($sale->items as $item) {
                if ($item->product) {
                    $dailyCost[$date] += $item->unit_cost * (float) $item->quantity;
                }
            }
        }

        $this->profitByDay = [];
        foreach ($dailySales as $date => $val) {
            $rev = (float) $val->revenue;
            $cst = $dailyCost[$date] ?? 0;
            $this->profitByDay[] = [
                'label' => Carbon::parse($date)->format('d M'),
                'revenue' => round($rev, 2),
                'cost' => round($cst, 2),
                'profit' => round($rev - $cst, 2),
            ];
        }

        // Categories
        $metrics = $this->computePeriodMetrics($this->startDate, $this->endDate);
        $this->revenueByCategory = array_values($metrics['categories']);

        // Payment Methods
        $this->revenueByPaymentMethod = array_values($metrics['paymentMethods']);

        // Products
        $allProducts = collect(array_values($metrics['products']));
        $this->topProfitableProducts = $allProducts->sortByDesc('profit')->take(10)->values()->toArray();
        $this->topLossProducts = $allProducts->filter(fn($p) => $p['profit'] < 0)->sortBy('profit')->take(10)->values()->toArray();

        // Expense breakdown
        $cashExpenses = CashMovement::where('cash_movements.type', 'expense')
            ->whereDate('cash_movements.created_at', '>=', $this->startDate)
            ->whereDate('cash_movements.created_at', '<=', $this->endDate)
            ->where(function ($q) {
                $q->where('cash_movements.concept', 'not like', 'Devolución %')
                  ->where('cash_movements.concept', 'not like', 'Nota Crédito %');
            });
        if ($this->selectedBranchId) {
            $cashExpenses->whereHas('reconciliation', fn($q) => $q->where('branch_id', $this->selectedBranchId));
        } elseif (!auth()->user()->isSuperAdmin()) {
            $cashExpenses->whereHas('reconciliation', fn($q) => $q->where('branch_id', auth()->user()->branch_id));
        }
        $cashExpenseData = $cashExpenses
            ->select('concept', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('concept')
            ->get()
            ->map(fn($e) => ['concept' => $e->concept . ' (Caja)', 'total' => round($e->total, 2), 'count' => $e->count]);

        $moduleExpenses = Expense::whereDate('expenses.expense_date', '>=', $this->startDate)
            ->whereDate('expenses.expense_date', '<=', $this->endDate);
        if ($this->selectedBranchId) {
            $moduleExpenses->where('expenses.branch_id', $this->selectedBranchId);
        } elseif (!auth()->user()->isSuperAdmin()) {
            $moduleExpenses->where('expenses.branch_id', auth()->user()->branch_id);
        }
        $moduleExpenseData = $moduleExpenses
            ->select('description as concept', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('description')
            ->get()
            ->map(fn($e) => ['concept' => $e->concept, 'total' => round($e->total, 2), 'count' => $e->count]);

        $this->expenseBreakdown = $cashExpenseData->concat($moduleExpenseData)->sortByDesc('total')->take(10)->values()->toArray();
    }

    private function calculateVersusData()
    {
        $dataA = $this->computePeriodMetrics($this->startDateA, $this->endDateA);
        $dataB = $this->computePeriodMetrics($this->startDateB, $this->endDateB);

        $this->versusSummary = [
            'A' => $dataA,
            'B' => $dataB,
        ];

        // Build Daily Comparison array for Day 1..31
        $this->versusDaily = [];
        for ($d = 1; $d <= 31; $d++) {
            $revA = $dataA['dailySales'][$d] ?? 0;
            $costA = $dataA['dailyCost'][$d] ?? 0;
            $profA = $revA - $costA;

            $revB = $dataB['dailySales'][$d] ?? 0;
            $costB = $dataB['dailyCost'][$d] ?? 0;
            $profB = $revB - $costB;

            $this->versusDaily[] = [
                'day' => $d,
                'label' => "Día {$d}",
                'revenueA' => round($revA, 0),
                'profitA' => round($profA, 0),
                'revenueB' => round($revB, 0),
                'profitB' => round($profB, 0),
                'diffRevenue' => round($revB - $revA, 0),
                'diffProfit' => round($profB - $profA, 0),
            ];
        }

        // Combined Categories
        $allCatNames = array_unique(array_merge(array_keys($dataA['categories']), array_keys($dataB['categories'])));
        $catComparison = [];
        foreach ($allCatNames as $name) {
            $revA = $dataA['categories'][$name]['revenue'] ?? 0;
            $profA = $dataA['categories'][$name]['profit'] ?? 0;
            $revB = $dataB['categories'][$name]['revenue'] ?? 0;
            $profB = $dataB['categories'][$name]['profit'] ?? 0;
            $diff = $profB - $profA;
            $growth = $profA != 0 ? (($profB - $profA) / abs($profA)) * 100 : ($profB > 0 ? 100 : 0);

            $catComparison[] = [
                'name' => $name,
                'revenueA' => $revA,
                'profitA' => $profA,
                'revenueB' => $revB,
                'profitB' => $profB,
                'diffRevenue' => $revB - $revA,
                'diffProfit' => $diff,
                'growth' => round($growth, 1),
            ];
        }
        $this->versusCategories = collect($catComparison)->sortByDesc('revenueB')->values()->toArray();

        // Payment Methods
        $allPmNames = array_unique(array_merge(array_keys($dataA['paymentMethods']), array_keys($dataB['paymentMethods'])));
        $pmComparison = [];
        foreach ($allPmNames as $name) {
            $totA = $dataA['paymentMethods'][$name]['total'] ?? 0;
            $totB = $dataB['paymentMethods'][$name]['total'] ?? 0;
            $pmComparison[] = [
                'name' => $name,
                'totalA' => $totA,
                'totalB' => $totB,
                'diff' => $totB - $totA,
                'growth' => $totA != 0 ? round((($totB - $totA) / $totA) * 100, 1) : 100,
            ];
        }
        $this->versusPaymentMethods = collect($pmComparison)->sortByDesc('totalB')->values()->toArray();

        // Product Shifts (Top Growth and Top Drop)
        $allPIds = array_unique(array_merge(array_keys($dataA['products']), array_keys($dataB['products'])));
        $productShifts = [];
        foreach ($allPIds as $pId) {
            $pA = $dataA['products'][$pId] ?? null;
            $pB = $dataB['products'][$pId] ?? null;
            $name = $pB['name'] ?? ($pA['name'] ?? 'Producto');
            $sku = $pB['sku'] ?? ($pA['sku'] ?? '');
            $revA = $pA['revenue'] ?? 0;
            $revB = $pB['revenue'] ?? 0;
            $profA = $pA['profit'] ?? 0;
            $profB = $pB['profit'] ?? 0;
            $diffProfit = $profB - $profA;
            $growth = $profA != 0 ? (($profB - $profA) / abs($profA)) * 100 : ($profB > 0 ? 100 : 0);

            $productShifts[] = [
                'name' => $name,
                'sku' => $sku,
                'revenueA' => $revA,
                'revenueB' => $revB,
                'profitA' => $profA,
                'profitB' => $profB,
                'diffProfit' => $diffProfit,
                'growth' => round($growth, 1),
            ];
        }
        $shiftsCollect = collect($productShifts);
        $this->versusTopGrowthProducts = $shiftsCollect->sortByDesc('diffProfit')->take(8)->values()->toArray();
        $this->versusTopDropProducts = $shiftsCollect->sortBy('diffProfit')->take(8)->values()->toArray();
    }

    public function exportExcel()
    {
        if (!auth()->user()->hasPermission('reports.export')) {
            $this->dispatch('notify', message: 'No tienes permiso para exportar', type: 'error');
            return;
        }

        if ($this->viewMode === 'versus') {
            return redirect()->route('reports.profit-loss.excel-versus', [
                'start_date_a' => $this->startDateA,
                'end_date_a' => $this->endDateA,
                'start_date_b' => $this->startDateB,
                'end_date_b' => $this->endDateB,
                'label_a' => $this->labelA,
                'label_b' => $this->labelB,
                'branch_id' => $this->selectedBranchId,
            ]);
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
        $this->versusPreset = 'mom';
        $this->initVersusPeriods();
        if (auth()->user()->isSuperAdmin()) {
            $this->selectedBranchId = null;
        }
    }

    public function render()
    {
        if ($this->viewMode === 'versus') {
            $this->calculateVersusData();
            $this->dispatch('pyg-versus-charts-ready', [
                'daily' => $this->versusDaily,
                'categories' => array_slice($this->versusCategories, 0, 7),
                'paymentMethods' => $this->versusPaymentMethods,
                'labelA' => ucfirst($this->labelA),
                'labelB' => ucfirst($this->labelB),
            ]);
        } else {
            $this->calculateSummary();
            $this->loadChartData();
        }

        $branches = auth()->user()->isSuperAdmin()
            ? Branch::where('is_active', true)->orderBy('name')->get()
            : collect();

        return view('livewire.reports.profit-loss', [
            'branches' => $branches,
            'isSuperAdmin' => auth()->user()->isSuperAdmin(),
        ]);
    }
}

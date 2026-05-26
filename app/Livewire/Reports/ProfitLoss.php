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
    public float $totalExpenses = 0;            // operating expenses (cash + module), WITHOUT payroll
    public float $totalCashExpenses = 0;
    public float $totalModuleExpenses = 0;
    public float $totalPayrollExpenses = 0;     // shown separately on P&L
    public float $totalCashIncome = 0;
    public float $netProfit = 0;
    public float $netMargin = 0;
    public int $totalTransactions = 0;
    public float $totalTax = 0;
    public float $totalDiscount = 0;
    public float $totalPurchases = 0;

    // Gross revenue before subtracting returns (used for P&L display consistency)
    public float $rawRevenue = 0;

    // Returns (refunds + credit notes) — partial returns specifically were
    // missing from previous reports. Total refunds change sale.status, but
    // partial ones don't, so we must read the refunds/credit_notes tables directly.
    public float $totalRefunds = 0;        // money returned (refund total + credit_note total)
    public float $totalRefundsCost = 0;    // cost of products returned (to reduce COGS)
    public int $totalRefundsCount = 0;

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

        // ====================================================================
        // RETURNS (Refunds + Credit Notes)
        // Refunds (POS) and credit notes (electronic invoices) — both reduce
        // the actual revenue and the cost of goods sold for the period. Total
        // refunds also flip sale.status to 'refunded'/'cancelled', so to avoid
        // double-counting we only consider sales that are still 'completed'
        // for refund/credit_note totals tied to those sales (i.e. partial
        // returns where the sale is still active).
        //
        // We measure them by THEIR OWN created_at, so a return processed in
        // the current period is reflected in the current period's P&L,
        // regardless of when the original sale was made.
        // ====================================================================

        // Refunds (status=completed)
        $refundsQuery = Refund::query()
            ->where('refunds.status', 'completed')
            ->whereDate('refunds.created_at', '>=', $this->startDate)
            ->whereDate('refunds.created_at', '<=', $this->endDate)
            // Only include refunds whose parent sale is still 'completed' to avoid
            // double-counting. When a sale is fully refunded its status becomes
            // 'refunded' and it's already excluded from $totalRevenue above.
            ->whereHas('sale', fn($q) => $q->where('sales.status', 'completed'));

        if ($this->selectedBranchId) {
            $refundsQuery->where('refunds.branch_id', $this->selectedBranchId);
        } elseif (!auth()->user()->isSuperAdmin()) {
            $refundsQuery->where('refunds.branch_id', auth()->user()->branch_id);
        }

        $refundsAggregate = (clone $refundsQuery)->selectRaw('
            COUNT(*) as count,
            COALESCE(SUM(refunds.total), 0) as total
        ')->first();

        $totalRefundAmount = (float) ($refundsAggregate->total ?? 0);
        $totalRefundCount = (int) ($refundsAggregate->count ?? 0);

        // Refund cost (cost of products returned via POS refunds)
        $refundCost = 0;
        $refundIds = (clone $refundsQuery)->pluck('refunds.id');
        if ($refundIds->isNotEmpty()) {
            $refundCost = (float) RefundItem::join('products', 'refund_items.product_id', '=', 'products.id')
                ->whereIn('refund_items.refund_id', $refundIds)
                ->sum(DB::raw('refund_items.quantity * products.purchase_price'));
        }

        // Credit notes (status pending/validated) tied to sales still in 'completed'
        $creditNotesQuery = CreditNote::query()
            ->whereIn('credit_notes.status', ['pending', 'validated'])
            ->whereDate('credit_notes.created_at', '>=', $this->startDate)
            ->whereDate('credit_notes.created_at', '<=', $this->endDate)
            ->whereHas('sale', fn($q) => $q->where('sales.status', 'completed'));

        if ($this->selectedBranchId) {
            $creditNotesQuery->where('credit_notes.branch_id', $this->selectedBranchId);
        } elseif (!auth()->user()->isSuperAdmin()) {
            $creditNotesQuery->where('credit_notes.branch_id', auth()->user()->branch_id);
        }

        $creditNotesAggregate = (clone $creditNotesQuery)->selectRaw('
            COUNT(*) as count,
            COALESCE(SUM(credit_notes.total), 0) as total
        ')->first();

        $totalCreditNoteAmount = (float) ($creditNotesAggregate->total ?? 0);
        $totalCreditNoteCount = (int) ($creditNotesAggregate->count ?? 0);

        $creditNoteCost = 0;
        $creditNoteIds = (clone $creditNotesQuery)->pluck('credit_notes.id');
        if ($creditNoteIds->isNotEmpty()) {
            $creditNoteCost = (float) CreditNoteItem::join('products', 'credit_note_items.product_id', '=', 'products.id')
                ->whereIn('credit_note_items.credit_note_id', $creditNoteIds)
                ->sum(DB::raw('credit_note_items.quantity * products.purchase_price'));
        }

        $this->totalRefunds = round($totalRefundAmount + $totalCreditNoteAmount, 2);
        $this->totalRefundsCost = round($refundCost + $creditNoteCost, 2);
        $this->totalRefundsCount = $totalRefundCount + $totalCreditNoteCount;

        // Save gross revenue BEFORE subtracting returns (used for P&L display)
        $this->rawRevenue = $this->totalRevenue;

        // Adjust revenue and cost for returns
        $this->totalRevenue = max(0, $this->totalRevenue - $this->totalRefunds);
        $this->totalCost = max(0, $this->totalCost - $this->totalRefundsCost);

        // Purchases total
        $purchasesQuery = Purchase::whereDate('purchases.created_at', '>=', $this->startDate)
            ->whereDate('purchases.created_at', '<=', $this->endDate);
        $this->applyBranchFilter($purchasesQuery, 'purchases');
        $this->totalPurchases = (float) $purchasesQuery->sum('total');

        // Cash incomes (ingresos from cash movements)
        $cashIncomeQuery = CashMovement::where('cash_movements.type', 'income')
            ->whereDate('cash_movements.created_at', '>=', $this->startDate)
            ->whereDate('cash_movements.created_at', '<=', $this->endDate);
        if ($this->selectedBranchId) {
            $cashIncomeQuery->whereHas('reconciliation', fn($q) => $q->where('branch_id', $this->selectedBranchId));
        } elseif (!auth()->user()->isSuperAdmin()) {
            $cashIncomeQuery->whereHas('reconciliation', fn($q) => $q->where('branch_id', auth()->user()->branch_id));
        }
        $this->totalCashIncome = (float) $cashIncomeQuery->sum('amount');

        // Cash expenses (egresos from cash movements)
        // IMPORTANT: We exclude cash movements created automatically by refunds
        // and credit notes (concept starting with "Devolución " or "Nota Crédito ").
        // Those amounts are already subtracted from revenue via $totalRefunds, so
        // counting them again as operating expenses would double-count.
        $expensesQuery = CashMovement::where('cash_movements.type', 'expense')
            ->whereDate('cash_movements.created_at', '>=', $this->startDate)
            ->whereDate('cash_movements.created_at', '<=', $this->endDate)
            ->where(function ($q) {
                $q->where('cash_movements.concept', 'not like', 'Devolución %')
                  ->where('cash_movements.concept', 'not like', 'Nota Crédito %');
            });
        if ($this->selectedBranchId) {
            $expensesQuery->whereHas('reconciliation', fn($q) => $q->where('branch_id', $this->selectedBranchId));
        } elseif (!auth()->user()->isSuperAdmin()) {
            $expensesQuery->whereHas('reconciliation', fn($q) => $q->where('branch_id', auth()->user()->branch_id));
        }
        $this->totalCashExpenses = (float) $expensesQuery->sum('amount');

        // Module expenses (from expenses table)
        $moduleExpensesQuery = Expense::whereDate('expenses.created_at', '>=', $this->startDate)
            ->whereDate('expenses.created_at', '<=', $this->endDate);
        if ($this->selectedBranchId) {
            $moduleExpensesQuery->where('expenses.branch_id', $this->selectedBranchId);
        } elseif (!auth()->user()->isSuperAdmin()) {
            $moduleExpensesQuery->where('expenses.branch_id', auth()->user()->branch_id);
        }
        $this->totalModuleExpenses = (float) $moduleExpensesQuery->sum('amount');

        // Payroll expenses (paid payrolls in period) - direct join for efficiency
        $payrollExpQuery = \App\Models\PayrollDetail::join('payrolls', 'payroll_details.payroll_id', '=', 'payrolls.id')
            ->where('payrolls.status', 'pagada')
            ->whereDate('payrolls.payment_date', '>=', $this->startDate)
            ->whereDate('payrolls.payment_date', '<=', $this->endDate);
        if ($this->selectedBranchId) {
            $payrollExpQuery->where('payrolls.branch_id', $this->selectedBranchId);
        } elseif (!auth()->user()->isSuperAdmin()) {
            $payrollExpQuery->where('payrolls.branch_id', auth()->user()->branch_id);
        }
        $this->totalPayrollExpenses = (float) $payrollExpQuery->sum('payroll_details.net_pay');

        // Total operating expenses = cash egresos + module expenses (WITHOUT payroll).
        // Payroll is tracked separately ($totalPayrollExpenses) and subtracted on its own
        // line in the P&L statement so the user can see it apart from operating expenses.
        $this->totalExpenses = $this->totalCashExpenses + $this->totalModuleExpenses;

        // Gross profit = Revenue + Cash Income - Cost of goods sold
        $this->grossProfit = $this->totalRevenue + $this->totalCashIncome - $this->totalCost;
        $totalIncome = $this->totalRevenue + $this->totalCashIncome;
        $this->grossMargin = $totalIncome > 0 ? ($this->grossProfit / $totalIncome) * 100 : 0;

        // Net profit = Gross profit - Operating expenses - Payroll
        $this->netProfit = $this->grossProfit - $this->totalExpenses - $this->totalPayrollExpenses;
        $this->netMargin = $totalIncome > 0 ? ($this->netProfit / $totalIncome) * 100 : 0;
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

        // Daily refunds (subtract from revenue and cost on the day they were processed)
        $dailyRefundRevenue = [];
        $dailyRefundCost = [];

        $refundsForChart = Refund::query()
            ->where('refunds.status', 'completed')
            ->whereDate('refunds.created_at', '>=', $this->startDate)
            ->whereDate('refunds.created_at', '<=', $this->endDate)
            ->whereHas('sale', fn($q) => $q->where('sales.status', 'completed'));
        if ($this->selectedBranchId) {
            $refundsForChart->where('refunds.branch_id', $this->selectedBranchId);
        } elseif (!auth()->user()->isSuperAdmin()) {
            $refundsForChart->where('refunds.branch_id', auth()->user()->branch_id);
        }
        foreach ($refundsForChart->with('items.product')->get() as $refund) {
            $date = $refund->created_at->format('Y-m-d');
            $dailyRefundRevenue[$date] = ($dailyRefundRevenue[$date] ?? 0) + (float) $refund->total;
            foreach ($refund->items as $item) {
                if ($item->product) {
                    $dailyRefundCost[$date] = ($dailyRefundCost[$date] ?? 0)
                        + (float) $item->quantity * (float) $item->product->purchase_price;
                }
            }
        }

        $creditNotesForChart = CreditNote::query()
            ->whereIn('credit_notes.status', ['pending', 'validated'])
            ->whereDate('credit_notes.created_at', '>=', $this->startDate)
            ->whereDate('credit_notes.created_at', '<=', $this->endDate)
            ->whereHas('sale', fn($q) => $q->where('sales.status', 'completed'));
        if ($this->selectedBranchId) {
            $creditNotesForChart->where('credit_notes.branch_id', $this->selectedBranchId);
        } elseif (!auth()->user()->isSuperAdmin()) {
            $creditNotesForChart->where('credit_notes.branch_id', auth()->user()->branch_id);
        }
        foreach ($creditNotesForChart->with('items.product')->get() as $cn) {
            $date = $cn->created_at->format('Y-m-d');
            $dailyRefundRevenue[$date] = ($dailyRefundRevenue[$date] ?? 0) + (float) $cn->total;
            foreach ($cn->items as $item) {
                if ($item->product) {
                    $dailyRefundCost[$date] = ($dailyRefundCost[$date] ?? 0)
                        + (float) $item->quantity * (float) $item->product->purchase_price;
                }
            }
        }

        // Build profit by day, including days with returns even if there were no sales
        $allDays = collect($dailySales->keys()->all())
            ->merge(array_keys($dailyRefundRevenue))
            ->unique()
            ->sort()
            ->values();

        $this->profitByDay = [];
        foreach ($allDays as $date) {
            $revenue = isset($dailySales[$date]) ? (float) $dailySales[$date]->revenue : 0;
            $cost = $dailyCost[$date] ?? 0;
            $refRev = $dailyRefundRevenue[$date] ?? 0;
            $refCost = $dailyRefundCost[$date] ?? 0;

            $netRevenue = max(0, $revenue - $refRev);
            $netCost = max(0, $cost - $refCost);

            $this->profitByDay[] = [
                'label' => Carbon::parse($date)->format('d M'),
                'revenue' => round($netRevenue, 2),
                'cost' => round($netCost, 2),
                'profit' => round($netRevenue - $netCost, 2),
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

        // Expense breakdown (cash movements + module expenses).
        // Same exclusion as in calculateSummary: cash movements created by refunds
        // and credit notes are NOT operating expenses; they're already counted as
        // reductions in revenue.
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

        $moduleExpenses = Expense::whereDate('expenses.created_at', '>=', $this->startDate)
            ->whereDate('expenses.created_at', '<=', $this->endDate);
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

        $allExpenses = $cashExpenseData->concat($moduleExpenseData);

        // NOTE: Payroll is intentionally NOT included in the expense breakdown.
        // It is shown separately on its own KPI card and as a dedicated line on the
        // P&L statement, so adding it here would make it look duplicated.

        $this->expenseBreakdown = $allExpenses
            ->sortByDesc('total')
            ->take(10)
            ->values()
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

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

    // View Mode
    public string $viewMode = 'standard'; // 'standard' | 'versus'

    // Standard Filters
    public string $dateRange = 'month';
    public ?string $startDate = null;
    public ?string $endDate = null;
    public ?int $selectedBranchId = null;
    public ?int $selectedUserId = null; // Vendedor (seller_id)
    public ?int $selectedCashierId = null; // Cajero/Usuario (user_id)
    public ?int $selectedCustomerId = null;
    public ?int $selectedPaymentMethodId = null;
    public ?int $selectedCashRegisterId = null;
    public string $search = '';
    public string $statusFilter = 'all'; // all, completed, cancelled

    // Versus Filters
    public string $versusPreset = 'mom'; // 'mom', 'yoy', 'custom'
    public string $monthA = '';
    public string $monthB = '';
    public ?string $startDateA = null;
    public ?string $endDateA = null;
    public ?string $startDateB = null;
    public ?string $endDateB = null;
    public string $labelA = '';
    public string $labelB = '';

    // Standard Summary stats
    public float $totalSales = 0;
    public float $totalSubtotal = 0;
    public float $totalTax = 0;
    public float $totalDiscount = 0;
    public int $totalTransactions = 0;
    public float $averageTicket = 0;
    public float $totalProfit = 0;

    // Standard Chart data
    public array $salesByDay = [];
    public array $salesByPaymentMethod = [];
    public array $salesByUser = [];
    public array $salesByCashRegister = [];
    public array $salesByHour = [];

    // Versus Comparison Data
    public array $versusSummary = [];
    public array $versusDaily = [];
    public array $versusHourly = [];
    public array $versusSellers = [];
    public array $versusPaymentMethods = [];

    // Detail modal
    public ?int $selectedSaleId = null;
    public ?Sale $selectedSale = null;
    public bool $isDetailModalOpen = false;

    public function mount()
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');

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
        $this->resetPage();
    }

    private function getBaseQuery(?string $customStart = null, ?string $customEnd = null)
    {
        $start = $customStart ?? $this->startDate;
        $end = $customEnd ?? $this->endDate;

        $query = Sale::query();

        if ($start) {
            $query->whereDate('sales.created_at', '>=', $start);
        }
        if ($end) {
            $query->whereDate('sales.created_at', '<=', $end);
        }

        if ($this->selectedBranchId) {
            $query->where('sales.branch_id', $this->selectedBranchId);
        } elseif (!auth()->user()->isSuperAdmin()) {
            $query->where('sales.branch_id', auth()->user()->branch_id);
        }

        if ($this->selectedUserId) {
            $query->where('sales.seller_id', $this->selectedUserId);
        }

        if ($this->selectedCashierId) {
            $query->where('sales.user_id', $this->selectedCashierId);
        }

        if ($this->selectedCustomerId) {
            $query->where('sales.customer_id', $this->selectedCustomerId);
        }

        if ($this->selectedPaymentMethodId) {
            $query->whereHas('payments', function ($q) {
                $q->where('payment_method_id', $this->selectedPaymentMethodId);
            });
        }

        // Cash register filter
        $user = auth()->user();
        if ($user->isSupervisor()) {
            $supervisorRegisterIds = $user->getSupervisorCashRegisterIds();
            if (empty($supervisorRegisterIds)) {
                $query->whereRaw('0 = 1');
            } else {
                $filterIds = ($this->selectedCashRegisterId && in_array((int) $this->selectedCashRegisterId, $supervisorRegisterIds))
                    ? [(int) $this->selectedCashRegisterId]
                    : $supervisorRegisterIds;
                $query->whereHas('cashReconciliation', function ($q) use ($filterIds) {
                    $q->whereIn('cash_register_id', $filterIds);
                });
            }
        } elseif ($this->selectedCashRegisterId) {
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
                      $cq->where('first_name', 'like', "%{$this->search}%")
                        ->orWhere('last_name', 'like', "%{$this->search}%")
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

        $this->totalTransactions = (int) ($summary->total_transactions ?? 0);
        $this->totalSubtotal = (float) ($summary->total_subtotal ?? 0);
        $this->totalTax = (float) ($summary->tax ?? 0);
        $this->totalDiscount = (float) ($summary->total_discount ?? 0);
        $this->totalSales = (float) ($summary->total_sales ?? 0);
        $this->averageTicket = $this->totalTransactions > 0 
            ? $this->totalSales / $this->totalTransactions 
            : 0;

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
                    $cost = $item->unit_cost * (float) $item->quantity;
                    $revenue = (float) $item->subtotal;
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
                    'total' => round((float) $item->total, 2),
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
            ->map(fn($item) => (array) $item)
            ->toArray();

        // Sales by user (seller)
        $this->salesByUser = (clone $baseQuery)
            ->join('users', 'sales.seller_id', '=', 'users.id')
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
            ->map(fn($item) => (array) $item)
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
            ->map(fn($item) => (array) $item)
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
                    'total' => round((float) $item->total, 2),
                ];
            })
            ->toArray();
    }

    /**
     * Helper to compute full sales book metrics for an arbitrary period.
     */
    private function computePeriodSalesMetrics(string $start, string $end): array
    {
        $salesQuery = $this->getBaseQuery($start, $end)->where('sales.status', 'completed');

        $summary = (clone $salesQuery)->selectRaw('
            COUNT(*) as total_transactions,
            COALESCE(SUM(sales.subtotal), 0) as total_subtotal,
            COALESCE(SUM(sales.tax_total), 0) as total_tax,
            COALESCE(SUM(sales.discount), 0) as total_discount,
            COALESCE(SUM(sales.total), 0) as total_sales
        ')->first();

        $totalSales = (float) ($summary->total_sales ?? 0);
        $totalSubtotal = (float) ($summary->total_subtotal ?? 0);
        $totalTax = (float) ($summary->total_tax ?? 0);
        $totalDiscount = (float) ($summary->total_discount ?? 0);
        $totalTransactions = (int) ($summary->total_transactions ?? 0);
        $averageTicket = $totalTransactions > 0 ? $totalSales / $totalTransactions : 0;

        $totalProfit = 0;
        $dailySales = [];
        $hourlySales = [];
        $sellerSales = [];

        $sales = (clone $salesQuery)->with(['items.product', 'seller'])->get();
        foreach ($sales as $sale) {
            $saleProfit = 0;
            foreach ($sale->items as $item) {
                if ($item->product) {
                    $saleProfit += ((float) $item->subtotal - ((float) $item->unit_cost * (float) $item->quantity));
                }
            }
            $totalProfit += $saleProfit;

            $day = (int) $sale->created_at->format('j');
            if (!isset($dailySales[$day])) {
                $dailySales[$day] = ['count' => 0, 'total' => 0, 'profit' => 0];
            }
            $dailySales[$day]['count']++;
            $dailySales[$day]['total'] += (float) $sale->total;
            $dailySales[$day]['profit'] += $saleProfit;

            $hour = (int) $sale->created_at->format('G');
            if (!isset($hourlySales[$hour])) {
                $hourlySales[$hour] = ['count' => 0, 'total' => 0];
            }
            $hourlySales[$hour]['count']++;
            $hourlySales[$hour]['total'] += (float) $sale->total;

            $sellerName = $sale->seller?->name ?? 'Sin asignar';
            if (!isset($sellerSales[$sellerName])) {
                $sellerSales[$sellerName] = ['count' => 0, 'total' => 0];
            }
            $sellerSales[$sellerName]['count']++;
            $sellerSales[$sellerName]['total'] += (float) $sale->total;
        }

        // Payment Methods
        $pmData = DB::table('sale_payments')
            ->join('sales', 'sale_payments.sale_id', '=', 'sales.id')
            ->join('payment_methods', 'sale_payments.payment_method_id', '=', 'payment_methods.id')
            ->where('sales.status', 'completed')
            ->whereDate('sales.created_at', '>=', $start)
            ->whereDate('sales.created_at', '<=', $end)
            ->when($this->selectedBranchId, fn($q) => $q->where('sales.branch_id', $this->selectedBranchId))
            ->when(!auth()->user()->isSuperAdmin() && !$this->selectedBranchId, 
                fn($q) => $q->where('sales.branch_id', auth()->user()->branch_id))
            ->select(
                'payment_methods.name',
                DB::raw('COUNT(DISTINCT sales.id) as count'),
                DB::raw('SUM(sale_payments.amount) as total')
            )
            ->groupBy('payment_methods.id', 'payment_methods.name')
            ->get()
            ->keyBy('name')
            ->toArray();

        return [
            'totalSales' => $totalSales,
            'totalSubtotal' => $totalSubtotal,
            'totalTax' => $totalTax,
            'totalDiscount' => $totalDiscount,
            'totalTransactions' => $totalTransactions,
            'averageTicket' => $averageTicket,
            'totalProfit' => $totalProfit,
            'dailySales' => $dailySales,
            'hourlySales' => $hourlySales,
            'sellerSales' => $sellerSales,
            'paymentMethods' => (array) $pmData,
        ];
    }

    private function calculateVersusData()
    {
        $dataA = $this->computePeriodSalesMetrics($this->startDateA, $this->endDateA);
        $dataB = $this->computePeriodSalesMetrics($this->startDateB, $this->endDateB);

        $this->versusSummary = [
            'A' => $dataA,
            'B' => $dataB,
        ];

        // Daily Comparison array (Day 1..31)
        $this->versusDaily = [];
        for ($d = 1; $d <= 31; $d++) {
            $tA = $dataA['dailySales'][$d]['count'] ?? 0;
            $vA = $dataA['dailySales'][$d]['total'] ?? 0;
            $tB = $dataB['dailySales'][$d]['count'] ?? 0;
            $vB = $dataB['dailySales'][$d]['total'] ?? 0;

            $this->versusDaily[] = [
                'day' => $d,
                'label' => "Día {$d}",
                'countA' => $tA,
                'totalA' => round($vA, 0),
                'countB' => $tB,
                'totalB' => round($vB, 0),
                'diffTotal' => round($vB - $vA, 0),
            ];
        }

        // Hourly Comparison (Hour 0..23)
        $this->versusHourly = [];
        for ($h = 0; $h <= 23; $h++) {
            $hLabel = str_pad($h, 2, '0', STR_PAD_LEFT) . ':00';
            $tA = $dataA['hourlySales'][$h]['count'] ?? 0;
            $vA = $dataA['hourlySales'][$h]['total'] ?? 0;
            $tB = $dataB['hourlySales'][$h]['count'] ?? 0;
            $vB = $dataB['hourlySales'][$h]['total'] ?? 0;

            $this->versusHourly[] = [
                'hour' => $hLabel,
                'totalA' => round($vA, 0),
                'countA' => $tA,
                'totalB' => round($vB, 0),
                'countB' => $tB,
                'diff' => round($vB - $vA, 0),
            ];
        }

        // Sellers Comparison
        $allSellers = array_unique(array_merge(array_keys($dataA['sellerSales']), array_keys($dataB['sellerSales'])));
        $sellerComp = [];
        foreach ($allSellers as $name) {
            $totA = $dataA['sellerSales'][$name]['total'] ?? 0;
            $cntA = $dataA['sellerSales'][$name]['count'] ?? 0;
            $totB = $dataB['sellerSales'][$name]['total'] ?? 0;
            $cntB = $dataB['sellerSales'][$name]['count'] ?? 0;
            $diff = $totB - $totA;
            $growth = $totA != 0 ? (($totB - $totA) / $totA) * 100 : ($totB > 0 ? 100 : 0);

            $sellerComp[] = [
                'name' => $name,
                'countA' => $cntA,
                'totalA' => round($totA, 0),
                'countB' => $cntB,
                'totalB' => round($totB, 0),
                'diff' => round($diff, 0),
                'growth' => round($growth, 1),
            ];
        }
        $this->versusSellers = collect($sellerComp)->sortByDesc('totalB')->values()->toArray();

        // Payment Methods Comparison
        $allPms = array_unique(array_merge(array_keys($dataA['paymentMethods']), array_keys($dataB['paymentMethods'])));
        $pmComp = [];
        foreach ($allPms as $name) {
            $itemA = (array) ($dataA['paymentMethods'][$name] ?? null);
            $itemB = (array) ($dataB['paymentMethods'][$name] ?? null);
            $totA = (float) ($itemA['total'] ?? 0);
            $cntA = (int) ($itemA['count'] ?? 0);
            $totB = (float) ($itemB['total'] ?? 0);
            $cntB = (int) ($itemB['count'] ?? 0);
            $diff = $totB - $totA;
            $growth = $totA != 0 ? (($totB - $totA) / $totA) * 100 : ($totB > 0 ? 100 : 0);

            $pmComp[] = [
                'name' => $name,
                'countA' => $cntA,
                'totalA' => round($totA, 0),
                'countB' => $cntB,
                'totalB' => round($totB, 0),
                'diff' => round($diff, 0),
                'growth' => round($growth, 1),
            ];
        }
        $this->versusPaymentMethods = collect($pmComp)->sortByDesc('totalB')->values()->toArray();
    }

    public function viewSaleDetail(int $saleId)
    {
        $this->selectedSaleId = $saleId;
        $this->selectedSale = Sale::with([
            'customer',
            'user',
            'seller',
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

    public function exportExcel()
    {
        if (!auth()->user()->hasPermission('reports.export')) {
            $this->dispatch('notify', message: 'No tienes permiso para exportar', type: 'error');
            return;
        }

        if ($this->viewMode === 'versus') {
            return redirect()->route('reports.sales-book.excel-versus', [
                'start_date_a' => $this->startDateA,
                'end_date_a' => $this->endDateA,
                'start_date_b' => $this->startDateB,
                'end_date_b' => $this->endDateB,
                'label_a' => $this->labelA,
                'label_b' => $this->labelB,
                'branch_id' => $this->selectedBranchId,
                'user_id' => $this->selectedUserId,
                'cashier_id' => $this->selectedCashierId,
                'payment_method_id' => $this->selectedPaymentMethodId,
                'cash_register_id' => $this->selectedCashRegisterId,
                'status' => $this->statusFilter,
                'search' => $this->search,
            ]);
        }

        return redirect()->route('reports.sales-book.excel', [
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'branch_id' => $this->selectedBranchId,
            'user_id' => $this->selectedUserId,
            'cashier_id' => $this->selectedCashierId,
            'payment_method_id' => $this->selectedPaymentMethodId,
            'cash_register_id' => $this->selectedCashRegisterId,
            'status' => $this->statusFilter,
            'search' => $this->search,
        ]);
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->dateRange = 'month';
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
        $this->selectedUserId = null;
        $this->selectedCashierId = null;
        $this->selectedCustomerId = null;
        $this->selectedPaymentMethodId = null;
        $this->selectedCashRegisterId = null;
        $this->statusFilter = 'all';
        $this->versusPreset = 'mom';
        $this->initVersusPeriods();
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

        if ($this->viewMode === 'versus') {
            $this->calculateVersusData();
            $this->dispatch('sales-versus-charts-ready', [
                'daily' => $this->versusDaily,
                'hourly' => $this->versusHourly,
                'sellers' => array_slice($this->versusSellers, 0, 7),
                'paymentMethods' => array_slice($this->versusPaymentMethods, 0, 7),
                'labelA' => ucfirst($this->labelA),
                'labelB' => ucfirst($this->labelB),
            ]);
            $sales = collect();
        } else {
            $this->calculateSummary();
            $this->loadChartData();
            $sales = $this->getBaseQuery()
                ->with(['customer', 'user', 'seller', 'branch', 'payments.paymentMethod'])
                ->orderByDesc('created_at')
                ->paginate(15);
        }

        $branches = $isSuperAdmin ? Branch::where('is_active', true)->orderBy('name')->get() : collect();
        $users = User::whereHas('roles')->orderBy('name')->get();
        $customers = Customer::orderBy('first_name')->limit(100)->get();
        $paymentMethods = PaymentMethod::where('is_active', true)->orderBy('name')->get();

        if ($user->isSupervisor()) {
            $cashRegisters = $user->cashRegisters()
                ->where('cash_registers.is_active', true)
                ->orderBy('cash_registers.name')
                ->get();
        } else {
            $cashRegistersQuery = CashRegister::where('is_active', true);
            if ($this->selectedBranchId) {
                $cashRegistersQuery->where('branch_id', $this->selectedBranchId);
            } elseif (!$isSuperAdmin) {
                $cashRegistersQuery->where('branch_id', $user->branch_id);
            }
            $cashRegisters = $cashRegistersQuery->orderBy('name')->get();
        }

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

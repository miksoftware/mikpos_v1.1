<?php

namespace App\Livewire\Reports;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Subcategory;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Carbon\Carbon;

#[Layout('layouts.app')]
class ProductsSold extends Component
{
    use WithPagination;

    // Filters
    public string $dateRange = 'month';
    public ?string $startDate = null;
    public ?string $endDate = null;
    public ?int $selectedBranchId = null;
    public ?int $selectedCashRegisterId = null;
    public ?int $selectedCategoryId = null;
    public ?int $selectedBrandId = null;
    public string $search = '';
    public string $sortBy = 'quantity';
    public string $sortDirection = 'desc';

    // Product lookup
    public string $productSearch = '';
    public array $productSearchResults = [];

    // View mode
    public string $viewMode = 'table'; // table, cards

    // Chart data
    public array $chartData = [];
    public array $topProducts = [];
    public array $salesByDay = [];
    public array $salesByCategory = [];
    public array $salesByBrand = [];
    public array $salesBySubcategory = [];
    public array $salesByPaymentMethod = [];
    public array $salesByHour = [];
    public array $salesByDayOfWeek = [];
    public array $revenueVsQuantity = [];

    // Summary
    public float $totalRevenue = 0;
    public int $totalQuantity = 0;
    public int $totalTransactions = 0;
    public float $averageTicket = 0;
    public float $averageUnitPrice = 0;
    public int $uniqueProducts = 0;

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
                // Keep current dates
                break;
        }
        $this->resetPage();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function sortByColumn($column)
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'desc';
        }
    }

    public function exportPdf()
    {
        $this->dispatch('notify', message: 'Generando PDF...', type: 'info');
        return redirect()->route('reports.products-sold.pdf', [
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'branch_id' => $this->selectedBranchId,
            'category_id' => $this->selectedCategoryId,
        ]);
    }

    public function exportExcel()
    {
        $this->dispatch('notify', message: 'Generando Excel...', type: 'info');
        return redirect()->route('reports.products-sold.excel', [
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'branch_id' => $this->selectedBranchId,
            'category_id' => $this->selectedCategoryId,
        ]);
    }

    private function getBaseQuery()
    {
        $query = SaleItem::query()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sales.status', 'completed')
            ->whereDate('sales.created_at', '>=', $this->startDate)
            ->whereDate('sales.created_at', '<=', $this->endDate);

        $user = auth()->user();
        if ($this->selectedBranchId) {
            $query->where('sales.branch_id', $this->selectedBranchId);
        } elseif (!$user->isSuperAdmin()) {
            $query->where('sales.branch_id', $user->branch_id);
        }

        if ($user->isSupervisor()) {
            $supervisorRegisterIds = $user->getSupervisorCashRegisterIds();
            if (empty($supervisorRegisterIds)) {
                $query->whereRaw('0 = 1');
            } else {
                $filterIds = ($this->selectedCashRegisterId && in_array((int) $this->selectedCashRegisterId, $supervisorRegisterIds))
                    ? [(int) $this->selectedCashRegisterId]
                    : $supervisorRegisterIds;
                $reconciliationIds = \App\Models\CashReconciliation::whereIn('cash_register_id', $filterIds)->pluck('id');
                $query->whereIn('sales.cash_reconciliation_id', $reconciliationIds);
            }
        } elseif ($this->selectedCashRegisterId) {
            $reconciliationIds = \App\Models\CashReconciliation::where('cash_register_id', $this->selectedCashRegisterId)->pluck('id');
            $query->whereIn('sales.cash_reconciliation_id', $reconciliationIds);
        }

        if ($this->selectedCategoryId) {
            $query->whereHas('product', function ($q) {
                $q->where('category_id', $this->selectedCategoryId);
            });
        }

        if ($this->selectedBrandId) {
            $query->whereHas('product', function ($q) {
                $q->where('brand_id', $this->selectedBrandId);
            });
        }

        return $query;
    }

    private function calculateSummary()
    {
        $query = $this->getBaseQuery();
        
        $this->totalQuantity = (float) (clone $query)->sum('sale_items.quantity');
        $this->totalRevenue = (float) (clone $query)->sum('sale_items.total');
        $this->uniqueProducts = (int) (clone $query)->distinct('sale_items.product_id')->count('sale_items.product_id');
        $this->averageUnitPrice = $this->totalQuantity > 0 
            ? $this->totalRevenue / $this->totalQuantity 
            : 0;
        
        $salesQuery = Sale::query()
            ->where('status', 'completed')
            ->whereDate('created_at', '>=', $this->startDate)
            ->whereDate('created_at', '<=', $this->endDate);
            
        $user = auth()->user();
        if ($this->selectedBranchId) {
            $salesQuery->where('branch_id', $this->selectedBranchId);
        } elseif (!$user->isSuperAdmin()) {
            $salesQuery->where('branch_id', $user->branch_id);
        }
        if ($user->isSupervisor()) {
            $supervisorRegisterIds = $user->getSupervisorCashRegisterIds();
            if (empty($supervisorRegisterIds)) {
                $salesQuery->whereRaw('0 = 1');
            } else {
                $filterIds = ($this->selectedCashRegisterId && in_array((int) $this->selectedCashRegisterId, $supervisorRegisterIds))
                    ? [(int) $this->selectedCashRegisterId]
                    : $supervisorRegisterIds;
                $reconciliationIds = \App\Models\CashReconciliation::whereIn('cash_register_id', $filterIds)->pluck('id');
                $salesQuery->whereIn('cash_reconciliation_id', $reconciliationIds);
            }
        } elseif ($this->selectedCashRegisterId) {
            $reconciliationIds = \App\Models\CashReconciliation::where('cash_register_id', $this->selectedCashRegisterId)->pluck('id');
            $salesQuery->whereIn('cash_reconciliation_id', $reconciliationIds);
        }

        $this->totalTransactions = $salesQuery->count();
        $this->averageTicket = $this->totalTransactions > 0 
            ? $this->totalRevenue / $this->totalTransactions 
            : 0;
    }

    private function getTopProducts()
    {
        return $this->getBaseQuery()
            ->select(
                'sale_items.product_name',
                'sale_items.product_sku',
                DB::raw('SUM(sale_items.quantity) as total_quantity'),
                DB::raw('SUM(sale_items.total) as total_revenue'),
                DB::raw('COUNT(DISTINCT sale_items.sale_id) as transactions')
            )
            ->groupBy('sale_items.product_name', 'sale_items.product_sku')
            ->orderByDesc('total_quantity')
            ->limit(10)
            ->get()
            ->toArray();
    }

    private function getSalesByDay()
    {
        $days = Carbon::parse($this->startDate)->diffInDays(Carbon::parse($this->endDate)) + 1;
        $groupBy = $days > 31 ? 'week' : 'day';
        
        if ($groupBy === 'week') {
            $format = '%Y-%u'; // Year-Week
        } else {
            $format = '%Y-%m-%d';
        }

        $query = $this->getBaseQuery()
            ->select(
                DB::raw("DATE_FORMAT(sales.created_at, '{$format}') as period"),
                DB::raw('SUM(sale_items.quantity) as quantity'),
                DB::raw('SUM(sale_items.total) as revenue')
            )
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        return $query->map(function ($item) use ($groupBy) {
            if ($groupBy === 'week') {
                $parts = explode('-', $item->period);
                $label = 'Sem ' . $parts[1];
            } else {
                $label = Carbon::parse($item->period)->format('d M');
            }
            return [
                'label' => $label,
                'quantity' => (float) $item->quantity,
                'revenue' => (float) $item->revenue,
            ];
        })->toArray();
    }

    private function getSalesByCategory()
    {
        return $this->getBaseQuery()
            ->leftJoin('products', 'sale_items.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->select(
                DB::raw("COALESCE(categories.name, 'Sin categoría') as category_name"),
                DB::raw('SUM(sale_items.quantity) as quantity'),
                DB::raw('SUM(sale_items.total) as revenue')
            )
            ->groupBy('categories.name')
            ->orderByDesc('revenue')
            ->limit(8)
            ->get()
            ->toArray();
    }

    private function getSalesByBrand()
    {
        return $this->getBaseQuery()
            ->leftJoin('products', 'sale_items.product_id', '=', 'products.id')
            ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
            ->select(
                DB::raw("COALESCE(brands.name, 'Sin marca') as brand_name"),
                DB::raw('SUM(sale_items.quantity) as quantity'),
                DB::raw('SUM(sale_items.total) as revenue')
            )
            ->groupBy('brands.name')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get()
            ->toArray();
    }

    private function getSalesBySubcategory()
    {
        return $this->getBaseQuery()
            ->leftJoin('products', 'sale_items.product_id', '=', 'products.id')
            ->leftJoin('subcategories', 'products.subcategory_id', '=', 'subcategories.id')
            ->select(
                DB::raw("COALESCE(subcategories.name, 'Sin subcategoría') as subcategory_name"),
                DB::raw('SUM(sale_items.quantity) as quantity'),
                DB::raw('SUM(sale_items.total) as revenue')
            )
            ->groupBy('subcategories.name')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get()
            ->toArray();
    }

    private function getSalesByPaymentMethod()
    {
        $salesQuery = Sale::query()
            ->join('sale_payments', 'sales.id', '=', 'sale_payments.sale_id')
            ->join('payment_methods', 'sale_payments.payment_method_id', '=', 'payment_methods.id')
            ->where('sales.status', 'completed')
            ->whereDate('sales.created_at', '>=', $this->startDate)
            ->whereDate('sales.created_at', '<=', $this->endDate);

        $user = auth()->user();
        if ($this->selectedBranchId) {
            $salesQuery->where('sales.branch_id', $this->selectedBranchId);
        } elseif (!$user->isSuperAdmin()) {
            $salesQuery->where('sales.branch_id', $user->branch_id);
        }

        if ($user->isSupervisor()) {
            $supervisorRegisterIds = $user->getSupervisorCashRegisterIds();
            if (empty($supervisorRegisterIds)) {
                $salesQuery->whereRaw('0 = 1');
            } else {
                $filterIds = ($this->selectedCashRegisterId && in_array((int) $this->selectedCashRegisterId, $supervisorRegisterIds))
                    ? [(int) $this->selectedCashRegisterId]
                    : $supervisorRegisterIds;
                $reconciliationIds = \App\Models\CashReconciliation::whereIn('cash_register_id', $filterIds)->pluck('id');
                $salesQuery->whereIn('sales.cash_reconciliation_id', $reconciliationIds);
            }
        } elseif ($this->selectedCashRegisterId) {
            $reconciliationIds = \App\Models\CashReconciliation::where('cash_register_id', $this->selectedCashRegisterId)->pluck('id');
            $salesQuery->whereIn('sales.cash_reconciliation_id', $reconciliationIds);
        }

        return $salesQuery
            ->select(
                'payment_methods.name as method_name',
                DB::raw('SUM(sale_payments.amount) as total_amount'),
                DB::raw('COUNT(DISTINCT sales.id) as transactions')
            )
            ->groupBy('payment_methods.name')
            ->orderByDesc('total_amount')
            ->get()
            ->toArray();
    }

    private function getSalesByHour()
    {
        return $this->getBaseQuery()
            ->select(
                DB::raw("HOUR(sales.created_at) as hour"),
                DB::raw('SUM(sale_items.quantity) as quantity'),
                DB::raw('SUM(sale_items.total) as revenue')
            )
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->map(function ($item) {
                return [
                    'hour' => sprintf('%02d:00', $item->hour),
                    'quantity' => (float) $item->quantity,
                    'revenue' => (float) $item->revenue,
                ];
            })
            ->toArray();
    }

    private function getSalesByDayOfWeek()
    {
        $dayNames = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
        
        return $this->getBaseQuery()
            ->select(
                DB::raw("DAYOFWEEK(sales.created_at) as day_num"),
                DB::raw('SUM(sale_items.quantity) as quantity'),
                DB::raw('SUM(sale_items.total) as revenue')
            )
            ->groupBy('day_num')
            ->orderBy('day_num')
            ->get()
            ->map(function ($item) use ($dayNames) {
                return [
                    'day' => $dayNames[$item->day_num - 1],
                    'day_num' => $item->day_num,
                    'quantity' => (float) $item->quantity,
                    'revenue' => (float) $item->revenue,
                ];
            })
            ->toArray();
    }

    private function getRevenueVsQuantity()
    {
        return $this->getBaseQuery()
            ->leftJoin('products', 'sale_items.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->select(
                DB::raw("COALESCE(categories.name, 'Sin categoría') as category_name"),
                DB::raw('SUM(sale_items.quantity) as quantity'),
                DB::raw('SUM(sale_items.total) as revenue'),
                DB::raw('AVG(sale_items.unit_price) as avg_price')
            )
            ->groupBy('categories.name')
            ->orderByDesc('revenue')
            ->limit(6)
            ->get()
            ->toArray();
    }

    private function getProductSearchResults(): array
    {
        if (empty($this->productSearch)) {
            return [];
        }

        return $this->getBaseQuery()
            ->where(function ($q) {
                $q->where('sale_items.product_name', 'like', "%{$this->productSearch}%")
                  ->orWhere('sale_items.product_sku', 'like', "%{$this->productSearch}%");
            })
            ->select(
                'sale_items.product_name',
                'sale_items.product_sku',
                DB::raw('SUM(sale_items.quantity) as total_quantity'),
                DB::raw('SUM(sale_items.total) as total_revenue'),
                DB::raw('COUNT(DISTINCT sale_items.sale_id) as transactions'),
                DB::raw('AVG(sale_items.unit_price) as avg_price')
            )
            ->groupBy('sale_items.product_name', 'sale_items.product_sku')
            ->orderByDesc('total_quantity')
            ->get()
            ->toArray();
    }

    public function render()
    {
        $user = auth()->user();
        $isSuperAdmin = $user->isSuperAdmin();

        // Calculate summary
        $this->calculateSummary();

        // Get chart data
        $this->topProducts = $this->getTopProducts();
        $this->salesByDay = $this->getSalesByDay();
        $this->salesByCategory = $this->getSalesByCategory();
        $this->salesByBrand = $this->getSalesByBrand();
        $this->salesBySubcategory = $this->getSalesBySubcategory();
        $this->salesByPaymentMethod = $this->getSalesByPaymentMethod();
        $this->salesByHour = $this->getSalesByHour();
        $this->salesByDayOfWeek = $this->getSalesByDayOfWeek();
        $this->revenueVsQuantity = $this->getRevenueVsQuantity();

        // Get detailed items with pagination
        $query = $this->getBaseQuery()
            ->select(
                'sale_items.*',
                'sales.invoice_number',
                'sales.created_at as sale_date',
                'sales.branch_id'
            )
            ->with(['sale.customer', 'sale.branch', 'product.category']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('sale_items.product_name', 'like', "%{$this->search}%")
                  ->orWhere('sale_items.product_sku', 'like', "%{$this->search}%")
                  ->orWhere('sales.invoice_number', 'like', "%{$this->search}%");
            });
        }

        // Sorting
        switch ($this->sortBy) {
            case 'quantity':
                $query->orderBy('sale_items.quantity', $this->sortDirection);
                break;
            case 'total':
                $query->orderBy('sale_items.total', $this->sortDirection);
                break;
            case 'date':
                $query->orderBy('sales.created_at', $this->sortDirection);
                break;
            case 'product':
                $query->orderBy('sale_items.product_name', $this->sortDirection);
                break;
            default:
                $query->orderBy('sales.created_at', 'desc');
        }

        $items = $query->paginate(15);

        // Get filter options
        $isSupervisor = $user->isSupervisor();
        $branches = $isSuperAdmin ? Branch::where('is_active', true)->orderBy('name')->get() : collect();
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $brands = Brand::where('is_active', true)->orderBy('name')->get();
        $cashRegisters = $isSupervisor
            ? $user->cashRegisters()->where('cash_registers.is_active', true)->orderBy('cash_registers.name')->get()
            : collect();

        // Product search results
        $productSearchResults = $this->getProductSearchResults();

        // Dispatch chart data to the browser after every render
        $this->dispatch('charts-ready',
            salesByDay: $this->salesByDay,
            topProducts: $this->topProducts,
            salesByCategory: $this->salesByCategory,
            salesByBrand: $this->salesByBrand,
            salesBySubcategory: $this->salesBySubcategory,
            salesByPaymentMethod: $this->salesByPaymentMethod,
            salesByHour: $this->salesByHour,
            salesByDayOfWeek: $this->salesByDayOfWeek,
        );

        return view('livewire.reports.products-sold', [
            'items' => $items,
            'branches' => $branches,
            'categories' => $categories,
            'brands' => $brands,
            'isSuperAdmin' => $isSuperAdmin,
            'isSupervisor' => $isSupervisor,
            'cashRegisters' => $cashRegisters,
            'productSearchResults' => $productSearchResults,
        ]);
    }
}

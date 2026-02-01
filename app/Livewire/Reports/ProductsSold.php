<?php

namespace App\Livewire\Reports;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Branch;
use App\Models\Category;
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
    public ?int $selectedCategoryId = null;
    public string $search = '';
    public string $sortBy = 'quantity';
    public string $sortDirection = 'desc';

    // View mode
    public string $viewMode = 'table'; // table, cards

    // Chart data
    public array $chartData = [];
    public array $topProducts = [];
    public array $salesByDay = [];
    public array $salesByCategory = [];

    // Summary
    public float $totalRevenue = 0;
    public int $totalQuantity = 0;
    public int $totalTransactions = 0;
    public float $averageTicket = 0;

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

        if ($this->selectedBranchId) {
            $query->where('sales.branch_id', $this->selectedBranchId);
        } elseif (!auth()->user()->isSuperAdmin()) {
            $query->where('sales.branch_id', auth()->user()->branch_id);
        }

        if ($this->selectedCategoryId) {
            $query->whereHas('product', function ($q) {
                $q->where('category_id', $this->selectedCategoryId);
            });
        }

        return $query;
    }

    private function calculateSummary()
    {
        $query = $this->getBaseQuery();
        
        $this->totalQuantity = (int) (clone $query)->sum('sale_items.quantity');
        $this->totalRevenue = (float) (clone $query)->sum('sale_items.total');
        
        $salesQuery = Sale::query()
            ->where('status', 'completed')
            ->whereDate('created_at', '>=', $this->startDate)
            ->whereDate('created_at', '<=', $this->endDate);
            
        if ($this->selectedBranchId) {
            $salesQuery->where('branch_id', $this->selectedBranchId);
        } elseif (!auth()->user()->isSuperAdmin()) {
            $salesQuery->where('branch_id', auth()->user()->branch_id);
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
            $labelFormat = 'Sem %u';
        } else {
            $format = '%Y-%m-%d';
            $labelFormat = 'd M';
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

        return $query->map(function ($item) use ($groupBy, $labelFormat) {
            if ($groupBy === 'week') {
                $parts = explode('-', $item->period);
                $label = 'Sem ' . $parts[1];
            } else {
                $label = Carbon::parse($item->period)->format('d M');
            }
            return [
                'label' => $label,
                'quantity' => (int) $item->quantity,
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
        $branches = $isSuperAdmin ? Branch::where('is_active', true)->orderBy('name')->get() : collect();
        $categories = Category::where('is_active', true)->orderBy('name')->get();

        return view('livewire.reports.products-sold', [
            'items' => $items,
            'branches' => $branches,
            'categories' => $categories,
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }
}

<?php

namespace App\Livewire\Reports;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Product;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Brand;
use App\Models\InventoryMovement;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class Kardex extends Component
{
    use WithPagination;

    // Filters
    public ?int $selectedBranchId = null;
    public ?int $selectedCategoryId = null;
    public ?int $selectedBrandId = null;
    public string $stockFilter = 'all'; // all, zero, positive, negative
    public string $search = '';
    public ?string $dateFrom = null;
    public ?string $dateTo = null;

    // Summary stats
    public int $totalProducts = 0;
    public int $productsWithStock = 0;
    public int $productsZeroStock = 0;
    public int $productsNegativeStock = 0;
    public float $totalInventoryValue = 0;
    public float $totalInventoryCost = 0;
    public float $totalPotentialProfit = 0;

    // Chart data
    public array $stockByCategory = [];
    public array $stockDistribution = [];
    public array $topValueProducts = [];
    public array $lowStockProducts = [];

    // Detail view
    public ?int $selectedProductId = null;
    public array $productMovements = [];
    public bool $isDetailModalOpen = false;

    public function mount()
    {
        $user = auth()->user();
        if (!$user->isSuperAdmin() && $user->branch_id) {
            $this->selectedBranchId = $user->branch_id;
        }
        
        // No default date filter - show all data from the beginning
        $this->dateFrom = null;
        $this->dateTo = null;
    }

    private function getBaseQuery()
    {
        $query = Product::query()->where('is_active', true);

        if ($this->selectedBranchId) {
            $query->where('branch_id', $this->selectedBranchId);
        } elseif (!auth()->user()->isSuperAdmin()) {
            $query->where('branch_id', auth()->user()->branch_id);
        }

        if ($this->selectedCategoryId) {
            $query->where('category_id', $this->selectedCategoryId);
        }

        if ($this->selectedBrandId) {
            $query->where('brand_id', $this->selectedBrandId);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('sku', 'like', "%{$this->search}%")
                  ->orWhere('barcode', 'like', "%{$this->search}%");
            });
        }

        // Stock filter
        switch ($this->stockFilter) {
            case 'zero':
                $query->where('current_stock', 0);
                break;
            case 'positive':
                $query->where('current_stock', '>', 0);
                break;
            case 'negative':
                $query->where('current_stock', '<', 0);
                break;
        }

        return $query;
    }

    private function calculateSummary()
    {
        $baseQuery = Product::query()->where('is_active', true);

        if ($this->selectedBranchId) {
            $baseQuery->where('branch_id', $this->selectedBranchId);
        } elseif (!auth()->user()->isSuperAdmin()) {
            $baseQuery->where('branch_id', auth()->user()->branch_id);
        }

        if ($this->selectedCategoryId) {
            $baseQuery->where('category_id', $this->selectedCategoryId);
        }

        if ($this->selectedBrandId) {
            $baseQuery->where('brand_id', $this->selectedBrandId);
        }

        $this->totalProducts = (clone $baseQuery)->count();
        $this->productsWithStock = (clone $baseQuery)->where('current_stock', '>', 0)->count();
        $this->productsZeroStock = (clone $baseQuery)->where('current_stock', 0)->count();
        $this->productsNegativeStock = (clone $baseQuery)->where('current_stock', '<', 0)->count();

        // Calculate inventory value (sale price * stock)
        $this->totalInventoryValue = (clone $baseQuery)
            ->where('current_stock', '>', 0)
            ->selectRaw('SUM(current_stock * sale_price) as total')
            ->value('total') ?? 0;

        // Calculate inventory cost (purchase price * stock)
        $this->totalInventoryCost = (clone $baseQuery)
            ->where('current_stock', '>', 0)
            ->selectRaw('SUM(current_stock * purchase_price) as total')
            ->value('total') ?? 0;

        // Calculate potential profit (value - cost)
        $this->totalPotentialProfit = $this->totalInventoryValue - $this->totalInventoryCost;
    }

    private function loadChartData()
    {
        $baseQuery = Product::query()->where('products.is_active', true);

        if ($this->selectedBranchId) {
            $baseQuery->where('products.branch_id', $this->selectedBranchId);
        } elseif (!auth()->user()->isSuperAdmin()) {
            $baseQuery->where('products.branch_id', auth()->user()->branch_id);
        }

        if ($this->selectedCategoryId) {
            $baseQuery->where('products.category_id', $this->selectedCategoryId);
        }

        if ($this->selectedBrandId) {
            $baseQuery->where('products.brand_id', $this->selectedBrandId);
        }

        // Stock by category
        $this->stockByCategory = (clone $baseQuery)
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->select(
                DB::raw("COALESCE(categories.name, 'Sin categoría') as category_name"),
                DB::raw('SUM(products.current_stock) as total_stock'),
                DB::raw('COUNT(products.id) as product_count')
            )
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total_stock')
            ->limit(8)
            ->get()
            ->toArray();

        // Stock distribution (pie chart data)
        $this->stockDistribution = [
            ['label' => 'Con existencias', 'value' => $this->productsWithStock, 'color' => '#22c55e'],
            ['label' => 'Sin existencias', 'value' => $this->productsZeroStock, 'color' => '#f59e0b'],
            ['label' => 'Stock negativo', 'value' => $this->productsNegativeStock, 'color' => '#ef4444'],
        ];

        // Top 10 products by inventory value
        $this->topValueProducts = (clone $baseQuery)
            ->where('products.current_stock', '>', 0)
            ->select(
                'products.id',
                'products.name',
                'products.sku',
                'products.current_stock',
                'products.sale_price',
                DB::raw('(products.current_stock * products.sale_price) as inventory_value')
            )
            ->orderByDesc('inventory_value')
            ->limit(10)
            ->get()
            ->toArray();

        // Low stock products (at or below min_stock)
        $this->lowStockProducts = (clone $baseQuery)
            ->whereColumn('products.current_stock', '<=', 'products.min_stock')
            ->where('products.min_stock', '>', 0)
            ->select(
                'products.id',
                'products.name',
                'products.sku',
                'products.current_stock',
                'products.min_stock'
            )
            ->orderBy('products.current_stock')
            ->limit(10)
            ->get()
            ->toArray();
    }

    public function viewProductKardex(int $productId)
    {
        $this->selectedProductId = $productId;
        $this->dateFrom = null;
        $this->dateTo = null;
        $this->loadProductMovements();
        $this->isDetailModalOpen = true;
    }

    public function closeDetailModal()
    {
        $this->isDetailModalOpen = false;
        $this->selectedProductId = null;
        $this->productMovements = [];
    }

    public function updatedStockFilter()
    {
        $this->resetPage();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedSelectedBranchId()
    {
        $this->resetPage();
    }

    public function updatedSelectedCategoryId()
    {
        $this->resetPage();
    }

    public function updatedSelectedBrandId()
    {
        $this->resetPage();
    }

    public function updatedDateFrom()
    {
        if ($this->selectedProductId) {
            $this->loadProductMovements();
        }
    }

    public function updatedDateTo()
    {
        if ($this->selectedProductId) {
            $this->loadProductMovements();
        }
    }

    private function loadProductMovements()
    {
        $query = InventoryMovement::where('product_id', $this->selectedProductId)
            ->with(['systemDocument', 'user', 'branch']);

        // Apply date filters
        if ($this->dateFrom) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        $this->productMovements = $query
            ->orderByDesc('created_at')
            ->limit(100)
            ->get()
            ->map(function ($movement) {
                // Resolve invoice number from reference
                $invoiceNumber = null;
                $receiptUrl = null;
                if ($movement->reference_type === 'App\\Models\\Sale' && $movement->reference_id) {
                    $sale = \App\Models\Sale::find($movement->reference_id);
                    if ($sale) {
                        $invoiceNumber = $sale->invoice_number;
                        $receiptUrl = route('receipt.show', $sale->id);
                    }
                } elseif ($movement->reference_type === 'App\\Models\\Refund' && $movement->reference_id) {
                    $refund = \App\Models\Refund::find($movement->reference_id);
                    if ($refund) {
                        $invoiceNumber = $refund->number;
                        $receiptUrl = route('refund-receipt.show', $refund->id);
                    }
                } elseif ($movement->reference_type === 'App\\Models\\Purchase' && $movement->reference_id) {
                    $purchase = \App\Models\Purchase::find($movement->reference_id);
                    if ($purchase) {
                        $invoiceNumber = $purchase->purchase_number ?? $movement->document_number;
                        $receiptUrl = route('purchase-receipt.show', $purchase->id);
                    }
                }

                return [
                    'id' => $movement->id,
                    'date' => $movement->created_at->format('d/m/Y H:i'),
                    'document' => $movement->systemDocument?->name ?? 'N/A',
                    'document_number' => $movement->document_number,
                    'invoice_number' => $invoiceNumber,
                    'receipt_url' => $receiptUrl,
                    'type' => $movement->movement_type,
                    'quantity' => $movement->quantity,
                    'stock_before' => $movement->stock_before,
                    'stock_after' => $movement->stock_after,
                    'unit_cost' => $movement->unit_cost,
                    'total_cost' => $movement->total_cost,
                    'user' => $movement->user?->name ?? 'Sistema',
                    'notes' => $movement->notes,
                ];
            })
            ->toArray();
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->stockFilter = 'all';
        $this->selectedCategoryId = null;
        $this->selectedBrandId = null;
        $this->dateFrom = null;
        $this->dateTo = null;
        if (auth()->user()->isSuperAdmin()) {
            $this->selectedBranchId = null;
        }
        $this->resetPage();
    }

    public function render()
    {
        $user = auth()->user();
        $isSuperAdmin = $user->isSuperAdmin();

        $this->calculateSummary();
        $this->loadChartData();

        $products = $this->getBaseQuery()
            ->with(['category', 'brand', 'unit'])
            ->orderBy('name')
            ->paginate(15);

        $branches = $isSuperAdmin ? Branch::where('is_active', true)->orderBy('name')->get() : collect();
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $brands = Brand::where('is_active', true)->orderBy('name')->get();

        // Get selected product for modal
        $selectedProduct = $this->selectedProductId 
            ? Product::with(['category', 'brand', 'unit'])->find($this->selectedProductId)
            : null;

        return view('livewire.reports.kardex', [
            'products' => $products,
            'branches' => $branches,
            'categories' => $categories,
            'brands' => $brands,
            'isSuperAdmin' => $isSuperAdmin,
            'selectedProduct' => $selectedProduct,
        ]);
    }
}

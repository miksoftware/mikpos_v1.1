<?php

namespace App\Livewire\Reports;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Branch;
use App\Models\User;
use App\Models\Product;
use App\Models\Service;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Carbon\Carbon;

#[Layout('layouts.app')]
class Commissions extends Component
{
    use WithPagination;

    // Filters
    public string $dateRange = 'month';
    public ?string $startDate = null;
    public ?string $endDate = null;
    public ?int $selectedBranchId = null;
    public ?int $selectedUserId = null;
    public ?int $selectedCategoryId = null;
    public ?int $selectedBrandId = null;
    public string $search = '';
    
    // Detail view
    public ?int $expandedUserId = null;
    public array $userSalesDetail = [];

    // Summary
    public float $totalCommissions = 0;
    public float $totalSales = 0;
    public int $totalTransactions = 0;
    public int $totalItemsSold = 0;
    public float $averageCommissionRate = 0;

    // Chart data
    public array $commissionsByUser = [];
    public array $commissionsByDay = [];
    public array $commissionsByProduct = [];
    public array $commissionsByCategory = [];
    public array $userRanking = [];

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
        $query = SaleItem::query()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->leftJoin('products', 'sale_items.product_id', '=', 'products.id')
            ->leftJoin('services', 'sale_items.service_id', '=', 'services.id')
            ->where('sales.status', 'completed')
            ->whereDate('sales.created_at', '>=', $this->startDate)
            ->whereDate('sales.created_at', '<=', $this->endDate)
            ->where(function ($q) {
                // Products with commission
                $q->where(function ($pq) {
                    $pq->where('products.has_commission', true)
                       ->whereNotNull('products.commission_value')
                       ->where('products.commission_value', '>', 0);
                })
                // OR services with commission
                ->orWhere(function ($sq) {
                    $sq->where('services.has_commission', true)
                       ->whereNotNull('services.commission_value')
                       ->where('services.commission_value', '>', 0);
                });
            });

        if ($this->selectedBranchId) {
            $query->where('sales.branch_id', $this->selectedBranchId);
        } elseif (!auth()->user()->isSuperAdmin()) {
            $query->where('sales.branch_id', auth()->user()->branch_id);
        }

        if ($this->selectedUserId) {
            $query->where('sales.user_id', $this->selectedUserId);
        }

        if ($this->selectedCategoryId) {
            $query->where(function ($q) {
                $q->where('products.category_id', $this->selectedCategoryId)
                  ->orWhere('services.category_id', $this->selectedCategoryId);
            });
        }

        if ($this->selectedBrandId) {
            $query->where('products.brand_id', $this->selectedBrandId);
        }

        return $query;
    }

    private function calculateCommission($item): float
    {
        $basePrice = (float) $item->unit_price;
        $quantity = (float) $item->quantity;

        // Check if it's a service
        if ($item->service_id ?? null) {
            $service = $item->service ?? null;
            if (!$service || !$service->has_commission) {
                return 0;
            }
            $commissionValue = (float) $service->commission_value;
            $commissionType = $service->commission_type;
        } else {
            // It's a product
            if (!$item->product || !$item->product->has_commission) {
                return 0;
            }
            $commissionValue = (float) $item->product->commission_value;
            $commissionType = $item->product->commission_type;
        }

        if ($commissionType === 'percentage') {
            return ($basePrice * ($commissionValue / 100)) * $quantity;
        }

        return $commissionValue * $quantity;
    }

    private function calculateSummary()
    {
        $items = $this->getBaseQuery()
            ->select(
                'sale_items.*',
                'products.has_commission as product_has_commission',
                'products.commission_type as product_commission_type',
                'products.commission_value as product_commission_value',
                'services.has_commission as service_has_commission',
                'services.commission_type as service_commission_type',
                'services.commission_value as service_commission_value'
            )
            ->with(['product', 'service'])
            ->get();

        $this->totalCommissions = 0;
        $this->totalSales = 0;
        $this->totalItemsSold = 0;

        foreach ($items as $item) {
            $this->totalCommissions += $this->calculateCommission($item);
            $this->totalSales += (float) $item->total;
            $this->totalItemsSold += (float) $item->quantity;
        }

        $salesQuery = Sale::query()
            ->where('status', 'completed')
            ->whereDate('created_at', '>=', $this->startDate)
            ->whereDate('created_at', '<=', $this->endDate);

        if ($this->selectedBranchId) {
            $salesQuery->where('branch_id', $this->selectedBranchId);
        } elseif (!auth()->user()->isSuperAdmin()) {
            $salesQuery->where('branch_id', auth()->user()->branch_id);
        }

        if ($this->selectedUserId) {
            $salesQuery->where('user_id', $this->selectedUserId);
        }

        $this->totalTransactions = $salesQuery->count();
        $this->averageCommissionRate = $this->totalSales > 0 
            ? ($this->totalCommissions / $this->totalSales) * 100 
            : 0;
    }

    private function getCommissionsByUser()
    {
        $items = $this->getBaseQuery()
            ->join('users', 'sales.user_id', '=', 'users.id')
            ->select(
                'users.id as user_id',
                'users.name as user_name',
                'sale_items.*'
            )
            ->with(['product', 'service'])
            ->get();

        $userCommissions = [];
        foreach ($items as $item) {
            $userId = $item->user_id;
            if (!isset($userCommissions[$userId])) {
                $userCommissions[$userId] = [
                    'user_id' => $userId,
                    'user_name' => $item->user_name,
                    'commission' => 0,
                    'sales' => 0,
                    'items' => 0,
                ];
            }
            $userCommissions[$userId]['commission'] += $this->calculateCommission($item);
            $userCommissions[$userId]['sales'] += (float) $item->total;
            $userCommissions[$userId]['items'] += (float) $item->quantity;
        }

        uasort($userCommissions, fn($a, $b) => $b['commission'] <=> $a['commission']);
        
        return array_values($userCommissions);
    }

    private function getCommissionsByDay()
    {
        $items = $this->getBaseQuery()
            ->select(
                DB::raw("DATE(sales.created_at) as sale_date"),
                'sale_items.*'
            )
            ->with(['product', 'service'])
            ->get();

        $dailyCommissions = [];
        foreach ($items as $item) {
            $date = $item->sale_date;
            if (!isset($dailyCommissions[$date])) {
                $dailyCommissions[$date] = ['commission' => 0, 'sales' => 0];
            }
            $dailyCommissions[$date]['commission'] += $this->calculateCommission($item);
            $dailyCommissions[$date]['sales'] += (float) $item->total;
        }

        ksort($dailyCommissions);

        return collect($dailyCommissions)->map(function ($data, $date) {
            return [
                'label' => Carbon::parse($date)->format('d M'),
                'commission' => round($data['commission'], 2),
                'sales' => round($data['sales'], 2),
            ];
        })->values()->toArray();
    }

    private function getCommissionsByProduct()
    {
        $items = $this->getBaseQuery()
            ->select(
                'sale_items.product_name',
                'sale_items.product_sku',
                'sale_items.*'
            )
            ->with(['product', 'service'])
            ->get();

        $productCommissions = [];
        foreach ($items as $item) {
            $key = $item->product_sku ?? $item->product_name;
            if (!isset($productCommissions[$key])) {
                $productCommissions[$key] = [
                    'name' => $item->product_name,
                    'sku' => $item->product_sku,
                    'commission' => 0,
                    'quantity' => 0,
                    'sales' => 0,
                ];
            }
            $productCommissions[$key]['commission'] += $this->calculateCommission($item);
            $productCommissions[$key]['quantity'] += (float) $item->quantity;
            $productCommissions[$key]['sales'] += (float) $item->total;
        }

        uasort($productCommissions, fn($a, $b) => $b['commission'] <=> $a['commission']);

        return array_slice(array_values($productCommissions), 0, 10);
    }

    private function getCommissionsByCategory()
    {
        $items = $this->getBaseQuery()
            ->leftJoin('categories', function ($join) {
                $join->on('categories.id', '=', DB::raw('COALESCE(products.category_id, services.category_id)'));
            })
            ->select(
                DB::raw("COALESCE(categories.name, 'Sin categoría') as category_name"),
                'sale_items.*'
            )
            ->with(['product', 'service'])
            ->get();

        $categoryCommissions = [];
        foreach ($items as $item) {
            $category = $item->category_name;
            if (!isset($categoryCommissions[$category])) {
                $categoryCommissions[$category] = ['commission' => 0, 'sales' => 0];
            }
            $categoryCommissions[$category]['commission'] += $this->calculateCommission($item);
            $categoryCommissions[$category]['sales'] += (float) $item->total;
        }

        uasort($categoryCommissions, fn($a, $b) => $b['commission'] <=> $a['commission']);

        return collect($categoryCommissions)->map(function ($data, $name) {
            return [
                'category_name' => $name,
                'commission' => round($data['commission'], 2),
                'sales' => round($data['sales'], 2),
            ];
        })->values()->take(8)->toArray();
    }

    public function toggleUserDetail($userId)
    {
        if ($this->expandedUserId === $userId) {
            $this->expandedUserId = null;
            $this->userSalesDetail = [];
        } else {
            $this->expandedUserId = $userId;
            $this->loadUserSalesDetail($userId);
        }
    }

    private function loadUserSalesDetail($userId)
    {
        $items = $this->getBaseQuery()
            ->leftJoin('categories', function ($join) {
                $join->on('categories.id', '=', DB::raw('COALESCE(products.category_id, services.category_id)'));
            })
            ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
            ->where('sales.user_id', $userId)
            ->select(
                'sale_items.*',
                'sales.invoice_number',
                'sales.created_at as sale_date',
                DB::raw("COALESCE(categories.name, 'Sin categoría') as category_name"),
                DB::raw("COALESCE(brands.name, 'Sin marca') as brand_name")
            )
            ->with(['product', 'service'])
            ->orderBy('sales.created_at', 'desc')
            ->get();

        $this->userSalesDetail = $items->map(function ($item) {
            $isService = $item->service_id !== null;
            $commissionSource = $isService ? $item->service : $item->product;
            
            return [
                'invoice_number' => $item->invoice_number,
                'date' => Carbon::parse($item->sale_date)->format('d/m/Y H:i'),
                'product_name' => $item->product_name,
                'product_sku' => $item->product_sku,
                'category' => $item->category_name,
                'brand' => $item->brand_name,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'subtotal' => (float) $item->subtotal,
                'total' => (float) $item->total,
                'commission_type' => $commissionSource?->commission_type,
                'commission_value' => (float) ($commissionSource?->commission_value ?? 0),
                'commission' => $this->calculateCommission($item),
                'is_service' => $isService,
            ];
        })->toArray();
    }

    public function exportPdf($mode = 'detailed')
    {
        $params = http_build_query([
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'branch_id' => $this->selectedBranchId,
            'user_id' => $this->selectedUserId,
            'category_id' => $this->selectedCategoryId,
            'brand_id' => $this->selectedBrandId,
            'mode' => $mode,
        ]);
        
        return redirect()->to(route('reports.commissions.pdf') . '?' . $params);
    }

    public function render()
    {
        $user = auth()->user();
        $isSuperAdmin = $user->isSuperAdmin();

        $this->calculateSummary();
        $this->commissionsByUser = $this->getCommissionsByUser();
        $this->commissionsByDay = $this->getCommissionsByDay();
        $this->commissionsByProduct = $this->getCommissionsByProduct();
        $this->commissionsByCategory = $this->getCommissionsByCategory();
        $this->userRanking = array_slice($this->commissionsByUser, 0, 5);

        $branches = $isSuperAdmin ? Branch::where('is_active', true)->orderBy('name')->get() : collect();
        $users = User::whereHas('roles')->orderBy('name')->get();
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $brands = Brand::where('is_active', true)->orderBy('name')->get();

        // Dispatch event to update charts
        $this->dispatch('charts-updated', [
            'trend' => $this->commissionsByDay,
            'users' => $this->commissionsByUser,
            'products' => $this->commissionsByProduct,
            'categories' => $this->commissionsByCategory,
        ]);

        return view('livewire.reports.commissions', [
            'branches' => $branches,
            'users' => $users,
            'categories' => $categories,
            'brands' => $brands,
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }
}

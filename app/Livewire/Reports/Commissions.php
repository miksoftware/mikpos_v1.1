<?php

namespace App\Livewire\Reports;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Branch;
use App\Models\User;
use App\Models\Product;
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
    public string $search = '';

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
            ->where('sales.status', 'completed')
            ->whereDate('sales.created_at', '>=', $this->startDate)
            ->whereDate('sales.created_at', '<=', $this->endDate)
            ->where(function ($q) {
                $q->where('products.has_commission', true)
                  ->whereNotNull('products.commission_value')
                  ->where('products.commission_value', '>', 0);
            });

        if ($this->selectedBranchId) {
            $query->where('sales.branch_id', $this->selectedBranchId);
        } elseif (!auth()->user()->isSuperAdmin()) {
            $query->where('sales.branch_id', auth()->user()->branch_id);
        }

        if ($this->selectedUserId) {
            $query->where('sales.user_id', $this->selectedUserId);
        }

        return $query;
    }

    private function calculateCommission($item): float
    {
        if (!$item->product || !$item->product->has_commission) {
            return 0;
        }

        $basePrice = (float) $item->unit_price;
        $quantity = (int) $item->quantity;
        $commissionValue = (float) $item->product->commission_value;
        $commissionType = $item->product->commission_type;

        if ($commissionType === 'percentage') {
            return ($basePrice * ($commissionValue / 100)) * $quantity;
        }

        return $commissionValue * $quantity;
    }

    private function calculateSummary()
    {
        $items = $this->getBaseQuery()
            ->select('sale_items.*', 'products.has_commission', 'products.commission_type', 'products.commission_value')
            ->with('product')
            ->get();

        $this->totalCommissions = 0;
        $this->totalSales = 0;
        $this->totalItemsSold = 0;

        foreach ($items as $item) {
            $this->totalCommissions += $this->calculateCommission($item);
            $this->totalSales += (float) $item->total;
            $this->totalItemsSold += (int) $item->quantity;
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
                'sale_items.*',
                'products.has_commission',
                'products.commission_type', 
                'products.commission_value'
            )
            ->with('product')
            ->get();

        $userCommissions = [];
        foreach ($items as $item) {
            $userId = $item->user_id;
            if (!isset($userCommissions[$userId])) {
                $userCommissions[$userId] = [
                    'user_name' => $item->user_name,
                    'commission' => 0,
                    'sales' => 0,
                    'items' => 0,
                ];
            }
            $userCommissions[$userId]['commission'] += $this->calculateCommission($item);
            $userCommissions[$userId]['sales'] += (float) $item->total;
            $userCommissions[$userId]['items'] += (int) $item->quantity;
        }

        uasort($userCommissions, fn($a, $b) => $b['commission'] <=> $a['commission']);
        
        return array_values($userCommissions);
    }

    private function getCommissionsByDay()
    {
        $items = $this->getBaseQuery()
            ->select(
                DB::raw("DATE(sales.created_at) as sale_date"),
                'sale_items.*',
                'products.has_commission',
                'products.commission_type',
                'products.commission_value'
            )
            ->with('product')
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
                'sale_items.*',
                'products.has_commission',
                'products.commission_type',
                'products.commission_value'
            )
            ->with('product')
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
            $productCommissions[$key]['quantity'] += (int) $item->quantity;
            $productCommissions[$key]['sales'] += (float) $item->total;
        }

        uasort($productCommissions, fn($a, $b) => $b['commission'] <=> $a['commission']);

        return array_slice(array_values($productCommissions), 0, 10);
    }

    private function getCommissionsByCategory()
    {
        $items = $this->getBaseQuery()
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->select(
                DB::raw("COALESCE(categories.name, 'Sin categoría') as category_name"),
                'sale_items.*',
                'products.has_commission',
                'products.commission_type',
                'products.commission_value'
            )
            ->with('product')
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

        return view('livewire.reports.commissions', [
            'branches' => $branches,
            'users' => $users,
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }
}

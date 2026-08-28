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

    // View Mode
    public string $viewMode = 'standard'; // 'standard' | 'versus'

    // Standard Filters
    public string $dateRange = 'month';
    public ?string $startDate = null;
    public ?string $endDate = null;
    public ?int $selectedBranchId = null;
    public ?int $selectedUserId = null;
    public ?int $selectedCashRegisterId = null;
    public ?int $selectedCategoryId = null;
    public ?int $selectedBrandId = null;
    public string $search = '';
    
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

    // Detail view
    public ?int $expandedUserId = null;
    public array $userSalesDetail = [];

    // Standard Summary
    public float $totalCommissions = 0;
    public float $totalSales = 0;
    public int $totalTransactions = 0;
    public int $totalItemsSold = 0;
    public float $averageCommissionRate = 0;

    // Standard Chart data
    public array $commissionsByUser = [];
    public array $commissionsByDay = [];
    public array $commissionsByProduct = [];
    public array $commissionsByCategory = [];
    public array $userRanking = [];

    // Versus Comparison Data
    public array $versusSummary = [];
    public array $versusDaily = [];
    public array $versusSellers = [];
    public array $versusCategories = [];
    public array $versusProducts = [];

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

        $query = SaleItem::query()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->leftJoin('products', 'sale_items.product_id', '=', 'products.id')
            ->leftJoin('services', 'sale_items.service_id', '=', 'services.id')
            ->where('sales.status', 'completed')
            ->whereDate('sales.created_at', '>=', $start)
            ->whereDate('sales.created_at', '<=', $end)
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

        if ($this->selectedUserId) {
            $query->where('sales.seller_id', $this->selectedUserId);
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
            $salesQuery->where('seller_id', $this->selectedUserId);
        }

        $this->totalTransactions = $salesQuery->count();
        $this->averageCommissionRate = $this->totalSales > 0 
            ? ($this->totalCommissions / $this->totalSales) * 100 
            : 0;
    }

    private function getCommissionsByUser()
    {
        $items = $this->getBaseQuery()
            ->join('users', 'sales.seller_id', '=', 'users.id')
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

    /**
     * Compute full commission metrics for an arbitrary period.
     */
    private function computePeriodCommissionMetrics(string $start, string $end): array
    {
        $items = $this->getBaseQuery($start, $end)
            ->leftJoin('categories', function ($join) {
                $join->on('categories.id', '=', DB::raw('COALESCE(products.category_id, services.category_id)'));
            })
            ->join('users', 'sales.seller_id', '=', 'users.id')
            ->select(
                'sale_items.*',
                'sales.invoice_number',
                'sales.created_at as sale_date',
                'users.id as seller_id',
                'users.name as seller_name',
                DB::raw("COALESCE(categories.name, 'Sin categoría') as cat_name"),
                'products.has_commission as p_has_comm',
                'products.commission_type as p_comm_type',
                'products.commission_value as p_comm_val',
                'services.has_commission as s_has_comm',
                'services.commission_type as s_comm_type',
                'services.commission_value as s_comm_val'
            )
            ->with(['product', 'service'])
            ->get();

        $totalCommissions = 0;
        $totalSales = 0;
        $totalItems = 0;
        $uniqueSaleIds = [];
        $sellerData = [];
        $dailyData = [];
        $categoryData = [];
        $productData = [];

        foreach ($items as $item) {
            $basePrice = (float) $item->unit_price;
            $quantity = (float) $item->quantity;
            $itemTotal = (float) $item->total;

            $isService = $item->service_id !== null;
            $hasComm = $isService ? $item->s_has_comm : $item->p_has_comm;
            $commType = $isService ? $item->s_comm_type : $item->p_comm_type;
            $commVal = (float) ($isService ? $item->s_comm_val : $item->p_comm_val);

            $comm = 0;
            if ($hasComm && $commVal > 0) {
                if ($commType === 'percentage') {
                    $comm = ($basePrice * ($commVal / 100)) * $quantity;
                } else {
                    $comm = $commVal * $quantity;
                }
            }

            $totalCommissions += $comm;
            $totalSales += $itemTotal;
            $totalItems += $quantity;
            $uniqueSaleIds[$item->sale_id] = true;

            // Seller breakdown
            $sName = $item->seller_name ?? 'Sin asignar';
            if (!isset($sellerData[$sName])) {
                $sellerData[$sName] = ['name' => $sName, 'commission' => 0, 'sales' => 0, 'items' => 0, 'count' => 0];
            }
            $sellerData[$sName]['commission'] += $comm;
            $sellerData[$sName]['sales'] += $itemTotal;
            $sellerData[$sName]['items'] += $quantity;
            $sellerData[$sName]['count']++;

            // Daily breakdown (1..31)
            $day = (int) Carbon::parse($item->sale_date)->format('j');
            if (!isset($dailyData[$day])) {
                $dailyData[$day] = ['day' => $day, 'commission' => 0, 'sales' => 0, 'items' => 0, 'count' => 0];
            }
            $dailyData[$day]['commission'] += $comm;
            $dailyData[$day]['sales'] += $itemTotal;
            $dailyData[$day]['items'] += $quantity;
            $dailyData[$day]['count']++;

            // Category breakdown
            $cName = $item->cat_name ?? 'Sin categoría';
            if (!isset($categoryData[$cName])) {
                $categoryData[$cName] = ['name' => $cName, 'commission' => 0, 'sales' => 0, 'items' => 0];
            }
            $categoryData[$cName]['commission'] += $comm;
            $categoryData[$cName]['sales'] += $itemTotal;
            $categoryData[$cName]['items'] += $quantity;

            // Product breakdown
            $pKey = $item->product_sku ? $item->product_sku : $item->product_name;
            if (!isset($productData[$pKey])) {
                $productData[$pKey] = ['name' => $item->product_name, 'sku' => $item->product_sku, 'commission' => 0, 'sales' => 0, 'quantity' => 0];
            }
            $productData[$pKey]['commission'] += $comm;
            $productData[$pKey]['sales'] += $itemTotal;
            $productData[$pKey]['quantity'] += $quantity;
        }

        uasort($sellerData, fn($a, $b) => $b['commission'] <=> $a['commission']);
        uasort($categoryData, fn($a, $b) => $b['commission'] <=> $a['commission']);
        uasort($productData, fn($a, $b) => $b['commission'] <=> $a['commission']);

        $topSeller = !empty($sellerData) ? reset($sellerData) : ['name' => 'Ninguno', 'commission' => 0, 'sales' => 0];

        return [
            'totalCommissions' => $totalCommissions,
            'totalSales' => $totalSales,
            'totalItems' => $totalItems,
            'totalTransactions' => count($uniqueSaleIds),
            'avgCommissionRate' => $totalSales > 0 ? ($totalCommissions / $totalSales) * 100 : 0,
            'sellerData' => $sellerData,
            'dailyData' => $dailyData,
            'categoryData' => $categoryData,
            'productData' => $productData,
            'topSeller' => $topSeller,
        ];
    }

    private function calculateVersusData()
    {
        $dataA = $this->computePeriodCommissionMetrics($this->startDateA, $this->endDateA);
        $dataB = $this->computePeriodCommissionMetrics($this->startDateB, $this->endDateB);

        $this->versusSummary = [
            'A' => $dataA,
            'B' => $dataB,
        ];

        // Daily Comparison (Day 1..31)
        $this->versusDaily = [];
        for ($d = 1; $d <= 31; $d++) {
            $cA = $dataA['dailyData'][$d]['commission'] ?? 0;
            $sA = $dataA['dailyData'][$d]['sales'] ?? 0;
            $cB = $dataB['dailyData'][$d]['commission'] ?? 0;
            $sB = $dataB['dailyData'][$d]['sales'] ?? 0;

            $this->versusDaily[] = [
                'day' => $d,
                'label' => "Día {$d}",
                'commA' => round($cA, 0),
                'salesA' => round($sA, 0),
                'commB' => round($cB, 0),
                'salesB' => round($sB, 0),
                'diffComm' => round($cB - $cA, 0),
            ];
        }

        // Sellers Comparison
        $allSellers = array_unique(array_merge(array_keys($dataA['sellerData']), array_keys($dataB['sellerData'])));
        $sellerComp = [];
        foreach ($allSellers as $name) {
            $commA = $dataA['sellerData'][$name]['commission'] ?? 0;
            $salesA = $dataA['sellerData'][$name]['sales'] ?? 0;
            $itemsA = $dataA['sellerData'][$name]['items'] ?? 0;

            $commB = $dataB['sellerData'][$name]['commission'] ?? 0;
            $salesB = $dataB['sellerData'][$name]['sales'] ?? 0;
            $itemsB = $dataB['sellerData'][$name]['items'] ?? 0;

            $diffComm = $commB - $commA;
            $growthComm = $commA != 0 ? (($commB - $commA) / $commA) * 100 : ($commB > 0 ? 100 : 0);

            $diffSales = $salesB - $salesA;
            $growthSales = $salesA != 0 ? (($salesB - $salesA) / $salesA) * 100 : ($salesB > 0 ? 100 : 0);

            $sellerComp[] = [
                'name' => $name,
                'commA' => round($commA, 0),
                'salesA' => round($salesA, 0),
                'itemsA' => $itemsA,
                'commB' => round($commB, 0),
                'salesB' => round($salesB, 0),
                'itemsB' => $itemsB,
                'diffComm' => round($diffComm, 0),
                'growthComm' => round($growthComm, 1),
                'diffSales' => round($diffSales, 0),
                'growthSales' => round($growthSales, 1),
            ];
        }
        $this->versusSellers = collect($sellerComp)->sortByDesc('commB')->values()->toArray();

        // Categories Comparison
        $allCats = array_unique(array_merge(array_keys($dataA['categoryData']), array_keys($dataB['categoryData'])));
        $catComp = [];
        foreach ($allCats as $name) {
            $cA = $dataA['categoryData'][$name]['commission'] ?? 0;
            $sA = $dataA['categoryData'][$name]['sales'] ?? 0;
            $cB = $dataB['categoryData'][$name]['commission'] ?? 0;
            $sB = $dataB['categoryData'][$name]['sales'] ?? 0;
            $diff = $cB - $cA;
            $growth = $cA != 0 ? (($cB - $cA) / $cA) * 100 : ($cB > 0 ? 100 : 0);

            $catComp[] = [
                'name' => $name,
                'commA' => round($cA, 0),
                'salesA' => round($sA, 0),
                'commB' => round($cB, 0),
                'salesB' => round($sB, 0),
                'diffComm' => round($diff, 0),
                'growthComm' => round($growth, 1),
            ];
        }
        $this->versusCategories = collect($catComp)->sortByDesc('commB')->values()->toArray();

        // Products Comparison
        $allProds = array_unique(array_merge(array_keys($dataA['productData']), array_keys($dataB['productData'])));
        $prodComp = [];
        foreach ($allProds as $key) {
            $itemA = $dataA['productData'][$key] ?? null;
            $itemB = $dataB['productData'][$key] ?? null;

            $pName = $itemB['name'] ?? $itemA['name'] ?? $key;
            $pSku = $itemB['sku'] ?? $itemA['sku'] ?? '';
            $cA = $itemA['commission'] ?? 0;
            $sA = $itemA['sales'] ?? 0;
            $cB = $itemB['commission'] ?? 0;
            $sB = $itemB['sales'] ?? 0;
            $diff = $cB - $cA;
            $growth = $cA != 0 ? (($cB - $cA) / $cA) * 100 : ($cB > 0 ? 100 : 0);

            $prodComp[] = [
                'name' => $pName,
                'sku' => $pSku,
                'commA' => round($cA, 0),
                'salesA' => round($sA, 0),
                'commB' => round($cB, 0),
                'salesB' => round($sB, 0),
                'diffComm' => round($diff, 0),
                'growthComm' => round($growth, 1),
            ];
        }
        $this->versusProducts = collect($prodComp)->sortByDesc('commB')->values()->take(10)->toArray();
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
            ->where('sales.seller_id', $userId)
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

    public function exportExcel()
    {
        if (!auth()->user()->hasPermission('reports.export')) {
            $this->dispatch('notify', message: 'No tienes permiso para exportar', type: 'error');
            return;
        }

        if ($this->viewMode === 'versus') {
            return redirect()->route('reports.commissions.excel-versus', [
                'start_date_a' => $this->startDateA,
                'end_date_a' => $this->endDateA,
                'start_date_b' => $this->startDateB,
                'end_date_b' => $this->endDateB,
                'label_a' => $this->labelA,
                'label_b' => $this->labelB,
                'branch_id' => $this->selectedBranchId,
                'user_id' => $this->selectedUserId,
                'category_id' => $this->selectedCategoryId,
                'brand_id' => $this->selectedBrandId,
                'cash_register_id' => $this->selectedCashRegisterId,
            ]);
        }

        return redirect()->route('reports.commissions.excel', [
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'branch_id' => $this->selectedBranchId,
            'user_id' => $this->selectedUserId,
            'category_id' => $this->selectedCategoryId,
            'brand_id' => $this->selectedBrandId,
            'cash_register_id' => $this->selectedCashRegisterId,
        ]);
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

    public function clearFilters()
    {
        $this->search = '';
        $this->dateRange = 'month';
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
        $this->selectedUserId = null;
        $this->selectedCategoryId = null;
        $this->selectedBrandId = null;
        $this->selectedCashRegisterId = null;
        $this->versusPreset = 'mom';
        $this->initVersusPeriods();
        if (auth()->user()->isSuperAdmin()) {
            $this->selectedBranchId = null;
        }
        $this->resetPage();
    }

    public function render()
    {
        $user = auth()->user();
        $isSuperAdmin = $user->isSuperAdmin();

        if ($this->viewMode === 'versus') {
            $this->calculateVersusData();
        } else {
            $this->calculateSummary();
            $this->commissionsByUser = $this->getCommissionsByUser();
            $this->commissionsByDay = $this->getCommissionsByDay();
            $this->commissionsByProduct = $this->getCommissionsByProduct();
            $this->commissionsByCategory = $this->getCommissionsByCategory();
            $this->userRanking = array_slice($this->commissionsByUser, 0, 5);
        }

        $isSupervisor = $user->isSupervisor();
        $branches = $isSuperAdmin ? Branch::where('is_active', true)->orderBy('name')->get() : collect();
        $users = User::whereHas('roles')->orderBy('name')->get();
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $brands = Brand::where('is_active', true)->orderBy('name')->get();
        $cashRegisters = $isSupervisor
            ? $user->cashRegisters()->where('cash_registers.is_active', true)->orderBy('cash_registers.name')->get()
            : collect();

        return view('livewire.reports.commissions', [
            'branches' => $branches,
            'users' => $users,
            'categories' => $categories,
            'brands' => $brands,
            'isSuperAdmin' => $isSuperAdmin,
            'isSupervisor' => $isSupervisor,
            'cashRegisters' => $cashRegisters,
        ]);
    }
}

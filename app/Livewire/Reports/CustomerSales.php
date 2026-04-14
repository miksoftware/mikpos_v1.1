<?php

namespace App\Livewire\Reports;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Sale;
use App\Models\Branch;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Carbon\Carbon;

#[Layout('layouts.app')]
class CustomerSales extends Component
{
    use WithPagination;

    public string $dateRange = 'month';
    public ?string $startDate = null;
    public ?string $endDate = null;
    public ?int $selectedBranchId = null;
    public string $search = '';
    public string $sortBy = 'total_revenue';
    public string $sortDirection = 'desc';

    // Summary
    public float $totalRevenue = 0;
    public int $totalTransactions = 0;
    public int $totalCustomers = 0;
    public float $averageTicket = 0;
    public float $averagePerCustomer = 0;

    // Chart data
    public array $topCustomers = [];
    public array $salesByDay = [];
    public array $salesByPaymentType = [];

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

    private function getBaseQuery()
    {
        $query = Sale::query()
            ->where('sales.status', 'completed')
            ->whereNotNull('sales.customer_id')
            ->whereDate('sales.created_at', '>=', $this->startDate)
            ->whereDate('sales.created_at', '<=', $this->endDate);

        if ($this->selectedBranchId) {
            $query->where('sales.branch_id', $this->selectedBranchId);
        } elseif (!auth()->user()->isSuperAdmin()) {
            $query->where('sales.branch_id', auth()->user()->branch_id);
        }

        return $query;
    }

    private function calculateSummary()
    {
        $query = $this->getBaseQuery();

        $this->totalRevenue = (float) (clone $query)->sum('sales.total');
        $this->totalTransactions = (int) (clone $query)->count();
        $this->totalCustomers = (int) (clone $query)->distinct('sales.customer_id')->count('sales.customer_id');
        $this->averageTicket = $this->totalTransactions > 0
            ? $this->totalRevenue / $this->totalTransactions
            : 0;
        $this->averagePerCustomer = $this->totalCustomers > 0
            ? $this->totalRevenue / $this->totalCustomers
            : 0;
    }

    private function getTopCustomers()
    {
        return $this->getBaseQuery()
            ->join('customers', 'sales.customer_id', '=', 'customers.id')
            ->select(
                'customers.id',
                DB::raw("CASE WHEN customers.customer_type = 'juridico' AND customers.business_name IS NOT NULL AND customers.business_name != '' THEN customers.business_name ELSE CONCAT(customers.first_name, ' ', customers.last_name) END as customer_name"),
                DB::raw('SUM(sales.total) as total_revenue'),
                DB::raw('COUNT(sales.id) as total_transactions')
            )
            ->groupBy('customers.id', 'customers.customer_type', 'customers.business_name', 'customers.first_name', 'customers.last_name')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get()
            ->toArray();
    }

    private function getSalesByDay()
    {
        $days = Carbon::parse($this->startDate)->diffInDays(Carbon::parse($this->endDate)) + 1;
        $format = $days > 31 ? '%Y-%u' : '%Y-%m-%d';

        $query = $this->getBaseQuery()
            ->select(
                DB::raw("DATE_FORMAT(sales.created_at, '{$format}') as period"),
                DB::raw('SUM(sales.total) as revenue'),
                DB::raw('COUNT(sales.id) as transactions'),
                DB::raw('COUNT(DISTINCT sales.customer_id) as customers')
            )
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        return $query->map(function ($item) use ($days) {
            if ($days > 31) {
                $parts = explode('-', $item->period);
                $label = 'Sem ' . $parts[1];
            } else {
                $label = Carbon::parse($item->period)->format('d M');
            }
            return [
                'label' => $label,
                'revenue' => (float) $item->revenue,
                'transactions' => (int) $item->transactions,
                'customers' => (int) $item->customers,
            ];
        })->toArray();
    }

    private function getSalesByPaymentType()
    {
        return $this->getBaseQuery()
            ->select(
                'sales.payment_type',
                DB::raw('SUM(sales.total) as total_amount'),
                DB::raw('COUNT(sales.id) as transactions')
            )
            ->groupBy('sales.payment_type')
            ->orderByDesc('total_amount')
            ->get()
            ->map(function ($item) {
                $labels = ['cash' => 'Contado', 'credit' => 'Crédito'];
                return [
                    'type' => $labels[$item->payment_type] ?? $item->payment_type,
                    'total_amount' => (float) $item->total_amount,
                    'transactions' => (int) $item->transactions,
                ];
            })
            ->toArray();
    }

    public function render()
    {
        $user = auth()->user();
        $isSuperAdmin = $user->isSuperAdmin();

        $this->calculateSummary();
        $this->topCustomers = $this->getTopCustomers();
        $this->salesByDay = $this->getSalesByDay();
        $this->salesByPaymentType = $this->getSalesByPaymentType();

        // Paginated customer list
        $query = $this->getBaseQuery()
            ->join('customers', 'sales.customer_id', '=', 'customers.id')
            ->select(
                'customers.id',
                'customers.customer_type',
                'customers.document_number',
                'customers.first_name',
                'customers.last_name',
                'customers.business_name',
                'customers.phone',
                'customers.email',
                DB::raw('SUM(sales.total) as total_revenue'),
                DB::raw('COUNT(sales.id) as total_transactions'),
                DB::raw('AVG(sales.total) as avg_ticket'),
                DB::raw('MAX(sales.created_at) as last_sale_date'),
                DB::raw('MIN(sales.created_at) as first_sale_date')
            )
            ->groupBy(
                'customers.id',
                'customers.customer_type',
                'customers.document_number',
                'customers.first_name',
                'customers.last_name',
                'customers.business_name',
                'customers.phone',
                'customers.email'
            );

        if ($this->search) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('customers.first_name', 'like', "%{$search}%")
                  ->orWhere('customers.last_name', 'like', "%{$search}%")
                  ->orWhere('customers.business_name', 'like', "%{$search}%")
                  ->orWhere('customers.document_number', 'like', "%{$search}%")
                  ->orWhere('customers.phone', 'like', "%{$search}%");
            });
        }

        switch ($this->sortBy) {
            case 'total_revenue':
                $query->orderBy('total_revenue', $this->sortDirection);
                break;
            case 'total_transactions':
                $query->orderBy('total_transactions', $this->sortDirection);
                break;
            case 'avg_ticket':
                $query->orderBy('avg_ticket', $this->sortDirection);
                break;
            case 'customer_name':
                $query->orderBy('customers.first_name', $this->sortDirection);
                break;
            case 'last_sale':
                $query->orderBy('last_sale_date', $this->sortDirection);
                break;
            default:
                $query->orderBy('total_revenue', 'desc');
        }

        $customers = $query->paginate(15);

        $branches = $isSuperAdmin ? Branch::where('is_active', true)->orderBy('name')->get() : collect();

        return view('livewire.reports.customer-sales', [
            'customers' => $customers,
            'branches' => $branches,
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }
}

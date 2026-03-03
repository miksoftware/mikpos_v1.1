<?php

namespace App\Livewire\Reports;

use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\PaymentMethod;
use App\Models\Sale;
use App\Models\SalePayment;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class PaymentMethodsReport extends Component
{
    // Filters
    public string $dateRange = 'month';
    public ?string $startDate = null;
    public ?string $endDate = null;
    public ?int $selectedBranchId = null;
    public ?int $selectedCashRegisterId = null;
    public ?int $selectedPaymentMethodId = null;
    public ?int $selectedUserId = null;

    // View mode
    public string $viewMode = 'summary'; // summary, detail, by_user

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

    public function clearFilters()
    {
        $this->dateRange = 'month';
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
        $this->selectedCashRegisterId = null;
        $this->selectedPaymentMethodId = null;
        $this->selectedUserId = null;

        $user = auth()->user();
        if (!$user->isSuperAdmin() && $user->branch_id) {
            $this->selectedBranchId = $user->branch_id;
        } else {
            $this->selectedBranchId = null;
        }
    }

    private function baseQuery()
    {
        $query = SalePayment::join('sales', 'sale_payments.sale_id', '=', 'sales.id')
            ->join('payment_methods', 'sale_payments.payment_method_id', '=', 'payment_methods.id')
            ->where('sales.status', 'completed')
            ->whereDate('sales.created_at', '>=', $this->startDate)
            ->whereDate('sales.created_at', '<=', $this->endDate);

        if ($this->selectedBranchId) {
            $query->where('sales.branch_id', $this->selectedBranchId);
        } elseif (!auth()->user()->isSuperAdmin()) {
            $query->where('sales.branch_id', auth()->user()->branch_id);
        }

        if ($this->selectedCashRegisterId) {
            $query->whereHas('sale.cashReconciliation', function ($q) {
                $q->where('cash_register_id', $this->selectedCashRegisterId);
            });
        }

        if ($this->selectedPaymentMethodId) {
            $query->where('sale_payments.payment_method_id', $this->selectedPaymentMethodId);
        }

        if ($this->selectedUserId) {
            $query->where('sales.user_id', $this->selectedUserId);
        }

        return $query;
    }

    private function getSummaryData(): array
    {
        $data = (clone $this->baseQuery())
            ->select(
                'payment_methods.id',
                'payment_methods.name',
                DB::raw('SUM(sale_payments.amount) as total'),
                DB::raw('COUNT(DISTINCT sales.id) as transaction_count')
            )
            ->groupBy('payment_methods.id', 'payment_methods.name')
            ->orderByDesc('total')
            ->get();

        $grandTotal = $data->sum('total');

        return [
            'items' => $data,
            'grandTotal' => $grandTotal,
            'transactionCount' => $data->sum('transaction_count'),
        ];
    }

    private function getDetailData()
    {
        return (clone $this->baseQuery())
            ->select(
                'sales.id as sale_id',
                'sales.invoice_number',
                'sales.created_at',
                'sales.total as sale_total',
                'sale_payments.amount',
                'payment_methods.name as payment_method_name',
                DB::raw("(SELECT name FROM users WHERE users.id = sales.user_id) as user_name"),
                DB::raw("(SELECT name FROM branches WHERE branches.id = sales.branch_id) as branch_name")
            )
            ->orderByDesc('sales.created_at')
            ->paginate(20);
    }

    private function getByUserData(): array
    {
        return (clone $this->baseQuery())
            ->join('users', 'sales.user_id', '=', 'users.id')
            ->select(
                'users.id as user_id',
                'users.name as user_name',
                'payment_methods.name as payment_method_name',
                DB::raw('SUM(sale_payments.amount) as total'),
                DB::raw('COUNT(DISTINCT sales.id) as transaction_count')
            )
            ->groupBy('users.id', 'users.name', 'payment_methods.name')
            ->orderBy('users.name')
            ->orderByDesc('total')
            ->get()
            ->groupBy('user_name')
            ->toArray();
    }

    public function render()
    {
        $user = auth()->user();
        $isSuperAdmin = $user->isSuperAdmin();

        $branches = $isSuperAdmin ? Branch::orderBy('name')->get() : collect();

        $cashRegistersQuery = CashRegister::active()->orderBy('name');
        if ($this->selectedBranchId) {
            $cashRegistersQuery->where('branch_id', $this->selectedBranchId);
        } elseif (!$isSuperAdmin && $user->branch_id) {
            $cashRegistersQuery->where('branch_id', $user->branch_id);
        }
        $cashRegisters = $cashRegistersQuery->with('user')->get();

        $paymentMethods = PaymentMethod::where('is_active', true)->orderBy('name')->get();

        $usersQuery = \App\Models\User::where('is_active', true)->orderBy('name');
        if ($this->selectedBranchId) {
            $usersQuery->where('branch_id', $this->selectedBranchId);
        } elseif (!$isSuperAdmin && $user->branch_id) {
            $usersQuery->where('branch_id', $user->branch_id);
        }
        $users = $usersQuery->get();

        $summary = $this->getSummaryData();
        $detailData = $this->viewMode === 'detail' ? $this->getDetailData() : null;
        $byUserData = $this->viewMode === 'by_user' ? $this->getByUserData() : [];

        return view('livewire.reports.payment-methods-report', compact(
            'isSuperAdmin', 'branches', 'cashRegisters', 'paymentMethods',
            'users', 'summary', 'detailData', 'byUserData'
        ));
    }
}

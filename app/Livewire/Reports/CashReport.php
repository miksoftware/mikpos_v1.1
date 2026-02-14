<?php

namespace App\Livewire\Reports;

use App\Models\Branch;
use App\Models\CashMovement;
use App\Models\CashReconciliation;
use App\Models\CashRegister;
use App\Models\CreditNote;
use App\Models\CreditPayment;
use App\Models\Refund;
use App\Models\Sale;
use App\Models\SalePayment;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class CashReport extends Component
{
    use WithPagination;

    // Filters
    public ?int $selectedCashRegisterId = null;
    public ?string $startDate = null;
    public ?string $endDate = null;
    public ?int $selectedBranchId = null;
    public string $dateRange = 'month';

    // View mode tabs
    public string $viewMode = 'reconciliations'; // reconciliations, movements, report

    // Report data (for Informe tab)
    public array $reportData = [];

    // Cash registers list
    public $cashRegisters = [];

    public function mount()
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');

        $user = auth()->user();
        if (!$user->isSuperAdmin() && $user->branch_id) {
            $this->selectedBranchId = $user->branch_id;
        }

        $this->loadCashRegisters();
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
        $this->resetPage();
    }

    public function updatedViewMode() { $this->resetPage(); }
    public function updatedSelectedCashRegisterId() { $this->resetPage(); }

    public function updatedSelectedBranchId()
    {
        $this->selectedCashRegisterId = null;
        $this->loadCashRegisters();
        $this->resetPage();
    }

    private function loadCashRegisters()
    {
        $query = CashRegister::query();

        if ($this->selectedBranchId) {
            $query->where('branch_id', $this->selectedBranchId);
        } elseif (!auth()->user()->isSuperAdmin()) {
            $query->where('branch_id', auth()->user()->branch_id);
        }

        $this->cashRegisters = $query->orderBy('name')->get();
    }

    private function baseReconciliationQuery()
    {
        $query = CashReconciliation::query();

        if ($this->selectedBranchId) {
            $query->where('cash_reconciliations.branch_id', $this->selectedBranchId);
        } elseif (!auth()->user()->isSuperAdmin()) {
            $query->where('cash_reconciliations.branch_id', auth()->user()->branch_id);
        }

        if ($this->selectedCashRegisterId) {
            $query->where('cash_reconciliations.cash_register_id', $this->selectedCashRegisterId);
        }

        if ($this->startDate) {
            $query->whereDate('cash_reconciliations.opened_at', '>=', $this->startDate);
        }
        if ($this->endDate) {
            $query->whereDate('cash_reconciliations.opened_at', '<=', $this->endDate);
        }

        return $query;
    }

    private function getReconciliations()
    {
        return $this->baseReconciliationQuery()
            ->with(['cashRegister', 'openedByUser', 'closedByUser'])
            ->orderByDesc('cash_reconciliations.opened_at')
            ->paginate(15);
    }

    private function getMovements()
    {
        $query = CashMovement::join('cash_reconciliations', 'cash_movements.cash_reconciliation_id', '=', 'cash_reconciliations.id')
            ->leftJoin('cash_registers', 'cash_reconciliations.cash_register_id', '=', 'cash_registers.id')
            ->leftJoin('users', 'cash_movements.user_id', '=', 'users.id');

        if ($this->selectedBranchId) {
            $query->where('cash_reconciliations.branch_id', $this->selectedBranchId);
        } elseif (!auth()->user()->isSuperAdmin()) {
            $query->where('cash_reconciliations.branch_id', auth()->user()->branch_id);
        }

        if ($this->selectedCashRegisterId) {
            $query->where('cash_reconciliations.cash_register_id', $this->selectedCashRegisterId);
        }

        if ($this->startDate) {
            $query->whereDate('cash_movements.created_at', '>=', $this->startDate);
        }
        if ($this->endDate) {
            $query->whereDate('cash_movements.created_at', '<=', $this->endDate);
        }

        return $query->select(
            'cash_movements.*',
            'cash_registers.name as register_name',
            'users.name as user_name'
        )->orderByDesc('cash_movements.created_at')->paginate(20);
    }

    private function calculateReport()
    {
        // Get reconciliation IDs in the date range
        $reconciliationIds = $this->baseReconciliationQuery()->pluck('cash_reconciliations.id');

        if ($reconciliationIds->isEmpty()) {
            $this->reportData = [
                'register_name' => $this->getRegisterLabel(),
                'user_name' => $this->getUserLabel(),
                'total_sales' => 0,
                'total_income' => 0,
                'credit_payments_received' => 0,
                'total_expenses' => 0,
                'total_refunds' => 0,
                'total_credit_notes' => 0,
                'total_gastos' => 0,
                'total_tax' => 0,
                'available_no_tax' => 0,
                'sales_by_method' => [],
                'reconciliation_count' => 0,
            ];
            return;
        }

        // Total sales from reconciliations
        $totalSales = (float) Sale::whereIn('cash_reconciliation_id', $reconciliationIds)
            ->where('sales.status', 'completed')
            ->sum('sales.total');

        // Total tax from sales
        $totalTax = (float) Sale::whereIn('cash_reconciliation_id', $reconciliationIds)
            ->where('sales.status', 'completed')
            ->sum('sales.tax_total');

        // Income movements
        $totalIncome = (float) CashMovement::whereIn('cash_reconciliation_id', $reconciliationIds)
            ->where('type', 'income')
            ->sum('amount');

        // Expense movements
        $totalExpenses = (float) CashMovement::whereIn('cash_reconciliation_id', $reconciliationIds)
            ->where('type', 'expense')
            ->sum('amount');

        // Credit payments received (abonos a créditos de ventas - receivable)
        $creditPaymentsReceived = (float) CreditPayment::where('credit_type', 'receivable')
            ->whereIn('cash_reconciliation_id', $reconciliationIds)
            ->sum('amount');

        // Refunds
        $totalRefunds = (float) Refund::whereIn('cash_reconciliation_id', $reconciliationIds)
            ->where('refunds.status', 'completed')
            ->sum('refunds.total');

        // Credit notes (for electronic invoices)
        $branchFilter = $this->selectedBranchId ?? (auth()->user()->isSuperAdmin() ? null : auth()->user()->branch_id);
        $creditNotesQuery = CreditNote::whereIn('status', ['validated', 'pending']);
        if ($branchFilter) {
            $creditNotesQuery->where('branch_id', $branchFilter);
        }
        if ($this->startDate) {
            $creditNotesQuery->whereDate('created_at', '>=', $this->startDate);
        }
        if ($this->endDate) {
            $creditNotesQuery->whereDate('created_at', '<=', $this->endDate);
        }
        $totalCreditNotes = (float) $creditNotesQuery->sum('total');

        // Total gastos = expenses + refunds + credit notes
        $totalGastos = $totalExpenses + $totalRefunds + $totalCreditNotes;

        // Sales by payment method
        $salesByMethod = SalePayment::join('sales', 'sale_payments.sale_id', '=', 'sales.id')
            ->join('payment_methods', 'sale_payments.payment_method_id', '=', 'payment_methods.id')
            ->whereIn('sales.cash_reconciliation_id', $reconciliationIds)
            ->where('sales.status', 'completed')
            ->select(
                'payment_methods.name as method_name',
                DB::raw('SUM(sale_payments.amount) as total'),
                DB::raw('COUNT(DISTINCT sales.id) as sale_count')
            )
            ->groupBy('payment_methods.id', 'payment_methods.name')
            ->orderByDesc('total')
            ->get()
            ->toArray();

        // Available = sales + income + credit_payments - gastos - tax
        $available = $totalSales + $totalIncome + $creditPaymentsReceived - $totalGastos - $totalTax;

        $this->reportData = [
            'register_name' => $this->getRegisterLabel(),
            'user_name' => $this->getUserLabel(),
            'total_sales' => round($totalSales, 2),
            'total_income' => round($totalIncome, 2),
            'credit_payments_received' => round($creditPaymentsReceived, 2),
            'total_expenses' => round($totalExpenses, 2),
            'total_refunds' => round($totalRefunds, 2),
            'total_credit_notes' => round($totalCreditNotes, 2),
            'total_gastos' => round($totalGastos, 2),
            'total_tax' => round($totalTax, 2),
            'available_no_tax' => round($available, 2),
            'sales_by_method' => $salesByMethod,
            'reconciliation_count' => $reconciliationIds->count(),
        ];
    }

    private function getRegisterLabel(): string
    {
        if ($this->selectedCashRegisterId) {
            $register = CashRegister::find($this->selectedCashRegisterId);
            if ($register) {
                $userName = $register->user ? $register->user->name : 'Sin asignar';
                return $register->name . ' - ' . $userName;
            }
        }
        return 'Todas las cajas';
    }

    private function getUserLabel(): string
    {
        if ($this->selectedCashRegisterId) {
            $register = CashRegister::with('user')->find($this->selectedCashRegisterId);
            return $register && $register->user ? $register->user->name : 'Sin asignar';
        }
        return 'Todos los usuarios';
    }

    public function clearFilters()
    {
        $this->dateRange = 'month';
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
        $this->selectedCashRegisterId = null;
        $this->viewMode = 'reconciliations';
        if (auth()->user()->isSuperAdmin()) {
            $this->selectedBranchId = null;
            $this->loadCashRegisters();
        }
        $this->resetPage();
    }

    public function render()
    {
        $branches = auth()->user()->isSuperAdmin()
            ? Branch::where('is_active', true)->orderBy('name')->get()
            : collect();

        $detailData = match ($this->viewMode) {
            'reconciliations' => $this->getReconciliations(),
            'movements' => $this->getMovements(),
            default => collect(),
        };

        if ($this->viewMode === 'report') {
            $this->calculateReport();
        }

        return view('livewire.reports.cash-report', [
            'branches' => $branches,
            'isSuperAdmin' => auth()->user()->isSuperAdmin(),
            'detailData' => $detailData,
        ]);
    }
}

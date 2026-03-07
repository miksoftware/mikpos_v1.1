<?php

namespace App\Livewire;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\PaymentMethod;
use App\Models\Supplier;
use App\Services\ActivityLogService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Expenses extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filterPaymentMethod = '';
    public ?string $filterDateFrom = null;
    public ?string $filterDateTo = null;

    // Form
    public bool $isModalOpen = false;
    public ?int $itemId = null;
    #[Rule('required|min:3')]
    public string $description = '';
    #[Rule('required|numeric|min:0.01')]
    public $amount = '';
    public $payment_method_id = '';
    public array $expensePayments = [];
    public $contact_id = '';

    // Delete
    public bool $isDeleteModalOpen = false;
    public ?int $deleteId = null;

    public bool $needsBranchSelection = false;

    public function mount()
    {
        $this->needsBranchSelection = auth()->user()->isSuperAdmin();
    }

    public function create()
    {
        $this->resetForm();
        $this->expensePayments = [['method_id' => '', 'amount' => '']];
        $this->isModalOpen = true;
    }

    public function edit(int $id)
    {
        $expense = Expense::findOrFail($id);
        $this->itemId = $expense->id;
        $this->description = $expense->description;
        $this->amount = $expense->amount;
        $this->contact_id = $expense->contact_type && $expense->contact_id
            ? $expense->contact_type . '_' . $expense->contact_id
            : '';

        // Load payment details
        if ($expense->payment_details && is_array($expense->payment_details) && count($expense->payment_details) > 0) {
            $this->expensePayments = $expense->payment_details;
        } elseif ($expense->payment_method_id) {
            // Legacy: single payment method
            $this->expensePayments = [['method_id' => (string) $expense->payment_method_id, 'amount' => (string) $expense->amount]];
        } else {
            $this->expensePayments = [['method_id' => '', 'amount' => '']];
        }

        $this->isModalOpen = true;
    }

    public function addExpensePayment()
    {
        $this->expensePayments[] = ['method_id' => '', 'amount' => ''];
    }

    public function removeExpensePayment(int $index)
    {
        if (count($this->expensePayments) > 1) {
            array_splice($this->expensePayments, $index, 1);
            $this->expensePayments = array_values($this->expensePayments);
        }
    }

    public function fillRemainingExpensePayment(int $index)
    {
        $total = (float) ($this->amount ?: 0);
        $otherSum = 0;
        foreach ($this->expensePayments as $i => $p) {
            if ($i !== $index) {
                $otherSum += (float) ($p['amount'] ?? 0);
            }
        }
        $remaining = round($total - $otherSum, 2);
        if ($remaining > 0) {
            $this->expensePayments[$index]['amount'] = (string) $remaining;
        }
    }

    public function store()
    {
        $this->validate([
            'description' => 'required|min:3',
            'amount' => 'required|numeric|min:0.01',
        ]);

        // Validate payment methods
        $validPayments = collect($this->expensePayments)
            ->filter(fn($p) => !empty($p['method_id']) && (float) ($p['amount'] ?? 0) > 0);

        if ($validPayments->isEmpty()) {
            $this->dispatch('notify', message: 'Agrega al menos una forma de pago', type: 'error');
            return;
        }

        $paymentSum = $validPayments->sum(fn($p) => (float) $p['amount']);
        $total = (float) $this->amount;

        if (abs($paymentSum - $total) > 0.01) {
            $this->dispatch('notify', message: 'La suma de los pagos ($' . number_format($paymentSum, 2) . ') no coincide con el valor del gasto ($' . number_format($total, 2) . ')', type: 'error');
            return;
        }

        $user = auth()->user();
        $branchId = $user->isSuperAdmin() ? ($user->branch_id ?? Branch::first()?->id) : $user->branch_id;

        // Parse contact selection
        $contactType = null;
        $contactId = null;
        if ($this->contact_id) {
            $parts = explode('_', $this->contact_id, 2);
            if (count($parts) === 2) {
                $contactType = $parts[0];
                $contactId = (int) $parts[1];
            }
        }

        // Build payment details array
        $paymentDetails = $validPayments->map(fn($p) => [
            'method_id' => (int) $p['method_id'],
            'amount' => round((float) $p['amount'], 2),
        ])->values()->toArray();

        // Use first payment method as primary (for backward compatibility)
        $primaryMethodId = $paymentDetails[0]['method_id'] ?? null;

        if ($this->itemId) {
            $expense = Expense::findOrFail($this->itemId);
            $oldValues = $expense->toArray();
            $expense->update([
                'description' => $this->description,
                'amount' => $this->amount,
                'payment_method_id' => $primaryMethodId,
                'payment_details' => $paymentDetails,
                'contact_type' => $contactType,
                'contact_id' => $contactId,
            ]);
            ActivityLogService::logUpdate('expenses', $expense, $oldValues, "Gasto '{$this->description}' actualizado");
            $this->dispatch('notify', message: 'Gasto actualizado correctamente', type: 'success');
        } else {
            $expense = Expense::create([
                'branch_id' => $branchId,
                'user_id' => $user->id,
                'payment_method_id' => $primaryMethodId,
                'payment_details' => $paymentDetails,
                'contact_type' => $contactType,
                'contact_id' => $contactId,
                'description' => $this->description,
                'amount' => $this->amount,
            ]);
            ActivityLogService::logCreate('expenses', $expense, "Gasto '{$this->description}' registrado por \${$this->amount}");
            $this->dispatch('notify', message: 'Gasto registrado correctamente', type: 'success');
        }

        $this->isModalOpen = false;
        $this->resetForm();
    }

    public function confirmDelete(int $id)
    {
        $this->deleteId = $id;
        $this->isDeleteModalOpen = true;
    }

    public function delete()
    {
        $expense = Expense::findOrFail($this->deleteId);
        ActivityLogService::logDelete('expenses', $expense, "Gasto '{$expense->description}' eliminado");
        $expense->delete();

        $this->isDeleteModalOpen = false;
        $this->deleteId = null;
        $this->dispatch('notify', message: 'Gasto eliminado correctamente', type: 'success');
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->filterPaymentMethod = '';
        $this->filterDateFrom = null;
        $this->filterDateTo = null;
        $this->resetPage();
    }

    private function resetForm()
    {
        $this->itemId = null;
        $this->description = '';
        $this->amount = '';
        $this->payment_method_id = '';
        $this->expensePayments = [['method_id' => '', 'amount' => '']];
        $this->contact_id = '';
        $this->resetValidation();
    }

    public function render()
    {
        $user = auth()->user();

        $query = Expense::with(['user', 'paymentMethod', 'branch']);

        if (!$user->isSuperAdmin()) {
            $query->where('branch_id', $user->branch_id);
        }

        $items = $query
            ->when(trim($this->search), function ($q) {
                $q->where('description', 'like', '%' . trim($this->search) . '%');
            })
            ->when($this->filterPaymentMethod, fn($q) => $q->where('payment_method_id', $this->filterPaymentMethod))
            ->when($this->filterDateFrom, fn($q) => $q->whereDate('created_at', '>=', $this->filterDateFrom))
            ->when($this->filterDateTo, fn($q) => $q->whereDate('created_at', '<=', $this->filterDateTo))
            ->latest()
            ->paginate(10);

        $totalFiltered = $query
            ->when(trim($this->search), function ($q) {
                $q->where('description', 'like', '%' . trim($this->search) . '%');
            })
            ->when($this->filterPaymentMethod, fn($q) => $q->where('payment_method_id', $this->filterPaymentMethod))
            ->when($this->filterDateFrom, fn($q) => $q->whereDate('created_at', '>=', $this->filterDateFrom))
            ->when($this->filterDateTo, fn($q) => $q->whereDate('created_at', '<=', $this->filterDateTo))
            ->sum('amount');

        // Build contacts list: suppliers + customers
        $branchId = $user->isSuperAdmin() ? null : $user->branch_id;

        $suppliers = Supplier::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn($s) => ['id' => 'supplier_' . $s->id, 'name' => $s->name . ' (Proveedor)']);

        $customersQuery = Customer::where('is_active', true);
        if ($branchId) {
            $customersQuery->where('branch_id', $branchId);
        }
        $customers = $customersQuery
            ->orderBy('first_name')
            ->get()
            ->map(fn($c) => ['id' => 'customer_' . $c->id, 'name' => $c->full_name . ' (Cliente)']);

        $contacts = $suppliers->concat($customers)->toArray();

        return view('livewire.expenses', [
            'items' => $items,
            'totalFiltered' => $totalFiltered,
            'paymentMethods' => PaymentMethod::where('is_active', true)->orderBy('name')->get(),
            'contacts' => $contacts,
        ]);
    }
}

<?php

namespace App\Livewire;

use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\User;
use App\Services\ActivityLogService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class CashRegisters extends Component
{
    use WithPagination;

    // Search and filters
    public string $search = '';
    public ?string $filterBranch = null;

    // Modal states
    public bool $isModalOpen = false;
    public bool $isDeleteModalOpen = false;
    public ?int $itemIdToDelete = null;

    // Form data
    public ?int $itemId = null;
    public ?int $branch_id = null;
    public ?int $user_id = null;
    public string $name = '';
    public bool $is_active = true;

    // Branch control
    public bool $needsBranchSelection = false;
    public $branches = [];
    public $users = [];

    public function mount()
    {
        $user = auth()->user();
        $this->needsBranchSelection = $user->isSuperAdmin() || !$user->branch_id;
        
        if ($this->needsBranchSelection) {
            $this->branches = Branch::where('is_active', true)->orderBy('name')->get();
        } else {
            // Set default branch and load users
            $this->branch_id = $user->branch_id;
            $this->loadUsersForBranch($this->branch_id);
        }
    }

    public function render()
    {
        $user = auth()->user();
        
        $query = CashRegister::query()
            ->with(['branch', 'user']);

        // Apply branch filter
        if ($this->needsBranchSelection) {
            if ($this->filterBranch) {
                $query->where('branch_id', $this->filterBranch);
            }
        } else {
            $query->where('branch_id', $user->branch_id);
        }

        $items = $query
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('number', 'like', "%{$this->search}%"))
            ->orderBy('number')
            ->paginate(10);

        return view('livewire.cash-registers', [
            'items' => $items,
        ]);
    }

    public function updatedBranchId($value)
    {
        $this->user_id = null;
        $this->loadUsersForBranch($value);
    }

    private function loadUsersForBranch($branchId)
    {
        if ($branchId) {
            $this->users = User::where('branch_id', $branchId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        } else {
            $this->users = [];
        }
    }

    public function create()
    {
        if (!auth()->user()->hasPermission('cash_registers.create')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->resetValidation();
        $this->resetForm();
        
        $user = auth()->user();
        if (!$this->needsBranchSelection && $user->branch_id) {
            $this->branch_id = $user->branch_id;
            $this->loadUsersForBranch($this->branch_id);
        }
        
        $this->isModalOpen = true;
    }

    public function edit(int $id)
    {
        if (!auth()->user()->hasPermission('cash_registers.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->resetValidation();
        
        $item = CashRegister::findOrFail($id);
        
        $this->itemId = $item->id;
        $this->branch_id = $item->branch_id;
        $this->user_id = $item->user_id;
        $this->name = $item->name;
        $this->is_active = $item->is_active;
        
        $this->loadUsersForBranch($this->branch_id);
        
        $this->isModalOpen = true;
    }

    public function store()
    {
        $isNew = !$this->itemId;
        if (!auth()->user()->hasPermission($isNew ? 'cash_registers.create' : 'cash_registers.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $rules = [
            'name' => 'required|min:2',
            'user_id' => 'nullable|exists:users,id',
        ];

        if ($this->needsBranchSelection) {
            $rules['branch_id'] = 'required|exists:branches,id';
        }

        $this->validate($rules, [
            'name.required' => 'El nombre es obligatorio',
            'name.min' => 'El nombre debe tener al menos 2 caracteres',
            'branch_id.required' => 'Debe seleccionar una sucursal',
        ]);

        // Validate that user doesn't have another cash register assigned
        if ($this->user_id) {
            $existingCashRegister = CashRegister::where('user_id', $this->user_id)
                ->when($this->itemId, fn($q) => $q->where('id', '!=', $this->itemId))
                ->first();
            
            if ($existingCashRegister) {
                $this->addError('user_id', 'Este usuario ya tiene asignada la caja "' . $existingCashRegister->name . '"');
                return;
            }
        }

        $branchId = $this->needsBranchSelection ? $this->branch_id : auth()->user()->branch_id;

        $oldValues = $isNew ? null : CashRegister::find($this->itemId)?->toArray();

        $data = [
            'branch_id' => $branchId,
            'user_id' => $this->user_id ?: null,
            'name' => $this->name,
            'is_active' => $this->is_active,
        ];

        // Generate number for new cash registers
        if ($isNew) {
            $data['number'] = CashRegister::generateNumber($branchId);
        }

        $item = CashRegister::updateOrCreate(['id' => $this->itemId], $data);

        $isNew
            ? ActivityLogService::logCreate('cash_registers', $item, "Caja '{$item->name}' creada")
            : ActivityLogService::logUpdate('cash_registers', $item, $oldValues, "Caja '{$item->name}' actualizada");

        $this->isModalOpen = false;
        $this->dispatch('notify', message: $isNew ? 'Caja creada' : 'Caja actualizada');
    }

    public function confirmDelete(int $id)
    {
        if (!auth()->user()->hasPermission('cash_registers.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->itemIdToDelete = $id;
        $this->isDeleteModalOpen = true;
    }

    public function delete()
    {
        if (!auth()->user()->hasPermission('cash_registers.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $item = CashRegister::find($this->itemIdToDelete);
        if (!$item) {
            $this->dispatch('notify', message: 'Caja no encontrada', type: 'error');
            $this->isDeleteModalOpen = false;
            return;
        }

        ActivityLogService::logDelete('cash_registers', $item, "Caja '{$item->name}' eliminada");
        $item->delete();

        $this->isDeleteModalOpen = false;
        $this->dispatch('notify', message: 'Caja eliminada');
    }

    public function toggleStatus(int $id)
    {
        if (!auth()->user()->hasPermission('cash_registers.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $item = CashRegister::find($id);
        if (!$item) {
            return;
        }

        $oldValues = $item->toArray();
        $item->is_active = !$item->is_active;
        $item->save();

        ActivityLogService::logUpdate(
            'cash_registers',
            $item,
            $oldValues,
            "Caja '{$item->name}' " . ($item->is_active ? 'activada' : 'desactivada')
        );

        $this->dispatch('notify', message: $item->is_active ? 'Caja activada' : 'Caja desactivada');
    }

    private function resetForm()
    {
        $this->itemId = null;
        $this->branch_id = null;
        $this->user_id = null;
        $this->name = '';
        $this->is_active = true;
        $this->users = [];
    }
}

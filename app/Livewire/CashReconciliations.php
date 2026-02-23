<?php

namespace App\Livewire;

use App\Models\Branch;
use App\Models\CashMovement;
use App\Models\CashReconciliation;
use App\Models\CashReconciliationEdit;
use App\Models\CashRegister;
use App\Services\ActivityLogService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class CashReconciliations extends Component
{
    use WithPagination;

    // Search and filters
    public string $search = '';
    public ?string $filterBranch = null;
    public ?string $filterStatus = null;

    // Modal states
    public bool $isOpenModalOpen = false;
    public bool $isCloseModalOpen = false;
    public bool $isViewModalOpen = false;
    public bool $isMovementModalOpen = false;
    public bool $isEditModalOpen = false;
    public bool $isHistoryModalOpen = false;

    // Form data for opening
    public ?int $cash_register_id = null;
    public string $opening_amount = '0';
    public string $opening_notes = '';

    // Form data for closing
    public ?int $reconciliationId = null;
    public string $closing_amount = '0';
    public string $closing_notes = '';
    public ?CashReconciliation $currentReconciliation = null;

    // View data
    public ?int $viewReconciliationId = null;

    // Movement form data
    public ?int $movementReconciliationId = null;
    public string $movement_type = 'income';
    public string $movement_amount = '';
    public string $movement_concept = '';
    public string $movement_notes = '';

    // Edit form data
    public ?int $editReconciliationId = null;
    public string $edit_closing_amount = '0';
    public string $edit_closing_notes = '';
    public string $edit_comment = '';

    // History data
    public ?int $historyReconciliationId = null;

    // Branch control
    public bool $needsBranchSelection = false;
    public $branches = [];
    public $cashRegisters = [];
    public ?int $selectedBranchId = null;

    // User's assigned cash register
    public ?CashRegister $userCashRegister = null;
    public bool $hasAssignedCashRegister = false;

    public function mount()
    {
        $user = auth()->user();
        $this->needsBranchSelection = $user->isSuperAdmin() || !$user->branch_id;
        
        // Check if user has an assigned cash register
        $this->userCashRegister = CashRegister::where('user_id', $user->id)
            ->where('is_active', true)
            ->first();
        $this->hasAssignedCashRegister = $this->userCashRegister !== null;
        
        if ($this->needsBranchSelection && !$this->hasAssignedCashRegister) {
            $this->branches = Branch::where('is_active', true)->orderBy('name')->get();
        } elseif (!$this->hasAssignedCashRegister) {
            $this->selectedBranchId = $user->branch_id;
            $this->loadCashRegisters($this->selectedBranchId);
        }
    }

    public function render()
    {
        $user = auth()->user();
        
        $query = CashReconciliation::query()
            ->with(['branch', 'cashRegister', 'openedByUser', 'closedByUser']);

        // Filter by user for non-super admins
        if (!$user->isSuperAdmin()) {
            // Non-super admin users only see their own reconciliations
            $query->where('opened_by', $user->id);
        } elseif ($this->filterBranch) {
            // Super admin can filter by branch
            $query->where('branch_id', $this->filterBranch);
        }

        // Apply status filter
        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        // Apply search
        if (trim($this->search)) {
            $search = trim($this->search);
            $query->whereHas('cashRegister', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('number', 'like', "%{$search}%");
            });
        }

        $items = $query
            ->orderByDesc('opened_at')
            ->paginate(10);

        // Load view reconciliation if modal is open
        $viewReconciliation = null;
        if ($this->isViewModalOpen && $this->viewReconciliationId) {
            $viewReconciliation = CashReconciliation::with(['branch', 'cashRegister', 'openedByUser', 'closedByUser', 'movements.user', 'edits.user'])
                ->find($this->viewReconciliationId);
        }

        // Load history data
        $historyEdits = collect();
        if ($this->isHistoryModalOpen && $this->historyReconciliationId) {
            $historyEdits = CashReconciliationEdit::with('user')
                ->where('cash_reconciliation_id', $this->historyReconciliationId)
                ->orderByDesc('created_at')
                ->get();
        }

        return view('livewire.cash-reconciliations', [
            'items' => $items,
            'viewReconciliation' => $viewReconciliation,
            'historyEdits' => $historyEdits,
        ]);
    }

    public function updatedSelectedBranchId($value)
    {
        $this->cash_register_id = null;
        $this->loadCashRegisters($value);
    }

    private function loadCashRegisters($branchId)
    {
        if ($branchId) {
            $this->cashRegisters = CashRegister::where('branch_id', $branchId)
                ->where('is_active', true)
                ->orderBy('number')
                ->get();
        } else {
            $this->cashRegisters = [];
        }
    }

    public function openCashRegister()
    {
        if (!auth()->user()->hasPermission('cash_reconciliations.create')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->resetValidation();
        $this->resetOpenForm();
        
        // If user has assigned cash register, pre-select it
        if ($this->hasAssignedCashRegister) {
            $this->cash_register_id = $this->userCashRegister->id;
            $this->selectedBranchId = $this->userCashRegister->branch_id;
            
            // Check if already has open reconciliation
            if (CashReconciliation::hasOpenReconciliation($this->cash_register_id)) {
                $this->dispatch('notify', message: 'Tu caja ya tiene un arqueo abierto', type: 'error');
                return;
            }
        } else {
            $user = auth()->user();
            if (!$this->needsBranchSelection && $user->branch_id) {
                $this->selectedBranchId = $user->branch_id;
                $this->loadCashRegisters($this->selectedBranchId);
            }
        }
        
        $this->isOpenModalOpen = true;
    }

    public function storeOpen()
    {
        if (!auth()->user()->hasPermission('cash_reconciliations.create')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $rules = [
            'opening_amount' => 'required|numeric|min:0',
        ];

        // Only validate cash register selection if user doesn't have assigned one
        if (!$this->hasAssignedCashRegister) {
            $rules['cash_register_id'] = 'required|exists:cash_registers,id';
            
            if ($this->needsBranchSelection) {
                $rules['selectedBranchId'] = 'required|exists:branches,id';
            }
        }

        $this->validate($rules, [
            'cash_register_id.required' => 'Debe seleccionar una caja',
            'opening_amount.required' => 'El monto inicial es obligatorio',
            'opening_amount.min' => 'El monto no puede ser negativo',
            'selectedBranchId.required' => 'Debe seleccionar una sucursal',
        ]);

        $cashRegisterId = $this->hasAssignedCashRegister ? $this->userCashRegister->id : $this->cash_register_id;
        $branchId = $this->hasAssignedCashRegister 
            ? $this->userCashRegister->branch_id 
            : ($this->needsBranchSelection ? $this->selectedBranchId : auth()->user()->branch_id);

        // Check if cash register already has an open reconciliation
        if (CashReconciliation::hasOpenReconciliation($cashRegisterId)) {
            $this->dispatch('notify', message: 'Esta caja ya tiene un arqueo abierto', type: 'error');
            return;
        }

        $reconciliation = CashReconciliation::create([
            'branch_id' => $branchId,
            'cash_register_id' => $cashRegisterId,
            'opened_by' => auth()->id(),
            'opening_amount' => $this->opening_amount,
            'opening_notes' => $this->opening_notes ?: null,
            'opened_at' => now(),
            'status' => 'open',
        ]);

        ActivityLogService::logCreate(
            'cash_reconciliations',
            $reconciliation,
            "Caja '{$reconciliation->cashRegister->name}' abierta con monto inicial: " . number_format($this->opening_amount, 2)
        );

        $this->isOpenModalOpen = false;
        $this->dispatch('notify', message: 'Caja abierta correctamente');
    }

    public function closeCashRegister(int $id)
    {
        if (!auth()->user()->hasPermission('cash_reconciliations.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->resetValidation();
        
        $this->currentReconciliation = CashReconciliation::with(['cashRegister', 'openedByUser', 'movements'])->find($id);
        
        if (!$this->currentReconciliation || $this->currentReconciliation->status !== 'open') {
            $this->dispatch('notify', message: 'Este arqueo no está abierto', type: 'error');
            return;
        }

        $this->reconciliationId = $id;
        $this->closing_amount = '0';
        $this->closing_notes = '';
        
        $this->isCloseModalOpen = true;
    }

    public function storeClose()
    {
        if (!auth()->user()->hasPermission('cash_reconciliations.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $this->validate([
            'closing_amount' => 'required|numeric|min:0',
        ], [
            'closing_amount.required' => 'El monto de cierre es obligatorio',
            'closing_amount.min' => 'El monto no puede ser negativo',
        ]);

        $reconciliation = CashReconciliation::find($this->reconciliationId);
        
        if (!$reconciliation || $reconciliation->status !== 'open') {
            $this->dispatch('notify', message: 'Este arqueo no está abierto', type: 'error');
            $this->isCloseModalOpen = false;
            return;
        }

        $oldValues = $reconciliation->toArray();

        // Calculate expected amount using the model method
        $expectedAmount = $reconciliation->calculateExpectedAmount();
        $difference = $this->closing_amount - $expectedAmount;

        $reconciliation->update([
            'closed_by' => auth()->id(),
            'closing_amount' => $this->closing_amount,
            'expected_amount' => $expectedAmount,
            'difference' => $difference,
            'closing_notes' => $this->closing_notes ?: null,
            'closed_at' => now(),
            'status' => 'closed',
        ]);

        ActivityLogService::logUpdate(
            'cash_reconciliations',
            $reconciliation,
            $oldValues,
            "Caja '{$reconciliation->cashRegister->name}' cerrada. Diferencia: " . number_format($difference, 2)
        );

        $this->isCloseModalOpen = false;
        $this->dispatch('notify', message: 'Caja cerrada correctamente');
        $this->dispatch('print-cash-reconciliation', id: $reconciliation->id);
    }

    public function viewReconciliation(int $id)
    {
        $reconciliation = CashReconciliation::with(['branch', 'cashRegister', 'openedByUser', 'closedByUser', 'movements.user'])->find($id);
        
        if (!$reconciliation) {
            $this->dispatch('notify', message: 'Arqueo no encontrado', type: 'error');
            return;
        }
        
        $this->viewReconciliationId = $id;
        $this->isViewModalOpen = true;
    }

    // Cash Movement Methods
    public function openMovementModal(int $reconciliationId)
    {
        if (!auth()->user()->hasPermission('cash_reconciliations.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $reconciliation = CashReconciliation::find($reconciliationId);
        if (!$reconciliation || $reconciliation->status !== 'open') {
            $this->dispatch('notify', message: 'Este arqueo no está abierto', type: 'error');
            return;
        }

        $this->resetValidation();
        $this->movementReconciliationId = $reconciliationId;
        $this->movement_type = 'income';
        $this->movement_amount = '';
        $this->movement_concept = '';
        $this->movement_notes = '';
        
        $this->isMovementModalOpen = true;
    }

    public function storeMovement()
    {
        if (!auth()->user()->hasPermission('cash_reconciliations.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $this->validate([
            'movement_type' => 'required|in:income,expense',
            'movement_amount' => 'required|numeric|min:0.01',
            'movement_concept' => 'required|min:2|max:255',
        ], [
            'movement_type.required' => 'Debe seleccionar el tipo de movimiento',
            'movement_amount.required' => 'El monto es obligatorio',
            'movement_amount.min' => 'El monto debe ser mayor a 0',
            'movement_concept.required' => 'El concepto es obligatorio',
            'movement_concept.min' => 'El concepto debe tener al menos 2 caracteres',
        ]);

        $reconciliation = CashReconciliation::find($this->movementReconciliationId);
        if (!$reconciliation || $reconciliation->status !== 'open') {
            $this->dispatch('notify', message: 'Este arqueo no está abierto', type: 'error');
            $this->isMovementModalOpen = false;
            return;
        }

        $movement = CashMovement::create([
            'cash_reconciliation_id' => $this->movementReconciliationId,
            'user_id' => auth()->id(),
            'type' => $this->movement_type,
            'amount' => $this->movement_amount,
            'concept' => $this->movement_concept,
            'notes' => $this->movement_notes ?: null,
        ]);

        $typeLabel = $this->movement_type === 'income' ? 'Ingreso' : 'Egreso';
        ActivityLogService::logCreate(
            'cash_movements',
            $movement,
            "{$typeLabel} de caja: {$this->movement_concept} - $" . number_format($this->movement_amount, 2)
        );

        $this->isMovementModalOpen = false;
        $this->dispatch('notify', message: "{$typeLabel} registrado correctamente");
    }

    // Edit Methods
    public function openEditModal(int $id)
    {
        if (!auth()->user()->hasPermission('cash_reconciliations.edit_closed')) {
            $this->dispatch('notify', message: 'No tienes permiso para editar arqueos cerrados', type: 'error');
            return;
        }

        $reconciliation = CashReconciliation::find($id);
        if (!$reconciliation || $reconciliation->status !== 'closed') {
            $this->dispatch('notify', message: 'Solo se pueden editar arqueos cerrados', type: 'error');
            return;
        }

        $this->resetValidation();
        $this->editReconciliationId = $id;
        $this->edit_closing_amount = (string) $reconciliation->closing_amount;
        $this->edit_closing_notes = $reconciliation->closing_notes ?? '';
        $this->edit_comment = '';
        $this->isEditModalOpen = true;
    }

    public function storeEdit()
    {
        if (!auth()->user()->hasPermission('cash_reconciliations.edit_closed')) {
            $this->dispatch('notify', message: 'No tienes permiso para editar arqueos cerrados', type: 'error');
            return;
        }

        $this->validate([
            'edit_closing_amount' => 'required|numeric|min:0',
            'edit_comment' => 'required|min:5|max:500',
        ], [
            'edit_closing_amount.required' => 'El monto de cierre es obligatorio',
            'edit_closing_amount.min' => 'El monto no puede ser negativo',
            'edit_comment.required' => 'El comentario es obligatorio',
            'edit_comment.min' => 'El comentario debe tener al menos 5 caracteres',
        ]);

        $reconciliation = CashReconciliation::find($this->editReconciliationId);
        if (!$reconciliation || $reconciliation->status !== 'closed') {
            $this->dispatch('notify', message: 'Arqueo no válido para edición', type: 'error');
            $this->isEditModalOpen = false;
            return;
        }

        $oldClosingAmount = $reconciliation->closing_amount;
        $oldClosingNotes = $reconciliation->closing_notes;
        $changes = [];

        // Track closing amount change
        if ((float) $this->edit_closing_amount !== (float) $oldClosingAmount) {
            $changes[] = [
                'field_changed' => 'closing_amount',
                'old_value' => number_format($oldClosingAmount, 2),
                'new_value' => number_format($this->edit_closing_amount, 2),
            ];
        }

        // Track closing notes change
        if ($this->edit_closing_notes !== ($oldClosingNotes ?? '')) {
            $changes[] = [
                'field_changed' => 'closing_notes',
                'old_value' => $oldClosingNotes,
                'new_value' => $this->edit_closing_notes ?: null,
            ];
        }

        if (empty($changes)) {
            $this->dispatch('notify', message: 'No se detectaron cambios', type: 'warning');
            return;
        }

        // Save edit records
        foreach ($changes as $change) {
            CashReconciliationEdit::create([
                'cash_reconciliation_id' => $reconciliation->id,
                'user_id' => auth()->id(),
                'field_changed' => $change['field_changed'],
                'old_value' => $change['old_value'],
                'new_value' => $change['new_value'],
                'comment' => $this->edit_comment,
            ]);
        }

        // Recalculate expected and difference
        $expectedAmount = $reconciliation->calculateExpectedAmount();
        $difference = (float) $this->edit_closing_amount - $expectedAmount;

        $oldValues = $reconciliation->toArray();

        $reconciliation->update([
            'closing_amount' => $this->edit_closing_amount,
            'closing_notes' => $this->edit_closing_notes ?: null,
            'expected_amount' => $expectedAmount,
            'difference' => $difference,
        ]);

        ActivityLogService::logUpdate(
            'cash_reconciliations',
            $reconciliation,
            $oldValues,
            "Arqueo editado por " . auth()->user()->name . ". Motivo: {$this->edit_comment}"
        );

        $this->isEditModalOpen = false;
        $this->dispatch('notify', message: 'Arqueo actualizado correctamente');
    }

    public function viewHistory(int $id)
    {
        $this->historyReconciliationId = $id;
        $this->isHistoryModalOpen = true;
    }

    private function resetOpenForm()
    {
        $this->cash_register_id = null;
        $this->opening_amount = '0';
        $this->opening_notes = '';
        
        if (!$this->hasAssignedCashRegister && $this->needsBranchSelection) {
            $this->selectedBranchId = null;
            $this->cashRegisters = [];
        }
    }
}

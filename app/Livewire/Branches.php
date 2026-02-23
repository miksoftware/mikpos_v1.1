<?php

namespace App\Livewire;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Municipality;
use App\Services\ActivityLogService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Branches extends Component
{
    use WithPagination;

    public $search = '';
    public $isModalOpen = false;
    public $isDeleteModalOpen = false;
    public $isViewModalOpen = false;
    public $branchIdToDelete = null;
    public $viewingBranch = null;

    // Form properties
    public $branchId;

    #[Rule('required|max:10|unique:branches,code')]
    public $code;

    #[Rule('required|min:3')]
    public $name;

    public $tax_id;
    public $department_id = '';
    public $municipality_id = '';
    public $address;
    public $phone;
    public $email;
    public $ticket_prefix;
    public $invoice_prefix;
    public $receipt_prefix;
    public $credit_note_prefix;
    public $activity_number;
    public $authorization_date;
    public $receipt_header;
    public $show_in_pos = true;
    public $is_active = true;

    // Select options
    public $departments = [];
    public $municipalities = [];

    // Load municipalities when department changes
    public function updatedDepartmentId($value)
    {
        $this->municipality_id = '';
        $this->municipalities = [];
        
        if ($value) {
            $this->municipalities = Municipality::where('department_id', $value)
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(fn($m) => ['id' => $m->id, 'name' => $m->name])
                ->toArray();
        }
    }

    public function render()
    {
        $this->departments = Department::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn($d) => ['id' => $d->id, 'name' => $d->name])
            ->toArray();

        $branches = Branch::query()
            ->with(['department', 'municipality'])
            ->when(trim($this->search), function ($query) {
                $search = trim($this->search);
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                        ->orWhere('code', 'like', '%' . $search . '%')
                        ->orWhereHas('department', fn($dq) => $dq->where('name', 'like', '%' . $search . '%'))
                        ->orWhereHas('municipality', fn($mq) => $mq->where('name', 'like', '%' . $search . '%'));
                });
            })
            ->latest()
            ->paginate(10);

        return view('livewire.branches', [
            'branches' => $branches,
        ]);
    }

    public function create()
    {
        if (!auth()->user()->hasPermission('branches.create')) {
            $this->dispatch('notify', message: 'No tienes permiso para crear sucursales', type: 'error');
            return;
        }

        $this->resetValidation();
        $this->resetForm();
        $this->isModalOpen = true;
    }

    public function edit($id)
    {
        if (!auth()->user()->hasPermission('branches.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso para editar sucursales', type: 'error');
            return;
        }

        $this->resetValidation();
        $branch = Branch::findOrFail($id);
        $this->branchId = $branch->id;
        $this->code = $branch->code;
        $this->name = $branch->name;
        $this->tax_id = $branch->tax_id;
        $this->department_id = $branch->department_id ?? '';
        
        // Load municipalities for the selected department
        if ($this->department_id) {
            $this->municipalities = Municipality::where('department_id', $this->department_id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(fn($m) => ['id' => $m->id, 'name' => $m->name])
                ->toArray();
        }
        
        $this->municipality_id = $branch->municipality_id ?? '';
        $this->address = $branch->address;
        $this->phone = $branch->phone;
        $this->email = $branch->email;
        $this->ticket_prefix = $branch->ticket_prefix;
        $this->invoice_prefix = $branch->invoice_prefix;
        $this->receipt_prefix = $branch->receipt_prefix;
        $this->credit_note_prefix = $branch->credit_note_prefix;
        $this->activity_number = $branch->activity_number;
        $this->authorization_date = $branch->authorization_date?->format('Y-m-d');
        $this->receipt_header = $branch->receipt_header;
        $this->show_in_pos = $branch->show_in_pos;
        $this->is_active = $branch->is_active;
        $this->isModalOpen = true;
    }

    public function view($id)
    {
        $this->viewingBranch = Branch::with(['department', 'municipality'])->withCount('users')->findOrFail($id);
        $this->isViewModalOpen = true;
    }

    public function store()
    {
        $isNew = !$this->branchId;
        $permission = $isNew ? 'branches.create' : 'branches.edit';

        if (!auth()->user()->hasPermission($permission)) {
            $this->dispatch('notify', message: 'No tienes permiso para realizar esta acción', type: 'error');
            return;
        }

        $rules = [
            'code' => 'required|max:10|unique:branches,code,' . $this->branchId,
            'name' => 'required|min:3',
            'tax_id' => 'nullable|max:25',
            'email' => 'nullable|email|max:120',
            'phone' => 'nullable|max:20',
        ];

        $this->validate($rules);

        $data = [
            'code' => strtoupper($this->code),
            'name' => $this->name,
            'tax_id' => $this->tax_id,
            'department_id' => $this->department_id ?: null,
            'municipality_id' => $this->municipality_id ?: null,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'ticket_prefix' => $this->ticket_prefix,
            'invoice_prefix' => $this->invoice_prefix,
            'receipt_prefix' => $this->receipt_prefix,
            'credit_note_prefix' => $this->credit_note_prefix,
            'activity_number' => $this->activity_number,
            'authorization_date' => $this->authorization_date ?: null,
            'receipt_header' => $this->receipt_header,
            'show_in_pos' => $this->show_in_pos,
            'is_active' => $this->is_active,
        ];

        $oldValues = null;

        if (!$isNew) {
            $oldValues = Branch::find($this->branchId)->toArray();
        }

        $branch = Branch::updateOrCreate(['id' => $this->branchId], $data);

        // Log activity
        if ($isNew) {
            ActivityLogService::logCreate('branches', $branch, "Sucursal '{$branch->name}' creada");
        } else {
            ActivityLogService::logUpdate('branches', $branch, $oldValues, "Sucursal '{$branch->name}' actualizada");
        }

        $this->isModalOpen = false;
        $this->dispatch('notify', message: $isNew ? 'Sucursal creada correctamente' : 'Sucursal actualizada correctamente');
    }

    public function confirmDelete($id)
    {
        if (!auth()->user()->hasPermission('branches.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso para desactivar sucursales', type: 'error');
            return;
        }

        $this->branchIdToDelete = $id;
        $this->isDeleteModalOpen = true;
    }

    public function delete()
    {
        if (!auth()->user()->hasPermission('branches.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso para desactivar sucursales', type: 'error');
            return;
        }

        $branch = Branch::find($this->branchIdToDelete);
        $oldValues = $branch->toArray();
        $branch->is_active = false;
        $branch->save();

        ActivityLogService::logUpdate('branches', $branch, $oldValues, "Sucursal '{$branch->name}' desactivada");

        $this->isDeleteModalOpen = false;
        $this->dispatch('notify', message: 'Sucursal desactivada correctamente');
    }

    public function toggleStatus($id)
    {
        if (!auth()->user()->hasPermission('branches.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso para modificar sucursales', type: 'error');
            return;
        }

        $branch = Branch::find($id);
        $oldValues = $branch->toArray();
        $branch->is_active = !$branch->is_active;
        $branch->save();

        $status = $branch->is_active ? 'activada' : 'desactivada';
        ActivityLogService::logUpdate('branches', $branch, $oldValues, "Sucursal '{$branch->name}' {$status}");
    }

    private function resetForm()
    {
        $this->branchId = null;
        $this->code = '';
        $this->name = '';
        $this->tax_id = '';
        $this->department_id = '';
        $this->municipality_id = '';
        $this->municipalities = [];
        $this->address = '';
        $this->phone = '';
        $this->email = '';
        $this->ticket_prefix = '';
        $this->invoice_prefix = '';
        $this->receipt_prefix = '';
        $this->credit_note_prefix = '';
        $this->activity_number = '';
        $this->authorization_date = '';
        $this->receipt_header = '';
        $this->show_in_pos = true;
        $this->is_active = true;
    }
}

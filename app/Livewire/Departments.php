<?php

namespace App\Livewire;

use App\Models\Department;
use App\Services\ActivityLogService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Departments extends Component
{
    use WithPagination;

    public $search = '';
    public $isModalOpen = false;
    public $isDeleteModalOpen = false;
    public $departmentIdToDelete = null;

    public $departmentId;
    public $name;
    public $dian_code;
    public $is_active = true;

    public function render()
    {
        $departments = Department::query()
            ->when(trim($this->search), function ($q) {
                $search = trim($this->search);
                $q->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('dian_code', 'like', "%{$search}%");
                });
            })
            ->withCount('municipalities')
            ->latest()
            ->paginate(10);

        return view('livewire.departments', compact('departments'));
    }

    public function create()
    {
        if (!auth()->user()->hasPermission('departments.create')) {
            $this->dispatch('notify', message: 'No tienes permiso para crear departamentos', type: 'error');
            return;
        }

        $this->resetValidation();
        $this->resetForm();
        $this->isModalOpen = true;
    }

    public function edit($id)
    {
        if (!auth()->user()->hasPermission('departments.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso para editar departamentos', type: 'error');
            return;
        }

        $this->resetValidation();
        $department = Department::findOrFail($id);
        $this->departmentId = $department->id;
        $this->name = $department->name;
        $this->dian_code = $department->dian_code;
        $this->is_active = $department->is_active;
        $this->isModalOpen = true;
    }

    public function store()
    {
        $isNew = !$this->departmentId;
        $permission = $isNew ? 'departments.create' : 'departments.edit';

        if (!auth()->user()->hasPermission($permission)) {
            $this->dispatch('notify', message: 'No tienes permiso para realizar esta acción', type: 'error');
            return;
        }

        $this->validate([
            'name' => 'required|min:2|unique:departments,name,' . $this->departmentId,
            'dian_code' => 'nullable|max:10|unique:departments,dian_code,' . $this->departmentId,
        ]);

        $oldValues = $isNew ? null : Department::find($this->departmentId)->toArray();

        $department = Department::updateOrCreate(
            ['id' => $this->departmentId],
            [
                'name' => $this->name,
                'dian_code' => $this->dian_code,
                'is_active' => $this->is_active,
            ]
        );

        if ($isNew) {
            ActivityLogService::logCreate('departments', $department, "Departamento '{$department->name}' creado");
        } else {
            ActivityLogService::logUpdate('departments', $department, $oldValues, "Departamento '{$department->name}' actualizado");
        }

        $this->isModalOpen = false;
        $this->dispatch('notify', message: $isNew ? 'Departamento creado correctamente' : 'Departamento actualizado correctamente');
    }

    public function confirmDelete($id)
    {
        if (!auth()->user()->hasPermission('departments.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso para eliminar departamentos', type: 'error');
            return;
        }

        $this->departmentIdToDelete = $id;
        $this->isDeleteModalOpen = true;
    }

    public function delete()
    {
        if (!auth()->user()->hasPermission('departments.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso para eliminar departamentos', type: 'error');
            return;
        }

        $department = Department::find($this->departmentIdToDelete);

        if ($department->municipalities()->count() > 0) {
            $this->dispatch('notify', message: 'No se puede eliminar, tiene municipios asociados', type: 'error');
            $this->isDeleteModalOpen = false;
            return;
        }

        ActivityLogService::logDelete('departments', $department, "Departamento '{$department->name}' eliminado");
        $department->delete();

        $this->isDeleteModalOpen = false;
        $this->dispatch('notify', message: 'Departamento eliminado correctamente');
    }

    public function toggleStatus($id)
    {
        if (!auth()->user()->hasPermission('departments.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso para modificar departamentos', type: 'error');
            return;
        }

        $department = Department::find($id);
        $oldValues = $department->toArray();
        $department->is_active = !$department->is_active;
        $department->save();

        $status = $department->is_active ? 'activado' : 'desactivado';
        ActivityLogService::logUpdate('departments', $department, $oldValues, "Departamento '{$department->name}' {$status}");
        $this->dispatch('notify', message: "Departamento {$status} correctamente");
    }

    private function resetForm()
    {
        $this->departmentId = null;
        $this->name = '';
        $this->dian_code = '';
        $this->is_active = true;
    }
}

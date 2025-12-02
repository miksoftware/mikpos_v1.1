<?php

namespace App\Livewire;

use App\Models\Department;
use App\Models\Municipality;
use App\Services\ActivityLogService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Municipalities extends Component
{
    use WithPagination;

    public $search = '';
    public $filterDepartment = '';
    public $isModalOpen = false;
    public $isDeleteModalOpen = false;
    public $municipalityIdToDelete = null;

    public $municipalityId;
    public $department_id;
    public $name;
    public $dian_code;
    public $is_active = true;

    public function render()
    {
        $municipalities = Municipality::query()
            ->with('department')
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('dian_code', 'like', "%{$this->search}%"))
            ->when($this->filterDepartment, fn($q) => $q->where('department_id', $this->filterDepartment))
            ->latest()
            ->paginate(15);

        $departments = Department::where('is_active', true)->orderBy('name')->get();

        return view('livewire.municipalities', compact('municipalities', 'departments'));
    }

    public function create()
    {
        if (!auth()->user()->hasPermission('municipalities.create')) {
            $this->dispatch('notify', message: 'No tienes permiso para crear municipios', type: 'error');
            return;
        }

        $this->resetValidation();
        $this->resetForm();
        $this->isModalOpen = true;
    }

    public function edit($id)
    {
        if (!auth()->user()->hasPermission('municipalities.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso para editar municipios', type: 'error');
            return;
        }

        $this->resetValidation();
        $municipality = Municipality::findOrFail($id);
        $this->municipalityId = $municipality->id;
        $this->department_id = $municipality->department_id;
        $this->name = $municipality->name;
        $this->dian_code = $municipality->dian_code;
        $this->is_active = $municipality->is_active;
        $this->isModalOpen = true;
    }

    public function store()
    {
        $isNew = !$this->municipalityId;
        $permission = $isNew ? 'municipalities.create' : 'municipalities.edit';

        if (!auth()->user()->hasPermission($permission)) {
            $this->dispatch('notify', message: 'No tienes permiso para realizar esta acción', type: 'error');
            return;
        }

        $this->validate([
            'department_id' => 'required|exists:departments,id',
            'name' => 'required|min:2',
            'dian_code' => 'nullable|max:10',
        ]);

        $oldValues = $isNew ? null : Municipality::find($this->municipalityId)->toArray();

        $municipality = Municipality::updateOrCreate(
            ['id' => $this->municipalityId],
            [
                'department_id' => $this->department_id,
                'name' => $this->name,
                'dian_code' => $this->dian_code,
                'is_active' => $this->is_active,
            ]
        );

        if ($isNew) {
            ActivityLogService::logCreate('municipalities', $municipality, "Municipio '{$municipality->name}' creado");
        } else {
            ActivityLogService::logUpdate('municipalities', $municipality, $oldValues, "Municipio '{$municipality->name}' actualizado");
        }

        $this->isModalOpen = false;
        $this->dispatch('notify', message: $isNew ? 'Municipio creado correctamente' : 'Municipio actualizado correctamente');
    }

    public function confirmDelete($id)
    {
        if (!auth()->user()->hasPermission('municipalities.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso para eliminar municipios', type: 'error');
            return;
        }

        $this->municipalityIdToDelete = $id;
        $this->isDeleteModalOpen = true;
    }

    public function delete()
    {
        if (!auth()->user()->hasPermission('municipalities.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso para eliminar municipios', type: 'error');
            return;
        }

        $municipality = Municipality::find($this->municipalityIdToDelete);
        ActivityLogService::logDelete('municipalities', $municipality, "Municipio '{$municipality->name}' eliminado");
        $municipality->delete();

        $this->isDeleteModalOpen = false;
        $this->dispatch('notify', message: 'Municipio eliminado correctamente');
    }

    public function toggleStatus($id)
    {
        if (!auth()->user()->hasPermission('municipalities.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso para modificar municipios', type: 'error');
            return;
        }

        $municipality = Municipality::find($id);
        $oldValues = $municipality->toArray();
        $municipality->is_active = !$municipality->is_active;
        $municipality->save();

        $status = $municipality->is_active ? 'activado' : 'desactivado';
        ActivityLogService::logUpdate('municipalities', $municipality, $oldValues, "Municipio '{$municipality->name}' {$status}");
        $this->dispatch('notify', message: "Municipio {$status} correctamente");
    }

    private function resetForm()
    {
        $this->municipalityId = null;
        $this->department_id = '';
        $this->name = '';
        $this->dian_code = '';
        $this->is_active = true;
    }
}

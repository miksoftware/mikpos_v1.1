<?php

namespace App\Livewire;

use App\Models\Unit;
use App\Services\ActivityLogService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Units extends Component
{
    use WithPagination;

    public $search = '';
    public $isModalOpen = false;
    public $isDeleteModalOpen = false;
    public $itemIdToDelete = null;

    public $itemId;
    public $name;
    public $abbreviation;
    public $is_active = true;

    public function render()
    {
        $items = Unit::query()
            ->when(trim($this->search), function ($q) {
                $search = trim($this->search);
                $q->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('abbreviation', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10);

        return view('livewire.units', ['items' => $items]);
    }

    public function create()
    {
        if (!auth()->user()->hasPermission('units.create')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->resetValidation();
        $this->resetForm();
        $this->isModalOpen = true;
    }

    public function edit($id)
    {
        if (!auth()->user()->hasPermission('units.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->resetValidation();
        $item = Unit::findOrFail($id);
        $this->itemId = $item->id;
        $this->name = $item->name;
        $this->abbreviation = $item->abbreviation;
        $this->is_active = $item->is_active;
        $this->isModalOpen = true;
    }

    public function store()
    {
        $isNew = !$this->itemId;
        if (!auth()->user()->hasPermission($isNew ? 'units.create' : 'units.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $this->validate([
            'name' => 'required|min:2|unique:units,name,' . $this->itemId,
            'abbreviation' => 'required|max:10|unique:units,abbreviation,' . $this->itemId,
        ]);

        $oldValues = $isNew ? null : Unit::find($this->itemId)->toArray();
        $item = Unit::updateOrCreate(['id' => $this->itemId], [
            'name' => $this->name,
            'abbreviation' => strtoupper($this->abbreviation),
            'is_active' => $this->is_active,
        ]);

        $isNew ? ActivityLogService::logCreate('units', $item, "Unidad '{$item->name}' creada")
               : ActivityLogService::logUpdate('units', $item, $oldValues, "Unidad '{$item->name}' actualizada");

        $this->isModalOpen = false;
        $this->dispatch('notify', message: $isNew ? 'Unidad creada' : 'Unidad actualizada');
    }

    public function confirmDelete($id)
    {
        if (!auth()->user()->hasPermission('units.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->itemIdToDelete = $id;
        $this->isDeleteModalOpen = true;
    }

    public function delete()
    {
        if (!auth()->user()->hasPermission('units.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $item = Unit::find($this->itemIdToDelete);
        if (\DB::table('products')->where('unit_id', $item->id)->exists()) {
            $this->dispatch('notify', message: 'No se puede eliminar: tiene productos asociados. Desactívela en su lugar.', type: 'error');
            $this->isDeleteModalOpen = false;
            return;
        }
        ActivityLogService::logDelete('units', $item, "Unidad '{$item->name}' eliminada");
        $item->delete();
        $this->isDeleteModalOpen = false;
        $this->dispatch('notify', message: 'Unidad eliminada');
    }

    public function toggleStatus($id)
    {
        if (!auth()->user()->hasPermission('units.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $item = Unit::find($id);
        $oldValues = $item->toArray();
        $item->is_active = !$item->is_active;
        $item->save();
        ActivityLogService::logUpdate('units', $item, $oldValues, "Unidad '{$item->name}' " . ($item->is_active ? 'activada' : 'desactivada'));
        $this->dispatch('notify', message: $item->is_active ? 'Activada' : 'Desactivada');
    }

    private function resetForm()
    {
        $this->itemId = null;
        $this->name = '';
        $this->abbreviation = '';
        $this->is_active = true;
    }
}

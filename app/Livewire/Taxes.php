<?php

namespace App\Livewire;

use App\Models\Tax;
use App\Services\ActivityLogService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Taxes extends Component
{
    use WithPagination;

    public $search = '';
    public $isModalOpen = false;
    public $isDeleteModalOpen = false;
    public $itemIdToDelete = null;

    public $itemId;
    public $name;
    public $value;
    public $is_active = true;

    public function render()
    {
        $items = Tax::query()
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->latest()
            ->paginate(10);

        return view('livewire.taxes', ['items' => $items]);
    }

    public function create()
    {
        if (!auth()->user()->hasPermission('taxes.create')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->resetValidation();
        $this->resetForm();
        $this->isModalOpen = true;
    }

    public function edit($id)
    {
        if (!auth()->user()->hasPermission('taxes.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->resetValidation();
        $item = Tax::findOrFail($id);
        $this->itemId = $item->id;
        $this->name = $item->name;
        $this->value = $item->value;
        $this->is_active = $item->is_active;
        $this->isModalOpen = true;
    }

    public function store()
    {
        $isNew = !$this->itemId;
        if (!auth()->user()->hasPermission($isNew ? 'taxes.create' : 'taxes.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $this->validate([
            'name' => 'required|min:2|unique:taxes,name,' . $this->itemId,
            'value' => 'required|numeric|min:0|max:100',
        ]);

        $oldValues = $isNew ? null : Tax::find($this->itemId)->toArray();
        $item = Tax::updateOrCreate(['id' => $this->itemId], [
            'name' => $this->name,
            'value' => $this->value,
            'is_active' => $this->is_active,
        ]);

        $isNew ? ActivityLogService::logCreate('taxes', $item, "Impuesto '{$item->name}' creado")
               : ActivityLogService::logUpdate('taxes', $item, $oldValues, "Impuesto '{$item->name}' actualizado");

        $this->isModalOpen = false;
        $this->dispatch('notify', message: $isNew ? 'Impuesto creado' : 'Impuesto actualizado');
    }

    public function confirmDelete($id)
    {
        if (!auth()->user()->hasPermission('taxes.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->itemIdToDelete = $id;
        $this->isDeleteModalOpen = true;
    }

    public function delete()
    {
        if (!auth()->user()->hasPermission('taxes.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $item = Tax::find($this->itemIdToDelete);
        ActivityLogService::logDelete('taxes', $item, "Impuesto '{$item->name}' eliminado");
        $item->delete();
        $this->isDeleteModalOpen = false;
        $this->dispatch('notify', message: 'Impuesto eliminado');
    }

    public function toggleStatus($id)
    {
        if (!auth()->user()->hasPermission('taxes.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $item = Tax::find($id);
        $oldValues = $item->toArray();
        $item->is_active = !$item->is_active;
        $item->save();
        ActivityLogService::logUpdate('taxes', $item, $oldValues, "Impuesto '{$item->name}' " . ($item->is_active ? 'activado' : 'desactivado'));
        $this->dispatch('notify', message: $item->is_active ? 'Activado' : 'Desactivado');
    }

    private function resetForm()
    {
        $this->itemId = null;
        $this->name = '';
        $this->value = '';
        $this->is_active = true;
    }
}

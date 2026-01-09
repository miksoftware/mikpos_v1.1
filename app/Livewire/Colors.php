<?php

namespace App\Livewire;

use App\Models\Color;
use App\Services\ActivityLogService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Colors extends Component
{
    use WithPagination;

    public $search = '';
    public $isModalOpen = false;
    public $isDeleteModalOpen = false;
    public $itemIdToDelete = null;

    public $itemId;
    public $name;
    public $hex_code;
    public $is_active = true;

    public function render()
    {
        $items = Color::query()
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('hex_code', 'like', "%{$this->search}%"))
            ->latest()
            ->paginate(10);

        return view('livewire.colors', ['items' => $items]);
    }

    public function create()
    {
        if (!auth()->user()->hasPermission('colors.create')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->resetValidation();
        $this->resetForm();
        $this->isModalOpen = true;
    }

    public function edit($id)
    {
        if (!auth()->user()->hasPermission('colors.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->resetValidation();
        $item = Color::findOrFail($id);
        $this->itemId = $item->id;
        $this->name = $item->name;
        $this->hex_code = $item->hex_code;
        $this->is_active = $item->is_active;
        $this->isModalOpen = true;
    }

    public function store()
    {
        $isNew = !$this->itemId;
        if (!auth()->user()->hasPermission($isNew ? 'colors.create' : 'colors.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $this->validate([
            'name' => 'required|min:2|unique:colors,name,' . $this->itemId,
            'hex_code' => 'nullable|regex:/^#[0-9A-Fa-f]{3,6}$/',
        ]);

        $oldValues = $isNew ? null : Color::find($this->itemId)->toArray();
        $item = Color::updateOrCreate(['id' => $this->itemId], [
            'name' => $this->name,
            'hex_code' => $this->hex_code ?: null,
            'is_active' => $this->is_active,
        ]);

        $isNew ? ActivityLogService::logCreate('colors', $item, "Color '{$item->name}' creado")
               : ActivityLogService::logUpdate('colors', $item, $oldValues, "Color '{$item->name}' actualizado");

        $this->isModalOpen = false;
        $this->dispatch('notify', message: $isNew ? 'Color creado' : 'Color actualizado');
    }

    public function confirmDelete($id)
    {
        if (!auth()->user()->hasPermission('colors.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->itemIdToDelete = $id;
        $this->isDeleteModalOpen = true;
    }

    public function delete()
    {
        if (!auth()->user()->hasPermission('colors.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $item = Color::find($this->itemIdToDelete);
        ActivityLogService::logDelete('colors', $item, "Color '{$item->name}' eliminado");
        $item->delete();
        $this->isDeleteModalOpen = false;
        $this->dispatch('notify', message: 'Color eliminado');
    }

    public function toggleStatus($id)
    {
        if (!auth()->user()->hasPermission('colors.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $item = Color::find($id);
        $oldValues = $item->toArray();
        $item->is_active = !$item->is_active;
        $item->save();
        ActivityLogService::logUpdate('colors', $item, $oldValues, "Color '{$item->name}' " . ($item->is_active ? 'activado' : 'desactivado'));
        $this->dispatch('notify', message: $item->is_active ? 'Activado' : 'Desactivado');
    }

    private function resetForm()
    {
        $this->itemId = null;
        $this->name = '';
        $this->hex_code = '#000000';
        $this->is_active = true;
    }
}

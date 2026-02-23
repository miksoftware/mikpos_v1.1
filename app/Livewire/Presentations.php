<?php

namespace App\Livewire;

use App\Models\Presentation;
use App\Services\ActivityLogService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Presentations extends Component
{
    use WithPagination;

    public $search = '';
    public $isModalOpen = false;
    public $isDeleteModalOpen = false;
    public $itemIdToDelete = null;

    public $itemId;
    public $name;
    public $description;
    public $is_active = true;

    public function render()
    {
        $items = Presentation::query()
            ->when(trim($this->search), function ($q) {
                $search = trim($this->search);
                $q->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10);

        return view('livewire.presentations', ['items' => $items]);
    }

    public function create()
    {
        if (!auth()->user()->hasPermission('presentations.create')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->resetValidation();
        $this->resetForm();
        $this->isModalOpen = true;
    }

    public function edit($id)
    {
        if (!auth()->user()->hasPermission('presentations.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->resetValidation();
        $item = Presentation::findOrFail($id);
        $this->itemId = $item->id;
        $this->name = $item->name;
        $this->description = $item->description;
        $this->is_active = $item->is_active;
        $this->isModalOpen = true;
    }

    public function store()
    {
        $isNew = !$this->itemId;
        if (!auth()->user()->hasPermission($isNew ? 'presentations.create' : 'presentations.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $this->validate([
            'name' => 'required|min:2|unique:presentations,name,' . $this->itemId,
        ]);

        $oldValues = $isNew ? null : Presentation::find($this->itemId)->toArray();
        $item = Presentation::updateOrCreate(['id' => $this->itemId], [
            'name' => $this->name,
            'description' => $this->description ?: null,
            'is_active' => $this->is_active,
        ]);

        $isNew ? ActivityLogService::logCreate('presentations', $item, "Presentación '{$item->name}' creada")
               : ActivityLogService::logUpdate('presentations', $item, $oldValues, "Presentación '{$item->name}' actualizada");

        $this->isModalOpen = false;
        $this->dispatch('notify', message: $isNew ? 'Presentación creada' : 'Presentación actualizada');
    }

    public function confirmDelete($id)
    {
        if (!auth()->user()->hasPermission('presentations.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->itemIdToDelete = $id;
        $this->isDeleteModalOpen = true;
    }

    public function delete()
    {
        if (!auth()->user()->hasPermission('presentations.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $item = Presentation::find($this->itemIdToDelete);
        ActivityLogService::logDelete('presentations', $item, "Presentación '{$item->name}' eliminada");
        $item->delete();
        $this->isDeleteModalOpen = false;
        $this->dispatch('notify', message: 'Presentación eliminada');
    }

    public function toggleStatus($id)
    {
        if (!auth()->user()->hasPermission('presentations.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $item = Presentation::find($id);
        $oldValues = $item->toArray();
        $item->is_active = !$item->is_active;
        $item->save();
        ActivityLogService::logUpdate('presentations', $item, $oldValues, "Presentación '{$item->name}' " . ($item->is_active ? 'activada' : 'desactivada'));
        $this->dispatch('notify', message: $item->is_active ? 'Activada' : 'Desactivada');
    }

    private function resetForm()
    {
        $this->itemId = null;
        $this->name = '';
        $this->description = '';
        $this->is_active = true;
    }
}

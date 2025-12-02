<?php

namespace App\Livewire;

use App\Models\Category;
use App\Services\ActivityLogService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Categories extends Component
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
        $items = Category::query()
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->withCount('subcategories')
            ->latest()
            ->paginate(10);

        return view('livewire.categories', ['items' => $items]);
    }

    public function create()
    {
        if (!auth()->user()->hasPermission('categories.create')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->resetValidation();
        $this->resetForm();
        $this->isModalOpen = true;
    }

    public function edit($id)
    {
        if (!auth()->user()->hasPermission('categories.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->resetValidation();
        $item = Category::findOrFail($id);
        $this->itemId = $item->id;
        $this->name = $item->name;
        $this->description = $item->description;
        $this->is_active = $item->is_active;
        $this->isModalOpen = true;
    }

    public function store()
    {
        $isNew = !$this->itemId;
        if (!auth()->user()->hasPermission($isNew ? 'categories.create' : 'categories.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $this->validate([
            'name' => 'required|min:2|unique:categories,name,' . $this->itemId,
        ]);

        $oldValues = $isNew ? null : Category::find($this->itemId)->toArray();
        $item = Category::updateOrCreate(['id' => $this->itemId], [
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => $this->is_active,
        ]);

        $isNew ? ActivityLogService::logCreate('categories', $item, "Categoría '{$item->name}' creada")
               : ActivityLogService::logUpdate('categories', $item, $oldValues, "Categoría '{$item->name}' actualizada");

        $this->isModalOpen = false;
        $this->dispatch('notify', message: $isNew ? 'Categoría creada' : 'Categoría actualizada');
    }

    public function confirmDelete($id)
    {
        if (!auth()->user()->hasPermission('categories.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->itemIdToDelete = $id;
        $this->isDeleteModalOpen = true;
    }

    public function delete()
    {
        if (!auth()->user()->hasPermission('categories.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $item = Category::find($this->itemIdToDelete);
        if ($item->subcategories()->count() > 0) {
            $this->dispatch('notify', message: 'No se puede eliminar, tiene subcategorías asociadas', type: 'error');
            $this->isDeleteModalOpen = false;
            return;
        }
        ActivityLogService::logDelete('categories', $item, "Categoría '{$item->name}' eliminada");
        $item->delete();
        $this->isDeleteModalOpen = false;
        $this->dispatch('notify', message: 'Categoría eliminada');
    }

    public function toggleStatus($id)
    {
        if (!auth()->user()->hasPermission('categories.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $item = Category::find($id);
        $oldValues = $item->toArray();
        $item->is_active = !$item->is_active;
        $item->save();
        ActivityLogService::logUpdate('categories', $item, $oldValues, "Categoría '{$item->name}' " . ($item->is_active ? 'activada' : 'desactivada'));
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

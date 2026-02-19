<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Subcategory;
use App\Services\ActivityLogService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Subcategories extends Component
{
    use WithPagination;

    public $search = '';
    public $filterCategory = '';
    public $isModalOpen = false;
    public $isDeleteModalOpen = false;
    public $itemIdToDelete = null;

    public $itemId;
    public $category_id;
    public $name;
    public $description;
    public $is_active = true;

    public function render()
    {
        $items = Subcategory::query()
            ->with('category')
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->when($this->filterCategory, fn($q) => $q->where('category_id', $this->filterCategory))
            ->latest()
            ->paginate(10);

        $categories = Category::where('is_active', true)->orderBy('name')->get();

        return view('livewire.subcategories', ['items' => $items, 'categories' => $categories]);
    }

    public function create()
    {
        if (!auth()->user()->hasPermission('subcategories.create')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->resetValidation();
        $this->resetForm();
        $this->isModalOpen = true;
    }

    public function edit($id)
    {
        if (!auth()->user()->hasPermission('subcategories.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->resetValidation();
        $item = Subcategory::findOrFail($id);
        $this->itemId = $item->id;
        $this->category_id = $item->category_id;
        $this->name = $item->name;
        $this->description = $item->description;
        $this->is_active = $item->is_active;
        $this->isModalOpen = true;
    }

    public function store()
    {
        $isNew = !$this->itemId;
        if (!auth()->user()->hasPermission($isNew ? 'subcategories.create' : 'subcategories.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $this->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|min:2',
        ]);

        $oldValues = $isNew ? null : Subcategory::find($this->itemId)->toArray();
        $item = Subcategory::updateOrCreate(['id' => $this->itemId], [
            'category_id' => $this->category_id,
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => $this->is_active,
        ]);

        $isNew ? ActivityLogService::logCreate('subcategories', $item, "Subcategoría '{$item->name}' creada")
               : ActivityLogService::logUpdate('subcategories', $item, $oldValues, "Subcategoría '{$item->name}' actualizada");

        $this->isModalOpen = false;
        $this->dispatch('notify', message: $isNew ? 'Subcategoría creada' : 'Subcategoría actualizada');
    }

    public function confirmDelete($id)
    {
        if (!auth()->user()->hasPermission('subcategories.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->itemIdToDelete = $id;
        $this->isDeleteModalOpen = true;
    }

    public function delete()
    {
        if (!auth()->user()->hasPermission('subcategories.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $item = Subcategory::find($this->itemIdToDelete);
        if (\DB::table('products')->where('subcategory_id', $item->id)->exists()) {
            $this->dispatch('notify', message: 'No se puede eliminar: tiene productos asociados. Desactívela en su lugar.', type: 'error');
            $this->isDeleteModalOpen = false;
            return;
        }
        ActivityLogService::logDelete('subcategories', $item, "Subcategoría '{$item->name}' eliminada");
        $item->delete();
        $this->isDeleteModalOpen = false;
        $this->dispatch('notify', message: 'Subcategoría eliminada');
    }

    public function toggleStatus($id)
    {
        if (!auth()->user()->hasPermission('subcategories.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $item = Subcategory::find($id);
        $oldValues = $item->toArray();
        $item->is_active = !$item->is_active;
        $item->save();
        ActivityLogService::logUpdate('subcategories', $item, $oldValues, "Subcategoría '{$item->name}' " . ($item->is_active ? 'activada' : 'desactivada'));
        $this->dispatch('notify', message: $item->is_active ? 'Activada' : 'Desactivada');
    }

    private function resetForm()
    {
        $this->itemId = null;
        $this->category_id = '';
        $this->name = '';
        $this->description = '';
        $this->is_active = true;
    }
}

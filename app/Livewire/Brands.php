<?php

namespace App\Livewire;

use App\Models\Brand;
use App\Services\ActivityLogService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Brands extends Component
{
    use WithPagination;

    public $search = '';
    public $isModalOpen = false;
    public $isDeleteModalOpen = false;
    public $itemIdToDelete = null;

    public $itemId;
    public $name;
    public $is_active = true;

    public function render()
    {
        $items = Brand::query()
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->withCount('productModels')
            ->latest()
            ->paginate(10);

        return view('livewire.brands', ['items' => $items]);
    }

    public function create()
    {
        if (!auth()->user()->hasPermission('brands.create')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->resetValidation();
        $this->resetForm();
        $this->isModalOpen = true;
    }

    public function edit($id)
    {
        if (!auth()->user()->hasPermission('brands.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->resetValidation();
        $item = Brand::findOrFail($id);
        $this->itemId = $item->id;
        $this->name = $item->name;
        $this->is_active = $item->is_active;
        $this->isModalOpen = true;
    }

    public function store()
    {
        $isNew = !$this->itemId;
        if (!auth()->user()->hasPermission($isNew ? 'brands.create' : 'brands.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $this->validate([
            'name' => 'required|min:2|unique:brands,name,' . $this->itemId,
        ]);

        $oldValues = $isNew ? null : Brand::find($this->itemId)->toArray();
        $item = Brand::updateOrCreate(['id' => $this->itemId], [
            'name' => $this->name,
            'is_active' => $this->is_active,
        ]);

        $isNew ? ActivityLogService::logCreate('brands', $item, "Marca '{$item->name}' creada")
               : ActivityLogService::logUpdate('brands', $item, $oldValues, "Marca '{$item->name}' actualizada");

        $this->isModalOpen = false;
        $this->dispatch('notify', message: $isNew ? 'Marca creada' : 'Marca actualizada');
    }

    public function confirmDelete($id)
    {
        if (!auth()->user()->hasPermission('brands.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->itemIdToDelete = $id;
        $this->isDeleteModalOpen = true;
    }

    public function delete()
    {
        if (!auth()->user()->hasPermission('brands.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $item = Brand::find($this->itemIdToDelete);
        if ($item->productModels()->count() > 0) {
            $this->dispatch('notify', message: 'No se puede eliminar, tiene modelos asociados', type: 'error');
            $this->isDeleteModalOpen = false;
            return;
        }
        if (\DB::table('products')->where('brand_id', $item->id)->exists()) {
            $this->dispatch('notify', message: 'No se puede eliminar: tiene productos asociados. Desactívela en su lugar.', type: 'error');
            $this->isDeleteModalOpen = false;
            return;
        }
        ActivityLogService::logDelete('brands', $item, "Marca '{$item->name}' eliminada");
        $item->delete();
        $this->isDeleteModalOpen = false;
        $this->dispatch('notify', message: 'Marca eliminada');
    }

    public function toggleStatus($id)
    {
        if (!auth()->user()->hasPermission('brands.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $item = Brand::find($id);
        $oldValues = $item->toArray();
        $item->is_active = !$item->is_active;
        $item->save();
        ActivityLogService::logUpdate('brands', $item, $oldValues, "Marca '{$item->name}' " . ($item->is_active ? 'activada' : 'desactivada'));
        $this->dispatch('notify', message: $item->is_active ? 'Activada' : 'Desactivada');
    }

    private function resetForm()
    {
        $this->itemId = null;
        $this->name = '';
        $this->is_active = true;
    }
}

<?php

namespace App\Livewire;

use App\Models\Brand;
use App\Models\ProductModel;
use App\Services\ActivityLogService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class ProductModels extends Component
{
    use WithPagination;

    public $search = '';
    public $filterBrand = '';
    public $isModalOpen = false;
    public $isDeleteModalOpen = false;
    public $itemIdToDelete = null;

    public $itemId;
    public $brand_id;
    public $name;
    public $description;
    public $is_active = true;

    public function render()
    {
        $items = ProductModel::query()
            ->with('brand')
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->when($this->filterBrand, fn($q) => $q->where('brand_id', $this->filterBrand))
            ->latest()
            ->paginate(10);

        $brands = Brand::where('is_active', true)->orderBy('name')->get();

        return view('livewire.product-models', ['items' => $items, 'brands' => $brands]);
    }

    public function create()
    {
        if (!auth()->user()->hasPermission('product_models.create')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->resetValidation();
        $this->resetForm();
        $this->isModalOpen = true;
    }

    public function edit($id)
    {
        if (!auth()->user()->hasPermission('product_models.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->resetValidation();
        $item = ProductModel::findOrFail($id);
        $this->itemId = $item->id;
        $this->brand_id = $item->brand_id;
        $this->name = $item->name;
        $this->description = $item->description;
        $this->is_active = $item->is_active;
        $this->isModalOpen = true;
    }

    public function store()
    {
        $isNew = !$this->itemId;
        if (!auth()->user()->hasPermission($isNew ? 'product_models.create' : 'product_models.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $this->validate([
            'name' => 'required|min:2',
            'brand_id' => 'nullable|exists:brands,id',
        ]);

        $oldValues = $isNew ? null : ProductModel::find($this->itemId)->toArray();
        $item = ProductModel::updateOrCreate(['id' => $this->itemId], [
            'brand_id' => $this->brand_id ?: null,
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => $this->is_active,
        ]);

        $isNew ? ActivityLogService::logCreate('product_models', $item, "Modelo '{$item->name}' creado")
               : ActivityLogService::logUpdate('product_models', $item, $oldValues, "Modelo '{$item->name}' actualizado");

        $this->isModalOpen = false;
        $this->dispatch('notify', message: $isNew ? 'Modelo creado' : 'Modelo actualizado');
    }

    public function confirmDelete($id)
    {
        if (!auth()->user()->hasPermission('product_models.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->itemIdToDelete = $id;
        $this->isDeleteModalOpen = true;
    }

    public function delete()
    {
        if (!auth()->user()->hasPermission('product_models.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $item = ProductModel::find($this->itemIdToDelete);
        ActivityLogService::logDelete('product_models', $item, "Modelo '{$item->name}' eliminado");
        $item->delete();
        $this->isDeleteModalOpen = false;
        $this->dispatch('notify', message: 'Modelo eliminado');
    }

    public function toggleStatus($id)
    {
        if (!auth()->user()->hasPermission('product_models.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $item = ProductModel::find($id);
        $oldValues = $item->toArray();
        $item->is_active = !$item->is_active;
        $item->save();
        ActivityLogService::logUpdate('product_models', $item, $oldValues, "Modelo '{$item->name}' " . ($item->is_active ? 'activado' : 'desactivado'));
        $this->dispatch('notify', message: $item->is_active ? 'Activado' : 'Desactivado');
    }

    private function resetForm()
    {
        $this->itemId = null;
        $this->brand_id = '';
        $this->name = '';
        $this->description = '';
        $this->is_active = true;
    }
}

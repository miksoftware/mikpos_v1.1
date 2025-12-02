<?php

namespace App\Livewire;

use App\Models\Currency;
use App\Services\ActivityLogService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Currencies extends Component
{
    use WithPagination;

    public $search = '';
    public $isModalOpen = false;
    public $isDeleteModalOpen = false;
    public $itemIdToDelete = null;

    public $itemId;
    public $name;
    public $code;
    public $symbol;
    public $is_active = true;

    public function render()
    {
        $items = Currency::query()
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('code', 'like', "%{$this->search}%"))
            ->latest()
            ->paginate(10);

        return view('livewire.currencies', ['items' => $items]);
    }

    public function create()
    {
        if (!auth()->user()->hasPermission('currencies.create')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->resetValidation();
        $this->resetForm();
        $this->isModalOpen = true;
    }

    public function edit($id)
    {
        if (!auth()->user()->hasPermission('currencies.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->resetValidation();
        $item = Currency::findOrFail($id);
        $this->itemId = $item->id;
        $this->name = $item->name;
        $this->code = $item->code;
        $this->symbol = $item->symbol;
        $this->is_active = $item->is_active;
        $this->isModalOpen = true;
    }

    public function store()
    {
        $isNew = !$this->itemId;
        if (!auth()->user()->hasPermission($isNew ? 'currencies.create' : 'currencies.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $this->validate([
            'name' => 'required|min:2',
            'code' => 'required|max:10|unique:currencies,code,' . $this->itemId,
            'symbol' => 'required|max:5',
        ]);

        $oldValues = $isNew ? null : Currency::find($this->itemId)->toArray();
        $item = Currency::updateOrCreate(['id' => $this->itemId], [
            'name' => $this->name,
            'code' => strtoupper($this->code),
            'symbol' => $this->symbol,
            'is_active' => $this->is_active,
        ]);

        $isNew ? ActivityLogService::logCreate('currencies', $item, "Moneda '{$item->name}' creada")
               : ActivityLogService::logUpdate('currencies', $item, $oldValues, "Moneda '{$item->name}' actualizada");

        $this->isModalOpen = false;
        $this->dispatch('notify', message: $isNew ? 'Moneda creada' : 'Moneda actualizada');
    }

    public function confirmDelete($id)
    {
        if (!auth()->user()->hasPermission('currencies.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->itemIdToDelete = $id;
        $this->isDeleteModalOpen = true;
    }

    public function delete()
    {
        if (!auth()->user()->hasPermission('currencies.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $item = Currency::find($this->itemIdToDelete);
        ActivityLogService::logDelete('currencies', $item, "Moneda '{$item->name}' eliminada");
        $item->delete();
        $this->isDeleteModalOpen = false;
        $this->dispatch('notify', message: 'Moneda eliminada');
    }

    public function toggleStatus($id)
    {
        if (!auth()->user()->hasPermission('currencies.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $item = Currency::find($id);
        $oldValues = $item->toArray();
        $item->is_active = !$item->is_active;
        $item->save();
        ActivityLogService::logUpdate('currencies', $item, $oldValues, "Moneda '{$item->name}' " . ($item->is_active ? 'activada' : 'desactivada'));
        $this->dispatch('notify', message: $item->is_active ? 'Activada' : 'Desactivada');
    }

    private function resetForm()
    {
        $this->itemId = null;
        $this->name = '';
        $this->code = '';
        $this->symbol = '';
        $this->is_active = true;
    }
}

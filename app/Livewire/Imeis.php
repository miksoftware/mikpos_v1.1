<?php

namespace App\Livewire;

use App\Models\Imei;
use App\Services\ActivityLogService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Imeis extends Component
{
    use WithPagination;

    public $search = '';
    public $filterStatus = '';
    public $isModalOpen = false;
    public $isDeleteModalOpen = false;
    public $itemIdToDelete = null;

    public $itemId;
    public $imei;
    public $imei2;
    public $status = 'available';
    public $notes;

    public function render()
    {
        $items = Imei::query()
            ->when($this->search, fn($q) => $q->where('imei', 'like', "%{$this->search}%")->orWhere('imei2', 'like', "%{$this->search}%"))
            ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
            ->latest()
            ->paginate(10);

        return view('livewire.imeis', ['items' => $items]);
    }

    public function create()
    {
        if (!auth()->user()->hasPermission('imeis.create')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->resetValidation();
        $this->resetForm();
        $this->isModalOpen = true;
    }

    public function edit($id)
    {
        if (!auth()->user()->hasPermission('imeis.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->resetValidation();
        $item = Imei::findOrFail($id);
        $this->itemId = $item->id;
        $this->imei = $item->imei;
        $this->imei2 = $item->imei2;
        $this->status = $item->status;
        $this->notes = $item->notes;
        $this->isModalOpen = true;
    }

    public function store()
    {
        $isNew = !$this->itemId;
        if (!auth()->user()->hasPermission($isNew ? 'imeis.create' : 'imeis.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $this->validate([
            'imei' => 'required|digits_between:15,17|unique:imeis,imei,' . $this->itemId,
            'imei2' => 'nullable|digits_between:15,17',
            'status' => 'required|in:available,sold,reserved',
        ]);

        $oldValues = $isNew ? null : Imei::find($this->itemId)->toArray();
        $item = Imei::updateOrCreate(['id' => $this->itemId], [
            'imei' => $this->imei,
            'imei2' => $this->imei2 ?: null,
            'status' => $this->status,
            'notes' => $this->notes,
        ]);

        $isNew ? ActivityLogService::logCreate('imeis', $item, "IMEI '{$item->imei}' creado")
               : ActivityLogService::logUpdate('imeis', $item, $oldValues, "IMEI '{$item->imei}' actualizado");

        $this->isModalOpen = false;
        $this->dispatch('notify', message: $isNew ? 'IMEI creado' : 'IMEI actualizado');
    }

    public function confirmDelete($id)
    {
        if (!auth()->user()->hasPermission('imeis.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->itemIdToDelete = $id;
        $this->isDeleteModalOpen = true;
    }

    public function delete()
    {
        if (!auth()->user()->hasPermission('imeis.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $item = Imei::find($this->itemIdToDelete);
        ActivityLogService::logDelete('imeis', $item, "IMEI '{$item->imei}' eliminado");
        $item->delete();
        $this->isDeleteModalOpen = false;
        $this->dispatch('notify', message: 'IMEI eliminado');
    }

    private function resetForm()
    {
        $this->itemId = null;
        $this->imei = '';
        $this->imei2 = '';
        $this->status = 'available';
        $this->notes = '';
    }
}

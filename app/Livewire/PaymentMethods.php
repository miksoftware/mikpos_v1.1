<?php

namespace App\Livewire;

use App\Models\PaymentMethod;
use App\Services\ActivityLogService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class PaymentMethods extends Component
{
    use WithPagination;

    public $search = '';
    public $isModalOpen = false;
    public $isDeleteModalOpen = false;
    public $itemIdToDelete = null;

    public $itemId;
    public $dian_code;
    public $name;
    public $is_active = true;

    public function render()
    {
        $items = PaymentMethod::query()
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('dian_code', 'like', "%{$this->search}%"))
            ->latest()
            ->paginate(10);

        return view('livewire.payment-methods', ['items' => $items]);
    }

    public function create()
    {
        if (!auth()->user()->hasPermission('payment_methods.create')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->resetValidation();
        $this->resetForm();
        $this->isModalOpen = true;
    }

    public function edit($id)
    {
        if (!auth()->user()->hasPermission('payment_methods.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->resetValidation();
        $item = PaymentMethod::findOrFail($id);
        $this->itemId = $item->id;
        $this->dian_code = $item->dian_code;
        $this->name = $item->name;
        $this->is_active = $item->is_active;
        $this->isModalOpen = true;
    }

    public function store()
    {
        $isNew = !$this->itemId;
        if (!auth()->user()->hasPermission($isNew ? 'payment_methods.create' : 'payment_methods.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $this->validate([
            'dian_code' => 'required|max:10|unique:payment_methods,dian_code,' . $this->itemId,
            'name' => 'required|min:2',
        ]);

        $oldValues = $isNew ? null : PaymentMethod::find($this->itemId)->toArray();
        $item = PaymentMethod::updateOrCreate(['id' => $this->itemId], [
            'dian_code' => $this->dian_code,
            'name' => $this->name,
            'is_active' => $this->is_active,
        ]);

        $isNew ? ActivityLogService::logCreate('payment_methods', $item, "Medio de pago '{$item->name}' creado")
               : ActivityLogService::logUpdate('payment_methods', $item, $oldValues, "Medio de pago '{$item->name}' actualizado");

        $this->isModalOpen = false;
        $this->dispatch('notify', message: $isNew ? 'Medio de pago creado' : 'Medio de pago actualizado');
    }

    public function confirmDelete($id)
    {
        if (!auth()->user()->hasPermission('payment_methods.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->itemIdToDelete = $id;
        $this->isDeleteModalOpen = true;
    }

    public function delete()
    {
        if (!auth()->user()->hasPermission('payment_methods.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $item = PaymentMethod::find($this->itemIdToDelete);
        ActivityLogService::logDelete('payment_methods', $item, "Medio de pago '{$item->name}' eliminado");
        $item->delete();
        $this->isDeleteModalOpen = false;
        $this->dispatch('notify', message: 'Medio de pago eliminado');
    }

    public function toggleStatus($id)
    {
        if (!auth()->user()->hasPermission('payment_methods.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $item = PaymentMethod::find($id);
        $oldValues = $item->toArray();
        $item->is_active = !$item->is_active;
        $item->save();
        ActivityLogService::logUpdate('payment_methods', $item, $oldValues, "Medio de pago '{$item->name}' " . ($item->is_active ? 'activado' : 'desactivado'));
        $this->dispatch('notify', message: $item->is_active ? 'Activado' : 'Desactivado');
    }

    private function resetForm()
    {
        $this->itemId = null;
        $this->dian_code = '';
        $this->name = '';
        $this->is_active = true;
    }
}

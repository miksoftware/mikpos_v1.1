<?php

namespace App\Livewire;

use App\Models\SystemDocument;
use App\Services\ActivityLogService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class SystemDocuments extends Component
{
    use WithPagination;

    public $search = '';
    public $isModalOpen = false;
    public $isDeleteModalOpen = false;
    public $itemIdToDelete = null;

    public $itemId;
    public $code;
    public $name;
    public $prefix;
    public $next_number = 1;
    public $description;
    public $is_active = true;

    public function render()
    {
        $items = SystemDocument::query()
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('code', 'like', "%{$this->search}%")
                ->orWhere('prefix', 'like', "%{$this->search}%"))
            ->latest()
            ->paginate(10);

        return view('livewire.system-documents', ['items' => $items]);
    }

    public function create()
    {
        if (!auth()->user()->hasPermission('system_documents.create')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->resetValidation();
        $this->resetForm();
        $this->isModalOpen = true;
    }

    public function edit($id)
    {
        if (!auth()->user()->hasPermission('system_documents.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->resetValidation();
        $item = SystemDocument::findOrFail($id);
        $this->itemId = $item->id;
        $this->code = $item->code;
        $this->name = $item->name;
        $this->prefix = $item->prefix;
        $this->next_number = $item->next_number;
        $this->description = $item->description;
        $this->is_active = $item->is_active;
        $this->isModalOpen = true;
    }

    public function store()
    {
        $isNew = !$this->itemId;
        if (!auth()->user()->hasPermission($isNew ? 'system_documents.create' : 'system_documents.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $this->validate([
            'code' => 'required|max:20|unique:system_documents,code,' . $this->itemId,
            'name' => 'required|min:2',
            'prefix' => 'required|max:10|unique:system_documents,prefix,' . $this->itemId,
            'next_number' => 'required|integer|min:1',
        ]);

        $oldValues = $isNew ? null : SystemDocument::find($this->itemId)->toArray();
        $item = SystemDocument::updateOrCreate(['id' => $this->itemId], [
            'code' => strtolower($this->code),
            'name' => $this->name,
            'prefix' => strtoupper($this->prefix),
            'next_number' => $this->next_number,
            'description' => $this->description,
            'is_active' => $this->is_active,
        ]);

        $isNew ? ActivityLogService::logCreate('system_documents', $item, "Documento sistema '{$item->name}' creado")
               : ActivityLogService::logUpdate('system_documents', $item, $oldValues, "Documento sistema '{$item->name}' actualizado");

        $this->isModalOpen = false;
        $this->dispatch('notify', message: $isNew ? 'Documento creado' : 'Documento actualizado');
    }

    public function confirmDelete($id)
    {
        if (!auth()->user()->hasPermission('system_documents.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->itemIdToDelete = $id;
        $this->isDeleteModalOpen = true;
    }

    public function delete()
    {
        if (!auth()->user()->hasPermission('system_documents.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $item = SystemDocument::find($this->itemIdToDelete);
        
        // Check if document has movements
        if ($item->inventoryMovements()->exists()) {
            $this->dispatch('notify', message: 'No se puede eliminar, tiene movimientos asociados', type: 'error');
            $this->isDeleteModalOpen = false;
            return;
        }
        
        ActivityLogService::logDelete('system_documents', $item, "Documento sistema '{$item->name}' eliminado");
        $item->delete();
        $this->isDeleteModalOpen = false;
        $this->dispatch('notify', message: 'Documento eliminado');
    }

    public function toggleStatus($id)
    {
        if (!auth()->user()->hasPermission('system_documents.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $item = SystemDocument::find($id);
        $oldValues = $item->toArray();
        $item->is_active = !$item->is_active;
        $item->save();
        ActivityLogService::logUpdate('system_documents', $item, $oldValues, "Documento sistema '{$item->name}' " . ($item->is_active ? 'activado' : 'desactivado'));
        $this->dispatch('notify', message: $item->is_active ? 'Activado' : 'Desactivado');
    }

    private function resetForm()
    {
        $this->itemId = null;
        $this->code = '';
        $this->name = '';
        $this->prefix = '';
        $this->next_number = 1;
        $this->description = '';
        $this->is_active = true;
    }
}

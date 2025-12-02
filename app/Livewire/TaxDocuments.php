<?php

namespace App\Livewire;

use App\Models\TaxDocument;
use App\Services\ActivityLogService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class TaxDocuments extends Component
{
    use WithPagination;

    public $search = '';
    public $isModalOpen = false;
    public $isDeleteModalOpen = false;
    public $itemIdToDelete = null;

    public $itemId;
    public $dian_code;
    public $description;
    public $abbreviation;
    public $is_active = true;

    public function render()
    {
        $items = TaxDocument::query()
            ->when($this->search, fn($q) => $q->where('description', 'like', "%{$this->search}%")
                ->orWhere('dian_code', 'like', "%{$this->search}%")
                ->orWhere('abbreviation', 'like', "%{$this->search}%"))
            ->latest()
            ->paginate(10);

        return view('livewire.tax-documents', ['items' => $items]);
    }

    public function create()
    {
        if (!auth()->user()->hasPermission('tax_documents.create')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->resetValidation();
        $this->resetForm();
        $this->isModalOpen = true;
    }

    public function edit($id)
    {
        if (!auth()->user()->hasPermission('tax_documents.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->resetValidation();
        $item = TaxDocument::findOrFail($id);
        $this->itemId = $item->id;
        $this->dian_code = $item->dian_code;
        $this->description = $item->description;
        $this->abbreviation = $item->abbreviation;
        $this->is_active = $item->is_active;
        $this->isModalOpen = true;
    }

    public function store()
    {
        $isNew = !$this->itemId;
        if (!auth()->user()->hasPermission($isNew ? 'tax_documents.create' : 'tax_documents.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $this->validate([
            'dian_code' => 'required|max:10|unique:tax_documents,dian_code,' . $this->itemId,
            'description' => 'required|min:2',
            'abbreviation' => 'required|max:20',
        ]);

        $oldValues = $isNew ? null : TaxDocument::find($this->itemId)->toArray();
        $item = TaxDocument::updateOrCreate(['id' => $this->itemId], [
            'dian_code' => $this->dian_code,
            'description' => $this->description,
            'abbreviation' => strtoupper($this->abbreviation),
            'is_active' => $this->is_active,
        ]);

        $isNew ? ActivityLogService::logCreate('tax_documents', $item, "Documento '{$item->description}' creado")
               : ActivityLogService::logUpdate('tax_documents', $item, $oldValues, "Documento '{$item->description}' actualizado");

        $this->isModalOpen = false;
        $this->dispatch('notify', message: $isNew ? 'Documento creado' : 'Documento actualizado');
    }

    public function confirmDelete($id)
    {
        if (!auth()->user()->hasPermission('tax_documents.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->itemIdToDelete = $id;
        $this->isDeleteModalOpen = true;
    }

    public function delete()
    {
        if (!auth()->user()->hasPermission('tax_documents.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $item = TaxDocument::find($this->itemIdToDelete);
        ActivityLogService::logDelete('tax_documents', $item, "Documento '{$item->description}' eliminado");
        $item->delete();
        $this->isDeleteModalOpen = false;
        $this->dispatch('notify', message: 'Documento eliminado');
    }

    public function toggleStatus($id)
    {
        if (!auth()->user()->hasPermission('tax_documents.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $item = TaxDocument::find($id);
        $oldValues = $item->toArray();
        $item->is_active = !$item->is_active;
        $item->save();
        ActivityLogService::logUpdate('tax_documents', $item, $oldValues, "Documento '{$item->description}' " . ($item->is_active ? 'activado' : 'desactivado'));
        $this->dispatch('notify', message: $item->is_active ? 'Activado' : 'Desactivado');
    }

    private function resetForm()
    {
        $this->itemId = null;
        $this->dian_code = '';
        $this->description = '';
        $this->abbreviation = '';
        $this->is_active = true;
    }
}

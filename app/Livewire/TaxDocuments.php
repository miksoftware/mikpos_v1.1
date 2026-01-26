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

    /**
     * DIAN document types with descriptions.
     * These are the official IDs for electronic invoicing in Colombia (Factus/DIAN).
     */
    public static array $dianDocumentTypes = [
        '3' => [
            'name' => 'Cédula de Ciudadanía',
            'abbreviation' => 'CC',
            'description' => 'Documento de identidad para ciudadanos colombianos mayores de edad',
            'icon' => 'identification',
        ],
        '2' => [
            'name' => 'Tarjeta de Identidad',
            'abbreviation' => 'TI',
            'description' => 'Documento de identidad para menores de edad colombianos',
            'icon' => 'identification',
        ],
        '6' => [
            'name' => 'NIT',
            'abbreviation' => 'NIT',
            'description' => 'Número de Identificación Tributaria para empresas y personas jurídicas',
            'icon' => 'building-office',
        ],
        '5' => [
            'name' => 'Cédula de Extranjería',
            'abbreviation' => 'CE',
            'description' => 'Documento para extranjeros residentes en Colombia',
            'icon' => 'globe-alt',
        ],
        '7' => [
            'name' => 'Pasaporte',
            'abbreviation' => 'PA',
            'description' => 'Documento de viaje internacional',
            'icon' => 'document',
        ],
        '9' => [
            'name' => 'PEP',
            'abbreviation' => 'PEP',
            'description' => 'Permiso Especial de Permanencia para venezolanos',
            'icon' => 'document-check',
        ],
        '1' => [
            'name' => 'Registro Civil',
            'abbreviation' => 'RC',
            'description' => 'Registro civil de nacimiento',
            'icon' => 'document-text',
        ],
        '4' => [
            'name' => 'Tarjeta de Extranjería',
            'abbreviation' => 'TE',
            'description' => 'Documento temporal para extranjeros',
            'icon' => 'globe-alt',
        ],
        '8' => [
            'name' => 'Documento de Identificación Extranjero',
            'abbreviation' => 'DIE',
            'description' => 'Documento de identificación emitido en otro país',
            'icon' => 'globe-americas',
        ],
        '10' => [
            'name' => 'NIT de Otro País',
            'abbreviation' => 'NIT-E',
            'description' => 'Número de identificación tributaria extranjero',
            'icon' => 'building-office-2',
        ],
        '11' => [
            'name' => 'NUIP',
            'abbreviation' => 'NUIP',
            'description' => 'Número Único de Identificación Personal',
            'icon' => 'finger-print',
        ],
    ];

    public function getDianDocumentTypes(): array
    {
        return self::$dianDocumentTypes;
    }

    public function getSelectedDianType(): ?array
    {
        if ($this->dian_code && isset(self::$dianDocumentTypes[$this->dian_code])) {
            return self::$dianDocumentTypes[$this->dian_code];
        }
        return null;
    }

    public function updatedDianCode($value)
    {
        // Auto-fill description and abbreviation when DIAN code is selected
        if ($value && isset(self::$dianDocumentTypes[$value])) {
            $type = self::$dianDocumentTypes[$value];
            if (empty($this->description)) {
                $this->description = $type['name'];
            }
            if (empty($this->abbreviation)) {
                $this->abbreviation = $type['abbreviation'];
            }
        }
    }

    public function render()
    {
        $items = TaxDocument::query()
            ->when($this->search, fn($q) => $q->where('description', 'like', "%{$this->search}%")
                ->orWhere('dian_code', 'like', "%{$this->search}%")
                ->orWhere('abbreviation', 'like', "%{$this->search}%"))
            ->latest()
            ->paginate(10);

        return view('livewire.tax-documents', [
            'items' => $items,
            'dianTypes' => self::$dianDocumentTypes,
        ]);
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
            'dian_code' => 'required|in:' . implode(',', array_keys(self::$dianDocumentTypes)),
            'description' => 'required|min:2',
            'abbreviation' => 'required|max:20',
        ], [
            'dian_code.required' => 'Selecciona un tipo de documento DIAN',
            'dian_code.in' => 'Tipo de documento DIAN inválido',
            'description.required' => 'La descripción es obligatoria',
            'description.min' => 'La descripción debe tener al menos 2 caracteres',
            'abbreviation.required' => 'La abreviación es obligatoria',
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

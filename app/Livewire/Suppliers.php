<?php

namespace App\Livewire;

use App\Models\Department;
use App\Models\Municipality;
use App\Models\Supplier;
use App\Models\TaxDocument;
use App\Services\ActivityLogService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Suppliers extends Component
{
    use WithPagination;

    public $search = '';
    public $isModalOpen = false;
    public $isDeleteModalOpen = false;
    public $itemIdToDelete = null;

    // Form properties
    public $itemId;
    public $tax_document_id;
    public $document_number;
    public $name;
    public $phone;
    public $email;
    public $department_id;
    public $municipality_id;
    public $address;
    public $salesperson_name;
    public $salesperson_phone;
    public $is_active = true;

    // Data collections
    public $municipalities = [];

    public function render()
    {
        $items = Supplier::query()
            ->with(['taxDocument', 'department', 'municipality'])
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('document_number', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")
                ->orWhere('salesperson_name', 'like', "%{$this->search}%"))
            ->latest()
            ->paginate(10);

        $taxDocuments = TaxDocument::where('is_active', true)->orderBy('description')->get();
        
        $departments = Department::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn($d) => ['id' => $d->id, 'name' => $d->name])
            ->toArray();

        return view('livewire.suppliers', [
            'items' => $items,
            'taxDocuments' => $taxDocuments,
            'departments' => $departments,
        ]);
    }

    public function updatedDepartmentId()
    {
        $this->municipality_id = '';
        $this->municipalities = $this->department_id 
            ? Municipality::where('department_id', $this->department_id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(fn($m) => ['id' => $m->id, 'name' => $m->name])
                ->toArray()
            : [];
    }

    public function create()
    {
        if (!auth()->user()->hasPermission('suppliers.create')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->resetValidation();
        $this->resetForm();
        $this->isModalOpen = true;
    }

    public function edit($id)
    {
        if (!auth()->user()->hasPermission('suppliers.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->resetValidation();
        $item = Supplier::findOrFail($id);
        
        $this->itemId = $item->id;
        $this->tax_document_id = $item->tax_document_id;
        $this->document_number = $item->document_number;
        $this->name = $item->name;
        $this->phone = $item->phone;
        $this->email = $item->email;
        $this->department_id = $item->department_id;
        $this->address = $item->address;
        $this->salesperson_name = $item->salesperson_name;
        $this->salesperson_phone = $item->salesperson_phone;
        $this->is_active = $item->is_active;

        // Load municipalities for selected department
        if ($this->department_id) {
            $this->municipalities = Municipality::where('department_id', $this->department_id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(fn($m) => ['id' => $m->id, 'name' => $m->name])
                ->toArray();
        }
        
        // Set municipality after loading the list
        $this->municipality_id = $item->municipality_id;
        
        $this->isModalOpen = true;
    }

    public function store()
    {
        $isNew = !$this->itemId;
        if (!auth()->user()->hasPermission($isNew ? 'suppliers.create' : 'suppliers.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $rules = [
            'tax_document_id' => 'required|exists:tax_documents,id',
            'document_number' => 'required|string|unique:suppliers,document_number,' . $this->itemId,
            'name' => 'required|string|min:2',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'department_id' => 'required|exists:departments,id',
            'municipality_id' => 'required|exists:municipalities,id',
            'address' => 'required|string|min:5',
            'salesperson_name' => 'nullable|string',
            'salesperson_phone' => 'nullable|string',
        ];

        $this->validate($rules);

        $oldValues = $isNew ? null : Supplier::find($this->itemId)->toArray();
        $item = Supplier::updateOrCreate(['id' => $this->itemId], [
            'tax_document_id' => $this->tax_document_id,
            'document_number' => $this->document_number,
            'name' => $this->name,
            'phone' => $this->phone ?: null,
            'email' => $this->email ?: null,
            'department_id' => $this->department_id,
            'municipality_id' => $this->municipality_id,
            'address' => $this->address,
            'salesperson_name' => $this->salesperson_name ?: null,
            'salesperson_phone' => $this->salesperson_phone ?: null,
            'is_active' => $this->is_active,
        ]);

        $isNew ? ActivityLogService::logCreate('suppliers', $item, "Proveedor '{$item->name}' creado")
               : ActivityLogService::logUpdate('suppliers', $item, $oldValues, "Proveedor '{$item->name}' actualizado");

        $this->isModalOpen = false;
        $this->dispatch('notify', message: $isNew ? 'Proveedor creado' : 'Proveedor actualizado');
    }

    public function confirmDelete($id)
    {
        if (!auth()->user()->hasPermission('suppliers.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->itemIdToDelete = $id;
        $this->isDeleteModalOpen = true;
    }

    public function delete()
    {
        if (!auth()->user()->hasPermission('suppliers.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $item = Supplier::find($this->itemIdToDelete);
        ActivityLogService::logDelete('suppliers', $item, "Proveedor '{$item->name}' eliminado");
        $item->delete();
        $this->isDeleteModalOpen = false;
        $this->dispatch('notify', message: 'Proveedor eliminado');
    }

    public function toggleStatus($id)
    {
        if (!auth()->user()->hasPermission('suppliers.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $item = Supplier::find($id);
        $oldValues = $item->toArray();
        $item->is_active = !$item->is_active;
        $item->save();
        ActivityLogService::logUpdate('suppliers', $item, $oldValues, "Proveedor '{$item->name}' " . ($item->is_active ? 'activado' : 'desactivado'));
        $this->dispatch('notify', message: $item->is_active ? 'Activado' : 'Desactivado');
    }

    private function resetForm()
    {
        $this->itemId = null;
        $this->tax_document_id = '';
        $this->document_number = '';
        $this->name = '';
        $this->phone = '';
        $this->email = '';
        $this->department_id = '';
        $this->municipality_id = '';
        $this->address = '';
        $this->salesperson_name = '';
        $this->salesperson_phone = '';
        $this->is_active = true;
        $this->municipalities = [];
    }
}

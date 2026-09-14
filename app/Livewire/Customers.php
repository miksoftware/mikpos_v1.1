<?php

namespace App\Livewire;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Municipality;
use App\Models\TaxDocument;
use App\Services\ActivityLogService;
use App\Services\CustomerImportService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Customers extends Component
{
    use WithPagination, WithFileUploads;

    public $search = '';
    public $filterCustomerType = '';
    public $filterBranch = '';
    public $filterSeller = '';
    public $isModalOpen = false;
    public $isDeleteModalOpen = false;
    public $itemIdToDelete = null;

    // Import properties
    public $importFile;
    public $importBranchId;
    public $isImportModalOpen = false;
    public $importResults = null;

    // Form properties
    public $itemId;
    public $branch_id;
    public $seller_id = null;
    public $customer_type = 'natural';
    public $tax_document_id;
    public $document_number;
    public $dv = '';
    public $first_name;
    public $last_name;
    public $business_name;
    public $phone;
    public $email;
    public $department_id;
    public $municipality_id;
    public $address;
    public $has_credit = false;
    public $credit_limit;
    public $is_active = true;
    public $is_default = false;

    // Data collections
    public $municipalities = [];

    // Branch control
    public bool $needsBranchSelection = false;
    public $branches = [];

    public function mount()
    {
        $user = auth()->user();
        // User needs branch selection if they are super_admin or have no branch assigned
        $this->needsBranchSelection = $user->isSuperAdmin() || !$user->branch_id;
        
        if ($this->needsBranchSelection) {
            $this->branches = Branch::where('is_active', true)->orderBy('name')->get();
        }
    }

    public function render()
    {
        $user = auth()->user();
        
        $query = Customer::query()
            ->with(['taxDocument', 'department', 'municipality', 'branch', 'seller']);

        // Apply branch filter
        if ($this->needsBranchSelection) {
            // Super admin or user without branch - can filter by branch
            if ($this->filterBranch) {
                $query->where('branch_id', $this->filterBranch);
            }
        } else {
            // Regular user - only see their branch's customers
            $query->where('branch_id', $user->branch_id);
        }

        $items = $query
            ->when(trim($this->search), function ($q) {
                $search = trim($this->search);
                $q->where(function ($query) use ($search) {
                    $query->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('business_name', 'like', "%{$search}%")
                        ->orWhere('document_number', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                    // Search full name across first_name + last_name
                    if (str_contains($search, ' ')) {
                        $query->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
                    }
                });
            })
            ->when($this->filterCustomerType, fn($q) => $q->where('customer_type', $this->filterCustomerType))
            ->when($this->filterSeller, fn($q) => $q->where('seller_id', $this->filterSeller))
            ->latest()
            ->paginate(10);

        $taxDocuments = TaxDocument::where('is_active', true)->orderBy('description')->get();
        
        $departments = Department::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn($d) => ['id' => $d->id, 'name' => $d->name])
            ->toArray();

        // Load active sellers
        $sellersQuery = \App\Models\User::where('is_active', true);
        if (!$this->needsBranchSelection && $user->branch_id) {
            $sellersQuery->where(function ($sq) use ($user) {
                $sq->where('branch_id', $user->branch_id)->orWhereNull('branch_id');
            });
        }
        $sellers = $sellersQuery->orderBy('name')->get();

        return view('livewire.customers', [
            'items' => $items,
            'taxDocuments' => $taxDocuments,
            'departments' => $departments,
            'sellers' => $sellers,
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
                ->toArray()
            : [];
    }

    public function updatedHasCredit()
    {
        if (!$this->has_credit) {
            $this->credit_limit = null;
        }
    }

    public function getIsNitProperty(): bool
    {
        if (!$this->tax_document_id) {
            return false;
        }

        $doc = TaxDocument::find($this->tax_document_id);
        if (!$doc) {
            return false;
        }

        return strtoupper((string) $doc->abbreviation) === 'NIT'
            || $doc->dian_code === '6'
            || $doc->dian_code === '31'
            || str_contains(strtoupper((string) $doc->description), 'NIT');
    }

    public function calculateDV(?string $nit): ?string
    {
        if (!$nit) {
            return null;
        }

        $clean = preg_replace('/[^0-9]/', '', $nit);
        if (empty($clean)) {
            return null;
        }

        $primes = [3, 7, 13, 17, 19, 23, 29, 37, 41, 43, 47, 53, 59, 67, 71];
        $sum = 0;
        $nitLength = strlen($clean);

        for ($i = 0; $i < $nitLength; $i++) {
            $sum += (int) $clean[$nitLength - 1 - $i] * $primes[$i];
        }

        $remainder = $sum % 11;
        if ($remainder > 1) {
            return (string) (11 - $remainder);
        }

        return (string) $remainder;
    }

    public function updatedTaxDocumentId($value)
    {
        if ($this->isNit) {
            if ($this->document_number && ($this->dv === null || $this->dv === '')) {
                $this->dv = $this->calculateDV($this->document_number) ?? '';
            }
        } else {
            $this->dv = '';
        }
    }

    public function updatedDocumentNumber($value)
    {
        if ($this->isNit && ($this->dv === null || $this->dv === '')) {
            $this->dv = $this->calculateDV($value) ?? '';
        }
    }

    public function create()
    {
        if (!auth()->user()->hasPermission('customers.create')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->resetValidation();
        $this->resetForm();
        
        // Set default branch for users with assigned branch
        $user = auth()->user();
        if (!$this->needsBranchSelection && $user->branch_id) {
            $this->branch_id = $user->branch_id;
        }
        
        $this->isModalOpen = true;
    }

    public function edit($id)
    {
        if (!auth()->user()->hasPermission('customers.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->resetValidation();
        $item = Customer::findOrFail($id);
        
        $this->itemId = $item->id;
        $this->branch_id = $item->branch_id;
        $this->seller_id = $item->seller_id;
        $this->customer_type = $item->customer_type;
        $this->tax_document_id = $item->tax_document_id;
        $this->document_number = $item->document_number;
        $this->dv = $item->dv ?? ($this->isNit ? $this->calculateDV($item->document_number) : '');
        $this->first_name = $item->first_name;
        $this->last_name = $item->last_name;
        $this->business_name = $item->business_name;
        $this->phone = $item->phone;
        $this->email = $item->email;
        $this->department_id = $item->department_id;
        $this->municipality_id = $item->municipality_id;
        $this->address = $item->address;
        $this->has_credit = $item->has_credit;
        $this->credit_limit = $item->credit_limit;
        $this->is_active = $item->is_active;
        $this->is_default = $item->is_default;

        // Load municipalities for selected department
        if ($this->department_id) {
            $this->municipalities = Municipality::where('department_id', $this->department_id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->toArray();
        }
        
        $this->isModalOpen = true;
    }

    public function store()
    {
        $isNew = !$this->itemId;
        if (!auth()->user()->hasPermission($isNew ? 'customers.create' : 'customers.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $rules = [
            'seller_id' => 'nullable|exists:users,id',
            'customer_type' => 'required|in:natural,juridico,exonerado',
            'tax_document_id' => 'required|exists:tax_documents,id',
            'document_number' => [
                'required',
                'string',
                Rule::unique('customers', 'document_number')
                    ->where(fn ($query) => $this->branch_id ? $query->where('branch_id', $this->branch_id) : $query)
                    ->ignore($this->itemId),
            ],
            'dv' => $this->isNit ? 'required|digits:1' : 'nullable|digits:1',
            'first_name' => 'required|string|min:2',
            'last_name' => 'required|string|min:2',
            'business_name' => $this->customer_type === 'juridico' ? 'required|string|min:2' : 'nullable|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'department_id' => 'required|exists:departments,id',
            'municipality_id' => 'required|exists:municipalities,id',
            'address' => 'required|string|min:5',
            'has_credit' => 'boolean',
            'credit_limit' => $this->has_credit ? 'required|numeric|min:0' : 'nullable|numeric|min:0',
        ];

        // Branch is required for super_admin or users without branch
        if ($this->needsBranchSelection) {
            $rules['branch_id'] = 'required|exists:branches,id';
        }

        $this->validate($rules, [
            'branch_id.required' => 'Debe seleccionar una sucursal',
            'dv.required' => 'El dígito de verificación (DV) es obligatorio para NIT',
            'dv.digits' => 'El DV debe ser de un solo dígito (0-9)',
        ]);

        // If setting as default, remove default from other customers
        if ($this->is_default) {
            Customer::where('is_default', true)->update(['is_default' => false]);
        }

        // Determine branch_id
        $branchId = $this->needsBranchSelection ? $this->branch_id : auth()->user()->branch_id;

        $oldValues = $isNew ? null : Customer::find($this->itemId)->toArray();
        $item = Customer::updateOrCreate(['id' => $this->itemId], [
            'branch_id' => $branchId,
            'seller_id' => $this->seller_id ?: null,
            'customer_type' => $this->customer_type,
            'tax_document_id' => $this->tax_document_id,
            'document_number' => $this->document_number,
            'dv' => $this->isNit ? $this->dv : null,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'business_name' => $this->business_name ?: null,
            'phone' => $this->phone ?: null,
            'email' => $this->email ?: null,
            'department_id' => $this->department_id,
            'municipality_id' => $this->municipality_id,
            'address' => $this->address,
            'has_credit' => $this->has_credit,
            'credit_limit' => $this->has_credit ? $this->credit_limit : null,
            'is_active' => $this->is_active,
            'is_default' => $this->is_default,
        ]);

        $isNew ? ActivityLogService::logCreate('customers', $item, "Cliente '{$item->full_name}' creado")
               : ActivityLogService::logUpdate('customers', $item, $oldValues, "Cliente '{$item->full_name}' actualizado");

        $this->isModalOpen = false;
        $this->dispatch('notify', message: $isNew ? 'Cliente creado' : 'Cliente actualizado');
    }

    public function confirmDelete($id)
    {
        if (!auth()->user()->hasPermission('customers.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->itemIdToDelete = $id;
        $this->isDeleteModalOpen = true;
    }

    public function delete()
    {
        if (!auth()->user()->hasPermission('customers.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $item = Customer::find($this->itemIdToDelete);

        // Check for associated sales
        if (DB::table('sales')->where('customer_id', $item->id)->exists()) {
            $this->dispatch('notify', message: 'No se puede eliminar: tiene ventas asociadas. Desactívelo en su lugar.', type: 'error');
            $this->isDeleteModalOpen = false;
            return;
        }

        // Check for credit payments
        if (DB::table('credit_payments')->where('customer_id', $item->id)->exists()) {
            $this->dispatch('notify', message: 'No se puede eliminar: tiene pagos de crédito asociados.', type: 'error');
            $this->isDeleteModalOpen = false;
            return;
        }

        ActivityLogService::logDelete('customers', $item, "Cliente '{$item->full_name}' eliminado");
        $item->delete();
        $this->isDeleteModalOpen = false;
        $this->dispatch('notify', message: 'Cliente eliminado');
    }

    public function toggleStatus($id)
    {
        if (!auth()->user()->hasPermission('customers.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $item = Customer::find($id);
        $oldValues = $item->toArray();
        $item->is_active = !$item->is_active;
        $item->save();
        ActivityLogService::logUpdate('customers', $item, $oldValues, "Cliente '{$item->full_name}' " . ($item->is_active ? 'activado' : 'desactivado'));
        $this->dispatch('notify', message: $item->is_active ? 'Activado' : 'Desactivado');
    }

    public function openImportModal()
    {
        if (!auth()->user()->hasPermission('customers.create')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->resetValidation();
        $this->importFile = null;
        $this->importResults = null;
        $this->importBranchId = $this->needsBranchSelection ? '' : auth()->user()->branch_id;
        $this->isImportModalOpen = true;
    }

    public function closeImportModal()
    {
        $this->isImportModalOpen = false;
        $this->importFile = null;
        $this->importResults = null;
    }

    public function importCustomers(CustomerImportService $importService)
    {
        if (!auth()->user()->hasPermission('customers.create')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $rules = [
            'importFile' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
        ];

        if ($this->needsBranchSelection) {
            $rules['importBranchId'] = 'required|exists:branches,id';
        }

        $this->validate($rules, [
            'importFile.required' => 'Debes seleccionar un archivo Excel o CSV',
            'importFile.mimes' => 'El archivo debe tener extensión .xlsx, .xls o .csv',
            'importBranchId.required' => 'Debe seleccionar una sucursal para la importación',
        ]);

        $path = $this->importFile->getRealPath();
        $targetBranchId = $this->needsBranchSelection ? (int) $this->importBranchId : auth()->user()->branch_id;

        $results = $importService->import($path, $targetBranchId);
        $this->importResults = $results;

        if (($results['created'] ?? 0) > 0 || ($results['updated'] ?? 0) > 0) {
            $msg = "Importación finalizada: {$results['created']} creados, {$results['updated']} actualizados.";
            $this->dispatch('notify', message: $msg, type: 'success');
        } elseif (empty($results['errors'])) {
            $this->dispatch('notify', message: 'No se procesaron registros del archivo.', type: 'info');
        } else {
            $this->dispatch('notify', message: 'Ocurrieron errores al procesar el archivo.', type: 'error');
        }
    }

    private function resetForm()
    {
        $this->itemId = null;
        $this->branch_id = '';
        $this->seller_id = null;
        $this->customer_type = 'natural';
        $this->tax_document_id = '';
        $this->document_number = '';
        $this->dv = '';
        $this->first_name = '';
        $this->last_name = '';
        $this->business_name = '';
        $this->phone = '';
        $this->email = '';
        $this->department_id = '';
        $this->municipality_id = '';
        $this->address = '';
        $this->has_credit = false;
        $this->credit_limit = null;
        $this->is_active = true;
        $this->is_default = false;
        $this->municipalities = [];
    }
}
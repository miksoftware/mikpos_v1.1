<?php

namespace App\Livewire;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Municipality;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\CashReconciliation;
use App\Models\CreditPayment;
use App\Models\Expense;
use App\Models\InventoryMovement;
use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\ProductChild;
use App\Models\ProductBarcode;
use App\Models\Customer;
use App\Models\Supplier;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Branches extends Component
{
    use WithPagination;
    use WithFileUploads;

    public $search = '';
    public $isModalOpen = false;
    public $isDeleteModalOpen = false;
    public $isViewModalOpen = false;
    public $isCleanModalOpen = false;
    public $branchIdToDelete = null;
    public $branchIdToClean = null;
    public $cleanConfirmText = '';
    public $viewingBranch = null;

    // Copy Modal properties (Products, Customers, Suppliers)
    public bool $isCopyModalOpen = false;
    public $copyFromBranchId = '';
    public $copyToBranchId = '';

    // Entity selection toggles
    public bool $copyProducts = true;
    public bool $copyCustomers = false;
    public bool $copySuppliers = false;

    // Product options
    public string $copyFilter = 'all'; // 'all', 'active_only'
    public string $copyDuplicateHandling = 'skip'; // 'skip', 'overwrite'
    public string $copyStockMode = 'zero'; // 'zero', 'copy'
    public bool $copyVariants = true;
    public int $copySourceCount = 0;
    public int $copyTargetCount = 0;
    public int $copySourceProductsCount = 0;
    public int $copyTargetProductsCount = 0;

    // Customer options
    public string $copyCustomerFilter = 'all'; // 'all', 'active_only'
    public string $copyCustomerDuplicateHandling = 'skip'; // 'skip', 'overwrite'
    public int $copySourceCustomersCount = 0;
    public int $copyTargetCustomersCount = 0;

    // Supplier options
    public string $copySupplierFilter = 'all'; // 'all', 'active_only'
    public string $copySupplierDuplicateHandling = 'skip'; // 'skip', 'overwrite'
    public int $copySourceSuppliersCount = 0;
    public int $copyTargetSuppliersCount = 0;

    // Form properties
    public $branchId;

    #[Rule('required|max:10|unique:branches,code')]
    public $code;

    #[Rule('required|min:3')]
    public $name;

    public $tax_id;
    public $department_id = '';
    public $municipality_id = '';
    public $address;
    public $phone;
    public $email;
    public $ticket_prefix;
    public $invoice_prefix;
    public $receipt_prefix;
    public $credit_note_prefix;
    public $activity_number;
    public $authorization_date;
    public $receipt_header;
    public $show_in_pos = true;
    public $ecommerce_enabled = false;
    public $show_stock_in_shop = false;
    public $quotes_reserve_inventory = false;
    public $print_qr = false;
    public $enable_costo_fe = false;
    public $tax_exempt_preserves_price = false;
    public $is_active = true;

    // Logo upload
    public $logo = null;
    public ?string $existingLogo = null;

    // Select options
    public $departments = [];
    public $municipalities = [];

    // Load municipalities when department changes
    public function updatedDepartmentId($value)
    {
        $this->municipality_id = '';
        $this->municipalities = [];
        
        if ($value) {
            $this->municipalities = Municipality::where('department_id', $value)
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(fn($m) => ['id' => $m->id, 'name' => $m->name])
                ->toArray();
        }
    }

    public function render()
    {
        $this->departments = Department::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn($d) => ['id' => $d->id, 'name' => $d->name])
            ->toArray();

        $branches = Branch::query()
            ->with(['department', 'municipality'])
            ->when(trim($this->search), function ($query) {
                $search = trim($this->search);
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                        ->orWhere('code', 'like', '%' . $search . '%')
                        ->orWhereHas('department', fn($dq) => $dq->where('name', 'like', '%' . $search . '%'))
                        ->orWhereHas('municipality', fn($mq) => $mq->where('name', 'like', '%' . $search . '%'));
                });
            })
            ->latest()
            ->paginate(10);

        $allBranches = Branch::where('is_active', true)->orderBy('name')->get();

        return view('livewire.branches', [
            'branches' => $branches,
            'allBranches' => $allBranches,
        ]);
    }

    public function create()
    {
        if (!auth()->user()->hasPermission('branches.create')) {
            $this->dispatch('notify', message: 'No tienes permiso para crear sucursales', type: 'error');
            return;
        }

        $this->resetValidation();
        $this->resetForm();
        $this->isModalOpen = true;
    }

    public function edit($id)
    {
        if (!auth()->user()->hasPermission('branches.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso para editar sucursales', type: 'error');
            return;
        }

        $this->resetValidation();
        $branch = Branch::findOrFail($id);
        $this->branchId = $branch->id;
        $this->code = $branch->code;
        $this->name = $branch->name;
        $this->tax_id = $branch->tax_id;
        $this->department_id = $branch->department_id ?? '';
        
        // Load municipalities for the selected department
        if ($this->department_id) {
            $this->municipalities = Municipality::where('department_id', $this->department_id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(fn($m) => ['id' => $m->id, 'name' => $m->name])
                ->toArray();
        }
        
        $this->municipality_id = $branch->municipality_id ?? '';
        $this->address = $branch->address;
        $this->phone = $branch->phone;
        $this->email = $branch->email;
        $this->ticket_prefix = $branch->ticket_prefix;
        $this->invoice_prefix = $branch->invoice_prefix;
        $this->receipt_prefix = $branch->receipt_prefix;
        $this->credit_note_prefix = $branch->credit_note_prefix;
        $this->activity_number = $branch->activity_number;
        $this->authorization_date = $branch->authorization_date?->format('Y-m-d');
        $this->receipt_header = $branch->receipt_header;
        $this->show_in_pos = $branch->show_in_pos;
        $this->ecommerce_enabled = $branch->ecommerce_enabled;
        $this->show_stock_in_shop = $branch->show_stock_in_shop;
        $this->quotes_reserve_inventory = $branch->quotes_reserve_inventory;
        $this->print_qr = $branch->print_qr ?? false;
        $this->enable_costo_fe = $branch->enable_costo_fe ?? false;
        $this->tax_exempt_preserves_price = $branch->tax_exempt_preserves_price ?? false;
        $this->is_active = $branch->is_active;
        $this->existingLogo = $branch->logo;
        $this->logo = null;
        $this->isModalOpen = true;
    }

    public function view($id)
    {
        $this->viewingBranch = Branch::with(['department', 'municipality'])->withCount('users')->findOrFail($id);
        $this->isViewModalOpen = true;
    }

    public function store()
    {
        $isNew = !$this->branchId;
        $permission = $isNew ? 'branches.create' : 'branches.edit';

        if (!auth()->user()->hasPermission($permission)) {
            $this->dispatch('notify', message: 'No tienes permiso para realizar esta acción', type: 'error');
            return;
        }

        $rules = [
            'code' => 'required|max:10|unique:branches,code,' . $this->branchId,
            'name' => 'required|min:3',
            'tax_id' => 'nullable|max:25',
            'email' => 'nullable|email|max:120',
            'phone' => 'nullable|max:20',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp,svg|max:2048',
        ];

        $this->validate($rules);

        // Handle logo upload
        $logoPath = $this->existingLogo;
        if ($this->logo) {
            if ($this->existingLogo && Storage::disk('public')->exists($this->existingLogo)) {
                Storage::disk('public')->delete($this->existingLogo);
            }
            $logoPath = $this->logo->store('branches', 'public');
        }

        $data = [
            'code' => strtoupper($this->code),
            'name' => $this->name,
            'logo' => $logoPath,
            'tax_id' => $this->tax_id,
            'department_id' => $this->department_id ?: null,
            'municipality_id' => $this->municipality_id ?: null,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'ticket_prefix' => $this->ticket_prefix,
            'invoice_prefix' => $this->invoice_prefix,
            'receipt_prefix' => $this->receipt_prefix,
            'credit_note_prefix' => $this->credit_note_prefix,
            'activity_number' => $this->activity_number,
            'authorization_date' => $this->authorization_date ?: null,
            'receipt_header' => $this->receipt_header,
            'show_in_pos' => $this->show_in_pos,
            'ecommerce_enabled' => $this->ecommerce_enabled,
            'show_stock_in_shop' => $this->show_stock_in_shop,
            'quotes_reserve_inventory' => $this->quotes_reserve_inventory,
            'print_qr' => $this->print_qr,
            'enable_costo_fe' => $this->enable_costo_fe,
            'tax_exempt_preserves_price' => $this->tax_exempt_preserves_price,
            'is_active' => $this->is_active,
        ];

        $oldValues = null;

        if (!$isNew) {
            $oldValues = Branch::find($this->branchId)->toArray();
        }

        $branch = Branch::updateOrCreate(['id' => $this->branchId], $data);

        // When enabling ecommerce, mark all branch products as show_in_shop
        if ($this->ecommerce_enabled && (!$oldValues || !($oldValues['ecommerce_enabled'] ?? false))) {
            Product::where('branch_id', $branch->id)->update(['show_in_shop' => true]);
            ProductChild::whereHas('product', fn($q) => $q->where('branch_id', $branch->id))
                ->update(['show_in_shop' => true]);
        }

        // Log activity
        if ($isNew) {
            ActivityLogService::logCreate('branches', $branch, "Sucursal '{$branch->name}' creada");
        } else {
            ActivityLogService::logUpdate('branches', $branch, $oldValues, "Sucursal '{$branch->name}' actualizada");
        }

        $this->isModalOpen = false;
        $this->dispatch('notify', message: $isNew ? 'Sucursal creada correctamente' : 'Sucursal actualizada correctamente');
    }

    public function confirmDelete($id)
    {
        if (!auth()->user()->hasPermission('branches.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso para desactivar sucursales', type: 'error');
            return;
        }

        $this->branchIdToDelete = $id;
        $this->isDeleteModalOpen = true;
    }

    public function delete()
    {
        if (!auth()->user()->hasPermission('branches.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso para desactivar sucursales', type: 'error');
            return;
        }

        $branch = Branch::find($this->branchIdToDelete);
        $oldValues = $branch->toArray();
        $branch->is_active = false;
        $branch->save();

        ActivityLogService::logUpdate('branches', $branch, $oldValues, "Sucursal '{$branch->name}' desactivada");

        $this->isDeleteModalOpen = false;
        $this->dispatch('notify', message: 'Sucursal desactivada correctamente');
    }

    public function toggleStatus($id)
    {
        if (!auth()->user()->hasPermission('branches.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso para modificar sucursales', type: 'error');
            return;
        }

        $branch = Branch::find($id);
        $oldValues = $branch->toArray();
        $branch->is_active = !$branch->is_active;
        $branch->save();

        $status = $branch->is_active ? 'activada' : 'desactivada';
        ActivityLogService::logUpdate('branches', $branch, $oldValues, "Sucursal '{$branch->name}' {$status}");
    }

    public function removeLogo()
    {
        if ($this->existingLogo && Storage::disk('public')->exists($this->existingLogo)) {
            Storage::disk('public')->delete($this->existingLogo);
        }
        $this->existingLogo = null;
        $this->logo = null;
    }

    public function confirmClean($id)
    {
        if (!str_contains(auth()->user()->email ?? '', 'softwaremik')) {
            return;
        }

        $this->branchIdToClean = $id;
        $this->cleanConfirmText = '';
        $this->isCleanModalOpen = true;
    }

    public function cleanBranch()
    {
        if (!str_contains(auth()->user()->email ?? '', 'softwaremik')) {
            return;
        }

        if ($this->cleanConfirmText !== 'LIMPIAR') {
            $this->dispatch('notify', message: 'Debes escribir LIMPIAR para confirmar', type: 'error');
            return;
        }

        $branch = Branch::find($this->branchIdToClean);
        if (!$branch) {
            $this->isCleanModalOpen = false;
            return;
        }

        DB::beginTransaction();
        try {
            $branchId = $branch->id;

            // 1. Delete credit payments for this branch
            CreditPayment::where('branch_id', $branchId)->delete();

            // 2. Delete sales (cascades: sale_items, sale_payments, sale_reprints, credit_notes, credit_note_items, refunds, refund_items, ecommerce_orders)
            Sale::where('branch_id', $branchId)->delete();

            // 3. Delete purchases and purchase_items (cascade)
            Purchase::where('branch_id', $branchId)->delete();

            // 4. Delete inventory movements for this branch
            InventoryMovement::where('branch_id', $branchId)->delete();

            // 5. Delete cash reconciliations (cascades: cash_movements, cash_reconciliation_edits)
            CashReconciliation::where('branch_id', $branchId)->delete();

            // 6. Delete expenses
            Expense::where('branch_id', $branchId)->delete();

            // 7. Reset product stock to 0 (only for products that manage inventory)
            Product::where('branch_id', $branchId)
                ->where('manages_inventory', true)
                ->update(['current_stock' => 0]);

            // 8. Delete activity logs for this branch
            ActivityLog::where('branch_id', $branchId)->delete();

            DB::commit();

            $this->isCleanModalOpen = false;
            $this->cleanConfirmText = '';
            $this->dispatch('notify', message: "Sucursal '{$branch->name}' limpiada correctamente. Todos los datos transaccionales fueron eliminados.");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('notify', message: 'Error al limpiar: ' . $e->getMessage(), type: 'error');
        }
    }

    public function openCopyModal(?int $sourceBranchId = null)
    {
        if (!auth()->user()->hasPermission('branches.copy_products')) {
            $this->dispatch('notify', message: 'No tienes permiso para copiar datos entre sucursales', type: 'error');
            return;
        }

        $this->resetValidation();
        $this->copyFromBranchId = $sourceBranchId ? (string) $sourceBranchId : '';
        $this->copyToBranchId = '';
        $this->copyProducts = true;
        $this->copyCustomers = false;
        $this->copySuppliers = false;
        $this->copyFilter = 'all';
        $this->copyDuplicateHandling = 'skip';
        $this->copyStockMode = 'zero';
        $this->copyVariants = true;
        $this->copyCustomerFilter = 'all';
        $this->copyCustomerDuplicateHandling = 'skip';
        $this->copySupplierFilter = 'all';
        $this->copySupplierDuplicateHandling = 'skip';
        $this->updateCopyCounts();
        $this->isCopyModalOpen = true;
    }

    public function closeCopyModal()
    {
        $this->isCopyModalOpen = false;
        $this->resetValidation();
    }

    public function updatedCopyFromBranchId()
    {
        $this->updateCopyCounts();
    }

    public function updatedCopyToBranchId()
    {
        $this->updateCopyCounts();
    }

    public function updatedCopyProducts()
    {
        $this->updateCopyCounts();
    }

    public function updatedCopyCustomers()
    {
        $this->updateCopyCounts();
    }

    public function updatedCopySuppliers()
    {
        $this->updateCopyCounts();
    }

    public function updatedCopyFilter()
    {
        $this->updateCopyCounts();
    }

    public function updatedCopyCustomerFilter()
    {
        $this->updateCopyCounts();
    }

    public function updatedCopySupplierFilter()
    {
        $this->updateCopyCounts();
    }

    private function updateCopyCounts()
    {
        // Products count
        if ($this->copyFromBranchId) {
            $query = Product::where('branch_id', $this->copyFromBranchId);
            if ($this->copyFilter === 'active_only') {
                $query->where('is_active', true);
            }
            $this->copySourceProductsCount = $query->count();
        } else {
            $this->copySourceProductsCount = 0;
        }

        if ($this->copyToBranchId) {
            $this->copyTargetProductsCount = Product::where('branch_id', $this->copyToBranchId)->count();
        } else {
            $this->copyTargetProductsCount = 0;
        }
        $this->copySourceCount = $this->copySourceProductsCount;
        $this->copyTargetCount = $this->copyTargetProductsCount;

        // Customers count
        if ($this->copyFromBranchId) {
            $hasBranchCust = Customer::where('branch_id', $this->copyFromBranchId)->exists();
            $query = Customer::query();
            if ($hasBranchCust) {
                $query->where('branch_id', $this->copyFromBranchId);
            } else {
                $query->where(function($q) {
                    $q->where('branch_id', $this->copyFromBranchId)->orWhereNull('branch_id');
                });
            }
            if ($this->copyCustomerFilter === 'active_only') {
                $query->where('is_active', true);
            }
            $this->copySourceCustomersCount = $query->count();
        } else {
            $this->copySourceCustomersCount = 0;
        }

        if ($this->copyToBranchId) {
            $this->copyTargetCustomersCount = Customer::where('branch_id', $this->copyToBranchId)->count();
        } else {
            $this->copyTargetCustomersCount = 0;
        }

        // Suppliers count
        if ($this->copyFromBranchId) {
            $hasBranchSupp = Supplier::where('branch_id', $this->copyFromBranchId)->exists();
            $query = Supplier::query();
            if ($hasBranchSupp) {
                $query->where('branch_id', $this->copyFromBranchId);
            } else {
                $query->where(function($q) {
                    $q->where('branch_id', $this->copyFromBranchId)->orWhereNull('branch_id');
                });
            }
            if ($this->copySupplierFilter === 'active_only') {
                $query->where('is_active', true);
            }
            $this->copySourceSuppliersCount = $query->count();
        } else {
            $this->copySourceSuppliersCount = 0;
        }

        if ($this->copyToBranchId) {
            $this->copyTargetSuppliersCount = Supplier::where('branch_id', $this->copyToBranchId)->count();
        } else {
            $this->copyTargetSuppliersCount = 0;
        }
    }

    public function executeCopyProducts()
    {
        if (!auth()->user()->hasPermission('branches.copy_products')) {
            $this->dispatch('notify', message: 'No tienes permiso para copiar datos entre sucursales', type: 'error');
            return;
        }

        if (!$this->copyProducts && !$this->copyCustomers && !$this->copySuppliers) {
            $this->dispatch('notify', message: 'Debes seleccionar al menos un tipo de elemento a copiar (Productos, Clientes o Proveedores)', type: 'warning');
            return;
        }

        $this->copyFromBranchId = $this->copyFromBranchId !== '' ? (string) $this->copyFromBranchId : '';
        $this->copyToBranchId = $this->copyToBranchId !== '' ? (string) $this->copyToBranchId : '';

        $rules = [
            'copyFromBranchId' => 'required|exists:branches,id',
            'copyToBranchId' => 'required|exists:branches,id|different:copyFromBranchId',
        ];

        if ($this->copyProducts) {
            $rules['copyFilter'] = 'required|in:all,active_only';
            $rules['copyDuplicateHandling'] = 'required|in:skip,overwrite';
            $rules['copyStockMode'] = 'required|in:zero,copy';
        }

        if ($this->copyCustomers) {
            $rules['copyCustomerFilter'] = 'required|in:all,active_only';
            $rules['copyCustomerDuplicateHandling'] = 'required|in:skip,overwrite';
        }

        if ($this->copySuppliers) {
            $rules['copySupplierFilter'] = 'required|in:all,active_only';
            $rules['copySupplierDuplicateHandling'] = 'required|in:skip,overwrite';
        }

        $this->validate($rules, [
            'copyFromBranchId.required' => 'Selecciona la sucursal de origen',
            'copyFromBranchId.exists' => 'La sucursal de origen no existe',
            'copyToBranchId.required' => 'Selecciona la sucursal de destino',
            'copyToBranchId.exists' => 'La sucursal de destino no existe',
            'copyToBranchId.different' => 'La sucursal de destino debe ser diferente a la de origen',
        ]);

        $fromBranch = Branch::find($this->copyFromBranchId);
        $toBranch = Branch::find($this->copyToBranchId);

        if (!$fromBranch || !$toBranch) {
            $this->dispatch('notify', message: 'Sucursales no válidas', type: 'error');
            return;
        }

        $copiedCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;
        $variantsCount = 0;

        $copiedCustomersCount = 0;
        $updatedCustomersCount = 0;
        $skippedCustomersCount = 0;

        $copiedSuppliersCount = 0;
        $updatedSuppliersCount = 0;
        $skippedSuppliersCount = 0;

        DB::beginTransaction();
        try {
            // 1. COPIAR PRODUCTOS
            if ($this->copyProducts) {
                $sourceProductsQuery = Product::where('branch_id', $fromBranch->id)
                    ->with(['children.barcodes', 'barcodes']);

                if ($this->copyFilter === 'active_only') {
                    $sourceProductsQuery->where('is_active', true);
                }

                $sourceProducts = $sourceProductsQuery->get();

                $initialStockDoc = null;
                if ($this->copyStockMode === 'copy') {
                    $initialStockDoc = \App\Models\SystemDocument::findByCode('initial_stock');
                }

                foreach ($sourceProducts as $sourceProduct) {
                    $existingProduct = Product::where('branch_id', $toBranch->id)
                        ->where('name', $sourceProduct->name)
                        ->first();

                    if ($existingProduct) {
                        if ($this->copyDuplicateHandling === 'skip') {
                            $skippedCount++;
                            continue;
                        }

                        // Overwrite mode: update fields of existing product
                        $stockToSet = $this->copyStockMode === 'copy' ? (float) $sourceProduct->current_stock : (float) $existingProduct->current_stock;

                        $existingProduct->update([
                            'type' => $sourceProduct->type ?? Product::TYPE_STANDARD,
                            'barcode' => $sourceProduct->barcode,
                            'description' => $sourceProduct->description,
                            'category_id' => $sourceProduct->category_id,
                            'subcategory_id' => $sourceProduct->subcategory_id,
                            'brand_id' => $sourceProduct->brand_id,
                            'unit_id' => $sourceProduct->unit_id,
                            'tax_id' => $sourceProduct->tax_id,
                            'purchase_price' => $sourceProduct->purchase_price,
                            'average_cost' => $sourceProduct->average_cost,
                            'sale_price' => $sourceProduct->sale_price,
                            'special_price' => $sourceProduct->special_price,
                            'suggested_price' => $sourceProduct->suggested_price,
                            'price_includes_tax' => $sourceProduct->price_includes_tax,
                            'min_stock' => $sourceProduct->min_stock,
                            'max_stock' => $sourceProduct->max_stock,
                            'current_stock' => $stockToSet,
                            'is_active' => $sourceProduct->is_active,
                            'manages_inventory' => $sourceProduct->manages_inventory,
                            'show_in_shop' => $sourceProduct->show_in_shop,
                            'show_in_pos' => $sourceProduct->show_in_pos,
                            'has_commission' => $sourceProduct->has_commission,
                            'commission_type' => $sourceProduct->commission_type,
                            'commission_value' => $sourceProduct->commission_value,
                            'image' => $sourceProduct->image,
                            'presentation_id' => $sourceProduct->presentation_id,
                            'color_id' => $sourceProduct->color_id,
                            'product_model_id' => $sourceProduct->product_model_id,
                            'size' => $sourceProduct->size,
                            'weight' => $sourceProduct->weight,
                            'import_code' => $sourceProduct->import_code,
                            'import_declaration' => $sourceProduct->import_declaration,
                        ]);

                        // Sync product barcodes
                        if ($sourceProduct->barcodes->isNotEmpty()) {
                            foreach ($sourceProduct->barcodes as $sourceBarcode) {
                                ProductBarcode::firstOrCreate(
                                    [
                                        'product_id' => $existingProduct->id,
                                        'product_child_id' => null,
                                        'barcode' => $sourceBarcode->barcode,
                                    ],
                                    [
                                        'description' => $sourceBarcode->description,
                                        'is_primary' => $sourceBarcode->is_primary,
                                    ]
                                );
                            }
                        }

                        // Copy variants if enabled
                        if ($this->copyVariants && $sourceProduct->children->isNotEmpty()) {
                            foreach ($sourceProduct->children as $sourceChild) {
                                $existingChild = ProductChild::where('product_id', $existingProduct->id)
                                    ->where('name', $sourceChild->name)
                                    ->first();

                                $childData = [
                                    'barcode' => $sourceChild->barcode,
                                    'unit_quantity' => $sourceChild->unit_quantity,
                                    'presentation_id' => $sourceChild->presentation_id,
                                    'color_id' => $sourceChild->color_id,
                                    'product_model_id' => $sourceChild->product_model_id,
                                    'size' => $sourceChild->size,
                                    'weight' => $sourceChild->weight,
                                    'sale_price' => $sourceChild->sale_price,
                                    'special_price' => $sourceChild->special_price,
                                    'suggested_price' => $sourceChild->suggested_price,
                                    'price_includes_tax' => $sourceChild->price_includes_tax,
                                    'is_active' => $sourceChild->is_active,
                                    'show_in_shop' => $sourceChild->show_in_shop,
                                    'show_in_pos' => $sourceChild->show_in_pos ?? true,
                                    'has_commission' => $sourceChild->has_commission,
                                    'commission_type' => $sourceChild->commission_type,
                                    'commission_value' => $sourceChild->commission_value,
                                    'image' => $sourceChild->image,
                                ];

                                if ($existingChild) {
                                    $existingChild->update($childData);
                                    $targetChild = $existingChild;
                                } else {
                                    $childData['product_id'] = $existingProduct->id;
                                    $childData['name'] = $sourceChild->name;
                                    $targetChild = ProductChild::create($childData);
                                    $variantsCount++;
                                }

                                // Sync variant barcodes
                                if ($sourceChild->barcodes->isNotEmpty()) {
                                    foreach ($sourceChild->barcodes as $childBarcode) {
                                        ProductBarcode::firstOrCreate(
                                            [
                                                'product_id' => null,
                                                'product_child_id' => $targetChild->id,
                                                'barcode' => $childBarcode->barcode,
                                            ],
                                            [
                                                'description' => $childBarcode->description,
                                                'is_primary' => $childBarcode->is_primary,
                                            ]
                                        );
                                    }
                                }
                            }
                        }

                        $updatedCount++;
                        continue;
                    }

                    // Create new cloned product in target branch
                    $initialStock = $this->copyStockMode === 'copy' ? (float) $sourceProduct->current_stock : 0;

                    $newProduct = Product::create([
                        'branch_id' => $toBranch->id,
                        'type' => $sourceProduct->type ?? Product::TYPE_STANDARD,
                        'barcode' => $sourceProduct->barcode,
                        'name' => $sourceProduct->name,
                        'description' => $sourceProduct->description,
                        'category_id' => $sourceProduct->category_id,
                        'subcategory_id' => $sourceProduct->subcategory_id,
                        'brand_id' => $sourceProduct->brand_id,
                        'unit_id' => $sourceProduct->unit_id,
                        'tax_id' => $sourceProduct->tax_id,
                        'purchase_price' => $sourceProduct->purchase_price,
                        'average_cost' => $sourceProduct->average_cost,
                        'sale_price' => $sourceProduct->sale_price,
                        'special_price' => $sourceProduct->special_price,
                        'suggested_price' => $sourceProduct->suggested_price,
                        'price_includes_tax' => $sourceProduct->price_includes_tax,
                        'min_stock' => $sourceProduct->min_stock,
                        'max_stock' => $sourceProduct->max_stock,
                        'current_stock' => $initialStock,
                        'is_active' => $sourceProduct->is_active,
                        'manages_inventory' => $sourceProduct->manages_inventory,
                        'show_in_shop' => $sourceProduct->show_in_shop,
                        'show_in_pos' => $sourceProduct->show_in_pos,
                        'has_commission' => $sourceProduct->has_commission,
                        'commission_type' => $sourceProduct->commission_type,
                        'commission_value' => $sourceProduct->commission_value,
                        'image' => $sourceProduct->image,
                        'presentation_id' => $sourceProduct->presentation_id,
                        'color_id' => $sourceProduct->color_id,
                        'product_model_id' => $sourceProduct->product_model_id,
                        'size' => $sourceProduct->size,
                        'weight' => $sourceProduct->weight,
                        'imei' => null,
                        'import_code' => $sourceProduct->import_code,
                        'import_declaration' => $sourceProduct->import_declaration,
                    ]);

                    // Generate unique SKU
                    $newProduct->generateSku();
                    $newProduct->save();

                    // Copy parent barcodes
                    if ($sourceProduct->barcodes->isNotEmpty()) {
                        foreach ($sourceProduct->barcodes as $sourceBarcode) {
                            ProductBarcode::create([
                                'product_id' => $newProduct->id,
                                'product_child_id' => null,
                                'barcode' => $sourceBarcode->barcode,
                                'description' => $sourceBarcode->description,
                                'is_primary' => $sourceBarcode->is_primary,
                            ]);
                        }
                    }

                    // Create initial stock movement if stock copied and > 0
                    if ($initialStock > 0 && $initialStockDoc) {
                        try {
                            InventoryMovement::create([
                                'system_document_id' => $initialStockDoc->id,
                                'document_number' => $initialStockDoc->generateNextNumber(),
                                'product_id' => $newProduct->id,
                                'branch_id' => $toBranch->id,
                                'user_id' => auth()->id(),
                                'movement_type' => 'in',
                                'quantity' => $initialStock,
                                'stock_before' => 0,
                                'stock_after' => $initialStock,
                                'unit_cost' => $newProduct->purchase_price,
                                'total_cost' => $newProduct->purchase_price * $initialStock,
                                'notes' => "Stock inicial copiado desde '{$fromBranch->name}'",
                                'movement_date' => now(),
                            ]);
                        } catch (\Exception $e) {
                            \Log::warning("No se pudo registrar movimiento de stock inicial al copiar producto: " . $e->getMessage());
                        }
                    }

                    // Copy variants if enabled
                    if ($this->copyVariants && $sourceProduct->children->isNotEmpty()) {
                        foreach ($sourceProduct->children as $sourceChild) {
                            $newChild = ProductChild::create([
                                'product_id' => $newProduct->id,
                                'name' => $sourceChild->name,
                                'barcode' => $sourceChild->barcode,
                                'unit_quantity' => $sourceChild->unit_quantity,
                                'presentation_id' => $sourceChild->presentation_id,
                                'color_id' => $sourceChild->color_id,
                                'product_model_id' => $sourceChild->product_model_id,
                                'size' => $sourceChild->size,
                                'weight' => $sourceChild->weight,
                                'sale_price' => $sourceChild->sale_price,
                                'special_price' => $sourceChild->special_price,
                                'suggested_price' => $sourceChild->suggested_price,
                                'price_includes_tax' => $sourceChild->price_includes_tax,
                                'imei' => null,
                                'is_active' => $sourceChild->is_active,
                                'show_in_shop' => $sourceChild->show_in_shop,
                                'show_in_pos' => $sourceChild->show_in_pos ?? true,
                                'has_commission' => $sourceChild->has_commission,
                                'commission_type' => $sourceChild->commission_type,
                                'commission_value' => $sourceChild->commission_value,
                                'image' => $sourceChild->image,
                            ]);

                            // Copy variant barcodes
                            if ($sourceChild->barcodes->isNotEmpty()) {
                                foreach ($sourceChild->barcodes as $childBarcode) {
                                    ProductBarcode::create([
                                        'product_id' => null,
                                        'product_child_id' => $newChild->id,
                                        'barcode' => $childBarcode->barcode,
                                        'description' => $childBarcode->description,
                                        'is_primary' => $childBarcode->is_primary,
                                    ]);
                                }
                            }

                            $variantsCount++;
                        }
                    }

                    $copiedCount++;
                }
            }

            // 2. COPIAR CLIENTES
            if ($this->copyCustomers) {
                $hasBranchCust = Customer::where('branch_id', $fromBranch->id)->exists();
                $sourceCustQuery = Customer::query();
                if ($hasBranchCust) {
                    $sourceCustQuery->where('branch_id', $fromBranch->id);
                } else {
                    $sourceCustQuery->where(function($q) use ($fromBranch) {
                        $q->where('branch_id', $fromBranch->id)->orWhereNull('branch_id');
                    });
                }

                if ($this->copyCustomerFilter === 'active_only') {
                    $sourceCustQuery->where('is_active', true);
                }

                $sourceCustomers = $sourceCustQuery->get();

                foreach ($sourceCustomers as $sourceCustomer) {
                    $existingCustomer = Customer::where('branch_id', $toBranch->id)
                        ->where('document_number', $sourceCustomer->document_number)
                        ->first();

                    if ($existingCustomer) {
                        if ($this->copyCustomerDuplicateHandling === 'skip') {
                            $skippedCustomersCount++;
                            continue;
                        }

                        $existingCustomer->update([
                            'customer_type' => $sourceCustomer->customer_type,
                            'tax_document_id' => $sourceCustomer->tax_document_id,
                            'dv' => $sourceCustomer->dv,
                            'first_name' => $sourceCustomer->first_name,
                            'last_name' => $sourceCustomer->last_name,
                            'business_name' => $sourceCustomer->business_name,
                            'phone' => $sourceCustomer->phone,
                            'email' => $sourceCustomer->email,
                            'department_id' => $sourceCustomer->department_id,
                            'municipality_id' => $sourceCustomer->municipality_id,
                            'address' => $sourceCustomer->address,
                            'has_credit' => $sourceCustomer->has_credit ?? false,
                            'credit_limit' => $sourceCustomer->credit_limit ?? 0,
                            'is_active' => $sourceCustomer->is_active,
                        ]);
                        $updatedCustomersCount++;
                    } else {
                        Customer::create([
                            'branch_id' => $toBranch->id,
                            'seller_id' => null,
                            'customer_type' => $sourceCustomer->customer_type,
                            'tax_document_id' => $sourceCustomer->tax_document_id,
                            'document_number' => $sourceCustomer->document_number,
                            'dv' => $sourceCustomer->dv,
                            'first_name' => $sourceCustomer->first_name,
                            'last_name' => $sourceCustomer->last_name,
                            'business_name' => $sourceCustomer->business_name,
                            'phone' => $sourceCustomer->phone,
                            'email' => $sourceCustomer->email,
                            'department_id' => $sourceCustomer->department_id,
                            'municipality_id' => $sourceCustomer->municipality_id,
                            'address' => $sourceCustomer->address,
                            'has_credit' => $sourceCustomer->has_credit ?? false,
                            'credit_limit' => $sourceCustomer->credit_limit ?? 0,
                            'is_active' => $sourceCustomer->is_active,
                            'is_default' => false,
                        ]);
                        $copiedCustomersCount++;
                    }
                }
            }

            // 3. COPIAR PROVEEDORES
            if ($this->copySuppliers) {
                $hasBranchSupp = Supplier::where('branch_id', $fromBranch->id)->exists();
                $sourceSuppQuery = Supplier::query();
                if ($hasBranchSupp) {
                    $sourceSuppQuery->where('branch_id', $fromBranch->id);
                } else {
                    $sourceSuppQuery->where(function($q) use ($fromBranch) {
                        $q->where('branch_id', $fromBranch->id)->orWhereNull('branch_id');
                    });
                }

                if ($this->copySupplierFilter === 'active_only') {
                    $sourceSuppQuery->where('is_active', true);
                }

                $sourceSuppliers = $sourceSuppQuery->get();

                foreach ($sourceSuppliers as $sourceSupplier) {
                    $existingSupplier = Supplier::where('branch_id', $toBranch->id)
                        ->where('document_number', $sourceSupplier->document_number)
                        ->first();

                    if ($existingSupplier) {
                        if ($this->copySupplierDuplicateHandling === 'skip') {
                            $skippedSuppliersCount++;
                            continue;
                        }

                        $existingSupplier->update([
                            'tax_document_id' => $sourceSupplier->tax_document_id,
                            'name' => $sourceSupplier->name,
                            'phone' => $sourceSupplier->phone,
                            'email' => $sourceSupplier->email,
                            'department_id' => $sourceSupplier->department_id,
                            'municipality_id' => $sourceSupplier->municipality_id,
                            'address' => $sourceSupplier->address,
                            'salesperson_name' => $sourceSupplier->salesperson_name,
                            'salesperson_phone' => $sourceSupplier->salesperson_phone,
                            'is_active' => $sourceSupplier->is_active,
                        ]);
                        $updatedSuppliersCount++;
                    } else {
                        Supplier::create([
                            'branch_id' => $toBranch->id,
                            'tax_document_id' => $sourceSupplier->tax_document_id,
                            'document_number' => $sourceSupplier->document_number,
                            'name' => $sourceSupplier->name,
                            'phone' => $sourceSupplier->phone,
                            'email' => $sourceSupplier->email,
                            'department_id' => $sourceSupplier->department_id,
                            'municipality_id' => $sourceSupplier->municipality_id,
                            'address' => $sourceSupplier->address,
                            'salesperson_name' => $sourceSupplier->salesperson_name,
                            'salesperson_phone' => $sourceSupplier->salesperson_phone,
                            'is_active' => $sourceSupplier->is_active,
                        ]);
                        $copiedSuppliersCount++;
                    }
                }
            }

            DB::commit();

            // Log activity
            $logParts = [];
            if ($this->copyProducts) {
                $logParts[] = "Productos: {$copiedCount} creados, {$updatedCount} actualizados, {$skippedCount} omitidos, {$variantsCount} variantes";
            }
            if ($this->copyCustomers) {
                $logParts[] = "Clientes: {$copiedCustomersCount} creados, {$updatedCustomersCount} actualizados, {$skippedCustomersCount} omitidos";
            }
            if ($this->copySuppliers) {
                $logParts[] = "Proveedores: {$copiedSuppliersCount} creados, {$updatedSuppliersCount} actualizados, {$skippedSuppliersCount} omitidos";
            }

            $summaryMsg = "Copia entre sucursales ('{$fromBranch->name}' -> '{$toBranch->name}'): " . implode(' | ', $logParts);
            ActivityLogService::logCreate('branches', $toBranch, $summaryMsg);

            $this->isCopyModalOpen = false;

            // User notification message
            $msgParts = [];
            if ($this->copyProducts) {
                $pMsg = "{$copiedCount} productos nuevos";
                if ($variantsCount > 0) $pMsg .= " ({$variantsCount} variantes)";
                if ($updatedCount > 0) $pMsg .= ", {$updatedCount} actualizados";
                if ($skippedCount > 0) $pMsg .= " ({$skippedCount} omitidos por ya existir)";
                $msgParts[] = $pMsg;
            }
            if ($this->copyCustomers) {
                $cMsg = "{$copiedCustomersCount} clientes nuevos";
                if ($updatedCustomersCount > 0) $cMsg .= ", {$updatedCustomersCount} actualizados";
                if ($skippedCustomersCount > 0) $cMsg .= " ({$skippedCustomersCount} omitidos)";
                $msgParts[] = $cMsg;
            }
            if ($this->copySuppliers) {
                $sMsg = "{$copiedSuppliersCount} proveedores nuevos";
                if ($updatedSuppliersCount > 0) $sMsg .= ", {$updatedSuppliersCount} actualizados";
                if ($skippedSuppliersCount > 0) $sMsg .= " ({$skippedSuppliersCount} omitidos)";
                $msgParts[] = $sMsg;
            }

            $message = "Operación completada exitosamente hacia '{$toBranch->name}': " . implode('; ', $msgParts) . ".";
            $this->dispatch('notify', message: $message);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('notify', message: 'Error al copiar datos entre sucursales: ' . $e->getMessage(), type: 'error');
        }
    }

    private function resetForm()
    {
        $this->branchId = null;
        $this->code = '';
        $this->name = '';
        $this->logo = null;
        $this->existingLogo = null;
        $this->tax_id = '';
        $this->department_id = '';
        $this->municipality_id = '';
        $this->municipalities = [];
        $this->address = '';
        $this->phone = '';
        $this->email = '';
        $this->ticket_prefix = '';
        $this->invoice_prefix = '';
        $this->receipt_prefix = '';
        $this->credit_note_prefix = '';
        $this->activity_number = '';
        $this->authorization_date = '';
        $this->receipt_header = '';
        $this->show_in_pos = true;
        $this->ecommerce_enabled = false;
        $this->show_stock_in_shop = false;
        $this->quotes_reserve_inventory = false;
        $this->print_qr = false;
        $this->enable_costo_fe = false;
        $this->tax_exempt_preserves_price = false;
        $this->is_active = true;
    }
}

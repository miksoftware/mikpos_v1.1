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

    // Copy Products Modal properties
    public bool $isCopyModalOpen = false;
    public $copyFromBranchId = '';
    public $copyToBranchId = '';
    public string $copyFilter = 'all'; // 'all', 'active_only'
    public string $copyDuplicateHandling = 'skip'; // 'skip', 'overwrite'
    public string $copyStockMode = 'zero'; // 'zero', 'copy'
    public bool $copyVariants = true;
    public int $copySourceCount = 0;
    public int $copyTargetCount = 0;

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
            $this->dispatch('notify', message: 'No tienes permiso para copiar productos entre sucursales', type: 'error');
            return;
        }

        $this->resetValidation();
        $this->copyFromBranchId = $sourceBranchId ? (string) $sourceBranchId : '';
        $this->copyToBranchId = '';
        $this->copyFilter = 'all';
        $this->copyDuplicateHandling = 'skip';
        $this->copyStockMode = 'zero';
        $this->copyVariants = true;
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

    public function updatedCopyFilter()
    {
        $this->updateCopyCounts();
    }

    private function updateCopyCounts()
    {
        if ($this->copyFromBranchId) {
            $query = Product::where('branch_id', $this->copyFromBranchId);
            if ($this->copyFilter === 'active_only') {
                $query->where('is_active', true);
            }
            $this->copySourceCount = $query->count();
        } else {
            $this->copySourceCount = 0;
        }

        if ($this->copyToBranchId) {
            $this->copyTargetCount = Product::where('branch_id', $this->copyToBranchId)->count();
        } else {
            $this->copyTargetCount = 0;
        }
    }

    public function executeCopyProducts()
    {
        if (!auth()->user()->hasPermission('branches.copy_products')) {
            $this->dispatch('notify', message: 'No tienes permiso para copiar productos entre sucursales', type: 'error');
            return;
        }

        $this->copyFromBranchId = $this->copyFromBranchId !== '' ? (string) $this->copyFromBranchId : '';
        $this->copyToBranchId = $this->copyToBranchId !== '' ? (string) $this->copyToBranchId : '';

        $this->validate([
            'copyFromBranchId' => 'required|exists:branches,id',
            'copyToBranchId' => 'required|exists:branches,id|different:copyFromBranchId',
            'copyFilter' => 'required|in:all,active_only',
            'copyDuplicateHandling' => 'required|in:skip,overwrite',
            'copyStockMode' => 'required|in:zero,copy',
        ], [
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

        $sourceProductsQuery = Product::where('branch_id', $fromBranch->id)
            ->with(['children.barcodes', 'barcodes']);

        if ($this->copyFilter === 'active_only') {
            $sourceProductsQuery->where('is_active', true);
        }

        $sourceProducts = $sourceProductsQuery->get();

        if ($sourceProducts->isEmpty()) {
            $this->dispatch('notify', message: 'No hay productos para copiar en la sucursal de origen', type: 'warning');
            return;
        }

        $copiedCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;
        $variantsCount = 0;

        DB::beginTransaction();
        try {
            $initialStockDoc = null;
            if ($this->copyStockMode === 'copy') {
                $initialStockDoc = \App\Models\SystemDocument::findByCode('initial_stock');
            }

            foreach ($sourceProducts as $sourceProduct) {
                // Check if product with same name already exists in destination branch
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

            DB::commit();

            // Log activity
            $summaryMsg = "Copia de productos: {$copiedCount} creados, {$updatedCount} actualizados, {$skippedCount} omitidos, {$variantsCount} variantes desde '{$fromBranch->name}' a '{$toBranch->name}'";
            ActivityLogService::logCreate('branches', $toBranch, $summaryMsg);

            $this->isCopyModalOpen = false;

            $message = "Se copiaron {$copiedCount} productos nuevos";
            if ($variantsCount > 0) {
                $message .= " con {$variantsCount} variantes";
            }
            if ($updatedCount > 0) {
                $message .= ", {$updatedCount} actualizados";
            }
            if ($skippedCount > 0) {
                $message .= " ({$skippedCount} omitidos por ya existir)";
            }
            $message .= " a '{$toBranch->name}' exitosamente.";

            $this->dispatch('notify', message: $message);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('notify', message: 'Error al copiar productos: ' . $e->getMessage(), type: 'error');
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
        $this->is_active = true;
    }
}

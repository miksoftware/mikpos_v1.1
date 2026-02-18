<?php

namespace App\Livewire;

use App\Models\Branch;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Color;
use App\Models\InventoryMovement;
use App\Models\Presentation;
use App\Models\Product;
use App\Models\ProductBarcode;
use App\Models\ProductChild;
use App\Models\ProductFieldSetting;
use App\Models\ProductModel;
use App\Models\Subcategory;
use App\Models\Tax;
use App\Models\Unit;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Products extends Component
{
    use WithPagination;
    use WithFileUploads;

    // Search and filters
    public string $search = '';
    public ?int $filterCategory = null;
    public ?int $filterBrand = null;
    public ?string $filterStatus = null;
    public ?string $filterBranch = null;
    public ?string $filterHasVariants = null;
    public ?string $filterStockStatus = null;
    public string $sortBy = 'created_at';
    public string $sortDirection = 'desc';

    // Branch control
    public bool $needsBranchSelection = false;
    public $branches = [];

    // Modal states
    public bool $isModalOpen = false;
    public bool $isDeleteModalOpen = false;
    public bool $isChildModalOpen = false;
    public bool $isChildDeleteModalOpen = false;
    public ?int $itemIdToDelete = null;
    public ?int $childIdToDelete = null;

    // Form data for parent product
    public ?int $itemId = null;
    public ?int $branch_id = null;
    public ?string $sku = null;
    public ?string $barcode = null;
    public string $name = '';
    public ?string $description = null;
    public ?int $category_id = null;
    public ?int $subcategory_id = null;
    public ?int $brand_id = null;
    public ?int $unit_id = null;
    public ?int $tax_id = null;
    public float $purchase_price = 0;
    public float $sale_price = 0;
    public ?float $special_price = null;
    public bool $price_includes_tax = false;
    public int $min_stock = 0;
    public ?int $max_stock = null;
    public int $current_stock = 0;
    public bool $is_active = true;
    public bool $has_commission = false;
    public ?string $commission_type = 'percentage';
    public ?float $commission_value = null;
    public $image = null; // For file upload
    public ?string $existingImage = null; // To track existing image path

    // Configurable fields for parent product
    public ?int $presentation_id = null;
    public ?int $color_id = null;
    public ?int $product_model_id = null;
    public ?string $size = null;
    public ?float $weight = null;
    public ?string $imei = null;

    // Form data for child product
    public ?int $childId = null;
    public ?int $childProductId = null;
    public float $childUnitQuantity = 1;
    public ?string $childSku = null;
    public ?string $childBarcode = null;
    public string $childName = '';
    public ?int $childPresentationId = null;
    public ?int $childColorId = null;
    public ?int $childProductModelId = null;
    public ?string $childSize = null;
    public ?float $childWeight = null;
    public float $childSalePrice = 0;
    public bool $childPriceIncludesTax = false;
    public ?string $childImei = null;
    public bool $childIsActive = true;
    public bool $childHasCommission = false;
    public ?string $childCommissionType = 'percentage';
    public ?float $childCommissionValue = null;
    public $childImage = null; // For file upload
    public ?string $childExistingImage = null; // To track existing image path

    // Parent product info for child modal
    public ?Product $parentProduct = null;

    // Field settings for child form
    public $fieldSettings = [];

    // Subcategories for selected category
    public $subcategories = [];

    // Expanded products (to show children)
    public array $expandedProducts = [];

    // Import CSV properties
    public bool $isImportModalOpen = false;
    public $importFile = null;
    public array $importErrors = [];
    public array $importPreview = [];
    public bool $importProcessed = false;
    public int $importSuccessCount = 0;
    public int $importErrorCount = 0;
    public bool $isImporting = false;
    public int $importProgress = 0;
    public int $importTotal = 0;
    public bool $showOnlyErrors = false;
    public bool $showOnlyWarnings = false;
    public string $importFilter = 'all'; // all, errors, warnings

    // Barcode management
    public bool $isBarcodeModalOpen = false;
    public ?int $barcodeProductId = null;
    public ?int $barcodeProductChildId = null;
    public string $barcodeProductName = '';
    public array $productBarcodes = [];
    public string $newBarcode = '';
    public string $newBarcodeDescription = '';

    // Bulk delete
    public bool $isBulkDeleteModalOpen = false;
    public string $bulkDeleteSearch = '';
    public string $bulkDeleteStockFilter = ''; // '', 'zero', 'low', 'has'
    public string $bulkDeleteStatusFilter = ''; // '', '1', '0'
    public ?int $bulkDeleteCategoryFilter = null;
    public array $bulkDeleteSelected = []; // array of product IDs
    public bool $isBulkDeleting = false;

    public function mount()
    {
        $user = auth()->user();
        $this->needsBranchSelection = $user->isSuperAdmin() || !$user->branch_id;
        
        if ($this->needsBranchSelection) {
            $this->branches = Branch::where('is_active', true)->orderBy('name')->get();
        }
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFilterCategory()
    {
        $this->resetPage();
    }

    public function updatedFilterBrand()
    {
        $this->resetPage();
    }

    public function updatedFilterStatus()
    {
        $this->resetPage();
    }

    public function updatedFilterBranch()
    {
        $this->resetPage();
    }

    public function updatedFilterHasVariants()
    {
        $this->resetPage();
    }

    public function updatedFilterStockStatus()
    {
        $this->resetPage();
    }

    public function render()
    {
        $user = auth()->user();
        
        $query = Product::query()
            ->with(['category', 'subcategory', 'brand', 'unit', 'tax', 'branch', 'children.presentation', 'children.color', 'children.productModel'])
            ->withCount('children')
            ->withCount('activeChildren');

        // Apply branch filter
        if ($this->needsBranchSelection) {
            if ($this->filterBranch) {
                $query->where('branch_id', $this->filterBranch);
            }
        } else {
            $query->where('branch_id', $user->branch_id);
        }

        $items = $query
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('name', 'like', "%{$this->search}%")
                        ->orWhere('sku', 'like', "%{$this->search}%")
                        ->orWhere('description', 'like', "%{$this->search}%")
                        ->orWhere('barcode', 'like', "%{$this->search}%")
                        ->orWhereHas('barcodes', function ($bq) {
                            $bq->where('barcode', 'like', "%{$this->search}%");
                        })
                        ->orWhereHas('children', function ($childQuery) {
                            $childQuery->where('name', 'like', "%{$this->search}%")
                                ->orWhere('sku', 'like', "%{$this->search}%")
                                ->orWhere('barcode', 'like', "%{$this->search}%");
                        })
                        ->orWhereHas('children.barcodes', function ($cbq) {
                            $cbq->where('barcode', 'like', "%{$this->search}%");
                        });
                });
            })
            ->when($this->filterCategory, fn($q) => $q->where('category_id', $this->filterCategory))
            ->when($this->filterBrand, fn($q) => $q->where('brand_id', $this->filterBrand))
            ->when($this->filterStatus !== null && $this->filterStatus !== '', function ($q) {
                $q->where('is_active', $this->filterStatus === '1');
            })
            ->when($this->filterHasVariants !== null && $this->filterHasVariants !== '', function ($q) {
                if ($this->filterHasVariants === '1') {
                    $q->has('children');
                } else {
                    $q->doesntHave('children');
                }
            })
            ->when($this->filterStockStatus !== null && $this->filterStockStatus !== '', function ($q) {
                if ($this->filterStockStatus === 'low') {
                    $q->whereRaw('current_stock <= min_stock');
                } elseif ($this->filterStockStatus === 'out') {
                    $q->where('current_stock', '<=', 0);
                } elseif ($this->filterStockStatus === 'ok') {
                    $q->whereRaw('current_stock > min_stock');
                }
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(10);

        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $brands = Brand::where('is_active', true)->orderBy('name')->get();
        $units = Unit::where('is_active', true)->orderBy('name')->get();
        $taxes = Tax::where('is_active', true)->orderBy('name')->get();
        $presentations = Presentation::where('is_active', true)->orderBy('name')->get();
        $colors = Color::where('is_active', true)->orderBy('name')->get();
        $productModels = ProductModel::where('is_active', true)->orderBy('name')->get();

        return view('livewire.products', [
            'items' => $items,
            'categories' => $categories,
            'brands' => $brands,
            'units' => $units,
            'taxes' => $taxes,
            'presentations' => $presentations,
            'colors' => $colors,
            'productModels' => $productModels,
        ]);
    }

    public function updatedCategoryId($value)
    {
        $this->subcategory_id = null;
        $this->subcategories = $value
            ? Subcategory::where('category_id', $value)->where('is_active', true)->orderBy('name')->get()
            : [];
    }

    public function create()
    {
        if (!auth()->user()->hasPermission('products.create')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->resetValidation();
        $this->resetForm();
        $this->loadFieldSettings();
        
        // Set default branch for users with assigned branch
        $user = auth()->user();
        if (!$this->needsBranchSelection && $user->branch_id) {
            $this->branch_id = $user->branch_id;
        }
        
        $this->isModalOpen = true;
    }

    public function edit(int $id)
    {
        if (!auth()->user()->hasPermission('products.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->resetValidation();
        $this->loadFieldSettings();
        $item = Product::findOrFail($id);
        
        $this->itemId = $item->id;
        $this->branch_id = $item->branch_id;
        $this->sku = $item->sku;
        $this->barcode = $item->barcode;
        $this->name = $item->name;
        $this->description = $item->description;
        $this->category_id = $item->category_id;
        $this->subcategory_id = $item->subcategory_id;
        $this->brand_id = $item->brand_id;
        $this->unit_id = $item->unit_id;
        $this->tax_id = $item->tax_id;
        $this->purchase_price = (float) $item->purchase_price;
        $this->sale_price = (float) $item->sale_price;
        $this->special_price = $item->special_price ? (float) $item->special_price : null;
        $this->price_includes_tax = $item->price_includes_tax;
        $this->min_stock = $item->min_stock;
        $this->max_stock = $item->max_stock;
        $this->current_stock = $item->current_stock;
        $this->is_active = $item->is_active;
        
        // Load configurable fields
        $this->presentation_id = $item->presentation_id;
        $this->color_id = $item->color_id;
        $this->product_model_id = $item->product_model_id;
        $this->size = $item->size;
        $this->weight = $item->weight ? (float) $item->weight : null;
        $this->imei = $item->imei;
        $this->has_commission = $item->has_commission;
        $this->commission_type = $item->commission_type ?? 'percentage';
        $this->commission_value = $item->commission_value ? (float) $item->commission_value : null;
        $this->existingImage = $item->image;
        $this->image = null;

        // Load subcategories for the selected category
        $this->subcategories = $this->category_id
            ? Subcategory::where('category_id', $this->category_id)->where('is_active', true)->orderBy('name')->get()
            : [];

        $this->isModalOpen = true;
    }

    public function store()
    {
        $isNew = !$this->itemId;
        if (!auth()->user()->hasPermission($isNew ? 'products.create' : 'products.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        // Build validation rules dynamically
        $rules = $this->buildParentValidationRules();
        $messages = $this->getParentValidationMessages();
        
        // Branch is required for super_admin or users without branch
        if ($this->needsBranchSelection) {
            $rules['branch_id'] = 'required|exists:branches,id';
            $messages['branch_id.required'] = 'Debe seleccionar una sucursal';
        }
        
        $this->validate($rules, $messages);

        $oldValues = $isNew ? null : Product::find($this->itemId)->toArray();

        // Handle image upload
        $imagePath = $this->existingImage;
        if ($this->image) {
            // Delete old image if exists
            if ($this->existingImage && Storage::disk('public')->exists($this->existingImage)) {
                Storage::disk('public')->delete($this->existingImage);
            }
            // Store new image
            $imagePath = $this->image->store('products', 'public');
        }

        // Determine branch_id
        $branchId = $this->needsBranchSelection ? $this->branch_id : auth()->user()->branch_id;

        $item = Product::updateOrCreate(['id' => $this->itemId], [
            'branch_id' => $branchId,
            'barcode' => $this->barcode ?: null,
            'name' => mb_strtoupper($this->name),
            'description' => $this->description ? mb_strtoupper($this->description) : null,
            'category_id' => $this->category_id,
            'subcategory_id' => $this->subcategory_id ?: null,
            'brand_id' => $this->brand_id ?: null,
            'unit_id' => $this->unit_id,
            'tax_id' => $this->tax_id ?: null,
            'purchase_price' => $this->purchase_price,
            'sale_price' => $this->sale_price,
            'special_price' => $this->special_price ?: null,
            'price_includes_tax' => $this->price_includes_tax,
            'min_stock' => $this->min_stock,
            'max_stock' => $this->max_stock ?: null,
            'current_stock' => $this->current_stock,
            'is_active' => $this->is_active,
            'has_commission' => $this->has_commission,
            'commission_type' => $this->has_commission ? $this->commission_type : null,
            'commission_value' => $this->has_commission ? $this->commission_value : null,
            'image' => $imagePath,
            // Configurable fields
            'presentation_id' => $this->presentation_id ?: null,
            'color_id' => $this->color_id ?: null,
            'product_model_id' => $this->product_model_id ?: null,
            'size' => $this->size ?: null,
            'weight' => $this->weight ?: null,
            'imei' => $this->imei ?: null,
        ]);

        // Generate SKU if not provided
        if (!$item->sku) {
            $item->generateSku();
            $item->save();
        } elseif ($this->sku && $this->sku !== $item->sku) {
            $item->sku = $this->sku;
            $item->save();
        }

        // Create inventory movement for initial stock on new products
        if ($isNew && $this->current_stock > 0) {
            try {
                // For initial stock, we need to set stock_before to 0 manually
                // since the product was just created with current_stock already set
                $systemDocument = \App\Models\SystemDocument::findByCode('initial_stock');
                if ($systemDocument) {
                    \App\Models\InventoryMovement::create([
                        'system_document_id' => $systemDocument->id,
                        'document_number' => $systemDocument->generateNextNumber(),
                        'product_id' => $item->id,
                        'branch_id' => $branchId,
                        'user_id' => auth()->id(),
                        'movement_type' => 'in',
                        'quantity' => $this->current_stock,
                        'stock_before' => 0,
                        'stock_after' => $this->current_stock,
                        'unit_cost' => $this->purchase_price,
                        'total_cost' => $this->purchase_price * $this->current_stock,
                        'notes' => "Stock inicial del producto '{$item->name}'",
                        'movement_date' => now(),
                    ]);
                }
            } catch (\Exception $e) {
                // Log error but don't fail the product creation
                \Log::warning("Could not create initial stock movement: " . $e->getMessage());
            }
        }

        // Handle barcode - save to product_barcodes table
        if ($this->barcode) {
            $existingBarcode = ProductBarcode::where('product_id', $item->id)
                ->whereNull('product_child_id')
                ->where('barcode', $this->barcode)
                ->first();
            
            if (!$existingBarcode) {
                // Check if product has any barcodes to determine if this should be primary
                $hasBarcodes = ProductBarcode::where('product_id', $item->id)
                    ->whereNull('product_child_id')
                    ->exists();
                
                ProductBarcode::create([
                    'product_id' => $item->id,
                    'product_child_id' => null,
                    'barcode' => $this->barcode,
                    'description' => $isNew ? 'Código principal' : null,
                    'is_primary' => !$hasBarcodes, // Primary if no other barcodes exist
                ]);
            }
        }

        $isNew
            ? ActivityLogService::logCreate('products', $item, "Producto '{$item->name}' creado")
            : ActivityLogService::logUpdate('products', $item, $oldValues, "Producto '{$item->name}' actualizado");

        $this->isModalOpen = false;
        $this->dispatch('notify', message: $isNew ? 'Producto creado' : 'Producto actualizado');
    }

    public function confirmDelete(int $id)
    {
        if (!auth()->user()->hasPermission('products.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->itemIdToDelete = $id;
        $this->isDeleteModalOpen = true;
    }

    public function delete()
    {
        if (!auth()->user()->hasPermission('products.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $item = Product::find($this->itemIdToDelete);
        
        if (!$item) {
            $this->dispatch('notify', message: 'Producto no encontrado', type: 'error');
            $this->isDeleteModalOpen = false;
            return;
        }

        // Check if product has active children (protected deletion)
        if (!$item->canDelete()) {
            $this->dispatch('notify', message: 'No se puede eliminar: tiene variantes activas', type: 'error');
            $this->isDeleteModalOpen = false;
            return;
        }

        // Delete child images first
        foreach ($item->children as $child) {
            if ($child->image && Storage::disk('public')->exists($child->image)) {
                Storage::disk('public')->delete($child->image);
            }
        }

        // Delete all children first (if any inactive ones exist)
        $item->children()->delete();

        // Delete parent image
        if ($item->image && Storage::disk('public')->exists($item->image)) {
            Storage::disk('public')->delete($item->image);
        }

        ActivityLogService::logDelete('products', $item, "Producto '{$item->name}' eliminado");
        $item->delete();
        
        $this->isDeleteModalOpen = false;
        $this->dispatch('notify', message: 'Producto eliminado');
    }

    public function toggleStatus(int $id)
    {
        if (!auth()->user()->hasPermission('products.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $item = Product::find($id);
        if (!$item) {
            return;
        }

        $oldValues = $item->toArray();
        $item->is_active = !$item->is_active;
        $item->save();

        // If deactivating parent, deactivate all children (cascade)
        if (!$item->is_active) {
            $item->children()->update(['is_active' => false]);
        }

        ActivityLogService::logUpdate(
            'products',
            $item,
            $oldValues,
            "Producto '{$item->name}' " . ($item->is_active ? 'activado' : 'desactivado')
        );

        $this->dispatch('notify', message: $item->is_active ? 'Activado' : 'Desactivado');
    }

    public function toggleExpand(int $id)
    {
        if (in_array($id, $this->expandedProducts)) {
            $this->expandedProducts = array_diff($this->expandedProducts, [$id]);
        } else {
            $this->expandedProducts[] = $id;
        }
    }

    // Child Product Methods

    public function createChild(int $parentId)
    {
        if (!auth()->user()->hasPermission('products.create')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $this->resetValidation();
        $this->resetChildForm();
        
        $this->parentProduct = Product::with(['category', 'subcategory', 'brand', 'tax', 'unit'])->findOrFail($parentId);
        $this->childProductId = $parentId;
        
        // Load field settings for the current branch
        $this->loadFieldSettings();
        
        $this->isChildModalOpen = true;
    }

    public function editChild(int $id)
    {
        if (!auth()->user()->hasPermission('products.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $this->resetValidation();
        $child = ProductChild::with('product.category', 'product.subcategory', 'product.brand', 'product.tax', 'product.unit')->findOrFail($id);
        
        $this->childId = $child->id;
        $this->childProductId = $child->product_id;
        $this->parentProduct = $child->product;
        $this->childUnitQuantity = (float) $child->unit_quantity;
        $this->childSku = $child->sku;
        $this->childBarcode = $child->barcode;
        $this->childName = $child->name;
        $this->childPresentationId = $child->presentation_id;
        $this->childColorId = $child->color_id;
        $this->childProductModelId = $child->product_model_id;
        $this->childSize = $child->size;
        $this->childWeight = $child->weight;
        $this->childSalePrice = (float) $child->sale_price;
        $this->childPriceIncludesTax = $child->price_includes_tax;
        $this->childImei = $child->imei;
        $this->childIsActive = $child->is_active;
        $this->childHasCommission = $child->has_commission;
        $this->childCommissionType = $child->commission_type ?? 'percentage';
        $this->childCommissionValue = $child->commission_value ? (float) $child->commission_value : null;
        $this->childExistingImage = $child->image;
        $this->childImage = null;

        // Load field settings for the current branch
        $this->loadFieldSettings();

        $this->isChildModalOpen = true;
    }

    public function storeChild()
    {
        $isNew = !$this->childId;
        if (!auth()->user()->hasPermission($isNew ? 'products.create' : 'products.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        // Build validation rules dynamically based on field settings
        $rules = $this->buildChildValidationRules();
        // Add image validation
        $rules['childImage'] = 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048';
        
        $messages = $this->getChildValidationMessages();
        $messages['childImage.image'] = 'El archivo debe ser una imagen';
        $messages['childImage.mimes'] = 'La imagen debe ser JPG, PNG o WebP';
        $messages['childImage.max'] = 'La imagen no debe superar 2MB';
        
        $this->validate($rules, $messages);

        $oldValues = $isNew ? null : ProductChild::find($this->childId)->toArray();

        // Handle image upload
        $imagePath = $this->childExistingImage;
        if ($this->childImage) {
            // Delete old image if exists
            if ($this->childExistingImage && Storage::disk('public')->exists($this->childExistingImage)) {
                Storage::disk('public')->delete($this->childExistingImage);
            }
            // Store new image in products/variants folder
            $imagePath = $this->childImage->store('products/variants', 'public');
        }

        $child = ProductChild::updateOrCreate(['id' => $this->childId], [
            'product_id' => $this->childProductId,
            'unit_quantity' => $this->childUnitQuantity,
            'sku' => $this->childSku ?: null,
            'barcode' => $this->childBarcode ?: null,
            'name' => mb_strtoupper($this->childName),
            'presentation_id' => $this->childPresentationId ?: null,
            'color_id' => $this->childColorId ?: null,
            'product_model_id' => $this->childProductModelId ?: null,
            'size' => $this->childSize ?: null,
            'weight' => $this->childWeight ?: null,
            'sale_price' => $this->childSalePrice,
            'price_includes_tax' => $this->childPriceIncludesTax,
            'imei' => $this->childImei ?: null,
            'is_active' => $this->childIsActive,
            'has_commission' => $this->childHasCommission,
            'commission_type' => $this->childHasCommission ? $this->childCommissionType : null,
            'commission_value' => $this->childHasCommission ? $this->childCommissionValue : null,
            'image' => $imagePath,
        ]);

        $parentName = $this->parentProduct?->name ?? 'Producto';
        
        // Handle barcode - save to product_barcodes table
        if ($this->childBarcode) {
            $existingBarcode = ProductBarcode::where('product_child_id', $child->id)
                ->where('barcode', $this->childBarcode)
                ->first();
            
            if (!$existingBarcode) {
                // Check if child has any barcodes to determine if this should be primary
                $hasBarcodes = ProductBarcode::where('product_child_id', $child->id)->exists();
                
                ProductBarcode::create([
                    'product_id' => $this->childProductId,
                    'product_child_id' => $child->id,
                    'barcode' => $this->childBarcode,
                    'description' => $isNew ? 'Código principal' : null,
                    'is_primary' => !$hasBarcodes, // Primary if no other barcodes exist
                ]);
            }
        }

        $isNew
            ? ActivityLogService::logCreate('product_children', $child, "Variante '{$child->name}' creada para '{$parentName}'")
            : ActivityLogService::logUpdate('product_children', $child, $oldValues, "Variante '{$child->name}' actualizada");

        $this->isChildModalOpen = false;
        
        // Ensure parent is expanded to show the new/updated child
        if (!in_array($this->childProductId, $this->expandedProducts)) {
            $this->expandedProducts[] = $this->childProductId;
        }
        
        $this->dispatch('notify', message: $isNew ? 'Variante creada' : 'Variante actualizada');
    }

    public function confirmDeleteChild(int $id)
    {
        if (!auth()->user()->hasPermission('products.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->childIdToDelete = $id;
        $this->isChildDeleteModalOpen = true;
    }

    public function deleteChild()
    {
        if (!auth()->user()->hasPermission('products.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $child = ProductChild::find($this->childIdToDelete);
        
        if (!$child) {
            $this->dispatch('notify', message: 'Variante no encontrada', type: 'error');
            $this->isChildDeleteModalOpen = false;
            return;
        }

        // Delete child image if exists
        if ($child->image && Storage::disk('public')->exists($child->image)) {
            Storage::disk('public')->delete($child->image);
        }

        $parentName = $child->product?->name ?? 'Producto';
        ActivityLogService::logDelete('product_children', $child, "Variante '{$child->name}' eliminada de '{$parentName}'");
        $child->delete();
        
        $this->isChildDeleteModalOpen = false;
        $this->dispatch('notify', message: 'Variante eliminada');
    }

    public function toggleChildStatus(int $id)
    {
        if (!auth()->user()->hasPermission('products.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $child = ProductChild::find($id);
        if (!$child) {
            return;
        }

        $oldValues = $child->toArray();
        $child->is_active = !$child->is_active;
        $child->save();

        ActivityLogService::logUpdate(
            'product_children',
            $child,
            $oldValues,
            "Variante '{$child->name}' " . ($child->is_active ? 'activada' : 'desactivada')
        );

        $this->dispatch('notify', message: $child->is_active ? 'Variante activada' : 'Variante desactivada');
    }

    private function loadFieldSettings()
    {
        // Get branch ID from authenticated user if available
        $branchId = auth()->user()->branch_id ?? null;
        $this->fieldSettings = ProductFieldSetting::getFieldsForBranch($branchId)->toArray();
    }

    private function isParentFieldVisible(string $fieldName): bool
    {
        if (!isset($this->fieldSettings[$fieldName])) {
            return false; // Default to not visible for configurable fields
        }
        
        $field = $this->fieldSettings[$fieldName];
        return is_object($field) ? $field->parent_visible : ($field['parent_visible'] ?? false);
    }

    private function isParentFieldRequired(string $fieldName): bool
    {
        if (!isset($this->fieldSettings[$fieldName])) {
            return false;
        }
        
        $field = $this->fieldSettings[$fieldName];
        $isVisible = is_object($field) ? $field->parent_visible : ($field['parent_visible'] ?? false);
        $isRequired = is_object($field) ? $field->parent_required : ($field['parent_required'] ?? false);
        
        return $isVisible && $isRequired;
    }

    private function isChildFieldVisible(string $fieldName): bool
    {
        if (!isset($this->fieldSettings[$fieldName])) {
            return false;
        }
        
        $field = $this->fieldSettings[$fieldName];
        return is_object($field) ? $field->child_visible : ($field['child_visible'] ?? false);
    }

    private function isChildFieldRequired(string $fieldName): bool
    {
        if (!isset($this->fieldSettings[$fieldName])) {
            return false;
        }
        
        $field = $this->fieldSettings[$fieldName];
        $isVisible = is_object($field) ? $field->child_visible : ($field['child_visible'] ?? false);
        $isRequired = is_object($field) ? $field->child_required : ($field['child_required'] ?? false);
        
        return $isVisible && $isRequired;
    }

    private function buildParentValidationRules(): array
    {
        $rules = [
            'name' => 'required|min:2',
            'sku' => 'nullable|unique:products,sku,' . $this->itemId,
            'category_id' => 'required|exists:categories,id',
            'subcategory_id' => 'nullable|exists:subcategories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'unit_id' => 'required|exists:units,id',
            'tax_id' => 'nullable|exists:taxes,id',
            'purchase_price' => 'required|numeric|min:0',
            'sale_price' => 'required|numeric|min:0',
            'min_stock' => 'required|integer|min:0',
            'max_stock' => 'nullable|integer|min:0',
            'current_stock' => 'required|integer|min:0',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ];

        // Barcode - configurable, validate against product_barcodes table
        if ($this->isParentFieldVisible('barcode')) {
            $barcodeRule = 'unique:product_barcodes,barcode';
            // When editing, exclude the current product's existing barcode
            if ($this->itemId && $this->barcode) {
                $existingBarcode = ProductBarcode::where('product_id', $this->itemId)
                    ->whereNull('product_child_id')
                    ->where('barcode', $this->barcode)
                    ->first();
                if ($existingBarcode) {
                    $barcodeRule = 'unique:product_barcodes,barcode,' . $existingBarcode->id;
                }
            }
            $rules['barcode'] = $this->isParentFieldRequired('barcode') 
                ? "required|{$barcodeRule}"
                : "nullable|{$barcodeRule}";
        }

        // Presentation - configurable
        if ($this->isParentFieldVisible('presentation_id')) {
            $rules['presentation_id'] = $this->isParentFieldRequired('presentation_id')
                ? 'required|exists:presentations,id'
                : 'nullable|exists:presentations,id';
        }

        // Color - configurable
        if ($this->isParentFieldVisible('color_id')) {
            $rules['color_id'] = $this->isParentFieldRequired('color_id')
                ? 'required|exists:colors,id'
                : 'nullable|exists:colors,id';
        }

        // Product Model - configurable
        if ($this->isParentFieldVisible('product_model_id')) {
            $rules['product_model_id'] = $this->isParentFieldRequired('product_model_id')
                ? 'required|exists:product_models,id'
                : 'nullable|exists:product_models,id';
        }

        // Size - configurable
        if ($this->isParentFieldVisible('size')) {
            $rules['size'] = $this->isParentFieldRequired('size')
                ? 'required|string|max:50'
                : 'nullable|string|max:50';
        }

        // Weight - configurable
        if ($this->isParentFieldVisible('weight')) {
            $rules['weight'] = $this->isParentFieldRequired('weight')
                ? 'required|numeric|min:0'
                : 'nullable|numeric|min:0';
        }

        // IMEI - configurable
        if ($this->isParentFieldVisible('imei')) {
            $rules['imei'] = $this->isParentFieldRequired('imei')
                ? 'required|string|min:15|max:17'
                : 'nullable|string|min:15|max:17';
        }

        return $rules;
    }

    private function getParentValidationMessages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio',
            'name.min' => 'El nombre debe tener al menos 2 caracteres',
            'sku.unique' => 'El SKU ya está registrado',
            'barcode.unique' => 'El código de barras ya está registrado',
            'barcode.required' => 'El código de barras es obligatorio',
            'category_id.required' => 'La categoría es obligatoria',
            'category_id.exists' => 'La categoría seleccionada no existe',
            'unit_id.required' => 'La unidad es obligatoria',
            'unit_id.exists' => 'La unidad seleccionada no existe',
            'purchase_price.required' => 'El precio de compra es obligatorio',
            'purchase_price.numeric' => 'El precio de compra debe ser numérico',
            'sale_price.required' => 'El precio de venta es obligatorio',
            'sale_price.numeric' => 'El precio de venta debe ser numérico',
            'current_stock.required' => 'El stock inicial es obligatorio',
            'current_stock.integer' => 'El stock debe ser un número entero',
            'image.image' => 'El archivo debe ser una imagen',
            'image.mimes' => 'La imagen debe ser JPG, PNG o WebP',
            'image.max' => 'La imagen no debe superar 2MB',
            'presentation_id.required' => 'La presentación es obligatoria',
            'presentation_id.exists' => 'La presentación seleccionada no existe',
            'color_id.required' => 'El color es obligatorio',
            'color_id.exists' => 'El color seleccionado no existe',
            'product_model_id.required' => 'El modelo es obligatorio',
            'product_model_id.exists' => 'El modelo seleccionado no existe',
            'size.required' => 'La talla es obligatoria',
            'weight.required' => 'El peso es obligatorio',
            'weight.numeric' => 'El peso debe ser numérico',
            'imei.required' => 'El IMEI es obligatorio',
            'imei.min' => 'El IMEI debe tener al menos 15 caracteres',
            'imei.max' => 'El IMEI no puede tener más de 17 caracteres',
        ];
    }

    private function buildChildValidationRules(): array
    {
        $rules = [
            'childName' => 'required|min:2',
            'childProductId' => 'required|exists:products,id',
            'childUnitQuantity' => 'required|numeric|min:0.001',
            'childSku' => 'nullable|unique:product_children,sku,' . $this->childId,
            'childSalePrice' => 'required|numeric|min:0',
        ];

        // Add barcode validation against product_barcodes table
        if ($this->isChildFieldVisible('barcode')) {
            $barcodeRule = 'unique:product_barcodes,barcode';
            // When editing, exclude the current child's existing barcode
            if ($this->childId && $this->childBarcode) {
                $existingBarcode = ProductBarcode::where('product_child_id', $this->childId)
                    ->where('barcode', $this->childBarcode)
                    ->first();
                if ($existingBarcode) {
                    $barcodeRule = 'unique:product_barcodes,barcode,' . $existingBarcode->id;
                }
            }
            $rules['childBarcode'] = $this->isChildFieldRequired('barcode')
                ? "required|{$barcodeRule}"
                : "nullable|{$barcodeRule}";
        }

        // Add presentation validation
        if ($this->isChildFieldVisible('presentation_id')) {
            $rules['childPresentationId'] = $this->isChildFieldRequired('presentation_id')
                ? 'required|exists:presentations,id'
                : 'nullable|exists:presentations,id';
        }

        // Add color validation
        if ($this->isChildFieldVisible('color_id')) {
            $rules['childColorId'] = $this->isChildFieldRequired('color_id')
                ? 'required|exists:colors,id'
                : 'nullable|exists:colors,id';
        }

        // Add product model validation
        if ($this->isChildFieldVisible('product_model_id')) {
            $rules['childProductModelId'] = $this->isChildFieldRequired('product_model_id')
                ? 'required|exists:product_models,id'
                : 'nullable|exists:product_models,id';
        }

        // Add size validation
        if ($this->isChildFieldVisible('size')) {
            $rules['childSize'] = $this->isChildFieldRequired('size')
                ? 'required|string|max:50'
                : 'nullable|string|max:50';
        }

        // Add weight validation
        if ($this->isChildFieldVisible('weight')) {
            $rules['childWeight'] = $this->isChildFieldRequired('weight')
                ? 'required|numeric|min:0'
                : 'nullable|numeric|min:0';
        }

        // Add IMEI validation
        if ($this->isChildFieldVisible('imei')) {
            $rules['childImei'] = $this->isChildFieldRequired('imei')
                ? 'required|string|min:15|max:17'
                : 'nullable|string|min:15|max:17';
        }

        return $rules;
    }

    private function getChildValidationMessages(): array
    {
        return [
            'childName.required' => 'El nombre de la variante es obligatorio',
            'childName.min' => 'El nombre debe tener al menos 2 caracteres',
            'childProductId.required' => 'El producto padre es obligatorio',
            'childProductId.exists' => 'El producto padre no existe',
            'childUnitQuantity.required' => 'La cantidad de unidades es obligatoria',
            'childUnitQuantity.numeric' => 'La cantidad de unidades debe ser numérica',
            'childUnitQuantity.min' => 'La cantidad de unidades debe ser mayor a 0',
            'childSku.unique' => 'El SKU ya está registrado',
            'childBarcode.unique' => 'El código de barras ya existe',
            'childBarcode.required' => 'El código de barras es obligatorio',
            'childSalePrice.required' => 'El precio de venta es obligatorio',
            'childSalePrice.numeric' => 'El precio de venta debe ser numérico',
            'childSalePrice.min' => 'El precio de venta no puede ser negativo',
            'childPresentationId.required' => 'La presentación es obligatoria',
            'childPresentationId.exists' => 'La presentación seleccionada no existe',
            'childColorId.required' => 'El color es obligatorio',
            'childColorId.exists' => 'El color seleccionado no existe',
            'childProductModelId.required' => 'El modelo es obligatorio',
            'childProductModelId.exists' => 'El modelo seleccionado no existe',
            'childSize.required' => 'La talla es obligatoria',
            'childWeight.required' => 'El peso es obligatorio',
            'childWeight.numeric' => 'El peso debe ser numérico',
            'childImei.required' => 'El IMEI es obligatorio',
            'childImei.min' => 'El IMEI debe tener al menos 15 caracteres',
            'childImei.max' => 'El IMEI no puede tener más de 17 caracteres',
        ];
    }

    private function resetChildForm()
    {
        $this->childId = null;
        $this->childProductId = null;
        $this->parentProduct = null;
        $this->childUnitQuantity = 1;
        $this->childSku = null;
        $this->childBarcode = null;
        $this->childName = '';
        $this->childPresentationId = null;
        $this->childColorId = null;
        $this->childProductModelId = null;
        $this->childSize = null;
        $this->childWeight = null;
        $this->childSalePrice = 0;
        $this->childPriceIncludesTax = false;
        $this->childImei = null;
        $this->childIsActive = true;
        $this->childHasCommission = false;
        $this->childCommissionType = 'percentage';
        $this->childCommissionValue = null;
        $this->fieldSettings = [];
        $this->childImage = null;
        $this->childExistingImage = null;
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->filterCategory = null;
        $this->filterBrand = null;
        $this->filterStatus = null;
        $this->filterBranch = null;
        $this->filterHasVariants = null;
        $this->filterStockStatus = null;
        $this->sortBy = 'created_at';
        $this->sortDirection = 'desc';
        $this->resetPage();
    }

    public function sortByColumn(string $column)
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'desc';
        }
        $this->resetPage();
    }

    public function removeImage()
    {
        if ($this->existingImage && Storage::disk('public')->exists($this->existingImage)) {
            Storage::disk('public')->delete($this->existingImage);
        }
        $this->existingImage = null;
        $this->image = null;
    }

    public function removeChildImage()
    {
        if ($this->childExistingImage && Storage::disk('public')->exists($this->childExistingImage)) {
            Storage::disk('public')->delete($this->childExistingImage);
        }
        $this->childExistingImage = null;
        $this->childImage = null;
    }

    private function resetForm()
    {
        $this->itemId = null;
        $this->branch_id = null;
        $this->sku = null;
        $this->barcode = null;
        $this->name = '';
        $this->description = null;
        $this->category_id = null;
        $this->subcategory_id = null;
        $this->brand_id = null;
        $this->unit_id = null;
        $this->tax_id = null;
        $this->purchase_price = 0;
        $this->sale_price = 0;
        $this->special_price = null;
        $this->price_includes_tax = false;
        $this->min_stock = 0;
        $this->max_stock = null;
        $this->current_stock = 0;
        $this->is_active = true;
        $this->has_commission = false;
        $this->commission_type = 'percentage';
        $this->commission_value = null;
        $this->subcategories = [];
        $this->image = null;
        $this->existingImage = null;
        // Configurable fields
        $this->presentation_id = null;
        $this->color_id = null;
        $this->product_model_id = null;
        $this->size = null;
        $this->weight = null;
        $this->imei = null;
    }

    // ==================== CSV EXPORT METHOD ====================

    public function exportProducts()
    {
        if (!auth()->user()->hasPermission('products.view')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $user = auth()->user();

        // Determine branch
        $branchId = null;
        if ($this->needsBranchSelection) {
            if (!$this->filterBranch) {
                $this->dispatch('notify', message: 'Selecciona una sucursal antes de exportar', type: 'error');
                return;
            }
            $branchId = $this->filterBranch;
        } else {
            $branchId = $user->branch_id;
        }

        $products = Product::with(['tax', 'children.barcodes', 'barcodes'])
            ->where('branch_id', $branchId)
            ->orderBy('name')
            ->get();

        if ($products->isEmpty()) {
            $this->dispatch('notify', message: 'No hay productos para exportar', type: 'warning');
            return;
        }

        // Build CSV rows in the same format as the import template
        $headers = [
            'tipo', 'sku', 'nombre', 'descripcion', 'producto_padre_sku',
            'cantidad_unidades', 'stock_inicial', 'precio_compra', 'precio_venta',
            'codigo_barras', 'tiene_comision', 'tipo_comision', 'valor_comision',
            'precio_incluye_impuesto', 'impuesto_nombre',
        ];

        $rows = [];

        foreach ($products as $product) {
            // Get primary barcode
            $barcode = $product->getPrimaryBarcode() ?? $product->barcode ?? '';

            $rows[] = [
                'PADRE',
                $product->sku ?? '',
                $product->name,
                $product->description ?? '',
                '', // producto_padre_sku (empty for parents)
                '', // cantidad_unidades (empty for parents)
                (float) $product->current_stock,
                (float) $product->purchase_price,
                (float) $product->sale_price,
                $barcode,
                $product->has_commission ? 'SI' : 'NO',
                $product->has_commission ? strtoupper($product->commission_type === 'percentage' ? 'PORCENTAJE' : 'FIJO') : '',
                $product->has_commission ? (float) $product->commission_value : '',
                $product->price_includes_tax ? 'SI' : 'NO',
                $product->tax?->name ?? '',
            ];

            // Export children
            foreach ($product->children as $child) {
                $childBarcode = $child->getPrimaryBarcode() ?? $child->barcode ?? '';

                $rows[] = [
                    'VARIANTE',
                    $child->sku ?? '',
                    $child->name,
                    '', // description (children don't have it)
                    $product->sku ?? '', // producto_padre_sku
                    (float) $child->unit_quantity,
                    '', // stock_inicial (empty for variants)
                    '', // precio_compra (empty for variants)
                    (float) $child->sale_price,
                    $childBarcode,
                    $child->has_commission ? 'SI' : 'NO',
                    $child->has_commission ? strtoupper($child->commission_type === 'percentage' ? 'PORCENTAJE' : 'FIJO') : '',
                    $child->has_commission ? (float) $child->commission_value : '',
                    $child->price_includes_tax ? 'SI' : 'NO',
                    '', // impuesto_nombre (inherited from parent)
                ];
            }
        }

        // Generate CSV
        $filename = 'productos_export_' . date('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($headers, $rows) {
            $output = fopen('php://output', 'w');
            // UTF-8 BOM for Excel compatibility
            fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($output, $headers);
            foreach ($rows as $row) {
                fputcsv($output, $row);
            }
            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    // ==================== CSV IMPORT METHODS ====================

    public function openImportModal()
    {
        if (!auth()->user()->hasPermission('products.create')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->resetImportForm();
        $this->isImportModalOpen = true;
    }

    public function closeImportModal()
    {
        $this->isImportModalOpen = false;
        $this->resetImportForm();
    }

    private function resetImportForm()
    {
        $this->importFile = null;
        $this->importErrors = [];
        $this->importPreview = [];
        $this->importProcessed = false;
        $this->importSuccessCount = 0;
        $this->importErrorCount = 0;
        $this->isImporting = false;
        $this->importProgress = 0;
        $this->importTotal = 0;
        $this->showOnlyErrors = false;
        $this->showOnlyWarnings = false;
        $this->importFilter = 'all';
    }

    public function downloadTemplate()
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Productos');

        // Headers
        $headers = [
            'A1' => 'tipo',
            'B1' => 'sku',
            'C1' => 'nombre',
            'D1' => 'descripcion',
            'E1' => 'producto_padre_sku',
            'F1' => 'cantidad_unidades',
            'G1' => 'stock_inicial',
            'H1' => 'precio_compra',
            'I1' => 'precio_venta',
            'J1' => 'codigo_barras',
            'K1' => 'tiene_comision',
            'L1' => 'tipo_comision',
            'M1' => 'valor_comision',
            'N1' => 'precio_incluye_impuesto',
            'O1' => 'impuesto_nombre',
        ];

        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        // Style headers
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
        ];
        $sheet->getStyle('A1:O1')->applyFromArray($headerStyle);

        // Get available taxes for examples
        $taxes = Tax::where('is_active', true)->pluck('name')->toArray();
        $taxExample1 = $taxes[0] ?? 'IVA';
        $taxExample2 = $taxes[1] ?? 'EXENTO';

        // Example data
        $examples = [
            ['PADRE', 'MED-001', 'Acetaminofén 500mg', 'Analgésico y antipirético', '', '', 100, 1500, 2500, '7701234567890', 'SI', 'PORCENTAJE', 5, 'NO', $taxExample1],
            ['VARIANTE', '', 'Acetaminofén - Caja x 10', 'Caja de 10 tabletas', 'MED-001', 10, '', '', 22000, '7701234567891', 'NO', '', '', 'SI', ''],
            ['PADRE', 'MED-002', 'Ibuprofeno 400mg', 'Antiinflamatorio', '', '', 50, 2000, 3500, '', 'NO', '', '', 'NO', $taxExample2],
            ['VARIANTE', '', 'Ibuprofeno - Blister x 4', 'Blister de 4 tabletas', 'MED-002', 4, '', '', 12000, '', 'SI', 'FIJO', 500, 'NO', ''],
            ['PADRE', '', 'Aspirina 100mg', 'Sin variantes', '', '', 30, 800, 1500, '', 'NO', '', '', 'NO', ''],
        ];

        $row = 2;
        foreach ($examples as $data) {
            $col = 'A';
            foreach ($data as $value) {
                $sheet->setCellValue($col . $row, $value);
                $col++;
            }
            // Color rows by type
            $fillColor = $data[0] === 'PADRE' ? 'E0E7FF' : 'FEF3C7';
            $sheet->getStyle("A{$row}:O{$row}")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB($fillColor);
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'O') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Add borders to data
        $sheet->getStyle('A2:O6')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        // Instructions sheet
        $instructionsSheet = $spreadsheet->createSheet();
        $instructionsSheet->setTitle('Instrucciones');
        
        $instructions = [
            ['INSTRUCCIONES PARA IMPORTAR PRODUCTOS'],
            [''],
            ['PASO A PASO:'],
            ['1. Complete los datos en la hoja "Productos"'],
            ['2. Elimine las filas de ejemplo (filas 2-6) antes de agregar sus productos'],
            ['3. Guarde el archivo como CSV: Archivo → Guardar como → CSV UTF-8'],
            ['4. Suba el archivo CSV en el sistema'],
            [''],
            ['═══════════════════════════════════════════════════════════════'],
            ['CAMPOS OBLIGATORIOS:'],
            ['═══════════════════════════════════════════════════════════════'],
            ['- tipo: Escriba PADRE o VARIANTE'],
            ['- nombre: Nombre del producto'],
            ['- precio_venta: Precio de venta al público'],
            [''],
            ['═══════════════════════════════════════════════════════════════'],
            ['SOLO PARA PRODUCTOS PADRE:'],
            ['═══════════════════════════════════════════════════════════════'],
            ['- sku: Código único del producto (ej: MED-001, PROD-ABC)'],
            ['  * OBLIGATORIO si el producto tiene variantes'],
            ['  * Opcional si el producto NO tiene variantes (se genera automático)'],
            ['- stock_inicial: Cantidad inicial en inventario'],
            ['- precio_compra: Costo/precio de compra del producto'],
            ['- impuesto_nombre: Nombre del impuesto (ver lista abajo)'],
            [''],
            ['═══════════════════════════════════════════════════════════════'],
            ['SOLO PARA VARIANTES:'],
            ['═══════════════════════════════════════════════════════════════'],
            ['- producto_padre_sku: SKU del producto padre al que pertenece'],
            ['  Debe coincidir EXACTAMENTE con el SKU del padre (ej: MED-001)'],
            ['- cantidad_unidades: Cuántas unidades del padre consume esta variante'],
            ['  Ejemplo: Una caja de 10 tabletas consume 10 unidades del padre'],
            [''],
            ['IMPORTANTE: Las variantes heredan el impuesto del producto padre.'],
            [''],
            ['═══════════════════════════════════════════════════════════════'],
            ['CAMPOS OPCIONALES:'],
            ['═══════════════════════════════════════════════════════════════'],
            ['- descripcion: Descripción del producto'],
            ['- codigo_barras: Código de barras único (no repetir)'],
            ['- tiene_comision: Escriba SI o NO'],
            ['- tipo_comision: PORCENTAJE o FIJO (solo si tiene_comision = SI)'],
            ['- valor_comision: Número (ej: 5 para 5% o 500 para $500 fijo)'],
            ['- precio_incluye_impuesto: SI si el precio ya tiene impuesto, NO si no'],
            [''],
            ['═══════════════════════════════════════════════════════════════'],
            ['IMPUESTOS DISPONIBLES EN SU SISTEMA:'],
            ['═══════════════════════════════════════════════════════════════'],
            ['Escriba el NOMBRE exacto del impuesto en la columna impuesto_nombre:'],
            [''],
        ];

        $rowNum = 1;
        foreach ($instructions as $line) {
            $instructionsSheet->setCellValue('A' . $rowNum, $line[0] ?? '');
            $rowNum++;
        }
        
        // Add each tax on its own row
        $activeTaxes = Tax::where('is_active', true)->get();
        foreach ($activeTaxes as $tax) {
            $instructionsSheet->setCellValue('A' . $rowNum, "   → {$tax->name} ({$tax->value}%)");
            $rowNum++;
        }
        
        if ($activeTaxes->isEmpty()) {
            $instructionsSheet->setCellValue('A' . $rowNum, "   (No hay impuestos configurados)");
            $rowNum++;
        }
        
        $rowNum++;
        $instructionsSheet->setCellValue('A' . $rowNum, 'Si no desea aplicar impuesto, deje la columna vacía.');
        $rowNum += 2;
        
        $instructionsSheet->setCellValue('A' . $rowNum, '═══════════════════════════════════════════════════════════════');
        $rowNum++;
        $instructionsSheet->setCellValue('A' . $rowNum, 'EJEMPLO DE USO:');
        $rowNum++;
        $instructionsSheet->setCellValue('A' . $rowNum, '═══════════════════════════════════════════════════════════════');
        $rowNum++;
        $instructionsSheet->setCellValue('A' . $rowNum, 'Producto con variantes:');
        $rowNum++;
        $instructionsSheet->setCellValue('A' . $rowNum, '  Fila 1: PADRE | MED-001 | Acetaminofén 500mg | ... ');
        $rowNum++;
        $instructionsSheet->setCellValue('A' . $rowNum, '  Fila 2: VARIANTE | (vacío) | Caja x 10 | ... | MED-001 | 10');
        $rowNum++;
        $instructionsSheet->setCellValue('A' . $rowNum, '');
        $rowNum++;
        $instructionsSheet->setCellValue('A' . $rowNum, 'Producto SIN variantes:');
        $rowNum++;
        $instructionsSheet->setCellValue('A' . $rowNum, '  Fila 1: PADRE | (vacío) | Aspirina 100mg | ... ');
        $rowNum++;
        $instructionsSheet->setCellValue('A' . $rowNum, '  (El SKU se genera automáticamente)');
        $rowNum += 2;
        
        $instructionsSheet->setCellValue('A' . $rowNum, '═══════════════════════════════════════════════════════════════');
        $rowNum++;
        $instructionsSheet->setCellValue('A' . $rowNum, 'COLORES EN LA PLANTILLA:');
        $rowNum++;
        $instructionsSheet->setCellValue('A' . $rowNum, '═══════════════════════════════════════════════════════════════');
        $rowNum++;
        $instructionsSheet->setCellValue('A' . $rowNum, '- Azul claro: Productos PADRE');
        $rowNum++;
        $instructionsSheet->setCellValue('A' . $rowNum, '- Amarillo claro: VARIANTES');
        
        // Style title
        $instructionsSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $instructionsSheet->getColumnDimension('A')->setWidth(70);

        // Set first sheet as active
        $spreadsheet->setActiveSheetIndex(0);

        // Generate file
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        
        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'plantilla_productos.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function updatedImportFile()
    {
        $this->importErrors = [];
        $this->importPreview = [];
        $this->importProcessed = false;

        if (!$this->importFile) {
            return;
        }

        // Validate file type
        $extension = $this->importFile->getClientOriginalExtension();
        if (strtolower($extension) !== 'csv') {
            $this->importErrors[] = ['row' => 0, 'message' => 'El archivo debe ser CSV'];
            return;
        }

        $this->processImportPreview();
    }

    private function processImportPreview()
    {
        $path = $this->importFile->getRealPath();
        $content = file_get_contents($path);
        
        if (!$content) {
            $this->importErrors[] = ['row' => 0, 'message' => 'No se pudo leer el archivo'];
            return;
        }

        // Clean BOM if present
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        
        // Detect separator (comma, semicolon, or tab)
        $firstLine = strtok($content, "\n");
        $commaCount = substr_count($firstLine, ',');
        $semicolonCount = substr_count($firstLine, ';');
        $tabCount = substr_count($firstLine, "\t");
        
        if ($tabCount > $commaCount && $tabCount > $semicolonCount) {
            $separator = "\t";
        } elseif ($semicolonCount > $commaCount) {
            $separator = ';';
        } else {
            $separator = ',';
        }
        
        // Parse CSV with detected separator
        $lines = array_filter(explode("\n", $content), fn($line) => trim($line) !== '');
        
        if (count($lines) < 1) {
            $this->importErrors[] = ['row' => 0, 'message' => 'El archivo está vacío'];
            return;
        }

        // Read header
        $header = str_getcsv(array_shift($lines), $separator);
        $header = array_map('trim', array_map('strtolower', $header));

        // Required columns
        $requiredColumns = ['tipo', 'nombre', 'precio_venta'];
        $missingColumns = array_diff($requiredColumns, $header);
        
        if (!empty($missingColumns)) {
            $this->importErrors[] = ['row' => 0, 'message' => 'Columnas faltantes: ' . implode(', ', $missingColumns) . '. Separador detectado: ' . ($separator === ';' ? 'punto y coma' : ($separator === "\t" ? 'tabulador' : 'coma'))];
            return;
        }

        // First pass: collect all parent SKUs and barcodes from the file
        $parentSkusInFile = [];
        $barcodesInFile = []; // Track barcodes and their first occurrence row
        $rowNumber = 1;
        foreach ($lines as $line) {
            $rowNumber++;
            $row = str_getcsv($line, $separator);
            if (count($row) >= count($header)) {
                $data = array_combine($header, array_map('trim', $row));
                $tipo = strtoupper($data['tipo'] ?? '');
                if ($tipo === 'PADRE' && !empty($data['sku'])) {
                    $parentSkusInFile[] = $data['sku'];
                }
                // Track barcodes
                if (!empty($data['codigo_barras'])) {
                    $barcode = $data['codigo_barras'];
                    if (!isset($barcodesInFile[$barcode])) {
                        $barcodesInFile[$barcode] = $rowNumber;
                    }
                }
            }
        }

        // Second pass: validate and preview
        $rowNumber = 1;
        $preview = [];
        $seenBarcodes = []; // Track barcodes we've already seen in this pass

        foreach ($lines as $line) {
            $rowNumber++;
            $row = str_getcsv($line, $separator);
            
            if (count($row) < count($header)) {
                $this->importErrors[] = ['row' => $rowNumber, 'message' => 'Número de columnas incorrecto'];
                continue;
            }

            $data = array_combine($header, array_map('trim', $row));
            $result = $this->validateImportRow($data, $rowNumber, $parentSkusInFile, $seenBarcodes);
            $errors = $result['errors'];
            $warnings = $result['warnings'];
            
            // Track seen barcodes for duplicate detection
            if (!empty($data['codigo_barras'])) {
                $seenBarcodes[$data['codigo_barras']] = $rowNumber;
            }

            $preview[] = [
                'row' => $rowNumber,
                'data' => $data,
                'errors' => $errors,
                'warnings' => $warnings,
                'valid' => empty($errors),
                'hasWarnings' => !empty($warnings),
            ];
        }

        $this->importPreview = $preview;
    }

    private function validateImportRow(array $data, int $rowNumber, array $parentSkusInFile = [], array $seenBarcodes = []): array
    {
        $errors = [];
        $warnings = [];
        $tipo = strtoupper($data['tipo'] ?? '');

        // Validate type
        if (!in_array($tipo, ['PADRE', 'VARIANTE'])) {
            $errors[] = "Tipo debe ser PADRE o VARIANTE";
        }

        // Validate name
        if (empty($data['nombre'])) {
            $errors[] = "Nombre es obligatorio";
        }

        // Validate sale price
        if (!is_numeric($data['precio_venta'] ?? '') || floatval($data['precio_venta']) < 0) {
            $errors[] = "Precio de venta debe ser un número válido";
        }

        if ($tipo === 'PADRE') {
            // Parent-specific validations
            if (!is_numeric($data['stock_inicial'] ?? '') || intval($data['stock_inicial']) < 0) {
                $errors[] = "Stock inicial debe ser un número válido para productos padre";
            }
            if (!is_numeric($data['precio_compra'] ?? '') || floatval($data['precio_compra']) < 0) {
                $errors[] = "Precio de compra debe ser un número válido para productos padre";
            }
            
            // Validate SKU uniqueness if provided
            if (!empty($data['sku'])) {
                $skuExists = Product::where('sku', $data['sku'])->exists();
                if ($skuExists) {
                    $errors[] = "SKU '{$data['sku']}' ya existe en el sistema";
                }
            }
        }

        if ($tipo === 'VARIANTE') {
            // Variant-specific validations
            if (empty($data['producto_padre_sku'])) {
                $errors[] = "SKU del producto padre es obligatorio para variantes";
            } else {
                // Check if parent exists in database
                $parentExists = Product::where('sku', $data['producto_padre_sku'])->exists();
                if (!$parentExists) {
                    // Check if parent will be created in this import
                    $willBeCreated = in_array($data['producto_padre_sku'], $parentSkusInFile);
                    if (!$willBeCreated) {
                        $errors[] = "Producto padre con SKU '{$data['producto_padre_sku']}' no existe. Asegúrese de que el padre tenga ese SKU en la columna 'sku'";
                    }
                }
            }

            if (!is_numeric($data['cantidad_unidades'] ?? '') || floatval($data['cantidad_unidades']) <= 0) {
                $errors[] = "Cantidad de unidades debe ser mayor a 0 para variantes";
            }
        }

        // Validate commission
        $tieneComision = strtoupper($data['tiene_comision'] ?? '') === 'SI';
        if ($tieneComision) {
            $tipoComision = strtoupper($data['tipo_comision'] ?? '');
            if (!in_array($tipoComision, ['PORCENTAJE', 'FIJO'])) {
                $errors[] = "Tipo de comisión debe ser PORCENTAJE o FIJO";
            }
            if (!is_numeric($data['valor_comision'] ?? '') || floatval($data['valor_comision']) < 0) {
                $errors[] = "Valor de comisión debe ser un número válido";
            }
        }

        // Validate barcode - check for duplicates (warning, not error)
        if (!empty($data['codigo_barras'])) {
            $barcode = $data['codigo_barras'];
            
            // Check if barcode exists in database
            $barcodeExistsInDb = Product::where('barcode', $barcode)->exists() ||
                                 ProductChild::where('barcode', $barcode)->exists();
            if ($barcodeExistsInDb) {
                $warnings[] = "Código de barras '{$barcode}' ya existe en el sistema (se omitirá)";
            }
            
            // Check if barcode is duplicated within the file
            if (isset($seenBarcodes[$barcode])) {
                $warnings[] = "Código de barras '{$barcode}' duplicado en fila {$seenBarcodes[$barcode]} (se omitirá)";
            }
        }

        return [
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    public function executeImport()
    {
        if (!auth()->user()->hasPermission('products.create')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        if (empty($this->importPreview)) {
            $this->dispatch('notify', message: 'No hay datos para importar', type: 'error');
            return;
        }

        // Check if user needs to select branch
        $user = auth()->user();
        $branchId = $user->branch_id;

        if ($this->needsBranchSelection && !$this->filterBranch) {
            $this->dispatch('notify', message: 'Debe seleccionar una sucursal antes de importar', type: 'error');
            return;
        }

        if ($this->needsBranchSelection) {
            $branchId = $this->filterBranch;
        }

        // Get default unit
        $defaultUnit = Unit::where('is_active', true)->first();
        if (!$defaultUnit) {
            $this->dispatch('notify', message: 'No hay unidades de medida configuradas', type: 'error');
            return;
        }

        // Get default category
        $defaultCategory = Category::where('is_active', true)->first();
        if (!$defaultCategory) {
            $this->dispatch('notify', message: 'No hay categorías configuradas', type: 'error');
            return;
        }

        // Initialize progress tracking
        $this->isImporting = true;
        $this->importProgress = 0;
        $validRows = array_filter($this->importPreview, fn($row) => $row['valid']);
        $this->importTotal = count($validRows);

        $successCount = 0;
        $errorCount = 0;
        $createdParentSkus = [];
        
        // Track used barcodes during import to avoid duplicates
        $usedBarcodes = [];

        // Separate parents and variants
        $parents = array_filter($this->importPreview, fn($row) => strtoupper($row['data']['tipo'] ?? '') === 'PADRE' && $row['valid']);
        $variants = array_filter($this->importPreview, fn($row) => strtoupper($row['data']['tipo'] ?? '') === 'VARIANTE' && $row['valid']);

        // Pre-load taxes for faster lookup
        $taxesMap = Tax::where('is_active', true)->pluck('id', 'name')->toArray();

        // Get system document for initial stock (once)
        $systemDocument = \App\Models\SystemDocument::findByCode('initial_stock');

        // Disable query logging for performance
        \DB::disableQueryLog();

        // Process each parent individually with its own transaction
        foreach ($parents as $row) {
            \DB::beginTransaction();
            try {
                $data = $row['data'];

                // Find tax if specified
                $taxId = null;
                if (!empty($data['impuesto_nombre']) && isset($taxesMap[$data['impuesto_nombre']])) {
                    $taxId = $taxesMap[$data['impuesto_nombre']];
                }

                $sku = !empty($data['sku']) ? $data['sku'] : null;
                
                // Handle barcode: only use if not already used
                $barcode = null;
                if (!empty($data['codigo_barras'])) {
                    $barcodeValue = $data['codigo_barras'];
                    if (!isset($usedBarcodes[$barcodeValue])) {
                        // Check if barcode exists in database
                        $barcodeExists = Product::where('barcode', $barcodeValue)->exists() ||
                                        ProductChild::where('barcode', $barcodeValue)->exists();
                        if (!$barcodeExists) {
                            $barcode = $barcodeValue;
                            $usedBarcodes[$barcodeValue] = true;
                        }
                    }
                }

                $product = Product::create([
                    'branch_id' => $branchId,
                    'sku' => $sku,
                    'name' => mb_strtoupper($data['nombre']),
                    'description' => !empty($data['descripcion']) ? mb_strtoupper($data['descripcion']) : null,
                    'barcode' => $barcode,
                    'category_id' => $defaultCategory->id,
                    'unit_id' => $defaultUnit->id,
                    'tax_id' => $taxId,
                    'purchase_price' => floatval($data['precio_compra'] ?? 0),
                    'sale_price' => floatval($data['precio_venta']),
                    'price_includes_tax' => strtoupper($data['precio_incluye_impuesto'] ?? '') === 'SI',
                    'current_stock' => intval($data['stock_inicial'] ?? 0),
                    'min_stock' => 0,
                    'is_active' => true,
                    'has_commission' => strtoupper($data['tiene_comision'] ?? '') === 'SI',
                    'commission_type' => strtoupper($data['tipo_comision'] ?? '') === 'FIJO' ? 'fixed' : 'percentage',
                    'commission_value' => floatval($data['valor_comision'] ?? 0),
                ]);

                if (!$product->sku) {
                    $product->generateSku();
                    $product->save();
                }

                // Store the product ID mapped to SKU
                if (!empty($data['sku'])) {
                    $createdParentSkus[$data['sku']] = $product->id;
                }
                $createdParentSkus[$product->sku] = $product->id;

                $stockInicial = intval($data['stock_inicial'] ?? 0);
                if ($stockInicial > 0 && $systemDocument) {
                    \App\Models\InventoryMovement::create([
                        'system_document_id' => $systemDocument->id,
                        'document_number' => $systemDocument->generateNextNumber(),
                        'product_id' => $product->id,
                        'branch_id' => $branchId,
                        'user_id' => auth()->id(),
                        'movement_type' => 'in',
                        'quantity' => $stockInicial,
                        'stock_before' => 0,
                        'stock_after' => $stockInicial,
                        'unit_cost' => floatval($data['precio_compra'] ?? 0),
                        'total_cost' => floatval($data['precio_compra'] ?? 0) * $stockInicial,
                        'notes' => "Stock inicial importado",
                        'movement_date' => now(),
                    ]);
                }

                \DB::commit();
                $successCount++;
                $this->importProgress = $successCount;
            } catch (\Exception $e) {
                \DB::rollBack();
                $errorCount++;
                $this->importErrors[] = ['row' => $row['row'], 'message' => 'Error: ' . $e->getMessage()];
            }
        }

        // Process each variant individually with its own transaction
        foreach ($variants as $row) {
            \DB::beginTransaction();
            try {
                $data = $row['data'];

                $parentSku = $data['producto_padre_sku'];
                $parentId = $createdParentSkus[$parentSku] ?? null;

                if (!$parentId) {
                    // Query the database to find the parent
                    $parent = Product::where('sku', $parentSku)->first();
                    if ($parent) {
                        $parentId = $parent->id;
                        $createdParentSkus[$parentSku] = $parentId;
                    }
                }

                if (!$parentId) {
                    \DB::rollBack();
                    $errorCount++;
                    $this->importErrors[] = ['row' => $row['row'], 'message' => "Producto padre con SKU '{$parentSku}' no encontrado"];
                    continue;
                }

                // Verify parent exists in database before creating child
                $parentExists = Product::where('id', $parentId)->exists();
                if (!$parentExists) {
                    \DB::rollBack();
                    $errorCount++;
                    $this->importErrors[] = ['row' => $row['row'], 'message' => "Producto padre ID {$parentId} no existe en la base de datos"];
                    continue;
                }

                // Handle barcode: only use if not already used
                $barcode = null;
                if (!empty($data['codigo_barras'])) {
                    $barcodeValue = $data['codigo_barras'];
                    if (!isset($usedBarcodes[$barcodeValue])) {
                        // Check if barcode exists in database
                        $barcodeExists = Product::where('barcode', $barcodeValue)->exists() ||
                                        ProductChild::where('barcode', $barcodeValue)->exists();
                        if (!$barcodeExists) {
                            $barcode = $barcodeValue;
                            $usedBarcodes[$barcodeValue] = true;
                        }
                    }
                }

                ProductChild::create([
                    'product_id' => $parentId,
                    'name' => mb_strtoupper($data['nombre']),
                    'barcode' => $barcode,
                    'unit_quantity' => floatval($data['cantidad_unidades'] ?? 1),
                    'sale_price' => floatval($data['precio_venta']),
                    'price_includes_tax' => strtoupper($data['precio_incluye_impuesto'] ?? '') === 'SI',
                    'is_active' => true,
                    'has_commission' => strtoupper($data['tiene_comision'] ?? '') === 'SI',
                    'commission_type' => strtoupper($data['tipo_comision'] ?? '') === 'FIJO' ? 'fixed' : 'percentage',
                    'commission_value' => floatval($data['valor_comision'] ?? 0),
                ]);

                \DB::commit();
                $successCount++;
                $this->importProgress = $successCount;
            } catch (\Exception $e) {
                \DB::rollBack();
                $errorCount++;
                $this->importErrors[] = ['row' => $row['row'], 'message' => 'Error variante: ' . $e->getMessage()];
            }
        }

        // Re-enable query logging
        \DB::enableQueryLog();

        $this->isImporting = false;
        $this->importProcessed = true;
        $this->importSuccessCount = $successCount;
        $this->importErrorCount = $errorCount;

        if ($successCount > 0) {
            $this->dispatch('notify', message: "{$successCount} productos importados correctamente", type: 'success');
        }

        if ($errorCount > 0) {
            $this->dispatch('notify', message: "{$errorCount} productos con errores", type: 'warning');
        }
    }

    // ==================== BARCODE MANAGEMENT METHODS ====================

    /**
     * Open barcode management modal for a product.
     */
    public function manageBarcodes(int $productId)
    {
        if (!auth()->user()->hasPermission('products.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $product = Product::findOrFail($productId);
        $this->barcodeProductId = $productId;
        $this->barcodeProductChildId = null;
        $this->barcodeProductName = $product->name;
        $this->loadProductBarcodes();
        $this->newBarcode = '';
        $this->newBarcodeDescription = '';
        $this->isBarcodeModalOpen = true;
    }

    /**
     * Open barcode management modal for a product child (variant).
     */
    public function manageChildBarcodes(int $childId)
    {
        if (!auth()->user()->hasPermission('products.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $child = ProductChild::with('product')->findOrFail($childId);
        $this->barcodeProductId = null;
        $this->barcodeProductChildId = $childId;
        $this->barcodeProductName = $child->product->name . ' - ' . $child->name;
        $this->loadProductBarcodes();
        $this->newBarcode = '';
        $this->newBarcodeDescription = '';
        $this->isBarcodeModalOpen = true;
    }

    /**
     * Load barcodes for the current product or product child.
     */
    private function loadProductBarcodes()
    {
        if ($this->barcodeProductChildId) {
            $this->productBarcodes = ProductBarcode::where('product_child_id', $this->barcodeProductChildId)
                ->orderByDesc('is_primary')
                ->orderBy('created_at')
                ->get()
                ->toArray();
        } elseif ($this->barcodeProductId) {
            $this->productBarcodes = ProductBarcode::where('product_id', $this->barcodeProductId)
                ->whereNull('product_child_id')
                ->orderByDesc('is_primary')
                ->orderBy('created_at')
                ->get()
                ->toArray();
        }
    }

    /**
     * Add a new barcode to the product.
     */
    public function addBarcode()
    {
        if (!auth()->user()->hasPermission('products.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $this->validate([
            'newBarcode' => 'required|min:3|unique:product_barcodes,barcode',
        ], [
            'newBarcode.required' => 'El código de barras es obligatorio',
            'newBarcode.min' => 'El código de barras debe tener al menos 3 caracteres',
            'newBarcode.unique' => 'Este código de barras ya existe en el sistema',
        ]);

        // Determine if this is the first barcode (make it primary)
        $isPrimary = count($this->productBarcodes) === 0;

        ProductBarcode::create([
            'product_id' => $this->barcodeProductId,
            'product_child_id' => $this->barcodeProductChildId,
            'barcode' => trim($this->newBarcode),
            'description' => trim($this->newBarcodeDescription) ?: null,
            'is_primary' => $isPrimary,
        ]);

        $this->newBarcode = '';
        $this->newBarcodeDescription = '';
        $this->loadProductBarcodes();
        $this->dispatch('notify', message: 'Código de barras agregado');
    }

    /**
     * Set a barcode as primary.
     */
    public function setPrimaryBarcode(int $barcodeId)
    {
        if (!auth()->user()->hasPermission('products.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $barcode = ProductBarcode::findOrFail($barcodeId);

        // Remove primary from all other barcodes of this product/child
        if ($barcode->product_child_id) {
            ProductBarcode::where('product_child_id', $barcode->product_child_id)
                ->update(['is_primary' => false]);
        } else {
            ProductBarcode::where('product_id', $barcode->product_id)
                ->whereNull('product_child_id')
                ->update(['is_primary' => false]);
        }

        // Set this one as primary
        $barcode->update(['is_primary' => true]);

        $this->loadProductBarcodes();
        $this->dispatch('notify', message: 'Código principal actualizado');
    }

    /**
     * Delete a barcode.
     */
    public function deleteBarcode(int $barcodeId)
    {
        if (!auth()->user()->hasPermission('products.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $barcode = ProductBarcode::findOrFail($barcodeId);
        $wasPrimary = $barcode->is_primary;
        $barcode->delete();

        // If deleted barcode was primary, make the first remaining one primary
        if ($wasPrimary) {
            $this->loadProductBarcodes();
            if (count($this->productBarcodes) > 0) {
                $firstBarcode = ProductBarcode::find($this->productBarcodes[0]['id']);
                if ($firstBarcode) {
                    $firstBarcode->update(['is_primary' => true]);
                }
            }
        }

        $this->loadProductBarcodes();
        $this->dispatch('notify', message: 'Código de barras eliminado');
    }

    /**
     * Close barcode modal.
     */
    public function closeBarcodeModal()
    {
        $this->isBarcodeModalOpen = false;
        $this->barcodeProductId = null;
        $this->barcodeProductChildId = null;
        $this->barcodeProductName = '';
        $this->productBarcodes = [];
        $this->newBarcode = '';
        $this->newBarcodeDescription = '';
    }

    // ==================== BULK DELETE METHODS ====================

    public function openBulkDeleteModal()
    {
        if (!auth()->user()->hasPermission('products.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->resetBulkDeleteForm();
        $this->isBulkDeleteModalOpen = true;
    }

    public function closeBulkDeleteModal()
    {
        $this->isBulkDeleteModalOpen = false;
        $this->resetBulkDeleteForm();
    }

    private function resetBulkDeleteForm()
    {
        $this->bulkDeleteSearch = '';
        $this->bulkDeleteStockFilter = '';
        $this->bulkDeleteStatusFilter = '';
        $this->bulkDeleteCategoryFilter = null;
        $this->bulkDeleteSelected = [];
        $this->isBulkDeleting = false;
    }

    public function getBulkDeleteProductsProperty()
    {
        $user = auth()->user();
        $query = Product::query()
            ->with(['category', 'brand'])
            ->withCount('activeChildren');

        // Branch filter
        if ($this->needsBranchSelection) {
            if ($this->filterBranch) {
                $query->where('branch_id', $this->filterBranch);
            } else {
                return collect();
            }
        } else {
            $query->where('branch_id', $user->branch_id);
        }

        // Search
        if ($this->bulkDeleteSearch) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->bulkDeleteSearch}%")
                  ->orWhere('sku', 'like', "%{$this->bulkDeleteSearch}%");
            });
        }

        // Stock filter
        if ($this->bulkDeleteStockFilter === 'zero') {
            $query->where('current_stock', '<=', 0);
        } elseif ($this->bulkDeleteStockFilter === 'low') {
            $query->whereRaw('current_stock <= min_stock')->where('current_stock', '>', 0);
        } elseif ($this->bulkDeleteStockFilter === 'has') {
            $query->where('current_stock', '>', 0);
        }

        // Status filter
        if ($this->bulkDeleteStatusFilter !== '') {
            $query->where('is_active', $this->bulkDeleteStatusFilter === '1');
        }

        // Category filter
        if ($this->bulkDeleteCategoryFilter) {
            $query->where('category_id', $this->bulkDeleteCategoryFilter);
        }

        return $query->orderBy('name')->limit(100)->get();
    }

    public function toggleBulkDeleteProduct($productId)
    {
        $productId = (int) $productId;
        if (in_array($productId, $this->bulkDeleteSelected)) {
            $this->bulkDeleteSelected = array_values(array_diff($this->bulkDeleteSelected, [$productId]));
        } else {
            $this->bulkDeleteSelected[] = $productId;
        }
    }

    public function selectAllBulkDelete()
    {
        $products = $this->getBulkDeleteProductsProperty();
        // Only select products that can be deleted (no active children)
        $deletable = $products->filter(fn($p) => $p->active_children_count === 0);
        $this->bulkDeleteSelected = $deletable->pluck('id')->toArray();
    }

    public function deselectAllBulkDelete()
    {
        $this->bulkDeleteSelected = [];
    }

    public function executeBulkDelete()
    {
        if (!auth()->user()->hasPermission('products.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        if (empty($this->bulkDeleteSelected)) {
            $this->dispatch('notify', message: 'No hay productos seleccionados', type: 'warning');
            return;
        }

        $this->isBulkDeleting = true;
        $deleted = 0;
        $skipped = 0;

        try {
            DB::beginTransaction();

            foreach ($this->bulkDeleteSelected as $productId) {
                $product = Product::with('children')->find($productId);
                if (!$product) continue;

                if (!$product->canDelete()) {
                    $skipped++;
                    continue;
                }

                // Delete child images
                foreach ($product->children as $child) {
                    if ($child->image && Storage::disk('public')->exists($child->image)) {
                        Storage::disk('public')->delete($child->image);
                    }
                }
                $product->children()->delete();

                // Delete product barcodes
                $product->barcodes()->delete();

                // Delete product image
                if ($product->image && Storage::disk('public')->exists($product->image)) {
                    Storage::disk('public')->delete($product->image);
                }

                ActivityLogService::logDelete('products', $product, "Producto '{$product->name}' eliminado (masivo)");
                $product->delete();
                $deleted++;
            }

            DB::commit();

            $message = "{$deleted} producto(s) eliminado(s)";
            if ($skipped > 0) {
                $message .= ". {$skipped} omitido(s) por tener variantes activas";
            }

            $this->dispatch('notify', message: $message, type: $skipped > 0 ? 'warning' : 'success');
            $this->closeBulkDeleteModal();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('notify', message: 'Error al eliminar: ' . $e->getMessage(), type: 'error');
        }

        $this->isBulkDeleting = false;
    }

}

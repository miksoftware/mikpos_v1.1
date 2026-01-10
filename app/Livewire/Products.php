<?php

namespace App\Livewire;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Color;
use App\Models\Presentation;
use App\Models\Product;
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

    // Modal states
    public bool $isModalOpen = false;
    public bool $isDeleteModalOpen = false;
    public bool $isChildModalOpen = false;
    public bool $isChildDeleteModalOpen = false;
    public ?int $itemIdToDelete = null;
    public ?int $childIdToDelete = null;

    // Form data for parent product
    public ?int $itemId = null;
    public ?string $sku = null;
    public string $name = '';
    public ?string $description = null;
    public ?int $category_id = null;
    public ?int $subcategory_id = null;
    public ?int $brand_id = null;
    public ?int $unit_id = null;
    public ?int $tax_id = null;
    public float $purchase_price = 0;
    public float $sale_price = 0;
    public bool $price_includes_tax = false;
    public int $min_stock = 0;
    public ?int $max_stock = null;
    public int $current_stock = 0;
    public bool $is_active = true;
    public $image = null; // For file upload
    public ?string $existingImage = null; // To track existing image path

    // Form data for child product
    public ?int $childId = null;
    public ?int $childProductId = null;
    public ?string $childSku = null;
    public ?string $childBarcode = null;
    public string $childName = '';
    public ?int $childPresentationId = null;
    public ?int $childColorId = null;
    public ?int $childProductModelId = null;
    public ?string $childSize = null;
    public ?float $childWeight = null;
    public float $childPurchasePrice = 0;
    public float $childSalePrice = 0;
    public bool $childPriceIncludesTax = false;
    public int $childMinStock = 0;
    public ?int $childMaxStock = null;
    public ?string $childImei = null;
    public bool $childIsActive = true;
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

    public function render()
    {
        $items = Product::query()
            ->with(['category', 'subcategory', 'brand', 'unit', 'tax', 'children.presentation', 'children.color', 'children.productModel'])
            ->withCount('children')
            ->withCount('activeChildren')
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('name', 'like', "%{$this->search}%")
                        ->orWhere('sku', 'like', "%{$this->search}%")
                        ->orWhere('description', 'like', "%{$this->search}%")
                        ->orWhereHas('children', function ($childQuery) {
                            $childQuery->where('name', 'like', "%{$this->search}%")
                                ->orWhere('sku', 'like', "%{$this->search}%")
                                ->orWhere('barcode', 'like', "%{$this->search}%");
                        });
                });
            })
            ->when($this->filterCategory, fn($q) => $q->where('category_id', $this->filterCategory))
            ->when($this->filterBrand, fn($q) => $q->where('brand_id', $this->filterBrand))
            ->when($this->filterStatus !== null && $this->filterStatus !== '', function ($q) {
                $q->where('is_active', $this->filterStatus === '1');
            })
            ->latest()
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
        $this->isModalOpen = true;
    }

    public function edit(int $id)
    {
        if (!auth()->user()->hasPermission('products.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->resetValidation();
        $item = Product::findOrFail($id);
        
        $this->itemId = $item->id;
        $this->sku = $item->sku;
        $this->name = $item->name;
        $this->description = $item->description;
        $this->category_id = $item->category_id;
        $this->subcategory_id = $item->subcategory_id;
        $this->brand_id = $item->brand_id;
        $this->unit_id = $item->unit_id;
        $this->tax_id = $item->tax_id;
        $this->purchase_price = (float) $item->purchase_price;
        $this->sale_price = (float) $item->sale_price;
        $this->price_includes_tax = $item->price_includes_tax;
        $this->min_stock = $item->min_stock;
        $this->max_stock = $item->max_stock;
        $this->current_stock = $item->current_stock;
        $this->is_active = $item->is_active;
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

        $this->validate([
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
        ], [
            'name.required' => 'El nombre es obligatorio',
            'name.min' => 'El nombre debe tener al menos 2 caracteres',
            'sku.unique' => 'El SKU ya está registrado',
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
        ]);

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

        $item = Product::updateOrCreate(['id' => $this->itemId], [
            'name' => $this->name,
            'description' => $this->description,
            'category_id' => $this->category_id,
            'subcategory_id' => $this->subcategory_id ?: null,
            'brand_id' => $this->brand_id ?: null,
            'unit_id' => $this->unit_id,
            'tax_id' => $this->tax_id ?: null,
            'purchase_price' => $this->purchase_price,
            'sale_price' => $this->sale_price,
            'price_includes_tax' => $this->price_includes_tax,
            'min_stock' => $this->min_stock,
            'max_stock' => $this->max_stock ?: null,
            'current_stock' => $this->current_stock,
            'is_active' => $this->is_active,
            'image' => $imagePath,
        ]);

        // Generate SKU if not provided
        if (!$item->sku) {
            $item->generateSku();
            $item->save();
        } elseif ($this->sku && $this->sku !== $item->sku) {
            $item->sku = $this->sku;
            $item->save();
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
        $this->childSku = $child->sku;
        $this->childBarcode = $child->barcode;
        $this->childName = $child->name;
        $this->childPresentationId = $child->presentation_id;
        $this->childColorId = $child->color_id;
        $this->childProductModelId = $child->product_model_id;
        $this->childSize = $child->size;
        $this->childWeight = $child->weight;
        $this->childPurchasePrice = (float) $child->purchase_price;
        $this->childSalePrice = (float) $child->sale_price;
        $this->childPriceIncludesTax = $child->price_includes_tax;
        $this->childMinStock = $child->min_stock;
        $this->childMaxStock = $child->max_stock;
        $this->childImei = $child->imei;
        $this->childIsActive = $child->is_active;
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
            'sku' => $this->childSku ?: null,
            'barcode' => $this->childBarcode ?: null,
            'name' => $this->childName,
            'presentation_id' => $this->childPresentationId ?: null,
            'color_id' => $this->childColorId ?: null,
            'product_model_id' => $this->childProductModelId ?: null,
            'size' => $this->childSize ?: null,
            'weight' => $this->childWeight ?: null,
            'purchase_price' => $this->childPurchasePrice,
            'sale_price' => $this->childSalePrice,
            'price_includes_tax' => $this->childPriceIncludesTax,
            'min_stock' => $this->childMinStock,
            'max_stock' => $this->childMaxStock ?: null,
            'imei' => $this->childImei ?: null,
            'is_active' => $this->childIsActive,
            'image' => $imagePath,
        ]);

        $parentName = $this->parentProduct?->name ?? 'Producto';
        
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

    private function isFieldVisible(string $fieldName): bool
    {
        if (!isset($this->fieldSettings[$fieldName])) {
            return true; // Default to visible if not configured
        }
        
        $field = $this->fieldSettings[$fieldName];
        return is_object($field) ? $field->is_visible : ($field['is_visible'] ?? true);
    }

    private function isFieldRequired(string $fieldName): bool
    {
        if (!isset($this->fieldSettings[$fieldName])) {
            return false; // Default to not required if not configured
        }
        
        $field = $this->fieldSettings[$fieldName];
        $isVisible = is_object($field) ? $field->is_visible : ($field['is_visible'] ?? true);
        $isRequired = is_object($field) ? $field->is_required : ($field['is_required'] ?? false);
        
        // Only required if visible AND marked as required
        return $isVisible && $isRequired;
    }

    private function buildChildValidationRules(): array
    {
        $rules = [
            'childName' => 'required|min:2',
            'childProductId' => 'required|exists:products,id',
            'childSku' => 'nullable|unique:product_children,sku,' . $this->childId,
            'childPurchasePrice' => 'required|numeric|min:0',
            'childSalePrice' => 'required|numeric|min:0',
            'childMinStock' => 'required|integer|min:0',
        ];

        // Add barcode validation
        if ($this->isFieldVisible('barcode')) {
            $rules['childBarcode'] = 'nullable|unique:product_children,barcode,' . $this->childId;
            if ($this->isFieldRequired('barcode')) {
                $rules['childBarcode'] = 'required|unique:product_children,barcode,' . $this->childId;
            }
        }

        // Add presentation validation
        if ($this->isFieldVisible('presentation_id')) {
            $rules['childPresentationId'] = 'nullable|exists:presentations,id';
            if ($this->isFieldRequired('presentation_id')) {
                $rules['childPresentationId'] = 'required|exists:presentations,id';
            }
        }

        // Add color validation
        if ($this->isFieldVisible('color_id')) {
            $rules['childColorId'] = 'nullable|exists:colors,id';
            if ($this->isFieldRequired('color_id')) {
                $rules['childColorId'] = 'required|exists:colors,id';
            }
        }

        // Add product model validation
        if ($this->isFieldVisible('product_model_id')) {
            $rules['childProductModelId'] = 'nullable|exists:product_models,id';
            if ($this->isFieldRequired('product_model_id')) {
                $rules['childProductModelId'] = 'required|exists:product_models,id';
            }
        }

        // Add size validation
        if ($this->isFieldVisible('size')) {
            $rules['childSize'] = 'nullable|string|max:50';
            if ($this->isFieldRequired('size')) {
                $rules['childSize'] = 'required|string|max:50';
            }
        }

        // Add weight validation
        if ($this->isFieldVisible('weight')) {
            $rules['childWeight'] = 'nullable|numeric|min:0';
            if ($this->isFieldRequired('weight')) {
                $rules['childWeight'] = 'required|numeric|min:0';
            }
        }

        // Add IMEI validation
        if ($this->isFieldVisible('imei')) {
            $rules['childImei'] = 'nullable|string|min:15|max:17';
            if ($this->isFieldRequired('imei')) {
                $rules['childImei'] = 'required|string|min:15|max:17';
            }
        }

        // Add max stock validation
        if ($this->isFieldVisible('max_stock')) {
            $rules['childMaxStock'] = 'nullable|integer|min:0';
            if ($this->isFieldRequired('max_stock')) {
                $rules['childMaxStock'] = 'required|integer|min:0';
            }
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
            'childSku.unique' => 'El SKU ya está registrado',
            'childBarcode.unique' => 'El código de barras ya existe',
            'childBarcode.required' => 'El código de barras es obligatorio',
            'childPurchasePrice.required' => 'El precio de compra es obligatorio',
            'childPurchasePrice.numeric' => 'El precio de compra debe ser numérico',
            'childPurchasePrice.min' => 'El precio de compra no puede ser negativo',
            'childSalePrice.required' => 'El precio de venta es obligatorio',
            'childSalePrice.numeric' => 'El precio de venta debe ser numérico',
            'childSalePrice.min' => 'El precio de venta no puede ser negativo',
            'childMinStock.required' => 'El stock mínimo es obligatorio',
            'childMinStock.integer' => 'El stock mínimo debe ser un número entero',
            'childMinStock.min' => 'El stock mínimo no puede ser negativo',
            'childMaxStock.integer' => 'El stock máximo debe ser un número entero',
            'childMaxStock.min' => 'El stock máximo no puede ser negativo',
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
        $this->childSku = null;
        $this->childBarcode = null;
        $this->childName = '';
        $this->childPresentationId = null;
        $this->childColorId = null;
        $this->childProductModelId = null;
        $this->childSize = null;
        $this->childWeight = null;
        $this->childPurchasePrice = 0;
        $this->childSalePrice = 0;
        $this->childPriceIncludesTax = false;
        $this->childMinStock = 0;
        $this->childMaxStock = null;
        $this->childImei = null;
        $this->childIsActive = true;
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
        $this->sku = null;
        $this->name = '';
        $this->description = null;
        $this->category_id = null;
        $this->subcategory_id = null;
        $this->brand_id = null;
        $this->unit_id = null;
        $this->tax_id = null;
        $this->purchase_price = 0;
        $this->sale_price = 0;
        $this->price_includes_tax = false;
        $this->min_stock = 0;
        $this->max_stock = null;
        $this->current_stock = 0;
        $this->is_active = true;
        $this->subcategories = [];
        $this->image = null;
        $this->existingImage = null;
    }
}

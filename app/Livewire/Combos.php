<?php

namespace App\Livewire;

use App\Models\Branch;
use App\Models\Combo;
use App\Models\ComboItem;
use App\Models\Product;
use App\Models\ProductChild;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Combos extends Component
{
    use WithPagination;
    use WithFileUploads;

    // Search and filters
    public string $search = '';
    public ?string $filterStatus = null;
    public ?string $filterLimitType = null;
    public ?string $filterBranch = null;

    // Modal states
    public bool $isModalOpen = false;
    public bool $isDeleteModalOpen = false;
    public bool $isProductSearchOpen = false;
    public ?int $itemIdToDelete = null;

    // Form data
    public ?int $itemId = null;
    public ?int $branch_id = null;
    public string $name = '';
    public ?string $description = null;
    public float $combo_price = 0;
    public string $limit_type = 'none';
    public ?string $start_date = null;
    public ?string $end_date = null;
    public ?int $max_sales = null;
    public bool $is_active = true;
    public $image = null;
    public ?string $existingImage = null;

    // Combo items
    public array $comboItems = [];
    public string $productSearch = '';
    public array $searchResults = [];

    // Branch control
    public bool $needsBranchSelection = false;
    public $branches = [];

    public function mount()
    {
        $user = auth()->user();
        $this->needsBranchSelection = $user->isSuperAdmin() || !$user->branch_id;
        
        if ($this->needsBranchSelection) {
            $this->branches = Branch::where('is_active', true)->orderBy('name')->get();
        }
    }

    public function render()
    {
        $user = auth()->user();
        
        $query = Combo::query()
            ->with('branch')
            ->withCount('items');

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
                        ->orWhere('description', 'like', "%{$this->search}%");
                });
            })
            ->when($this->filterStatus !== null && $this->filterStatus !== '', function ($q) {
                if ($this->filterStatus === 'available') {
                    $q->available();
                } elseif ($this->filterStatus === '1') {
                    $q->where('is_active', true);
                } elseif ($this->filterStatus === '0') {
                    $q->where('is_active', false);
                }
            })
            ->when($this->filterLimitType, fn($q) => $q->where('limit_type', $this->filterLimitType))
            ->latest()
            ->paginate(10);

        return view('livewire.combos', [
            'items' => $items,
        ]);
    }

    public function updatedProductSearch()
    {
        if (strlen($this->productSearch) < 2) {
            $this->searchResults = [];
            return;
        }

        // Search products (parents)
        $products = Product::where('is_active', true)
            ->where(function ($q) {
                $q->where('name', 'like', "%{$this->productSearch}%")
                    ->orWhere('sku', 'like', "%{$this->productSearch}%")
                    ->orWhere('barcode', 'like', "%{$this->productSearch}%");
            })
            ->limit(5)
            ->get()
            ->map(fn($p) => [
                'type' => 'product',
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'price' => $p->sale_price,
                'image' => $p->image,
                'stock' => $p->current_stock,
                'unit' => $p->unit?->abbreviation ?? 'und',
            ]);

        // Search product children
        $children = ProductChild::where('is_active', true)
            ->whereHas('product', fn($q) => $q->where('is_active', true))
            ->where(function ($q) {
                $q->where('name', 'like', "%{$this->productSearch}%")
                    ->orWhere('sku', 'like', "%{$this->productSearch}%")
                    ->orWhere('barcode', 'like', "%{$this->productSearch}%");
            })
            ->with('product.unit')
            ->limit(5)
            ->get()
            ->map(fn($c) => [
                'type' => 'child',
                'id' => $c->id,
                'name' => $c->full_name,
                'sku' => $c->sku,
                'price' => $c->sale_price,
                'image' => $c->getDisplayImage(),
                'stock' => $c->product?->current_stock ?? 0,
                'unit' => $c->product?->unit?->abbreviation ?? 'und',
            ]);

        $this->searchResults = $products->concat($children)->toArray();
    }

    public function addProduct(string $type, int $id)
    {
        // Check if already added
        foreach ($this->comboItems as $item) {
            if ($item['type'] === $type && $item['id'] === $id) {
                $this->dispatch('notify', message: 'Este producto ya está en el combo', type: 'warning');
                return;
            }
        }

        if ($type === 'product') {
            $product = Product::with('unit')->find($id);
            if ($product) {
                $this->comboItems[] = [
                    'type' => 'product',
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => (float) $product->sale_price,
                    'quantity' => 1,
                    'image' => $product->image,
                    'unit' => $product->unit?->abbreviation ?? 'und',
                ];
            }
        } else {
            $child = ProductChild::with('product.unit')->find($id);
            if ($child) {
                $this->comboItems[] = [
                    'type' => 'child',
                    'id' => $child->id,
                    'name' => $child->full_name,
                    'price' => (float) $child->sale_price,
                    'quantity' => 1,
                    'image' => $child->getDisplayImage(),
                    'unit' => $child->product?->unit?->abbreviation ?? 'und',
                ];
            }
        }

        $this->productSearch = '';
        $this->searchResults = [];
        $this->isProductSearchOpen = false;
    }

    public function removeProduct(int $index)
    {
        unset($this->comboItems[$index]);
        $this->comboItems = array_values($this->comboItems);
    }

    public function updateQuantity(int $index, int $quantity)
    {
        if ($quantity < 1) {
            $quantity = 1;
        }
        $this->comboItems[$index]['quantity'] = $quantity;
    }

    public function getOriginalPriceProperty(): float
    {
        return collect($this->comboItems)->sum(fn($item) => $item['price'] * $item['quantity']);
    }

    public function getSavingsProperty(): float
    {
        return max(0, $this->original_price - $this->combo_price);
    }

    public function getSavingsPercentageProperty(): float
    {
        if ($this->original_price <= 0) {
            return 0;
        }
        return round(($this->savings / $this->original_price) * 100, 1);
    }

    public function create()
    {
        if (!auth()->user()->hasPermission('combos.create')) {
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

    public function edit(int $id)
    {
        if (!auth()->user()->hasPermission('combos.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->resetValidation();
        
        $combo = Combo::with('items.product.unit', 'items.productChild.product.unit')->findOrFail($id);
        
        $this->itemId = $combo->id;
        $this->branch_id = $combo->branch_id;
        $this->name = $combo->name;
        $this->description = $combo->description;
        $this->combo_price = (float) $combo->combo_price;
        $this->limit_type = $combo->limit_type;
        $this->start_date = $combo->start_date?->format('Y-m-d\TH:i');
        $this->end_date = $combo->end_date?->format('Y-m-d\TH:i');
        $this->max_sales = $combo->max_sales;
        $this->is_active = $combo->is_active;
        $this->existingImage = $combo->image;
        $this->image = null;

        // Load combo items
        $this->comboItems = $combo->items->map(function ($item) {
            if ($item->product_child_id && $item->productChild) {
                return [
                    'type' => 'child',
                    'id' => $item->product_child_id,
                    'name' => $item->productChild->full_name,
                    'price' => (float) $item->unit_price,
                    'quantity' => $item->quantity,
                    'image' => $item->productChild->getDisplayImage(),
                    'unit' => $item->productChild->product?->unit?->abbreviation ?? 'und',
                ];
            }
            return [
                'type' => 'product',
                'id' => $item->product_id,
                'name' => $item->product?->name ?? 'Producto eliminado',
                'price' => (float) $item->unit_price,
                'quantity' => $item->quantity,
                'image' => $item->product?->image,
                'unit' => $item->product?->unit?->abbreviation ?? 'und',
            ];
        })->toArray();

        $this->isModalOpen = true;
    }

    public function store()
    {
        $isNew = !$this->itemId;
        if (!auth()->user()->hasPermission($isNew ? 'combos.create' : 'combos.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $rules = [
            'name' => 'required|min:2',
            'combo_price' => 'required|numeric|min:0',
            'limit_type' => 'required|in:none,time,quantity,both',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ];

        // Branch is required for super_admin or users without branch
        if ($this->needsBranchSelection) {
            $rules['branch_id'] = 'required|exists:branches,id';
        }

        // Conditional validation based on limit type
        if (in_array($this->limit_type, ['time', 'both'])) {
            $rules['start_date'] = 'nullable|date';
            $rules['end_date'] = 'nullable|date|after_or_equal:start_date';
        }
        if (in_array($this->limit_type, ['quantity', 'both'])) {
            $rules['max_sales'] = 'required|integer|min:1';
        }

        $this->validate($rules, [
            'name.required' => 'El nombre es obligatorio',
            'name.min' => 'El nombre debe tener al menos 2 caracteres',
            'combo_price.required' => 'El precio del combo es obligatorio',
            'combo_price.numeric' => 'El precio debe ser numérico',
            'end_date.after_or_equal' => 'La fecha fin debe ser posterior a la fecha inicio',
            'max_sales.required' => 'La cantidad máxima es obligatoria',
            'max_sales.min' => 'La cantidad máxima debe ser al menos 1',
            'image.image' => 'El archivo debe ser una imagen',
            'image.mimes' => 'La imagen debe ser JPG, PNG o WebP',
            'image.max' => 'La imagen no debe superar 2MB',
            'branch_id.required' => 'Debe seleccionar una sucursal',
        ]);

        // Validate at least 2 products
        if (count($this->comboItems) < 2) {
            $this->addError('comboItems', 'El combo debe tener al menos 2 productos');
            return;
        }

        $oldValues = $isNew ? null : Combo::find($this->itemId)?->toArray();

        // Handle image upload
        $imagePath = $this->existingImage;
        if ($this->image) {
            if ($this->existingImage && Storage::disk('public')->exists($this->existingImage)) {
                Storage::disk('public')->delete($this->existingImage);
            }
            $imagePath = $this->image->store('combos', 'public');
        }

        // Determine branch_id
        $branchId = $this->needsBranchSelection ? $this->branch_id : auth()->user()->branch_id;

        $combo = Combo::updateOrCreate(['id' => $this->itemId], [
            'branch_id' => $branchId,
            'name' => $this->name,
            'description' => $this->description,
            'combo_price' => $this->combo_price,
            'original_price' => $this->original_price,
            'limit_type' => $this->limit_type,
            'start_date' => in_array($this->limit_type, ['time', 'both']) ? $this->start_date : null,
            'end_date' => in_array($this->limit_type, ['time', 'both']) ? $this->end_date : null,
            'max_sales' => in_array($this->limit_type, ['quantity', 'both']) ? $this->max_sales : null,
            'is_active' => $this->is_active,
            'image' => $imagePath,
        ]);

        // Sync combo items
        $combo->items()->delete();
        foreach ($this->comboItems as $item) {
            ComboItem::create([
                'combo_id' => $combo->id,
                'product_id' => $item['type'] === 'product' ? $item['id'] : null,
                'product_child_id' => $item['type'] === 'child' ? $item['id'] : null,
                'quantity' => $item['quantity'],
                'unit_price' => $item['price'],
            ]);
        }

        $isNew
            ? ActivityLogService::logCreate('combos', $combo, "Combo '{$combo->name}' creado")
            : ActivityLogService::logUpdate('combos', $combo, $oldValues, "Combo '{$combo->name}' actualizado");

        $this->isModalOpen = false;
        $this->dispatch('notify', message: $isNew ? 'Combo creado' : 'Combo actualizado');
    }

    public function confirmDelete(int $id)
    {
        if (!auth()->user()->hasPermission('combos.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->itemIdToDelete = $id;
        $this->isDeleteModalOpen = true;
    }

    public function delete()
    {
        if (!auth()->user()->hasPermission('combos.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $combo = Combo::find($this->itemIdToDelete);
        if (!$combo) {
            $this->dispatch('notify', message: 'Combo no encontrado', type: 'error');
            $this->isDeleteModalOpen = false;
            return;
        }

        // Delete image if exists
        if ($combo->image && Storage::disk('public')->exists($combo->image)) {
            Storage::disk('public')->delete($combo->image);
        }

        ActivityLogService::logDelete('combos', $combo, "Combo '{$combo->name}' eliminado");
        $combo->delete();

        $this->isDeleteModalOpen = false;
        $this->dispatch('notify', message: 'Combo eliminado');
    }

    public function toggleStatus(int $id)
    {
        if (!auth()->user()->hasPermission('combos.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $combo = Combo::find($id);
        if (!$combo) {
            return;
        }

        $oldValues = $combo->toArray();
        $combo->is_active = !$combo->is_active;
        $combo->save();

        ActivityLogService::logUpdate(
            'combos',
            $combo,
            $oldValues,
            "Combo '{$combo->name}' " . ($combo->is_active ? 'activado' : 'desactivado')
        );

        $this->dispatch('notify', message: $combo->is_active ? 'Combo activado' : 'Combo desactivado');
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->filterStatus = null;
        $this->filterLimitType = null;
        $this->filterBranch = null;
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

    private function resetForm()
    {
        $this->itemId = null;
        $this->branch_id = null;
        $this->name = '';
        $this->description = null;
        $this->combo_price = 0;
        $this->limit_type = 'none';
        $this->start_date = null;
        $this->end_date = null;
        $this->max_sales = null;
        $this->is_active = true;
        $this->image = null;
        $this->existingImage = null;
        $this->comboItems = [];
        $this->productSearch = '';
        $this->searchResults = [];
        $this->isProductSearchOpen = false;
    }
}

<?php

namespace App\Livewire;

use App\Models\Branch;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Discount;
use App\Models\Product;
use App\Models\Subcategory;
use App\Services\ActivityLogService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Discounts extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filterScope = '';
    public string $filterStatus = '';

    // Form
    public bool $isModalOpen = false;
    public ?int $itemId = null;
    #[Rule('required|min:3')]
    public string $name = '';
    public string $description = '';
    #[Rule('required|in:all,category,subcategory,brand,products')]
    public string $scope = 'all';
    public $scope_id = '';
    #[Rule('required|in:percentage,fixed')]
    public string $discount_type = 'percentage';
    #[Rule('required|numeric|min:0.01')]
    public $discount_value = '';
    #[Rule('required|date')]
    public ?string $start_date = null;
    #[Rule('required|date|after_or_equal:start_date')]
    public ?string $end_date = null;

    // Delete
    public bool $isDeleteModalOpen = false;
    public ?int $deleteId = null;

    // Show detail
    public bool $isDetailModalOpen = false;
    public ?Discount $detailDiscount = null;
    public array $affectedProducts = [];

    // Products multi-select
    public string $productSearch = '';
    public array $selectedProductIds = [];
    public array $selectedProductNames = [];

    public function create()
    {
        $this->resetForm();
        $this->start_date = now()->format('Y-m-d');
        $this->end_date = now()->addMonth()->format('Y-m-d');
        $this->isModalOpen = true;
    }

    public function edit(int $id)
    {
        $discount = Discount::findOrFail($id);
        $this->itemId = $discount->id;
        $this->name = $discount->name;
        $this->description = $discount->description ?? '';
        $this->scope = $discount->scope;
        $this->scope_id = $discount->scope_id ?? '';
        $this->discount_type = $discount->discount_type;
        $this->discount_value = $discount->discount_value;
        $this->start_date = $discount->start_date->format('Y-m-d');
        $this->end_date = $discount->end_date->format('Y-m-d');

        // Load selected products for 'products' scope
        if ($discount->scope === 'products') {
            $prods = $discount->products()->get();
            $this->selectedProductIds = $prods->pluck('id')->toArray();
            $this->selectedProductNames = $prods->pluck('name', 'id')->toArray();
        } else {
            $this->selectedProductIds = [];
            $this->selectedProductNames = [];
        }

        $this->isModalOpen = true;
    }

    public function store()
    {
        $this->validate();

        if ($this->scope !== 'all' && $this->scope !== 'products' && !$this->scope_id) {
            $this->dispatch('notify', message: 'Debes seleccionar un elemento para el alcance', type: 'error');
            return;
        }

        if ($this->scope === 'products' && empty($this->selectedProductIds)) {
            $this->dispatch('notify', message: 'Debes seleccionar al menos un producto', type: 'error');
            return;
        }

        if ($this->discount_type === 'percentage' && (float) $this->discount_value > 100) {
            $this->dispatch('notify', message: 'El porcentaje no puede ser mayor a 100%', type: 'error');
            return;
        }

        $user = auth()->user();
        $branchId = $user->isSuperAdmin() ? ($user->branch_id ?? Branch::first()?->id) : $user->branch_id;

        $data = [
            'name' => $this->name,
            'description' => $this->description ?: null,
            'scope' => $this->scope,
            'scope_id' => in_array($this->scope, ['all', 'products']) ? null : $this->scope_id,
            'discount_type' => $this->discount_type,
            'discount_value' => $this->discount_value,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
        ];

        if ($this->itemId) {
            $discount = Discount::findOrFail($this->itemId);
            $oldValues = $discount->toArray();
            $discount->update($data);
            ActivityLogService::logUpdate('discounts', $discount, $oldValues, "Descuento '{$this->name}' actualizado");
            $this->dispatch('notify', message: 'Descuento actualizado correctamente', type: 'success');
        } else {
            $data['branch_id'] = $branchId;
            $data['created_by'] = $user->id;
            $discount = Discount::create($data);
            ActivityLogService::logCreate('discounts', $discount, "Descuento '{$this->name}' creado");
            $this->dispatch('notify', message: 'Descuento creado correctamente', type: 'success');
        }

        // Sync products for 'products' scope
        if ($this->scope === 'products') {
            $discount->products()->sync($this->selectedProductIds);
        } else {
            $discount->products()->detach();
        }

        $this->isModalOpen = false;
        $this->resetForm();
    }

    public function toggleStatus(int $id)
    {
        $discount = Discount::findOrFail($id);
        $discount->update(['is_active' => !$discount->is_active]);
        $status = $discount->is_active ? 'activado' : 'desactivado';
        ActivityLogService::logUpdate('discounts', $discount, ['is_active' => !$discount->is_active], "Descuento '{$discount->name}' {$status}");
        $this->dispatch('notify', message: "Descuento {$status}", type: 'success');
    }

    public function showDetail(int $id)
    {
        $this->detailDiscount = Discount::with('creator', 'branch')->findOrFail($id);

        // Load affected products count
        $user = auth()->user();
        $branchId = $user->isSuperAdmin() ? $this->detailDiscount->branch_id : $user->branch_id;

        $query = Product::where('branch_id', $branchId)->where('is_active', true);

        if ($this->detailDiscount->scope === 'category') {
            $query->where('category_id', $this->detailDiscount->scope_id);
        } elseif ($this->detailDiscount->scope === 'subcategory') {
            $query->where('subcategory_id', $this->detailDiscount->scope_id);
        } elseif ($this->detailDiscount->scope === 'brand') {
            $query->where('brand_id', $this->detailDiscount->scope_id);
        } elseif ($this->detailDiscount->scope === 'products') {
            $productIds = $this->detailDiscount->products()->pluck('product_id')->toArray();
            $query->whereIn('id', $productIds);
        }

        $this->affectedProducts = $query->limit(20)->get()->map(fn($p) => [
            'name' => $p->name,
            'sku' => $p->sku,
            'sale_price' => $p->sale_price,
            'discounted_price' => $this->detailDiscount->discount_type === 'percentage'
                ? round($p->sale_price * (1 - $this->detailDiscount->discount_value / 100), 2)
                : max(0, round($p->sale_price - $this->detailDiscount->discount_value, 2)),
        ])->toArray();

        $this->isDetailModalOpen = true;
    }

    public function confirmDelete(int $id)
    {
        $this->deleteId = $id;
        $this->isDeleteModalOpen = true;
    }

    public function delete()
    {
        $discount = Discount::findOrFail($this->deleteId);
        ActivityLogService::logDelete('discounts', $discount, "Descuento '{$discount->name}' eliminado");
        $discount->delete();
        $this->isDeleteModalOpen = false;
        $this->deleteId = null;
        $this->dispatch('notify', message: 'Descuento eliminado correctamente', type: 'success');
    }

    public function addProduct(int $productId)
    {
        if (in_array($productId, $this->selectedProductIds)) return;

        $product = Product::find($productId);
        if (!$product) return;

        $this->selectedProductIds[] = $productId;
        $this->selectedProductNames[$productId] = $product->name . ' (' . $product->sku . ')';
        $this->productSearch = '';
    }

    public function removeProduct(int $productId)
    {
        $this->selectedProductIds = array_values(array_filter($this->selectedProductIds, fn($id) => $id !== $productId));
        unset($this->selectedProductNames[$productId]);
    }

    public function updatedScope()
    {
        $this->scope_id = '';
        $this->productSearch = '';
        $this->selectedProductIds = [];
        $this->selectedProductNames = [];
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->filterScope = '';
        $this->filterStatus = '';
        $this->resetPage();
    }

    private function resetForm()
    {
        $this->itemId = null;
        $this->name = '';
        $this->description = '';
        $this->scope = 'all';
        $this->scope_id = '';
        $this->discount_type = 'percentage';
        $this->discount_value = '';
        $this->start_date = null;
        $this->end_date = null;
        $this->productSearch = '';
        $this->selectedProductIds = [];
        $this->selectedProductNames = [];
        $this->resetValidation();
    }

    public function render()
    {
        $user = auth()->user();

        $query = Discount::with(['creator', 'branch']);

        if (!$user->isSuperAdmin()) {
            $query->where('branch_id', $user->branch_id);
        }

        $today = now()->toDateString();

        $items = $query
            ->when(trim($this->search), fn($q) => $q->where('name', 'like', '%' . trim($this->search) . '%'))
            ->when($this->filterScope, fn($q) => $q->where('scope', $this->filterScope))
            ->when($this->filterStatus, function ($q) use ($today) {
                match ($this->filterStatus) {
                    'active' => $q->where('is_active', true)->where('start_date', '<=', $today)->where('end_date', '>=', $today),
                    'scheduled' => $q->where('is_active', true)->where('start_date', '>', $today),
                    'expired' => $q->where('end_date', '<', $today),
                    'inactive' => $q->where('is_active', false),
                    default => $q,
                };
            })
            ->latest()
            ->paginate(10);

        $branchId = $user->isSuperAdmin() ? null : $user->branch_id;

        // Scope options for the form
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $subcategories = Subcategory::where('is_active', true)->orderBy('name')->get();
        $brands = Brand::where('is_active', true)->orderBy('name')->get();

        $productsQuery = Product::where('is_active', true);
        if ($branchId) {
            $productsQuery->where('branch_id', $branchId);
        }
        if (trim($this->productSearch)) {
            $productsQuery->where(function ($q) {
                $q->where('name', 'like', '%' . trim($this->productSearch) . '%')
                  ->orWhere('sku', 'like', '%' . trim($this->productSearch) . '%');
            });
        }
        if (!empty($this->selectedProductIds)) {
            $productsQuery->whereNotIn('id', $this->selectedProductIds);
        }
        $products = $productsQuery->orderBy('name')->limit(20)->get();

        return view('livewire.discounts', [
            'items' => $items,
            'categories' => $categories,
            'subcategories' => $subcategories,
            'brands' => $brands,
            'products' => $products,
        ]);
    }
}

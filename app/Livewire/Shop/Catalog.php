<?php

namespace App\Livewire\Shop;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductChild;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.shop')]
class Catalog extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $category = '';

    #[Url]
    public string $brand = '';

    #[Url(as: 'pp')]
    public int $perPage = 12;

    // Product detail modal
    public bool $showProductModal = false;
    public ?Product $selectedProduct = null;
    public ?int $selectedVariantId = null;
    public int $modalQuantity = 1;

    // Cart sidebar
    public bool $showCartSidebar = false;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategory(): void
    {
        $this->resetPage();
    }

    public function updatingBrand(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'category', 'brand']);
        $this->resetPage();
    }

    // Product detail modal methods
    public function openProductModal(int $productId): void
    {
        $this->selectedProduct = Product::with(['category', 'brand', 'unit', 'tax', 'activeChildren' => function ($q) {
                $q->where('show_in_shop', true);
            }])
            ->find($productId);

        if (!$this->selectedProduct) {
            return;
        }

        $this->selectedVariantId = null;
        $this->modalQuantity = 1;
        $this->showProductModal = true;
    }

    public function closeProductModal(): void
    {
        $this->showProductModal = false;
        $this->selectedProduct = null;
        $this->selectedVariantId = null;
        $this->modalQuantity = 1;
    }

    public function updatedSelectedVariantId(): void
    {
        $this->modalQuantity = 1;
    }

    public function getModalMaxStockProperty(): float
    {
        if (!$this->selectedProduct) return 0;
        if (!$this->selectedProduct->manages_inventory) return 9999;
        return (float) $this->selectedProduct->current_stock;
    }

    public function incrementModalQuantity(): void
    {
        if ($this->modalQuantity < $this->modalMaxStock) {
            $this->modalQuantity++;
        }
    }

    public function decrementModalQuantity(): void
    {
        if ($this->modalQuantity > 1) {
            $this->modalQuantity--;
        }
    }

    public function addToCartFromModal(): void
    {
        if (!$this->selectedProduct) {
            return;
        }

        $this->addProductToCart(
            $this->selectedProduct,
            $this->selectedVariantId,
            $this->modalQuantity
        );

        $this->closeProductModal();
    }

    // Quick add to cart (from product card)
    public function quickAddToCart(int $productId): void
    {
        $product = Product::with(['tax'])->find($productId);
        if (!$product || !$product->is_active) {
            $this->dispatch('notify', message: 'Producto no disponible', type: 'error');
            return;
        }

        if ($product->manages_inventory && $product->current_stock <= 0) {
            $this->dispatch('notify', message: 'Producto sin stock disponible', type: 'error');
            return;
        }

        $this->addProductToCart($product, null, 1);
    }

    // Cart sidebar
    #[On('toggle-cart-sidebar')]
    public function toggleCartSidebar(): void
    {
        $this->showCartSidebar = !$this->showCartSidebar;
    }

    public function closeCartSidebar(): void
    {
        $this->showCartSidebar = false;
    }

    public function getCartItemsProperty(): array
    {
        return session('ecommerce_cart.items', []);
    }

    public function getCartTotalProperty(): float
    {
        return collect($this->cartItems)->sum(fn($item) => $item['unit_price'] * $item['quantity']);
    }

    public function getCartCountProperty(): int
    {
        return count($this->cartItems);
    }

    public function updateCartQuantity(int $index, $quantity): void
    {
        $cart = session('ecommerce_cart', ['items' => []]);
        if (!isset($cart['items'][$index])) {
            return;
        }

        $quantity = max(1, (int) $quantity);
        $product = Product::find($cart['items'][$index]['product_id']);
        if ($product && $product->manages_inventory) {
            $quantity = min($quantity, (int) $product->current_stock);
            $cart['items'][$index]['max_stock'] = (float) $product->current_stock;
        }

        $cart['items'][$index]['quantity'] = $quantity;
        $cart['updated_at'] = now()->toDateTimeString();
        session(['ecommerce_cart' => $cart]);
        $this->dispatch('cart-updated', count: count($cart['items']));
    }

    public function removeCartItem(int $index): void
    {
        $cart = session('ecommerce_cart', ['items' => []]);
        if (!isset($cart['items'][$index])) {
            return;
        }

        array_splice($cart['items'], $index, 1);
        $cart['updated_at'] = now()->toDateTimeString();
        session(['ecommerce_cart' => $cart]);
        $this->dispatch('cart-updated', count: count($cart['items']));
        $this->dispatch('notify', message: 'Producto eliminado del carrito', type: 'success');
    }

    // Shared add to cart logic
    private function addProductToCart(Product $product, ?int $variantId, int $quantity): void
    {
        $cart = session('ecommerce_cart', ['items' => []]);

        $variant = null;
        if ($variantId) {
            $variant = ProductChild::find($variantId);
        }

        $unitPrice = $variant
            ? $variant->getSalePriceWithTax()
            : $product->getSalePriceWithTax();

        $taxRate = $product->tax ? (float) $product->tax->value : 0;
        $managesInventory = (bool) $product->manages_inventory;
        $maxStock = $managesInventory ? (float) $product->current_stock : 9999;

        $existingIndex = null;
        foreach ($cart['items'] as $index => $item) {
            if ($item['product_id'] === $product->id && $item['product_child_id'] === $variantId) {
                $existingIndex = $index;
                break;
            }
        }

        if ($existingIndex !== null) {
            $newQty = $cart['items'][$existingIndex]['quantity'] + $quantity;
            if ($managesInventory) {
                $cart['items'][$existingIndex]['quantity'] = min($newQty, (int) $maxStock);
                if ($newQty > (int) $maxStock) {
                    $this->dispatch('notify', message: "Stock máximo disponible: {$maxStock}", type: 'warning');
                }
            } else {
                $cart['items'][$existingIndex]['quantity'] = $newQty;
            }
            $cart['items'][$existingIndex]['max_stock'] = $maxStock;
            $cart['items'][$existingIndex]['manages_inventory'] = $managesInventory;
        } else {
            $cart['items'][] = [
                'product_id' => $product->id,
                'product_child_id' => $variantId,
                'name' => $variant ? $variant->full_name : $product->name,
                'sku' => $variant ? $variant->sku : $product->sku,
                'unit_price' => $unitPrice,
                'tax_rate' => $taxRate,
                'quantity' => $managesInventory ? min($quantity, (int) $maxStock) : $quantity,
                'max_stock' => $maxStock,
                'manages_inventory' => $managesInventory,
                'image' => $variant ? $variant->getDisplayImage() : $product->getDisplayImage(),
            ];
        }

        $cart['updated_at'] = now()->toDateTimeString();
        session(['ecommerce_cart' => $cart]);

        $this->dispatch('cart-updated', count: count($cart['items']));
        $this->dispatch('notify', message: 'Producto agregado al carrito', type: 'success');
    }

    public function render()
    {
        $branchId = config('ecommerce.branch_id');

        $query = Product::query()
            ->where('is_active', true)
            ->where('show_in_shop', true)
            ->where(function ($q) {
                $q->where('manages_inventory', false)
                  ->orWhere('current_stock', '>', 0);
            })
            ->where('branch_id', $branchId)
            ->with(['category', 'brand', 'tax']);

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('sku', 'like', '%' . $this->search . '%')
                    ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->category !== '') {
            $query->where('category_id', $this->category);
        }

        if ($this->brand !== '') {
            $query->where('brand_id', $this->brand);
        }

        $products = $query->orderBy('name')->paginate($this->perPage);

        $availableCategoryIds = Product::where('is_active', true)
            ->where('show_in_shop', true)
            ->where(function ($q) {
                $q->where('manages_inventory', false)->orWhere('current_stock', '>', 0);
            })
            ->where('branch_id', $branchId)
            ->whereNotNull('category_id')
            ->distinct()
            ->pluck('category_id');

        $availableBrandIds = Product::where('is_active', true)
            ->where('show_in_shop', true)
            ->where(function ($q) {
                $q->where('manages_inventory', false)->orWhere('current_stock', '>', 0);
            })
            ->where('branch_id', $branchId)
            ->whereNotNull('brand_id')
            ->distinct()
            ->pluck('brand_id');

        $categories = Category::where('is_active', true)
            ->whereIn('id', $availableCategoryIds)
            ->orderBy('name')
            ->get();

        $brands = Brand::where('is_active', true)
            ->whereIn('id', $availableBrandIds)
            ->orderBy('name')
            ->get();

        $branch = \App\Models\Branch::find($branchId);
        $showStockInShop = $branch ? (bool) $branch->show_stock_in_shop : false;

        return view('livewire.shop.catalog', [
            'products' => $products,
            'categories' => $categories,
            'brands' => $brands,
            'showStockInShop' => $showStockInShop,
        ]);
    }
}

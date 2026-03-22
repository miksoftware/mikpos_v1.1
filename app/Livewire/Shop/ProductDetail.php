<?php

namespace App\Livewire\Shop;

use App\Models\Product;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.shop')]
class ProductDetail extends Component
{
    public Product $product;
    public ?int $selectedVariantId = null;
    public int $quantity = 1;

    public function mount(Product $product): void
    {
        // Validate product belongs to ecommerce branch and is active
        if (
            $product->branch_id != config('ecommerce.branch_id') ||
            !$product->is_active
        ) {
            abort(404);
        }

        // Products with inventory must have stock
        if ($product->manages_inventory && $product->current_stock <= 0) {
            abort(404);
        }

        $this->product = $product->load(['category', 'brand', 'unit', 'tax', 'activeChildren']);

        // Auto-select first active variant if available
        $firstVariant = $this->product->activeChildren->first();
        if ($firstVariant) {
            $this->selectedVariantId = $firstVariant->id;
        }
    }

    public function updatedSelectedVariantId(): void
    {
        $this->quantity = 1;
    }

    public function getMaxStockProperty(): float
    {
        if (!$this->product->manages_inventory) return 9999;
        return (float) $this->product->current_stock;
    }

    public function incrementQuantity(): void
    {
        if ($this->quantity < $this->maxStock) {
            $this->quantity++;
        }
    }

    public function decrementQuantity(): void
    {
        if ($this->quantity > 1) {
            $this->quantity--;
        }
    }

    public function updatedQuantity(): void
    {
        $max = $this->product->manages_inventory ? (int) $this->maxStock : 9999;
        $this->quantity = max(1, min((int) $this->quantity, $max));
    }

    public function addToCart(): void
    {
        $cart = session('ecommerce_cart', ['items' => []]);

        $variant = null;
        if ($this->selectedVariantId) {
            $variant = $this->product->activeChildren->find($this->selectedVariantId);
        }

        $unitPrice = $variant
            ? $variant->getSalePriceWithTax()
            : $this->product->getSalePriceWithTax();

        $taxRate = $this->product->tax ? (float) $this->product->tax->value : 0;
        $managesInventory = (bool) $this->product->manages_inventory;

        // Check if product/variant already in cart
        $existingIndex = null;
        foreach ($cart['items'] as $index => $item) {
            if (
                $item['product_id'] === $this->product->id &&
                $item['product_child_id'] === ($variant?->id)
            ) {
                $existingIndex = $index;
                break;
            }
        }

        if ($existingIndex !== null) {
            $newQty = $cart['items'][$existingIndex]['quantity'] + $this->quantity;
            if ($managesInventory) {
                $cart['items'][$existingIndex]['quantity'] = min($newQty, (int) $this->maxStock);
            } else {
                $cart['items'][$existingIndex]['quantity'] = $newQty;
            }
            $cart['items'][$existingIndex]['max_stock'] = $this->maxStock;
            $cart['items'][$existingIndex]['manages_inventory'] = $managesInventory;
        } else {
            $cart['items'][] = [
                'product_id' => $this->product->id,
                'product_child_id' => $variant?->id,
                'name' => $variant ? $variant->full_name : $this->product->name,
                'sku' => $variant ? $variant->sku : $this->product->sku,
                'unit_price' => $unitPrice,
                'tax_rate' => $taxRate,
                'quantity' => $managesInventory ? min($this->quantity, (int) $this->maxStock) : $this->quantity,
                'max_stock' => $this->maxStock,
                'manages_inventory' => $managesInventory,
                'image' => $variant ? $variant->getDisplayImage() : $this->product->getDisplayImage(),
            ];
        }

        $cart['updated_at'] = now()->toDateTimeString();
        session(['ecommerce_cart' => $cart]);

        $this->dispatch('cart-updated', count: count($cart['items']));
        $this->dispatch('notify', message: 'Producto agregado al carrito', type: 'success');
    }

    public function render()
    {
        return view('livewire.shop.product-detail');
    }
}

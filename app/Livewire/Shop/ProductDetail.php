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
        // Validate product belongs to ecommerce branch, is active and has stock
        if (
            $product->branch_id != config('ecommerce.branch_id') ||
            !$product->is_active ||
            $product->current_stock <= 0
        ) {
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
        $this->quantity = max(1, min((int) $this->quantity, (int) $this->maxStock));
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
            $cart['items'][$existingIndex]['quantity'] = min($newQty, (int) $this->maxStock);
            $cart['items'][$existingIndex]['max_stock'] = $this->maxStock;
        } else {
            $cart['items'][] = [
                'product_id' => $this->product->id,
                'product_child_id' => $variant?->id,
                'name' => $variant ? $variant->full_name : $this->product->name,
                'sku' => $variant ? $variant->sku : $this->product->sku,
                'unit_price' => $unitPrice,
                'tax_rate' => $taxRate,
                'quantity' => min($this->quantity, (int) $this->maxStock),
                'max_stock' => $this->maxStock,
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

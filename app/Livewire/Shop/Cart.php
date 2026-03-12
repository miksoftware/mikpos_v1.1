<?php

namespace App\Livewire\Shop;

use App\Models\Product;
use App\Models\ProductChild;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.shop')]
class Cart extends Component
{
    public array $items = [];

    public function mount(): void
    {
        $this->loadCart();
    }

    public function loadCart(): void
    {
        $cart = session('ecommerce_cart', ['items' => []]);
        $this->items = $cart['items'] ?? [];

        // Refresh stock info from DB
        $this->refreshStockInfo();
    }

    public function addToCart(int $productId, ?int $productChildId = null): void
    {
        $product = Product::with(['tax'])->find($productId);
        if (!$product || !$product->is_active || $product->current_stock <= 0) {
            $this->dispatch('notify', message: 'Producto no disponible', type: 'error');
            return;
        }

        $variant = null;
        if ($productChildId) {
            $variant = ProductChild::find($productChildId);
            if (!$variant || !$variant->is_active) {
                $this->dispatch('notify', message: 'Variante no disponible', type: 'error');
                return;
            }
        }

        $unitPrice = $variant
            ? $variant->getSalePriceWithTax()
            : $product->getSalePriceWithTax();

        $taxRate = $product->tax ? (float) $product->tax->value : 0;
        $maxStock = (float) $product->current_stock;

        // Check if already in cart
        $existingIndex = null;
        foreach ($this->items as $index => $item) {
            if ($item['product_id'] === $productId && $item['product_child_id'] === $productChildId) {
                $existingIndex = $index;
                break;
            }
        }

        if ($existingIndex !== null) {
            $newQty = $this->items[$existingIndex]['quantity'] + 1;
            $this->items[$existingIndex]['quantity'] = min($newQty, (int) $maxStock);
            $this->items[$existingIndex]['max_stock'] = $maxStock;
            if ($newQty > (int) $maxStock) {
                $this->dispatch('notify', message: "Stock máximo disponible: {$maxStock}", type: 'warning');
            }
        } else {
            $this->items[] = [
                'product_id' => $productId,
                'product_child_id' => $productChildId,
                'name' => $variant ? $variant->full_name : $product->name,
                'sku' => $variant ? $variant->sku : $product->sku,
                'unit_price' => $unitPrice,
                'tax_rate' => $taxRate,
                'quantity' => 1,
                'max_stock' => $maxStock,
                'image' => $variant ? $variant->getDisplayImage() : $product->getDisplayImage(),
            ];
        }

        $this->saveCart();
        $this->dispatch('notify', message: 'Producto agregado al carrito', type: 'success');
    }

    public function updateQuantity(int $index, $quantity): void
    {
        if (!isset($this->items[$index])) {
            return;
        }

        $quantity = max(1, (int) $quantity);

        // Refresh current stock from DB
        $item = $this->items[$index];
        $product = Product::find($item['product_id']);
        if ($product) {
            $currentStock = (int) $product->current_stock;
            $this->items[$index]['max_stock'] = (float) $product->current_stock;

            if ($quantity > $currentStock) {
                $quantity = $currentStock;
                $this->dispatch('notify', message: "Stock máximo disponible: {$currentStock}", type: 'warning');
            }
        }

        $this->items[$index]['quantity'] = $quantity;
        $this->saveCart();
    }

    public function removeItem(int $index): void
    {
        if (!isset($this->items[$index])) {
            return;
        }

        array_splice($this->items, $index, 1);
        $this->saveCart();
        $this->dispatch('notify', message: 'Producto eliminado del carrito', type: 'success');
    }

    public function getSubtotalProperty(): float
    {
        return collect($this->items)->sum(fn($item) => $item['unit_price'] * $item['quantity']);
    }

    public function getTaxTotalProperty(): float
    {
        return collect($this->items)->sum(function ($item) {
            $priceWithTax = $item['unit_price'];
            $taxRate = $item['tax_rate'];
            if ($taxRate > 0) {
                $priceWithoutTax = $priceWithTax / (1 + $taxRate / 100);
                return ($priceWithTax - $priceWithoutTax) * $item['quantity'];
            }
            return 0;
        });
    }

    public function getTotalProperty(): float
    {
        return $this->subtotal;
    }

    public function getItemCountProperty(): int
    {
        return count($this->items);
    }

    private function refreshStockInfo(): void
    {
        $productIds = collect($this->items)->pluck('product_id')->unique()->toArray();
        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

        $changed = false;
        foreach ($this->items as $index => &$item) {
            $product = $products->get($item['product_id']);
            if ($product) {
                $item['max_stock'] = (float) $product->current_stock;
                if ($item['quantity'] > (int) $product->current_stock) {
                    $item['quantity'] = max(1, (int) $product->current_stock);
                    $changed = true;
                }
            }
        }
        unset($item);

        if ($changed) {
            $this->saveCart();
            $this->dispatch('notify', message: 'Algunas cantidades fueron ajustadas por cambios en el stock', type: 'warning');
        }
    }

    private function saveCart(): void
    {
        session(['ecommerce_cart' => [
            'items' => $this->items,
            'updated_at' => now()->toDateTimeString(),
        ]]);

        $this->dispatch('cart-updated', count: count($this->items));
    }

    public function render()
    {
        return view('livewire.shop.cart');
    }
}

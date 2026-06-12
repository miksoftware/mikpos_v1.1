<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\ProductChild;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class BarcodePrinter extends Component
{
    public string $search = '';
    public array $printList = [];
    public array $searchResults = [];

    public function mount()
    {
        $productId = request()->query('product_id');
        $variantId = request()->query('variant_id');

        if ($productId) {
            $product = Product::find($productId);
            if ($product) {
                $barcode = $product->getPrimaryBarcode() ?? $product->barcode;
                if ($barcode) {
                    $this->addToPrintList([
                        'id' => 'p_' . $product->id,
                        'model_id' => $product->id,
                        'type' => 'product',
                        'name' => $product->name,
                        'barcode' => $barcode,
                        'price' => $product->sale_price,
                        'sku' => $product->sku
                    ]);
                }
            }
        }

        if ($variantId) {
            $variant = ProductChild::find($variantId);
            if ($variant) {
                $childBarcode = $variant->getPrimaryBarcode() ?? $variant->barcode;
                if ($childBarcode) {
                    $this->addToPrintList([
                        'id' => 'c_' . $variant->id,
                        'model_id' => $variant->id,
                        'type' => 'variant',
                        'name' => $variant->product->name . ' - ' . $variant->name,
                        'barcode' => $childBarcode,
                        'price' => $variant->sale_price,
                        'sku' => $variant->sku
                    ]);
                }
            }
        }
    }

    public function updatedSearch()
    {
        if (strlen($this->search) < 2) {
            $this->searchResults = [];
            return;
        }

        $products = Product::where('name', 'like', '%' . $this->search . '%')
            ->orWhere('sku', 'like', '%' . $this->search . '%')
            ->orWhere('barcode', 'like', '%' . $this->search . '%')
            ->with(['barcodes'])
            ->limit(5)
            ->get();

        $results = [];

        foreach ($products as $product) {
            $barcode = $product->getPrimaryBarcode() ?? $product->barcode;
            if ($barcode) {
                $results[] = [
                    'id' => 'p_' . $product->id,
                    'model_id' => $product->id,
                    'type' => 'product',
                    'name' => $product->name,
                    'barcode' => $barcode,
                    'price' => $product->sale_price,
                    'sku' => $product->sku
                ];
            }

            foreach ($product->children as $child) {
                $childBarcode = $child->getPrimaryBarcode() ?? $child->barcode;
                if ($childBarcode) {
                    $results[] = [
                        'id' => 'c_' . $child->id,
                        'model_id' => $child->id,
                        'type' => 'variant',
                        'name' => $product->name . ' - ' . $child->name,
                        'barcode' => $childBarcode,
                        'price' => $child->sale_price,
                        'sku' => $child->sku
                    ];
                }
            }
        }

        $this->searchResults = $results;
    }

    public function addToPrintList($item)
    {
        $id = $item['id'];

        if (isset($this->printList[$id])) {
            $this->printList[$id]['quantity']++;
        } else {
            $this->printList[$id] = [
                'id' => $id,
                'model_id' => $item['model_id'],
                'type' => $item['type'],
                'name' => $item['name'],
                'barcode' => $item['barcode'],
                'price' => $item['price'],
                'sku' => $item['sku'],
                'quantity' => 1
            ];
        }

        $this->search = '';
        $this->searchResults = [];
        $this->dispatch('notify', message: 'Producto agregado a la lista');
    }

    public function removeFromPrintList($id)
    {
        unset($this->printList[$id]);
    }

    public function updateQuantity($id, $quantity)
    {
        if ($quantity <= 0) {
            $this->removeFromPrintList($id);
            return;
        }
        $this->printList[$id]['quantity'] = $quantity;
    }

    public function clearList()
    {
        $this->printList = [];
    }

    public function print()
    {
        if (empty($this->printList)) {
            $this->dispatch('notify', message: 'La lista de impresión está vacía', type: 'error');
            return;
        }

        // We'll pass the data via session to avoid long URLs
        session()->put('barcode_print_data', $this->printList);
        
        $this->dispatch('open-print-window', url: route('barcode.print', ['print' => 1]));
    }

    public function render()
    {
        return view('livewire.barcode-printer');
    }
}

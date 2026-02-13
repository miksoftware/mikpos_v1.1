<?php

namespace App\Livewire;

use App\Models\Branch;
use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\Tax;
use App\Models\Unit;
use App\Services\ActivityLogService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class PurchaseCreate extends Component
{
    // Edit mode
    public ?int $purchaseId = null;
    public ?Purchase $purchase = null;
    public bool $isEditing = false;
    public bool $isCompletedEdit = false;

    // Purchase header
    public ?int $supplier_id = null;
    public ?int $branch_id = null;
    public string $supplier_invoice = '';
    public string $purchase_date;
    public ?string $due_date = null;
    public string $notes = '';

    // Payment fields
    public string $payment_type = 'cash';
    public ?int $payment_method_id = null;
    public ?float $credit_amount = null;
    public float $paid_amount = 0;
    public ?int $partial_payment_method_id = null;
    public ?string $payment_due_date = null;

    // Cart items
    public array $cartItems = [];

    // Product search
    public string $productSearch = '';
    public array $searchResults = [];

    // Lists
    public $suppliers = [];
    public $paymentMethods = [];
    public $branches = [];

    // Branch control
    public bool $needsBranchSelection = false;

    // Totals
    public float $subtotal = 0;
    public float $taxAmount = 0;
    public float $discountAmount = 0;
    public float $total = 0;

    // Quick product creation
    public bool $isQuickCreateOpen = false;
    public string $quickName = '';
    public ?int $quickCategoryId = null;
    public ?int $quickUnitId = null;
    public ?int $quickTaxId = null;
    public float $quickPurchasePrice = 0;
    public float $quickSalePrice = 0;
    public $quickCategories = [];
    public $quickUnits = [];
    public $quickTaxes = [];

    public function mount(?int $id = null)
    {
        $this->purchase_date = now()->format('Y-m-d');
        $this->suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        $this->paymentMethods = PaymentMethod::where('is_active', true)->orderBy('name')->get();

        $user = auth()->user();
        $this->needsBranchSelection = $user->isSuperAdmin() || !$user->branch_id;
        
        if ($this->needsBranchSelection) {
            $this->branches = Branch::where('is_active', true)->orderBy('name')->get();
        } else {
            $this->branch_id = $user->branch_id;
        }

        if ($id) {
            $this->loadPurchase($id);
        }
    }

    public function loadPurchase(int $id)
    {
        $this->purchase = Purchase::with('items.product.unit')->find($id);
        
        if (!$this->purchase) {
            $this->dispatch('notify', message: 'Compra no encontrada', type: 'error');
            return $this->redirect(route('purchases'), navigate: true);
        }

        $this->purchaseId = $this->purchase->id;
        $this->isEditing = true;
        $this->isCompletedEdit = $this->purchase->status === 'completed';

        // Load header data
        $this->supplier_id = $this->purchase->supplier_id;
        $this->branch_id = $this->purchase->branch_id;
        $this->supplier_invoice = $this->purchase->supplier_invoice ?? '';
        $this->purchase_date = $this->purchase->purchase_date->format('Y-m-d');
        $this->due_date = $this->purchase->due_date?->format('Y-m-d');
        $this->notes = $this->purchase->notes ?? '';

        // Load payment data
        $this->payment_type = $this->purchase->payment_type ?? 'cash';
        $this->payment_method_id = $this->purchase->payment_method_id;
        $this->credit_amount = (float) $this->purchase->credit_amount;
        $this->paid_amount = (float) $this->purchase->paid_amount;
        $this->partial_payment_method_id = $this->purchase->partial_payment_method_id;
        $this->payment_due_date = $this->purchase->payment_due_date?->format('Y-m-d');

        // Load items
        foreach ($this->purchase->items as $item) {
            $this->cartItems[] = [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'name' => $item->product?->name ?? 'Producto eliminado',
                'sku' => $item->product?->sku,
                'image' => $item->product?->image,
                'unit' => $item->product?->unit?->abbreviation ?? 'und',
                'quantity' => $item->quantity,
                'unit_cost' => (float) $item->unit_cost,
                'sale_price' => (float) ($item->product?->sale_price ?? 0),
                'tax_rate' => (float) $item->tax_rate,
                'tax_amount' => (float) $item->tax_amount,
                'discount' => (float) $item->discount,
                'discount_type' => $item->discount_type ?? 'percentage',
                'discount_type_value' => (float) ($item->discount_type_value ?? 0),
                'subtotal' => (float) $item->subtotal,
                'total' => (float) $item->total,
            ];
        }

        $this->calculateTotals();
    }

    public function render()
    {
        return view('livewire.purchase-create');
    }

    public function updatedProductSearch()
    {
        if (strlen($this->productSearch) < 2) {
            $this->searchResults = [];
            return;
        }

        // Determine branch_id for filtering
        $branchId = $this->needsBranchSelection ? $this->branch_id : auth()->user()->branch_id;

        // If super_admin and no branch selected, don't allow search
        if ($this->needsBranchSelection && !$branchId) {
            $this->searchResults = [];
            $this->dispatch('notify', message: 'Selecciona una sucursal primero', type: 'warning');
            return;
        }

        $query = Product::where('is_active', true)
            ->where('branch_id', $branchId)
            ->where(function ($q) {
                $q->where('name', 'like', "%{$this->productSearch}%")
                    ->orWhere('sku', 'like', "%{$this->productSearch}%")
                    ->orWhere('barcode', 'like', "%{$this->productSearch}%");
            });

        $this->searchResults = $query
            ->with(['category', 'unit', 'tax'])
            ->limit(10)
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'barcode' => $p->barcode,
                'image' => $p->image,
                'purchase_price' => (float) $p->purchase_price,
                'current_stock' => $p->current_stock,
                'unit' => $p->unit?->abbreviation ?? 'und',
                'category' => $p->category?->name,
                'tax_rate' => (float) ($p->tax?->value ?? 0),
            ])
            ->toArray();
    }

    public function addProduct(int $productId)
    {
        foreach ($this->cartItems as $index => $item) {
            if ($item['product_id'] === $productId) {
                $this->cartItems[$index]['quantity']++;
                $this->calculateItemTotal($index);
                $this->calculateTotals();
                $this->productSearch = '';
                $this->searchResults = [];
                return;
            }
        }

        $product = Product::with(['unit', 'tax'])->find($productId);
        if (!$product) {
            return;
        }

        $this->cartItems[] = [
            'id' => null,
            'product_id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'image' => $product->image,
            'unit' => $product->unit?->abbreviation ?? 'und',
            'quantity' => 1,
            'unit_cost' => (float) $product->purchase_price,
            'sale_price' => (float) $product->sale_price,
            'tax_rate' => (float) ($product->tax?->value ?? 0),
            'tax_amount' => 0,
            'discount' => 0,
            'discount_type' => 'percentage',
            'discount_type_value' => 0,
            'subtotal' => (float) $product->purchase_price,
            'total' => 0,
        ];

        $lastIndex = count($this->cartItems) - 1;
        $this->calculateItemTotal($lastIndex);
        $this->calculateTotals();

        $this->productSearch = '';
        $this->searchResults = [];
    }

    public function addProductByBarcode()
    {
        if (empty($this->productSearch)) {
            return;
        }

        // Determine branch_id for filtering
        $branchId = $this->needsBranchSelection ? $this->branch_id : auth()->user()->branch_id;

        // If super_admin and no branch selected, don't allow search
        if ($this->needsBranchSelection && !$branchId) {
            $this->dispatch('notify', message: 'Selecciona una sucursal primero', type: 'warning');
            $this->productSearch = '';
            return;
        }

        $product = Product::where('is_active', true)
            ->where('branch_id', $branchId)
            ->where(function ($q) {
                $q->where('barcode', $this->productSearch)
                    ->orWhere('sku', $this->productSearch);
            })
            ->first();

        if ($product) {
            $this->addProduct($product->id);
        } else {
            $this->dispatch('notify', message: 'Producto no encontrado', type: 'error');
        }

        $this->productSearch = '';
        $this->searchResults = [];
    }

    public function updateQuantity(int $index, int $quantity)
    {
        if ($quantity < 1) {
            $quantity = 1;
        }
        $this->cartItems[$index]['quantity'] = $quantity;
        $this->calculateItemTotal($index);
        $this->calculateTotals();
    }

    public function updateUnitCost(int $index, float $cost)
    {
        if ($cost < 0) {
            $cost = 0;
        }
        $this->cartItems[$index]['unit_cost'] = $cost;
        $this->calculateItemTotal($index);
        $this->calculateTotals();
    }

    public function updateDiscount(int $index, float $discount)
    {
        if ($discount < 0) {
            $discount = 0;
        }

        $item = &$this->cartItems[$index];
        $type = $item['discount_type'] ?? 'percentage';

        if ($type === 'percentage' && $discount > 100) {
            $discount = 100;
        }

        $item['discount_type_value'] = $discount;

        // Calculate actual discount amount
        $subtotal = $item['quantity'] * $item['unit_cost'];
        if ($type === 'percentage') {
            $item['discount'] = round($subtotal * ($discount / 100), 2);
        } else {
            $item['discount'] = round($discount, 2);
            // Don't exceed subtotal
            if ($item['discount'] > $subtotal) {
                $item['discount'] = $subtotal;
            }
        }

        $this->calculateItemTotal($index);
        $this->calculateTotals();
    }

    public function updateDiscountType(int $index, string $type)
    {
        if (!in_array($type, ['percentage', 'fixed'])) {
            return;
        }

        $this->cartItems[$index]['discount_type'] = $type;
        $this->cartItems[$index]['discount_type_value'] = 0;
        $this->cartItems[$index]['discount'] = 0;
        $this->calculateItemTotal($index);
        $this->calculateTotals();
    }

    public function updateSalePrice(int $index, float $price)
    {
        if ($price < 0) {
            $price = 0;
        }
        $this->cartItems[$index]['sale_price'] = $price;
    }

    public function removeItem(int $index)
    {
        unset($this->cartItems[$index]);
        $this->cartItems = array_values($this->cartItems);
        $this->calculateTotals();
    }

    public function clearCart()
    {
        $this->cartItems = [];
        $this->calculateTotals();
    }

    private function calculateItemTotal(int $index): void
    {
        $item = &$this->cartItems[$index];
        $item['subtotal'] = $item['quantity'] * $item['unit_cost'];

        // Recalculate discount based on type
        $discountTypeValue = (float) ($item['discount_type_value'] ?? 0);
        if ($discountTypeValue > 0) {
            if (($item['discount_type'] ?? 'percentage') === 'percentage') {
                $item['discount'] = round($item['subtotal'] * ($discountTypeValue / 100), 2);
            } else {
                $item['discount'] = round($discountTypeValue, 2);
                if ($item['discount'] > $item['subtotal']) {
                    $item['discount'] = $item['subtotal'];
                }
            }
        } else {
            $item['discount'] = 0;
        }

        $item['tax_amount'] = ($item['subtotal'] - $item['discount']) * ($item['tax_rate'] / 100);
        $item['total'] = $item['subtotal'] + $item['tax_amount'] - $item['discount'];
    }

    private function calculateTotals(): void
    {
        $this->subtotal = collect($this->cartItems)->sum('subtotal');
        $this->taxAmount = collect($this->cartItems)->sum('tax_amount');
        $this->discountAmount = collect($this->cartItems)->sum('discount');
        $this->total = $this->subtotal + $this->taxAmount - $this->discountAmount;

        // Auto-set credit amount if credit type
        if ($this->payment_type === 'credit' && !$this->credit_amount) {
            $this->credit_amount = $this->total;
        }
    }

    public function updatedBranchId()
    {
        // Clear search results when branch changes
        $this->productSearch = '';
        $this->searchResults = [];
    }

    public function updatedPaymentType()
    {
        if ($this->payment_type === 'cash') {
            $this->credit_amount = null;
            $this->paid_amount = 0;
            $this->partial_payment_method_id = null;
            $this->payment_due_date = null;
        } else {
            $this->payment_method_id = null;
            $this->credit_amount = $this->total;
        }
    }

    public function saveDraft()
    {
        $this->savePurchase('draft');
    }

    public function openQuickCreate()
    {
        $this->quickCategories = Category::where('is_active', true)->orderBy('name')->get();
        $this->quickUnits = Unit::where('is_active', true)->orderBy('name')->get();
        $this->quickTaxes = Tax::where('is_active', true)->orderBy('name')->get();

        $this->quickName = mb_strtoupper($this->productSearch);
        $this->quickCategoryId = null;
        $this->quickUnitId = null;
        $this->quickTaxId = null;
        $this->quickPurchasePrice = 0;
        $this->quickSalePrice = 0;

        $this->isQuickCreateOpen = true;
    }

    public function storeQuickProduct()
    {
        if (!auth()->user()->hasPermission('products.create')) {
            $this->dispatch('notify', message: 'No tienes permiso para crear productos', type: 'error');
            return;
        }

        $this->validate([
            'quickName' => 'required|min:2',
            'quickCategoryId' => 'required|exists:categories,id',
            'quickUnitId' => 'required|exists:units,id',
            'quickPurchasePrice' => 'required|numeric|min:0',
            'quickSalePrice' => 'required|numeric|min:0',
        ], [
            'quickName.required' => 'El nombre es obligatorio',
            'quickName.min' => 'El nombre debe tener al menos 2 caracteres',
            'quickCategoryId.required' => 'La categoría es obligatoria',
            'quickUnitId.required' => 'La unidad es obligatoria',
            'quickPurchasePrice.required' => 'El precio de compra es obligatorio',
            'quickSalePrice.required' => 'El precio de venta es obligatorio',
        ]);

        $branchId = $this->needsBranchSelection ? $this->branch_id : auth()->user()->branch_id;

        $product = Product::create([
            'branch_id' => $branchId,
            'name' => mb_strtoupper($this->quickName),
            'category_id' => $this->quickCategoryId,
            'unit_id' => $this->quickUnitId,
            'tax_id' => $this->quickTaxId ?: null,
            'purchase_price' => $this->quickPurchasePrice,
            'sale_price' => $this->quickSalePrice,
            'current_stock' => 0,
            'min_stock' => 0,
            'is_active' => true,
        ]);

        $product->generateSku();
        $product->save();

        ActivityLogService::logCreate('products', $product, "Producto '{$product->name}' creado desde compras");

        $this->isQuickCreateOpen = false;
        $this->productSearch = '';
        $this->searchResults = [];

        // Auto-add to cart
        $this->addProduct($product->id);

        $this->dispatch('notify', message: "Producto '{$product->name}' creado y agregado", type: 'success');
    }

    public function completePurchase()
    {
        $this->savePurchase('completed');
    }

    private function savePurchase(string $status)
    {
        $permission = $this->isEditing ? 'purchases.edit' : 'purchases.create';
        if (!auth()->user()->hasPermission($permission)) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        if (count($this->cartItems) === 0) {
            $this->dispatch('notify', message: 'Agrega al menos un producto', type: 'error');
            return;
        }

        $rules = [
            'purchase_date' => 'required|date',
            'supplier_id' => 'required|exists:suppliers,id',
            'payment_type' => 'required|in:cash,credit',
        ];

        $messages = [
            'purchase_date.required' => 'La fecha es obligatoria',
            'supplier_id.required' => 'El proveedor es obligatorio',
            'supplier_id.exists' => 'El proveedor seleccionado no es válido',
            'branch_id.required' => 'Debe seleccionar una sucursal',
        ];

        // Branch is required for super_admin
        if ($this->needsBranchSelection) {
            $rules['branch_id'] = 'required|exists:branches,id';
        }

        if ($this->payment_type === 'cash') {
            $rules['payment_method_id'] = 'required|exists:payment_methods,id';
            $messages['payment_method_id.required'] = 'Selecciona un método de pago';
        } else {
            $rules['credit_amount'] = 'required|numeric|min:0';
            $rules['payment_due_date'] = 'required|date';
            $messages['credit_amount.required'] = 'El monto del crédito es obligatorio';
            $messages['payment_due_date.required'] = 'La fecha de pago es obligatoria';
        }

        $this->validate($rules, $messages);

        // Determine branch_id
        $branchId = $this->needsBranchSelection ? $this->branch_id : auth()->user()->branch_id;

        $purchaseData = [
            'supplier_id' => $this->supplier_id,
            'branch_id' => $branchId,
            'user_id' => auth()->id(),
            'supplier_invoice' => $this->supplier_invoice ?: null,
            'purchase_date' => $this->purchase_date,
            'due_date' => $this->due_date ?: null,
            'subtotal' => $this->subtotal,
            'tax_amount' => $this->taxAmount,
            'discount_amount' => $this->discountAmount,
            'total' => $this->total,
            'payment_type' => $this->payment_type,
            'payment_method_id' => $this->payment_type === 'cash' ? $this->payment_method_id : null,
            'credit_amount' => $this->payment_type === 'credit' ? $this->credit_amount : null,
            'paid_amount' => $this->payment_type === 'credit' ? $this->paid_amount : 0,
            'partial_payment_method_id' => $this->payment_type === 'credit' && $this->paid_amount > 0 ? $this->partial_payment_method_id : null,
            'payment_due_date' => $this->payment_type === 'credit' ? $this->payment_due_date : null,
            'notes' => $this->notes ?: null,
        ];

        // Determine payment status
        if ($this->payment_type === 'cash') {
            $purchaseData['payment_status'] = 'paid';
        } else {
            if ($this->paid_amount >= $this->credit_amount) {
                $purchaseData['payment_status'] = 'paid';
            } elseif ($this->paid_amount > 0) {
                $purchaseData['payment_status'] = 'partial';
            } else {
                $purchaseData['payment_status'] = 'pending';
            }
        }

        if ($this->isEditing) {
            $this->updatePurchase($purchaseData, $status);
        } else {
            $this->createPurchase($purchaseData, $status);
        }
    }

    private function createPurchase(array $data, string $status)
    {
        $data['purchase_number'] = Purchase::generatePurchaseNumber();
        $data['status'] = $status === 'completed' ? 'draft' : $status;

        $purchase = Purchase::create($data);

        foreach ($this->cartItems as $item) {
            PurchaseItem::create([
                'purchase_id' => $purchase->id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_cost' => $item['unit_cost'],
                'tax_rate' => $item['tax_rate'],
                'tax_amount' => $item['tax_amount'],
                'discount' => $item['discount'],
                'discount_type' => $item['discount_type'] ?? 'percentage',
                'discount_type_value' => $item['discount_type_value'] ?? 0,
                'subtotal' => $item['subtotal'],
                'total' => $item['total'],
            ]);

            // Update product prices if changed
            $product = Product::find($item['product_id']);
            if ($product) {
                $updates = [];
                if ($item['unit_cost'] != $product->purchase_price) {
                    $updates['purchase_price'] = $item['unit_cost'];
                }
                if (isset($item['sale_price']) && $item['sale_price'] != $product->sale_price) {
                    $updates['sale_price'] = $item['sale_price'];
                }
                if (!empty($updates)) {
                    $product->update($updates);
                }
            }
        }

        if ($status === 'completed') {
            $purchase->complete();
        }

        ActivityLogService::logCreate('purchases', $purchase, "Compra '{$purchase->purchase_number}' creada");

        $this->dispatch('notify', message: $status === 'completed' ? 'Compra completada' : 'Borrador guardado');
        
        // Auto-print if completed
        if ($status === 'completed') {
            $this->dispatch('print-purchase', purchaseId: $purchase->id);
        }
        
        return $this->redirect(route('purchases'), navigate: true);
    }

    private function updatePurchase(array $data, string $status)
    {
        $oldData = $this->purchase->toArray();
        $wasCompleted = $this->purchase->status === 'completed';

        // If editing a completed purchase, revert stock and delete old movements
        if ($wasCompleted) {
            // Delete old inventory movements for this purchase
            $this->purchase->inventoryMovements()->delete();
            
            foreach ($this->purchase->items as $item) {
                $product = $item->product;
                if ($product) {
                    $product->decrement('current_stock', $item->quantity);
                }
            }
        }

        // Update purchase
        $this->purchase->update($data);

        // Delete old items and create new ones
        $this->purchase->items()->delete();

        foreach ($this->cartItems as $item) {
            PurchaseItem::create([
                'purchase_id' => $this->purchase->id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_cost' => $item['unit_cost'],
                'tax_rate' => $item['tax_rate'],
                'tax_amount' => $item['tax_amount'],
                'discount' => $item['discount'],
                'discount_type' => $item['discount_type'] ?? 'percentage',
                'discount_type_value' => $item['discount_type_value'] ?? 0,
                'subtotal' => $item['subtotal'],
                'total' => $item['total'],
            ]);

            // Update product prices if changed
            $product = Product::find($item['product_id']);
            if ($product) {
                $updates = [];
                if ($item['unit_cost'] != $product->purchase_price) {
                    $updates['purchase_price'] = $item['unit_cost'];
                }
                if (isset($item['sale_price']) && $item['sale_price'] != $product->sale_price) {
                    $updates['sale_price'] = $item['sale_price'];
                }
                if (!empty($updates)) {
                    $product->update($updates);
                }
            }
        }

        // If completing or was completed, update stock
        if ($status === 'completed' || $wasCompleted) {
            $this->purchase->status = 'draft';
            $this->purchase->save();
            $this->purchase->refresh();
            $this->purchase->complete();
        }

        ActivityLogService::logUpdate('purchases', $this->purchase, $oldData, "Compra '{$this->purchase->purchase_number}' actualizada");

        $this->dispatch('notify', message: 'Compra actualizada');
        
        // Auto-print if completed
        if ($status === 'completed' || $wasCompleted) {
            $this->dispatch('print-purchase', purchaseId: $this->purchase->id);
        }
        
        return $this->redirect(route('purchases'), navigate: true);
    }
}

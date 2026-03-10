<?php

namespace App\Livewire;

use App\Models\Branch;
use App\Models\Brand;
use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductBarcode;
use App\Models\ProductFieldSetting;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Subcategory;
use App\Models\Supplier;
use App\Models\Municipality;
use App\Models\Tax;
use App\Models\TaxDocument;
use App\Models\Unit;
use App\Services\ActivityLogService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class PurchaseCreate extends Component
{
    use WithFileUploads;

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
    public string $quickDescription = '';
    public ?int $quickCategoryId = null;
    public ?int $quickSubcategoryId = null;
    public ?int $quickBrandId = null;
    public ?int $quickUnitId = null;
    public ?int $quickTaxId = null;
    public string $quickBarcode = '';
    public float $quickPurchasePrice = 0;
    public float $quickSalePrice = 0;
    public float $quickSpecialPrice = 0;
    public bool $quickPriceIncludesTax = false;
    public float $quickMinStock = 0;
    public float $quickMaxStock = 0;
    public bool $quickHasCommission = false;
    public string $quickCommissionType = 'percentage';
    public float $quickCommissionValue = 0;
    public $quickImage = null;
    public bool $quickIsActive = true;
    public $quickCategories = [];
    public $quickSubcategories = [];
    public $quickBrands = [];
    public $quickUnits = [];
    public $quickTaxes = [];

    // Quick supplier creation
    public bool $isSupplierCreateOpen = false;
    public string $supplierName = '';
    public string $supplierPhone = '';
    public string $supplierDocument = '';
    public ?int $supplierTaxDocumentId = null;
    public string $supplierEmail = '';
    public $supplierDepartmentId = '';
    public $supplierMunicipalityId = '';
    public string $supplierAddress = '';
    public string $supplierSalespersonName = '';
    public string $supplierSalespersonPhone = '';
    public array $supplierMunicipalities = [];

    // Multiple payment methods (for cash purchases)
    public array $purchasePayments = [];
    public ?int $newPaymentMethodId = null;
    public ?float $newPaymentAmount = null;

    // Global purchase discount
    public $showGlobalDiscountModal = false;
    public $globalDiscountType = 'percentage';
    public $globalDiscountValue = '';
    public $globalDiscountReason = '';
    public $globalDiscountApplied = false;
    public $globalDiscountAmount = 0;

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
        } else {
            $this->purchasePayments = [['method_id' => '', 'amount' => '']];
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

        // Load global discount
        if ($this->purchase->global_discount_type) {
            $this->globalDiscountApplied = true;
            $this->globalDiscountType = $this->purchase->global_discount_type;
            $this->globalDiscountValue = (string) $this->purchase->global_discount_value;
            $this->globalDiscountReason = $this->purchase->global_discount_reason ?? '';
            $this->globalDiscountAmount = (float) $this->purchase->global_discount_amount;
        }

        // Load multiple payment methods
        if ($this->payment_type === 'cash' && $this->purchase->payment_details) {
            $details = json_decode($this->purchase->payment_details, true);
            if (is_array($details) && !empty($details)) {
                $this->purchasePayments = array_map(fn($d) => [
                    'method_id' => $d['payment_method_id'] ?? '',
                    'amount' => $d['amount'] ?? '',
                ], $details);
            } else {
                $this->purchasePayments = $this->payment_method_id
                    ? [['method_id' => $this->payment_method_id, 'amount' => (float) $this->purchase->total]]
                    : [['method_id' => '', 'amount' => '']];
            }
        } elseif ($this->payment_type === 'cash') {
            // Legacy: single payment method
            $this->purchasePayments = $this->payment_method_id
                ? [['method_id' => $this->payment_method_id, 'amount' => (float) $this->purchase->total]]
                : [['method_id' => '', 'amount' => '']];
        }

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
        $trimmed = trim($this->productSearch);
        if (strlen($trimmed) < 2) {
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
            ->where(function ($q) use ($trimmed) {
                $q->where('name', 'like', "%{$trimmed}%")
                    ->orWhere('sku', 'like', "%{$trimmed}%")
                    ->orWhere('barcode', 'like', "%{$trimmed}%");
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
        $this->globalDiscountApplied = false;
        $this->globalDiscountAmount = 0;
        $this->globalDiscountValue = '';
        $this->globalDiscountReason = '';
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
        $baseTotal = $this->subtotal + $this->taxAmount - $this->discountAmount;

        // Recalculate global discount amount if applied (percentage may change with cart changes)
        if ($this->globalDiscountApplied) {
            $value = (float) str_replace(',', '.', $this->globalDiscountValue);
            if ($this->globalDiscountType === 'percentage' && $value > 0) {
                $this->globalDiscountAmount = round($baseTotal * ($value / 100), 2);
            }
            // For fixed, keep the amount as-is but cap it
            if ($this->globalDiscountAmount > $baseTotal) {
                $this->globalDiscountAmount = $baseTotal;
            }
        }

        $this->total = $baseTotal - $this->globalDiscountAmount;

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
            // Initialize with one empty payment row if none exist
            if (empty($this->purchasePayments)) {
                $this->purchasePayments = [['method_id' => '', 'amount' => '']];
            }
        } else {
            $this->payment_method_id = null;
            $this->purchasePayments = [];
            $this->credit_amount = $this->total;
        }
    }

    public function addPaymentRow()
    {
        $this->purchasePayments[] = ['method_id' => '', 'amount' => ''];
    }

    public function removePaymentRow(int $index)
    {
        if (count($this->purchasePayments) > 1) {
            array_splice($this->purchasePayments, $index, 1);
            $this->purchasePayments = array_values($this->purchasePayments);
        }
    }

    public function fillRemainingPayment(int $index)
    {
        $allocated = 0;
        foreach ($this->purchasePayments as $i => $p) {
            if ($i !== $index) {
                $allocated += floatval($p['amount'] ?? 0);
            }
        }
        $remaining = round($this->total - $allocated, 2);
        if ($remaining > 0) {
            $this->purchasePayments[$index]['amount'] = $remaining;
        }
    }

    public function openGlobalDiscountModal()
    {
        if (empty($this->cartItems)) {
            $this->dispatch('notify', message: 'Agrega productos primero', type: 'warning');
            return;
        }

        if (!$this->globalDiscountApplied) {
            $this->globalDiscountType = 'percentage';
            $this->globalDiscountValue = '';
            $this->globalDiscountReason = '';
        }

        $this->showGlobalDiscountModal = true;
    }

    public function applyGlobalDiscount()
    {
        $value = (float) str_replace(',', '.', $this->globalDiscountValue);

        if ($value < 0) {
            $this->dispatch('notify', message: 'El descuento no puede ser negativo', type: 'error');
            return;
        }

        if ($this->globalDiscountType === 'percentage' && $value > 100) {
            $this->dispatch('notify', message: 'El porcentaje no puede ser mayor a 100%', type: 'error');
            return;
        }

        $baseTotal = $this->subtotal + $this->taxAmount - $this->discountAmount;

        if ($value > 0) {
            if ($this->globalDiscountType === 'percentage') {
                $discountAmount = round($baseTotal * ($value / 100), 2);
            } else {
                $discountAmount = round($value, 2);
            }

            if ($discountAmount > $baseTotal) {
                $this->dispatch('notify', message: 'El descuento no puede ser mayor al total', type: 'error');
                return;
            }

            $this->globalDiscountApplied = true;
            $this->globalDiscountAmount = $discountAmount;
        } else {
            $this->globalDiscountApplied = false;
            $this->globalDiscountAmount = 0;
        }

        $this->calculateTotals();
        $this->showGlobalDiscountModal = false;
        $this->dispatch('notify', message: $value > 0 ? 'Descuento global aplicado' : 'Descuento global eliminado');
    }

    public function removeGlobalDiscount()
    {
        $this->globalDiscountApplied = false;
        $this->globalDiscountAmount = 0;
        $this->globalDiscountValue = '';
        $this->globalDiscountReason = '';
        $this->calculateTotals();
        $this->dispatch('notify', message: 'Descuento global eliminado');
    }

    public function closeGlobalDiscountModal()
    {
        $this->showGlobalDiscountModal = false;
    }

    // Supplier quick create
    public function openSupplierCreate()
    {
        $this->supplierName = '';
        $this->supplierPhone = '';
        $this->supplierDocument = '';
        $this->supplierTaxDocumentId = null;
        $this->supplierEmail = '';
        $this->supplierDepartmentId = '';
        $this->supplierMunicipalityId = '';
        $this->supplierAddress = '';
        $this->supplierSalespersonName = '';
        $this->supplierSalespersonPhone = '';
        $this->supplierMunicipalities = [];
        $this->resetValidation();
        $this->isSupplierCreateOpen = true;
    }

    public function updatedSupplierDepartmentId()
    {
        $this->supplierMunicipalityId = '';
        $this->supplierMunicipalities = $this->supplierDepartmentId
            ? Municipality::where('department_id', $this->supplierDepartmentId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(fn($m) => ['id' => $m->id, 'name' => $m->name])
                ->toArray()
            : [];
    }

    public function storeQuickSupplier()
    {
        $this->validate([
            'supplierName' => 'required|min:2',
            'supplierTaxDocumentId' => 'required|exists:tax_documents,id',
            'supplierDocument' => 'required|string|unique:suppliers,document_number',
            'supplierDepartmentId' => 'required|exists:departments,id',
            'supplierMunicipalityId' => 'required|exists:municipalities,id',
            'supplierAddress' => 'required|string|min:5',
        ], [
            'supplierName.required' => 'El nombre es obligatorio',
            'supplierName.min' => 'Mínimo 2 caracteres',
            'supplierTaxDocumentId.required' => 'Selecciona un tipo de documento',
            'supplierDocument.required' => 'El número de documento es obligatorio',
            'supplierDocument.unique' => 'Este documento ya está registrado',
            'supplierDepartmentId.required' => 'Selecciona un departamento',
            'supplierMunicipalityId.required' => 'Selecciona un municipio',
            'supplierAddress.required' => 'La dirección es obligatoria',
            'supplierAddress.min' => 'Mínimo 5 caracteres',
        ]);

        $supplier = Supplier::create([
            'name' => mb_strtoupper($this->supplierName),
            'tax_document_id' => $this->supplierTaxDocumentId,
            'document_number' => $this->supplierDocument,
            'phone' => $this->supplierPhone ?: null,
            'email' => $this->supplierEmail ?: null,
            'department_id' => $this->supplierDepartmentId,
            'municipality_id' => $this->supplierMunicipalityId,
            'address' => $this->supplierAddress,
            'salesperson_name' => $this->supplierSalespersonName ?: null,
            'salesperson_phone' => $this->supplierSalespersonPhone ?: null,
            'is_active' => true,
        ]);

        ActivityLogService::logCreate('suppliers', $supplier, "Proveedor '{$supplier->name}' creado desde compras");

        // Refresh suppliers list and select the new one
        $this->suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        $this->supplier_id = $supplier->id;
        $this->isSupplierCreateOpen = false;

        $this->dispatch('notify', message: 'Proveedor creado correctamente', type: 'success');
    }

    public function saveDraft()
    {
        $this->savePurchase('draft');
    }

    public function openQuickCreate()
    {
        $this->quickCategories = Category::where('is_active', true)->orderBy('name')->get();
        $this->quickSubcategories = [];
        $this->quickBrands = Brand::where('is_active', true)->orderBy('name')->get();
        $this->quickUnits = Unit::where('is_active', true)->orderBy('name')->get();
        $this->quickTaxes = Tax::where('is_active', true)->orderBy('name')->get();

        $this->quickName = mb_strtoupper($this->productSearch);
        $this->quickDescription = '';
        $this->quickCategoryId = null;
        $this->quickSubcategoryId = null;
        $this->quickBrandId = null;
        $this->quickUnitId = null;
        $this->quickTaxId = null;
        $this->quickBarcode = '';
        $this->quickPurchasePrice = 0;
        $this->quickSalePrice = 0;
        $this->quickSpecialPrice = 0;
        $this->quickPriceIncludesTax = false;
        $this->quickMinStock = 0;
        $this->quickMaxStock = 0;
        $this->quickHasCommission = false;
        $this->quickCommissionType = 'percentage';
        $this->quickCommissionValue = 0;
        $this->quickImage = null;
        $this->quickIsActive = true;

        $this->isQuickCreateOpen = true;
    }

    public function updatedQuickCategoryId($value)
    {
        $this->quickSubcategoryId = null;
        $this->quickSubcategories = $value
            ? Subcategory::where('category_id', $value)->where('is_active', true)->orderBy('name')->get()
            : [];
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
            'quickBarcode' => 'nullable|string',
            'quickImage' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'quickMinStock' => 'nullable|numeric|min:0',
            'quickMaxStock' => 'nullable|numeric|min:0',
            'quickSpecialPrice' => 'nullable|numeric|min:0',
        ], [
            'quickName.required' => 'El nombre es obligatorio',
            'quickName.min' => 'El nombre debe tener al menos 2 caracteres',
            'quickCategoryId.required' => 'La categoría es obligatoria',
            'quickUnitId.required' => 'La unidad es obligatoria',
            'quickPurchasePrice.required' => 'El precio de compra es obligatorio',
            'quickSalePrice.required' => 'El precio de venta es obligatorio',
            'quickImage.image' => 'El archivo debe ser una imagen',
            'quickImage.mimes' => 'La imagen debe ser JPG, PNG o WebP',
            'quickImage.max' => 'La imagen no debe superar 2MB',
        ]);

        $branchId = $this->needsBranchSelection ? $this->branch_id : auth()->user()->branch_id;

        // Handle image upload
        $imagePath = null;
        if ($this->quickImage) {
            $imagePath = $this->quickImage->store('products', 'public');
        }

        $product = Product::create([
            'branch_id' => $branchId,
            'name' => mb_strtoupper($this->quickName),
            'description' => $this->quickDescription ? mb_strtoupper($this->quickDescription) : null,
            'category_id' => $this->quickCategoryId,
            'subcategory_id' => $this->quickSubcategoryId ?: null,
            'brand_id' => $this->quickBrandId ?: null,
            'unit_id' => $this->quickUnitId,
            'tax_id' => $this->quickTaxId ?: null,
            'barcode' => $this->quickBarcode ?: null,
            'purchase_price' => $this->quickPurchasePrice,
            'sale_price' => $this->quickSalePrice,
            'special_price' => $this->quickSpecialPrice ?: null,
            'price_includes_tax' => $this->quickPriceIncludesTax,
            'current_stock' => 0,
            'min_stock' => $this->quickMinStock,
            'max_stock' => $this->quickMaxStock ?: null,
            'has_commission' => $this->quickHasCommission,
            'commission_type' => $this->quickHasCommission ? $this->quickCommissionType : null,
            'commission_value' => $this->quickHasCommission ? $this->quickCommissionValue : null,
            'is_active' => $this->quickIsActive,
            'image' => $imagePath,
        ]);

        $product->generateSku();
        $product->save();

        // Save barcode to product_barcodes table
        if ($this->quickBarcode) {
            ProductBarcode::create([
                'product_id' => $product->id,
                'product_child_id' => null,
                'barcode' => $this->quickBarcode,
                'description' => 'Código principal',
                'is_primary' => true,
            ]);
        }

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
            // Validate multiple payments
            if (empty($this->purchasePayments)) {
                $this->dispatch('notify', message: 'Agrega al menos un método de pago', type: 'error');
                return;
            }
            foreach ($this->purchasePayments as $i => $payment) {
                if (empty($payment['method_id'])) {
                    $this->dispatch('notify', message: 'Selecciona el método de pago en la fila ' . ($i + 1), type: 'error');
                    return;
                }
                if (!is_numeric($payment['amount'] ?? '') || floatval($payment['amount']) <= 0) {
                    $this->dispatch('notify', message: 'El monto debe ser mayor a 0 en la fila ' . ($i + 1), type: 'error');
                    return;
                }
            }
            // Validate total matches
            $paymentTotal = array_sum(array_map(fn($p) => floatval($p['amount'] ?? 0), $this->purchasePayments));
            if (abs($paymentTotal - $this->total) > 0.01) {
                $this->dispatch('notify', message: 'El total de los pagos ($' . number_format($paymentTotal, 2) . ') no coincide con el total de la compra ($' . number_format($this->total, 2) . ')', type: 'error');
                return;
            }
        } else {
            $rules['credit_amount'] = 'required|numeric|min:0';
            $rules['payment_due_date'] = 'required|date';
            $messages['credit_amount.required'] = 'El monto del crédito es obligatorio';
            $messages['payment_due_date.required'] = 'La fecha de pago es obligatoria';
        }

        $this->validate($rules, $messages);

        // Determine branch_id
        $branchId = $this->needsBranchSelection ? $this->branch_id : auth()->user()->branch_id;

        // For cash: use first payment method as the main one (backward compatibility)
        $mainPaymentMethodId = null;
        if ($this->payment_type === 'cash' && !empty($this->purchasePayments)) {
            $mainPaymentMethodId = $this->purchasePayments[0]['method_id'];
        }

        $purchaseData = [
            'supplier_id' => $this->supplier_id,
            'branch_id' => $branchId,
            'user_id' => auth()->id(),
            'supplier_invoice' => $this->supplier_invoice ?: null,
            'purchase_date' => $this->purchase_date,
            'due_date' => $this->due_date ?: null,
            'subtotal' => $this->subtotal,
            'tax_amount' => $this->taxAmount,
            'discount_amount' => $this->discountAmount + $this->globalDiscountAmount,
            'total' => $this->total,
            'payment_type' => $this->payment_type,
            'payment_method_id' => $mainPaymentMethodId,
            'credit_amount' => $this->payment_type === 'credit' ? $this->credit_amount : null,
            'paid_amount' => $this->payment_type === 'credit' ? $this->paid_amount : 0,
            'partial_payment_method_id' => $this->payment_type === 'credit' && $this->paid_amount > 0 ? $this->partial_payment_method_id : null,
            'payment_due_date' => $this->payment_type === 'credit' ? $this->payment_due_date : null,
            'notes' => $this->notes ?: null,
            'global_discount_type' => $this->globalDiscountApplied ? $this->globalDiscountType : null,
            'global_discount_value' => $this->globalDiscountApplied ? (float) str_replace(',', '.', $this->globalDiscountValue) : 0,
            'global_discount_amount' => $this->globalDiscountAmount,
            'global_discount_reason' => $this->globalDiscountApplied && trim($this->globalDiscountReason) ? trim($this->globalDiscountReason) : null,
        ];

        // Build payment details JSON for multiple payments
        if ($this->payment_type === 'cash' && count($this->purchasePayments) > 0) {
            $paymentDetails = [];
            foreach ($this->purchasePayments as $p) {
                $method = PaymentMethod::find($p['method_id']);
                $paymentDetails[] = [
                    'payment_method_id' => (int) $p['method_id'],
                    'payment_method_name' => $method?->name ?? '',
                    'amount' => round(floatval($p['amount']), 2),
                ];
            }
            $purchaseData['payment_details'] = json_encode($paymentDetails);
        } else {
            $purchaseData['payment_details'] = null;
        }

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

<?php

namespace App\Livewire;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Combo;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Discount;
use App\Models\Municipality;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductBarcode;
use App\Models\ProductChild;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Service;
use App\Models\TaxDocument;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.pos')]
class QuoteCreate extends Component
{
    // Customer
    public $customerId = null;
    public $customerSearch = '';
    public $selectedCustomer = null;

    // Create customer form
    public $showCreateCustomer = false;
    public $newCustomerType = 'natural';
    public $newCustomerDocumentType = null;
    public $newCustomerDocument = '';
    public $newCustomerFirstName = '';
    public $newCustomerLastName = '';
    public $newCustomerBusinessName = '';
    public $newCustomerPhone = '';
    public $newCustomerEmail = '';
    public $newCustomerDepartmentId = '';
    public $newCustomerMunicipalityId = '';
    public $newCustomerMunicipalities = [];

    // Product search
    public $productSearch = '';
    public $barcodeSearch = '';
    public $selectedCategory = null;

    // Cart
    public $cart = [];

    // Branch (super_admin must select)
    public $branchId = null;
    public $availableBranches = [];

    // Variant modal
    public $showVariantModal = false;
    public $variantProduct = null;
    public $variantOptions = [];

    // Item discount modal
    public $showDiscountModal = false;
    public $discountCartKey = null;
    public $discountType = 'percentage';
    public $discountValue = '';
    public $discountReason = '';

    // Weight modal
    public $showWeightModal = false;
    public $weightModalProduct = null;
    public $weightModalQuantity = '';

    // Global discount modal
    public $showGlobalDiscountModal = false;
    public $globalDiscountType = 'percentage';
    public $globalDiscountValue = '';
    public $globalDiscountReason = '';
    public $globalDiscountApplied = false;
    public $globalDiscountAmount = 0;

    // Price override mode
    public $showPriceOverride = false;

    // Save quote modal
    public $showSaveModal = false;
    public $saveValidUntil = '';
    public $saveNotes = '';

    // Save confirmation modal (after save - cannot edit warning)
    public $showSavedConfirmModal = false;
    public $savedQuoteId = null;

    public function mount(): void
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            $this->availableBranches = Branch::where('is_active', true)->orderBy('name')->get()->toArray();
            // Default to user's branch if set, otherwise leave empty (must select)
            $this->branchId = $user->branch_id ?: null;
        } else {
            $this->branchId = $user->branch_id;
        }

        // Set default valid_until to 15 days from now
        $this->saveValidUntil = now()->addDays(15)->format('Y-m-d');

        if ($this->branchId) {
            $this->loadDefaultCustomer();
        }
    }

    public function updatedBranchId(): void
    {
        // When super_admin changes branch, clear cart and reload default customer
        $this->cart = [];
        $this->customerId = null;
        $this->selectedCustomer = null;
        $this->productSearch = '';
        $this->customerSearch = '';
        $this->globalDiscountApplied = false;
        $this->globalDiscountAmount = 0;
        $this->globalDiscountValue = '';
        $this->globalDiscountReason = '';

        if ($this->branchId) {
            $this->loadDefaultCustomer();
        }
    }

    public function loadDefaultCustomer(): void
    {
        $defaultCustomer = Customer::where('is_default', true)
            ->forBranch($this->branchId)
            ->first();

        if ($defaultCustomer) {
            $this->customerId = $defaultCustomer->id;
            $this->selectedCustomer = $defaultCustomer;
        }
    }

    public function selectCustomer($customerId): void
    {
        $this->selectedCustomer = Customer::find($customerId);
        $this->customerId = $customerId;
        $this->customerSearch = '';
    }

    public function clearCustomer(): void
    {
        $this->loadDefaultCustomer();
        $this->customerSearch = '';
    }

    public function openCreateCustomer(): void
    {
        $this->showCreateCustomer = true;
        $this->resetCreateCustomerForm();
    }

    public function closeCreateCustomer(): void
    {
        $this->showCreateCustomer = false;
        $this->resetCreateCustomerForm();
    }

    public function resetCreateCustomerForm(): void
    {
        $this->newCustomerType = 'natural';
        $this->newCustomerDocumentType = null;
        $this->newCustomerDocument = '';
        $this->newCustomerFirstName = '';
        $this->newCustomerLastName = '';
        $this->newCustomerBusinessName = '';
        $this->newCustomerPhone = '';
        $this->newCustomerEmail = '';
        $this->newCustomerDepartmentId = '';
        $this->newCustomerMunicipalityId = '';
        $this->newCustomerMunicipalities = [];
    }

    public function updatedNewCustomerDepartmentId(): void
    {
        $this->newCustomerMunicipalityId = '';
        $this->newCustomerMunicipalities = $this->newCustomerDepartmentId
            ? Municipality::where('department_id', $this->newCustomerDepartmentId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->toArray()
            : [];
    }

    public function saveNewCustomer(): void
    {
        if (!$this->branchId) {
            $this->dispatch('notify', message: 'Debes seleccionar una sucursal primero', type: 'error');
            return;
        }

        if ($this->newCustomerType === 'natural') {
            if (empty($this->newCustomerFirstName)) {
                $this->dispatch('notify', message: 'El nombre es obligatorio', type: 'error');
                return;
            }
        } else {
            if (empty($this->newCustomerBusinessName)) {
                $this->dispatch('notify', message: 'La razón social es obligatoria', type: 'error');
                return;
            }
        }

        if (empty($this->newCustomerDocument)) {
            $this->dispatch('notify', message: 'El número de documento es obligatorio', type: 'error');
            return;
        }

        if (empty($this->newCustomerDepartmentId)) {
            $this->dispatch('notify', message: 'El departamento es obligatorio', type: 'error');
            return;
        }

        if (empty($this->newCustomerMunicipalityId)) {
            $this->dispatch('notify', message: 'El municipio es obligatorio', type: 'error');
            return;
        }

        $exists = Customer::where('document_number', $this->newCustomerDocument)
            ->forBranch($this->branchId)
            ->exists();

        if ($exists) {
            $this->dispatch('notify', message: 'Ya existe un cliente con ese documento', type: 'error');
            return;
        }

        try {
            $customer = Customer::create([
                'branch_id' => $this->branchId,
                'customer_type' => $this->newCustomerType,
                'tax_document_id' => $this->newCustomerDocumentType,
                'document_number' => $this->newCustomerDocument,
                'first_name' => $this->newCustomerType === 'natural' ? $this->newCustomerFirstName : null,
                'last_name' => $this->newCustomerType === 'natural' ? $this->newCustomerLastName : null,
                'business_name' => $this->newCustomerType === 'juridico' ? $this->newCustomerBusinessName : null,
                'phone' => $this->newCustomerPhone ?: null,
                'email' => $this->newCustomerEmail ?: null,
                'department_id' => $this->newCustomerDepartmentId,
                'municipality_id' => $this->newCustomerMunicipalityId,
                'is_active' => true,
                'is_default' => false,
            ]);

            $this->selectCustomer($customer->id);
            $this->showCreateCustomer = false;
            $this->resetCreateCustomerForm();

            $this->dispatch('close-customer-modal');
            $this->dispatch('notify', message: 'Cliente creado correctamente', type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('notify', message: 'Error al crear cliente: ' . $e->getMessage(), type: 'error');
        }
    }

    public function searchByBarcode(): void
    {
        if (!$this->branchId) {
            $this->dispatch('notify', message: 'Selecciona una sucursal primero', type: 'warning');
            return;
        }

        $barcode = trim($this->barcodeSearch);
        if (empty($barcode)) {
            return;
        }

        $barcodeRecord = ProductBarcode::where('barcode', $barcode)->first();

        if ($barcodeRecord) {
            if ($barcodeRecord->product_child_id) {
                $child = ProductChild::where('id', $barcodeRecord->product_child_id)
                    ->where('is_active', true)
                    ->whereHas('product', function ($q) {
                        $q->where('is_active', true)->forBranch($this->branchId);
                    })
                    ->first();
                if ($child) {
                    $this->addToCart($child->product_id, $child->id);
                    $this->barcodeSearch = '';
                    $this->dispatch('focus-barcode-search');
                    return;
                }
            }

            if ($barcodeRecord->product_id) {
                $product = Product::where('id', $barcodeRecord->product_id)
                    ->where('is_active', true)
                    ->forBranch($this->branchId)
                    ->with(['children' => fn($q) => $q->where('is_active', true), 'brand'])
                    ->first();
                if ($product) {
                    if ($product->children->count() > 0) {
                        $this->openVariantModal($product);
                        $this->barcodeSearch = '';
                    } else {
                        $this->addToCart($product->id);
                        $this->barcodeSearch = '';
                        $this->dispatch('focus-barcode-search');
                    }
                    return;
                }
            }
        }

        // Legacy fallback
        $child = ProductChild::where('barcode', $barcode)
            ->where('is_active', true)
            ->whereHas('product', function ($q) {
                $q->where('is_active', true)->forBranch($this->branchId);
            })
            ->first();
        if ($child) {
            $this->addToCart($child->product_id, $child->id);
            $this->barcodeSearch = '';
            $this->dispatch('focus-barcode-search');
            return;
        }

        $product = Product::where('barcode', $barcode)
            ->where('is_active', true)
            ->forBranch($this->branchId)
            ->with(['children' => fn($q) => $q->where('is_active', true), 'brand'])
            ->first();
        if ($product) {
            if ($product->children->count() > 0) {
                $this->openVariantModal($product);
                $this->barcodeSearch = '';
            } else {
                $this->addToCart($product->id);
                $this->barcodeSearch = '';
                $this->dispatch('focus-barcode-search');
            }
            return;
        }

        $this->dispatch('notify', message: 'Producto no encontrado: ' . $barcode, type: 'warning');
        $this->barcodeSearch = '';
        $this->dispatch('focus-barcode-search');
    }

    public function openVariantModal($product): void
    {
        $this->variantProduct = [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'image' => $product->image,
            'brand' => $product->brand?->name,
            'sale_price' => (float) $product->sale_price,
            'current_stock' => (float) $product->current_stock,
        ];

        $this->variantOptions = $product->children->map(function ($child) {
            return [
                'id' => $child->id,
                'name' => $child->name,
                'sku' => $child->sku,
                'image' => $child->image,
                'sale_price' => (float) $child->sale_price,
                'current_stock' => (float) $child->current_stock,
            ];
        })->toArray();

        $this->showVariantModal = true;
    }

    public function selectVariant($childId = null): void
    {
        if ($this->variantProduct) {
            $this->addToCart($this->variantProduct['id'], $childId);
        }
        $this->closeVariantModal();
    }

    public function closeVariantModal(): void
    {
        $this->showVariantModal = false;
        $this->variantProduct = null;
        $this->variantOptions = [];
        $this->dispatch('focus-barcode-search');
    }

    public function selectCategory($categoryId): void
    {
        $this->selectedCategory = $categoryId === $this->selectedCategory ? null : $categoryId;
    }

    /**
     * Add a product to cart.
     * Validates that there is enough stock available before adding/incrementing.
     * Stock = current_stock (already accounts for other quote reservations).
     */
    public function addToCart($productId, $childId = null): void
    {
        if (!$this->branchId) {
            $this->dispatch('notify', message: 'Selecciona una sucursal primero', type: 'warning');
            return;
        }

        $product = Product::with(['tax', 'unit', 'children' => fn($q) => $q->where('is_active', true)])
            ->find($productId);

        if (!$product) return;

        // Weight-based product → open weight modal
        if ($this->isWeightBasedProduct($product)) {
            $this->openWeightModal($productId, $childId);
            return;
        }

        $child = $childId ? ProductChild::find($childId) : null;
        $price = (float) ($child?->sale_price ?? $product->sale_price);

        $cartKey = $productId . '-' . ($childId ?? 'parent');

        if (isset($this->cart[$cartKey])) {
            // Validate stock before incrementing
            if ($product->manages_inventory) {
                $currentInCart = (float) $this->cart[$cartKey]['quantity'];
                $availableStock = (float) $product->current_stock;
                if ($currentInCart >= $availableStock) {
                    $this->dispatch('notify', message: "Sin stock disponible para \"{$product->name}\" (disponible: " . rtrim(rtrim(number_format($availableStock, 3), '0'), '.') . ')', type: 'error');
                    return;
                }
            }
            $this->cart[$cartKey]['quantity']++;
            $this->updateCartItemTotals($cartKey);
        } else {
            // Validate stock before adding new item
            if ($product->manages_inventory) {
                $availableStock = (float) $product->current_stock;
                if ($availableStock <= 0) {
                    $this->dispatch('notify', message: "Sin stock disponible para \"{$product->name}\"", type: 'error');
                    return;
                }
            }

            $priceIncludesTax = $child ? $child->price_includes_tax : $product->price_includes_tax;
            $taxRate = $product->tax?->value ?? 0;

            if ($priceIncludesTax) {
                $priceWithTax = $price;
                $basePrice = $taxRate > 0 ? $price / (1 + ($taxRate / 100)) : $price;
            } else {
                $basePrice = $price;
                $priceWithTax = $taxRate > 0 ? $price * (1 + ($taxRate / 100)) : $price;
            }

            $displayName = $child ? $child->name : $product->name;
            $displayImage = $child ? ($child->image ?? $product->image) : $product->image;
            $specialPrice = $child ? $child->special_price : $product->special_price;
            $hasSpecialPrice = $specialPrice && $specialPrice > 0;

            $this->cart[$cartKey] = [
                'product_id' => $productId,
                'child_id' => $childId,
                'service_id' => null,
                'combo_id' => null,
                'is_service' => false,
                'is_combo' => false,
                'name' => $displayName,
                'sku' => $child ? $child->sku : $product->sku,
                'price' => round($priceWithTax, 2),
                'base_price' => round($basePrice, 2),
                'original_price' => round($priceWithTax, 2),
                'original_base_price' => round($basePrice, 2),
                'special_price' => $hasSpecialPrice ? round((float) $specialPrice, 2) : null,
                'using_special_price' => false,
                'quantity' => 1,
                'subtotal' => round($basePrice, 2),
                'tax_id' => $product->tax_id,
                'tax_rate' => $taxRate,
                'tax_amount' => round($priceWithTax - $basePrice, 2),
                'price_includes_tax' => $priceIncludesTax,
                'image' => $displayImage,
                'discount_type' => null,
                'discount_type_value' => 0,
                'discount_amount' => 0,
                'discount_reason' => null,
                'price_overridden' => false,
            ];

            $this->applyAutoDiscount($cartKey, $product);
        }

        $this->productSearch = '';
        $this->dispatch('focus-product-search');
    }

    public function addServiceToCart($serviceId): void
    {
        $service = Service::with('tax')->find($serviceId);
        if (!$service) return;

        $cartKey = 'service-' . $serviceId;

        if (isset($this->cart[$cartKey])) {
            $this->cart[$cartKey]['quantity']++;
            $this->updateCartItemTotals($cartKey);
        } else {
            $priceIncludesTax = $service->price_includes_tax;
            $taxRate = $service->tax?->value ?? 0;
            $price = (float) $service->sale_price;

            if ($priceIncludesTax) {
                $priceWithTax = $price;
                $basePrice = $taxRate > 0 ? $price / (1 + ($taxRate / 100)) : $price;
            } else {
                $basePrice = $price;
                $priceWithTax = $taxRate > 0 ? $price * (1 + ($taxRate / 100)) : $price;
            }

            $this->cart[$cartKey] = [
                'product_id' => null,
                'child_id' => null,
                'service_id' => $serviceId,
                'combo_id' => null,
                'is_service' => true,
                'is_combo' => false,
                'name' => $service->name,
                'sku' => $service->sku,
                'price' => round($priceWithTax, 2),
                'base_price' => round($basePrice, 2),
                'original_price' => round($priceWithTax, 2),
                'original_base_price' => round($basePrice, 2),
                'special_price' => null,
                'using_special_price' => false,
                'quantity' => 1,
                'subtotal' => round($basePrice, 2),
                'tax_id' => $service->tax_id,
                'tax_rate' => $taxRate,
                'tax_amount' => round($priceWithTax - $basePrice, 2),
                'price_includes_tax' => $priceIncludesTax,
                'image' => $service->image,
                'discount_type' => null,
                'discount_type_value' => 0,
                'discount_amount' => 0,
                'discount_reason' => null,
                'price_overridden' => false,
            ];
        }

        $this->productSearch = '';
        $this->dispatch('focus-product-search');
    }

    public function addComboToCart($comboId): void
    {
        $combo = Combo::with(['items.product', 'items.productChild'])->find($comboId);
        if (!$combo) {
            $this->dispatch('notify', message: 'Combo no disponible', type: 'error');
            return;
        }

        $cartKey = 'combo-' . $comboId;

        if (isset($this->cart[$cartKey])) {
            $this->cart[$cartKey]['quantity']++;
            $this->updateCartItemTotals($cartKey);
        } else {
            $comboPrice = (float) $combo->combo_price;

            $this->cart[$cartKey] = [
                'product_id' => null,
                'child_id' => null,
                'service_id' => null,
                'combo_id' => $comboId,
                'is_service' => false,
                'is_combo' => true,
                'name' => $combo->name,
                'sku' => 'COMBO-' . $combo->id,
                'price' => $comboPrice,
                'base_price' => $comboPrice,
                'original_price' => $comboPrice,
                'original_base_price' => $comboPrice,
                'special_price' => null,
                'using_special_price' => false,
                'quantity' => 1,
                'subtotal' => $comboPrice,
                'tax_id' => null,
                'tax_rate' => 0,
                'tax_amount' => 0,
                'price_includes_tax' => true,
                'image' => $combo->image,
                'discount_type' => null,
                'discount_type_value' => 0,
                'discount_amount' => 0,
                'discount_reason' => null,
                'price_overridden' => false,
            ];
        }

        $this->productSearch = '';
        $this->dispatch('focus-product-search');
    }

    protected function isWeightBasedProduct($product): bool
    {
        if (!$product->relationLoaded('unit')) {
            $product->load('unit');
        }
        return $product->unit && $product->unit->is_weight_unit;
    }

    public function openWeightModal($productId, $childId = null): void
    {
        $product = Product::with(['tax', 'unit', 'children' => fn($q) => $q->where('is_active', true)])
            ->find($productId);
        if (!$product) return;

        $child = $childId ? ProductChild::find($childId) : null;
        $price = (float) ($child?->sale_price ?? $product->sale_price);

        $priceIncludesTax = $child ? $child->price_includes_tax : $product->price_includes_tax;
        $taxRate = $product->tax?->value ?? 0;

        if ($priceIncludesTax) {
            $priceWithTax = $price;
        } else {
            $priceWithTax = $taxRate > 0 ? $price * (1 + ($taxRate / 100)) : $price;
        }

        $displayName = $child ? $child->name : $product->name;
        $displayImage = $child ? ($child->image ?? $product->image) : $product->image;

        $this->weightModalProduct = [
            'product_id' => $productId,
            'child_id' => $childId,
            'name' => $displayName,
            'price' => round($priceWithTax, 2),
            'unit' => $product->unit?->abbreviation ?? 'UND',
            'image' => $displayImage,
        ];

        $this->weightModalQuantity = '';
        $this->showWeightModal = true;
    }

    public function confirmWeightModal(): void
    {
        if (!$this->showWeightModal || !$this->weightModalProduct) return;

        $quantityStr = trim($this->weightModalQuantity);
        if ($quantityStr === '' || $quantityStr === null) {
            $this->dispatch('notify', message: 'Ingresa una cantidad válida', type: 'error');
            return;
        }

        $quantityStr = str_replace(',', '.', $quantityStr);
        if (!is_numeric($quantityStr)) {
            $this->dispatch('notify', message: 'Ingresa una cantidad válida', type: 'error');
            return;
        }

        $quantity = (float) $quantityStr;
        if ($quantity <= 0) {
            $this->dispatch('notify', message: 'La cantidad debe ser mayor a cero', type: 'error');
            return;
        }

        $quantity = round($quantity, 3);

        $productId = $this->weightModalProduct['product_id'];
        $childId = $this->weightModalProduct['child_id'];

        $this->addProductToCartWithQuantity($productId, $childId, $quantity);
        $this->closeWeightModal();
    }

    public function closeWeightModal(): void
    {
        $this->showWeightModal = false;
        $this->weightModalProduct = null;
        $this->weightModalQuantity = '';
        $this->dispatch('focus-barcode-search');
    }

    protected function addProductToCartWithQuantity($productId, $childId, $quantity): void
    {
        $product = Product::with(['tax', 'children' => fn($q) => $q->where('is_active', true)])
            ->find($productId);
        if (!$product) return;

        $child = $childId ? ProductChild::find($childId) : null;
        $price = (float) ($child?->sale_price ?? $product->sale_price);
        $cartKey = $productId . '-' . ($childId ?? 'parent');

        if (isset($this->cart[$cartKey])) {
            // Validate stock before adding more quantity
            if ($product->manages_inventory) {
                $currentInCart = (float) $this->cart[$cartKey]['quantity'];
                $availableStock = (float) $product->current_stock;
                $newQty = round($currentInCart + $quantity, 3);
                if ($newQty > $availableStock) {
                    $remaining = round($availableStock - $currentInCart, 3);
                    if ($remaining <= 0) {
                        $this->dispatch('notify', message: "Sin stock disponible para \"{$product->name}\"", type: 'error');
                        return;
                    }
                    $quantity = $remaining;
                    $this->dispatch('notify', message: "Stock ajustado al máximo disponible: " . rtrim(rtrim(number_format($quantity, 3), '0'), '.'), type: 'warning');
                }
            }
            $this->cart[$cartKey]['quantity'] = round($this->cart[$cartKey]['quantity'] + $quantity, 3);
            $this->updateCartItemTotals($cartKey);
        } else {
            // Validate stock for new item
            if ($product->manages_inventory) {
                $availableStock = (float) $product->current_stock;
                if ($availableStock <= 0) {
                    $this->dispatch('notify', message: "Sin stock disponible para \"{$product->name}\"", type: 'error');
                    return;
                }
                if ($quantity > $availableStock) {
                    $quantity = $availableStock;
                    $this->dispatch('notify', message: "Stock ajustado al máximo disponible: " . rtrim(rtrim(number_format($quantity, 3), '0'), '.'), type: 'warning');
                }
            }

            $priceIncludesTax = $child ? $child->price_includes_tax : $product->price_includes_tax;
            $taxRate = $product->tax?->value ?? 0;

            if ($priceIncludesTax) {
                $priceWithTax = $price;
                $basePrice = $taxRate > 0 ? $price / (1 + ($taxRate / 100)) : $price;
            } else {
                $basePrice = $price;
                $priceWithTax = $taxRate > 0 ? $price * (1 + ($taxRate / 100)) : $price;
            }

            $displayName = $child ? $child->name : $product->name;
            $displayImage = $child ? ($child->image ?? $product->image) : $product->image;
            $specialPrice = $child ? $child->special_price : $product->special_price;
            $hasSpecialPrice = $specialPrice && $specialPrice > 0;

            $this->cart[$cartKey] = [
                'product_id' => $productId,
                'child_id' => $childId,
                'service_id' => null,
                'combo_id' => null,
                'is_service' => false,
                'is_combo' => false,
                'name' => $displayName,
                'sku' => $child ? $child->sku : $product->sku,
                'price' => round($priceWithTax, 2),
                'base_price' => round($basePrice, 2),
                'original_price' => round($priceWithTax, 2),
                'original_base_price' => round($basePrice, 2),
                'special_price' => $hasSpecialPrice ? round((float) $specialPrice, 2) : null,
                'using_special_price' => false,
                'quantity' => round($quantity, 3),
                'subtotal' => round($basePrice * $quantity, 2),
                'tax_id' => $product->tax_id,
                'tax_rate' => $taxRate,
                'tax_amount' => round(($priceWithTax - $basePrice) * $quantity, 2),
                'price_includes_tax' => $priceIncludesTax,
                'image' => $displayImage,
                'discount_type' => null,
                'discount_type_value' => 0,
                'discount_amount' => 0,
                'discount_reason' => null,
                'price_overridden' => false,
            ];

            $this->applyAutoDiscount($cartKey, $product);
        }

        $this->productSearch = '';
        $this->dispatch('focus-product-search');
    }

    protected function applyAutoDiscount(string $cartKey, Product $product): void
    {
        $discount = Discount::findBestForProduct($product, $this->branchId);
        if (!$discount) return;

        $item = &$this->cart[$cartKey];

        if ($discount->discount_type === 'percentage') {
            $discountAmount = round($item['subtotal'] * ($discount->discount_value / 100), 2);
        } else {
            $discountAmount = round(min($discount->discount_value * $item['quantity'], $item['subtotal']), 2);
        }

        $item['discount_type'] = $discount->discount_type;
        $item['discount_type_value'] = (float) $discount->discount_value;
        $item['discount_amount'] = $discountAmount;
        $item['discount_reason'] = $discount->name;

        $taxableAmount = $item['subtotal'] - $item['discount_amount'];
        $item['tax_amount'] = round($taxableAmount * ($item['tax_rate'] / 100), 2);
    }

    protected function updateCartItemTotals($cartKey): void
    {
        if (!isset($this->cart[$cartKey])) return;

        $item = &$this->cart[$cartKey];
        $item['subtotal'] = round($item['base_price'] * $item['quantity'], 2);

        if ($item['discount_type'] && $item['discount_type_value'] > 0) {
            if ($item['discount_type'] === 'percentage') {
                $item['discount_amount'] = round($item['subtotal'] * ($item['discount_type_value'] / 100), 2);
            } else {
                $item['discount_amount'] = round($item['discount_type_value'] * $item['quantity'], 2);
            }
            $item['discount_amount'] = min($item['discount_amount'], $item['subtotal']);
        }

        $taxableAmount = $item['subtotal'] - $item['discount_amount'];
        $item['tax_amount'] = round($taxableAmount * ($item['tax_rate'] / 100), 2);
    }

    public function toggleSpecialPrice($cartKey): void
    {
        if (!isset($this->cart[$cartKey])) return;
        $item = &$this->cart[$cartKey];
        if ($item['is_service'] || $item['is_combo']) return;
        if (!$item['special_price']) {
            $this->dispatch('notify', message: 'Este producto no tiene precio especial', type: 'warning');
            return;
        }

        $item['using_special_price'] = !$item['using_special_price'];

        if ($item['using_special_price']) {
            $specialPrice = $item['special_price'];
            $taxRate = $item['tax_rate'];
            if ($item['price_includes_tax']) {
                $priceWithTax = $specialPrice;
                $basePrice = $taxRate > 0 ? $specialPrice / (1 + ($taxRate / 100)) : $specialPrice;
            } else {
                $basePrice = $specialPrice;
                $priceWithTax = $taxRate > 0 ? $specialPrice * (1 + ($taxRate / 100)) : $specialPrice;
            }
            $item['price'] = round($priceWithTax, 2);
            $item['base_price'] = round($basePrice, 2);
        } else {
            $item['price'] = $item['original_price'];
            $item['base_price'] = $item['original_base_price'];
        }

        $this->updateCartItemTotals($cartKey);
    }

    public function updateQuantity($cartKey, $quantity): void
    {
        $quantity = (float) str_replace(',', '.', (string) $quantity);
        if ($quantity <= 0) {
            $this->removeFromCart($cartKey);
            return;
        }

        if (isset($this->cart[$cartKey])) {
            $this->cart[$cartKey]['quantity'] = round($quantity, 3);
            $this->updateCartItemTotals($cartKey);
        }
    }

    public function incrementQuantity($cartKey): void
    {
        if (!isset($this->cart[$cartKey])) return;

        $item = $this->cart[$cartKey];

        // Validate stock for products that manage inventory
        if (!empty($item['product_id']) && empty($item['service_id']) && empty($item['combo_id'])) {
            $product = Product::find($item['product_id']);
            if ($product && $product->manages_inventory) {
                $availableStock = (float) $product->current_stock;
                if ((float) $item['quantity'] >= $availableStock) {
                    $this->dispatch('notify', message: "Sin stock disponible para \"{$item['name']}\" (disponible: " . rtrim(rtrim(number_format($availableStock, 3), '0'), '.') . ')', type: 'error');
                    return;
                }
            }
        }

        $this->cart[$cartKey]['quantity']++;
        $this->updateCartItemTotals($cartKey);
    }

    public function decrementQuantity($cartKey): void
    {
        if (isset($this->cart[$cartKey])) {
            if ($this->cart[$cartKey]['quantity'] > 1) {
                $this->cart[$cartKey]['quantity'] = round($this->cart[$cartKey]['quantity'] - 1, 3);
                $this->updateCartItemTotals($cartKey);
            } else {
                $this->removeFromCart($cartKey);
            }
        }
    }

    public function removeFromCart($cartKey): void
    {
        unset($this->cart[$cartKey]);
    }

    public function clearCart(): void
    {
        $this->cart = [];
        $this->globalDiscountApplied = false;
        $this->globalDiscountAmount = 0;
        $this->globalDiscountValue = '';
        $this->globalDiscountReason = '';
        $this->showPriceOverride = false;
    }

    // Item discount
    public function openDiscountModal($cartKey): void
    {
        if (!isset($this->cart[$cartKey])) return;
        $this->discountCartKey = $cartKey;
        $item = $this->cart[$cartKey];
        $this->discountType = $item['discount_type'] ?? 'percentage';
        $this->discountValue = $item['discount_type_value'] > 0 ? (string) $item['discount_type_value'] : '';
        $this->discountReason = $item['discount_reason'] ?? '';
        $this->showDiscountModal = true;
    }

    public function applyDiscount(): void
    {
        if (!$this->discountCartKey || !isset($this->cart[$this->discountCartKey])) {
            $this->closeDiscountModal();
            return;
        }

        $value = (float) str_replace(',', '.', $this->discountValue);
        if ($value < 0) {
            $this->dispatch('notify', message: 'El descuento no puede ser negativo', type: 'error');
            return;
        }

        $item = &$this->cart[$this->discountCartKey];

        if ($this->discountType === 'percentage' && $value > 100) {
            $this->dispatch('notify', message: 'El porcentaje no puede ser mayor a 100%', type: 'error');
            return;
        }

        if ($value > 0) {
            if ($this->discountType === 'percentage') {
                $discountAmount = round($item['subtotal'] * ($value / 100), 2);
            } else {
                $discountAmount = round($value * $item['quantity'], 2);
            }
            if ($discountAmount > $item['subtotal']) {
                $this->dispatch('notify', message: 'El descuento no puede ser mayor al subtotal', type: 'error');
                return;
            }
            $item['discount_type'] = $this->discountType;
            $item['discount_type_value'] = $value;
            $item['discount_amount'] = $discountAmount;
            $item['discount_reason'] = trim($this->discountReason) ?: null;
        } else {
            $item['discount_type'] = null;
            $item['discount_type_value'] = 0;
            $item['discount_amount'] = 0;
            $item['discount_reason'] = null;
        }

        $taxableAmount = $item['subtotal'] - $item['discount_amount'];
        $item['tax_amount'] = round($taxableAmount * ($item['tax_rate'] / 100), 2);

        $this->closeDiscountModal();
        $this->dispatch('notify', message: $value > 0 ? 'Descuento aplicado' : 'Descuento eliminado');
    }

    public function removeDiscount($cartKey): void
    {
        if (!isset($this->cart[$cartKey])) return;
        $item = &$this->cart[$cartKey];
        $item['discount_type'] = null;
        $item['discount_type_value'] = 0;
        $item['discount_amount'] = 0;
        $item['discount_reason'] = null;
        $item['tax_amount'] = round($item['subtotal'] * ($item['tax_rate'] / 100), 2);
        $this->dispatch('notify', message: 'Descuento eliminado');
    }

    public function closeDiscountModal(): void
    {
        $this->showDiscountModal = false;
        $this->discountCartKey = null;
        $this->discountType = 'percentage';
        $this->discountValue = '';
        $this->discountReason = '';
    }

    // Global discount
    public function openGlobalDiscountModal(): void
    {
        if (empty($this->cart)) {
            $this->dispatch('notify', message: 'Agrega productos al carrito', type: 'warning');
            return;
        }
        if (!$this->globalDiscountApplied) {
            $this->globalDiscountType = 'percentage';
            $this->globalDiscountValue = '';
            $this->globalDiscountReason = '';
        }
        $this->showGlobalDiscountModal = true;
    }

    public function applyGlobalDiscount(): void
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

        $baseTotal = collect($this->cart)->sum('subtotal')
            - collect($this->cart)->sum('discount_amount')
            + collect($this->cart)->sum('tax_amount');

        if ($value > 0) {
            $discountAmount = $this->globalDiscountType === 'percentage'
                ? round($baseTotal * ($value / 100), 2)
                : round($value, 2);

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

        $this->showGlobalDiscountModal = false;
        $this->dispatch('notify', message: $value > 0 ? 'Descuento global aplicado' : 'Descuento global eliminado');
    }

    public function removeGlobalDiscount(): void
    {
        $this->globalDiscountApplied = false;
        $this->globalDiscountAmount = 0;
        $this->globalDiscountValue = '';
        $this->globalDiscountReason = '';
        $this->dispatch('notify', message: 'Descuento global eliminado');
    }

    public function closeGlobalDiscountModal(): void
    {
        $this->showGlobalDiscountModal = false;
    }

    // Price override
    public function togglePriceOverride(): void
    {
        if (empty($this->cart)) {
            $this->dispatch('notify', message: 'Agrega productos al carrito', type: 'warning');
            return;
        }
        $this->showPriceOverride = !$this->showPriceOverride;
    }

    public function overrideItemPrice($cartKey, $newPrice): void
    {
        if (!isset($this->cart[$cartKey])) return;

        $newPrice = (float) str_replace(',', '.', $newPrice);
        if ($newPrice <= 0) {
            $this->dispatch('notify', message: 'El precio debe ser mayor a 0', type: 'error');
            return;
        }

        $item = &$this->cart[$cartKey];
        $taxRate = $item['tax_rate'];

        if ($item['price_includes_tax']) {
            $priceWithTax = $newPrice;
            $basePrice = $taxRate > 0 ? $newPrice / (1 + ($taxRate / 100)) : $newPrice;
        } else {
            $basePrice = $newPrice;
            $priceWithTax = $taxRate > 0 ? $newPrice * (1 + ($taxRate / 100)) : $newPrice;
        }

        $item['price_overridden'] = true;
        $item['price'] = round($priceWithTax, 2);
        $item['base_price'] = round($basePrice, 2);
        $this->updateCartItemTotals($cartKey);
        $this->dispatch('notify', message: 'Precio actualizado', type: 'success');
    }

    public function resetItemPrice($cartKey): void
    {
        if (!isset($this->cart[$cartKey])) return;
        $item = &$this->cart[$cartKey];
        $item['price'] = $item['original_price'];
        $item['base_price'] = $item['original_base_price'];
        $item['price_overridden'] = false;
        $item['using_special_price'] = false;
        $this->updateCartItemTotals($cartKey);
        $this->dispatch('notify', message: 'Precio restaurado');
    }

    // Computed totals
    public function getSubtotalProperty()
    {
        return collect($this->cart)->sum('subtotal');
    }

    public function getDiscountTotalProperty()
    {
        return collect($this->cart)->sum('discount_amount');
    }

    public function getTaxTotalProperty()
    {
        return collect($this->cart)->sum('tax_amount');
    }

    public function getTotalProperty()
    {
        $total = $this->getSubtotalProperty() - $this->getDiscountTotalProperty() + $this->getTaxTotalProperty();
        return $total - $this->globalDiscountAmount;
    }

    public function getItemCountProperty()
    {
        return collect($this->cart)->sum('quantity');
    }

    // Save quote
    public function openSaveModal(): void
    {
        if (empty($this->cart)) {
            $this->dispatch('notify', message: 'Agrega productos al carrito', type: 'warning');
            return;
        }
        if (!$this->branchId) {
            $this->dispatch('notify', message: 'Selecciona una sucursal', type: 'error');
            return;
        }
        if (!$this->customerId) {
            $this->dispatch('notify', message: 'Selecciona un cliente para la cotización', type: 'error');
            return;
        }

        $this->saveValidUntil = $this->saveValidUntil ?: now()->addDays(15)->format('Y-m-d');
        $this->saveNotes = '';
        $this->showSaveModal = true;
    }

    public function cancelSave(): void
    {
        $this->showSaveModal = false;
    }

    public function confirmSaveQuote(): void
    {
        if (empty($this->cart)) {
            $this->dispatch('notify', message: 'Agrega productos al carrito', type: 'warning');
            return;
        }
        if (!$this->customerId) {
            $this->dispatch('notify', message: 'Selecciona un cliente', type: 'error');
            return;
        }

        try {
            DB::beginTransaction();

            $quote = Quote::create([
                'branch_id' => $this->branchId,
                'customer_id' => $this->customerId,
                'user_id' => auth()->id(),
                'quote_number' => Quote::generateQuoteNumber(),
                'valid_until' => $this->saveValidUntil ?: null,
                'subtotal' => $this->getSubtotalProperty(),
                'tax_total' => $this->getTaxTotalProperty(),
                'discount' => $this->getDiscountTotalProperty() + $this->globalDiscountAmount,
                'total' => $this->getTotalProperty(),
                'global_discount_type' => $this->globalDiscountApplied ? $this->globalDiscountType : null,
                'global_discount_value' => $this->globalDiscountApplied ? (float) str_replace(',', '.', (string) $this->globalDiscountValue) : 0,
                'global_discount_amount' => $this->globalDiscountAmount,
                'global_discount_reason' => $this->globalDiscountApplied && trim($this->globalDiscountReason) ? trim($this->globalDiscountReason) : null,
                'status' => 'draft',
                'notes' => $this->saveNotes ?: null,
            ]);

            foreach ($this->cart as $item) {
                QuoteItem::create([
                    'quote_id' => $quote->id,
                    'product_id' => $item['product_id'],
                    'product_child_id' => $item['child_id'],
                    'service_id' => $item['service_id'] ?? null,
                    'combo_id' => $item['combo_id'] ?? null,
                    'product_name' => $item['name'],
                    'product_sku' => $item['sku'],
                    'unit_price' => $item['base_price'],
                    'quantity' => $item['quantity'],
                    'tax_rate' => $item['tax_rate'],
                    'tax_amount' => $item['tax_amount'],
                    'subtotal' => $item['subtotal'],
                    'discount_type' => $item['discount_type'] ?? null,
                    'discount_type_value' => $item['discount_type_value'] ?? 0,
                    'discount_amount' => $item['discount_amount'] ?? 0,
                    'discount_reason' => $item['discount_reason'] ?? null,
                    'total' => $item['subtotal'] - ($item['discount_amount'] ?? 0) + $item['tax_amount'],
                ]);
            }

            DB::commit();

            // Reserve inventory for product items (decrements stock so other quotes/sales reflect real availability)
            $quote->reserveInventory();

            ActivityLogService::logCreate(
                'quotes',
                $quote,
                "Cotización {$quote->quote_number} creada por $" . number_format($quote->total, 2)
            );

            // Show confirmation modal warning user it cannot be edited
            $this->savedQuoteId = $quote->id;
            $this->showSaveModal = false;
            $this->showSavedConfirmModal = true;

            // Reset form
            $this->cart = [];
            $this->globalDiscountApplied = false;
            $this->globalDiscountAmount = 0;
            $this->globalDiscountValue = '';
            $this->globalDiscountReason = '';
            $this->saveNotes = '';
            $this->saveValidUntil = now()->addDays(15)->format('Y-m-d');
            $this->loadDefaultCustomer();

            $this->dispatch('notify', message: 'Cotización ' . $quote->quote_number . ' guardada', type: 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('notify', message: 'Error al guardar la cotización: ' . $e->getMessage(), type: 'error');
        }
    }

    public function printSavedQuote(): void
    {
        if ($this->savedQuoteId) {
            $this->dispatch('print-quote', quoteId: $this->savedQuoteId);
        }
        $this->closeSavedConfirmModal();
    }

    public function closeSavedConfirmModal(): void
    {
        $this->showSavedConfirmModal = false;
        $this->savedQuoteId = null;
        $this->dispatch('focus-barcode-search');
    }

    public function render()
    {
        $customers = [];
        $customerSearchTrimmed = trim($this->customerSearch);
        if ($this->branchId && strlen($customerSearchTrimmed) >= 2) {
            $customers = Customer::where('is_active', true)
                ->forBranch($this->branchId)
                ->where(function ($q) use ($customerSearchTrimmed) {
                    $q->where('first_name', 'like', '%' . $customerSearchTrimmed . '%')
                      ->orWhere('last_name', 'like', '%' . $customerSearchTrimmed . '%')
                      ->orWhere('business_name', 'like', '%' . $customerSearchTrimmed . '%')
                      ->orWhere('document_number', 'like', '%' . $customerSearchTrimmed . '%')
                      ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$customerSearchTrimmed}%"]);
                })
                ->limit(10)
                ->get();
        }

        $categories = Category::where('is_active', true)
            ->withCount(['subcategories' => fn($q) => $q->where('is_active', true)])
            ->orderBy('name')
            ->get();

        $sellableItems = collect();

        if ($this->branchId) {
            // Quotes do NOT filter by stock — show ALL active products regardless of stock
            $productsQuery = Product::with(['category', 'brand', 'tax', 'unit', 'children' => fn($q) => $q->where('is_active', true)])
                ->where('is_active', true)
                ->forBranch($this->branchId);

            if ($this->selectedCategory) {
                $productsQuery->where('category_id', $this->selectedCategory);
            }

            if (strlen(trim($this->productSearch)) >= 2) {
                $search = trim($this->productSearch);
                $productsQuery->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                      ->orWhere('sku', 'like', '%' . $search . '%')
                      ->orWhereHas('children', function ($cq) use ($search) {
                          $cq->where('is_active', true)
                             ->where(function ($ccq) use ($search) {
                                 $ccq->where('name', 'like', '%' . $search . '%')
                                     ->orWhere('sku', 'like', '%' . $search . '%')
                                     ->orWhere('barcode', 'like', '%' . $search . '%');
                             });
                      });
                });
            }

            $products = $productsQuery->orderBy('name')->limit(50)->get();

            foreach ($products as $product) {
                if ($product->children->isEmpty()) {
                    $sellableItems->push([
                        'type' => 'product',
                        'id' => $product->id,
                        'child_id' => null,
                        'name' => $product->name,
                        'sku' => $product->sku,
                        'brand' => $product->brand?->name,
                        'price' => $product->price_includes_tax
                            ? $product->sale_price
                            : $product->getSalePriceWithTax(),
                        'stock' => (float) $product->current_stock,
                        'manages_inventory' => (bool) $product->manages_inventory,
                        'image' => $product->image,
                        'unit' => $product->unit?->abbreviation ?? 'UND',
                    ]);
                } else {
                    $sellableItems->push([
                        'type' => 'product',
                        'id' => $product->id,
                        'child_id' => null,
                        'name' => $product->name,
                        'sku' => $product->sku,
                        'brand' => $product->brand?->name,
                        'price' => $product->price_includes_tax
                            ? $product->sale_price
                            : $product->getSalePriceWithTax(),
                        'stock' => (float) $product->current_stock,
                        'manages_inventory' => (bool) $product->manages_inventory,
                        'image' => $product->image,
                        'unit' => $product->unit?->abbreviation ?? 'UND',
                        'has_variants' => true,
                        'variant_count' => $product->children->count(),
                    ]);

                    foreach ($product->children as $child) {
                        $sellableItems->push([
                            'type' => 'child',
                            'id' => $product->id,
                            'child_id' => $child->id,
                            'name' => $child->name,
                            'parent_name' => $product->name,
                            'sku' => $child->sku,
                            'brand' => $product->brand?->name,
                            'price' => $child->price_includes_tax
                                ? $child->sale_price
                                : $child->getSalePriceWithTax(),
                            'stock' => (float) $product->current_stock,
                            'manages_inventory' => (bool) $product->manages_inventory,
                            'image' => $child->image ?? $product->image,
                            'unit' => $product->unit?->abbreviation ?? 'UND',
                        ]);
                    }
                }
            }

            // Services
            $servicesQuery = Service::with(['category', 'tax'])
                ->where('is_active', true)
                ->forBranch($this->branchId);

            if ($this->selectedCategory) {
                $servicesQuery->where('category_id', $this->selectedCategory);
            }

            if (strlen(trim($this->productSearch)) >= 2) {
                $search = trim($this->productSearch);
                $servicesQuery->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                      ->orWhere('sku', 'like', '%' . $search . '%')
                      ->orWhere('description', 'like', '%' . $search . '%');
                });
            }

            $services = $servicesQuery->orderBy('name')->limit(20)->get();

            foreach ($services as $service) {
                $sellableItems->push([
                    'type' => 'service',
                    'id' => $service->id,
                    'child_id' => null,
                    'name' => $service->name,
                    'sku' => $service->sku,
                    'brand' => null,
                    'price' => $service->price_includes_tax
                        ? $service->sale_price
                        : $service->getSalePriceWithTax(),
                    'stock' => null,
                    'image' => $service->image,
                    'unit' => 'SRV',
                ]);
            }

            // Combos
            $combosQuery = Combo::with(['items.product'])->forBranch($this->branchId);

            if (strlen(trim($this->productSearch)) >= 2) {
                $search = trim($this->productSearch);
                $combosQuery->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                      ->orWhere('description', 'like', '%' . $search . '%');
                });
            }

            $combos = $combosQuery->orderBy('name')->limit(20)->get();

            foreach ($combos as $combo) {
                $sellableItems->push([
                    'type' => 'combo',
                    'id' => $combo->id,
                    'child_id' => null,
                    'name' => $combo->name,
                    'sku' => 'COMBO-' . $combo->id,
                    'brand' => null,
                    'price' => (float) $combo->combo_price,
                    'original_price' => (float) $combo->original_price,
                    'savings_pct' => method_exists($combo, 'getSavingsPercentage') ? $combo->getSavingsPercentage() : 0,
                    'stock' => null,
                    'image' => $combo->image,
                    'unit' => 'COMBO',
                    'items_count' => method_exists($combo, 'getTotalProductsCount') ? $combo->getTotalProductsCount() : 0,
                ]);
            }
        }

        $taxDocuments = TaxDocument::where('is_active', true)->orderBy('description')->get();
        $departments = Department::where('is_active', true)->orderBy('name')->get();

        return view('livewire.quote-create', [
            'customers' => $customers,
            'categories' => $categories,
            'sellableItems' => $sellableItems,
            'taxDocuments' => $taxDocuments,
            'departments' => $departments,
            'subtotal' => $this->getSubtotalProperty(),
            'taxTotal' => $this->getTaxTotalProperty(),
            'total' => $this->getTotalProperty(),
            'itemCount' => $this->getItemCountProperty(),
        ]);
    }
}

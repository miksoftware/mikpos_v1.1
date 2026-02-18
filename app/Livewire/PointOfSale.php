<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Product;
use App\Models\ProductChild;
use App\Models\ProductBarcode;
use App\Models\Service;
use App\Models\Customer;
use App\Models\Category;
use App\Models\CashRegister;
use App\Models\CashReconciliation;
use App\Models\PaymentMethod;
use App\Models\BillingSetting;
use App\Models\TaxDocument;
use App\Models\Department;
use App\Models\Municipality;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\InventoryMovement;
use App\Services\ActivityLogService;
use App\Services\FactusService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;

#[Layout('layouts.pos')]
class PointOfSale extends Component
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
    
    // Category filter
    public $selectedCategory = null;
    
    // Price type: public, wholesale, retail
    public $priceType = 'public';
    
    // Cart items
    public $cart = [];
    
    // Cash register & reconciliation
    public $cashRegister = null;
    public $openReconciliation = null;
    public $needsReconciliation = false;
    
    // Payment modal - Multiple payment methods
    public $showPaymentModal = false;
    public $payments = [];
    public $paymentNotes = '';
    
    // Hold/Park functionality
    public $heldOrders = [];
    public $showHeldOrdersModal = false;
    public $holdNote = '';
    
    // Cash opening modal
    public $showOpenCashModal = false;
    public $openingAmount = '0';
    public $openingNotes = '';
    
    // Branch
    public $branchId = null;

    // Variant selection modal
    public $showVariantModal = false;
    public $variantProduct = null;
    public $variantOptions = [];

    // Discount modal
    public $showDiscountModal = false;
    public $discountCartKey = null;
    public $discountType = 'percentage'; // 'percentage' or 'fixed'
    public $discountValue = '';
    public $discountReason = '';

    // Weight quantity modal
    public $showWeightModal = false;
    public $weightModalProduct = null;
    public $weightModalQuantity = '';

    // Print confirmation modal
    public $showPrintConfirmModal = false;
    public $pendingPrintSaleId = null;

    // Credit sale
    public bool $isCredit = false;

    public function mount()
    {
        $user = auth()->user();
        $this->branchId = $user->branch_id;
        
        // Load held orders from session
        $this->heldOrders = session()->get('pos_held_orders_' . $user->id, []);
        
        // Check if user has assigned cash register
        $this->cashRegister = CashRegister::where('user_id', $user->id)
            ->where('is_active', true)
            ->first();
        
        if ($this->cashRegister) {
            $this->branchId = $this->cashRegister->branch_id;
            $this->openReconciliation = CashReconciliation::getOpenReconciliation($this->cashRegister->id);
            $this->needsReconciliation = !$this->openReconciliation;
        } else {
            $this->needsReconciliation = true;
        }
        
        // Load default customer
        $this->loadDefaultCustomer();
    }

    public function loadDefaultCustomer()
    {
        $defaultCustomer = Customer::where('is_default', true)
            ->forBranch($this->branchId)
            ->first();
        
        if ($defaultCustomer) {
            $this->customerId = $defaultCustomer->id;
            $this->selectedCustomer = $defaultCustomer;
        }
    }

    public function updatedCustomerSearch()
    {
        // Triggered when customer search changes
    }

    public function selectCustomer($customerId)
    {
        $this->selectedCustomer = Customer::find($customerId);
        $this->customerId = $customerId;
        $this->customerSearch = '';
    }

    public function clearCustomer()
    {
        $this->loadDefaultCustomer();
        $this->customerSearch = '';
    }

    public function openCreateCustomer()
    {
        $this->showCreateCustomer = true;
        $this->resetCreateCustomerForm();
    }

    public function closeCreateCustomer()
    {
        $this->showCreateCustomer = false;
        $this->resetCreateCustomerForm();
    }

    public function resetCreateCustomerForm()
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

    public function updatedNewCustomerDepartmentId()
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

    public function saveNewCustomer()
    {
        // Basic validation
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

        // Check if document already exists
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

            // Select the new customer
            $this->selectCustomer($customer->id);
            $this->showCreateCustomer = false;
            $this->resetCreateCustomerForm();
            
            // Close the modal via Alpine.js
            $this->dispatch('close-customer-modal');
            $this->dispatch('notify', message: 'Cliente creado correctamente', type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('notify', message: 'Error al crear cliente: ' . $e->getMessage(), type: 'error');
        }
    }

    public function updatedBarcodeSearch()
    {
        $this->searchByBarcode();
    }

    public function searchByBarcode()
    {
        $barcode = trim($this->barcodeSearch);
        
        if (strlen($barcode) < 3) {
            return;
        }

        // Search in product_barcodes table
        $barcodeRecord = ProductBarcode::where('barcode', $barcode)->first();

        if ($barcodeRecord) {
            // Found a barcode - check if it's for a product child
            if ($barcodeRecord->product_child_id) {
                $child = ProductChild::where('id', $barcodeRecord->product_child_id)
                    ->where('is_active', true)
                    ->whereHas('product', function ($q) {
                        $q->where('is_active', true)
                          ->forBranch($this->branchId);
                    })
                    ->first();
                
                if ($child) {
                    $this->addToCart($child->product_id, $child->id);
                    $this->barcodeSearch = '';
                    $this->dispatch('focus-barcode-search');
                    return;
                }
            }
            
            // Check if it's for a parent product
            if ($barcodeRecord->product_id) {
                $product = Product::where('id', $barcodeRecord->product_id)
                    ->where('is_active', true)
                    ->forBranch($this->branchId)
                    ->with(['children' => function ($q) {
                        $q->where('is_active', true);
                    }, 'brand'])
                    ->first();
                
                if ($product) {
                    // Check if product has active variants
                    if ($product->children->count() > 0) {
                        // Open variant selection modal
                        $this->openVariantModal($product);
                        $this->barcodeSearch = '';
                    } else {
                        // No variants, add directly
                        $this->addToCart($product->id);
                        $this->barcodeSearch = '';
                        $this->dispatch('focus-barcode-search');
                    }
                    return;
                }
            }
        }

        // Fallback: search in legacy barcode fields (for backwards compatibility)
        $child = ProductChild::where('barcode', $barcode)
            ->where('is_active', true)
            ->whereHas('product', function ($q) {
                $q->where('is_active', true)
                  ->forBranch($this->branchId);
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
            ->with(['children' => function ($q) {
                $q->where('is_active', true);
            }, 'brand'])
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

        // If barcode looks complete (8+ digits) but not found, show notification
        if (strlen($barcode) >= 8 && preg_match('/^\d+$/', $barcode)) {
            $this->dispatch('notify', message: 'Producto no encontrado: ' . $barcode, type: 'warning');
            $this->barcodeSearch = '';
            $this->dispatch('focus-barcode-search');
        }
    }

    public function openVariantModal($product)
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

    public function selectVariant($childId = null)
    {
        if ($this->variantProduct) {
            $this->addToCart($this->variantProduct['id'], $childId);
        }
        $this->closeVariantModal();
    }

    public function closeVariantModal()
    {
        $this->showVariantModal = false;
        $this->variantProduct = null;
        $this->variantOptions = [];
        $this->dispatch('focus-barcode-search');
    }

    public function selectCategory($categoryId)
    {
        $this->selectedCategory = $categoryId === $this->selectedCategory ? null : $categoryId;
    }

    public function addToCart($productId, $childId = null)
    {
        $product = Product::with(['tax', 'unit', 'children' => function ($q) {
            $q->where('is_active', true);
        }])->find($productId);
        
        if (!$product) return;
        
        // Check if product has stock
        if ($product->current_stock <= 0) {
            $this->dispatch('notify', message: 'Producto sin stock disponible', type: 'error');
            return;
        }
        
        // Check if weight-based product - show quantity modal instead of adding directly
        if ($this->isWeightBasedProduct($product)) {
            $this->openWeightModal($productId, $childId);
            return;
        }
        
        // Get child if specified (don't auto-select first child anymore)
        $child = $childId ? ProductChild::find($childId) : null;
        
        // Get price based on price type
        $price = $this->getPrice($product, $child);
        
        // Cart key: use 'parent' suffix when selling parent directly, child_id otherwise
        // This ensures parent and first child have different cart keys
        $cartKey = $productId . '-' . ($childId ?? 'parent');
        
        // Check stock availability
        $currentQtyInCart = isset($this->cart[$cartKey]) ? $this->cart[$cartKey]['quantity'] : 0;
        if ($currentQtyInCart >= $product->current_stock) {
            $this->dispatch('notify', message: 'Stock insuficiente. Disponible: ' . number_format($product->current_stock, 3), type: 'warning');
            return;
        }
        
        if (isset($this->cart[$cartKey])) {
            $this->cart[$cartKey]['quantity']++;
            $this->updateCartItemTotals($cartKey);
        } else {
            // Determine if price includes tax
            $priceIncludesTax = $child ? $child->price_includes_tax : $product->price_includes_tax;
            $taxRate = $product->tax?->value ?? 0;
            
            // Calculate base price (without tax) and price with tax
            if ($priceIncludesTax) {
                // Price already includes tax, calculate base price
                $priceWithTax = $price;
                $basePrice = $taxRate > 0 ? $price / (1 + ($taxRate / 100)) : $price;
            } else {
                // Price doesn't include tax
                $basePrice = $price;
                $priceWithTax = $taxRate > 0 ? $price * (1 + ($taxRate / 100)) : $price;
            }
            
            // Determine display name and image
            $displayName = $child ? $child->name : $product->name;
            $displayImage = $child ? ($child->image ?? $product->image) : $product->image;
            
            // Get special price if available
            $specialPrice = $child ? $child->special_price : $product->special_price;
            $hasSpecialPrice = $specialPrice && $specialPrice > 0;
            
            $this->cart[$cartKey] = [
                'product_id' => $productId,
                'child_id' => $childId,
                'service_id' => null,
                'is_service' => false,
                'name' => $displayName,
                'sku' => $child ? $child->sku : $product->sku,
                'price' => round($priceWithTax, 2), // Price shown to customer (with tax)
                'base_price' => round($basePrice, 2), // Price without tax for calculations
                'original_price' => round($priceWithTax, 2), // Store original price
                'original_base_price' => round($basePrice, 2), // Store original base price
                'special_price' => $hasSpecialPrice ? round((float) $specialPrice, 2) : null,
                'using_special_price' => false,
                'quantity' => 1,
                'subtotal' => round($basePrice, 2), // Subtotal is base price * quantity
                'tax_id' => $product->tax_id,
                'tax_rate' => $taxRate,
                'tax_amount' => round($priceWithTax - $basePrice, 2), // Tax for 1 unit
                'price_includes_tax' => $priceIncludesTax,
                'image' => $displayImage,
                'max_stock' => (float) $product->current_stock,
                // Discount fields
                'discount_type' => null,
                'discount_type_value' => 0,
                'discount_amount' => 0,
                'discount_reason' => null,
            ];
        }
        
        // Clear search and refocus
        $this->productSearch = '';
        $this->dispatch('focus-product-search');
    }

    public function toggleSpecialPrice($cartKey)
    {
        if (!isset($this->cart[$cartKey])) return;
        
        $item = &$this->cart[$cartKey];
        
        // Services don't have special price
        if ($item['is_service']) return;
        
        // Check if special price is available
        if (!$item['special_price']) {
            $this->dispatch('notify', message: 'Este producto no tiene precio especial', type: 'warning');
            return;
        }
        
        $item['using_special_price'] = !$item['using_special_price'];
        
        if ($item['using_special_price']) {
            // Switch to special price
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
            // Switch back to original price
            $item['price'] = $item['original_price'];
            $item['base_price'] = $item['original_base_price'];
        }
        
        $this->updateCartItemTotals($cartKey);
    }

    /**
     * Apply special price to all cart items that have it available.
     * Triggered by F3 shortcut.
     */
    public function applyAllSpecialPrices()
    {
        $appliedCount = 0;
        
        foreach ($this->cart as $cartKey => &$item) {
            // Skip services and items without special price
            if ($item['is_service'] || !$item['special_price']) {
                continue;
            }
            
            // Skip if already using special price
            if ($item['using_special_price']) {
                continue;
            }
            
            // Apply special price
            $item['using_special_price'] = true;
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
            $this->updateCartItemTotals($cartKey);
            $appliedCount++;
        }
        
        if ($appliedCount > 0) {
            $this->dispatch('notify', message: "Precio especial aplicado a {$appliedCount} producto(s)", type: 'success');
        } else {
            $this->dispatch('notify', message: 'No hay productos con precio especial disponible', type: 'info');
        }
    }

    public function addServiceToCart($serviceId)
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
                'is_service' => true,
                'name' => $service->name,
                'sku' => $service->sku,
                'price' => round($priceWithTax, 2),
                'base_price' => round($basePrice, 2),
                'quantity' => 1,
                'subtotal' => round($basePrice, 2),
                'tax_id' => $service->tax_id,
                'tax_rate' => $taxRate,
                'tax_amount' => round($priceWithTax - $basePrice, 2),
                'price_includes_tax' => $priceIncludesTax,
                'image' => $service->image,
                'max_stock' => PHP_INT_MAX, // Services have no stock limit
                // Discount fields
                'discount_type' => null,
                'discount_type_value' => 0,
                'discount_amount' => 0,
                'discount_reason' => null,
            ];
        }
        
        // Clear search and refocus
        $this->productSearch = '';
        $this->dispatch('focus-product-search');
    }

    /**
     * Check if a product uses a weight-based unit.
     * 
     * @param Product $product The product to check
     * @return bool True if the product has a weight-based unit, false otherwise
     */
    protected function isWeightBasedProduct($product): bool
    {
        // Load unit relationship if not already loaded
        if (!$product->relationLoaded('unit')) {
            $product->load('unit');
        }
        
        // Return true only if product has a unit and it's a weight unit
        return $product->unit && $product->unit->is_weight_unit;
    }

    /**
     * Open the weight quantity modal for a product.
     * 
     * @param int $productId The product ID
     * @param int|null $childId The product child ID (if variant)
     */
    public function openWeightModal($productId, $childId = null): void
    {
        // Load product with required relationships
        $product = Product::with(['tax', 'unit', 'children' => function ($q) {
            $q->where('is_active', true);
        }])->find($productId);
        
        if (!$product) {
            return;
        }
        
        // Get child if specified
        $child = $childId ? ProductChild::find($childId) : null;
        
        // Get price based on price type
        $price = $this->getPrice($product, $child);
        
        // Determine if price includes tax
        $priceIncludesTax = $child ? $child->price_includes_tax : $product->price_includes_tax;
        $taxRate = $product->tax?->value ?? 0;
        
        // Calculate price with tax for display
        if ($priceIncludesTax) {
            $priceWithTax = $price;
        } else {
            $priceWithTax = $taxRate > 0 ? $price * (1 + ($taxRate / 100)) : $price;
        }
        
        // Determine display name and image
        $displayName = $child ? $child->name : $product->name;
        $displayImage = $child ? ($child->image ?? $product->image) : $product->image;
        
        // Populate modal data
        $this->weightModalProduct = [
            'product_id' => $productId,
            'child_id' => $childId,
            'name' => $displayName,
            'price' => round($priceWithTax, 2),
            'unit' => $product->unit?->abbreviation ?? 'UND',
            'stock' => (float) $product->current_stock,
            'image' => $displayImage,
        ];
        
        // Clear quantity and show modal
        $this->weightModalQuantity = '';
        $this->showWeightModal = true;
    }

    /**
     * Confirm the weight quantity and add to cart.
     * Validates quantity and adds the product with the specified weight.
     */
    public function confirmWeightModal(): void
    {
        // Ensure modal is open and has product data
        if (!$this->showWeightModal || !$this->weightModalProduct) {
            return;
        }

        // Parse quantity - handle both comma and period as decimal separator
        $quantityStr = trim($this->weightModalQuantity);
        
        // Check for empty or invalid input
        if ($quantityStr === '' || $quantityStr === null) {
            $this->dispatch('notify', message: 'Ingresa una cantidad válida', type: 'error');
            return;
        }

        // Replace comma with period for decimal parsing
        $quantityStr = str_replace(',', '.', $quantityStr);
        
        // Validate it's a valid numeric value
        if (!is_numeric($quantityStr)) {
            $this->dispatch('notify', message: 'Ingresa una cantidad válida', type: 'error');
            return;
        }

        $quantity = (float) $quantityStr;

        // Validate quantity > 0
        if ($quantity <= 0) {
            $this->dispatch('notify', message: 'La cantidad debe ser mayor a cero', type: 'error');
            return;
        }

        // Round quantity to 3 decimal places
        $quantity = round($quantity, 3);

        // Validate quantity <= stock
        $stock = $this->weightModalProduct['stock'];
        if ($quantity > $stock) {
            $formattedStock = rtrim(rtrim(number_format($stock, 3), '0'), '.');
            $this->dispatch('notify', message: "Stock insuficiente. Disponible: {$formattedStock}", type: 'error');
            return;
        }

        // Get product data from modal
        $productId = $this->weightModalProduct['product_id'];
        $childId = $this->weightModalProduct['child_id'];

        // Add product to cart with specified quantity
        $this->addProductToCartWithQuantity($productId, $childId, $quantity);

        // Close modal and reset state
        $this->closeWeightModal();
    }

    /**
     * Close the weight modal without adding to cart.
     */
    public function closeWeightModal(): void
    {
        $this->showWeightModal = false;
        $this->weightModalProduct = null;
        $this->weightModalQuantity = '';
        $this->dispatch('focus-barcode-search');
    }

    /**
     * Confirm print and open receipt window.
     */
    public function confirmPrint(): void
    {
        if ($this->pendingPrintSaleId) {
            $this->dispatch('print-receipt', saleId: $this->pendingPrintSaleId);
        }
        $this->closePrintConfirmModal();
    }

    /**
     * Close print confirmation modal without printing.
     */
    public function closePrintConfirmModal(): void
    {
        $this->showPrintConfirmModal = false;
        $this->pendingPrintSaleId = null;
        $this->dispatch('focus-barcode-search');
    }

    /**
     * Add a product to cart with a specific quantity.
     * Used by weight modal and can be used for other quantity-specific additions.
     * 
     * @param int $productId The product ID
     * @param int|null $childId The product child ID (if variant)
     * @param float $quantity The quantity to add
     */
    protected function addProductToCartWithQuantity($productId, $childId, $quantity): void
    {
        $product = Product::with(['tax', 'children' => function ($q) {
            $q->where('is_active', true);
        }])->find($productId);
        
        if (!$product) return;
        
        // Get child if specified
        $child = $childId ? ProductChild::find($childId) : null;
        
        // Get price based on price type
        $price = $this->getPrice($product, $child);
        
        // Cart key: use 'parent' suffix when selling parent directly, child_id otherwise
        $cartKey = $productId . '-' . ($childId ?? 'parent');
        
        // Check stock availability (including existing cart quantity)
        $currentQtyInCart = isset($this->cart[$cartKey]) ? $this->cart[$cartKey]['quantity'] : 0;
        $totalQty = $currentQtyInCart + $quantity;
        
        if ($totalQty > $product->current_stock) {
            $available = $product->current_stock - $currentQtyInCart;
            $formattedAvailable = rtrim(rtrim(number_format($available, 3), '0'), '.');
            $this->dispatch('notify', message: "Stock insuficiente. Disponible: {$formattedAvailable}", type: 'warning');
            return;
        }
        
        if (isset($this->cart[$cartKey])) {
            // Update existing cart item quantity
            $this->cart[$cartKey]['quantity'] = round($this->cart[$cartKey]['quantity'] + $quantity, 3);
            $this->updateCartItemTotals($cartKey);
        } else {
            // Determine if price includes tax
            $priceIncludesTax = $child ? $child->price_includes_tax : $product->price_includes_tax;
            $taxRate = $product->tax?->value ?? 0;
            
            // Calculate base price (without tax) and price with tax
            if ($priceIncludesTax) {
                $priceWithTax = $price;
                $basePrice = $taxRate > 0 ? $price / (1 + ($taxRate / 100)) : $price;
            } else {
                $basePrice = $price;
                $priceWithTax = $taxRate > 0 ? $price * (1 + ($taxRate / 100)) : $price;
            }
            
            // Determine display name and image
            $displayName = $child ? $child->name : $product->name;
            $displayImage = $child ? ($child->image ?? $product->image) : $product->image;
            
            // Get special price if available
            $specialPrice = $child ? $child->special_price : $product->special_price;
            $hasSpecialPrice = $specialPrice && $specialPrice > 0;
            
            $this->cart[$cartKey] = [
                'product_id' => $productId,
                'child_id' => $childId,
                'service_id' => null,
                'is_service' => false,
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
                'max_stock' => (float) $product->current_stock,
                // Discount fields
                'discount_type' => null,
                'discount_type_value' => 0,
                'discount_amount' => 0,
                'discount_reason' => null,
            ];
        }
        
        // Clear search and refocus
        $this->productSearch = '';
        $this->dispatch('focus-product-search');
    }

    protected function updateCartItemTotals($cartKey)
    {
        if (!isset($this->cart[$cartKey])) return;
        
        $item = &$this->cart[$cartKey];
        $item['subtotal'] = round($item['base_price'] * $item['quantity'], 2);
        
        // Recalculate discount if exists
        if ($item['discount_type'] && $item['discount_type_value'] > 0) {
            if ($item['discount_type'] === 'percentage') {
                $item['discount_amount'] = round($item['subtotal'] * ($item['discount_type_value'] / 100), 2);
            } else {
                // Fixed discount per unit * quantity
                $item['discount_amount'] = round($item['discount_type_value'] * $item['quantity'], 2);
            }
            // Ensure discount doesn't exceed subtotal
            $item['discount_amount'] = min($item['discount_amount'], $item['subtotal']);
        }
        
        // Tax is calculated on subtotal after discount
        $taxableAmount = $item['subtotal'] - $item['discount_amount'];
        $item['tax_amount'] = round($taxableAmount * ($item['tax_rate'] / 100), 2);
    }

    public function getPrice($product, $child = null)
    {
        // For now, use sale_price. In future, implement wholesale/retail prices
        $basePrice = $child?->sale_price ?? $product->sale_price;
        
        return (float) $basePrice;
    }

    public function updateQuantity($cartKey, $quantity)
    {
        $quantity = (float) $quantity;
        
        if ($quantity <= 0) {
            $this->removeFromCart($cartKey);
            return;
        }
        
        if (isset($this->cart[$cartKey])) {
            // Check stock limit for products (not services)
            if (!($this->cart[$cartKey]['is_service'] ?? false)) {
                $maxStock = $this->cart[$cartKey]['max_stock'] ?? PHP_INT_MAX;
                if ($quantity > $maxStock) {
                    $this->dispatch('notify', message: 'Stock insuficiente. Disponible: ' . number_format($maxStock, 3), type: 'warning');
                    $quantity = $maxStock;
                }
            }
            
            $this->cart[$cartKey]['quantity'] = round($quantity, 3);
            $this->updateCartItemTotals($cartKey);
        }
    }

    public function incrementQuantity($cartKey)
    {
        if (isset($this->cart[$cartKey])) {
            // Check stock limit
            $maxStock = $this->cart[$cartKey]['max_stock'] ?? PHP_INT_MAX;
            if ($this->cart[$cartKey]['quantity'] >= $maxStock) {
                $this->dispatch('notify', message: 'Stock insuficiente. Disponible: ' . number_format($maxStock, 3), type: 'warning');
                return;
            }
            $this->cart[$cartKey]['quantity']++;
            $this->updateCartItemTotals($cartKey);
        }
    }

    public function decrementQuantity($cartKey)
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

    public function removeFromCart($cartKey)
    {
        unset($this->cart[$cartKey]);
    }

    public function clearCart()
    {
        $this->cart = [];
    }

    // Discount methods
    public function openDiscountModal($cartKey)
    {
        if (!isset($this->cart[$cartKey])) return;
        
        $this->discountCartKey = $cartKey;
        $item = $this->cart[$cartKey];
        
        // Load existing discount if any
        $this->discountType = $item['discount_type'] ?? 'percentage';
        $this->discountValue = $item['discount_type_value'] > 0 ? (string) $item['discount_type_value'] : '';
        $this->discountReason = $item['discount_reason'] ?? '';
        
        $this->showDiscountModal = true;
    }

    public function applyDiscount()
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
        
        // Validate percentage doesn't exceed 100%
        if ($this->discountType === 'percentage' && $value > 100) {
            $this->dispatch('notify', message: 'El porcentaje no puede ser mayor a 100%', type: 'error');
            return;
        }

        // Calculate discount amount
        if ($value > 0) {
            if ($this->discountType === 'percentage') {
                $discountAmount = round($item['subtotal'] * ($value / 100), 2);
            } else {
                // Fixed discount per unit * quantity
                $discountAmount = round($value * $item['quantity'], 2);
            }
            
            // Ensure discount doesn't exceed subtotal
            if ($discountAmount > $item['subtotal']) {
                $this->dispatch('notify', message: 'El descuento no puede ser mayor al subtotal', type: 'error');
                return;
            }

            $item['discount_type'] = $this->discountType;
            $item['discount_type_value'] = $value;
            $item['discount_amount'] = $discountAmount;
            $item['discount_reason'] = trim($this->discountReason) ?: null;
        } else {
            // Remove discount
            $item['discount_type'] = null;
            $item['discount_type_value'] = 0;
            $item['discount_amount'] = 0;
            $item['discount_reason'] = null;
        }

        // Recalculate tax after discount
        $taxableAmount = $item['subtotal'] - $item['discount_amount'];
        $item['tax_amount'] = round($taxableAmount * ($item['tax_rate'] / 100), 2);

        $this->closeDiscountModal();
        $this->dispatch('notify', message: $value > 0 ? 'Descuento aplicado' : 'Descuento eliminado');
    }

    public function removeDiscount($cartKey)
    {
        if (!isset($this->cart[$cartKey])) return;
        
        $item = &$this->cart[$cartKey];
        $item['discount_type'] = null;
        $item['discount_type_value'] = 0;
        $item['discount_amount'] = 0;
        $item['discount_reason'] = null;
        
        // Recalculate tax
        $item['tax_amount'] = round($item['subtotal'] * ($item['tax_rate'] / 100), 2);
        
        $this->dispatch('notify', message: 'Descuento eliminado');
    }

    public function closeDiscountModal()
    {
        $this->showDiscountModal = false;
        $this->discountCartKey = null;
        $this->discountType = 'percentage';
        $this->discountValue = '';
        $this->discountReason = '';
    }

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
        return $this->getSubtotalProperty() - $this->getDiscountTotalProperty() + $this->getTaxTotalProperty();
    }

    public function getItemCountProperty()
    {
        return collect($this->cart)->sum('quantity');
    }

    public function getTotalReceivedProperty()
    {
        return collect($this->payments)->sum(function ($payment) {
            return (float) ($payment['amount'] ?? 0);
        });
    }

    public function getPendingAmountProperty()
    {
        if ($this->isCredit) {
            // In credit mode, no payment is required (but partial upfront is allowed)
            return 0;
        }
        $pending = $this->getTotalProperty() - $this->getTotalReceivedProperty();
        return max(0, $pending);
    }

    public function getChangeProperty()
    {
        $change = $this->getTotalReceivedProperty() - $this->getTotalProperty();
        return max(0, $change);
    }

    public function getCustomerCreditInfoProperty(): array
    {
        if (!$this->selectedCustomer || !$this->selectedCustomer->has_credit) {
            return ['available' => false, 'limit' => 0, 'used' => 0, 'remaining' => 0];
        }

        $limit = (float) $this->selectedCustomer->credit_limit;

        // Sum unpaid credit sales for this customer
        $used = (float) Sale::where('customer_id', $this->selectedCustomer->id)
            ->where('payment_type', 'credit')
            ->whereIn('payment_status', ['pending', 'partial'])
            ->selectRaw('COALESCE(SUM(credit_amount - paid_amount), 0) as total_used')
            ->value('total_used');

        return [
            'available' => true,
            'limit' => $limit,
            'used' => $used,
            'remaining' => max(0, $limit - $used),
        ];
    }

    public function openPayment()
    {
        if (empty($this->cart)) {
            $this->dispatch('notify', message: 'Agrega productos al carrito', type: 'warning');
            return;
        }
        
        if ($this->needsReconciliation) {
            $this->dispatch('notify', message: 'Debes abrir caja antes de vender', type: 'error');
            return;
        }
        
        // Get default payment method (Efectivo/Cash)
        $defaultPaymentMethod = \App\Models\PaymentMethod::where('is_active', true)
            ->where(function ($q) {
                $q->where('name', 'like', '%efectivo%')
                  ->orWhere('dian_code', '10'); // DIAN code 10 = Efectivo
            })
            ->first();
        
        // Initialize with one payment method with the total amount
        $this->payments = [
            ['method_id' => $defaultPaymentMethod?->id ?? '', 'amount' => $this->getTotalProperty()]
        ];
        $this->isCredit = false;
        $this->showPaymentModal = true;
    }

    public function addPaymentMethod()
    {
        $this->payments[] = ['method_id' => '', 'amount' => 0];
    }

    public function removePaymentMethod($index)
    {
        if (count($this->payments) > 1) {
            unset($this->payments[$index]);
            $this->payments = array_values($this->payments);
        }
    }

    public function processPayment()
    {
        $total = $this->getTotalProperty();
        $totalReceived = $this->getTotalReceivedProperty();

        if ($this->isCredit) {
            // Credit sale validation
            if (!$this->selectedCustomer || !$this->selectedCustomer->has_credit) {
                $this->dispatch('notify', message: 'El cliente no tiene crédito habilitado', type: 'error');
                return;
            }

            $creditInfo = $this->getCustomerCreditInfoProperty();
            $creditAmount = $total - $totalReceived; // Amount that goes on credit

            if ($creditAmount > 0 && $creditInfo['limit'] > 0 && $creditAmount > $creditInfo['remaining']) {
                $this->dispatch('notify', message: 'El monto excede el crédito disponible ($' . number_format($creditInfo['remaining'], 2) . ')', type: 'error');
                return;
            }

            // Validate payment methods only if there's an upfront payment
            if ($totalReceived > 0) {
                foreach ($this->payments as $payment) {
                    if ((float) ($payment['amount'] ?? 0) > 0 && empty($payment['method_id'])) {
                        $this->dispatch('notify', message: 'Selecciona un método de pago', type: 'error');
                        return;
                    }
                }
            }
        } else {
            // Cash sale validation
            foreach ($this->payments as $payment) {
                if (empty($payment['method_id'])) {
                    $this->dispatch('notify', message: 'Selecciona un método de pago', type: 'error');
                    return;
                }
            }

            if ($this->getPendingAmountProperty() > 0) {
                $this->dispatch('notify', message: 'El monto recibido es insuficiente', type: 'error');
                return;
            }
        }
        
        try {
            DB::beginTransaction();

            // Determine payment type and status
            $paymentType = $this->isCredit ? 'credit' : 'cash';
            $paidAmount = $this->isCredit ? $totalReceived : $total;
            $creditAmount = $this->isCredit ? ($total - $totalReceived) : 0;
            $paymentStatus = 'paid';
            if ($this->isCredit) {
                $paymentStatus = $paidAmount >= $total ? 'paid' : ($paidAmount > 0 ? 'partial' : 'pending');
            }
            
            // Create sale
            $sale = Sale::create([
                'branch_id' => $this->branchId,
                'cash_reconciliation_id' => $this->openReconciliation->id,
                'customer_id' => $this->customerId,
                'user_id' => auth()->id(),
                'invoice_number' => Sale::generateInvoiceNumber($this->branchId),
                'subtotal' => $this->getSubtotalProperty(),
                'tax_total' => $this->getTaxTotalProperty(),
                'discount' => $this->getDiscountTotalProperty(),
                'total' => $total,
                'status' => 'completed',
                'payment_type' => $paymentType,
                'payment_status' => $paymentStatus,
                'credit_amount' => $this->isCredit ? $total : 0,
                'paid_amount' => $paidAmount,
                'notes' => $this->paymentNotes ?: null,
            ]);
            
            // Create sale items and update stock
            foreach ($this->cart as $item) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'product_child_id' => $item['child_id'],
                    'service_id' => $item['service_id'] ?? null,
                    'product_name' => $item['name'],
                    'product_sku' => $item['sku'],
                    'unit_price' => $item['base_price'], // Price without tax
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
                
                // Update product stock (only for products, not services)
                if ($item['product_id']) {
                    $product = Product::find($item['product_id']);
                    if ($product) {
                        // Create inventory movement for sale
                        InventoryMovement::createMovement(
                            'sale',
                            $product,
                            'out',
                            $item['quantity'],
                            (float) $item['base_price'],
                            "Venta #{$sale->invoice_number}",
                            $sale,
                            $this->branchId
                        );

                        // Update stock
                        $product->decrement('current_stock', $item['quantity']);
                    }
                }
            }
            
            // Create sale payments (only for payments with amount > 0 and valid method)
            // IMPORTANT: For cash sales, we must ensure the sum of payments equals the sale total,
            // not the amount received (which may include change/vuelto).
            $validPayments = collect($this->payments)
                ->filter(fn($p) => (float) ($p['amount'] ?? 0) > 0 && !empty($p['method_id']));
            
            if (!$this->isCredit && $validPayments->count() > 0) {
                $saleTotal = $total;
                $paymentsSoFar = 0;
                $paymentsList = $validPayments->values()->all();
                
                foreach ($paymentsList as $i => $payment) {
                    $isLast = ($i === count($paymentsList) - 1);
                    
                    if ($isLast) {
                        // Last payment gets the remaining amount to ensure exact total
                        $paymentAmount = round($saleTotal - $paymentsSoFar, 2);
                    } else {
                        // Non-last payments: use entered amount but cap at remaining
                        $paymentAmount = min((float) $payment['amount'], round($saleTotal - $paymentsSoFar, 2));
                    }
                    
                    if ($paymentAmount > 0) {
                        SalePayment::create([
                            'sale_id' => $sale->id,
                            'payment_method_id' => $payment['method_id'],
                            'amount' => $paymentAmount,
                        ]);
                        $paymentsSoFar += $paymentAmount;
                    }
                }
            } else {
                // Credit sales: store payments as-is (partial upfront payments)
                foreach ($validPayments as $payment) {
                    SalePayment::create([
                        'sale_id' => $sale->id,
                        'payment_method_id' => $payment['method_id'],
                        'amount' => $payment['amount'],
                    ]);
                }
            }
            
            DB::commit();
            
            $creditLabel = $this->isCredit ? ' (Crédito)' : '';
            ActivityLogService::logCreate(
                'sales',
                $sale,
                "Venta {$sale->invoice_number}{$creditLabel} por $" . number_format($sale->total, 2)
            );
            
            // Process electronic invoice if enabled
            $electronicInvoiceResult = $this->processElectronicInvoice($sale);
            
            $message = 'Venta procesada: ' . $sale->invoice_number;
            if ($electronicInvoiceResult['sent']) {
                if ($electronicInvoiceResult['success']) {
                    $message .= ' | Factura DIAN: ' . ($sale->fresh()->dian_number ?? 'Validada');
                } else {
                    $message .= ' | Error DIAN: ' . $electronicInvoiceResult['error'];
                }
            }
            
            $this->dispatch('notify', 
                message: $message, 
                type: $electronicInvoiceResult['sent'] && !$electronicInvoiceResult['success'] ? 'warning' : 'success'
            );
            
            // Store sale ID and show print confirmation modal
            $this->pendingPrintSaleId = $sale->id;
            $this->showPrintConfirmModal = true;
            
            // Reset cart and payment modal
            $this->cart = [];
            $this->showPaymentModal = false;
            $this->payments = [];
            $this->paymentNotes = '';
            $this->isCredit = false;
            $this->loadDefaultCustomer();
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('notify', message: 'Error al procesar la venta: ' . $e->getMessage(), type: 'error');
        }
    }

    /**
     * Process electronic invoice if enabled.
     */
    protected function processElectronicInvoice(Sale $sale): array
    {
        $result = [
            'sent' => false,
            'success' => false,
            'error' => null,
        ];

        try {
            $factusService = new FactusService();
            
            if (!$factusService->isEnabled()) {
                return $result;
            }

            $result['sent'] = true;
            
            $response = $factusService->createInvoice($sale);
            $result['success'] = true;
            
            Log::info('Electronic invoice created successfully', [
                'sale_id' => $sale->id,
                'dian_number' => $sale->fresh()->dian_number,
            ]);
            
        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
            Log::error('Electronic invoice failed', [
                'sale_id' => $sale->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $result;
    }

    public function cancelPayment()
    {
        $this->showPaymentModal = false;
        $this->payments = [];
        $this->isCredit = false;
    }

    public function updatedIsCredit($value)
    {
        if ($value) {
            // When switching to credit, set payment amount to 0 (no upfront by default)
            $this->payments = [['method_id' => $this->payments[0]['method_id'] ?? '', 'amount' => 0]];
        } else {
            // When switching back to cash, set payment amount to total
            $this->payments = [['method_id' => $this->payments[0]['method_id'] ?? '', 'amount' => $this->getTotalProperty()]];
        }
    }

    // Cash Opening Methods
    public function openCashModal()
    {
        if (!$this->cashRegister) {
            $this->dispatch('notify', message: 'No tienes una caja asignada', type: 'error');
            return;
        }
        
        $this->openingAmount = '0';
        $this->openingNotes = '';
        $this->showOpenCashModal = true;
    }

    public function storeOpenCash()
    {
        $this->validate([
            'openingAmount' => 'required|numeric|min:0',
        ], [
            'openingAmount.required' => 'El monto inicial es obligatorio',
            'openingAmount.min' => 'El monto no puede ser negativo',
        ]);

        if (!$this->cashRegister) {
            $this->dispatch('notify', message: 'No tienes una caja asignada', type: 'error');
            return;
        }

        // Check if already has open reconciliation
        if (CashReconciliation::hasOpenReconciliation($this->cashRegister->id)) {
            $this->dispatch('notify', message: 'Esta caja ya tiene un arqueo abierto', type: 'error');
            $this->showOpenCashModal = false;
            return;
        }

        $reconciliation = CashReconciliation::create([
            'branch_id' => $this->cashRegister->branch_id,
            'cash_register_id' => $this->cashRegister->id,
            'opened_by' => auth()->id(),
            'opening_amount' => $this->openingAmount,
            'opening_notes' => $this->openingNotes ?: null,
            'opened_at' => now(),
            'status' => 'open',
        ]);

        ActivityLogService::logCreate(
            'cash_reconciliations',
            $reconciliation,
            "Caja '{$this->cashRegister->name}' abierta desde POS con monto inicial: $" . number_format($this->openingAmount, 2)
        );

        $this->openReconciliation = $reconciliation;
        $this->needsReconciliation = false;
        $this->showOpenCashModal = false;
        
        $this->dispatch('notify', message: 'Caja abierta correctamente', type: 'success');
    }

    public function cancelOpenCash()
    {
        $this->showOpenCashModal = false;
        $this->openingAmount = '0';
        $this->openingNotes = '';
    }

    // Hold/Park Order Methods
    public function holdOrder()
    {
        if (empty($this->cart)) {
            $this->dispatch('notify', message: 'No hay productos en el carrito', type: 'warning');
            return;
        }
        
        $heldOrder = [
            'cart' => $this->cart,
            'customer_id' => $this->customerId,
            'customer_name' => $this->selectedCustomer ? $this->selectedCustomer->full_name : 'Cliente General',
            'total' => $this->getTotalProperty(),
            'item_count' => $this->getItemCountProperty(),
            'created_at' => now()->format('H:i'),
            'note' => $this->holdNote,
        ];
        
        $this->heldOrders[] = $heldOrder;
        $this->saveHeldOrdersToSession();
        
        // Clear current cart
        $this->cart = [];
        $this->holdNote = '';
        $this->loadDefaultCustomer();
        
        $this->dispatch('notify', message: 'Orden guardada en espera', type: 'success');
    }

    public function showHeldOrders()
    {
        $this->showHeldOrdersModal = true;
    }

    public function restoreOrder($index)
    {
        if (!isset($this->heldOrders[$index])) {
            return;
        }
        
        // If current cart has items, ask to hold them first
        if (!empty($this->cart)) {
            $this->dispatch('notify', message: 'Limpia el carrito actual antes de restaurar', type: 'warning');
            return;
        }
        
        $order = $this->heldOrders[$index];
        
        // Restore cart
        $this->cart = $order['cart'];
        
        // Restore customer
        if ($order['customer_id']) {
            $customer = Customer::find($order['customer_id']);
            if ($customer) {
                $this->customerId = $customer->id;
                $this->selectedCustomer = $customer;
            }
        }
        
        // Remove from held orders
        unset($this->heldOrders[$index]);
        $this->heldOrders = array_values($this->heldOrders);
        $this->saveHeldOrdersToSession();
        
        $this->showHeldOrdersModal = false;
        $this->dispatch('notify', message: 'Orden restaurada', type: 'success');
    }

    public function deleteHeldOrder($index)
    {
        if (isset($this->heldOrders[$index])) {
            unset($this->heldOrders[$index]);
            $this->heldOrders = array_values($this->heldOrders);
            $this->saveHeldOrdersToSession();
            $this->dispatch('notify', message: 'Orden eliminada', type: 'success');
        }
    }

    protected function saveHeldOrdersToSession()
    {
        $user = auth()->user();
        session()->put('pos_held_orders_' . $user->id, $this->heldOrders);
    }

    public function render()
    {
        // Get customers for search
        $customers = [];
        if (strlen($this->customerSearch) >= 2) {
            $customers = Customer::where('is_active', true)
                ->forBranch($this->branchId)
                ->where(function ($q) {
                    $q->where('first_name', 'like', '%' . $this->customerSearch . '%')
                      ->orWhere('last_name', 'like', '%' . $this->customerSearch . '%')
                      ->orWhere('business_name', 'like', '%' . $this->customerSearch . '%')
                      ->orWhere('document_number', 'like', '%' . $this->customerSearch . '%');
                })
                ->limit(10)
                ->get();
        }
        
        // Get categories
        $categories = Category::where('is_active', true)
            ->withCount(['subcategories' => function ($q) {
                $q->where('is_active', true);
            }])
            ->orderBy('name')
            ->get();
        
        // Build combined list of sellable items (parents without children + all children)
        $sellableItems = collect();
        
        // Query for products with stock
        $productsQuery = Product::with(['category', 'brand', 'tax', 'unit', 'children' => function ($q) {
                $q->where('is_active', true);
            }])
            ->where('is_active', true)
            ->where('current_stock', '>', 0)
            ->forBranch($this->branchId);
        
        if ($this->selectedCategory) {
            $productsQuery->where('category_id', $this->selectedCategory);
        }
        
        if (strlen($this->productSearch) >= 2) {
            $search = $this->productSearch;
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
        
        // Build sellable items list
        foreach ($products as $product) {
            if ($product->children->isEmpty()) {
                // Product without children - add as sellable item
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
                    'image' => $product->image,
                    'unit' => $product->unit?->abbreviation ?? 'UND',
                ]);
            } else {
                // Product with children - add parent AND each child separately
                // Add parent (can be sold at parent price)
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
                    'image' => $product->image,
                    'unit' => $product->unit?->abbreviation ?? 'UND',
                    'has_variants' => true,
                    'variant_count' => $product->children->count(),
                ]);
                
                // Add each child
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
                        'stock' => (float) $product->current_stock, // Stock is at parent level
                        'image' => $child->image ?? $product->image,
                        'unit' => $product->unit?->abbreviation ?? 'UND',
                    ]);
                }
            }
        }
        
        // Add services to sellable items
        $servicesQuery = Service::with(['category', 'tax'])
            ->where('is_active', true)
            ->forBranch($this->branchId);
        
        if ($this->selectedCategory) {
            $servicesQuery->where('category_id', $this->selectedCategory);
        }
        
        if (strlen($this->productSearch) >= 2) {
            $search = $this->productSearch;
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
                'stock' => null, // Services have no stock
                'image' => $service->image,
                'unit' => 'SRV',
            ]);
        }
        
        // Get payment methods
        $paymentMethods = PaymentMethod::where('is_active', true)->get();
        
        // Get tax documents for customer creation
        $taxDocuments = TaxDocument::where('is_active', true)->orderBy('description')->get();
        
        // Get departments for customer creation
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        
        // Check if electronic invoicing is enabled
        $billingSettings = BillingSetting::getSettings();
        $isElectronicInvoicingEnabled = $billingSettings->is_enabled && $billingSettings->isConfigured();
        
        return view('livewire.point-of-sale', [
            'customers' => $customers,
            'categories' => $categories,
            'sellableItems' => $sellableItems,
            'paymentMethods' => $paymentMethods,
            'taxDocuments' => $taxDocuments,
            'departments' => $departments,
            'subtotal' => $this->getSubtotalProperty(),
            'taxTotal' => $this->getTaxTotalProperty(),
            'total' => $this->getTotalProperty(),
            'itemCount' => $this->getItemCountProperty(),
            'totalReceived' => $this->getTotalReceivedProperty(),
            'pendingAmount' => $this->getPendingAmountProperty(),
            'change' => $this->getChangeProperty(),
            'isElectronicInvoicingEnabled' => $isElectronicInvoicingEnabled,
            'creditInfo' => $this->getCustomerCreditInfoProperty(),
        ]);
    }
}

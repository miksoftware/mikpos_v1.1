<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Product;
use App\Models\ProductChild;
use App\Models\Service;
use App\Models\Customer;
use App\Models\Category;
use App\Models\CashRegister;
use App\Models\CashReconciliation;
use App\Models\PaymentMethod;
use App\Models\BillingSetting;
use App\Models\TaxDocument;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
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

        // First, search exact match in product children
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
            return;
        }

        // Then, search exact match in parent products
        $product = Product::where('barcode', $barcode)
            ->where('is_active', true)
            ->forBranch($this->branchId)
            ->first();
        
        if ($product) {
            $this->addToCart($product->id);
            $this->barcodeSearch = '';
            return;
        }

        // If barcode looks complete (8+ digits) but not found, show notification
        if (strlen($barcode) >= 8 && preg_match('/^\d+$/', $barcode)) {
            $this->dispatch('notify', message: 'Producto no encontrado: ' . $barcode, type: 'warning');
            $this->barcodeSearch = '';
        }
    }

    public function selectCategory($categoryId)
    {
        $this->selectedCategory = $categoryId === $this->selectedCategory ? null : $categoryId;
    }

    public function addToCart($productId, $childId = null)
    {
        $product = Product::with(['tax', 'children' => function ($q) {
            $q->where('is_active', true);
        }])->find($productId);
        
        if (!$product) return;
        
        // Check if product has stock
        if ($product->current_stock <= 0) {
            $this->dispatch('notify', message: 'Producto sin stock disponible', type: 'error');
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
            $this->dispatch('notify', message: 'Stock insuficiente. Disponible: ' . (int)$product->current_stock, type: 'warning');
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
            
            $this->cart[$cartKey] = [
                'product_id' => $productId,
                'child_id' => $childId,
                'service_id' => null,
                'is_service' => false,
                'name' => $displayName,
                'sku' => $child ? $child->sku : $product->sku,
                'price' => round($priceWithTax, 2), // Price shown to customer (with tax)
                'base_price' => round($basePrice, 2), // Price without tax for calculations
                'quantity' => 1,
                'subtotal' => round($basePrice, 2), // Subtotal is base price * quantity
                'tax_id' => $product->tax_id,
                'tax_rate' => $taxRate,
                'tax_amount' => round($priceWithTax - $basePrice, 2), // Tax for 1 unit
                'price_includes_tax' => $priceIncludesTax,
                'image' => $displayImage,
                'max_stock' => (int)$product->current_stock,
            ];
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
            ];
        }
    }

    protected function updateCartItemTotals($cartKey)
    {
        if (!isset($this->cart[$cartKey])) return;
        
        $item = &$this->cart[$cartKey];
        $item['subtotal'] = round($item['base_price'] * $item['quantity'], 2);
        $item['tax_amount'] = round($item['subtotal'] * ($item['tax_rate'] / 100), 2);
    }

    public function getPrice($product, $child = null)
    {
        // For now, use sale_price. In future, implement wholesale/retail prices
        $basePrice = $child?->sale_price ?? $product->sale_price;
        
        return (float) $basePrice;
    }

    public function updateQuantity($cartKey, $quantity)
    {
        if ($quantity <= 0) {
            $this->removeFromCart($cartKey);
            return;
        }
        
        if (isset($this->cart[$cartKey])) {
            $this->cart[$cartKey]['quantity'] = $quantity;
            $this->updateCartItemTotals($cartKey);
        }
    }

    public function incrementQuantity($cartKey)
    {
        if (isset($this->cart[$cartKey])) {
            // Check stock limit
            $maxStock = $this->cart[$cartKey]['max_stock'] ?? PHP_INT_MAX;
            if ($this->cart[$cartKey]['quantity'] >= $maxStock) {
                $this->dispatch('notify', message: 'Stock insuficiente. Disponible: ' . $maxStock, type: 'warning');
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
                $this->cart[$cartKey]['quantity']--;
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

    public function getSubtotalProperty()
    {
        return collect($this->cart)->sum('subtotal');
    }

    public function getTaxTotalProperty()
    {
        return collect($this->cart)->sum('tax_amount');
    }

    public function getTotalProperty()
    {
        return $this->getSubtotalProperty() + $this->getTaxTotalProperty();
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
        $pending = $this->getTotalProperty() - $this->getTotalReceivedProperty();
        return max(0, $pending);
    }

    public function getChangeProperty()
    {
        $change = $this->getTotalReceivedProperty() - $this->getTotalProperty();
        return max(0, $change);
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
        
        // Initialize with one payment method with the total amount
        $this->payments = [
            ['method_id' => '', 'amount' => $this->getTotalProperty()]
        ];
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
        // Validate all payment methods have a method selected
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
        
        try {
            DB::beginTransaction();
            
            // Create sale
            $sale = Sale::create([
                'branch_id' => $this->branchId,
                'cash_reconciliation_id' => $this->openReconciliation->id,
                'customer_id' => $this->customerId,
                'user_id' => auth()->id(),
                'invoice_number' => Sale::generateInvoiceNumber($this->branchId),
                'subtotal' => $this->getSubtotalProperty(),
                'tax_total' => $this->getTaxTotalProperty(),
                'discount' => 0,
                'total' => $this->getTotalProperty(),
                'status' => 'completed',
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
                    'total' => $item['subtotal'] + $item['tax_amount'],
                ]);
                
                // Update product stock (only for products, not services)
                if ($item['product_id']) {
                    $product = Product::find($item['product_id']);
                    if ($product) {
                        $product->decrement('current_stock', $item['quantity']);
                    }
                }
            }
            
            // Create sale payments
            foreach ($this->payments as $payment) {
                if ($payment['amount'] > 0) {
                    SalePayment::create([
                        'sale_id' => $sale->id,
                        'payment_method_id' => $payment['method_id'],
                        'amount' => $payment['amount'],
                    ]);
                }
            }
            
            DB::commit();
            
            ActivityLogService::logCreate(
                'sales',
                $sale,
                "Venta {$sale->invoice_number} por $" . number_format($sale->total, 2)
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
            
            // Open print receipt window
            $this->dispatch('print-receipt', saleId: $sale->id);
            
            // Reset
            $this->cart = [];
            $this->showPaymentModal = false;
            $this->payments = [];
            $this->paymentNotes = '';
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
                    'stock' => (int) $product->current_stock,
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
                    'stock' => (int) $product->current_stock,
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
                        'stock' => (int) $product->current_stock, // Stock is at parent level
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
        
        // Check if electronic invoicing is enabled
        $billingSettings = BillingSetting::getSettings();
        $isElectronicInvoicingEnabled = $billingSettings->is_enabled && $billingSettings->isConfigured();
        
        return view('livewire.point-of-sale', [
            'customers' => $customers,
            'categories' => $categories,
            'sellableItems' => $sellableItems,
            'paymentMethods' => $paymentMethods,
            'taxDocuments' => $taxDocuments,
            'subtotal' => $this->getSubtotalProperty(),
            'taxTotal' => $this->getTaxTotalProperty(),
            'total' => $this->getTotalProperty(),
            'itemCount' => $this->getItemCountProperty(),
            'totalReceived' => $this->getTotalReceivedProperty(),
            'pendingAmount' => $this->getPendingAmountProperty(),
            'change' => $this->getChangeProperty(),
            'isElectronicInvoicingEnabled' => $isElectronicInvoicingEnabled,
        ]);
    }
}

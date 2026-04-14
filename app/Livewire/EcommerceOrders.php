<?php

namespace App\Livewire;

use App\Mail\EcommerceItemsUnavailable;
use App\Mail\EcommerceOrderItemsModified;
use App\Mail\EcommerceOrderStatusChanged;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\EcommerceOrder;
use App\Models\Product;
use App\Models\InventoryMovement;
use App\Services\EcommerceCheckoutService;
use App\Services\FactusService;
use App\Services\ActivityLogService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

#[Layout('layouts.app')]
class EcommerceOrders extends Component
{
    use WithPagination;

    public string $search = '';
    public string $activeTab = 'pending';
    public string $dateFrom = '';
    public string $dateTo = '';

    // Detail modal
    public bool $showDetailModal = false;
    public ?Sale $selectedSale = null;
    public ?EcommerceOrder $selectedOrder = null;

    // Reject modal
    public bool $showRejectModal = false;
    public ?int $rejectSaleId = null;
    public string $rejectReason = '';

    // Item unavailability
    public array $unavailableItems = [];

    // Bulk selection
    public array $selectedOrders = [];
    public bool $selectAll = false;

    // Edit quantities modal
    public bool $showEditQuantitiesModal = false;
    public array $editableItems = [];
    public string $quantityChangeReason = '';

    // Report tab
    public string $reportPeriod = 'month';
    public string $reportDateFrom = '';
    public string $reportDateTo = '';
    public string $reportStatus = 'all';

    public function mount()
    {
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
        $this->applyReportPeriod();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatedActiveTab()
    {
        $this->resetPage();
        $this->selectedOrders = [];
        $this->selectAll = false;
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedOrders = $this->getCurrentPageOrderIds();
        } else {
            $this->selectedOrders = [];
        }
    }

    public function updatedReportPeriod()
    {
        $this->applyReportPeriod();
    }

    private function applyReportPeriod(): void
    {
        $today = now();
        switch ($this->reportPeriod) {
            case 'today':
                $this->reportDateFrom = $today->format('Y-m-d');
                $this->reportDateTo = $today->format('Y-m-d');
                break;
            case 'yesterday':
                $yesterday = $today->copy()->subDay();
                $this->reportDateFrom = $yesterday->format('Y-m-d');
                $this->reportDateTo = $yesterday->format('Y-m-d');
                break;
            case 'week':
                $this->reportDateFrom = $today->copy()->startOfWeek()->format('Y-m-d');
                $this->reportDateTo = $today->format('Y-m-d');
                break;
            case 'last_week':
                $this->reportDateFrom = $today->copy()->subWeek()->startOfWeek()->format('Y-m-d');
                $this->reportDateTo = $today->copy()->subWeek()->endOfWeek()->format('Y-m-d');
                break;
            case 'month':
                $this->reportDateFrom = $today->copy()->startOfMonth()->format('Y-m-d');
                $this->reportDateTo = $today->format('Y-m-d');
                break;
            case 'last_month':
                $this->reportDateFrom = $today->copy()->subMonth()->startOfMonth()->format('Y-m-d');
                $this->reportDateTo = $today->copy()->subMonth()->endOfMonth()->format('Y-m-d');
                break;
            case 'custom':
                // Keep current dates
                break;
        }
    }

    private function getCurrentPageOrderIds(): array
    {
        $query = $this->buildQuery();
        return $query->pluck('sales.id')->toArray();
    }

    public function viewOrder(int $saleId)
    {
        $this->selectedSale = Sale::with([
            'customer.taxDocument',
            'user',
            'branch',
            'items.product',
            'payments.paymentMethod',
            'ecommerceOrder.shippingDepartment',
            'ecommerceOrder.shippingMunicipality',
        ])->find($saleId);

        $this->selectedOrder = $this->selectedSale?->ecommerceOrder;

        // Initialize unavailable items tracking
        $this->unavailableItems = [];
        if ($this->selectedSale) {
            foreach ($this->selectedSale->items as $item) {
                $this->unavailableItems[$item->id] = [
                    'is_unavailable' => (bool) $item->is_unavailable,
                    'reason' => $item->unavailable_reason ?? '',
                ];
            }
        }

        $this->showDetailModal = true;
    }

    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->selectedSale = null;
        $this->selectedOrder = null;
        $this->unavailableItems = [];
    }

    public function toggleItemUnavailable(int $itemId)
    {
        if (isset($this->unavailableItems[$itemId])) {
            $this->unavailableItems[$itemId]['is_unavailable'] = !$this->unavailableItems[$itemId]['is_unavailable'];
            if (!$this->unavailableItems[$itemId]['is_unavailable']) {
                $this->unavailableItems[$itemId]['reason'] = '';
            }
        }
    }

    public function saveUnavailableItems()
    {
        if (!$this->selectedSale) return;

        try {
            DB::beginTransaction();

            foreach ($this->unavailableItems as $itemId => $data) {
                $saleItem = SaleItem::find($itemId);
                if (!$saleItem) continue;

                $wasUnavailable = (bool) $saleItem->is_unavailable;
                $nowUnavailable = (bool) $data['is_unavailable'];

                SaleItem::where('id', $itemId)->update([
                    'is_unavailable' => $nowUnavailable,
                    'unavailable_reason' => $nowUnavailable ? ($data['reason'] ?: 'Producto no disponible') : null,
                ]);

                // Return stock if newly marked unavailable
                if ($nowUnavailable && !$wasUnavailable && $saleItem->product_id) {
                    $product = Product::find($saleItem->product_id);
                    if ($product && $product->manages_inventory) {
                        $product->increment('current_stock', (float) $saleItem->quantity);
                    }
                }

                // Re-reserve stock if unmarked
                if (!$nowUnavailable && $wasUnavailable && $saleItem->product_id) {
                    $product = Product::find($saleItem->product_id);
                    if ($product && $product->manages_inventory) {
                        $product->decrement('current_stock', (float) $saleItem->quantity);
                    }
                }
            }

            // Recalculate sale totals based on available items
            $this->recalculateSaleTotals($this->selectedSale);

            DB::commit();

            // Refresh the sale data in the modal
            $this->selectedSale->refresh();
            $this->selectedSale->load('items.product');

            // Send email to customer about unavailable items
            try {
                $unavailableNames = $this->selectedSale->items
                    ->filter(fn($item) => $item->is_unavailable)
                    ->pluck('product_name')
                    ->toArray();

                if (!empty($unavailableNames)) {
                    $customer = $this->selectedSale->customer;
                    if ($customer && $customer->email) {
                        Mail::to($customer->email)->send(new EcommerceItemsUnavailable($this->selectedSale, $unavailableNames));
                    }
                }
            } catch (\Exception $e) {
                Log::error('Error enviando email de productos no disponibles: ' . $e->getMessage());
            }

            $this->dispatch('notify', message: 'Cambios guardados', type: 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('notify', message: 'Error: ' . $e->getMessage(), type: 'error');
        }
    }

    public function openEditQuantitiesModal()
    {
        if (!$this->selectedSale) return;

        $this->editableItems = [];
        foreach ($this->selectedSale->items as $item) {
            if ($item->is_unavailable) continue;

            $this->editableItems[$item->id] = [
                'product_name' => $item->product_name,
                'unit_price' => (float) $item->unit_price,
                'tax_rate' => (float) $item->tax_rate,
                'current_quantity' => (float) $item->quantity,
                'new_quantity' => (float) $item->quantity,
            ];
        }
        $this->quantityChangeReason = '';
        $this->showEditQuantitiesModal = true;
    }

    public function closeEditQuantitiesModal()
    {
        $this->showEditQuantitiesModal = false;
        $this->editableItems = [];
        $this->quantityChangeReason = '';
    }

    public function saveEditedQuantities()
    {
        $this->validate([
            'quantityChangeReason' => 'required|min:5',
        ], [
            'quantityChangeReason.required' => 'El motivo del cambio es obligatorio.',
            'quantityChangeReason.min' => 'El motivo debe tener al menos 5 caracteres.',
        ]);

        if (!$this->selectedSale) return;

        // Check that at least one quantity changed
        $hasChanges = false;
        foreach ($this->editableItems as $data) {
            if ((float) $data['new_quantity'] !== (float) $data['current_quantity']) {
                $hasChanges = true;
                break;
            }
        }

        if (!$hasChanges) {
            $this->dispatch('notify', message: 'No hay cambios en las cantidades', type: 'warning');
            return;
        }

        try {
            DB::beginTransaction();

            $changes = [];

            foreach ($this->editableItems as $itemId => $data) {
                $newQty = (float) $data['new_quantity'];
                $oldQty = (float) $data['current_quantity'];

                if ($newQty === $oldQty) continue;
                if ($newQty < 0) continue;

                $saleItem = SaleItem::find($itemId);
                if (!$saleItem) continue;

                // Track the change
                $changes[] = [
                    'product_name' => $saleItem->product_name,
                    'old_quantity' => $oldQty,
                    'new_quantity' => $newQty,
                    'reason' => $this->quantityChangeReason,
                ];

                // Adjust inventory
                if ($saleItem->product_id) {
                    $product = Product::find($saleItem->product_id);
                    if ($product && $product->manages_inventory) {
                        $diff = $oldQty - $newQty;
                        if ($diff > 0) {
                            // Quantity reduced → return stock
                            $product->increment('current_stock', $diff);
                        } elseif ($diff < 0) {
                            // Quantity increased → reserve more stock
                            $product->decrement('current_stock', abs($diff));
                        }
                    }
                }

                // Recalculate item totals
                $lineTotal = $saleItem->unit_price * $newQty;
                $taxAmount = 0;
                if ((float) $saleItem->tax_rate > 0) {
                    $priceWithoutTax = $saleItem->unit_price / (1 + (float) $saleItem->tax_rate / 100);
                    $taxAmount = ($saleItem->unit_price - $priceWithoutTax) * $newQty;
                }

                $saleItem->update([
                    'original_quantity' => $saleItem->original_quantity ?? $oldQty,
                    'quantity' => $newQty,
                    'quantity_change_reason' => $this->quantityChangeReason,
                    'tax_amount' => round($taxAmount, 2),
                    'subtotal' => round($lineTotal - $taxAmount, 2),
                    'total' => round($lineTotal, 2),
                ]);
            }

            // Recalculate sale totals
            $this->recalculateSaleTotals($this->selectedSale);

            ActivityLogService::log(
                'ecommerce_orders',
                'update',
                "Cantidades modificadas en pedido #{$this->selectedSale->invoice_number}: {$this->quantityChangeReason}",
                $this->selectedSale,
                ['items' => collect($changes)->map(fn($c) => "{$c['product_name']}: {$c['old_quantity']}")->implode(', ')],
                ['items' => collect($changes)->map(fn($c) => "{$c['product_name']}: {$c['new_quantity']}")->implode(', ')],
            );

            DB::commit();

            // Send email to customer
            try {
                $this->selectedSale->refresh();
                $customer = $this->selectedSale->customer;
                if ($customer && $customer->email && !empty($changes)) {
                    Mail::to($customer->email)->send(new EcommerceOrderItemsModified($this->selectedSale, $changes));
                }
            } catch (\Exception $e) {
                Log::error('Error enviando email de cambio de cantidades: ' . $e->getMessage());
            }

            // Refresh modal data
            $this->selectedSale->refresh();
            $this->selectedSale->load('items.product');

            $this->closeEditQuantitiesModal();
            $this->dispatch('notify', message: 'Cantidades actualizadas correctamente', type: 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('notify', message: 'Error: ' . $e->getMessage(), type: 'error');
        }
    }

    public function approveOrder(?int $saleId = null)
    {
        $saleId = $saleId ?? $this->selectedSale?->id;
        if (!$saleId) return;

        $sale = Sale::with(['items', 'ecommerceOrder'])->find($saleId);
        if (!$sale || $sale->status !== 'pending_approval') {
            $this->dispatch('notify', message: 'El pedido no puede ser aprobado', type: 'error');
            return;
        }

        try {
            DB::beginTransaction();

            // Save unavailable items if viewing detail
            $hasUnavailable = false;
            if ($this->selectedSale && $this->selectedSale->id === $saleId) {
                foreach ($this->unavailableItems as $itemId => $data) {
                    $saleItem = SaleItem::find($itemId);
                    if (!$saleItem) continue;

                    $wasUnavailable = (bool) $saleItem->is_unavailable;
                    $nowUnavailable = (bool) $data['is_unavailable'];

                    if ($nowUnavailable) {
                        $hasUnavailable = true;
                        $saleItem->update([
                            'is_unavailable' => true,
                            'unavailable_reason' => $data['reason'] ?: 'Producto no disponible',
                        ]);

                        // Return stock for unavailable items (only if it wasn't already returned)
                        if (!$wasUnavailable && $saleItem->product_id) {
                            $product = Product::find($saleItem->product_id);
                            if ($product && $product->manages_inventory) {
                                $product->increment('current_stock', (float) $saleItem->quantity);
                            }
                        }
                    } else {
                        $saleItem->update([
                            'is_unavailable' => false,
                            'unavailable_reason' => null,
                        ]);

                        // Re-reserve stock if it was previously unavailable
                        if ($wasUnavailable && $saleItem->product_id) {
                            $product = Product::find($saleItem->product_id);
                            if ($product && $product->manages_inventory) {
                                $product->decrement('current_stock', (float) $saleItem->quantity);
                            }
                        }
                    }
                }
            }

            // Check if there are any available items left
            $availableItemsCount = $sale->items()->where('is_unavailable', false)->count();
            if ($availableItemsCount === 0) {
                throw new \Exception('No hay productos disponibles para aprobar. Por favor rechace el pedido.');
            }

            // Update order status
            $orderStatus = $hasUnavailable ? 'partial' : 'approved';
            $sale->ecommerceOrder->update(['status' => $orderStatus]);

            // Approve the sale (mark as completed)
            $sale->update(['status' => 'completed', 'source' => 'ecommerce']);

            // Recalculate totals if items were marked unavailable
            // We always do this before electronic invoicing to ensure correct totals
            $this->recalculateSaleTotals($sale);

            // PROCESS ELECTRONIC INVOICE (DIAN/FACTUS)
            // If the customer has electronic invoicing data or it's enabled globally
            $factusService = new FactusService();
            if ($factusService->isEnabled()) {
                try {
                    $factusService->createInvoice($sale->fresh());
                } catch (\Exception $e) {
                    Log::error('Error facturando pedido e-commerce #' . $sale->invoice_number . ': ' . $e->getMessage());
                    // We don't roll back because the sale is already approved in our system
                    // But we notify the user
                    $this->dispatch('notify', message: 'Pedido aprobado pero falló factura electrónica: ' . $e->getMessage(), type: 'warning');
                }
            }

            ActivityLogService::log(
                'ecommerce_orders',
                'update',
                "Pedido e-commerce #{$sale->invoice_number} aprobado" . ($hasUnavailable ? ' (con productos faltantes)' : ''),
                $sale,
                ['status' => 'pending_approval'],
                ['status' => 'completed']
            );

            DB::commit();

            // Send status changed email to customer
            try {
                $sale->refresh();
                $customer = $sale->customer;
                if ($customer && $customer->email) {
                    Mail::to($customer->email)->send(new EcommerceOrderStatusChanged($sale, 'completed'));
                }
            } catch (\Exception $e) {
                Log::error('Error enviando email de aprobación: ' . $e->getMessage());
            }

            $this->closeDetailModal();
            $this->dispatch('notify', message: 'Pedido aprobado exitosamente', type: 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('notify', message: 'Error: ' . $e->getMessage(), type: 'error');
        }
    }

    private function recalculateSaleTotals(Sale $sale): void
    {
        $sale->refresh();
        $availableItems = $sale->items()->where('is_unavailable', false)->get();

        $subtotal = $availableItems->sum('subtotal');
        $taxTotal = $availableItems->sum('tax_amount');
        $total = $availableItems->sum('total');

        $sale->update([
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'total' => $total,
            'paid_amount' => $total,
        ]);

        // Update payment amount
        $payment = $sale->payments()->first();
        if ($payment) {
            $payment->update(['amount' => $total]);
        }
    }

    public function bulkApprove()
    {
        if (empty($this->selectedOrders)) {
            $this->dispatch('notify', message: 'Selecciona al menos un pedido', type: 'warning');
            return;
        }

        $approved = 0;
        $errors = 0;

        foreach ($this->selectedOrders as $saleId) {
            $sale = Sale::with('ecommerceOrder')->find($saleId);
            if (!$sale || $sale->status !== 'pending_approval') {
                $errors++;
                continue;
            }

            try {
                $service = new EcommerceCheckoutService();
                $service->approveOrder($sale);
                $sale->ecommerceOrder->update(['status' => 'approved']);
                $approved++;
            } catch (\Exception $e) {
                $errors++;
            }
        }

        $this->selectedOrders = [];
        $this->selectAll = false;

        $message = "Se aprobaron {$approved} pedido(s)";
        if ($errors > 0) {
            $message .= " ({$errors} con errores)";
        }
        $this->dispatch('notify', message: $message, type: $errors > 0 ? 'warning' : 'success');
    }

    public function openRejectModal(int $saleId)
    {
        $this->rejectSaleId = $saleId;
        $this->rejectReason = '';
        $this->showRejectModal = true;
    }

    public function closeRejectModal()
    {
        $this->showRejectModal = false;
        $this->rejectSaleId = null;
        $this->rejectReason = '';
    }

    public function rejectOrder()
    {
        $this->validate([
            'rejectReason' => 'required|min:10',
        ], [
            'rejectReason.required' => 'El motivo de rechazo es obligatorio.',
            'rejectReason.min' => 'El motivo debe tener al menos 10 caracteres.',
        ]);

        $sale = Sale::find($this->rejectSaleId);
        if (!$sale || $sale->status !== 'pending_approval') {
            $this->dispatch('notify', message: 'El pedido no puede ser rechazado', type: 'error');
            return;
        }

        try {
            $service = new EcommerceCheckoutService();
            $service->rejectOrder($sale, $this->rejectReason);
            $sale->ecommerceOrder->update(['status' => 'rejected']);

            $this->closeRejectModal();
            $this->closeDetailModal();
            $this->dispatch('notify', message: 'Pedido rechazado', type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('notify', message: 'Error: ' . $e->getMessage(), type: 'error');
        }
    }

    private function buildQuery()
    {
        $statusMap = [
            'pending' => 'pending_approval',
            'approved' => 'completed',
            'rejected' => 'rejected',
        ];

        $saleStatus = $statusMap[$this->activeTab] ?? 'pending_approval';

        $query = Sale::query()
            ->where('sales.source', 'ecommerce')
            ->where('sales.status', $saleStatus)
            ->with(['customer', 'payments.paymentMethod', 'ecommerceOrder'])
            ->when(trim($this->search), function ($q) {
                $search = trim($this->search);
                $q->where(function ($sq) use ($search) {
                    $sq->where('sales.invoice_number', 'like', "%{$search}%")
                       ->orWhereHas('customer', function ($cq) use ($search) {
                           $cq->where('first_name', 'like', "%{$search}%")
                              ->orWhere('last_name', 'like', "%{$search}%")
                              ->orWhere('business_name', 'like', "%{$search}%")
                              ->orWhere('document_number', 'like', "%{$search}%");
                       });
                });
            })
            ->when($this->dateFrom, fn($q) => $q->whereDate('sales.created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('sales.created_at', '<=', $this->dateTo))
            ->latest('sales.created_at');

        return $query;
    }

    public function getReportData(): array
    {
        $query = SaleItem::query()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->leftJoin('customers', 'sales.customer_id', '=', 'customers.id')
            ->where('sales.source', 'ecommerce')
            ->where('sale_items.is_unavailable', false);

        if ($this->reportStatus === 'pending') {
            $query->where('sales.status', 'pending_approval');
        } elseif ($this->reportStatus === 'approved') {
            $query->where('sales.status', 'completed');
        } elseif ($this->reportStatus === 'rejected') {
            $query->where('sales.status', 'rejected');
        } else {
            $query->whereIn('sales.status', ['pending_approval', 'completed', 'rejected']);
        }

        if ($this->reportDateFrom) {
            $query->whereDate('sales.created_at', '>=', $this->reportDateFrom);
        }
        if ($this->reportDateTo) {
            $query->whereDate('sales.created_at', '<=', $this->reportDateTo);
        }

        $items = $query->select(
            'sale_items.product_id',
            'sale_items.product_name',
            'sale_items.product_sku',
            'sales.customer_id',
            DB::raw("COALESCE(CONCAT(customers.first_name, ' ', customers.last_name), customers.business_name, 'Sin cliente') as customer_name"),
            DB::raw('SUM(sale_items.quantity) as total_quantity'),
        )
        ->groupBy(
            'sale_items.product_id',
            'sale_items.product_name',
            'sale_items.product_sku',
            'sales.customer_id',
            'customers.first_name',
            'customers.last_name',
            'customers.business_name',
        )
        ->get();

        // Build cross-tab: products (rows) x customers (columns)
        $products = [];
        $customers = [];

        foreach ($items as $item) {
            $productKey = $item->product_id ?? $item->product_name;
            $customerKey = $item->customer_id ?? 'sin_cliente';
            $customerName = trim($item->customer_name) ?: 'Sin cliente';

            if (!isset($products[$productKey])) {
                $products[$productKey] = [
                    'name' => $item->product_name,
                    'sku' => $item->product_sku,
                    'quantities' => [],
                    'total' => 0,
                ];
            }

            if (!isset($customers[$customerKey])) {
                $customers[$customerKey] = $customerName;
            }

            $qty = (float) $item->total_quantity;
            $products[$productKey]['quantities'][$customerKey] = ($products[$productKey]['quantities'][$customerKey] ?? 0) + $qty;
            $products[$productKey]['total'] += $qty;
        }

        // Sort products by name
        uasort($products, fn($a, $b) => strcmp($a['name'], $b['name']));

        // Sort customers by name
        asort($customers);

        // Customer totals
        $customerTotals = [];
        foreach ($customers as $key => $name) {
            $customerTotals[$key] = 0;
            foreach ($products as $product) {
                $customerTotals[$key] += $product['quantities'][$key] ?? 0;
            }
        }

        return [
            'products' => $products,
            'customers' => $customers,
            'customerTotals' => $customerTotals,
            'grandTotal' => collect($products)->sum('total'),
        ];
    }

    public function render()
    {
        $orders = !in_array($this->activeTab, ['products', 'report']) ? $this->buildQuery()->paginate(15) : null;

        $pendingCount = Sale::where('source', 'ecommerce')->where('status', 'pending_approval')->count();
        $approvedCount = Sale::where('source', 'ecommerce')->where('status', 'completed')->count();
        $rejectedCount = Sale::where('source', 'ecommerce')->where('status', 'rejected')->count();

        // Build aggregated products list for the "products" tab
        $aggregatedProducts = collect();
        if ($this->activeTab === 'products') {
            $aggregatedProducts = SaleItem::query()
                ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->where('sales.source', 'ecommerce')
                ->where('sales.status', 'pending_approval')
                ->where('sale_items.is_unavailable', false)
                ->select(
                    'sale_items.product_id',
                    'sale_items.product_child_id',
                    'sale_items.product_name',
                    'sale_items.product_sku',
                    'sale_items.unit_price',
                    DB::raw('SUM(sale_items.quantity) as total_quantity'),
                    DB::raw('COUNT(DISTINCT sale_items.sale_id) as order_count'),
                )
                ->groupBy(
                    'sale_items.product_id',
                    'sale_items.product_child_id',
                    'sale_items.product_name',
                    'sale_items.product_sku',
                    'sale_items.unit_price',
                )
                ->orderBy('sale_items.product_name')
                ->get()
                ->map(function ($item) {
                    $product = $item->product_id ? Product::find($item->product_id) : null;
                    $item->current_stock = $product ? (float) $product->current_stock : 0;
                    $item->manages_inventory = $product ? (bool) $product->manages_inventory : true;
                    $item->image = $product?->image;
                    return $item;
                });
        }

        // Build report data for the "report" tab
        $reportData = [];
        if ($this->activeTab === 'report') {
            $reportData = $this->getReportData();
        }

        return view('livewire.ecommerce-orders', [
            'orders' => $orders,
            'pendingCount' => $pendingCount,
            'approvedCount' => $approvedCount,
            'rejectedCount' => $rejectedCount,
            'aggregatedProducts' => $aggregatedProducts,
            'reportData' => $reportData,
        ]);
    }
}

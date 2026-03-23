<?php

namespace App\Livewire;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\EcommerceOrder;
use App\Models\Product;
use App\Models\InventoryMovement;
use App\Services\EcommerceCheckoutService;
use App\Services\ActivityLogService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

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

    public function mount()
    {
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
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
                    if ($data['is_unavailable']) {
                        $hasUnavailable = true;
                        SaleItem::where('id', $itemId)->update([
                            'is_unavailable' => true,
                            'unavailable_reason' => $data['reason'] ?: 'Producto no disponible',
                        ]);

                        // Return stock for unavailable items
                        $saleItem = SaleItem::find($itemId);
                        if ($saleItem && $saleItem->product_id) {
                            $product = Product::find($saleItem->product_id);
                            if ($product && $product->manages_inventory) {
                                $product->increment('current_stock', (float) $saleItem->quantity);
                            }
                        }
                    } else {
                        SaleItem::where('id', $itemId)->update([
                            'is_unavailable' => false,
                            'unavailable_reason' => null,
                        ]);
                    }
                }
            }

            // Update order status
            $orderStatus = $hasUnavailable ? 'partial' : 'approved';
            $sale->ecommerceOrder->update(['status' => $orderStatus]);

            // Approve the sale (mark as completed)
            $sale->update(['status' => 'completed', 'source' => 'ecommerce']);

            // Recalculate totals if items were marked unavailable
            if ($hasUnavailable) {
                $this->recalculateSaleTotals($sale);
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

    public function render()
    {
        $orders = $this->activeTab !== 'products' ? $this->buildQuery()->paginate(15) : null;

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

        return view('livewire.ecommerce-orders', [
            'orders' => $orders,
            'pendingCount' => $pendingCount,
            'approvedCount' => $approvedCount,
            'rejectedCount' => $rejectedCount,
            'aggregatedProducts' => $aggregatedProducts,
        ]);
    }
}

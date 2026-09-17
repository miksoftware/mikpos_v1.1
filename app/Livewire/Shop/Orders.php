<?php

namespace App\Livewire\Shop;

use App\Models\Sale;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use App\Livewire\Shop\Concerns\WithShopBranch;

#[Layout('layouts.shop')]
class Orders extends Component
{
    use WithPagination;
    use WithShopBranch;

    public ?int $selectedSaleId = null;

    public function viewOrder(int $saleId): void
    {
        $customer = Auth::guard('customer')->user();
        $syncService = app(\App\Services\CustomerSyncService::class);
        $customerIds = $syncService->getAllCustomerIds($customer);

        $sale = Sale::where('id', $saleId)
            ->whereIn('customer_id', $customerIds)
            ->where('source', 'ecommerce')
            ->first();

        if ($sale) {
            $this->selectedSaleId = $saleId;
        }
    }

    public function closeDetail(): void
    {
        $this->selectedSaleId = null;
    }

    public function render()
    {
        $customer = Auth::guard('customer')->user();
        $syncService = app(\App\Services\CustomerSyncService::class);
        $customerIds = $syncService->getAllCustomerIds($customer);

        $orders = Sale::whereIn('customer_id', $customerIds)
            ->where('source', 'ecommerce')
            ->with(['payments.paymentMethod', 'ecommerceOrder', 'items', 'branch'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $selectedSale = null;
        if ($this->selectedSaleId) {
            $selectedSale = Sale::where('id', $this->selectedSaleId)
                ->whereIn('customer_id', $customerIds)
                ->where('source', 'ecommerce')
                ->with([
                    'items',
                    'payments.paymentMethod',
                    'ecommerceOrder.shippingDepartment',
                    'ecommerceOrder.shippingMunicipality',
                    'branch',
                ])
                ->first();
        }

        return view('livewire.shop.orders', [
            'orders' => $orders,
            'selectedSale' => $selectedSale,
        ]);
    }
}

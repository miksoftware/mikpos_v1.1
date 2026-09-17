<?php

namespace App\Livewire\Shop;

use App\Models\Sale;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Livewire\Shop\Concerns\WithShopBranch;

#[Layout('layouts.shop')]
class OrderConfirmation extends Component
{
    use WithShopBranch;
    public Sale $sale;

    public function mount(Sale $sale): void
    {
        // Validate the sale belongs to the authenticated customer
        $customer = Auth::guard('customer')->user();
        $syncService = app(\App\Services\CustomerSyncService::class);
        $customerIds = $syncService->getAllCustomerIds($customer);

        if (!in_array($sale->customer_id, $customerIds) || $sale->source !== 'ecommerce') {
            abort(403);
        }

        $this->sale = $sale->load(['items', 'payments.paymentMethod', 'ecommerceOrder', 'branch']);
    }

    public function render()
    {
        return view('livewire.shop.order-confirmation');
    }
}

<?php

namespace App\Livewire\Shop;

use App\Models\Sale;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.shop')]
class OrderConfirmation extends Component
{
    public Sale $sale;

    public function mount(Sale $sale): void
    {
        // Validate the sale belongs to the authenticated customer
        $customer = Auth::guard('customer')->user();

        if ($sale->customer_id !== $customer->id || $sale->source !== 'ecommerce') {
            abort(403);
        }

        $this->sale = $sale->load(['items', 'payments.paymentMethod', 'ecommerceOrder']);
    }

    public function render()
    {
        return view('livewire.shop.order-confirmation');
    }
}

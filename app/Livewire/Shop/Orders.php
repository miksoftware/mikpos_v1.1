<?php

namespace App\Livewire\Shop;

use App\Models\Sale;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.shop')]
class Orders extends Component
{
    use WithPagination;

    public ?int $selectedSaleId = null;

    public function viewOrder(int $saleId): void
    {
        $customer = Auth::guard('customer')->user();
        $sale = Sale::where('id', $saleId)
            ->where('customer_id', $customer->id)
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

        $orders = Sale::where('customer_id', $customer->id)
            ->where('source', 'ecommerce')
            ->with(['payments.paymentMethod'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $selectedSale = null;
        if ($this->selectedSaleId) {
            $selectedSale = Sale::where('id', $this->selectedSaleId)
                ->where('customer_id', $customer->id)
                ->where('source', 'ecommerce')
                ->with([
                    'items',
                    'payments.paymentMethod',
                    'ecommerceOrder.shippingDepartment',
                    'ecommerceOrder.shippingMunicipality',
                ])
                ->first();
        }

        return view('livewire.shop.orders', [
            'orders' => $orders,
            'selectedSale' => $selectedSale,
        ]);
    }
}

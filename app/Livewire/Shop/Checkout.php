<?php

namespace App\Livewire\Shop;

use App\Models\Department;
use App\Models\Municipality;
use App\Models\PaymentMethod;
use App\Services\EcommerceCheckoutService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.shop')]
class Checkout extends Component
{
    public array $items = [];

    // Shipping
    public string $department_id = '';
    public string $municipality_id = '';
    public string $address = '';
    public string $phone = '';
    public string $notes = '';

    // Payment
    public string $payment_method_id = '';

    public function mount(): void
    {
        $cart = session('ecommerce_cart', ['items' => []]);
        $this->items = $cart['items'] ?? [];

        if (empty($this->items)) {
            $this->dispatch('notify', message: 'Tu carrito está vacío', type: 'warning');
            $this->redirect('/shop', navigate: true);
            return;
        }

        // Preload customer data
        $customer = Auth::guard('customer')->user();
        if ($customer->phone) {
            $this->phone = $customer->phone;
        }
        if ($customer->department_id) {
            $this->department_id = (string) $customer->department_id;
        }
        if ($customer->municipality_id) {
            $this->municipality_id = (string) $customer->municipality_id;
        }
        if ($customer->address) {
            $this->address = $customer->address;
        }
    }

    public function updatedDepartmentId(): void
    {
        $this->municipality_id = '';
    }

    public function rules(): array
    {
        return [
            'department_id' => 'nullable|exists:departments,id',
            'municipality_id' => 'nullable|exists:municipalities,id',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:1000',
            'payment_method_id' => 'required|exists:payment_methods,id',
        ];
    }

    public function messages(): array
    {
        return [
            'payment_method_id.required' => 'Seleccione un método de pago.',
        ];
    }

    public function placeOrder(): void
    {
        $this->validate();

        $customer = Auth::guard('customer')->user();

        try {
            $service = new EcommerceCheckoutService();

            $sale = $service->placeOrder(
                customer: $customer,
                cartItems: $this->items,
                paymentMethodId: (int) $this->payment_method_id,
                shippingData: [
                    'department_id' => $this->department_id ?: null,
                    'municipality_id' => $this->municipality_id ?: null,
                    'address' => $this->address ?: null,
                    'phone' => $this->phone ?: null,
                    'notes' => $this->notes ?: null,
                ]
            );

            // Clear cart
            session()->forget('ecommerce_cart');
            $this->dispatch('cart-updated', count: 0);

            $this->redirect("/shop/order/{$sale->id}", navigate: true);
        } catch (\Exception $e) {
            $this->dispatch('notify', message: $e->getMessage(), type: 'error');
        }
    }

    public function getSubtotalProperty(): float
    {
        return collect($this->items)->sum(fn($item) => $item['unit_price'] * $item['quantity']);
    }

    public function getTaxTotalProperty(): float
    {
        return collect($this->items)->sum(function ($item) {
            if ($item['tax_rate'] > 0) {
                $priceWithoutTax = $item['unit_price'] / (1 + $item['tax_rate'] / 100);
                return ($item['unit_price'] - $priceWithoutTax) * $item['quantity'];
            }
            return 0;
        });
    }

    public function getTotalProperty(): float
    {
        return $this->subtotal;
    }

    public function render()
    {
        $departments = Department::orderBy('name')->get();
        $municipalities = $this->department_id
            ? Municipality::where('department_id', $this->department_id)->orderBy('name')->get()
            : collect();
        $paymentMethods = PaymentMethod::where('is_active', true)->get();

        return view('livewire.shop.checkout', compact('departments', 'municipalities', 'paymentMethods'));
    }
}

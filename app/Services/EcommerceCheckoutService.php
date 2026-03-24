<?php

namespace App\Services;

use App\Mail\EcommerceNewOrderNotification;
use App\Mail\EcommerceOrderPlaced;
use App\Mail\EcommerceOrderStatusChanged;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\EcommerceOrder;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\SystemDocument;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EcommerceCheckoutService
{
    /**
     * Create a complete e-commerce order within a DB transaction.
     *
     * @throws \Exception If stock is insufficient for any item.
     */
    public function placeOrder(
        Customer $customer,
        array $cartItems,
        int $paymentMethodId,
        array $shippingData
    ): Sale {
        return DB::transaction(function () use ($customer, $cartItems, $paymentMethodId, $shippingData) {
            $this->validateStock($cartItems);

            $branchId = (int) config('ecommerce.branch_id');

            // Calculate totals
            $subtotal = 0;
            $taxTotal = 0;
            foreach ($cartItems as $item) {
                $lineTotal = $item['unit_price'] * $item['quantity'];
                $subtotal += $lineTotal;
                if ($item['tax_rate'] > 0) {
                    $priceWithoutTax = $item['unit_price'] / (1 + $item['tax_rate'] / 100);
                    $taxTotal += ($item['unit_price'] - $priceWithoutTax) * $item['quantity'];
                }
            }

            $sale = Sale::create([
                'branch_id' => $branchId,
                'customer_id' => $customer->id,
                'invoice_number' => $this->generateInvoiceNumber($branchId),
                'subtotal' => round($subtotal - $taxTotal, 2),
                'tax_total' => round($taxTotal, 2),
                'discount' => 0,
                'total' => round($subtotal, 2),
                'status' => 'pending_approval',
                'source' => 'ecommerce',
                'payment_type' => 'cash',
                'payment_status' => 'paid',
                'credit_amount' => 0,
                'paid_amount' => round($subtotal, 2),
            ]);

            // Create sale items
            foreach ($cartItems as $item) {
                $lineTotal = $item['unit_price'] * $item['quantity'];
                $taxAmount = 0;
                if ($item['tax_rate'] > 0) {
                    $priceWithoutTax = $item['unit_price'] / (1 + $item['tax_rate'] / 100);
                    $taxAmount = ($item['unit_price'] - $priceWithoutTax) * $item['quantity'];
                }

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'product_child_id' => $item['product_child_id'] ?? null,
                    'product_name' => $item['name'],
                    'product_sku' => $item['sku'],
                    'unit_price' => $item['unit_price'],
                    'quantity' => $item['quantity'],
                    'tax_rate' => $item['tax_rate'],
                    'tax_amount' => round($taxAmount, 2),
                    'subtotal' => round($lineTotal - $taxAmount, 2),
                    'total' => round($lineTotal, 2),
                ]);
            }

            // Create sale payment
            SalePayment::create([
                'sale_id' => $sale->id,
                'payment_method_id' => $paymentMethodId,
                'amount' => round($subtotal, 2),
            ]);

            // Create ecommerce order with shipping data
            EcommerceOrder::create([
                'sale_id' => $sale->id,
                'customer_id' => $customer->id,
                'shipping_department_id' => $shippingData['department_id'] ?? null,
                'shipping_municipality_id' => $shippingData['municipality_id'] ?? null,
                'shipping_address' => $shippingData['address'] ?? null,
                'shipping_phone' => $shippingData['phone'] ?? null,
                'customer_notes' => $shippingData['notes'] ?? null,
            ]);

            // Reserve stock
            $this->reserveStock($sale, $cartItems);

            // Send email notifications
            $this->sendOrderPlacedEmails($sale, $customer);

            return $sale;
        });
    }

    /**
     * Approve a pending e-commerce order.
     */
    public function approveOrder(Sale $sale): void
    {
        $sale->update(['status' => 'completed']);

        ActivityLogService::log(
            'ecommerce_orders',
            'update',
            "Pedido e-commerce #{$sale->invoice_number} aprobado",
            $sale,
            ['status' => 'pending_approval'],
            ['status' => 'completed']
        );

        $this->sendStatusChangedEmail($sale, 'completed');
    }

    /**
     * Reject a pending e-commerce order and return stock.
     */
    public function rejectOrder(Sale $sale, string $reason): void
    {
        DB::transaction(function () use ($sale, $reason) {
            $sale->update(['status' => 'rejected']);

            $sale->ecommerceOrder->update([
                'rejection_reason' => $reason,
            ]);

            $this->returnStock($sale);

            ActivityLogService::log(
                'ecommerce_orders',
                'update',
                "Pedido e-commerce #{$sale->invoice_number} rechazado: {$reason}",
                $sale,
                ['status' => 'pending_approval'],
                ['status' => 'rejected', 'rejection_reason' => $reason]
            );

            $this->sendStatusChangedEmail($sale, 'rejected', $reason);
        });
    }

    /**
     * Generate invoice number for e-commerce sales.
     */
    private function generateInvoiceNumber(int $branchId): string
    {
        $prefix = 'ECM';
        $date = now()->format('Ymd');
        $pattern = "{$prefix}-{$date}-";

        $lastSale = Sale::where('branch_id', $branchId)
            ->where('invoice_number', 'like', "{$pattern}%")
            ->orderByRaw("CAST(SUBSTRING_INDEX(invoice_number, '-', -1) AS UNSIGNED) DESC")
            ->first();

        $sequence = 1;
        if ($lastSale) {
            $parts = explode('-', $lastSale->invoice_number);
            if (count($parts) === 3) {
                $sequence = (int) $parts[2] + 1;
            }
        }

        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }

    /**
     * Validate stock availability for all cart items.
     *
     * @throws \Exception With details of out-of-stock products.
     */
    private function validateStock(array $cartItems): void
    {
        $outOfStock = [];

        foreach ($cartItems as $item) {
            $product = Product::lockForUpdate()->find($item['product_id']);

            if (!$product || !$product->is_active) {
                $outOfStock[] = $item['name'] . ' (no disponible)';
                continue;
            }

            // Skip stock validation for products that don't manage inventory
            if (!$product->manages_inventory) {
                continue;
            }

            if ((float) $product->current_stock < (float) $item['quantity']) {
                $outOfStock[] = $item['name'] . " (disponible: {$product->current_stock}, solicitado: {$item['quantity']})";
            }
        }

        if (!empty($outOfStock)) {
            throw new \Exception('Stock insuficiente para: ' . implode(', ', $outOfStock));
        }
    }

    /**
     * Decrement stock and create inventory movements for each item.
     */
    private function reserveStock(Sale $sale, array $cartItems): void
        {
            $branchId = (int) config('ecommerce.branch_id');
            $systemDocument = SystemDocument::findByCode('ecommerce-sale');

            foreach ($cartItems as $item) {
                $product = Product::lockForUpdate()->find($item['product_id']);

                // Skip stock reservation for products that don't manage inventory
                if (!$product->manages_inventory) {
                    continue;
                }

                if (!$systemDocument) {
                    continue;
                }

                $quantity = (float) $item['quantity'];

                $stockBefore = (float) $product->current_stock;
                $product->decrement('current_stock', $quantity);

                InventoryMovement::create([
                    'system_document_id' => $systemDocument->id,
                    'document_number' => $systemDocument->generateNextNumber(),
                    'product_id' => $product->id,
                    'branch_id' => $branchId,
                    'user_id' => auth()->id(),
                    'movement_type' => 'out',
                    'quantity' => $quantity,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockBefore - $quantity,
                    'unit_cost' => $product->purchase_price,
                    'total_cost' => $product->purchase_price * $quantity,
                    'reference_type' => Sale::class,
                    'reference_id' => $sale->id,
                    'notes' => "Pedido e-commerce #{$sale->invoice_number}",
                    'movement_date' => now(),
                ]);
            }
        }


    /**
     * Return stock for a rejected order and create reverse inventory movements.
     */
    private function returnStock(Sale $sale): void
    {
        $branchId = (int) config('ecommerce.branch_id');
        $systemDocument = SystemDocument::findByCode('ecommerce-sale');

        if (!$systemDocument) {
            return;
        }

        foreach ($sale->items as $saleItem) {
            if (!$saleItem->product_id) {
                continue;
            }

            $product = Product::lockForUpdate()->find($saleItem->product_id);
            if (!$product || !$product->manages_inventory) {
                continue;
            }

            $quantity = (float) $saleItem->quantity;
            $stockBefore = (float) $product->current_stock;
            $product->increment('current_stock', $quantity);

            InventoryMovement::create([
                'system_document_id' => $systemDocument->id,
                'document_number' => $systemDocument->generateNextNumber(),
                'product_id' => $product->id,
                'branch_id' => $branchId,
                'user_id' => auth()->id(),
                'movement_type' => 'in',
                'quantity' => $quantity,
                'stock_before' => $stockBefore,
                'stock_after' => $stockBefore + $quantity,
                'unit_cost' => $product->purchase_price,
                'total_cost' => $product->purchase_price * $quantity,
                'reference_type' => Sale::class,
                'reference_id' => $sale->id,
                'notes' => "Rechazo pedido e-commerce #{$sale->invoice_number}",
                'movement_date' => now(),
            ]);
        }
    }

    /**
     * Send order confirmation email to customer and notification to POS users.
     */
    public function sendOrderPlacedEmails(Sale $sale, Customer $customer): void
    {
        try {
            // Email to customer
            if ($customer->email) {
                Mail::to($customer->email)->send(new EcommerceOrderPlaced($sale));
            }

            // Email to all active POS users with email
            $branchId = (int) config('ecommerce.branch_id');
            $posUsers = User::where('is_active', true)
                ->where(function ($q) use ($branchId) {
                    $q->where('branch_id', $branchId)
                      ->orWhereHas('roles', fn($r) => $r->where('name', 'super_admin'));
                })
                ->whereNotNull('email')
                ->where('email', '!=', '')
                ->get();

            Log::info("Enviando notificación de nuevo pedido #{$sale->invoice_number} a {$posUsers->count()} usuario(s) POS");

            foreach ($posUsers as $user) {
                try {
                    Mail::to($user->email)->send(new EcommerceNewOrderNotification($sale));
                    Log::info("Email enviado a {$user->email}");
                } catch (\Exception $e) {
                    Log::error("Error enviando email a {$user->email}: " . $e->getMessage());
                }
            }
        } catch (\Exception $e) {
            Log::error('Error enviando emails de pedido e-commerce: ' . $e->getMessage());
        }
    }

    /**
     * Send status change email to customer.
     */
    public function sendStatusChangedEmail(Sale $sale, string $newStatus, ?string $reason = null): void
    {
        try {
            $customer = $sale->customer;
            if ($customer && $customer->email) {
                Mail::to($customer->email)->send(new EcommerceOrderStatusChanged($sale, $newStatus, $reason));
            }
        } catch (\Exception $e) {
            Log::error('Error enviando email de cambio de estado: ' . $e->getMessage());
        }
    }
}

<?php

namespace Tests\Feature\Livewire;

use App\Livewire\PointOfSale;
use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\CashReconciliation;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Role;
use App\Models\Tax;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PointOfSaleTaxTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Branch $branch;
    protected CashRegister $cashRegister;
    protected CashReconciliation $reconciliation;
    protected Tax $tax19;
    protected Product $productWithTax;
    protected Product $productExempt;
    protected Customer $customer;
    protected PaymentMethod $paymentMethod;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create([
            'name' => 'admin',
            'display_name' => 'Admin',
            'is_super_admin' => true,
        ]);

        $this->branch = Branch::create([
            'name' => 'Principal',
            'code' => 'PRIN',
            'is_active' => true,
        ]);

        $this->user = User::create([
            'name' => 'Cashier',
            'email' => 'cashier@example.com',
            'password' => bcrypt('password'),
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);
        $this->user->roles()->attach($role->id);

        $this->cashRegister = CashRegister::create([
            'branch_id' => $this->branch->id,
            'user_id' => $this->user->id,
            'name' => 'Caja 1',
            'is_active' => true,
        ]);

        $this->reconciliation = CashReconciliation::create([
            'branch_id' => $this->branch->id,
            'cash_register_id' => $this->cashRegister->id,
            'opened_by' => $this->user->id,
            'opening_amount' => 100000,
            'opened_at' => now(),
            'status' => 'open',
        ]);

        \App\Models\SystemDocument::create([
            'code' => 'sale',
            'name' => 'Venta',
            'prefix' => 'VEN',
            'next_number' => 1,
            'is_active' => true,
        ]);

        $this->tax19 = Tax::create([
            'name' => 'IVA 19%',
            'value' => 19,
            'is_active' => true,
        ]);

        $dept = \App\Models\Department::create([
            'name' => 'Antioquia',
            'code' => '05',
            'is_active' => true,
        ]);

        $muni = \App\Models\Municipality::create([
            'department_id' => $dept->id,
            'name' => 'Medellin',
            'code' => '05001',
            'is_active' => true,
        ]);

        $taxDoc = \App\Models\TaxDocument::create([
            'dian_code' => '13',
            'description' => 'Cédula de ciudadanía',
            'abbreviation' => 'CC',
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'branch_id' => $this->branch->id,
            'customer_type' => 'natural',
            'tax_document_id' => $taxDoc->id,
            'department_id' => $dept->id,
            'municipality_id' => $muni->id,
            'first_name' => 'Consumidor',
            'last_name' => 'Final',
            'document_number' => '222222222222',
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->paymentMethod = PaymentMethod::create([
            'name' => 'Efectivo',
            'code' => 'cash',
            'dian_code' => '10',
            'is_active' => true,
        ]);

        $category = \App\Models\Category::create([
            'name' => 'General',
            'is_active' => true,
        ]);

        $unit = \App\Models\Unit::create([
            'name' => 'Unidad',
            'abbreviation' => 'UND',
            'is_active' => true,
        ]);

        // Product with 19% tax (price includes tax = false, base = 100, price with tax = 119)
        $this->productWithTax = Product::create([
            'branch_id' => $this->branch->id,
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'name' => 'Producto Con IVA',
            'sku' => 'PROD-IVA',
            'purchase_price' => 50,
            'sale_price' => 100,
            'tax_id' => $this->tax19->id,
            'price_includes_tax' => false,
            'current_stock' => 100,
            'manages_inventory' => true,
            'is_active' => true,
        ]);

        // Product with 0% tax / exempt
        $this->productExempt = Product::create([
            'branch_id' => $this->branch->id,
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'name' => 'Producto Sin IVA',
            'sku' => 'PROD-EXEMPT',
            'purchase_price' => 30,
            'sale_price' => 50,
            'tax_id' => null,
            'price_includes_tax' => false,
            'current_stock' => 100,
            'manages_inventory' => true,
            'is_active' => true,
        ]);
    }

    public function test_can_toggle_tax_for_single_product(): void
    {
        $this->actingAs($this->user);

        $cartKey = $this->productWithTax->id . '-parent';

        $component = Livewire::test(PointOfSale::class)
            ->call('addToCart', $this->productWithTax->id);

        $cart = $component->get('cart');
        $this->assertArrayHasKey($cartKey, $cart);
        $this->assertEquals(19, $cart[$cartKey]['tax_rate']);
        $this->assertEquals(19, $cart[$cartKey]['tax_amount']);
        $this->assertEquals(100, $cart[$cartKey]['subtotal']);
        $this->assertEquals(119, $component->get('total'));

        // Toggle Tax OFF
        $component->call('toggleItemTax', $cartKey);

        $cart = $component->get('cart');
        $this->assertTrue($cart[$cartKey]['tax_exempt']);
        $this->assertEquals(0, $cart[$cartKey]['tax_rate']);
        $this->assertEquals(0, $cart[$cartKey]['tax_amount']);
        $this->assertEquals(100, $cart[$cartKey]['price']);
        $this->assertEquals(100, $component->get('total'));

        // Toggle Tax back ON
        $component->call('toggleItemTax', $cartKey);

        $cart = $component->get('cart');
        $this->assertFalse($cart[$cartKey]['tax_exempt']);
        $this->assertEquals(19, $cart[$cartKey]['tax_rate']);
        $this->assertEquals(19, $cart[$cartKey]['tax_amount']);
        $this->assertEquals(119, $cart[$cartKey]['price']);
        $this->assertEquals(119, $component->get('total'));
    }

    public function test_can_toggle_all_taxes_in_cart(): void
    {
        $this->actingAs($this->user);

        $cartKey1 = $this->productWithTax->id . '-parent';
        $cartKey2 = $this->productExempt->id . '-parent';

        $component = Livewire::test(PointOfSale::class)
            ->call('addToCart', $this->productWithTax->id)
            ->call('addToCart', $this->productExempt->id);

        // Initial total: 119 (with tax) + 50 (exempt) = 169
        $this->assertEquals(169, $component->get('total'));

        // Toggle all taxes OFF (F8)
        $component->call('toggleAllTaxes');

        $cart = $component->get('cart');
        $this->assertTrue($cart[$cartKey1]['tax_exempt']);
        $this->assertEquals(0, $cart[$cartKey1]['tax_amount']);
        $this->assertFalse($cart[$cartKey2]['tax_exempt']); // untouched because it has no tax

        // New total: 100 + 50 = 150
        $this->assertEquals(150, $component->get('total'));

        // Toggle all taxes ON (F8)
        $component->call('toggleAllTaxes');

        $cart = $component->get('cart');
        $this->assertFalse($cart[$cartKey1]['tax_exempt']);
        $this->assertEquals(19, $cart[$cartKey1]['tax_amount']);
        $this->assertEquals(169, $component->get('total'));
    }

    public function test_sale_persists_zero_tax_when_exempt(): void
    {
        $this->actingAs($this->user);

        $cartKey = $this->productWithTax->id . '-parent';

        $component = Livewire::test(PointOfSale::class)
            ->call('addToCart', $this->productWithTax->id)
            ->call('toggleItemTax', $cartKey)
            ->call('openPayment')
            ->set('payments', [
                ['method_id' => (string)$this->paymentMethod->id, 'amount' => '100']
            ])
            ->call('processPayment');

        $this->assertDatabaseHas('sales', [
            'subtotal' => 100,
            'tax_total' => 0,
            'total' => 100,
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('sale_items', [
            'product_id' => $this->productWithTax->id,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total' => 100,
        ]);
    }

    public function test_price_includes_tax_product_tax_toggle(): void
    {
        $this->actingAs($this->user);

        // Product with 19% tax included in price (sale_price = 119 -> base = 100, tax = 19)
        $category = \App\Models\Category::first();
        $unit = \App\Models\Unit::first();
        $productInclusive = Product::create([
            'branch_id' => $this->branch->id,
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'name' => 'Producto IVA Incluido',
            'sku' => 'PROD-INC',
            'purchase_price' => 50,
            'sale_price' => 119,
            'tax_id' => $this->tax19->id,
            'price_includes_tax' => true,
            'current_stock' => 100,
            'manages_inventory' => true,
            'is_active' => true,
        ]);

        $cartKey = $productInclusive->id . '-parent';

        $component = Livewire::test(PointOfSale::class)
            ->call('addToCart', $productInclusive->id);

        $this->assertEquals(119, $component->get('total'));
        $this->assertEquals(19, $component->get('taxTotal'));
        $this->assertEquals(100, $component->get('subtotal'));

        // Toggle tax OFF
        $component->call('toggleItemTax', $cartKey);

        $this->assertEquals(100, $component->get('total'));
        $this->assertEquals(0, $component->get('taxTotal'));
        $this->assertEquals(100, $component->get('subtotal'));

        // Toggle tax ON
        $component->call('toggleItemTax', $cartKey);

        $this->assertEquals(119, $component->get('total'));
        $this->assertEquals(19, $component->get('taxTotal'));
        $this->assertEquals(100, $component->get('subtotal'));
    }

    public function test_discount_with_tax_toggle(): void
    {
        $this->actingAs($this->user);

        $cartKey = $this->productWithTax->id . '-parent';

        $component = Livewire::test(PointOfSale::class)
            ->call('addToCart', $this->productWithTax->id)
            ->call('openDiscountModal', $cartKey)
            ->set('discountType', 'percentage')
            ->set('discountValue', '10') // 10% discount on 100 = 10 discount. Taxable = 90. Tax 19% = 17.10. Total = 107.10
            ->call('applyDiscount');

        $this->assertEquals(107.10, $component->get('total'));
        $this->assertEquals(17.10, $component->get('taxTotal'));

        // Toggle tax OFF: Total should be Subtotal (100) - Discount (10) + Tax (0) = 90
        $component->call('toggleItemTax', $cartKey);

        $this->assertEquals(90, $component->get('total'));
        $this->assertEquals(0, $component->get('taxTotal'));

        // Toggle tax ON: Total should be back to 107.10
        $component->call('toggleItemTax', $cartKey);

        $this->assertEquals(107.10, $component->get('total'));
        $this->assertEquals(17.10, $component->get('taxTotal'));
    }

    public function test_hold_and_restore_order_preserves_tax_exempt_status(): void
    {
        $this->actingAs($this->user);

        $cartKey = $this->productWithTax->id . '-parent';

        $component = Livewire::test(PointOfSale::class)
            ->call('addToCart', $this->productWithTax->id)
            ->call('toggleItemTax', $cartKey);

        $this->assertEquals(100, $component->get('total'));

        // Hold order
        $component->call('holdOrder');

        $this->assertCount(0, $component->get('cart'));
        $this->assertCount(1, $component->get('heldOrders'));

        // Restore order
        $component->call('restoreOrder', 0);

        $this->assertCount(1, $component->get('cart'));
        $this->assertEquals(100, $component->get('total'));
        $cart = $component->get('cart');
        $this->assertTrue($cart[$cartKey]['tax_exempt']);
        $this->assertEquals(0, $cart[$cartKey]['tax_amount']);
    }

    public function test_can_toggle_tax_preserving_final_price_single_product(): void
    {
        $this->actingAs($this->user);

        // Enable preserve price setting on branch
        $this->branch->update(['tax_exempt_preserves_price' => true]);

        // Product with 19% tax included (1500 final price -> base ~1260.50, tax ~239.50)
        $category = \App\Models\Category::first();
        $unit = \App\Models\Unit::first();
        $product = Product::create([
            'branch_id' => $this->branch->id,
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'name' => 'Producto Prueba 1500',
            'sku' => 'PROD-1500',
            'purchase_price' => 800,
            'sale_price' => 1500,
            'tax_id' => $this->tax19->id,
            'price_includes_tax' => true,
            'current_stock' => 100,
            'manages_inventory' => true,
            'is_active' => true,
        ]);

        $cartKey = $product->id . '-parent';

        $component = Livewire::test(PointOfSale::class)
            ->call('addToCart', $product->id);

        $this->assertEquals(1500, $component->get('total'));
        $this->assertEquals(239.50, $component->get('taxTotal'));
        $this->assertEquals(1260.50, $component->get('subtotal'));

        // Toggle tax OFF: Total should remain 1500, tax 0, subtotal 1500
        $component->call('toggleItemTax', $cartKey);

        $this->assertEquals(1500, $component->get('total'));
        $this->assertEquals(0, $component->get('taxTotal'));
        $this->assertEquals(1500, $component->get('subtotal'));
        $cart = $component->get('cart');
        $this->assertTrue($cart[$cartKey]['tax_exempt']);
        $this->assertEquals(1500, $cart[$cartKey]['price']);
        $this->assertEquals(1500, $cart[$cartKey]['base_price']);
        $this->assertEquals(0, $cart[$cartKey]['tax_amount']);

        // Toggle tax ON: Total should still be 1500, tax 239.50, subtotal 1260.50
        $component->call('toggleItemTax', $cartKey);

        $this->assertEquals(1500, $component->get('total'));
        $this->assertEquals(239.50, $component->get('taxTotal'));
        $this->assertEquals(1260.50, $component->get('subtotal'));
        $cart = $component->get('cart');
        $this->assertFalse($cart[$cartKey]['tax_exempt']);
        $this->assertEquals(1500, $cart[$cartKey]['price']);
        $this->assertEquals(1260.50, $cart[$cartKey]['base_price']);
    }

    public function test_can_toggle_all_taxes_preserving_final_price(): void
    {
        $this->actingAs($this->user);

        // Enable preserve price setting on branch
        $this->branch->update(['tax_exempt_preserves_price' => true]);

        $cartKey1 = $this->productWithTax->id . '-parent';
        $cartKey2 = $this->productExempt->id . '-parent';

        $component = Livewire::test(PointOfSale::class)
            ->call('addToCart', $this->productWithTax->id) // priceWithTax = 119 (base 100, tax 19)
            ->call('addToCart', $this->productExempt->id);  // price = 50 (tax 0)

        // Initial total: 119 + 50 = 169
        $this->assertEquals(169, $component->get('total'));
        $this->assertEquals(19, $component->get('taxTotal'));

        // Toggle all taxes OFF (F8)
        $component->call('toggleAllTaxes');

        // Total should stay 169! (productWithTax stays 119 with 0 tax, exempt stays 50)
        $this->assertEquals(169, $component->get('total'));
        $this->assertEquals(0, $component->get('taxTotal'));
        $this->assertEquals(169, $component->get('subtotal'));

        $cart = $component->get('cart');
        $this->assertTrue($cart[$cartKey1]['tax_exempt']);
        $this->assertEquals(119, $cart[$cartKey1]['price']);
        $this->assertEquals(119, $cart[$cartKey1]['base_price']);
        $this->assertEquals(0, $cart[$cartKey1]['tax_amount']);

        // Toggle all taxes ON (F8)
        $component->call('toggleAllTaxes');

        $this->assertEquals(169, $component->get('total'));
        $this->assertEquals(19, $component->get('taxTotal'));
        $this->assertEquals(150, $component->get('subtotal'));

        $cart = $component->get('cart');
        $this->assertFalse($cart[$cartKey1]['tax_exempt']);
        $this->assertEquals(119, $cart[$cartKey1]['price']);
        $this->assertEquals(100, $cart[$cartKey1]['base_price']);
    }

    public function test_sale_persists_preserved_price_when_exempt(): void
    {
        $this->actingAs($this->user);

        $this->branch->update(['tax_exempt_preserves_price' => true]);

        $cartKey = $this->productWithTax->id . '-parent';

        $component = Livewire::test(PointOfSale::class)
            ->call('addToCart', $this->productWithTax->id)
            ->call('toggleItemTax', $cartKey)
            ->call('openPayment')
            ->set('payments', [
                ['method_id' => (string)$this->paymentMethod->id, 'amount' => '119']
            ])
            ->call('processPayment');

        $this->assertDatabaseHas('sales', [
            'subtotal' => 119,
            'tax_total' => 0,
            'total' => 119,
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('sale_items', [
            'product_id' => $this->productWithTax->id,
            'unit_price' => 119,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total' => 119,
        ]);
    }
}


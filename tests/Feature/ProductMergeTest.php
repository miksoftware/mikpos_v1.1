<?php

namespace Tests\Feature;

use App\Livewire\Products;
use App\Models\Branch;
use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\Module;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductBarcode;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SystemDocument;
use App\Models\Tax;
use App\Models\Unit;
use App\Models\User;
use App\Services\ProductMergeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Livewire\Livewire;
use Tests\TestCase;

class ProductMergeTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $regularUser;
    protected Branch $branch;
    protected Category $category;
    protected Unit $unit;
    protected Tax $tax;
    protected SystemDocument $adjDocument;
    protected SystemDocument $initialStockDoc;

    protected function setUp(): void
    {
        parent::setUp();

        $module = Module::firstOrCreate(
            ['name' => 'products'],
            ['display_name' => 'Productos', 'is_active' => true]
        );

        $permView = Permission::firstOrCreate(['name' => 'products.view'], ['display_name' => 'Ver', 'module_id' => $module->id]);
        $permEdit = Permission::firstOrCreate(['name' => 'products.edit'], ['display_name' => 'Editar', 'module_id' => $module->id]);
        $permDelete = Permission::firstOrCreate(['name' => 'products.delete'], ['display_name' => 'Eliminar', 'module_id' => $module->id]);
        $permMerge = Permission::firstOrCreate(['name' => 'products.merge'], ['display_name' => 'Unificar', 'module_id' => $module->id]);

        $adminRole = Role::create([
            'name' => 'super_admin',
            'display_name' => 'Super Admin',
            'is_super_admin' => true,
        ]);
        $adminRole->permissions()->attach([$permView->id, $permEdit->id, $permDelete->id, $permMerge->id]);

        $cashierRole = Role::create([
            'name' => 'cashier',
            'display_name' => 'Cajero',
            'is_super_admin' => false,
        ]);
        $cashierRole->permissions()->attach([$permView->id]);

        $this->branch = Branch::create([
            'name' => 'Sucursal Principal',
            'code' => 'SUC01',
            'is_active' => true,
        ]);

        $this->adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('secret'),
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);
        $this->adminUser->roles()->attach($adminRole->id);

        $this->regularUser = User::create([
            'name' => 'Regular Cashier',
            'email' => 'cashier@test.com',
            'password' => bcrypt('secret'),
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);
        $this->regularUser->roles()->attach($cashierRole->id);

        $this->category = Category::create([
            'name' => 'Bebidas',
            'is_active' => true,
        ]);

        $this->unit = Unit::create([
            'name' => 'Unidad',
            'abbreviation' => 'und',
            'is_active' => true,
        ]);

        $this->tax = Tax::create([
            'name' => 'IVA 19%',
            'value' => 19,
            'is_active' => true,
        ]);

        $this->adjDocument = SystemDocument::create([
            'code' => 'adjustment',
            'name' => 'Ajuste de Inventario',
            'prefix' => 'AJU',
            'next_number' => 1,
            'is_active' => true,
        ]);

        $this->initialStockDoc = SystemDocument::create([
            'code' => 'initial_stock',
            'name' => 'Stock Inicial',
            'prefix' => 'STI',
            'next_number' => 1,
            'is_active' => true,
        ]);
    }

    public function test_can_merge_two_products_and_transfer_all_movements_cleanly()
    {
        $this->actingAs($this->adminUser);

        // Product A (Target to keep)
        $productA = Product::create([
            'branch_id' => $this->branch->id,
            'name' => 'Coca Cola 400ml',
            'sku' => 'BEB-00001',
            'barcode' => '7701111111111',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'purchase_price' => 2000,
            'sale_price' => 3500,
            'current_stock' => 10,
            'is_active' => true,
        ]);

        // Product B (Source to merge and delete)
        $productB = Product::create([
            'branch_id' => $this->branch->id,
            'name' => 'Gaseosa Coca-Cola 400ml',
            'sku' => 'BEB-00002',
            'barcode' => '7702222222222',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'purchase_price' => 2200,
            'sale_price' => 3500,
            'current_stock' => 5,
            'is_active' => true,
        ]);

        // Barcode for Product B
        ProductBarcode::create([
            'product_id' => $productB->id,
            'barcode' => '7702222222222',
            'is_primary' => true,
        ]);

        // Sale item on Product B
        $sale = Sale::create([
            'branch_id' => $this->branch->id,
            'user_id' => $this->adminUser->id,
            'invoice_number' => 'VTA-0001',
            'subtotal' => 7000,
            'tax_total' => 0,
            'discount' => 0,
            'total' => 7000,
            'status' => 'completed',
        ]);
        $saleItem = SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $productB->id,
            'product_name' => $productB->name,
            'quantity' => 2,
            'unit_price' => 3500,
            'subtotal' => 7000,
            'total' => 7000,
        ]);

        // Purchase item on Product A
        $purchase = Purchase::create([
            'branch_id' => $this->branch->id,
            'user_id' => $this->adminUser->id,
            'purchase_number' => 'CMP-0001',
            'purchase_date' => now(),
            'subtotal' => 20000,
            'tax_amount' => 0,
            'discount' => 0,
            'total' => 20000,
            'status' => 'received',
        ]);
        $purchaseItem = PurchaseItem::create([
            'purchase_id' => $purchase->id,
            'product_id' => $productA->id,
            'quantity' => 10,
            'unit_cost' => 2000,
            'subtotal' => 20000,
            'total' => 20000,
        ]);

        // Inventory movement on Product B
        $movB = InventoryMovement::create([
            'system_document_id' => $this->initialStockDoc->id,
            'document_number' => 'STI-0001',
            'product_id' => $productB->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->adminUser->id,
            'movement_type' => 'in',
            'quantity' => 5,
            'stock_before' => 0,
            'stock_after' => 5,
            'unit_cost' => 2200,
            'notes' => 'Stock inicial B',
            'movement_date' => now(),
        ]);

        $service = new ProductMergeService();
        $result = $service->merge($productA, $productB, $this->adminUser->id);

        $this->assertTrue($result['success']);
        $this->assertEquals(15, $result['stock_final']);

        // Check Product A updated
        $productA->refresh();
        $this->assertEquals(15, $productA->current_stock);

        // Check Product B deleted
        $this->assertDatabaseMissing('products', ['id' => $productB->id]);

        // Check SaleItem reassigned to Product A
        $saleItem->refresh();
        $this->assertEquals($productA->id, $saleItem->product_id);

        // Check Inventory movements reassigned
        $movB->refresh();
        $this->assertEquals($productA->id, $movB->product_id);

        // Check explicit Unification Kardex Movement created
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $productA->id,
            'movement_type' => 'in',
            'quantity' => 5,
            'stock_before' => 10,
            'stock_after' => 15,
        ]);

        // Check Barcode transferred
        $this->assertDatabaseHas('product_barcodes', [
            'product_id' => $productA->id,
            'barcode' => '7702222222222',
            'is_primary' => false,
        ]);
    }

    public function test_merge_fails_if_resulting_stock_is_negative()
    {
        $this->actingAs($this->adminUser);

        // Product A (stock 3)
        $productA = Product::create([
            'branch_id' => $this->branch->id,
            'name' => 'Aceite 500ml A',
            'sku' => 'ACT-00001',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'purchase_price' => 5000,
            'sale_price' => 7000,
            'current_stock' => 3,
            'is_active' => true,
        ]);

        // Product B (stock -10 due to overselling)
        $productB = Product::create([
            'branch_id' => $this->branch->id,
            'name' => 'Aceite 500ml B',
            'sku' => 'ACT-00002',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'purchase_price' => 5000,
            'sale_price' => 7000,
            'current_stock' => -10,
            'is_active' => true,
        ]);

        $service = new ProductMergeService();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('NEGATIVO');

        $service->merge($productA, $productB, $this->adminUser->id);

        // Assert neither was deleted
        $this->assertDatabaseHas('products', ['id' => $productA->id]);
        $this->assertDatabaseHas('products', ['id' => $productB->id]);
    }

    public function test_merge_succeeds_when_positive_stock_compensates_negative_stock()
    {
        $this->actingAs($this->adminUser);

        // Product A (stock 10)
        $productA = Product::create([
            'branch_id' => $this->branch->id,
            'name' => 'Arroz 1kg A',
            'sku' => 'ARR-00001',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'purchase_price' => 3000,
            'sale_price' => 4500,
            'current_stock' => 10,
            'is_active' => true,
        ]);

        // Product B (stock -4)
        $productB = Product::create([
            'branch_id' => $this->branch->id,
            'name' => 'Arroz 1kg B',
            'sku' => 'ARR-00002',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'purchase_price' => 3000,
            'sale_price' => 4500,
            'current_stock' => -4,
            'is_active' => true,
        ]);

        $service = new ProductMergeService();
        $result = $service->merge($productA, $productB, $this->adminUser->id);

        $this->assertTrue($result['success']);
        $this->assertEquals(6, $result['stock_final']);

        $productA->refresh();
        $this->assertEquals(6, $productA->current_stock);
        $this->assertDatabaseMissing('products', ['id' => $productB->id]);
    }

    public function test_livewire_products_component_merge_modal_interaction()
    {
        $this->actingAs($this->adminUser);

        $product1 = Product::create([
            'branch_id' => $this->branch->id,
            'name' => 'Galletas Oreo 12pk',
            'sku' => 'GAL-00001',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'purchase_price' => 5000,
            'sale_price' => 7000,
            'current_stock' => 8,
            'is_active' => true,
        ]);

        $product2 = Product::create([
            'branch_id' => $this->branch->id,
            'name' => 'Oreo Galleta 12pk',
            'sku' => 'GAL-00002',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'purchase_price' => 5000,
            'sale_price' => 7000,
            'current_stock' => 4,
            'is_active' => true,
        ]);

        Livewire::test(Products::class)
            ->call('openMergeModal', $product1->id)
            ->assertSet('isMergeModalOpen', true)
            ->assertSet('mergeProduct1Id', $product1->id)
            ->assertSet('mergeTargetId', $product1->id)
            ->call('selectMergeProduct', 2, $product2->id)
            ->assertSet('mergeProduct2Id', $product2->id)
            ->assertSet('mergeCombinedStock', 12.0)
            ->assertSet('mergeStockIsNegative', false)
            ->call('executeMerge')
            ->assertSet('isMergeModalOpen', false)
            ->assertDispatched('notify');

        $product1->refresh();
        $this->assertEquals(12, $product1->current_stock);
        $this->assertDatabaseMissing('products', ['id' => $product2->id]);
    }

    public function test_user_without_permission_cannot_open_or_execute_merge()
    {
        $this->actingAs($this->regularUser);

        $product1 = Product::create([
            'branch_id' => $this->branch->id,
            'name' => 'Producto 1',
            'sku' => 'PRD-00001',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'purchase_price' => 1000,
            'sale_price' => 2000,
            'current_stock' => 5,
            'is_active' => true,
        ]);

        Livewire::test(Products::class)
            ->call('openMergeModal', $product1->id)
            ->assertSet('isMergeModalOpen', false)
            ->assertDispatched('notify', function ($event, $data) {
                return str_contains($data['message'], 'No tienes permiso');
            });
    }

    public function test_barcodes_and_variants_are_correctly_transferred_without_duplicates()
    {
        $this->actingAs($this->adminUser);

        $productA = Product::create([
            'branch_id' => $this->branch->id,
            'name' => 'Camiseta Polo A',
            'sku' => 'CAM-00001',
            'barcode' => '1111111111',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'purchase_price' => 15000,
            'sale_price' => 30000,
            'current_stock' => 5,
            'is_active' => true,
        ]);

        $productB = Product::create([
            'branch_id' => $this->branch->id,
            'name' => 'Camiseta Polo B',
            'sku' => 'CAM-00002',
            'barcode' => '2222222222',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'purchase_price' => 15000,
            'sale_price' => 30000,
            'current_stock' => 3,
            'is_active' => true,
        ]);

        ProductBarcode::create([
            'product_id' => $productA->id,
            'barcode' => '1111111111',
            'is_primary' => true,
        ]);

        ProductBarcode::create([
            'product_id' => $productB->id,
            'barcode' => '2222222222',
            'is_primary' => true,
        ]);

        // Duplicate barcode on B that A already has
        ProductBarcode::create([
            'product_id' => $productB->id,
            'barcode' => '1111111111',
            'is_primary' => false,
        ]);

        $childB = \App\Models\ProductChild::create([
            'product_id' => $productB->id,
            'name' => 'Talla L',
            'sku' => 'CAM-00002-L',
            'unit_quantity' => 1,
            'sale_price' => 30000,
            'is_active' => true,
        ]);

        $service = new ProductMergeService();
        $result = $service->merge($productA, $productB, $this->adminUser->id);

        $this->assertTrue($result['success']);
        $productA->refresh();

        // Product A now has stock 8
        $this->assertEquals(8, $productA->current_stock);

        // Child variant was moved to product A
        $childB->refresh();
        $this->assertEquals($productA->id, $childB->product_id);

        // Barcode 2222222222 transferred to A as non-primary
        $this->assertDatabaseHas('product_barcodes', [
            'product_id' => $productA->id,
            'barcode' => '2222222222',
            'is_primary' => false,
        ]);

        // Total barcodes for product A is 2 (deduplicated)
        $this->assertEquals(2, ProductBarcode::where('product_id', $productA->id)->count());
    }

    public function test_recipes_and_production_orders_are_correctly_transferred()
    {
        $this->actingAs($this->adminUser);

        $productA = Product::create([
            'branch_id' => $this->branch->id,
            'name' => 'Pan Hamburguesa A',
            'sku' => 'PAN-00001',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'purchase_price' => 500,
            'sale_price' => 1500,
            'current_stock' => 20,
            'is_active' => true,
        ]);

        $productB = Product::create([
            'branch_id' => $this->branch->id,
            'name' => 'Pan Hamburguesa B',
            'sku' => 'PAN-00002',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'purchase_price' => 500,
            'sale_price' => 1500,
            'current_stock' => 10,
            'is_active' => true,
        ]);

        $recipe = \App\Models\Recipe::create([
            'product_id' => $productB->id,
            'yield_quantity' => 10,
            'is_active' => true,
        ]);

        $prodOrderId = DB::table('production_orders')->insertGetId([
            'branch_id' => $this->branch->id,
            'user_id' => $this->adminUser->id,
            'product_id' => $productB->id,
            'recipe_id' => $recipe->id,
            'quantity_to_produce' => 10,
            'total_cost' => 5000,
            'status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $prodItem = \App\Models\ProductionOrderItem::create([
            'production_order_id' => $prodOrderId,
            'recipe_id' => $recipe->id,
            'product_id' => $productB->id,
            'quantity_to_produce' => 10,
            'total_cost' => 5000,
        ]);

        $service = new ProductMergeService();
        $result = $service->merge($productA, $productB, $this->adminUser->id);

        $this->assertTrue($result['success']);

        $recipe->refresh();
        $this->assertEquals($productA->id, $recipe->product_id);

        $prodItem->refresh();
        $this->assertEquals($productA->id, $prodItem->product_id);
    }
}

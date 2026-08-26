<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Products;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductBarcode;
use App\Models\ProductChild;
use App\Models\ProductFieldSetting;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductsBarcodeBranchTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Role $superAdminRole;
    protected Branch $branchA;
    protected Branch $branchB;
    protected Category $category;
    protected Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdminRole = Role::factory()->superAdmin()->create();
        $this->branchA = Branch::factory()->create(['name' => 'Sucursal Norte']);
        $this->branchB = Branch::factory()->create(['name' => 'Sucursal Sur']);

        $this->adminUser = User::factory()->create([
            'branch_id' => $this->branchA->id,
        ]);
        $this->adminUser->roles()->attach($this->superAdminRole->id);

        $this->category = Category::factory()->create();
        $this->unit = Unit::factory()->create();

        // Enable barcode fields
        ProductFieldSetting::factory()->forField('barcode')->create([
            'branch_id' => null,
            'parent_visible' => true,
            'parent_required' => false,
            'child_visible' => true,
            'child_required' => false,
        ]);
    }

    public function test_can_edit_product_with_same_barcode_as_another_branch(): void
    {
        $this->actingAs($this->adminUser);

        // Product in Branch A with barcode '7701234567890'
        $productA = Product::factory()->create([
            'branch_id' => $this->branchA->id,
            'name' => 'Producto Sucursal A',
            'barcode' => '7701234567890',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'purchase_price' => 10,
            'sale_price' => 20,
            'current_stock' => 5,
        ]);
        ProductBarcode::create([
            'product_id' => $productA->id,
            'barcode' => '7701234567890',
            'is_primary' => true,
        ]);

        // Cloned Product in Branch B with the SAME barcode '7701234567890'
        $productB = Product::factory()->create([
            'branch_id' => $this->branchB->id,
            'name' => 'Producto Sucursal B',
            'barcode' => '7701234567890',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'purchase_price' => 10,
            'sale_price' => 20,
            'current_stock' => 0,
        ]);
        ProductBarcode::create([
            'product_id' => $productB->id,
            'barcode' => '7701234567890',
            'is_primary' => true,
        ]);

        // Edit Product A (in Branch A) - should succeed with no validation error
        Livewire::test(Products::class)
            ->call('edit', $productA->id)
            ->set('name', 'Producto Sucursal A Modificado')
            ->call('store')
            ->assertHasNoErrors(['barcode']);

        $productA->refresh();
        $this->assertEquals('PRODUCTO SUCURSAL A MODIFICADO', $productA->name);
        $this->assertEquals('7701234567890', $productA->barcode);

        // Edit Product B (in Branch B) - should also succeed with no validation error
        Livewire::test(Products::class)
            ->call('edit', $productB->id)
            ->set('name', 'Producto Sucursal B Modificado')
            ->call('store')
            ->assertHasNoErrors(['barcode']);

        $productB->refresh();
        $this->assertEquals('PRODUCTO SUCURSAL B MODIFICADO', $productB->name);
        $this->assertEquals('7701234567890', $productB->barcode);
    }

    public function test_cannot_use_duplicate_barcode_within_the_same_branch(): void
    {
        $this->actingAs($this->adminUser);

        // Existing product 1 in Branch A with barcode '7701111111111'
        $product1 = Product::factory()->create([
            'branch_id' => $this->branchA->id,
            'name' => 'Producto 1',
            'barcode' => '7701111111111',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);
        ProductBarcode::create([
            'product_id' => $product1->id,
            'barcode' => '7701111111111',
            'is_primary' => true,
        ]);

        // Product 2 in Branch A
        $product2 = Product::factory()->create([
            'branch_id' => $this->branchA->id,
            'name' => 'Producto 2',
            'barcode' => '7702222222222',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);
        ProductBarcode::create([
            'product_id' => $product2->id,
            'barcode' => '7702222222222',
            'is_primary' => true,
        ]);

        // Attempting to edit Product 2 and giving it Product 1's barcode in Branch A should fail
        Livewire::test(Products::class)
            ->call('edit', $product2->id)
            ->set('barcode', '7701111111111')
            ->call('store')
            ->assertHasErrors(['barcode']);
    }

    public function test_variant_barcode_can_be_shared_across_branches_but_not_same_branch(): void
    {
        $this->actingAs($this->adminUser);

        $parentA = Product::factory()->create([
            'branch_id' => $this->branchA->id,
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);

        $parentB = Product::factory()->create([
            'branch_id' => $this->branchB->id,
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);

        // Variant in Branch B
        $childB = ProductChild::factory()->create([
            'product_id' => $parentB->id,
            'barcode' => '7709999999999',
            'name' => 'Variante Branch B',
        ]);
        ProductBarcode::create([
            'product_child_id' => $childB->id,
            'barcode' => '7709999999999',
            'is_primary' => true,
        ]);

        // Variant in Branch A with the SAME barcode as Branch B
        $childA = ProductChild::factory()->create([
            'product_id' => $parentA->id,
            'barcode' => '7709999999999',
            'name' => 'Variante Branch A',
        ]);
        ProductBarcode::create([
            'product_child_id' => $childA->id,
            'barcode' => '7709999999999',
            'is_primary' => true,
        ]);

        // Editing variant A should succeed
        Livewire::test(Products::class)
            ->call('editChild', $childA->id)
            ->set('childName', 'Variante Branch A Editada')
            ->call('storeChild')
            ->assertHasNoErrors(['childBarcode']);

        $childA->refresh();
        $this->assertEquals('VARIANTE BRANCH A EDITADA', $childA->name);

        // Creating another variant in Branch A with the same barcode should fail
        Livewire::test(Products::class)
            ->call('createChild', $parentA->id)
            ->set('childName', 'Otra Variante Branch A')
            ->set('childBarcode', '7709999999999')
            ->set('childUnitQuantity', 1)
            ->set('childSalePrice', 50)
            ->call('storeChild')
            ->assertHasErrors(['childBarcode']);
    }

    public function test_add_barcode_modal_allows_other_branch_barcodes_but_blocks_same_branch(): void
    {
        $this->actingAs($this->adminUser);

        $productA = Product::factory()->create([
            'branch_id' => $this->branchA->id,
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);

        $productB = Product::factory()->create([
            'branch_id' => $this->branchB->id,
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);
        ProductBarcode::create([
            'product_id' => $productB->id,
            'barcode' => '7705555555555',
            'is_primary' => true,
        ]);

        // Adding '7705555555555' (which exists in Branch B) to Product A in Branch A should succeed
        Livewire::test(Products::class)
            ->call('manageBarcodes', $productA->id)
            ->set('newBarcode', '7705555555555')
            ->call('addBarcode')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('product_barcodes', [
            'product_id' => $productA->id,
            'barcode' => '7705555555555',
        ]);
    }
}

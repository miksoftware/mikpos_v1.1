<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Products;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductChild;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductsTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Role $superAdminRole;
    protected Category $category;
    protected Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->superAdminRole = Role::factory()->superAdmin()->create();
        $this->adminUser = User::factory()->create();
        $this->adminUser->roles()->attach($this->superAdminRole->id);
        
        // Create required related models
        $this->category = Category::factory()->create();
        $this->unit = Unit::factory()->create();
    }

    // ==========================================
    // Page Rendering Tests
    // ==========================================

    public function test_products_page_can_be_rendered(): void
    {
        $this->actingAs($this->adminUser);
        
        $response = $this->get('/products');
        
        $response->assertStatus(200);
        $response->assertSeeLivewire(Products::class);
    }

    public function test_products_page_displays_products_list(): void
    {
        $this->actingAs($this->adminUser);
        
        $product = Product::factory()->create([
            'name' => 'Test Product',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);
        
        Livewire::test(Products::class)
            ->assertSee('Test Product');
    }

    // ==========================================
    // Property 1: Parent Product CRUD Integrity
    // ==========================================

    public function test_user_with_permission_can_create_parent_product(): void
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(Products::class)
            ->call('create')
            ->assertSet('isModalOpen', true)
            ->set('name', 'New Product')
            ->set('category_id', $this->category->id)
            ->set('unit_id', $this->unit->id)
            ->set('purchase_price', 100)
            ->set('sale_price', 150)
            ->set('current_stock', 50)
            ->call('store')
            ->assertSet('isModalOpen', false);
        
        $this->assertDatabaseHas('products', [
            'name' => 'New Product',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);
    }

    public function test_user_without_permission_cannot_create_product(): void
    {
        $userWithoutPermission = User::factory()->create();
        $limitedRole = Role::factory()->create(['name' => 'limited']);
        $userWithoutPermission->roles()->attach($limitedRole->id);
        
        $this->actingAs($userWithoutPermission);
        
        Livewire::test(Products::class)
            ->call('create')
            ->assertSet('isModalOpen', false)
            ->assertDispatched('notify');
    }

    public function test_user_with_permission_can_edit_parent_product(): void
    {
        $this->actingAs($this->adminUser);
        
        $product = Product::factory()->create([
            'name' => 'Original Product',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);
        
        Livewire::test(Products::class)
            ->call('edit', $product->id)
            ->assertSet('isModalOpen', true)
            ->assertSet('itemId', $product->id)
            ->set('name', 'Updated Product')
            ->call('store')
            ->assertSet('isModalOpen', false);
        
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Product',
        ]);
    }

    public function test_product_stores_all_required_fields_correctly(): void
    {
        $this->actingAs($this->adminUser);
        $brand = Brand::factory()->create();
        
        Livewire::test(Products::class)
            ->call('create')
            ->set('name', 'Complete Product')
            ->set('description', 'Product description')
            ->set('category_id', $this->category->id)
            ->set('brand_id', $brand->id)
            ->set('unit_id', $this->unit->id)
            ->set('purchase_price', 100.50)
            ->set('sale_price', 150.75)
            ->set('price_includes_tax', true)
            ->set('min_stock', 10)
            ->set('max_stock', 100)
            ->set('current_stock', 50)
            ->set('is_active', true)
            ->call('store');
        
        $this->assertDatabaseHas('products', [
            'name' => 'Complete Product',
            'description' => 'Product description',
            'category_id' => $this->category->id,
            'brand_id' => $brand->id,
            'unit_id' => $this->unit->id,
            'price_includes_tax' => true,
            'min_stock' => 10,
            'max_stock' => 100,
            'current_stock' => 50,
            'is_active' => true,
        ]);
    }

    public function test_product_name_is_required(): void
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(Products::class)
            ->call('create')
            ->set('name', '')
            ->set('category_id', $this->category->id)
            ->set('unit_id', $this->unit->id)
            ->call('store')
            ->assertHasErrors(['name']);
    }

    public function test_product_category_is_required(): void
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(Products::class)
            ->call('create')
            ->set('name', 'Test Product')
            ->set('category_id', null)
            ->set('unit_id', $this->unit->id)
            ->call('store')
            ->assertHasErrors(['category_id']);
    }

    public function test_product_unit_is_required(): void
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(Products::class)
            ->call('create')
            ->set('name', 'Test Product')
            ->set('category_id', $this->category->id)
            ->set('unit_id', null)
            ->call('store')
            ->assertHasErrors(['unit_id']);
    }

    public function test_product_sku_must_be_unique(): void
    {
        $this->actingAs($this->adminUser);
        
        Product::factory()->create([
            'sku' => 'UNIQUE-SKU',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);
        
        Livewire::test(Products::class)
            ->call('create')
            ->set('name', 'New Product')
            ->set('sku', 'UNIQUE-SKU')
            ->set('category_id', $this->category->id)
            ->set('unit_id', $this->unit->id)
            ->set('purchase_price', 100)
            ->set('sale_price', 150)
            ->set('current_stock', 10)
            ->call('store')
            ->assertHasErrors(['sku']);
    }

    // ==========================================
    // Property 3: Parent Deletion Protection
    // ==========================================

    public function test_product_without_children_can_be_deleted(): void
    {
        $this->actingAs($this->adminUser);
        
        $product = Product::factory()->create([
            'name' => 'To Delete',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);
        
        Livewire::test(Products::class)
            ->call('confirmDelete', $product->id)
            ->assertSet('isDeleteModalOpen', true)
            ->call('delete')
            ->assertSet('isDeleteModalOpen', false);
        
        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
        ]);
    }

    public function test_product_with_active_children_cannot_be_deleted(): void
    {
        $this->actingAs($this->adminUser);
        
        $product = Product::factory()->create([
            'name' => 'Product with Children',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);
        
        ProductChild::factory()->create([
            'product_id' => $product->id,
            'is_active' => true,
        ]);
        
        Livewire::test(Products::class)
            ->call('confirmDelete', $product->id)
            ->assertSet('isDeleteModalOpen', true)
            ->call('delete')
            ->assertSet('isDeleteModalOpen', false)
            ->assertDispatched('notify');
        
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
        ]);
    }

    public function test_product_with_only_inactive_children_can_be_deleted(): void
    {
        $this->actingAs($this->adminUser);
        
        $product = Product::factory()->create([
            'name' => 'Product with Inactive Children',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);
        
        ProductChild::factory()->create([
            'product_id' => $product->id,
            'is_active' => false,
        ]);
        
        Livewire::test(Products::class)
            ->call('confirmDelete', $product->id)
            ->call('delete');
        
        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
        ]);
    }

    // ==========================================
    // Search and Filter Tests
    // ==========================================

    public function test_products_can_be_searched_by_name(): void
    {
        $this->actingAs($this->adminUser);
        
        Product::factory()->create([
            'name' => 'Samsung Galaxy',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);
        Product::factory()->create([
            'name' => 'iPhone Pro',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);
        
        Livewire::test(Products::class)
            ->set('search', 'Samsung')
            ->assertSee('Samsung Galaxy')
            ->assertDontSee('iPhone Pro');
    }

    public function test_products_can_be_searched_by_sku(): void
    {
        $this->actingAs($this->adminUser);
        
        Product::factory()->create([
            'name' => 'Product A',
            'sku' => 'SKU-12345',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);
        Product::factory()->create([
            'name' => 'Product B',
            'sku' => 'SKU-67890',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);
        
        Livewire::test(Products::class)
            ->set('search', 'SKU-12345')
            ->assertSee('Product A')
            ->assertDontSee('Product B');
    }

    public function test_products_can_be_filtered_by_category(): void
    {
        $this->actingAs($this->adminUser);
        
        $category2 = Category::factory()->create();
        
        Product::factory()->create([
            'name' => 'Category 1 Product',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);
        Product::factory()->create([
            'name' => 'Category 2 Product',
            'category_id' => $category2->id,
            'unit_id' => $this->unit->id,
        ]);
        
        Livewire::test(Products::class)
            ->set('filterCategory', $this->category->id)
            ->assertSee('Category 1 Product')
            ->assertDontSee('Category 2 Product');
    }

    public function test_products_can_be_filtered_by_brand(): void
    {
        $this->actingAs($this->adminUser);
        
        $brand1 = Brand::factory()->create();
        $brand2 = Brand::factory()->create();
        
        Product::factory()->create([
            'name' => 'Brand 1 Product',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'brand_id' => $brand1->id,
        ]);
        Product::factory()->create([
            'name' => 'Brand 2 Product',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'brand_id' => $brand2->id,
        ]);
        
        Livewire::test(Products::class)
            ->set('filterBrand', $brand1->id)
            ->assertSee('Brand 1 Product')
            ->assertDontSee('Brand 2 Product');
    }

    public function test_products_can_be_filtered_by_status(): void
    {
        $this->actingAs($this->adminUser);
        
        Product::factory()->create([
            'name' => 'Active Product',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'is_active' => true,
        ]);
        Product::factory()->create([
            'name' => 'Inactive Product',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'is_active' => false,
        ]);
        
        Livewire::test(Products::class)
            ->set('filterStatus', '1')
            ->assertSee('Active Product')
            ->assertDontSee('Inactive Product');
    }

    // ==========================================
    // Toggle Status Tests
    // ==========================================

    public function test_product_status_can_be_toggled(): void
    {
        $this->actingAs($this->adminUser);
        
        $product = Product::factory()->create([
            'is_active' => true,
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);
        
        Livewire::test(Products::class)
            ->call('toggleStatus', $product->id);
        
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'is_active' => false,
        ]);
    }

    public function test_deactivating_parent_deactivates_all_children(): void
    {
        $this->actingAs($this->adminUser);
        
        $product = Product::factory()->create([
            'is_active' => true,
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);
        
        $child1 = ProductChild::factory()->create([
            'product_id' => $product->id,
            'is_active' => true,
        ]);
        $child2 = ProductChild::factory()->create([
            'product_id' => $product->id,
            'is_active' => true,
        ]);
        
        Livewire::test(Products::class)
            ->call('toggleStatus', $product->id);
        
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('product_children', [
            'id' => $child1->id,
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('product_children', [
            'id' => $child2->id,
            'is_active' => false,
        ]);
    }

    // ==========================================
    // Activity Logging Tests
    // Property 13: Activity Log Creation
    // *For any* product create, update, or delete operation, an activity log entry 
    // should be created with user_id, action type, and timestamp.
    // **Validates: Requirements 9.1, 9.2**
    // ==========================================

    public function test_activity_log_is_created_on_product_creation(): void
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(Products::class)
            ->call('create')
            ->set('name', 'Logged Product')
            ->set('category_id', $this->category->id)
            ->set('unit_id', $this->unit->id)
            ->set('purchase_price', 100)
            ->set('sale_price', 150)
            ->set('current_stock', 10)
            ->call('store');
        
        $this->assertDatabaseHas('activity_logs', [
            'module' => 'products',
            'action' => 'create',
            'user_id' => $this->adminUser->id,
        ]);
    }

    public function test_activity_log_is_created_on_product_update(): void
    {
        $this->actingAs($this->adminUser);
        
        $product = Product::factory()->create([
            'name' => 'Original',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);
        
        Livewire::test(Products::class)
            ->call('edit', $product->id)
            ->set('name', 'Updated')
            ->call('store');
        
        $this->assertDatabaseHas('activity_logs', [
            'module' => 'products',
            'action' => 'update',
            'user_id' => $this->adminUser->id,
        ]);
    }

    public function test_activity_log_is_created_on_product_deletion(): void
    {
        $this->actingAs($this->adminUser);
        
        $product = Product::factory()->create([
            'name' => 'To Delete',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);
        
        Livewire::test(Products::class)
            ->call('confirmDelete', $product->id)
            ->call('delete');
        
        $this->assertDatabaseHas('activity_logs', [
            'module' => 'products',
            'action' => 'delete',
            'user_id' => $this->adminUser->id,
        ]);
    }

    /**
     * Property 13: Activity Log Creation
     * *For any* product create, update, or delete operation, an activity log entry 
     * should be created with user_id, action type, and timestamp.
     * **Validates: Requirements 9.1, 9.2**
     */
    public function test_property_13_activity_log_contains_required_fields_on_create(): void
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(Products::class)
            ->call('create')
            ->set('name', 'Property 13 Test Product')
            ->set('category_id', $this->category->id)
            ->set('unit_id', $this->unit->id)
            ->set('purchase_price', 100)
            ->set('sale_price', 150)
            ->set('current_stock', 10)
            ->call('store');
        
        $log = \App\Models\ActivityLog::where('module', 'products')
            ->where('action', 'create')
            ->where('description', 'like', "%Property 13 Test Product%")
            ->first();
        
        $this->assertNotNull($log, 'Activity log should be created');
        $this->assertEquals($this->adminUser->id, $log->user_id, 'Log should contain user_id');
        $this->assertEquals('create', $log->action, 'Log should contain action type');
        $this->assertNotNull($log->created_at, 'Log should contain timestamp');
        $this->assertNotNull($log->new_values, 'Create log should contain new_values');
        $this->assertNull($log->old_values, 'Create log should not contain old_values');
    }

    /**
     * Property 13: Activity Log Creation - Update Operation
     * **Validates: Requirements 9.1, 9.2**
     */
    public function test_property_13_activity_log_contains_required_fields_on_update(): void
    {
        $this->actingAs($this->adminUser);
        
        $product = Product::factory()->create([
            'name' => 'Original Name',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);
        
        Livewire::test(Products::class)
            ->call('edit', $product->id)
            ->set('name', 'Updated Name Property 13')
            ->call('store');
        
        $log = \App\Models\ActivityLog::where('module', 'products')
            ->where('action', 'update')
            ->where('model_id', $product->id)
            ->first();
        
        $this->assertNotNull($log, 'Activity log should be created');
        $this->assertEquals($this->adminUser->id, $log->user_id, 'Log should contain user_id');
        $this->assertEquals('update', $log->action, 'Log should contain action type');
        $this->assertNotNull($log->created_at, 'Log should contain timestamp');
        $this->assertNotNull($log->old_values, 'Update log should contain old_values');
        $this->assertNotNull($log->new_values, 'Update log should contain new_values');
        $this->assertEquals('Original Name', $log->old_values['name'], 'Old values should contain original name');
    }

    /**
     * Property 13: Activity Log Creation - Delete Operation
     * **Validates: Requirements 9.1, 9.2**
     */
    public function test_property_13_activity_log_contains_required_fields_on_delete(): void
    {
        $this->actingAs($this->adminUser);
        
        $product = Product::factory()->create([
            'name' => 'To Delete Property 13',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);
        $productId = $product->id;
        
        Livewire::test(Products::class)
            ->call('confirmDelete', $product->id)
            ->call('delete');
        
        $log = \App\Models\ActivityLog::where('module', 'products')
            ->where('action', 'delete')
            ->where('model_id', $productId)
            ->first();
        
        $this->assertNotNull($log, 'Activity log should be created');
        $this->assertEquals($this->adminUser->id, $log->user_id, 'Log should contain user_id');
        $this->assertEquals('delete', $log->action, 'Log should contain action type');
        $this->assertNotNull($log->created_at, 'Log should contain timestamp');
        $this->assertNotNull($log->old_values, 'Delete log should contain old_values');
        $this->assertNull($log->new_values, 'Delete log should not contain new_values');
        $this->assertEquals('To Delete Property 13', $log->old_values['name'], 'Old values should contain deleted product name');
    }

    // ==========================================
    // Search Including Children Tests
    // ==========================================

    public function test_search_finds_parent_when_child_matches(): void
    {
        $this->actingAs($this->adminUser);
        
        $product = Product::factory()->create([
            'name' => 'Parent Product',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);
        
        ProductChild::factory()->create([
            'product_id' => $product->id,
            'name' => 'Child Variant XYZ',
            'barcode' => '1234567890123',
        ]);
        
        Livewire::test(Products::class)
            ->set('search', 'XYZ')
            ->assertSee('Parent Product');
    }

    public function test_search_finds_parent_when_child_barcode_matches(): void
    {
        $this->actingAs($this->adminUser);
        
        $product = Product::factory()->create([
            'name' => 'Barcode Parent',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);
        
        ProductChild::factory()->create([
            'product_id' => $product->id,
            'barcode' => 'UNIQUE-BARCODE-123',
        ]);
        
        Livewire::test(Products::class)
            ->set('search', 'UNIQUE-BARCODE')
            ->assertSee('Barcode Parent');
    }

    // ==========================================
    // Child Product Tests (Task 5.5)
    // Property 4: Child Product Inheritance
    // Property 5: Child Product Optional Fields
    // Property 6: Parent-Child Cascade Delete
    // Property 10: Parent Deactivation Cascade
    // Requirements: 2.1, 2.2, 2.4, 2.5, 7.2
    // ==========================================

    public function test_user_can_create_child_product(): void
    {
        $this->actingAs($this->adminUser);
        
        $product = Product::factory()->create([
            'name' => 'Parent Product',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'purchase_price' => 10,
        ]);
        
        Livewire::test(Products::class)
            ->call('createChild', $product->id)
            ->assertSet('isChildModalOpen', true)
            ->assertSet('childProductId', $product->id)
            ->set('childName', 'Child Variant')
            ->set('childUnitQuantity', 6)
            ->set('childSalePrice', 75)
            ->call('storeChild')
            ->assertSet('isChildModalOpen', false);
        
        $this->assertDatabaseHas('product_children', [
            'product_id' => $product->id,
            'name' => 'Child Variant',
            'unit_quantity' => 6,
            'sale_price' => 75,
        ]);
    }

    public function test_user_can_edit_child_product(): void
    {
        $this->actingAs($this->adminUser);
        
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);
        
        $child = ProductChild::factory()->create([
            'product_id' => $product->id,
            'name' => 'Original Child',
        ]);
        
        Livewire::test(Products::class)
            ->call('editChild', $child->id)
            ->assertSet('isChildModalOpen', true)
            ->assertSet('childId', $child->id)
            ->set('childName', 'Updated Child')
            ->call('storeChild')
            ->assertSet('isChildModalOpen', false);
        
        $this->assertDatabaseHas('product_children', [
            'id' => $child->id,
            'name' => 'Updated Child',
        ]);
    }

    public function test_child_product_name_is_required(): void
    {
        $this->actingAs($this->adminUser);
        
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);
        
        Livewire::test(Products::class)
            ->call('createChild', $product->id)
            ->set('childName', '')
            ->set('childUnitQuantity', 1)
            ->set('childSalePrice', 75)
            ->call('storeChild')
            ->assertHasErrors(['childName']);
    }

    public function test_child_product_sku_must_be_unique(): void
    {
        $this->actingAs($this->adminUser);
        
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);
        
        ProductChild::factory()->create([
            'product_id' => $product->id,
            'sku' => 'UNIQUE-CHILD-SKU',
        ]);
        
        Livewire::test(Products::class)
            ->call('createChild', $product->id)
            ->set('childName', 'New Child')
            ->set('childSku', 'UNIQUE-CHILD-SKU')
            ->set('childUnitQuantity', 1)
            ->set('childSalePrice', 75)
            ->call('storeChild')
            ->assertHasErrors(['childSku']);
    }

    public function test_child_product_barcode_must_be_unique(): void
    {
        $this->actingAs($this->adminUser);
        
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);
        
        ProductChild::factory()->create([
            'product_id' => $product->id,
            'barcode' => '1234567890123',
        ]);
        
        Livewire::test(Products::class)
            ->call('createChild', $product->id)
            ->set('childName', 'New Child')
            ->set('childBarcode', '1234567890123')
            ->set('childUnitQuantity', 1)
            ->set('childSalePrice', 75)
            ->call('storeChild')
            ->assertHasErrors(['childBarcode']);
    }

    /**
     * Property 4: Child Product Inheritance
     * *For any* child product created, it should inherit category_id, subcategory_id, 
     * brand_id, and tax_id from its parent product.
     * **Validates: Requirements 2.1, 2.2**
     */
    public function test_child_product_inherits_category_from_parent(): void
    {
        $this->actingAs($this->adminUser);
        
        $brand = Brand::factory()->create();
        $tax = \App\Models\Tax::factory()->create();
        $subcategory = \App\Models\Subcategory::factory()->create(['category_id' => $this->category->id]);
        
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'subcategory_id' => $subcategory->id,
            'brand_id' => $brand->id,
            'tax_id' => $tax->id,
            'unit_id' => $this->unit->id,
        ]);
        
        Livewire::test(Products::class)
            ->call('createChild', $product->id)
            ->set('childName', 'Inherited Child')
            ->set('childUnitQuantity', 1)
            ->set('childSalePrice', 75)
            ->call('storeChild');
        
        $child = ProductChild::where('name', 'Inherited Child')->first();
        
        // Verify inheritance through accessors
        $this->assertEquals($this->category->id, $child->category_id);
        $this->assertEquals($subcategory->id, $child->subcategory_id);
        $this->assertEquals($brand->id, $child->brand_id);
        $this->assertEquals($tax->id, $child->tax_id);
    }

    /**
     * Property 5: Child Product Optional Fields
     * *For any* child product, it should accept optional fields (presentation_id, color_id, 
     * product_model_id, size, weight, imei) based on field configuration.
     * **Validates: Requirements 2.4**
     */
    public function test_child_product_accepts_optional_fields(): void
    {
        $this->actingAs($this->adminUser);
        
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);
        
        $presentation = \App\Models\Presentation::factory()->create();
        $color = \App\Models\Color::factory()->create();
        $productModel = \App\Models\ProductModel::factory()->create();
        
        Livewire::test(Products::class)
            ->call('createChild', $product->id)
            ->set('childName', 'Full Featured Child')
            ->set('childUnitQuantity', 1)
            ->set('childSalePrice', 150)
            ->set('childPresentationId', $presentation->id)
            ->set('childColorId', $color->id)
            ->set('childProductModelId', $productModel->id)
            ->set('childSize', 'XL')
            ->set('childWeight', 2.5)
            ->set('childImei', '123456789012345')
            ->call('storeChild');
        
        $this->assertDatabaseHas('product_children', [
            'product_id' => $product->id,
            'name' => 'Full Featured Child',
            'presentation_id' => $presentation->id,
            'color_id' => $color->id,
            'product_model_id' => $productModel->id,
            'size' => 'XL',
            'imei' => '123456789012345',
        ]);
    }

    public function test_child_product_can_be_created_without_optional_fields(): void
    {
        $this->actingAs($this->adminUser);
        
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);
        
        Livewire::test(Products::class)
            ->call('createChild', $product->id)
            ->set('childName', 'Minimal Child')
            ->set('childUnitQuantity', 1)
            ->set('childSalePrice', 75)
            ->call('storeChild');
        
        $this->assertDatabaseHas('product_children', [
            'product_id' => $product->id,
            'name' => 'Minimal Child',
            'presentation_id' => null,
            'color_id' => null,
            'product_model_id' => null,
            'size' => null,
            'weight' => null,
            'imei' => null,
        ]);
    }

    /**
     * Property 6: Parent-Child Cascade Delete
     * *For any* parent product deletion (when allowed), all associated child products 
     * should be deleted or deactivated.
     * **Validates: Requirements 2.5**
     */
    public function test_deleting_parent_deletes_all_inactive_children(): void
    {
        $this->actingAs($this->adminUser);
        
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);
        
        $child1 = ProductChild::factory()->create([
            'product_id' => $product->id,
            'is_active' => false,
        ]);
        $child2 = ProductChild::factory()->create([
            'product_id' => $product->id,
            'is_active' => false,
        ]);
        
        Livewire::test(Products::class)
            ->call('confirmDelete', $product->id)
            ->call('delete');
        
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        $this->assertDatabaseMissing('product_children', ['id' => $child1->id]);
        $this->assertDatabaseMissing('product_children', ['id' => $child2->id]);
    }

    public function test_child_product_can_be_deleted(): void
    {
        $this->actingAs($this->adminUser);
        
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);
        
        $child = ProductChild::factory()->create([
            'product_id' => $product->id,
            'name' => 'To Delete Child',
        ]);
        
        Livewire::test(Products::class)
            ->call('confirmDeleteChild', $child->id)
            ->assertSet('isChildDeleteModalOpen', true)
            ->call('deleteChild')
            ->assertSet('isChildDeleteModalOpen', false);
        
        $this->assertDatabaseMissing('product_children', ['id' => $child->id]);
    }

    /**
     * Property 10: Parent Deactivation Cascade
     * *For any* parent product that is deactivated, all its child products should also be deactivated.
     * **Validates: Requirements 7.1, 7.2**
     */
    public function test_deactivating_parent_cascades_to_multiple_children(): void
    {
        $this->actingAs($this->adminUser);
        
        $product = Product::factory()->create([
            'is_active' => true,
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);
        
        $children = ProductChild::factory()->count(5)->create([
            'product_id' => $product->id,
            'is_active' => true,
        ]);
        
        Livewire::test(Products::class)
            ->call('toggleStatus', $product->id);
        
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'is_active' => false,
        ]);
        
        foreach ($children as $child) {
            $this->assertDatabaseHas('product_children', [
                'id' => $child->id,
                'is_active' => false,
            ]);
        }
    }

    public function test_reactivating_parent_does_not_reactivate_children(): void
    {
        $this->actingAs($this->adminUser);
        
        $product = Product::factory()->create([
            'is_active' => false,
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);
        
        $child = ProductChild::factory()->create([
            'product_id' => $product->id,
            'is_active' => false,
        ]);
        
        Livewire::test(Products::class)
            ->call('toggleStatus', $product->id);
        
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'is_active' => true,
        ]);
        
        // Children should remain inactive
        $this->assertDatabaseHas('product_children', [
            'id' => $child->id,
            'is_active' => false,
        ]);
    }

    public function test_child_status_can_be_toggled_independently(): void
    {
        $this->actingAs($this->adminUser);
        
        $product = Product::factory()->create([
            'is_active' => true,
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);
        
        $child = ProductChild::factory()->create([
            'product_id' => $product->id,
            'is_active' => true,
        ]);
        
        Livewire::test(Products::class)
            ->call('toggleChildStatus', $child->id);
        
        $this->assertDatabaseHas('product_children', [
            'id' => $child->id,
            'is_active' => false,
        ]);
        
        // Parent should remain active
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'is_active' => true,
        ]);
    }

    // ==========================================
    // Activity Logging for Child Products
    // Property 13: Activity Log Creation (Child Products)
    // **Validates: Requirements 9.1, 9.2**
    // ==========================================

    public function test_activity_log_is_created_on_child_creation(): void
    {
        $this->actingAs($this->adminUser);
        
        $product = Product::factory()->create([
            'name' => 'Parent for Log',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);
        
        Livewire::test(Products::class)
            ->call('createChild', $product->id)
            ->set('childName', 'Logged Child')
            ->set('childUnitQuantity', 1)
            ->set('childSalePrice', 75)
            ->call('storeChild');
        
        $this->assertDatabaseHas('activity_logs', [
            'module' => 'product_children',
            'action' => 'create',
            'user_id' => $this->adminUser->id,
        ]);
    }

    public function test_activity_log_is_created_on_child_update(): void
    {
        $this->actingAs($this->adminUser);
        
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);
        
        $child = ProductChild::factory()->create([
            'product_id' => $product->id,
            'name' => 'Original Child',
        ]);
        
        Livewire::test(Products::class)
            ->call('editChild', $child->id)
            ->set('childName', 'Updated Child')
            ->call('storeChild');
        
        $this->assertDatabaseHas('activity_logs', [
            'module' => 'product_children',
            'action' => 'update',
            'user_id' => $this->adminUser->id,
        ]);
    }

    public function test_activity_log_is_created_on_child_deletion(): void
    {
        $this->actingAs($this->adminUser);
        
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);
        
        $child = ProductChild::factory()->create([
            'product_id' => $product->id,
            'name' => 'To Delete Child',
        ]);
        
        Livewire::test(Products::class)
            ->call('confirmDeleteChild', $child->id)
            ->call('deleteChild');
        
        $this->assertDatabaseHas('activity_logs', [
            'module' => 'product_children',
            'action' => 'delete',
            'user_id' => $this->adminUser->id,
        ]);
    }

    /**
     * Property 13: Activity Log Creation - Child Product Create
     * *For any* product create, update, or delete operation, an activity log entry 
     * should be created with user_id, action type, and timestamp.
     * **Validates: Requirements 9.1, 9.2**
     */
    public function test_property_13_child_activity_log_contains_required_fields_on_create(): void
    {
        $this->actingAs($this->adminUser);
        
        $product = Product::factory()->create([
            'name' => 'Parent for Child Log',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);
        
        Livewire::test(Products::class)
            ->call('createChild', $product->id)
            ->set('childName', 'Property 13 Child Test')
            ->set('childUnitQuantity', 1)
            ->set('childSalePrice', 75)
            ->call('storeChild');
        
        $log = \App\Models\ActivityLog::where('module', 'product_children')
            ->where('action', 'create')
            ->where('description', 'like', "%Property 13 Child Test%")
            ->first();
        
        $this->assertNotNull($log, 'Activity log should be created for child');
        $this->assertEquals($this->adminUser->id, $log->user_id, 'Log should contain user_id');
        $this->assertEquals('create', $log->action, 'Log should contain action type');
        $this->assertNotNull($log->created_at, 'Log should contain timestamp');
        $this->assertNotNull($log->new_values, 'Create log should contain new_values');
    }

    /**
     * Property 13: Activity Log Creation - Child Product Update
     * **Validates: Requirements 9.1, 9.2**
     */
    public function test_property_13_child_activity_log_contains_required_fields_on_update(): void
    {
        $this->actingAs($this->adminUser);
        
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);
        
        $child = ProductChild::factory()->create([
            'product_id' => $product->id,
            'name' => 'Original Child Name',
        ]);
        
        Livewire::test(Products::class)
            ->call('editChild', $child->id)
            ->set('childName', 'Updated Child Property 13')
            ->call('storeChild');
        
        $log = \App\Models\ActivityLog::where('module', 'product_children')
            ->where('action', 'update')
            ->where('model_id', $child->id)
            ->first();
        
        $this->assertNotNull($log, 'Activity log should be created for child update');
        $this->assertEquals($this->adminUser->id, $log->user_id, 'Log should contain user_id');
        $this->assertEquals('update', $log->action, 'Log should contain action type');
        $this->assertNotNull($log->created_at, 'Log should contain timestamp');
        $this->assertNotNull($log->old_values, 'Update log should contain old_values');
        $this->assertNotNull($log->new_values, 'Update log should contain new_values');
    }

    /**
     * Property 13: Activity Log Creation - Child Product Delete
     * **Validates: Requirements 9.1, 9.2**
     */
    public function test_property_13_child_activity_log_contains_required_fields_on_delete(): void
    {
        $this->actingAs($this->adminUser);
        
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);
        
        $child = ProductChild::factory()->create([
            'product_id' => $product->id,
            'name' => 'To Delete Child Property 13',
        ]);
        $childId = $child->id;
        
        Livewire::test(Products::class)
            ->call('confirmDeleteChild', $child->id)
            ->call('deleteChild');
        
        $log = \App\Models\ActivityLog::where('module', 'product_children')
            ->where('action', 'delete')
            ->where('model_id', $childId)
            ->first();
        
        $this->assertNotNull($log, 'Activity log should be created for child delete');
        $this->assertEquals($this->adminUser->id, $log->user_id, 'Log should contain user_id');
        $this->assertEquals('delete', $log->action, 'Log should contain action type');
        $this->assertNotNull($log->created_at, 'Log should contain timestamp');
        $this->assertNotNull($log->old_values, 'Delete log should contain old_values');
    }

    public function test_user_without_permission_cannot_create_child(): void
    {
        $userWithoutPermission = User::factory()->create();
        $limitedRole = Role::factory()->create(['name' => 'limited_child']);
        $userWithoutPermission->roles()->attach($limitedRole->id);
        
        $this->actingAs($userWithoutPermission);
        
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);
        
        Livewire::test(Products::class)
            ->call('createChild', $product->id)
            ->assertSet('isChildModalOpen', false)
            ->assertDispatched('notify');
    }

    // ==========================================
    // Task 9.4: Search and Filter Tests
    // Property 11: Inactive Product Search Exclusion
    // Requirements: 7.3, 8.1, 8.2
    // ==========================================

    public function test_products_can_be_searched_by_description(): void
    {
        $this->actingAs($this->adminUser);
        
        Product::factory()->create([
            'name' => 'Product A',
            'description' => 'This is a unique description for testing',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);
        Product::factory()->create([
            'name' => 'Product B',
            'description' => 'Different content here',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);
        
        Livewire::test(Products::class)
            ->set('search', 'unique description')
            ->assertSee('Product A')
            ->assertDontSee('Product B');
    }

    public function test_products_can_be_filtered_by_multiple_criteria(): void
    {
        $this->actingAs($this->adminUser);
        
        $category2 = Category::factory()->create();
        $brand1 = Brand::factory()->create();
        $brand2 = Brand::factory()->create();
        
        // Product matching both filters
        Product::factory()->create([
            'name' => 'Matching Product',
            'category_id' => $this->category->id,
            'brand_id' => $brand1->id,
            'unit_id' => $this->unit->id,
            'is_active' => true,
        ]);
        
        // Product matching only category
        Product::factory()->create([
            'name' => 'Category Only Product',
            'category_id' => $this->category->id,
            'brand_id' => $brand2->id,
            'unit_id' => $this->unit->id,
            'is_active' => true,
        ]);
        
        // Product matching only brand
        Product::factory()->create([
            'name' => 'Brand Only Product',
            'category_id' => $category2->id,
            'brand_id' => $brand1->id,
            'unit_id' => $this->unit->id,
            'is_active' => true,
        ]);
        
        Livewire::test(Products::class)
            ->set('filterCategory', $this->category->id)
            ->set('filterBrand', $brand1->id)
            ->assertSee('Matching Product')
            ->assertDontSee('Category Only Product')
            ->assertDontSee('Brand Only Product');
    }

    public function test_search_and_filters_can_be_combined(): void
    {
        $this->actingAs($this->adminUser);
        
        $brand = Brand::factory()->create();
        
        // Product matching search and filter
        Product::factory()->create([
            'name' => 'Samsung Galaxy S24',
            'category_id' => $this->category->id,
            'brand_id' => $brand->id,
            'unit_id' => $this->unit->id,
        ]);
        
        // Product matching search but not filter
        Product::factory()->create([
            'name' => 'Samsung Galaxy A54',
            'category_id' => $this->category->id,
            'brand_id' => null,
            'unit_id' => $this->unit->id,
        ]);
        
        // Product matching filter but not search
        Product::factory()->create([
            'name' => 'iPhone 15 Pro',
            'category_id' => $this->category->id,
            'brand_id' => $brand->id,
            'unit_id' => $this->unit->id,
        ]);
        
        Livewire::test(Products::class)
            ->set('search', 'Samsung')
            ->set('filterBrand', $brand->id)
            ->assertSee('Samsung Galaxy S24')
            ->assertDontSee('Samsung Galaxy A54')
            ->assertDontSee('iPhone 15 Pro');
    }

    public function test_inactive_products_excluded_when_status_filter_active(): void
    {
        $this->actingAs($this->adminUser);
        
        Product::factory()->create([
            'name' => 'Active Product',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'is_active' => true,
        ]);
        
        Product::factory()->create([
            'name' => 'Inactive Product',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'is_active' => false,
        ]);
        
        Livewire::test(Products::class)
            ->set('filterStatus', '1')
            ->assertSee('Active Product')
            ->assertDontSee('Inactive Product');
    }

    public function test_only_inactive_products_shown_when_status_filter_inactive(): void
    {
        $this->actingAs($this->adminUser);
        
        Product::factory()->create([
            'name' => 'Active Product',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'is_active' => true,
        ]);
        
        Product::factory()->create([
            'name' => 'Inactive Product',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'is_active' => false,
        ]);
        
        Livewire::test(Products::class)
            ->set('filterStatus', '0')
            ->assertSee('Inactive Product')
            ->assertDontSee('Active Product');
    }

    public function test_search_finds_parent_when_child_sku_matches(): void
    {
        $this->actingAs($this->adminUser);
        
        $product = Product::factory()->create([
            'name' => 'Parent With SKU Child',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
        ]);
        
        ProductChild::factory()->create([
            'product_id' => $product->id,
            'name' => 'Child Variant',
            'sku' => 'CHILD-SKU-UNIQUE-123',
        ]);
        
        Livewire::test(Products::class)
            ->set('search', 'CHILD-SKU-UNIQUE')
            ->assertSee('Parent With SKU Child');
    }

    public function test_clear_filters_resets_all_filters(): void
    {
        $this->actingAs($this->adminUser);
        
        $brand = Brand::factory()->create();
        
        Livewire::test(Products::class)
            ->set('search', 'test')
            ->set('filterCategory', $this->category->id)
            ->set('filterBrand', $brand->id)
            ->set('filterStatus', '1')
            ->call('clearFilters')
            ->assertSet('search', '')
            ->assertSet('filterCategory', null)
            ->assertSet('filterBrand', null)
            ->assertSet('filterStatus', null);
    }

    /**
     * Property 11: Inactive Product Search Exclusion
     * *For any* search query with active-only filter, inactive products (parent or child) 
     * should not appear in results.
     * **Validates: Requirements 7.3, 8.1, 8.2**
     */
    public function test_pos_search_excludes_inactive_products(): void
    {
        // Create active product with active child
        $activeProduct = Product::factory()->create([
            'name' => 'Active Sellable Product',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'is_active' => true,
        ]);
        
        ProductChild::factory()->create([
            'product_id' => $activeProduct->id,
            'name' => 'Active Child',
            'is_active' => true,
        ]);
        
        // Create inactive product
        $inactiveProduct = Product::factory()->create([
            'name' => 'Inactive Sellable Product',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'is_active' => false,
        ]);
        
        ProductChild::factory()->create([
            'product_id' => $inactiveProduct->id,
            'name' => 'Child of Inactive',
            'is_active' => true,
        ]);
        
        // Create active product with only inactive children
        $productWithInactiveChildren = Product::factory()->create([
            'name' => 'Product With Inactive Children',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'is_active' => true,
        ]);
        
        ProductChild::factory()->create([
            'product_id' => $productWithInactiveChildren->id,
            'name' => 'Inactive Child Only',
            'is_active' => false,
        ]);
        
        // Test POS search scope - should only return active products with active children
        $results = Product::posSearch('Sellable')->get();
        
        $this->assertTrue($results->contains('id', $activeProduct->id));
        $this->assertFalse($results->contains('id', $inactiveProduct->id));
        $this->assertFalse($results->contains('id', $productWithInactiveChildren->id));
    }

    public function test_pos_search_finds_by_child_barcode_only_active(): void
    {
        // Create active product with active child
        $activeProduct = Product::factory()->create([
            'name' => 'Active Product',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'is_active' => true,
        ]);
        
        ProductChild::factory()->create([
            'product_id' => $activeProduct->id,
            'name' => 'Active Child',
            'barcode' => 'BARCODE-ACTIVE-123',
            'is_active' => true,
        ]);
        
        // Create active product with inactive child
        $productWithInactiveChild = Product::factory()->create([
            'name' => 'Product With Inactive Child',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'is_active' => true,
        ]);
        
        ProductChild::factory()->create([
            'product_id' => $productWithInactiveChild->id,
            'name' => 'Inactive Child',
            'barcode' => 'BARCODE-INACTIVE-456',
            'is_active' => false,
        ]);
        
        // Search by active child barcode - should find
        $resultsActive = Product::posSearch('BARCODE-ACTIVE')->get();
        $this->assertTrue($resultsActive->contains('id', $activeProduct->id));
        
        // Search by inactive child barcode - should not find
        $resultsInactive = Product::posSearch('BARCODE-INACTIVE')->get();
        $this->assertFalse($resultsInactive->contains('id', $productWithInactiveChild->id));
    }

    public function test_for_pos_search_scope_excludes_products_without_active_children(): void
    {
        // Product with active children
        $productWithActiveChildren = Product::factory()->create([
            'name' => 'Has Active Children',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'is_active' => true,
        ]);
        
        ProductChild::factory()->create([
            'product_id' => $productWithActiveChildren->id,
            'is_active' => true,
        ]);
        
        // Product with no children
        $productWithNoChildren = Product::factory()->create([
            'name' => 'No Children',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'is_active' => true,
        ]);
        
        // Product with only inactive children
        $productWithInactiveChildren = Product::factory()->create([
            'name' => 'Only Inactive Children',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'is_active' => true,
        ]);
        
        ProductChild::factory()->create([
            'product_id' => $productWithInactiveChildren->id,
            'is_active' => false,
        ]);
        
        $results = Product::forPosSearch()->get();
        
        $this->assertTrue($results->contains('id', $productWithActiveChildren->id));
        $this->assertFalse($results->contains('id', $productWithNoChildren->id));
        $this->assertFalse($results->contains('id', $productWithInactiveChildren->id));
    }

    public function test_active_scope_returns_only_active_products(): void
    {
        Product::factory()->create([
            'name' => 'Active Product',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'is_active' => true,
        ]);
        
        Product::factory()->create([
            'name' => 'Inactive Product',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'is_active' => false,
        ]);
        
        $results = Product::active()->get();
        
        $this->assertEquals(1, $results->count());
        $this->assertEquals('Active Product', $results->first()->name);
    }
}

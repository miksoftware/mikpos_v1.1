<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Branches;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Municipality;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BranchesTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Role $superAdminRole;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->superAdminRole = Role::factory()->superAdmin()->create();
        $this->adminUser = User::factory()->create();
        $this->adminUser->roles()->attach($this->superAdminRole->id);
    }

    public function test_branches_page_can_be_rendered(): void
    {
        $this->actingAs($this->adminUser);
        
        $response = $this->get('/branches');
        
        $response->assertStatus(200);
        $response->assertSeeLivewire(Branches::class);
    }

    public function test_branches_page_displays_branches_list(): void
    {
        $this->actingAs($this->adminUser);
        
        $branch = Branch::factory()->create(['name' => 'Test Branch']);
        
        Livewire::test(Branches::class)
            ->assertSee('Test Branch');
    }

    public function test_branches_can_be_searched(): void
    {
        $this->actingAs($this->adminUser);
        
        $branch1 = Branch::factory()->create(['name' => 'Main Branch']);
        $branch2 = Branch::factory()->create(['name' => 'Secondary Branch']);
        
        Livewire::test(Branches::class)
            ->set('search', 'Main')
            ->assertSee('Main Branch')
            ->assertDontSee('Secondary Branch');
    }

    public function test_user_with_permission_can_create_branch(): void
    {
        $this->actingAs($this->adminUser);
        
        $department = Department::factory()->create();
        $municipality = Municipality::factory()->create(['department_id' => $department->id]);
        
        Livewire::test(Branches::class)
            ->call('create')
            ->assertSet('isModalOpen', true)
            ->set('code', 'SUC001')
            ->set('name', 'New Branch')
            ->set('department_id', $department->id)
            ->set('municipality_id', $municipality->id)
            ->call('store')
            ->assertSet('isModalOpen', false);
        
        $this->assertDatabaseHas('branches', [
            'code' => 'SUC001',
            'name' => 'New Branch',
            'department_id' => $department->id,
            'municipality_id' => $municipality->id,
        ]);
    }

    public function test_user_without_permission_cannot_create_branch(): void
    {
        $userWithoutPermission = User::factory()->create();
        $limitedRole = Role::factory()->create(['name' => 'limited']);
        $userWithoutPermission->roles()->attach($limitedRole->id);
        
        $this->actingAs($userWithoutPermission);
        
        Livewire::test(Branches::class)
            ->call('create')
            ->assertSet('isModalOpen', false)
            ->assertDispatched('notify');
    }

    public function test_user_with_permission_can_edit_branch(): void
    {
        $this->actingAs($this->adminUser);
        
        $department = Department::factory()->create();
        $municipality = Municipality::factory()->create(['department_id' => $department->id]);
        $branch = Branch::factory()->create([
            'code' => 'SUC001',
            'name' => 'Original Branch',
        ]);
        
        Livewire::test(Branches::class)
            ->call('edit', $branch->id)
            ->assertSet('isModalOpen', true)
            ->assertSet('branchId', $branch->id)
            ->set('name', 'Updated Branch')
            ->set('department_id', $department->id)
            ->set('municipality_id', $municipality->id)
            ->call('store')
            ->assertSet('isModalOpen', false);
        
        $this->assertDatabaseHas('branches', [
            'id' => $branch->id,
            'name' => 'Updated Branch',
        ]);
    }

    public function test_user_with_permission_can_delete_branch(): void
    {
        $this->actingAs($this->adminUser);
        
        $branch = Branch::factory()->create(['name' => 'To Delete']);
        
        Livewire::test(Branches::class)
            ->call('confirmDelete', $branch->id)
            ->assertSet('isDeleteModalOpen', true)
            ->call('delete')
            ->assertSet('isDeleteModalOpen', false);
        
        $this->assertDatabaseHas('branches', [
            'id' => $branch->id,
            'is_active' => false,
        ]);
    }

    public function test_branch_status_can_be_toggled(): void
    {
        $this->actingAs($this->adminUser);
        
        $branch = Branch::factory()->create(['is_active' => true]);
        
        Livewire::test(Branches::class)
            ->call('toggleStatus', $branch->id);
        
        $this->assertDatabaseHas('branches', [
            'id' => $branch->id,
            'is_active' => false,
        ]);
    }

    public function test_branch_code_must_be_unique(): void
    {
        $this->actingAs($this->adminUser);
        
        Branch::factory()->create(['code' => 'SUC001']);
        
        Livewire::test(Branches::class)
            ->call('create')
            ->set('code', 'SUC001')
            ->set('name', 'Another Branch')
            ->call('store')
            ->assertHasErrors(['code']);
    }

    public function test_branch_name_is_required(): void
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(Branches::class)
            ->call('create')
            ->set('code', 'SUC001')
            ->set('name', '')
            ->call('store')
            ->assertHasErrors(['name']);
    }

    public function test_municipalities_load_when_department_changes(): void
    {
        $this->actingAs($this->adminUser);
        
        $department = Department::factory()->create();
        $municipality = Municipality::factory()->create(['department_id' => $department->id]);
        
        $component = Livewire::test(Branches::class)
            ->call('create')
            ->set('department_id', $department->id);
        
        $this->assertCount(1, $component->get('municipalities'));
        $this->assertEquals($municipality->id, $component->get('municipalities')[0]['id']);
    }

    public function test_activity_log_is_created_on_branch_creation(): void
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(Branches::class)
            ->call('create')
            ->set('code', 'SUC001')
            ->set('name', 'Logged Branch')
            ->call('store');
        
        $this->assertDatabaseHas('activity_logs', [
            'module' => 'branches',
            'action' => 'create',
            'user_id' => $this->adminUser->id,
        ]);
    }

    public function test_activity_log_is_created_on_branch_update(): void
    {
        $this->actingAs($this->adminUser);
        
        $branch = Branch::factory()->create(['name' => 'Original']);
        
        Livewire::test(Branches::class)
            ->call('edit', $branch->id)
            ->set('name', 'Updated')
            ->call('store');
        
        $this->assertDatabaseHas('activity_logs', [
            'module' => 'branches',
            'action' => 'update',
            'user_id' => $this->adminUser->id,
        ]);
    }

    public function test_user_with_permission_can_open_copy_modal(): void
    {
        $this->actingAs($this->adminUser);

        $branch1 = Branch::factory()->create(['name' => 'Branch 1']);
        $branch2 = Branch::factory()->create(['name' => 'Branch 2']);

        Livewire::test(Branches::class)
            ->call('openCopyModal', $branch1->id)
            ->assertSet('isCopyModalOpen', true)
            ->assertSet('copyFromBranchId', (string) $branch1->id);
    }

    public function test_user_without_permission_cannot_open_copy_modal(): void
    {
        $userWithoutPermission = User::factory()->create();
        $limitedRole = Role::factory()->create(['name' => 'limited']);
        $userWithoutPermission->roles()->attach($limitedRole->id);

        $this->actingAs($userWithoutPermission);

        Livewire::test(Branches::class)
            ->call('openCopyModal')
            ->assertSet('isCopyModalOpen', false)
            ->assertDispatched('notify');
    }

    public function test_copy_products_validation_fails_for_same_branch(): void
    {
        $this->actingAs($this->adminUser);

        $branch = Branch::factory()->create(['name' => 'Same Branch']);

        Livewire::test(Branches::class)
            ->call('openCopyModal', $branch->id)
            ->set('copyToBranchId', $branch->id)
            ->call('executeCopyProducts')
            ->assertHasErrors(['copyToBranchId']);
    }

    public function test_user_can_copy_products_between_branches(): void
    {
        $this->actingAs($this->adminUser);

        $fromBranch = Branch::factory()->create(['name' => 'Origen Branch']);
        $toBranch = Branch::factory()->create(['name' => 'Destino Branch']);

        $category = \App\Models\Category::factory()->create();
        $unit = \App\Models\Unit::factory()->create();

        $product1 = \App\Models\Product::factory()->create([
            'branch_id' => $fromBranch->id,
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'name' => 'Producto Prueba 1',
            'sale_price' => 15000,
            'purchase_price' => 10000,
            'current_stock' => 50,
            'is_active' => true,
        ]);

        $product2 = \App\Models\Product::factory()->create([
            'branch_id' => $fromBranch->id,
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'name' => 'Producto Prueba 2',
            'sale_price' => 25000,
            'purchase_price' => 18000,
            'current_stock' => 30,
            'is_active' => true,
        ]);

        Livewire::test(Branches::class)
            ->call('openCopyModal', $fromBranch->id)
            ->set('copyToBranchId', (string) $toBranch->id)
            ->set('copyFilter', 'all')
            ->set('copyStockMode', 'zero')
            ->set('copyVariants', true)
            ->call('executeCopyProducts')
            ->assertSet('isCopyModalOpen', false)
            ->assertDispatched('notify');

        // Verify products copied to destination branch
        $this->assertDatabaseHas('products', [
            'branch_id' => $toBranch->id,
            'name' => 'Producto Prueba 1',
            'sale_price' => 15000,
            'current_stock' => 0,
        ]);

        $this->assertDatabaseHas('products', [
            'branch_id' => $toBranch->id,
            'name' => 'Producto Prueba 2',
            'sale_price' => 25000,
            'current_stock' => 0,
        ]);

        // Verify source branch products remain 100% intact
        $this->assertDatabaseHas('products', [
            'id' => $product1->id,
            'branch_id' => $fromBranch->id,
            'name' => 'Producto Prueba 1',
            'current_stock' => 50,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product2->id,
            'branch_id' => $fromBranch->id,
            'name' => 'Producto Prueba 2',
            'current_stock' => 30,
        ]);
    }

    public function test_copy_products_copies_child_variants(): void
    {
        $this->actingAs($this->adminUser);

        $fromBranch = Branch::factory()->create(['name' => 'Origen Variants']);
        $toBranch = Branch::factory()->create(['name' => 'Destino Variants']);

        $category = \App\Models\Category::factory()->create();
        $unit = \App\Models\Unit::factory()->create();

        $parentProduct = \App\Models\Product::factory()->create([
            'branch_id' => $fromBranch->id,
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'name' => 'Camisa Oxford',
            'sale_price' => 50000,
            'current_stock' => 20,
            'is_active' => true,
        ]);

        $child1 = \App\Models\ProductChild::factory()->create([
            'product_id' => $parentProduct->id,
            'name' => 'Talla M - Azul',
            'sale_price' => 50000,
            'is_active' => true,
        ]);

        $child2 = \App\Models\ProductChild::factory()->create([
            'product_id' => $parentProduct->id,
            'name' => 'Talla L - Rojo',
            'sale_price' => 55000,
            'is_active' => true,
        ]);

        Livewire::test(Branches::class)
            ->call('openCopyModal', $fromBranch->id)
            ->set('copyToBranchId', (string) $toBranch->id)
            ->set('copyFilter', 'all')
            ->set('copyStockMode', 'zero')
            ->set('copyVariants', true)
            ->call('executeCopyProducts');

        $clonedParent = \App\Models\Product::where('branch_id', $toBranch->id)
            ->where('name', 'Camisa Oxford')
            ->first();

        $this->assertNotNull($clonedParent);
        $this->assertDatabaseHas('product_children', [
            'product_id' => $clonedParent->id,
            'name' => 'Talla M - Azul',
            'sale_price' => 50000,
        ]);
        $this->assertDatabaseHas('product_children', [
            'product_id' => $clonedParent->id,
            'name' => 'Talla L - Rojo',
            'sale_price' => 55000,
        ]);

        // Original child variants in source branch are intact
        $this->assertDatabaseHas('product_children', [
            'id' => $child1->id,
            'product_id' => $parentProduct->id,
        ]);
        $this->assertDatabaseHas('product_children', [
            'id' => $child2->id,
            'product_id' => $parentProduct->id,
        ]);
    }

    public function test_copy_products_copies_images_barcodes_and_multi_barcodes(): void
    {
        $this->actingAs($this->adminUser);

        $fromBranch = Branch::factory()->create(['name' => 'Origen Barcodes']);
        $toBranch = Branch::factory()->create(['name' => 'Destino Barcodes']);

        $category = \App\Models\Category::factory()->create();
        $unit = \App\Models\Unit::factory()->create();

        $parentProduct = \App\Models\Product::factory()->create([
            'branch_id' => $fromBranch->id,
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'name' => 'Bebida Energizante',
            'barcode' => '7701234567890',
            'image' => 'products/bebida.jpg',
            'sale_price' => 5000,
            'is_active' => true,
        ]);

        \App\Models\ProductBarcode::create([
            'product_id' => $parentProduct->id,
            'product_child_id' => null,
            'barcode' => '7701234567890',
            'description' => 'Código principal',
            'is_primary' => true,
        ]);

        \App\Models\ProductBarcode::create([
            'product_id' => $parentProduct->id,
            'product_child_id' => null,
            'barcode' => '7701234567891',
            'description' => 'Código secundario empaque',
            'is_primary' => false,
        ]);

        $childVariant = \App\Models\ProductChild::factory()->create([
            'product_id' => $parentProduct->id,
            'name' => 'Pack x6',
            'barcode' => '7701234567892',
            'image' => 'products/pack6.jpg',
            'unit_quantity' => 6,
            'sale_price' => 28000,
            'is_active' => true,
        ]);

        \App\Models\ProductBarcode::create([
            'product_id' => null,
            'product_child_id' => $childVariant->id,
            'barcode' => '7701234567892',
            'description' => 'Código Pack',
            'is_primary' => true,
        ]);

        Livewire::test(Branches::class)
            ->call('openCopyModal', $fromBranch->id)
            ->set('copyToBranchId', (string) $toBranch->id)
            ->set('copyFilter', 'all')
            ->set('copyStockMode', 'zero')
            ->set('copyVariants', true)
            ->call('executeCopyProducts');

        $clonedParent = \App\Models\Product::where('branch_id', $toBranch->id)
            ->where('name', 'Bebida Energizante')
            ->first();

        $this->assertNotNull($clonedParent);
        $this->assertEquals('7701234567890', $clonedParent->barcode);
        $this->assertEquals('products/bebida.jpg', $clonedParent->image);

        // Check cloned parent barcodes
        $this->assertDatabaseHas('product_barcodes', [
            'product_id' => $clonedParent->id,
            'barcode' => '7701234567890',
            'is_primary' => true,
        ]);
        $this->assertDatabaseHas('product_barcodes', [
            'product_id' => $clonedParent->id,
            'barcode' => '7701234567891',
            'is_primary' => false,
        ]);

        // Check cloned variant
        $clonedChild = \App\Models\ProductChild::where('product_id', $clonedParent->id)
            ->where('name', 'Pack x6')
            ->first();

        $this->assertNotNull($clonedChild);
        $this->assertEquals('7701234567892', $clonedChild->barcode);
        $this->assertEquals('products/pack6.jpg', $clonedChild->image);

        // Check cloned variant barcodes
        $this->assertDatabaseHas('product_barcodes', [
            'product_child_id' => $clonedChild->id,
            'barcode' => '7701234567892',
            'is_primary' => true,
        ]);
    }

    public function test_zones_and_tables_module_and_permissions_are_not_present(): void
    {
        $this->assertDatabaseMissing('modules', ['name' => 'zones_tables']);
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasTable('restaurant_tables'));
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasTable('zones'));
        $this->assertFalse(\App\Models\Permission::where('name', 'like', 'zones_tables.%')->exists());
    }
}
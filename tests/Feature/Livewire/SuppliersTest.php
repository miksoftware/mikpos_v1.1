<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Suppliers;
use App\Models\Department;
use App\Models\Municipality;
use App\Models\Supplier;
use App\Models\TaxDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SuppliersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed();
        
        $user = User::where('email', 'admin@mikpos.com')->first();
        $this->actingAs($user);
    }

    public function test_component_can_render(): void
    {
        $component = Livewire::test(Suppliers::class);
        
        $component->assertStatus(200);
    }

    public function test_can_create_supplier(): void
    {
        $department = Department::factory()->create();
        $municipality = Municipality::factory()->create(['department_id' => $department->id]);
        $taxDocument = TaxDocument::factory()->create();

        Livewire::test(Suppliers::class)
            ->call('create')
            ->set('tax_document_id', $taxDocument->id)
            ->set('document_number', '900123456')
            ->set('name', 'Proveedor Test S.A.S.')
            ->set('phone', '3001234567')
            ->set('email', 'proveedor@test.com')
            ->set('department_id', $department->id)
            ->set('municipality_id', $municipality->id)
            ->set('address', 'Calle 123 #45-67')
            ->call('store')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('suppliers', [
            'document_number' => '900123456',
            'name' => 'Proveedor Test S.A.S.',
            'email' => 'proveedor@test.com',
        ]);
    }

    public function test_can_create_supplier_with_salesperson(): void
    {
        $department = Department::factory()->create();
        $municipality = Municipality::factory()->create(['department_id' => $department->id]);
        $taxDocument = TaxDocument::factory()->create();

        Livewire::test(Suppliers::class)
            ->call('create')
            ->set('tax_document_id', $taxDocument->id)
            ->set('document_number', '900654321')
            ->set('name', 'Distribuidora ABC')
            ->set('department_id', $department->id)
            ->set('municipality_id', $municipality->id)
            ->set('address', 'Carrera 50 #30-20')
            ->set('salesperson_name', 'Juan Vendedor')
            ->set('salesperson_phone', '3109876543')
            ->call('store')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('suppliers', [
            'document_number' => '900654321',
            'salesperson_name' => 'Juan Vendedor',
            'salesperson_phone' => '3109876543',
        ]);
    }

    public function test_can_edit_supplier(): void
    {
        $department = Department::factory()->create();
        $municipality = Municipality::factory()->create(['department_id' => $department->id]);
        $taxDocument = TaxDocument::factory()->create();
        
        $supplier = Supplier::factory()->create([
            'name' => 'Proveedor Original',
            'department_id' => $department->id,
            'municipality_id' => $municipality->id,
            'tax_document_id' => $taxDocument->id,
        ]);

        Livewire::test(Suppliers::class)
            ->call('edit', $supplier->id)
            ->set('name', 'Proveedor Actualizado')
            ->call('store')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'name' => 'Proveedor Actualizado',
        ]);
    }

    public function test_can_delete_supplier(): void
    {
        $supplier = Supplier::factory()->create(['name' => 'Proveedor a Eliminar']);

        Livewire::test(Suppliers::class)
            ->call('confirmDelete', $supplier->id)
            ->call('delete')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('suppliers', [
            'id' => $supplier->id,
        ]);
    }

    public function test_can_toggle_supplier_status(): void
    {
        $supplier = Supplier::factory()->create(['is_active' => true]);

        Livewire::test(Suppliers::class)
            ->call('toggleStatus', $supplier->id);

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'is_active' => false,
        ]);
    }

    public function test_can_search_suppliers(): void
    {
        $department = Department::factory()->create();
        $municipality = Municipality::factory()->create(['department_id' => $department->id]);
        $taxDocument = TaxDocument::factory()->create();

        Supplier::factory()->create([
            'name' => 'Distribuidora ABC',
            'department_id' => $department->id,
            'municipality_id' => $municipality->id,
            'tax_document_id' => $taxDocument->id,
        ]);
        
        Supplier::factory()->create([
            'name' => 'Comercializadora XYZ',
            'department_id' => $department->id,
            'municipality_id' => $municipality->id,
            'tax_document_id' => $taxDocument->id,
        ]);

        $component = Livewire::test(Suppliers::class)
            ->set('search', 'ABC');

        $component->assertSee('Distribuidora ABC')
                  ->assertDontSee('Comercializadora XYZ');
    }

    public function test_can_search_by_document_number(): void
    {
        $department = Department::factory()->create();
        $municipality = Municipality::factory()->create(['department_id' => $department->id]);
        $taxDocument = TaxDocument::factory()->create();

        Supplier::factory()->create([
            'document_number' => '900111222',
            'name' => 'Proveedor Uno',
            'department_id' => $department->id,
            'municipality_id' => $municipality->id,
            'tax_document_id' => $taxDocument->id,
        ]);
        
        Supplier::factory()->create([
            'document_number' => '900333444',
            'name' => 'Proveedor Dos',
            'department_id' => $department->id,
            'municipality_id' => $municipality->id,
            'tax_document_id' => $taxDocument->id,
        ]);

        $component = Livewire::test(Suppliers::class)
            ->set('search', '900111222');

        $component->assertSee('Proveedor Uno')
                  ->assertDontSee('Proveedor Dos');
    }

    public function test_municipalities_load_when_department_changes(): void
    {
        $department = Department::factory()->create();
        $municipality = Municipality::factory()->create(['department_id' => $department->id]);

        $component = Livewire::test(Suppliers::class)
            ->call('create')
            ->set('department_id', $department->id);

        $this->assertNotEmpty($component->get('municipalities'));
        $this->assertEquals($municipality->id, $component->get('municipalities')[0]['id']);
    }

    public function test_tax_document_is_required(): void
    {
        Livewire::test(Suppliers::class)
            ->call('create')
            ->set('tax_document_id', '')
            ->call('store')
            ->assertHasErrors(['tax_document_id' => 'required']);
    }

    public function test_document_number_is_required(): void
    {
        Livewire::test(Suppliers::class)
            ->call('create')
            ->set('document_number', '')
            ->call('store')
            ->assertHasErrors(['document_number' => 'required']);
    }

    public function test_document_number_must_be_unique(): void
    {
        Supplier::factory()->create(['document_number' => '900123456']);

        Livewire::test(Suppliers::class)
            ->call('create')
            ->set('document_number', '900123456')
            ->call('store')
            ->assertHasErrors(['document_number']);
    }

    public function test_name_is_required(): void
    {
        Livewire::test(Suppliers::class)
            ->call('create')
            ->set('name', '')
            ->call('store')
            ->assertHasErrors(['name' => 'required']);
    }

    public function test_address_is_required(): void
    {
        Livewire::test(Suppliers::class)
            ->call('create')
            ->set('address', '')
            ->call('store')
            ->assertHasErrors(['address' => 'required']);
    }

    public function test_email_must_be_valid_format(): void
    {
        Livewire::test(Suppliers::class)
            ->call('create')
            ->set('email', 'invalid-email')
            ->call('store')
            ->assertHasErrors(['email']);
    }

    public function test_salesperson_fields_are_optional(): void
    {
        $department = Department::factory()->create();
        $municipality = Municipality::factory()->create(['department_id' => $department->id]);
        $taxDocument = TaxDocument::factory()->create();

        Livewire::test(Suppliers::class)
            ->call('create')
            ->set('tax_document_id', $taxDocument->id)
            ->set('document_number', '900999888')
            ->set('name', 'Proveedor Sin Vendedor')
            ->set('department_id', $department->id)
            ->set('municipality_id', $municipality->id)
            ->set('address', 'Dirección de prueba')
            ->set('salesperson_name', '')
            ->set('salesperson_phone', '')
            ->call('store')
            ->assertHasNoErrors(['salesperson_name', 'salesperson_phone']);

        $this->assertDatabaseHas('suppliers', [
            'document_number' => '900999888',
            'salesperson_name' => null,
            'salesperson_phone' => null,
        ]);
    }
}

<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Customers;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Municipality;
use App\Models\TaxDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomersTest extends TestCase
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
        $component = Livewire::test(Customers::class);
        
        $component->assertStatus(200);
    }

    public function test_can_create_natural_customer(): void
    {
        $department = Department::factory()->create();
        $municipality = Municipality::factory()->create(['department_id' => $department->id]);
        $taxDocument = TaxDocument::factory()->create();

        Livewire::test(Customers::class)
            ->call('create')
            ->set('customer_type', 'natural')
            ->set('tax_document_id', $taxDocument->id)
            ->set('document_number', '12345678')
            ->set('first_name', 'Juan')
            ->set('last_name', 'Pérez')
            ->set('phone', '3001234567')
            ->set('email', 'juan@example.com')
            ->set('department_id', $department->id)
            ->set('municipality_id', $municipality->id)
            ->set('address', 'Calle 123 #45-67')
            ->set('has_credit', false)
            ->set('is_active', true)
            ->call('store')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('customers', [
            'customer_type' => 'natural',
            'document_number' => '12345678',
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'phone' => '3001234567',
            'email' => 'juan@example.com',
            'has_credit' => false,
            'is_active' => true,
        ]);
    }

    public function test_can_create_juridico_customer_with_business_name(): void
    {
        $department = Department::factory()->create();
        $municipality = Municipality::factory()->create(['department_id' => $department->id]);
        $taxDocument = TaxDocument::factory()->create();

        Livewire::test(Customers::class)
            ->call('create')
            ->set('customer_type', 'juridico')
            ->set('tax_document_id', $taxDocument->id)
            ->set('document_number', '900123456')
            ->set('first_name', 'Juan')
            ->set('last_name', 'Pérez')
            ->set('business_name', 'Empresa ABC S.A.S.')
            ->set('department_id', $department->id)
            ->set('municipality_id', $municipality->id)
            ->set('address', 'Carrera 50 #30-20')
            ->set('has_credit', true)
            ->set('credit_limit', 2000000)
            ->set('is_active', true)
            ->call('store')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('customers', [
            'customer_type' => 'juridico',
            'document_number' => '900123456',
            'business_name' => 'Empresa ABC S.A.S.',
            'has_credit' => true,
            'credit_limit' => 2000000,
        ]);
    }

    public function test_can_edit_customer(): void
    {
        $department = Department::factory()->create();
        $municipality = Municipality::factory()->create(['department_id' => $department->id]);
        
        $customer = Customer::factory()->create([
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'department_id' => $department->id,
            'municipality_id' => $municipality->id,
        ]);

        // Create a new municipality for the same department to test editing
        $newMunicipality = Municipality::factory()->create(['department_id' => $department->id]);

        Livewire::test(Customers::class)
            ->call('edit', $customer->id)
            ->set('first_name', 'Carlos')
            ->set('last_name', 'González')
            ->set('municipality_id', $newMunicipality->id)
            ->call('store')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'first_name' => 'Carlos',
            'last_name' => 'González',
            'municipality_id' => $newMunicipality->id,
        ]);
    }

    public function test_can_delete_customer(): void
    {
        $customer = Customer::factory()->create(['first_name' => 'Test Customer']);

        Livewire::test(Customers::class)
            ->call('confirmDelete', $customer->id)
            ->call('delete')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('customers', [
            'id' => $customer->id,
        ]);
    }

    public function test_can_toggle_customer_status(): void
    {
        $customer = Customer::factory()->create(['is_active' => true]);

        Livewire::test(Customers::class)
            ->call('toggleStatus', $customer->id);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'is_active' => false,
        ]);
    }

    public function test_can_search_customers(): void
    {
        // Create customers with specific names for testing
        $department = Department::factory()->create();
        $municipality = Municipality::factory()->create(['department_id' => $department->id]);
        $taxDocument = TaxDocument::factory()->create();

        // Create natural customer with specific name
        Customer::factory()->natural()->create([
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'department_id' => $department->id,
            'municipality_id' => $municipality->id,
            'tax_document_id' => $taxDocument->id,
        ]);
        
        // Create another natural customer with different name
        Customer::factory()->natural()->create([
            'first_name' => 'María',
            'last_name' => 'González',
            'department_id' => $department->id,
            'municipality_id' => $municipality->id,
            'tax_document_id' => $taxDocument->id,
        ]);

        $component = Livewire::test(Customers::class)
            ->set('search', 'Juan');

        $component->assertSee('Juan Pérez')
                  ->assertDontSee('María González');
    }

    public function test_can_search_by_document_number(): void
    {
        $department = Department::factory()->create();
        $municipality = Municipality::factory()->create(['department_id' => $department->id]);
        $taxDocument = TaxDocument::factory()->create();

        Customer::factory()->natural()->create([
            'document_number' => '12345678',
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'department_id' => $department->id,
            'municipality_id' => $municipality->id,
            'tax_document_id' => $taxDocument->id,
        ]);
        
        Customer::factory()->natural()->create([
            'document_number' => '87654321',
            'first_name' => 'María',
            'last_name' => 'González',
            'department_id' => $department->id,
            'municipality_id' => $municipality->id,
            'tax_document_id' => $taxDocument->id,
        ]);

        $component = Livewire::test(Customers::class)
            ->set('search', '12345678');

        $component->assertSee('Juan Pérez')
                  ->assertDontSee('María González');
    }

    public function test_can_search_by_business_name(): void
    {
        $department = Department::factory()->create();
        $municipality = Municipality::factory()->create(['department_id' => $department->id]);
        $taxDocument = TaxDocument::factory()->create();

        Customer::factory()->juridico()->create([
            'business_name' => 'Empresa ABC S.A.S.',
            'department_id' => $department->id,
            'municipality_id' => $municipality->id,
            'tax_document_id' => $taxDocument->id,
        ]);
        
        Customer::factory()->juridico()->create([
            'business_name' => 'Compañía XYZ Ltda.',
            'department_id' => $department->id,
            'municipality_id' => $municipality->id,
            'tax_document_id' => $taxDocument->id,
        ]);

        $component = Livewire::test(Customers::class)
            ->set('search', 'ABC');

        $component->assertSee('Empresa ABC S.A.S.')
                  ->assertDontSee('Compañía XYZ Ltda.');
    }

    public function test_can_filter_by_customer_type(): void
    {
        $department = Department::factory()->create();
        $municipality = Municipality::factory()->create(['department_id' => $department->id]);
        $taxDocument = TaxDocument::factory()->create();

        Customer::factory()->natural()->create([
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'department_id' => $department->id,
            'municipality_id' => $municipality->id,
            'tax_document_id' => $taxDocument->id,
        ]);
        
        Customer::factory()->juridico()->create([
            'first_name' => 'María',
            'last_name' => 'González',
            'business_name' => 'Empresa María S.A.S.',
            'department_id' => $department->id,
            'municipality_id' => $municipality->id,
            'tax_document_id' => $taxDocument->id,
        ]);

        $component = Livewire::test(Customers::class)
            ->set('filterCustomerType', 'natural');

        $component->assertSee('Juan Pérez')
                  ->assertDontSee('Empresa María S.A.S.');
    }

    public function test_municipalities_load_when_department_changes(): void
    {
        $department = Department::factory()->create();
        $municipality = Municipality::factory()->create(['department_id' => $department->id]);

        $component = Livewire::test(Customers::class)
            ->call('create')
            ->set('department_id', $department->id);

        $this->assertNotEmpty($component->get('municipalities'));
        $this->assertEquals($municipality->id, $component->get('municipalities')[0]['id']);
    }

    public function test_credit_limit_clears_when_has_credit_is_false(): void
    {
        Livewire::test(Customers::class)
            ->call('create')
            ->set('has_credit', true)
            ->set('credit_limit', 1000000)
            ->set('has_credit', false)
            ->assertSet('credit_limit', null);
    }

    public function test_customer_type_is_required(): void
    {
        Livewire::test(Customers::class)
            ->call('create')
            ->set('customer_type', '')
            ->call('store')
            ->assertHasErrors(['customer_type' => 'required']);
    }

    public function test_document_number_is_required(): void
    {
        Livewire::test(Customers::class)
            ->call('create')
            ->set('document_number', '')
            ->call('store')
            ->assertHasErrors(['document_number' => 'required']);
    }

    public function test_document_number_must_be_unique(): void
    {
        Customer::factory()->create(['document_number' => '12345678']);

        Livewire::test(Customers::class)
            ->call('create')
            ->set('document_number', '12345678')
            ->call('store')
            ->assertHasErrors(['document_number']);
    }

    public function test_first_name_is_required(): void
    {
        Livewire::test(Customers::class)
            ->call('create')
            ->set('first_name', '')
            ->call('store')
            ->assertHasErrors(['first_name' => 'required']);
    }

    public function test_last_name_is_required(): void
    {
        Livewire::test(Customers::class)
            ->call('create')
            ->set('last_name', '')
            ->call('store')
            ->assertHasErrors(['last_name' => 'required']);
    }

    public function test_business_name_is_required_for_juridico(): void
    {
        Livewire::test(Customers::class)
            ->call('create')
            ->set('customer_type', 'juridico')
            ->set('business_name', '')
            ->call('store')
            ->assertHasErrors(['business_name' => 'required']);
    }

    public function test_business_name_is_optional_for_natural(): void
    {
        $department = Department::factory()->create();
        $municipality = Municipality::factory()->create(['department_id' => $department->id]);
        $taxDocument = TaxDocument::factory()->create();

        Livewire::test(Customers::class)
            ->call('create')
            ->set('customer_type', 'natural')
            ->set('tax_document_id', $taxDocument->id)
            ->set('document_number', '12345678')
            ->set('first_name', 'Juan')
            ->set('last_name', 'Pérez')
            ->set('department_id', $department->id)
            ->set('municipality_id', $municipality->id)
            ->set('address', 'Calle 123')
            ->set('business_name', '')
            ->call('store')
            ->assertHasNoErrors(['business_name']);
    }

    public function test_address_is_required(): void
    {
        Livewire::test(Customers::class)
            ->call('create')
            ->set('address', '')
            ->call('store')
            ->assertHasErrors(['address' => 'required']);
    }

    public function test_credit_limit_is_required_when_has_credit_is_true(): void
    {
        Livewire::test(Customers::class)
            ->call('create')
            ->set('has_credit', true)
            ->set('credit_limit', '')
            ->call('store')
            ->assertHasErrors(['credit_limit' => 'required']);
    }

    public function test_email_must_be_valid_format(): void
    {
        Livewire::test(Customers::class)
            ->call('create')
            ->set('email', 'invalid-email')
            ->call('store')
            ->assertHasErrors(['email']);
    }

    public function test_can_set_customer_as_default(): void
    {
        $department = Department::factory()->create();
        $municipality = Municipality::factory()->create(['department_id' => $department->id]);
        $taxDocument = TaxDocument::factory()->create();

        Livewire::test(Customers::class)
            ->call('create')
            ->set('customer_type', 'natural')
            ->set('tax_document_id', $taxDocument->id)
            ->set('document_number', '222222222')
            ->set('first_name', 'Consumidor')
            ->set('last_name', 'Final')
            ->set('department_id', $department->id)
            ->set('municipality_id', $municipality->id)
            ->set('address', 'Sin dirección')
            ->set('is_default', true)
            ->call('store')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('customers', [
            'document_number' => '222222222',
            'is_default' => true,
        ]);
    }

    public function test_only_one_customer_can_be_default(): void
    {
        $department = Department::factory()->create();
        $municipality = Municipality::factory()->create(['department_id' => $department->id]);
        $taxDocument = TaxDocument::factory()->create();

        // Create first default customer
        $firstDefault = Customer::factory()->default()->create([
            'department_id' => $department->id,
            'municipality_id' => $municipality->id,
            'tax_document_id' => $taxDocument->id,
        ]);

        $this->assertTrue($firstDefault->is_default);

        // Create second customer and set as default
        Livewire::test(Customers::class)
            ->call('create')
            ->set('customer_type', 'natural')
            ->set('tax_document_id', $taxDocument->id)
            ->set('document_number', '333333333')
            ->set('first_name', 'Nuevo')
            ->set('last_name', 'Default')
            ->set('department_id', $department->id)
            ->set('municipality_id', $municipality->id)
            ->set('address', 'Calle 123')
            ->set('is_default', true)
            ->call('store')
            ->assertHasNoErrors();

        // Refresh first customer and check it's no longer default
        $firstDefault->refresh();
        $this->assertFalse($firstDefault->is_default);

        // Check new customer is default
        $this->assertDatabaseHas('customers', [
            'document_number' => '333333333',
            'is_default' => true,
        ]);
    }
}
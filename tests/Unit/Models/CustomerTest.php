<?php

namespace Tests\Unit\Models;

use App\Models\Customer;
use App\Models\Department;
use App\Models\Municipality;
use App\Models\TaxDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_be_created(): void
    {
        $department = Department::factory()->create();
        $municipality = Municipality::factory()->create(['department_id' => $department->id]);
        $taxDocument = TaxDocument::factory()->create();
        
        $customer = Customer::factory()->create([
            'customer_type' => 'natural',
            'tax_document_id' => $taxDocument->id,
            'document_number' => '12345678',
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'department_id' => $department->id,
            'municipality_id' => $municipality->id,
            'address' => 'Calle 123 #45-67',
            'has_credit' => true,
            'credit_limit' => 1000000.00,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('customers', [
            'customer_type' => 'natural',
            'document_number' => '12345678',
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'has_credit' => true,
            'credit_limit' => 1000000.00,
            'is_active' => true,
        ]);
    }

    public function test_customer_belongs_to_tax_document(): void
    {
        $taxDocument = TaxDocument::factory()->create(['description' => 'Cédula de Ciudadanía']);
        $customer = Customer::factory()->create(['tax_document_id' => $taxDocument->id]);

        $this->assertInstanceOf(TaxDocument::class, $customer->taxDocument);
        $this->assertEquals('Cédula de Ciudadanía', $customer->taxDocument->description);
    }

    public function test_customer_belongs_to_department(): void
    {
        $department = Department::factory()->create(['name' => 'Antioquia']);
        $customer = Customer::factory()->create(['department_id' => $department->id]);

        $this->assertInstanceOf(Department::class, $customer->department);
        $this->assertEquals('Antioquia', $customer->department->name);
    }

    public function test_customer_belongs_to_municipality(): void
    {
        $municipality = Municipality::factory()->create(['name' => 'Medellín']);
        $customer = Customer::factory()->create(['municipality_id' => $municipality->id]);

        $this->assertInstanceOf(Municipality::class, $customer->municipality);
        $this->assertEquals('Medellín', $customer->municipality->name);
    }

    public function test_customer_has_credit_is_cast_to_boolean(): void
    {
        $customer = Customer::factory()->withCredit()->create();
        $this->assertIsBool($customer->has_credit);
        $this->assertTrue($customer->has_credit);

        $customer = Customer::factory()->withoutCredit()->create();
        $this->assertIsBool($customer->has_credit);
        $this->assertFalse($customer->has_credit);
    }

    public function test_customer_is_active_is_cast_to_boolean(): void
    {
        $customer = Customer::factory()->create(['is_active' => 1]);
        $this->assertIsBool($customer->is_active);
        $this->assertTrue($customer->is_active);

        $customer = Customer::factory()->inactive()->create();
        $this->assertIsBool($customer->is_active);
        $this->assertFalse($customer->is_active);
    }

    public function test_customer_credit_limit_is_cast_to_decimal(): void
    {
        $customer = Customer::factory()->withCredit()->create(['credit_limit' => 1500000]);
        $this->assertEquals('1500000.00', $customer->credit_limit);
    }

    public function test_customer_fillable_attributes(): void
    {
        $customer = new Customer();
        $expected = [
            'customer_type',
            'tax_document_id',
            'document_number',
            'first_name',
            'last_name',
            'business_name',
            'phone',
            'email',
            'department_id',
            'municipality_id',
            'address',
            'has_credit',
            'credit_limit',
            'is_active',
        ];
        
        $this->assertEquals($expected, $customer->getFillable());
    }

    public function test_natural_customer_full_name_attribute(): void
    {
        $customer = Customer::factory()->natural()->create([
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'business_name' => null,
        ]);

        $this->assertEquals('Juan Pérez', $customer->full_name);
    }

    public function test_juridico_customer_full_name_attribute(): void
    {
        $customer = Customer::factory()->juridico()->create([
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'business_name' => 'Empresa ABC S.A.S.',
        ]);

        $this->assertEquals('Empresa ABC S.A.S.', $customer->full_name);
    }

    public function test_customer_display_name_attribute(): void
    {
        $customer = Customer::factory()->natural()->create([
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'document_number' => '12345678',
        ]);

        $this->assertEquals('Juan Pérez (12345678)', $customer->display_name);
    }

    public function test_customer_types(): void
    {
        $natural = Customer::factory()->natural()->create();
        $this->assertEquals('natural', $natural->customer_type);
        $this->assertNull($natural->business_name);

        $juridico = Customer::factory()->juridico()->create();
        $this->assertEquals('juridico', $juridico->customer_type);
        $this->assertNotNull($juridico->business_name);

        $exonerado = Customer::factory()->exonerado()->create();
        $this->assertEquals('exonerado', $exonerado->customer_type);
    }

    public function test_customer_can_be_created_with_factory(): void
    {
        $customer = Customer::factory()->create();

        $this->assertInstanceOf(Customer::class, $customer);
        $this->assertNotNull($customer->customer_type);
        $this->assertNotNull($customer->document_number);
        $this->assertNotNull($customer->first_name);
        $this->assertNotNull($customer->last_name);
    }
}
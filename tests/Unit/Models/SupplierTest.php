<?php

namespace Tests\Unit\Models;

use App\Models\Department;
use App\Models\Municipality;
use App\Models\Supplier;
use App\Models\TaxDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_can_be_created(): void
    {
        $supplier = Supplier::factory()->create();

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'name' => $supplier->name,
        ]);
    }

    public function test_supplier_belongs_to_tax_document(): void
    {
        $taxDocument = TaxDocument::factory()->create();
        $supplier = Supplier::factory()->create(['tax_document_id' => $taxDocument->id]);

        $this->assertInstanceOf(TaxDocument::class, $supplier->taxDocument);
        $this->assertEquals($taxDocument->id, $supplier->taxDocument->id);
    }

    public function test_supplier_belongs_to_department(): void
    {
        $department = Department::factory()->create();
        $municipality = Municipality::factory()->create(['department_id' => $department->id]);
        $supplier = Supplier::factory()->create([
            'department_id' => $department->id,
            'municipality_id' => $municipality->id,
        ]);

        $this->assertInstanceOf(Department::class, $supplier->department);
        $this->assertEquals($department->id, $supplier->department->id);
    }

    public function test_supplier_belongs_to_municipality(): void
    {
        $department = Department::factory()->create();
        $municipality = Municipality::factory()->create(['department_id' => $department->id]);
        $supplier = Supplier::factory()->create([
            'department_id' => $department->id,
            'municipality_id' => $municipality->id,
        ]);

        $this->assertInstanceOf(Municipality::class, $supplier->municipality);
        $this->assertEquals($municipality->id, $supplier->municipality->id);
    }

    public function test_supplier_is_active_is_cast_to_boolean(): void
    {
        $supplier = Supplier::factory()->create(['is_active' => 1]);

        $this->assertIsBool($supplier->is_active);
        $this->assertTrue($supplier->is_active);
    }

    public function test_supplier_fillable_attributes(): void
    {
        $supplier = new Supplier();
        $expected = [
            'tax_document_id',
            'document_number',
            'name',
            'phone',
            'email',
            'department_id',
            'municipality_id',
            'address',
            'salesperson_name',
            'salesperson_phone',
            'is_active',
        ];

        $this->assertEquals($expected, $supplier->getFillable());
    }

    public function test_supplier_can_have_salesperson(): void
    {
        $supplier = Supplier::factory()->withSalesperson()->create();

        $this->assertNotNull($supplier->salesperson_name);
        $this->assertNotNull($supplier->salesperson_phone);
    }

    public function test_supplier_can_be_created_without_salesperson(): void
    {
        $supplier = Supplier::factory()->create([
            'salesperson_name' => null,
            'salesperson_phone' => null,
        ]);

        $this->assertNull($supplier->salesperson_name);
        $this->assertNull($supplier->salesperson_phone);
    }

    public function test_supplier_can_be_created_with_factory(): void
    {
        $supplier = Supplier::factory()->create();

        $this->assertNotNull($supplier->id);
        $this->assertNotNull($supplier->name);
        $this->assertNotNull($supplier->document_number);
    }
}

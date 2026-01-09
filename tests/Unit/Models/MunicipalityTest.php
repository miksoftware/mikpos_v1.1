<?php

namespace Tests\Unit\Models;

use App\Models\Department;
use App\Models\Municipality;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MunicipalityTest extends TestCase
{
    use RefreshDatabase;

    public function test_municipality_has_fillable_attributes(): void
    {
        $municipality = new Municipality();
        
        $this->assertEquals([
            'department_id',
            'name',
            'dian_code',
            'is_active',
        ], $municipality->getFillable());
    }

    public function test_municipality_casts_is_active_to_boolean(): void
    {
        $municipality = Municipality::factory()->create(['is_active' => 1]);
        
        $this->assertIsBool($municipality->is_active);
        $this->assertTrue($municipality->is_active);
    }

    public function test_municipality_belongs_to_department(): void
    {
        $department = Department::factory()->create();
        $municipality = Municipality::factory()->create(['department_id' => $department->id]);
        
        $this->assertInstanceOf(Department::class, $municipality->department);
        $this->assertEquals($department->id, $municipality->department->id);
    }

    public function test_municipality_can_be_created_with_factory(): void
    {
        $department = Department::factory()->create();
        $municipality = Municipality::factory()->create([
            'department_id' => $department->id,
            'name' => 'Test Municipality',
            'dian_code' => '0001',
        ]);
        
        $this->assertDatabaseHas('municipalities', [
            'department_id' => $department->id,
            'name' => 'Test Municipality',
            'dian_code' => '0001',
            'is_active' => true,
        ]);
    }

    public function test_inactive_municipality_factory_state(): void
    {
        $municipality = Municipality::factory()->inactive()->create();
        
        $this->assertFalse($municipality->is_active);
    }
}
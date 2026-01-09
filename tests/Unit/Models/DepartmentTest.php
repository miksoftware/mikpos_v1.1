<?php

namespace Tests\Unit\Models;

use App\Models\Department;
use App\Models\Municipality;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_department_has_fillable_attributes(): void
    {
        $department = new Department();
        
        $this->assertEquals([
            'name',
            'dian_code',
            'is_active',
        ], $department->getFillable());
    }

    public function test_department_casts_is_active_to_boolean(): void
    {
        $department = Department::factory()->create(['is_active' => 1]);
        
        $this->assertIsBool($department->is_active);
        $this->assertTrue($department->is_active);
    }

    public function test_department_has_many_municipalities(): void
    {
        $department = Department::factory()->create();
        $municipality1 = Municipality::factory()->create(['department_id' => $department->id]);
        $municipality2 = Municipality::factory()->create(['department_id' => $department->id]);
        
        $this->assertCount(2, $department->municipalities);
        $this->assertTrue($department->municipalities->contains($municipality1));
        $this->assertTrue($department->municipalities->contains($municipality2));
    }

    public function test_department_has_many_active_municipalities(): void
    {
        $department = Department::factory()->create();
        $activeMunicipality = Municipality::factory()->create([
            'department_id' => $department->id,
            'is_active' => true
        ]);
        $inactiveMunicipality = Municipality::factory()->create([
            'department_id' => $department->id,
            'is_active' => false
        ]);
        
        $activeMunicipalities = $department->activeMunicipalities;
        
        $this->assertCount(1, $activeMunicipalities);
        $this->assertTrue($activeMunicipalities->contains($activeMunicipality));
        $this->assertFalse($activeMunicipalities->contains($inactiveMunicipality));
    }

    public function test_department_can_be_created_with_factory(): void
    {
        $department = Department::factory()->create([
            'name' => 'Test Department',
            'dian_code' => '01',
        ]);
        
        $this->assertDatabaseHas('departments', [
            'name' => 'Test Department',
            'dian_code' => '01',
            'is_active' => true,
        ]);
    }

    public function test_inactive_department_factory_state(): void
    {
        $department = Department::factory()->inactive()->create();
        
        $this->assertFalse($department->is_active);
    }
}
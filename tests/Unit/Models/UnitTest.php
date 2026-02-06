<?php

namespace Tests\Unit\Models;

use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitTest extends TestCase
{
    use RefreshDatabase;

    public function test_unit_has_fillable_attributes(): void
    {
        $fillable = ['name', 'abbreviation', 'is_active', 'is_weight_unit'];
        
        $unit = new Unit();
        
        $this->assertEquals($fillable, $unit->getFillable());
    }

    public function test_unit_casts_is_active_to_boolean(): void
    {
        $unit = Unit::factory()->create(['is_active' => 1]);
        
        $this->assertIsBool($unit->is_active);
        $this->assertTrue($unit->is_active);
    }

    public function test_unit_casts_is_weight_unit_to_boolean(): void
    {
        $unit = Unit::factory()->create(['is_weight_unit' => 1]);
        
        $this->assertIsBool($unit->is_weight_unit);
        $this->assertTrue($unit->is_weight_unit);
    }

    public function test_is_weight_unit_defaults_to_false(): void
    {
        $unit = Unit::factory()->create();
        
        $this->assertFalse($unit->is_weight_unit);
    }

    public function test_is_weight_unit_helper_method(): void
    {
        $weightUnit = Unit::factory()->create(['is_weight_unit' => true]);
        $nonWeightUnit = Unit::factory()->create(['is_weight_unit' => false]);
        
        $this->assertTrue($weightUnit->isWeightUnit());
        $this->assertFalse($nonWeightUnit->isWeightUnit());
    }

    public function test_unit_can_be_created_with_factory(): void
    {
        $unit = Unit::factory()->create();
        
        $this->assertInstanceOf(Unit::class, $unit);
        $this->assertDatabaseHas('units', [
            'id' => $unit->id,
            'name' => $unit->name,
        ]);
    }

    public function test_inactive_unit_factory_state(): void
    {
        $unit = Unit::factory()->inactive()->create();
        
        $this->assertFalse($unit->is_active);
    }
}
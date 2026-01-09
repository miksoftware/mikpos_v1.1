<?php

namespace Tests\Unit\Models;

use App\Models\Tax;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxTest extends TestCase
{
    use RefreshDatabase;

    public function test_tax_has_fillable_attributes(): void
    {
        $fillable = ['name', 'value', 'is_active'];
        
        $tax = new Tax();
        
        $this->assertEquals($fillable, $tax->getFillable());
    }

    public function test_tax_casts_value_to_decimal(): void
    {
        $tax = Tax::factory()->create(['value' => 19.00]);
        
        $this->assertEquals('19.00', $tax->value);
    }

    public function test_tax_casts_is_active_to_boolean(): void
    {
        $tax = Tax::factory()->create(['is_active' => 1]);
        
        $this->assertIsBool($tax->is_active);
        $this->assertTrue($tax->is_active);
    }

    public function test_tax_can_be_created_with_factory(): void
    {
        $tax = Tax::factory()->create();
        
        $this->assertInstanceOf(Tax::class, $tax);
        $this->assertDatabaseHas('taxes', [
            'id' => $tax->id,
            'name' => $tax->name,
        ]);
    }

    public function test_inactive_tax_factory_state(): void
    {
        $tax = Tax::factory()->inactive()->create();
        
        $this->assertFalse($tax->is_active);
    }
}
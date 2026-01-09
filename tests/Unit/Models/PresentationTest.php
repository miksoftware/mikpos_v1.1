<?php

namespace Tests\Unit\Models;

use App\Models\Presentation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PresentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_presentation_can_be_created(): void
    {
        $presentation = Presentation::factory()->create([
            'name' => 'Caja x12',
            'description' => 'Presentación en caja de 12 unidades',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('presentations', [
            'name' => 'Caja x12',
            'description' => 'Presentación en caja de 12 unidades',
            'is_active' => true,
        ]);
    }

    public function test_presentation_is_active_is_cast_to_boolean(): void
    {
        $presentation = Presentation::factory()->create(['is_active' => 1]);
        $this->assertIsBool($presentation->is_active);
        $this->assertTrue($presentation->is_active);

        $presentation = Presentation::factory()->inactive()->create();
        $this->assertIsBool($presentation->is_active);
        $this->assertFalse($presentation->is_active);
    }

    public function test_presentation_fillable_attributes(): void
    {
        $presentation = new Presentation();
        $expected = ['name', 'description', 'is_active'];
        
        $this->assertEquals($expected, $presentation->getFillable());
    }

    public function test_presentation_name_is_required(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        Presentation::create([
            'description' => 'Test description',
            'is_active' => true,
        ]);
    }

    public function test_presentation_can_be_created_with_minimal_data(): void
    {
        $presentation = Presentation::create([
            'name' => 'Individual',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('presentations', [
            'name' => 'Individual',
            'is_active' => true,
        ]);
        
        $this->assertNull($presentation->description);
    }
}
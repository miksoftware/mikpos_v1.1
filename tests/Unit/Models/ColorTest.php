<?php

namespace Tests\Unit\Models;

use App\Models\Color;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ColorTest extends TestCase
{
    use RefreshDatabase;

    public function test_color_can_be_created(): void
    {
        $color = Color::factory()->create([
            'name' => 'Rojo',
            'hex_code' => '#FF0000',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('colors', [
            'name' => 'Rojo',
            'hex_code' => '#FF0000',
            'is_active' => true,
        ]);
    }

    public function test_color_can_be_created_without_hex_code(): void
    {
        $color = Color::factory()->withoutHexCode()->create([
            'name' => 'Azul Personalizado',
        ]);

        $this->assertDatabaseHas('colors', [
            'name' => 'Azul Personalizado',
            'hex_code' => null,
        ]);
        
        $this->assertNull($color->hex_code);
    }

    public function test_color_is_active_is_cast_to_boolean(): void
    {
        $color = Color::factory()->create(['is_active' => 1]);
        $this->assertIsBool($color->is_active);
        $this->assertTrue($color->is_active);

        $color = Color::factory()->inactive()->create();
        $this->assertIsBool($color->is_active);
        $this->assertFalse($color->is_active);
    }

    public function test_color_fillable_attributes(): void
    {
        $color = new Color();
        $expected = ['name', 'hex_code', 'is_active'];
        
        $this->assertEquals($expected, $color->getFillable());
    }

    public function test_color_name_is_required(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        Color::create([
            'hex_code' => '#FF0000',
            'is_active' => true,
        ]);
    }

    public function test_color_hex_code_validation(): void
    {
        $color = Color::create([
            'name' => 'Verde',
            'hex_code' => '#00FF00',
            'is_active' => true,
        ]);

        $this->assertEquals('#00FF00', $color->hex_code);
    }
}
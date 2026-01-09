<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Colors;
use App\Models\Color;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ColorsTest extends TestCase
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
        $component = Livewire::test(Colors::class);
        
        $component->assertStatus(200);
    }

    public function test_can_create_color_with_hex_code(): void
    {
        Livewire::test(Colors::class)
            ->call('create')
            ->set('name', 'Rojo')
            ->set('hex_code', '#FF0000')
            ->set('is_active', true)
            ->call('store')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('colors', [
            'name' => 'Rojo',
            'hex_code' => '#FF0000',
            'is_active' => true,
        ]);
    }

    public function test_can_create_color_without_hex_code(): void
    {
        Livewire::test(Colors::class)
            ->call('create')
            ->set('name', 'Azul Personalizado')
            ->set('hex_code', '')
            ->set('is_active', true)
            ->call('store')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('colors', [
            'name' => 'Azul Personalizado',
            'hex_code' => null,
            'is_active' => true,
        ]);
    }

    public function test_can_edit_color(): void
    {
        $color = Color::factory()->create([
            'name' => 'Verde',
            'hex_code' => '#00FF00',
        ]);

        Livewire::test(Colors::class)
            ->call('edit', $color->id)
            ->set('name', 'Verde Oscuro')
            ->set('hex_code', '#008000')
            ->call('store')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('colors', [
            'id' => $color->id,
            'name' => 'Verde Oscuro',
            'hex_code' => '#008000',
        ]);
    }

    public function test_can_delete_color(): void
    {
        $color = Color::factory()->create(['name' => 'Test Color']);

        Livewire::test(Colors::class)
            ->call('confirmDelete', $color->id)
            ->call('delete')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('colors', [
            'id' => $color->id,
        ]);
    }

    public function test_can_toggle_color_status(): void
    {
        $color = Color::factory()->create(['is_active' => true]);

        Livewire::test(Colors::class)
            ->call('toggleStatus', $color->id);

        $this->assertDatabaseHas('colors', [
            'id' => $color->id,
            'is_active' => false,
        ]);
    }

    public function test_can_search_colors(): void
    {
        Color::factory()->create(['name' => 'Rojo Brillante']);
        Color::factory()->create(['name' => 'Azul Marino']);

        $component = Livewire::test(Colors::class)
            ->set('search', 'Rojo');

        $component->assertSee('Rojo Brillante')
                  ->assertDontSee('Azul Marino');
    }

    public function test_search_works_with_hex_code(): void
    {
        Color::factory()->create([
            'name' => 'Rojo',
            'hex_code' => '#FF0000'
        ]);
        Color::factory()->create([
            'name' => 'Azul',
            'hex_code' => '#0000FF'
        ]);

        $component = Livewire::test(Colors::class)
            ->set('search', '#FF0000');

        $component->assertSee('Rojo')
                  ->assertDontSee('Azul');
    }

    public function test_name_is_required(): void
    {
        Livewire::test(Colors::class)
            ->call('create')
            ->set('name', '')
            ->set('hex_code', '#FF0000')
            ->call('store')
            ->assertHasErrors(['name' => 'required']);
    }

    public function test_name_must_be_at_least_2_characters(): void
    {
        Livewire::test(Colors::class)
            ->call('create')
            ->set('name', 'A')
            ->call('store')
            ->assertHasErrors(['name' => 'min']);
    }

    public function test_hex_code_validation(): void
    {
        Livewire::test(Colors::class)
            ->call('create')
            ->set('name', 'Test Color')
            ->set('hex_code', 'invalid-hex')
            ->call('store')
            ->assertHasErrors(['hex_code']);
    }

    public function test_valid_hex_code_formats(): void
    {
        // Test 6-digit hex
        Livewire::test(Colors::class)
            ->call('create')
            ->set('name', 'Color 1')
            ->set('hex_code', '#FF0000')
            ->call('store')
            ->assertHasNoErrors();

        // Test 3-digit hex
        Livewire::test(Colors::class)
            ->call('create')
            ->set('name', 'Color 2')
            ->set('hex_code', '#F00')
            ->call('store')
            ->assertHasNoErrors();
    }
}
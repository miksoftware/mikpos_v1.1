<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Presentations;
use App\Models\Presentation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PresentationsTest extends TestCase
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
        $component = Livewire::test(Presentations::class);
        
        $component->assertStatus(200);
    }

    public function test_can_create_presentation(): void
    {
        Livewire::test(Presentations::class)
            ->call('create')
            ->set('name', 'Caja x12')
            ->set('description', 'Presentación en caja de 12 unidades')
            ->set('is_active', true)
            ->call('store')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('presentations', [
            'name' => 'Caja x12',
            'description' => 'Presentación en caja de 12 unidades',
            'is_active' => true,
        ]);
    }

    public function test_can_edit_presentation(): void
    {
        $presentation = Presentation::factory()->create([
            'name' => 'Blister x10',
            'description' => 'Original description',
        ]);

        Livewire::test(Presentations::class)
            ->call('edit', $presentation->id)
            ->set('name', 'Blister x20')
            ->set('description', 'Updated description')
            ->call('store')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('presentations', [
            'id' => $presentation->id,
            'name' => 'Blister x20',
            'description' => 'Updated description',
        ]);
    }

    public function test_can_delete_presentation(): void
    {
        $presentation = Presentation::factory()->create(['name' => 'Test Presentation']);

        Livewire::test(Presentations::class)
            ->call('confirmDelete', $presentation->id)
            ->call('delete')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('presentations', [
            'id' => $presentation->id,
        ]);
    }

    public function test_can_toggle_presentation_status(): void
    {
        $presentation = Presentation::factory()->create(['is_active' => true]);

        Livewire::test(Presentations::class)
            ->call('toggleStatus', $presentation->id);

        $this->assertDatabaseHas('presentations', [
            'id' => $presentation->id,
            'is_active' => false,
        ]);
    }

    public function test_can_search_presentations(): void
    {
        Presentation::factory()->create(['name' => 'Caja x12']);
        Presentation::factory()->create(['name' => 'Blister x10']);

        $component = Livewire::test(Presentations::class)
            ->set('search', 'Caja');

        $component->assertSee('Caja x12')
                  ->assertDontSee('Blister x10');
    }

    public function test_search_works_with_description(): void
    {
        Presentation::factory()->create([
            'name' => 'Individual',
            'description' => 'Presentación individual especial'
        ]);
        Presentation::factory()->create([
            'name' => 'Pack x6',
            'description' => 'Pack de 6 unidades'
        ]);

        $component = Livewire::test(Presentations::class)
            ->set('search', 'especial');

        $component->assertSee('Individual')
                  ->assertDontSee('Pack x6');
    }

    public function test_name_is_required(): void
    {
        Livewire::test(Presentations::class)
            ->call('create')
            ->set('name', '')
            ->set('description', 'Test description')
            ->call('store')
            ->assertHasErrors(['name' => 'required']);
    }

    public function test_name_must_be_at_least_2_characters(): void
    {
        Livewire::test(Presentations::class)
            ->call('create')
            ->set('name', 'A')
            ->call('store')
            ->assertHasErrors(['name' => 'min']);
    }

    public function test_can_create_presentation_without_description(): void
    {
        Livewire::test(Presentations::class)
            ->call('create')
            ->set('name', 'Individual')
            ->set('is_active', true)
            ->call('store')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('presentations', [
            'name' => 'Individual',
            'is_active' => true,
        ]);
        
        $presentation = Presentation::where('name', 'Individual')->first();
        $this->assertNull($presentation->description);
    }
}
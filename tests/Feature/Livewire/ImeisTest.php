<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Imeis;
use App\Models\Imei;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ImeisTest extends TestCase
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
        $component = Livewire::test(Imeis::class);
        
        $component->assertStatus(200);
    }

    public function test_can_create_imei(): void
    {
        Livewire::test(Imeis::class)
            ->call('create')
            ->set('imei', '123456789012345')
            ->set('imei2', '987654321098765')
            ->set('status', 'available')
            ->set('notes', 'Test IMEI device')
            ->call('store')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('imeis', [
            'imei' => '123456789012345',
            'imei2' => '987654321098765',
            'status' => 'available',
            'notes' => 'Test IMEI device',
        ]);
    }

    public function test_can_create_imei_without_imei2(): void
    {
        Livewire::test(Imeis::class)
            ->call('create')
            ->set('imei', '123456789012345')
            ->set('status', 'available')
            ->call('store')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('imeis', [
            'imei' => '123456789012345',
            'imei2' => null,
            'status' => 'available',
        ]);
    }

    public function test_can_edit_imei(): void
    {
        $imei = Imei::factory()->create([
            'imei' => '111111111111111',
            'status' => 'available',
        ]);

        Livewire::test(Imeis::class)
            ->call('edit', $imei->id)
            ->set('imei', '222222222222222')
            ->set('status', 'sold')
            ->set('notes', 'Updated notes')
            ->call('store')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('imeis', [
            'id' => $imei->id,
            'imei' => '222222222222222',
            'status' => 'sold',
            'notes' => 'Updated notes',
        ]);
    }

    public function test_can_delete_imei(): void
    {
        $imei = Imei::factory()->create(['imei' => '123456789012345']);

        Livewire::test(Imeis::class)
            ->call('confirmDelete', $imei->id)
            ->call('delete')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('imeis', [
            'id' => $imei->id,
        ]);
    }

    public function test_can_search_imeis(): void
    {
        Imei::factory()->create(['imei' => '123456789012345']);
        Imei::factory()->create(['imei' => '987654321098765']);

        $component = Livewire::test(Imeis::class)
            ->set('search', '123456');

        $component->assertSee('123456789012345')
                  ->assertDontSee('987654321098765');
    }

    public function test_can_filter_by_status(): void
    {
        Imei::factory()->available()->create(['imei' => '111111111111111']);
        Imei::factory()->sold()->create(['imei' => '222222222222222']);
        Imei::factory()->reserved()->create(['imei' => '333333333333333']);

        $component = Livewire::test(Imeis::class)
            ->set('filterStatus', 'sold');

        $component->assertSee('222222222222222')
                  ->assertDontSee('111111111111111')
                  ->assertDontSee('333333333333333');
    }

    public function test_search_works_with_notes(): void
    {
        Imei::factory()->create([
            'imei' => '111111111111111',
            'notes' => 'Samsung Galaxy device'
        ]);
        Imei::factory()->create([
            'imei' => '222222222222222',
            'notes' => 'iPhone device'
        ]);

        $component = Livewire::test(Imeis::class)
            ->set('search', 'Samsung');

        $component->assertSee('111111111111111')
                  ->assertDontSee('222222222222222');
    }

    public function test_imei_is_required(): void
    {
        Livewire::test(Imeis::class)
            ->call('create')
            ->set('imei', '')
            ->set('status', 'available')
            ->call('store')
            ->assertHasErrors(['imei' => 'required']);
    }

    public function test_imei_must_be_numeric(): void
    {
        Livewire::test(Imeis::class)
            ->call('create')
            ->set('imei', 'abc123def456789')
            ->set('status', 'available')
            ->call('store')
            ->assertHasErrors(['imei']);
    }

    public function test_imei_must_be_correct_length(): void
    {
        // Too short
        Livewire::test(Imeis::class)
            ->call('create')
            ->set('imei', '12345')
            ->set('status', 'available')
            ->call('store')
            ->assertHasErrors(['imei']);

        // Too long
        Livewire::test(Imeis::class)
            ->call('create')
            ->set('imei', '123456789012345678')
            ->set('status', 'available')
            ->call('store')
            ->assertHasErrors(['imei']);
    }

    public function test_status_is_required(): void
    {
        Livewire::test(Imeis::class)
            ->call('create')
            ->set('imei', '123456789012345')
            ->set('status', '')
            ->call('store')
            ->assertHasErrors(['status' => 'required']);
    }

    public function test_status_must_be_valid_value(): void
    {
        Livewire::test(Imeis::class)
            ->call('create')
            ->set('imei', '123456789012345')
            ->set('status', 'invalid_status')
            ->call('store')
            ->assertHasErrors(['status']);
    }
}
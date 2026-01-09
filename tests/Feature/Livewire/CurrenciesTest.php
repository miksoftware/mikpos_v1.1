<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Currencies;
use App\Models\Currency;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CurrenciesTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Role $superAdminRole;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->superAdminRole = Role::factory()->superAdmin()->create();
        $this->adminUser = User::factory()->create();
        $this->adminUser->roles()->attach($this->superAdminRole->id);
    }

    public function test_currencies_page_can_be_rendered(): void
    {
        $this->actingAs($this->adminUser);
        
        $response = $this->get('/currencies');
        
        $response->assertStatus(200);
        $response->assertSeeLivewire(Currencies::class);
    }

    public function test_currencies_page_displays_currencies_list(): void
    {
        $this->actingAs($this->adminUser);
        
        $currency = Currency::factory()->create(['name' => 'Test Currency']);
        
        Livewire::test(Currencies::class)
            ->assertSee('Test Currency');
    }

    public function test_currencies_can_be_searched(): void
    {
        $this->actingAs($this->adminUser);
        
        $currency1 = Currency::factory()->create(['name' => 'Peso Colombiano']);
        $currency2 = Currency::factory()->create(['name' => 'Dólar Estadounidense']);
        
        Livewire::test(Currencies::class)
            ->set('search', 'Peso')
            ->assertSee('Peso Colombiano')
            ->assertDontSee('Dólar Estadounidense');
    }

    public function test_user_with_permission_can_create_currency(): void
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(Currencies::class)
            ->call('create')
            ->assertSet('isModalOpen', true)
            ->set('name', 'Peso Colombiano')
            ->set('code', 'COP')
            ->set('symbol', '$')
            ->call('store')
            ->assertSet('isModalOpen', false);
        
        $this->assertDatabaseHas('currencies', [
            'name' => 'Peso Colombiano',
            'code' => 'COP',
            'symbol' => '$',
        ]);
    }

    public function test_user_without_permission_cannot_create_currency(): void
    {
        $userWithoutPermission = User::factory()->create();
        $limitedRole = Role::factory()->create(['name' => 'limited']);
        $userWithoutPermission->roles()->attach($limitedRole->id);
        
        $this->actingAs($userWithoutPermission);
        
        Livewire::test(Currencies::class)
            ->call('create')
            ->assertSet('isModalOpen', false)
            ->assertDispatched('notify');
    }

    public function test_user_with_permission_can_edit_currency(): void
    {
        $this->actingAs($this->adminUser);
        
        $currency = Currency::factory()->create([
            'name' => 'Original Currency',
        ]);
        
        Livewire::test(Currencies::class)
            ->call('edit', $currency->id)
            ->assertSet('isModalOpen', true)
            ->assertSet('itemId', $currency->id)
            ->set('name', 'Updated Currency')
            ->call('store')
            ->assertSet('isModalOpen', false);
        
        $this->assertDatabaseHas('currencies', [
            'id' => $currency->id,
            'name' => 'Updated Currency',
        ]);
    }

    public function test_user_with_permission_can_delete_currency(): void
    {
        $this->actingAs($this->adminUser);
        
        $currency = Currency::factory()->create(['name' => 'To Delete']);
        
        Livewire::test(Currencies::class)
            ->call('confirmDelete', $currency->id)
            ->assertSet('isDeleteModalOpen', true)
            ->call('delete')
            ->assertSet('isDeleteModalOpen', false);
        
        $this->assertDatabaseMissing('currencies', [
            'id' => $currency->id,
        ]);
    }

    public function test_currency_status_can_be_toggled(): void
    {
        $this->actingAs($this->adminUser);
        
        $currency = Currency::factory()->create(['is_active' => true]);
        
        Livewire::test(Currencies::class)
            ->call('toggleStatus', $currency->id);
        
        $this->assertDatabaseHas('currencies', [
            'id' => $currency->id,
            'is_active' => false,
        ]);
    }

    public function test_currency_code_must_be_unique(): void
    {
        $this->actingAs($this->adminUser);
        
        Currency::factory()->create(['code' => 'COP']);
        
        Livewire::test(Currencies::class)
            ->call('create')
            ->set('name', 'Another Currency')
            ->set('code', 'COP')
            ->set('symbol', '$')
            ->call('store')
            ->assertHasErrors(['code']);
    }

    public function test_currency_name_is_required(): void
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(Currencies::class)
            ->call('create')
            ->set('name', '')
            ->set('code', 'COP')
            ->set('symbol', '$')
            ->call('store')
            ->assertHasErrors(['name']);
    }

    public function test_activity_log_is_created_on_currency_creation(): void
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(Currencies::class)
            ->call('create')
            ->set('name', 'Logged Currency')
            ->set('code', 'LOG')
            ->set('symbol', 'L')
            ->call('store');
        
        $this->assertDatabaseHas('activity_logs', [
            'module' => 'currencies',
            'action' => 'create',
            'user_id' => $this->adminUser->id,
        ]);
    }

    public function test_activity_log_is_created_on_currency_update(): void
    {
        $this->actingAs($this->adminUser);
        
        $currency = Currency::factory()->create(['name' => 'Original']);
        
        Livewire::test(Currencies::class)
            ->call('edit', $currency->id)
            ->set('name', 'Updated')
            ->call('store');
        
        $this->assertDatabaseHas('activity_logs', [
            'module' => 'currencies',
            'action' => 'update',
            'user_id' => $this->adminUser->id,
        ]);
    }

    public function test_activity_log_is_created_on_currency_deletion(): void
    {
        $this->actingAs($this->adminUser);
        
        $currency = Currency::factory()->create(['name' => 'To Delete']);
        
        Livewire::test(Currencies::class)
            ->call('confirmDelete', $currency->id)
            ->call('delete');
        
        $this->assertDatabaseHas('activity_logs', [
            'module' => 'currencies',
            'action' => 'delete',
            'user_id' => $this->adminUser->id,
        ]);
    }
}
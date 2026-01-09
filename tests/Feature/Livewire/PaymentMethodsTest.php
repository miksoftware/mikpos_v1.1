<?php

namespace Tests\Feature\Livewire;

use App\Livewire\PaymentMethods;
use App\Models\PaymentMethod;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PaymentMethodsTest extends TestCase
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

    public function test_payment_methods_page_can_be_rendered(): void
    {
        $this->actingAs($this->adminUser);
        
        $response = $this->get('/payment-methods');
        
        $response->assertStatus(200);
        $response->assertSeeLivewire(PaymentMethods::class);
    }

    public function test_payment_methods_page_displays_payment_methods_list(): void
    {
        $this->actingAs($this->adminUser);
        
        $paymentMethod = PaymentMethod::factory()->create(['name' => 'Test Payment Method']);
        
        Livewire::test(PaymentMethods::class)
            ->assertSee('Test Payment Method');
    }

    public function test_payment_methods_can_be_searched(): void
    {
        $this->actingAs($this->adminUser);
        
        $paymentMethod1 = PaymentMethod::factory()->create(['name' => 'Efectivo']);
        $paymentMethod2 = PaymentMethod::factory()->create(['name' => 'Tarjeta de Crédito']);
        
        Livewire::test(PaymentMethods::class)
            ->set('search', 'Efectivo')
            ->assertSee('Efectivo')
            ->assertDontSee('Tarjeta de Crédito');
    }

    public function test_user_with_permission_can_create_payment_method(): void
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(PaymentMethods::class)
            ->call('create')
            ->assertSet('isModalOpen', true)
            ->set('dian_code', '10')
            ->set('name', 'Efectivo')
            ->call('store')
            ->assertSet('isModalOpen', false);
        
        $this->assertDatabaseHas('payment_methods', [
            'dian_code' => '10',
            'name' => 'Efectivo',
        ]);
    }

    public function test_user_without_permission_cannot_create_payment_method(): void
    {
        $userWithoutPermission = User::factory()->create();
        $limitedRole = Role::factory()->create(['name' => 'limited']);
        $userWithoutPermission->roles()->attach($limitedRole->id);
        
        $this->actingAs($userWithoutPermission);
        
        Livewire::test(PaymentMethods::class)
            ->call('create')
            ->assertSet('isModalOpen', false)
            ->assertDispatched('notify');
    }

    public function test_user_with_permission_can_edit_payment_method(): void
    {
        $this->actingAs($this->adminUser);
        
        $paymentMethod = PaymentMethod::factory()->create([
            'name' => 'Original Method',
        ]);
        
        Livewire::test(PaymentMethods::class)
            ->call('edit', $paymentMethod->id)
            ->assertSet('isModalOpen', true)
            ->assertSet('itemId', $paymentMethod->id)
            ->set('name', 'Updated Method')
            ->call('store')
            ->assertSet('isModalOpen', false);
        
        $this->assertDatabaseHas('payment_methods', [
            'id' => $paymentMethod->id,
            'name' => 'Updated Method',
        ]);
    }

    public function test_user_with_permission_can_delete_payment_method(): void
    {
        $this->actingAs($this->adminUser);
        
        $paymentMethod = PaymentMethod::factory()->create(['name' => 'To Delete']);
        
        Livewire::test(PaymentMethods::class)
            ->call('confirmDelete', $paymentMethod->id)
            ->assertSet('isDeleteModalOpen', true)
            ->call('delete')
            ->assertSet('isDeleteModalOpen', false);
        
        $this->assertDatabaseMissing('payment_methods', [
            'id' => $paymentMethod->id,
        ]);
    }

    public function test_payment_method_status_can_be_toggled(): void
    {
        $this->actingAs($this->adminUser);
        
        $paymentMethod = PaymentMethod::factory()->create(['is_active' => true]);
        
        Livewire::test(PaymentMethods::class)
            ->call('toggleStatus', $paymentMethod->id);
        
        $this->assertDatabaseHas('payment_methods', [
            'id' => $paymentMethod->id,
            'is_active' => false,
        ]);
    }

    public function test_payment_method_dian_code_must_be_unique(): void
    {
        $this->actingAs($this->adminUser);
        
        PaymentMethod::factory()->create(['dian_code' => '10']);
        
        Livewire::test(PaymentMethods::class)
            ->call('create')
            ->set('dian_code', '10')
            ->set('name', 'Another Method')
            ->call('store')
            ->assertHasErrors(['dian_code']);
    }

    public function test_payment_method_name_is_required(): void
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(PaymentMethods::class)
            ->call('create')
            ->set('dian_code', '10')
            ->set('name', '')
            ->call('store')
            ->assertHasErrors(['name']);
    }

    public function test_activity_log_is_created_on_payment_method_creation(): void
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(PaymentMethods::class)
            ->call('create')
            ->set('dian_code', '10')
            ->set('name', 'Logged Method')
            ->call('store');
        
        $this->assertDatabaseHas('activity_logs', [
            'module' => 'payment_methods',
            'action' => 'create',
            'user_id' => $this->adminUser->id,
        ]);
    }

    public function test_activity_log_is_created_on_payment_method_update(): void
    {
        $this->actingAs($this->adminUser);
        
        $paymentMethod = PaymentMethod::factory()->create(['name' => 'Original']);
        
        Livewire::test(PaymentMethods::class)
            ->call('edit', $paymentMethod->id)
            ->set('name', 'Updated')
            ->call('store');
        
        $this->assertDatabaseHas('activity_logs', [
            'module' => 'payment_methods',
            'action' => 'update',
            'user_id' => $this->adminUser->id,
        ]);
    }

    public function test_activity_log_is_created_on_payment_method_deletion(): void
    {
        $this->actingAs($this->adminUser);
        
        $paymentMethod = PaymentMethod::factory()->create(['name' => 'To Delete']);
        
        Livewire::test(PaymentMethods::class)
            ->call('confirmDelete', $paymentMethod->id)
            ->call('delete');
        
        $this->assertDatabaseHas('activity_logs', [
            'module' => 'payment_methods',
            'action' => 'delete',
            'user_id' => $this->adminUser->id,
        ]);
    }
}
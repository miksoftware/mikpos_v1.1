<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Users;
use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UsersTest extends TestCase
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

    public function test_users_page_can_be_rendered(): void
    {
        $this->actingAs($this->adminUser);
        
        $response = $this->get('/users');
        
        $response->assertStatus(200);
        $response->assertSeeLivewire(Users::class);
    }

    public function test_users_page_displays_users_list(): void
    {
        $this->actingAs($this->adminUser);
        
        $user = User::factory()->create(['name' => 'John Doe']);
        
        Livewire::test(Users::class)
            ->assertSee('John Doe');
    }

    public function test_users_can_be_searched_by_name(): void
    {
        $this->actingAs($this->adminUser);
        
        User::factory()->create(['name' => 'Alice Smith']);
        User::factory()->create(['name' => 'Bob Jones']);
        
        Livewire::test(Users::class)
            ->set('search', 'Alice')
            ->assertSee('Alice Smith')
            ->assertDontSee('Bob Jones');
    }

    public function test_users_can_be_searched_by_email(): void
    {
        $this->actingAs($this->adminUser);
        
        User::factory()->create(['name' => 'Alice', 'email' => 'alice@test.com']);
        User::factory()->create(['name' => 'Bob', 'email' => 'bob@test.com']);
        
        Livewire::test(Users::class)
            ->set('search', 'alice@test')
            ->assertSee('Alice')
            ->assertDontSee('Bob');
    }

    public function test_user_can_be_created(): void
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(Users::class)
            ->call('create')
            ->assertSet('isModalOpen', true)
            ->set('name', 'New User')
            ->set('email', 'newuser@test.com')
            ->set('password', 'password123')
            ->set('role', 'cashier')
            ->call('store')
            ->assertSet('isModalOpen', false);
        
        $this->assertDatabaseHas('users', [
            'name' => 'New User',
            'email' => 'newuser@test.com',
        ]);
    }

    public function test_user_can_be_edited(): void
    {
        $this->actingAs($this->adminUser);
        
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@test.com',
        ]);
        
        Livewire::test(Users::class)
            ->call('edit', $user->id)
            ->assertSet('isModalOpen', true)
            ->assertSet('userId', $user->id)
            ->assertSet('name', 'Original Name')
            ->set('name', 'Updated Name')
            ->set('email', 'original@test.com')
            ->set('role', 'cashier')
            ->call('store')
            ->assertSet('isModalOpen', false);
        
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_user_can_be_deleted(): void
    {
        $this->actingAs($this->adminUser);
        
        $user = User::factory()->create(['name' => 'To Delete']);
        
        Livewire::test(Users::class)
            ->call('confirmDelete', $user->id)
            ->assertSet('isDeleteModalOpen', true)
            ->call('delete')
            ->assertSet('isDeleteModalOpen', false);
        
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_user_status_can_be_toggled(): void
    {
        $this->actingAs($this->adminUser);
        
        $user = User::factory()->create(['is_active' => true]);
        
        Livewire::test(Users::class)
            ->call('toggleStatus', $user->id);
        
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_active' => false,
        ]);
    }

    public function test_user_name_is_required(): void
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(Users::class)
            ->call('create')
            ->set('name', '')
            ->set('email', 'test@test.com')
            ->set('password', 'password123')
            ->set('role', 'cashier')
            ->call('store')
            ->assertHasErrors(['name']);
    }

    public function test_user_email_is_required(): void
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(Users::class)
            ->call('create')
            ->set('name', 'Test User')
            ->set('email', '')
            ->set('password', 'password123')
            ->set('role', 'cashier')
            ->call('store')
            ->assertHasErrors(['email']);
    }

    public function test_user_email_must_be_unique(): void
    {
        $this->actingAs($this->adminUser);
        
        User::factory()->create(['email' => 'existing@test.com']);
        
        Livewire::test(Users::class)
            ->call('create')
            ->set('name', 'Test User')
            ->set('email', 'existing@test.com')
            ->set('password', 'password123')
            ->set('role', 'cashier')
            ->call('store')
            ->assertHasErrors(['email']);
    }

    public function test_password_is_required_for_new_user(): void
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(Users::class)
            ->call('create')
            ->set('name', 'Test User')
            ->set('email', 'test@test.com')
            ->set('password', '')
            ->set('role', 'cashier')
            ->call('store')
            ->assertHasErrors(['password']);
    }

    public function test_password_is_optional_when_editing(): void
    {
        $this->actingAs($this->adminUser);
        
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'testuser@test.com',
        ]);
        
        Livewire::test(Users::class)
            ->call('edit', $user->id)
            ->set('name', 'Updated Name')
            ->set('email', 'testuser@test.com')
            ->set('role', 'cashier')
            ->set('password', '')
            ->call('store')
            ->assertHasNoErrors(['password']);
        
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_user_can_be_assigned_to_branch(): void
    {
        $this->actingAs($this->adminUser);
        
        $branch = Branch::factory()->create();
        
        Livewire::test(Users::class)
            ->call('create')
            ->set('name', 'Branch User')
            ->set('email', 'branch@test.com')
            ->set('password', 'password123')
            ->set('role', 'cashier')
            ->set('branch_id', $branch->id)
            ->call('store');
        
        $this->assertDatabaseHas('users', [
            'email' => 'branch@test.com',
            'branch_id' => $branch->id,
        ]);
    }

    public function test_branches_are_available_in_form(): void
    {
        $this->actingAs($this->adminUser);
        
        $branch = Branch::factory()->create(['name' => 'Main Branch']);
        
        Livewire::test(Users::class)
            ->assertViewHas('branches', function ($branches) use ($branch) {
                return $branches->contains('id', $branch->id);
            });
    }

    public function test_password_must_be_at_least_6_characters(): void
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(Users::class)
            ->call('create')
            ->set('name', 'Test User')
            ->set('email', 'test@test.com')
            ->set('password', '12345')
            ->set('role', 'cashier')
            ->call('store')
            ->assertHasErrors(['password']);
    }

    public function test_role_must_be_valid(): void
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(Users::class)
            ->call('create')
            ->set('name', 'Test User')
            ->set('email', 'test@test.com')
            ->set('password', 'password123')
            ->set('role', 'invalid_role')
            ->call('store')
            ->assertHasErrors(['role']);
    }
}

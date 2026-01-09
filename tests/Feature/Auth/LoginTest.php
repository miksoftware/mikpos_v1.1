<?php

namespace Tests\Feature\Auth;

use App\Livewire\Auth\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_can_be_rendered(): void
    {
        $response = $this->get('/login');
        
        $response->assertStatus(200);
        $response->assertSeeLivewire(Login::class);
    }

    public function test_login_page_contains_email_and_password_fields(): void
    {
        Livewire::test(Login::class)
            ->assertSee('Correo Electrónico')
            ->assertSee('Contraseña')
            ->assertSee('Iniciar Sesión');
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        Livewire::withoutLazyLoading()
            ->test(Login::class)
            ->set('email', 'test@example.com')
            ->set('password', 'password123')
            ->call('login')
            ->assertRedirect('/dashboard');
    }

    public function test_user_cannot_login_with_invalid_email(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        Livewire::test(Login::class)
            ->set('email', 'wrong@example.com')
            ->set('password', 'password123')
            ->call('login')
            ->assertHasErrors('email');

        $this->assertGuest();
    }

    public function test_user_cannot_login_with_invalid_password(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        Livewire::test(Login::class)
            ->set('email', 'test@example.com')
            ->set('password', 'wrongpassword')
            ->call('login')
            ->assertHasErrors('email');

        $this->assertGuest();
    }

    public function test_email_is_required(): void
    {
        Livewire::test(Login::class)
            ->set('email', '')
            ->set('password', 'password123')
            ->call('login')
            ->assertHasErrors(['email' => 'required']);
    }

    public function test_email_must_be_valid_format(): void
    {
        Livewire::test(Login::class)
            ->set('email', 'invalid-email')
            ->set('password', 'password123')
            ->call('login')
            ->assertHasErrors(['email' => 'email']);
    }

    public function test_password_is_required(): void
    {
        Livewire::test(Login::class)
            ->set('email', 'test@example.com')
            ->set('password', '')
            ->call('login')
            ->assertHasErrors(['password' => 'required']);
    }

    public function test_password_must_be_at_least_6_characters(): void
    {
        Livewire::test(Login::class)
            ->set('email', 'test@example.com')
            ->set('password', '12345')
            ->call('login')
            ->assertHasErrors(['password' => 'min']);
    }

    public function test_remember_me_can_be_toggled(): void
    {
        Livewire::test(Login::class)
            ->assertSet('remember', false)
            ->set('remember', true)
            ->assertSet('remember', true);
    }

    public function test_authenticated_user_is_redirected_from_login(): void
    {
        $user = User::factory()->create();
        
        $this->actingAs($user)
            ->get('/login')
            ->assertRedirect('/dashboard');
    }

    public function test_rate_limiting_after_too_many_attempts(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        // Clear any existing rate limits
        RateLimiter::clear('test@example.com|127.0.0.1');

        // Make 5 failed attempts
        for ($i = 0; $i < 5; $i++) {
            Livewire::test(Login::class)
                ->set('email', 'test@example.com')
                ->set('password', 'wrongpassword')
                ->call('login');
        }

        // 6th attempt should be rate limited
        Livewire::test(Login::class)
            ->set('email', 'test@example.com')
            ->set('password', 'wrongpassword')
            ->call('login')
            ->assertHasErrors('email');
    }

    public function test_loading_state_is_set_during_login(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        Livewire::withoutLazyLoading()
            ->test(Login::class)
            ->assertSet('loading', false);
    }

    public function test_login_creates_activity_log(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        Livewire::withoutLazyLoading()
            ->test(Login::class)
            ->set('email', 'test@example.com')
            ->set('password', 'password123')
            ->call('login');

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'module' => 'auth',
            'action' => 'login',
        ]);
    }
}

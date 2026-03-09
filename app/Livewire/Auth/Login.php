<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

#[Layout('layouts.guest')]
#[Title('Login - MikPOS')]
class Login extends Component
{
    public string $email = '';
    public string $password = '';
    public bool $remember = false;
    public bool $loading = false;

    protected $rules = [
        'email' => 'required|email',
        'password' => 'required|min:6',
    ];

    protected $messages = [
        'email.required' => 'El correo electrónico es requerido',
        'email.email' => 'Por favor ingresa un correo electrónico válido',
        'password.required' => 'La contraseña es requerida',
        'password.min' => 'La contraseña debe tener al menos 6 caracteres',
    ];

    public function mount()
    {
        // Redirect to install if not installed
        if (!File::exists(storage_path('installed.lock'))) {
            return redirect('/install');
        }
    }

    public function login()
    {
        $this->loading = true;

        try {
            $this->validate();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->loading = false;
            throw $e;
        }

        $throttleKey = Str::transliterate(Str::lower($this->email).'|'.request()->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->addError('email', "Demasiados intentos. Intente nuevamente en {$seconds} segundos.");
            $this->loading = false;
            return;
        }

        if (Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::clear($throttleKey);
            
            if (request()->hasSession()) {
                request()->session()->regenerate();
            }
            
            ActivityLogService::logLogin();
            
            return redirect()->intended('/dashboard');
        }

        RateLimiter::hit($throttleKey, 60);

        $this->addError('email', 'Las credenciales proporcionadas no coinciden con nuestros registros.');
        $this->loading = false;
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}

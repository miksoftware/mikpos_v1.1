<?php

namespace App\Livewire\Shop\Auth;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.shop')]
class Login extends Component
{
    public string $email = '';
    public string $password = '';

    protected $rules = [
        'email' => 'required|email',
        'password' => 'required|min:8',
    ];

    protected $messages = [
        'email.required' => 'Ingrese el correo electrónico.',
        'email.email' => 'Ingrese un correo electrónico válido.',
        'password.required' => 'Ingrese la contraseña.',
        'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
    ];

    public function login(): void
    {
        $this->validate();

        $customer = \App\Models\Customer::where('email', $this->email)->first();
        if ($customer && !$customer->is_active) {
            $this->addError('email', 'Tu cuenta se encuentra inactiva. Comunícate con la administración.');
            return;
        }

        if (! Auth::guard('customer')->attempt(['email' => $this->email, 'password' => $this->password, 'is_active' => true])) {
            $this->addError('email', 'Las credenciales proporcionadas son incorrectas.');
            return;
        }

        session()->regenerate();

        $this->redirect('/shop', navigate: true);
    }

    public function render()
    {
        return view('livewire.shop.auth.login');
    }
}

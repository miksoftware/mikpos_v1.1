<?php

namespace App\Livewire\Shop\Auth;

use App\Models\Customer;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.shop')]
class ForgotPassword extends Component
{
    // Step 1: Verify identity
    public string $document_number = '';
    public string $email = '';

    // Step 2: Set new password
    public string $new_password = '';
    public string $new_password_confirmation = '';

    public int $step = 1;
    public bool $success = false;
    public ?int $customerId = null;

    public function verifyIdentity(): void
    {
        $this->validate([
            'document_number' => 'required|string|min:3',
            'email' => 'required|email',
        ], [
            'document_number.required' => 'Ingrese el número de documento.',
            'document_number.min' => 'El número de documento debe tener al menos 3 caracteres.',
            'email.required' => 'Ingrese el correo electrónico.',
            'email.email' => 'Ingrese un correo electrónico válido.',
        ]);

        $customer = Customer::where('document_number', $this->document_number)
            ->where('email', $this->email)
            ->first();

        if (!$customer) {
            $this->addError('email', 'No se encontró una cuenta con estos datos. Verifique el número de documento y correo electrónico.');
            return;
        }

        $this->customerId = $customer->id;
        $this->step = 2;
    }

    public function resetPassword(): void
    {
        $this->validate([
            'new_password' => 'required|string|min:8|confirmed',
        ], [
            'new_password.required' => 'Ingrese la nueva contraseña.',
            'new_password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'new_password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        $customer = Customer::find($this->customerId);
        if (!$customer) {
            $this->addError('new_password', 'Error al procesar la solicitud. Intente de nuevo.');
            $this->step = 1;
            return;
        }

        $customer->password = $this->new_password;
        $customer->save();

        $this->success = true;
    }

    public function render()
    {
        return view('livewire.shop.auth.forgot-password');
    }
}

<?php

namespace App\Livewire\Shop\Auth;

use App\Models\Customer;
use App\Models\Department;
use App\Models\Municipality;
use App\Models\TaxDocument;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.shop')]
class Register extends Component
{
    public string $customer_type = 'natural';
    public string $tax_document_id = '';
    public string $document_number = '';
    public string $first_name = '';
    public string $last_name = '';
    public string $business_name = '';
    public string $phone = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $department_id = '';
    public string $municipality_id = '';
    public string $address = '';

    public array $municipalities = [];

    public function updatedDepartmentId()
    {
        $this->municipality_id = '';
        $this->municipalities = $this->department_id
            ? Municipality::where('department_id', $this->department_id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->toArray()
            : [];
    }

    public function rules(): array
    {
        return [
            'customer_type' => 'required|in:natural,juridico',
            'tax_document_id' => 'required|exists:tax_documents,id',
            'document_number' => [
                'required',
                'string',
                'min:3',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $exists = Customer::where('document_number', $value)
                        ->where('tax_document_id', $this->tax_document_id)
                        ->exists();
                    if ($exists) {
                        $fail('Este número de documento ya está registrado para este tipo de documento.');
                    }
                },
            ],
            'first_name' => 'required|string|min:2',
            'last_name' => 'required|string|min:2',
            'business_name' => $this->customer_type === 'juridico' ? 'required|string|min:2' : 'nullable|string',
            'phone' => 'nullable|string',
            'email' => 'required|email|unique:customers,email',
            'password' => 'required|string|min:8|confirmed',
            'department_id' => 'required|exists:departments,id',
            'municipality_id' => 'required|exists:municipalities,id',
            'address' => 'required|string|min:5',
        ];
    }

    public function messages(): array
    {
        return [
            'customer_type.required' => 'Seleccione el tipo de persona.',
            'tax_document_id.required' => 'Seleccione el tipo de documento.',
            'document_number.required' => 'Ingrese el número de documento.',
            'first_name.required' => 'Ingrese el nombre.',
            'last_name.required' => 'Ingrese el apellido.',
            'business_name.required' => 'Ingrese la razón social.',
            'email.required' => 'Ingrese el correo electrónico.',
            'email.email' => 'Ingrese un correo electrónico válido.',
            'email.unique' => 'Este correo electrónico ya está registrado.',
            'password.required' => 'Ingrese la contraseña.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'department_id.required' => 'Seleccione el departamento.',
            'municipality_id.required' => 'Seleccione el municipio.',
            'address.required' => 'Ingrese la dirección.',
            'address.min' => 'La dirección debe tener al menos 5 caracteres.',
        ];
    }

    public function register(): void
    {
        $this->validate();

        $customer = Customer::create([
            'branch_id' => config('ecommerce.branch_id'),
            'customer_type' => $this->customer_type,
            'tax_document_id' => $this->tax_document_id,
            'document_number' => $this->document_number,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'business_name' => $this->customer_type === 'juridico' ? $this->business_name : null,
            'phone' => $this->phone ?: null,
            'email' => $this->email,
            'password' => $this->password,
            'department_id' => $this->department_id,
            'municipality_id' => $this->municipality_id,
            'address' => $this->address,
            'is_active' => true,
        ]);

        Auth::guard('customer')->login($customer);

        $this->redirect('/shop', navigate: true);
    }

    public function render()
    {
        return view('livewire.shop.auth.register', [
            'taxDocuments' => TaxDocument::where('is_active', true)->get(),
            'departments' => Department::where('is_active', true)->orderBy('name')->get(),
        ]);
    }
}

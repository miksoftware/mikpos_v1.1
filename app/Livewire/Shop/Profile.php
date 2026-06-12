<?php

namespace App\Livewire\Shop;

use App\Models\Customer;
use App\Models\Department;
use App\Models\Municipality;
use App\Models\TaxDocument;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.shop')]
class Profile extends Component
{
    public string $customer_type = 'natural';
    public string $tax_document_id = '';
    public string $document_number = '';
    public string $first_name = '';
    public string $last_name = '';
    public string $business_name = '';
    public string $phone = '';
    public string $email = '';
    public string $department_id = '';
    public string $municipality_id = '';
    public string $address = '';

    public string $current_password = '';
    public string $new_password = '';
    public string $new_password_confirmation = '';

    public array $municipalities = [];

    public function mount(): void
    {
        $customer = Auth::guard('customer')->user();

        $this->customer_type = $customer->customer_type ?? 'natural';
        $this->tax_document_id = (string) ($customer->tax_document_id ?? '');
        $this->document_number = (string) ($customer->document_number ?? '');
        $this->first_name = (string) ($customer->first_name ?? '');
        $this->last_name = (string) ($customer->last_name ?? '');
        $this->business_name = (string) ($customer->business_name ?? '');
        $this->phone = (string) ($customer->phone ?? '');
        $this->email = (string) ($customer->email ?? '');
        $this->department_id = (string) ($customer->department_id ?? '');
        $this->municipality_id = (string) ($customer->municipality_id ?? '');
        $this->address = (string) ($customer->address ?? '');

        $this->loadMunicipalities(keepSelected: true);
    }

    public function updatedDepartmentId(): void
    {
        $this->municipality_id = '';
        $this->loadMunicipalities(keepSelected: false);
    }

    protected function loadMunicipalities(bool $keepSelected): void
    {
        $this->municipalities = $this->department_id
            ? Municipality::where('department_id', $this->department_id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->toArray()
            : [];

        if ($keepSelected && $this->municipality_id !== '') {
            $exists = collect($this->municipalities)->contains(fn ($m) => (string) $m['id'] === (string) $this->municipality_id);
            if (!$exists) {
                $this->municipality_id = '';
            }
        }
    }

    public function rules(): array
    {
        $customerId = Auth::guard('customer')->id();

        return [
            'customer_type' => 'required|in:natural,juridico',
            'tax_document_id' => 'required|exists:tax_documents,id',
            'document_number' => [
                'required',
                'string',
                'min:3',
                function (string $attribute, mixed $value, \Closure $fail) use ($customerId) {
                    $exists = Customer::where('document_number', $value)
                        ->where('tax_document_id', $this->tax_document_id)
                        ->where('id', '!=', $customerId)
                        ->exists();
                    if ($exists) {
                        $fail('Este número de documento ya está registrado para este tipo de documento.');
                    }
                },
            ],
            'first_name' => 'required|string|min:2',
            'last_name' => 'required|string|min:2',
            'business_name' => $this->customer_type === 'juridico' ? 'required|string|min:2' : 'nullable|string',
            'phone' => ['required', 'string', 'min:10', 'max:20', 'regex:/^\\+?[0-9\\s\\-()]+$/'],
            'email' => 'required|email|unique:customers,email,' . $customerId,
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
            'phone.required' => 'Ingrese el teléfono.',
            'phone.regex' => 'Ingrese un teléfono válido.',
            'email.required' => 'Ingrese el correo electrónico.',
            'email.email' => 'Ingrese un correo electrónico válido.',
            'email.unique' => 'Este correo electrónico ya está registrado.',
            'department_id.required' => 'Seleccione el departamento.',
            'municipality_id.required' => 'Seleccione el municipio.',
            'address.required' => 'Ingrese la dirección.',
            'address.min' => 'La dirección debe tener al menos 5 caracteres.',
        ];
    }

    public function save(): void
    {
        $this->validate();

        $customer = Auth::guard('customer')->user();
        $phoneDigits = preg_replace('/\\D+/', '', $this->phone) ?? '';

        $customer->update([
            'customer_type' => $this->customer_type,
            'tax_document_id' => $this->tax_document_id,
            'document_number' => $this->document_number,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'business_name' => $this->customer_type === 'juridico' ? $this->business_name : null,
            'phone' => $phoneDigits,
            'email' => $this->email,
            'department_id' => $this->department_id,
            'municipality_id' => $this->municipality_id,
            'address' => $this->address,
        ]);

        $this->dispatch('notify', message: 'Perfil actualizado correctamente', type: 'success');
    }

    public function changePassword(): void
    {
        $this->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ], [
            'current_password.required' => 'Ingrese su contraseña actual.',
            'new_password.required' => 'Ingrese la nueva contraseña.',
            'new_password.min' => 'La nueva contraseña debe tener al menos 8 caracteres.',
            'new_password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        $customer = Auth::guard('customer')->user();

        if (!Hash::check($this->current_password, (string) $customer->password)) {
            $this->addError('current_password', 'La contraseña actual es incorrecta.');
            return;
        }

        $customer->update([
            'password' => $this->new_password,
        ]);

        $this->current_password = '';
        $this->new_password = '';
        $this->new_password_confirmation = '';
        $this->resetValidation(['current_password', 'new_password', 'new_password_confirmation']);

        $this->dispatch('notify', message: 'Contraseña actualizada correctamente', type: 'success');
    }

    public function render()
    {
        return view('livewire.shop.profile', [
            'taxDocuments' => TaxDocument::where('is_active', true)->get(),
            'departments' => Department::where('is_active', true)->orderBy('name')->get(),
        ]);
    }
}

<?php

namespace App\Livewire;

use App\Models\PaymentMethod;
use App\Services\ActivityLogService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class PaymentMethods extends Component
{
    use WithPagination;

    public $search = '';
    public $isModalOpen = false;
    public $isDeleteModalOpen = false;
    public $itemIdToDelete = null;

    public $itemId;
    public $dian_code;
    public $name;
    public $is_active = true;

    /**
     * DIAN payment method codes with descriptions.
     * These are the official codes for electronic invoicing in Colombia.
     */
    public static array $dianPaymentTypes = [
        '10' => [
            'name' => 'Efectivo',
            'description' => 'Pago en dinero físico (billetes y monedas)',
            'icon' => 'cash',
            'suggested_names' => ['Efectivo', 'Cash', 'Contado'],
        ],
        '49' => [
            'name' => 'Tarjeta Débito',
            'description' => 'Pago con tarjeta de débito bancaria',
            'icon' => 'card',
            'suggested_names' => ['Tarjeta Débito', 'Débito', 'Tarjeta Bancaria'],
        ],
        '48' => [
            'name' => 'Tarjeta Crédito',
            'description' => 'Pago con tarjeta de crédito',
            'icon' => 'card',
            'suggested_names' => ['Tarjeta Crédito', 'Crédito', 'Visa/Mastercard'],
        ],
        '47' => [
            'name' => 'Transferencia Bancaria',
            'description' => 'Transferencias electrónicas: PSE, Nequi, Daviplata, Bancolombia, etc.',
            'icon' => 'transfer',
            'suggested_names' => ['Nequi', 'Daviplata', 'PSE', 'Transferencia Bancolombia', 'Transferencia'],
        ],
        '42' => [
            'name' => 'Consignación Bancaria',
            'description' => 'Depósito directo en cuenta bancaria',
            'icon' => 'bank',
            'suggested_names' => ['Consignación', 'Depósito Bancario'],
        ],
        '20' => [
            'name' => 'Cheque',
            'description' => 'Pago mediante cheque bancario',
            'icon' => 'document',
            'suggested_names' => ['Cheque', 'Cheque Bancario'],
        ],
        '71' => [
            'name' => 'Bonos',
            'description' => 'Pago con bonos o certificados de regalo',
            'icon' => 'gift',
            'suggested_names' => ['Bonos', 'Bono Regalo', 'Gift Card'],
        ],
        '72' => [
            'name' => 'Vales',
            'description' => 'Pago con vales o cupones',
            'icon' => 'ticket',
            'suggested_names' => ['Vales', 'Cupones', 'Voucher'],
        ],
        'ZZ' => [
            'name' => 'Otro',
            'description' => 'Otros medios no clasificados (criptomonedas, pagos internacionales, etc.)',
            'icon' => 'dots',
            'suggested_names' => ['Otro', 'Cripto', 'PayPal'],
        ],
        '1' => [
            'name' => 'No Definido',
            'description' => 'Medio de pago sin clasificación específica',
            'icon' => 'question',
            'suggested_names' => ['Sin Definir', 'Otro'],
        ],
    ];

    public function getDianPaymentTypes(): array
    {
        return self::$dianPaymentTypes;
    }

    public function getSelectedDianType(): ?array
    {
        if ($this->dian_code && isset(self::$dianPaymentTypes[$this->dian_code])) {
            return self::$dianPaymentTypes[$this->dian_code];
        }
        return null;
    }

    public function updatedDianCode($value)
    {
        // Auto-suggest name when DIAN code is selected and name is empty
        if ($value && empty($this->name) && isset(self::$dianPaymentTypes[$value])) {
            $this->name = self::$dianPaymentTypes[$value]['name'];
        }
    }

    public function render()
    {
        $items = PaymentMethod::query()
            ->when(trim($this->search), fn($q) => $q->where('name', 'like', "%" . trim($this->search) . "%"))
            ->latest()
            ->paginate(10);

        return view('livewire.payment-methods', [
            'items' => $items,
            'dianTypes' => self::$dianPaymentTypes,
        ]);
    }

    public function create()
    {
        if (!auth()->user()->hasPermission('payment_methods.create')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->resetValidation();
        $this->resetForm();
        $this->isModalOpen = true;
    }

    public function edit($id)
    {
        if (!auth()->user()->hasPermission('payment_methods.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->resetValidation();
        $item = PaymentMethod::findOrFail($id);
        $this->itemId = $item->id;
        $this->dian_code = $item->dian_code;
        $this->name = $item->name;
        $this->is_active = $item->is_active;
        $this->isModalOpen = true;
    }

    public function store()
    {
        $isNew = !$this->itemId;
        if (!auth()->user()->hasPermission($isNew ? 'payment_methods.create' : 'payment_methods.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $this->validate([
            'dian_code' => 'required|in:' . implode(',', array_keys(self::$dianPaymentTypes)),
            'name' => 'required|min:2|max:100',
        ], [
            'dian_code.required' => 'Selecciona un tipo de pago DIAN',
            'dian_code.in' => 'Tipo de pago DIAN inválido',
            'name.required' => 'El nombre es obligatorio',
            'name.min' => 'El nombre debe tener al menos 2 caracteres',
        ]);

        $oldValues = $isNew ? null : PaymentMethod::find($this->itemId)->toArray();
        $item = PaymentMethod::updateOrCreate(['id' => $this->itemId], [
            'dian_code' => $this->dian_code,
            'name' => $this->name,
            'is_active' => $this->is_active,
        ]);

        $isNew ? ActivityLogService::logCreate('payment_methods', $item, "Medio de pago '{$item->name}' creado")
               : ActivityLogService::logUpdate('payment_methods', $item, $oldValues, "Medio de pago '{$item->name}' actualizado");

        $this->isModalOpen = false;
        $this->dispatch('notify', message: $isNew ? 'Medio de pago creado' : 'Medio de pago actualizado');
    }

    public function confirmDelete($id)
    {
        if (!auth()->user()->hasPermission('payment_methods.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->itemIdToDelete = $id;
        $this->isDeleteModalOpen = true;
    }

    public function delete()
    {
        if (!auth()->user()->hasPermission('payment_methods.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $item = PaymentMethod::find($this->itemIdToDelete);
        if (\DB::table('sale_payments')->where('payment_method_id', $item->id)->exists()
            || \DB::table('credit_payments')->where('payment_method_id', $item->id)->exists()) {
            $this->dispatch('notify', message: 'No se puede eliminar: tiene transacciones asociadas. Desactívelo en su lugar.', type: 'error');
            $this->isDeleteModalOpen = false;
            return;
        }
        ActivityLogService::logDelete('payment_methods', $item, "Medio de pago '{$item->name}' eliminado");
        $item->delete();
        $this->isDeleteModalOpen = false;
        $this->dispatch('notify', message: 'Medio de pago eliminado');
    }

    public function toggleStatus($id)
    {
        if (!auth()->user()->hasPermission('payment_methods.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $item = PaymentMethod::find($id);
        $oldValues = $item->toArray();
        $item->is_active = !$item->is_active;
        $item->save();
        ActivityLogService::logUpdate('payment_methods', $item, $oldValues, "Medio de pago '{$item->name}' " . ($item->is_active ? 'activado' : 'desactivado'));
        $this->dispatch('notify', message: $item->is_active ? 'Activado' : 'Desactivado');
    }

    private function resetForm()
    {
        $this->itemId = null;
        $this->dian_code = '';
        $this->name = '';
        $this->is_active = true;
    }
}

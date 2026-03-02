<?php

namespace App\Livewire\Nomina;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\PayrollCalculatorService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Employees extends Component
{
    use WithPagination;

    public $search = '';
    public $filterBranch = '';
    public $filterStatus = '';
    public $filterContractType = '';
    public $isModalOpen = false;
    public $isDeleteModalOpen = false;
    public $isShowModalOpen = false;
    public $itemIdToDelete = null;

    // Form properties
    public $itemId;
    public $user_id;
    public $branch_id;
    public $first_name, $last_name;
    public $document_type = 'CC', $document_number;
    public $email, $phone, $address;
    public $birth_date, $hire_date;
    public $position, $department;
    public $contract_type = 'indefinido';
    public $salary_type = 'minimo';
    public $base_salary;
    public $transport_allowance = true;
    public $transport_included_in_salary = false;
    public $risk_level = 'I';
    public $payment_frequency = 'mensual';
    public $health_fund, $pension_fund, $severance_fund, $compensation_fund;
    public $bank_name, $bank_account_type, $bank_account_number;
    public $status = 'activo';
    public $termination_date, $termination_reason;

    // Computed display values
    public $computed_base_salary = 0;
    public $computed_transport = 0;

    // Show modal data
    public $showEmployee = null;

    // Branch control
    public bool $needsBranchSelection = false;
    public $branches = [];

    public function mount()
    {
        $user = auth()->user();
        $this->needsBranchSelection = $user->isSuperAdmin() || !$user->branch_id;
        if ($this->needsBranchSelection) {
            $this->branches = Branch::where('is_active', true)->orderBy('name')->get();
        }
        $this->updateComputedValues();
    }

    /**
     * Cuando cambia salary_type, auto-cargar valores.
     */
    public function updatedSalaryType()
    {
        $smmlv = PayrollCalculatorService::SMMLV;
        $transport = PayrollCalculatorService::TRANSPORT_ALLOWANCE;

        if ($this->salary_type === 'minimo') {
            $this->base_salary = $smmlv;
            $this->transport_allowance = true;
            $this->transport_included_in_salary = false;
        } elseif ($this->salary_type === 'integral') {
            $this->base_salary = $smmlv * 13;
            $this->transport_allowance = false;
            $this->transport_included_in_salary = false;
        } else {
            // 'otro' - limpiar para que el usuario ingrese
            $this->base_salary = '';
            $this->transport_included_in_salary = false;
        }

        $this->updateComputedValues();
    }

    /**
     * Cuando cambia el contrato, ajustar campos visibles.
     */
    public function updatedContractType()
    {
        if ($this->contract_type === 'prestacion_servicios') {
            // Contratista paga su propia seguridad social
            $this->health_fund = '';
            $this->pension_fund = '';
            $this->severance_fund = '';
            $this->compensation_fund = '';
            $this->transport_allowance = false;
            $this->transport_included_in_salary = false;
        } elseif ($this->contract_type === 'aprendizaje') {
            $smmlv = PayrollCalculatorService::SMMLV;
            $this->base_salary = round($smmlv * 0.75, 0); // 75% SMMLV etapa productiva
            $this->transport_allowance = true;
            $this->transport_included_in_salary = false;
            $this->salary_type = 'otro';
        }

        $this->updateComputedValues();
    }

    public function updatedBaseSalary()
    {
        $this->updateComputedValues();
    }

    public function updatedTransportIncludedInSalary()
    {
        $this->updateComputedValues();
    }

    public function updatedTransportAllowance()
    {
        if (!$this->transport_allowance) {
            $this->transport_included_in_salary = false;
        }
        $this->updateComputedValues();
    }

    /**
     * Calcula valores de display: salario base real y auxilio.
     */
    private function updateComputedValues()
    {
        $smmlv = PayrollCalculatorService::SMMLV;
        $transport = PayrollCalculatorService::TRANSPORT_ALLOWANCE;
        $salary = (float) ($this->base_salary ?: 0);

        if ($this->salary_type === 'minimo') {
            $this->computed_base_salary = $smmlv;
            $this->computed_transport = $transport;
        } elseif ($this->salary_type === 'integral') {
            $this->computed_base_salary = $salary;
            $this->computed_transport = 0;
        } else {
            if ($this->transport_included_in_salary && $this->transport_allowance && $salary > $transport) {
                $this->computed_base_salary = $salary - $transport;
                $this->computed_transport = $transport;
            } else {
                $this->computed_base_salary = $salary;
                $this->computed_transport = ($this->transport_allowance && $salary <= ($smmlv * 2)) ? $transport : 0;
            }
        }
    }

    public function render()
    {
        $user = auth()->user();
        $query = Employee::query()->with(['branch', 'user']);

        if ($this->needsBranchSelection) {
            if ($this->filterBranch) {
                $query->where('employees.branch_id', $this->filterBranch);
            }
        } else {
            $query->where('employees.branch_id', $user->branch_id);
        }

        $items = $query
            ->when(trim($this->search), function ($q) {
                $search = trim($this->search);
                $q->where(function ($query) use ($search) {
                    $query->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('document_number', 'like', "%{$search}%")
                        ->orWhere('position', 'like', "%{$search}%")
                        ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
                });
            })
            ->when($this->filterStatus, fn($q) => $q->where('employees.status', $this->filterStatus))
            ->when($this->filterContractType, fn($q) => $q->where('employees.contract_type', $this->filterContractType))
            ->latest()
            ->paginate(10);

        $users = User::where('is_active', true)->orderBy('name')
            ->whereDoesntHave('employee', function ($q) {
                if ($this->itemId) {
                    $q->where('employees.id', '!=', $this->itemId);
                }
            })
            ->get();

        return view('livewire.nomina.employees', [
            'items' => $items,
            'users' => $users,
            'smmlv' => PayrollCalculatorService::SMMLV,
            'transportAllowanceValue' => PayrollCalculatorService::TRANSPORT_ALLOWANCE,
        ]);
    }

    public function create()
    {
        if (!auth()->user()->hasPermission('employees.create')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->resetValidation();
        $this->resetForm();
        $user = auth()->user();
        if (!$this->needsBranchSelection && $user->branch_id) {
            $this->branch_id = $user->branch_id;
        }
        $this->updatedSalaryType(); // Auto-cargar valores de salario mínimo
        $this->isModalOpen = true;
    }

    public function edit($id)
    {
        if (!auth()->user()->hasPermission('employees.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->resetValidation();
        $item = Employee::findOrFail($id);
        $this->itemId = $item->id;
        $this->user_id = $item->user_id;
        $this->branch_id = $item->branch_id;
        $this->first_name = $item->first_name;
        $this->last_name = $item->last_name;
        $this->document_type = $item->document_type;
        $this->document_number = $item->document_number;
        $this->email = $item->email;
        $this->phone = $item->phone;
        $this->address = $item->address;
        $this->birth_date = $item->birth_date?->format('Y-m-d');
        $this->hire_date = $item->hire_date?->format('Y-m-d');
        $this->position = $item->position;
        $this->department = $item->department;
        $this->contract_type = $item->contract_type;
        $this->salary_type = $item->salary_type;
        $this->base_salary = $item->base_salary;
        $this->transport_allowance = $item->transport_allowance;
        $this->transport_included_in_salary = $item->transport_included_in_salary;
        $this->risk_level = $item->risk_level;
        $this->payment_frequency = $item->payment_frequency ?? 'mensual';
        $this->health_fund = $item->health_fund;
        $this->pension_fund = $item->pension_fund;
        $this->severance_fund = $item->severance_fund;
        $this->compensation_fund = $item->compensation_fund;
        $this->bank_name = $item->bank_name;
        $this->bank_account_type = $item->bank_account_type;
        $this->bank_account_number = $item->bank_account_number;
        $this->status = $item->status;
        $this->termination_date = $item->termination_date?->format('Y-m-d');
        $this->termination_reason = $item->termination_reason;
        $this->updateComputedValues();
        $this->isModalOpen = true;
    }

    public function show($id)
    {
        $this->showEmployee = Employee::with(['branch', 'user', 'loans', 'payrollDetails.payroll'])->findOrFail($id);
        $this->isShowModalOpen = true;
    }

    public function store()
    {
        $isNew = !$this->itemId;
        if (!auth()->user()->hasPermission($isNew ? 'employees.create' : 'employees.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $smmlv = PayrollCalculatorService::SMMLV;

        // Para salario mínimo, forzar el valor
        if ($this->salary_type === 'minimo') {
            $this->base_salary = $smmlv;
        }

        $rules = [
            'first_name' => 'required|string|min:2',
            'last_name' => 'required|string|min:2',
            'document_type' => 'required|in:CC,CE,PA,TI',
            'document_number' => 'required|string|unique:employees,document_number,' . $this->itemId,
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'hire_date' => 'required|date',
            'position' => 'required|string|min:2',
            'contract_type' => 'required|in:indefinido,fijo,obra_labor,aprendizaje,prestacion_servicios',
            'salary_type' => 'required|in:minimo,otro,integral',
            'base_salary' => 'required|numeric|min:0',
            'risk_level' => 'required|in:I,II,III,IV,V',
            'status' => 'required|in:activo,vacaciones,incapacidad,retirado',
            'user_id' => 'nullable|exists:users,id',
        ];

        if ($this->needsBranchSelection) {
            $rules['branch_id'] = 'required|exists:branches,id';
        }

        $this->validate($rules);

        // Validar salario integral >= 13 SMMLV
        if ($this->salary_type === 'integral' && $this->base_salary < ($smmlv * 13)) {
            $minIntegral = number_format($smmlv * 13, 0, ',', '.');
            $this->addError('base_salary', "El salario integral debe ser mínimo 13 SMMLV (\${$minIntegral})");
            return;
        }

        // Validar salario mínimo para contratos laborales (no aprendizaje)
        if ($this->salary_type === 'otro' && $this->contract_type !== 'aprendizaje') {
            $realSalary = $this->transport_included_in_salary
                ? (float) $this->base_salary - PayrollCalculatorService::TRANSPORT_ALLOWANCE
                : (float) $this->base_salary;

            if ($realSalary < $smmlv) {
                $minFormatted = number_format($smmlv, 0, ',', '.');
                $this->addError('base_salary', "El salario base real no puede ser menor al SMMLV (\${$minFormatted})");
                return;
            }
        }

        $branchId = $this->needsBranchSelection ? $this->branch_id : auth()->user()->branch_id;

        // Determinar si aplica auxilio de transporte automáticamente
        $transportAllowance = $this->transport_allowance;
        if ($this->contract_type === 'prestacion_servicios' || $this->salary_type === 'integral') {
            $transportAllowance = false;
        }

        $oldValues = $isNew ? null : Employee::find($this->itemId)->toArray();
        $item = Employee::updateOrCreate(['id' => $this->itemId], [
            'user_id' => $this->user_id ?: null,
            'branch_id' => $branchId,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'document_type' => $this->document_type,
            'document_number' => $this->document_number,
            'email' => $this->email ?: null,
            'phone' => $this->phone ?: null,
            'address' => $this->address ?: null,
            'birth_date' => $this->birth_date ?: null,
            'hire_date' => $this->hire_date,
            'position' => $this->position,
            'department' => $this->department ?: null,
            'contract_type' => $this->contract_type,
            'salary_type' => $this->salary_type,
            'base_salary' => $this->base_salary,
            'transport_allowance' => $transportAllowance,
            'transport_included_in_salary' => $this->transport_included_in_salary,
            'risk_level' => $this->risk_level,
            'payment_frequency' => $this->payment_frequency,
            'health_fund' => $this->health_fund ?: null,
            'pension_fund' => $this->pension_fund ?: null,
            'severance_fund' => $this->severance_fund ?: null,
            'compensation_fund' => $this->compensation_fund ?: null,
            'bank_name' => $this->bank_name ?: null,
            'bank_account_type' => $this->bank_account_type ?: null,
            'bank_account_number' => $this->bank_account_number ?: null,
            'status' => $this->status,
            'termination_date' => $this->termination_date ?: null,
            'termination_reason' => $this->termination_reason ?: null,
        ]);

        $isNew
            ? ActivityLogService::logCreate('employees', $item, "Empleado '{$item->full_name}' creado")
            : ActivityLogService::logUpdate('employees', $item, $oldValues, "Empleado '{$item->full_name}' actualizado");

        $this->isModalOpen = false;
        $this->dispatch('notify', message: $isNew ? 'Empleado creado' : 'Empleado actualizado');
    }

    public function confirmDelete($id)
    {
        if (!auth()->user()->hasPermission('employees.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->itemIdToDelete = $id;
        $this->isDeleteModalOpen = true;
    }

    public function delete()
    {
        if (!auth()->user()->hasPermission('employees.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $item = Employee::find($this->itemIdToDelete);
        if ($item->payrollDetails()->exists()) {
            $this->dispatch('notify', message: 'No se puede eliminar: tiene nóminas asociadas. Cambie el estado a Retirado.', type: 'error');
            $this->isDeleteModalOpen = false;
            return;
        }
        ActivityLogService::logDelete('employees', $item, "Empleado '{$item->full_name}' eliminado");
        $item->delete();
        $this->isDeleteModalOpen = false;
        $this->dispatch('notify', message: 'Empleado eliminado');
    }

    private function resetForm()
    {
        $this->itemId = null;
        $this->user_id = '';
        $this->branch_id = '';
        $this->first_name = '';
        $this->last_name = '';
        $this->document_type = 'CC';
        $this->document_number = '';
        $this->email = '';
        $this->phone = '';
        $this->address = '';
        $this->birth_date = '';
        $this->hire_date = '';
        $this->position = '';
        $this->department = '';
        $this->contract_type = 'indefinido';
        $this->salary_type = 'minimo';
        $this->base_salary = PayrollCalculatorService::SMMLV;
        $this->transport_allowance = true;
        $this->transport_included_in_salary = false;
        $this->risk_level = 'I';
        $this->payment_frequency = 'mensual';
        $this->health_fund = '';
        $this->pension_fund = '';
        $this->severance_fund = '';
        $this->compensation_fund = '';
        $this->bank_name = '';
        $this->bank_account_type = '';
        $this->bank_account_number = '';
        $this->status = 'activo';
        $this->termination_date = '';
        $this->termination_reason = '';
    }
}

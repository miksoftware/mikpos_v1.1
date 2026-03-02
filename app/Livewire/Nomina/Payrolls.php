<?php

namespace App\Livewire\Nomina;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollDetail;
use App\Models\SaleItem;
use App\Services\ActivityLogService;
use App\Services\PayrollCalculatorService;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Payrolls extends Component
{
    use WithPagination;

    public $search = '';
    public $filterBranch = '';
    public $filterStatus = '';
    public $isModalOpen = false;
    public $isDeleteModalOpen = false;
    public $isDetailModalOpen = false;
    public $itemIdToDelete = null;

    // Confirm action modals
    public $isConfirmModalOpen = false;
    public $confirmAction = '';
    public $confirmPayrollId = null;
    public $confirmTitle = '';
    public $confirmMessage = '';

    // Form
    public $itemId;
    public $branch_id;
    public $period_type = 'mensual';
    public $period_start;
    public $period_end;
    public $payment_date;
    public $notes;

    // Detail view
    public $selectedPayroll = null;
    public $payrollDetails = [];

    // Novedad form
    public $isNovedadModalOpen = false;
    public $selectedDetailId = null;
    public $novedad_overtime_daytime_hours = 0;
    public $novedad_overtime_nighttime_hours = 0;
    public $novedad_overtime_sunday_daytime_hours = 0;
    public $novedad_overtime_sunday_nighttime_hours = 0;
    public $novedad_night_surcharge_hours = 0;
    public $novedad_sunday_holiday_hours = 0;
    public $novedad_bonuses = 0;
    public $novedad_other_income = 0;
    public $novedad_cooperative_deduction = 0;
    public $novedad_libranza_deduction = 0;
    public $novedad_other_deductions = 0;
    public $novedadEmployeeName = '';
    public $novedadCommissions = 0; // read-only, auto from sales

    public bool $needsBranchSelection = false;
    public $branches = [];

    public function mount()
    {
        $user = auth()->user();
        $this->needsBranchSelection = $user->isSuperAdmin() || !$user->branch_id;
        if ($this->needsBranchSelection) {
            $this->branches = Branch::where('is_active', true)->orderBy('name')->get();
        }
    }

    public function render()
    {
        $user = auth()->user();
        $query = Payroll::query()->with(['branch', 'creator']);

        if ($this->needsBranchSelection) {
            if ($this->filterBranch) {
                $query->where('payrolls.branch_id', $this->filterBranch);
            }
        } else {
            $query->where('payrolls.branch_id', $user->branch_id);
        }

        $items = $query
            ->when(trim($this->search), fn($q) => $q->where('payrolls.notes', 'like', '%' . trim($this->search) . '%'))
            ->when($this->filterStatus, fn($q) => $q->where('payrolls.status', $this->filterStatus))
            ->latest()
            ->paginate(10);

        return view('livewire.nomina.payrolls', ['items' => $items]);
    }

    public function create()
    {
        if (!auth()->user()->hasPermission('payrolls.create')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->resetValidation();
        $this->resetForm();
        $user = auth()->user();
        if (!$this->needsBranchSelection && $user->branch_id) {
            $this->branch_id = $user->branch_id;
        }
        $this->isModalOpen = true;
    }

    /**
     * Auto-fill period dates based on type selection.
     */
    public function updatedPeriodType()
    {
        $now = Carbon::now();

        if ($this->period_type === 'mensual') {
            $this->period_start = $now->copy()->startOfMonth()->format('Y-m-d');
            $this->period_end = $now->copy()->endOfMonth()->format('Y-m-d');
            $this->payment_date = $now->copy()->endOfMonth()->format('Y-m-d');
        } elseif ($this->period_type === 'quincenal') {
            if ($now->day <= 15) {
                $this->period_start = $now->copy()->startOfMonth()->format('Y-m-d');
                $this->period_end = $now->copy()->startOfMonth()->addDays(14)->format('Y-m-d');
                $this->payment_date = $now->copy()->startOfMonth()->addDays(14)->format('Y-m-d');
            } else {
                $this->period_start = $now->copy()->startOfMonth()->addDays(15)->format('Y-m-d');
                $this->period_end = $now->copy()->endOfMonth()->format('Y-m-d');
                $this->payment_date = $now->copy()->endOfMonth()->format('Y-m-d');
            }
        } elseif ($this->period_type === 'semanal') {
            $this->period_start = $now->copy()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
            $this->period_end = $now->copy()->endOfWeek(Carbon::SUNDAY)->format('Y-m-d');
            $this->payment_date = $now->copy()->endOfWeek(Carbon::SUNDAY)->format('Y-m-d');
        }
    }

    public function store()
    {
        $isNew = !$this->itemId;
        if (!auth()->user()->hasPermission($isNew ? 'payrolls.create' : 'payrolls.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $rules = [
            'period_type' => 'required|in:mensual,quincenal,semanal',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'payment_date' => 'required|date',
        ];
        if ($this->needsBranchSelection) {
            $rules['branch_id'] = 'required|exists:branches,id';
        }
        $this->validate($rules);

        $branchId = $this->needsBranchSelection ? $this->branch_id : auth()->user()->branch_id;

        $payroll = Payroll::updateOrCreate(['id' => $this->itemId], [
            'branch_id' => $branchId,
            'period_type' => $this->period_type,
            'period_start' => $this->period_start,
            'period_end' => $this->period_end,
            'payment_date' => $this->payment_date,
            'notes' => $this->notes ?: null,
            'status' => 'borrador',
            'created_by' => auth()->id(),
        ]);

        if ($isNew) {
            // Only include employees whose payment_frequency matches the period type
            $employees = Employee::where('branch_id', $branchId)
                ->whereIn('status', ['activo', 'vacaciones', 'incapacidad'])
                ->where('contract_type', '!=', 'prestacion_servicios')
                ->where('payment_frequency', $this->period_type)
                ->get();

            if ($employees->isEmpty()) {
                $payroll->delete();
                $this->dispatch('notify', message: 'No hay empleados con frecuencia de pago ' . $this->period_type . ' en esta sucursal', type: 'error');
                return;
            }

            foreach ($employees as $employee) {
                $commissions = $this->calculateEmployeeCommissions($employee, $this->period_start, $this->period_end);
                PayrollDetail::create([
                    'payroll_id' => $payroll->id,
                    'employee_id' => $employee->id,
                    'commissions' => $commissions,
                ]);
            }

            ActivityLogService::logCreate('payrolls', $payroll, "Nómina período {$payroll->period_label} creada con {$employees->count()} empleados");
        }

        $this->isModalOpen = false;
        $this->dispatch('notify', message: $isNew ? 'Nómina creada' : 'Nómina actualizada');
    }

    /**
     * Calculate commissions from sales for an employee in a period.
     */
    private function calculateEmployeeCommissions(Employee $employee, string $start, string $end): float
    {
        if (!$employee->user_id) return 0;

        $items = SaleItem::join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->leftJoin('products', 'sale_items.product_id', '=', 'products.id')
            ->leftJoin('services', 'sale_items.service_id', '=', 'services.id')
            ->where('sales.user_id', $employee->user_id)
            ->where('sales.status', 'completed')
            ->whereDate('sales.created_at', '>=', $start)
            ->whereDate('sales.created_at', '<=', $end)
            ->where(function ($q) {
                $q->where(function ($pq) {
                    $pq->where('products.has_commission', true)
                       ->where('products.commission_value', '>', 0);
                })->orWhere(function ($sq) {
                    $sq->where('services.has_commission', true)
                       ->where('services.commission_value', '>', 0);
                });
            })
            ->select('sale_items.*')
            ->with(['product', 'service'])
            ->get();

        $total = 0;
        foreach ($items as $item) {
            $quantity = (float) $item->quantity;
            $unitPrice = (float) $item->unit_price;

            if ($item->service_id && $item->service && $item->service->has_commission) {
                $commValue = (float) $item->service->commission_value;
                $commType = $item->service->commission_type;
            } elseif ($item->product && $item->product->has_commission) {
                $commValue = (float) $item->product->commission_value;
                $commType = $item->product->commission_type;
            } else {
                continue;
            }

            $total += $commType === 'percentage'
                ? ($unitPrice * ($commValue / 100)) * $quantity
                : $commValue * $quantity;
        }

        return round($total, 2);
    }

    /**
     * Open confirm modal instead of browser confirm.
     */
    public function openConfirm(string $action, int $id, string $title, string $message)
    {
        $this->confirmAction = $action;
        $this->confirmPayrollId = $id;
        $this->confirmTitle = $title;
        $this->confirmMessage = $message;
        $this->isConfirmModalOpen = true;
    }

    public function executeConfirm()
    {
        $this->isConfirmModalOpen = false;
        match ($this->confirmAction) {
            'calculate' => $this->calculatePayroll($this->confirmPayrollId),
            'approve' => $this->approvePayroll($this->confirmPayrollId),
            'paid' => $this->markAsPaid($this->confirmPayrollId),
            default => null,
        };
    }

    public function calculatePayroll($id)
    {
        if (!auth()->user()->hasPermission('payrolls.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $payroll = Payroll::with('details.employee')->findOrFail($id);

        if (!in_array($payroll->status, ['borrador', 'calculada'])) {
            $this->dispatch('notify', message: 'Solo se puede calcular nóminas en borrador o calculadas', type: 'error');
            return;
        }

        $calculator = new PayrollCalculatorService();

        foreach ($payroll->details as $detail) {
            // Re-fetch commissions from sales
            $detail->commissions = $this->calculateEmployeeCommissions(
                $detail->employee, 
                $payroll->period_start->format('Y-m-d'), 
                $payroll->period_end->format('Y-m-d')
            );
            $calculator->calculate($detail, $payroll);
            $detail->save();
        }

        $payroll->status = 'calculada';
        $payroll->save();

        ActivityLogService::logUpdate('payrolls', $payroll, ['status' => 'borrador'], "Nómina período {$payroll->period_label} calculada");
        $this->dispatch('notify', message: 'Nómina calculada correctamente');
    }

    public function approvePayroll($id)
    {
        if (!auth()->user()->hasPermission('payrolls.approve')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $payroll = Payroll::findOrFail($id);
        if ($payroll->status !== 'calculada') {
            $this->dispatch('notify', message: 'La nómina debe estar calculada para aprobar', type: 'error');
            return;
        }

        $oldValues = $payroll->toArray();
        $payroll->status = 'aprobada';
        $payroll->save();

        ActivityLogService::logUpdate('payrolls', $payroll, $oldValues, "Nómina período {$payroll->period_label} aprobada");
        $this->dispatch('notify', message: 'Nómina aprobada');
    }

    public function markAsPaid($id)
    {
        if (!auth()->user()->hasPermission('payrolls.approve')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $payroll = Payroll::findOrFail($id);
        if ($payroll->status !== 'aprobada') {
            $this->dispatch('notify', message: 'La nómina debe estar aprobada para marcar como pagada', type: 'error');
            return;
        }

        $oldValues = $payroll->toArray();
        $payroll->status = 'pagada';
        $payroll->save();

        foreach ($payroll->details as $detail) {
            if ((float) $detail->loan_deduction > 0) {
                $loans = \App\Models\EmployeeLoan::where('employee_id', $detail->employee_id)
                    ->where('status', 'activo')
                    ->where('remaining_balance', '>', 0)
                    ->get();

                foreach ($loans as $loan) {
                    $loan->remaining_balance = max(0, (float) $loan->remaining_balance - (float) $loan->monthly_deduction);
                    if ($loan->remaining_balance <= 0) {
                        $loan->status = 'pagado';
                    }
                    $loan->save();
                }
            }
        }

        ActivityLogService::logUpdate('payrolls', $payroll, $oldValues, "Nómina período {$payroll->period_label} marcada como pagada");
        $this->dispatch('notify', message: 'Nómina marcada como pagada');
    }

    public function viewDetails($id)
    {
        $this->selectedPayroll = Payroll::with(['details.employee', 'branch'])->findOrFail($id);
        $this->payrollDetails = $this->selectedPayroll->details;
        $this->isDetailModalOpen = true;
    }

    public function editNovedad($detailId)
    {
        $detail = PayrollDetail::with('employee')->findOrFail($detailId);
        $this->selectedDetailId = $detailId;
        $this->novedadEmployeeName = $detail->employee->full_name;
        $this->novedad_overtime_daytime_hours = (float) $detail->overtime_daytime_hours;
        $this->novedad_overtime_nighttime_hours = (float) $detail->overtime_nighttime_hours;
        $this->novedad_overtime_sunday_daytime_hours = (float) $detail->overtime_sunday_daytime_hours;
        $this->novedad_overtime_sunday_nighttime_hours = (float) $detail->overtime_sunday_nighttime_hours;
        $this->novedad_night_surcharge_hours = (float) $detail->night_surcharge_hours;
        $this->novedad_sunday_holiday_hours = (float) $detail->sunday_holiday_hours;
        $this->novedad_bonuses = (float) $detail->bonuses;
        $this->novedad_other_income = (float) $detail->other_income;
        $this->novedad_cooperative_deduction = (float) $detail->cooperative_deduction;
        $this->novedad_libranza_deduction = (float) $detail->libranza_deduction;
        $this->novedad_other_deductions = (float) $detail->other_deductions;
        $this->novedadCommissions = (float) $detail->commissions;
        $this->isNovedadModalOpen = true;
    }

    public function saveNovedad()
    {
        $detail = PayrollDetail::findOrFail($this->selectedDetailId);
        $payroll = $detail->payroll;

        if (!in_array($payroll->status, ['borrador', 'calculada'])) {
            $this->dispatch('notify', message: 'No se pueden editar novedades en nóminas aprobadas/pagadas', type: 'error');
            return;
        }

        $detail->overtime_daytime_hours = (float) $this->novedad_overtime_daytime_hours;
        $detail->overtime_nighttime_hours = (float) $this->novedad_overtime_nighttime_hours;
        $detail->overtime_sunday_daytime_hours = (float) $this->novedad_overtime_sunday_daytime_hours;
        $detail->overtime_sunday_nighttime_hours = (float) $this->novedad_overtime_sunday_nighttime_hours;
        $detail->night_surcharge_hours = (float) $this->novedad_night_surcharge_hours;
        $detail->sunday_holiday_hours = (float) $this->novedad_sunday_holiday_hours;
        $detail->bonuses = (float) $this->novedad_bonuses;
        $detail->other_income = (float) $this->novedad_other_income;
        $detail->cooperative_deduction = (float) $this->novedad_cooperative_deduction;
        $detail->libranza_deduction = (float) $this->novedad_libranza_deduction;
        $detail->other_deductions = (float) $this->novedad_other_deductions;
        $detail->save();

        // Recalculate
        $calculator = new PayrollCalculatorService();
        $calculator->calculate($detail, $payroll);
        $detail->save();

        $this->isNovedadModalOpen = false;
        $this->viewDetails($payroll->id);
        $this->dispatch('notify', message: 'Novedad guardada y recalculada');
    }

    public function confirmDelete($id)
    {
        if (!auth()->user()->hasPermission('payrolls.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->itemIdToDelete = $id;
        $this->isDeleteModalOpen = true;
    }

    public function delete()
    {
        $payroll = Payroll::find($this->itemIdToDelete);
        if ($payroll->status === 'pagada') {
            $this->dispatch('notify', message: 'No se puede eliminar una nómina pagada', type: 'error');
            $this->isDeleteModalOpen = false;
            return;
        }
        ActivityLogService::logDelete('payrolls', $payroll, "Nómina período {$payroll->period_label} eliminada");
        $payroll->details()->delete();
        $payroll->delete();
        $this->isDeleteModalOpen = false;
        $this->dispatch('notify', message: 'Nómina eliminada');
    }

    private function resetForm()
    {
        $this->itemId = null;
        $this->branch_id = '';
        $this->period_type = 'mensual';
        $this->period_start = '';
        $this->period_end = '';
        $this->payment_date = '';
        $this->notes = '';
    }
}

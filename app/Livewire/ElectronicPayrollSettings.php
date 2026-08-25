<?php

namespace App\Livewire;

use App\Models\BillingSetting;
use App\Models\ElectronicPayrollSetting;
use App\Services\FactusPayrollService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ElectronicPayrollSettings extends Component
{
    public bool $is_enabled = false;
    public string $environment = 'sandbox';
    public string $payroll_numbering_range_id = '';
    public string $adjustment_numbering_range_id = '';

    public array $numberingRanges = [];
    public bool $isFactusConfigured = false;
    public string $factusErrorMessage = '';

    public function mount()
    {
        $billingSettings = BillingSetting::getSettings();
        $this->isFactusConfigured = $billingSettings->isConfigured();

        $settings = ElectronicPayrollSetting::getSettings();
        $this->is_enabled = $settings->is_enabled;
        $this->environment = $settings->environment ?: 'sandbox';
        $this->payroll_numbering_range_id = (string) ($settings->payroll_numbering_range_id ?? '');
        $this->adjustment_numbering_range_id = (string) ($settings->adjustment_numbering_range_id ?? '');

        if ($this->isFactusConfigured) {
            $this->loadNumberingRanges();
        }
    }

    public function loadNumberingRanges()
    {
        try {
            $service = new FactusPayrollService();
            $this->numberingRanges = $service->getNumberingRanges();
            $this->factusErrorMessage = '';
        } catch (\Exception $e) {
            $this->numberingRanges = [];
            $this->factusErrorMessage = $e->getMessage();
        }
    }

    public function save()
    {
        if (!auth()->user()->hasPermission('electronic_payroll.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso para editar la configuración de nómina electrónica', type: 'error');
            return;
        }

        $this->validate([
            'environment' => 'required|in:sandbox,production',
            'payroll_numbering_range_id' => 'nullable|string',
            'adjustment_numbering_range_id' => 'nullable|string',
        ]);

        $settings = ElectronicPayrollSetting::getSettings();

        // Get prefixes from selected ranges
        $payrollPrefix = '';
        $adjPrefix = '';
        foreach ($this->numberingRanges as $range) {
            if ((string) ($range['id'] ?? '') === $this->payroll_numbering_range_id) {
                $payrollPrefix = $range['prefix'] ?? '';
            }
            if ((string) ($range['id'] ?? '') === $this->adjustment_numbering_range_id) {
                $adjPrefix = $range['prefix'] ?? '';
            }
        }

        $settings->update([
            'is_enabled' => $this->is_enabled,
            'environment' => $this->environment,
            'payroll_numbering_range_id' => $this->payroll_numbering_range_id ?: null,
            'payroll_numbering_range_prefix' => $payrollPrefix ?: null,
            'adjustment_numbering_range_id' => $this->adjustment_numbering_range_id ?: null,
            'adjustment_numbering_range_prefix' => $adjPrefix ?: null,
        ]);

        $this->dispatch('notify', message: 'Configuración de Nómina Electrónica guardada correctamente');
    }

    public function render()
    {
        return view('livewire.electronic-payroll-settings');
    }
}

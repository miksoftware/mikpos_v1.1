<?php

namespace App\Livewire;

use App\Models\Branch;
use App\Models\ProductFieldSetting;
use App\Services\ActivityLogService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ProductFieldConfig extends Component
{
    public ?int $branchId = null;
    public array $fieldSettings = [];
    public ?string $selectedPreset = null;
    public array $availablePresets = [];
    public array $configurableFields = [];
    public bool $hasChanges = false;

    public function mount()
    {
        $this->availablePresets = ProductFieldSetting::getAvailablePresets();
        $this->configurableFields = ProductFieldSetting::CONFIGURABLE_FIELDS;
        $this->loadSettings();
    }

    public function render()
    {
        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        return view('livewire.product-field-config', [
            'branches' => $branches,
        ]);
    }

    public function updatedBranchId()
    {
        $this->loadSettings();
        $this->selectedPreset = null;
        $this->hasChanges = false;
    }

    public function loadSettings()
    {
        $settings = ProductFieldSetting::getFieldsForBranch($this->branchId);
        
        $this->fieldSettings = [];
        foreach ($this->configurableFields as $fieldName => $config) {
            $setting = $settings->get($fieldName);
            
            $this->fieldSettings[$fieldName] = [
                'parent_visible' => is_object($setting) 
                    ? $setting->parent_visible 
                    : ($setting['parent_visible'] ?? $config['parent_visible']),
                'parent_required' => is_object($setting) 
                    ? $setting->parent_required 
                    : ($setting['parent_required'] ?? $config['parent_required']),
                'child_visible' => is_object($setting) 
                    ? $setting->child_visible 
                    : ($setting['child_visible'] ?? $config['child_visible']),
                'child_required' => is_object($setting) 
                    ? $setting->child_required 
                    : ($setting['child_required'] ?? $config['child_required']),
            ];
        }
    }

    public function toggleSetting(string $fieldName, string $settingType)
    {
        if (!auth()->user()->hasPermission('product_field_config.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        if (!isset($this->fieldSettings[$fieldName])) {
            return;
        }

        $this->fieldSettings[$fieldName][$settingType] = !$this->fieldSettings[$fieldName][$settingType];

        // If visibility is turned off, also turn off required
        if ($settingType === 'parent_visible' && !$this->fieldSettings[$fieldName]['parent_visible']) {
            $this->fieldSettings[$fieldName]['parent_required'] = false;
        }
        if ($settingType === 'child_visible' && !$this->fieldSettings[$fieldName]['child_visible']) {
            $this->fieldSettings[$fieldName]['child_required'] = false;
        }

        // Can't set required if not visible
        if ($settingType === 'parent_required' && !$this->fieldSettings[$fieldName]['parent_visible']) {
            $this->fieldSettings[$fieldName]['parent_required'] = false;
            return;
        }
        if ($settingType === 'child_required' && !$this->fieldSettings[$fieldName]['child_visible']) {
            $this->fieldSettings[$fieldName]['child_required'] = false;
            return;
        }

        $this->hasChanges = true;
        $this->selectedPreset = null;
    }

    public function applyPreset(string $preset)
    {
        if (!auth()->user()->hasPermission('product_field_config.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        if (!isset(ProductFieldSetting::PRESETS[$preset])) {
            $this->dispatch('notify', message: 'Preset no válido', type: 'error');
            return;
        }

        $presetConfig = ProductFieldSetting::PRESETS[$preset]['fields'];
        
        foreach ($this->configurableFields as $fieldName => $config) {
            if (isset($presetConfig[$fieldName])) {
                $this->fieldSettings[$fieldName] = [
                    'parent_visible' => $presetConfig[$fieldName]['parent_visible'],
                    'parent_required' => $presetConfig[$fieldName]['parent_required'],
                    'child_visible' => $presetConfig[$fieldName]['child_visible'],
                    'child_required' => $presetConfig[$fieldName]['child_required'],
                ];
            } else {
                $this->fieldSettings[$fieldName] = [
                    'parent_visible' => $config['parent_visible'],
                    'parent_required' => $config['parent_required'],
                    'child_visible' => $config['child_visible'],
                    'child_required' => $config['child_required'],
                ];
            }
        }

        $this->selectedPreset = $preset;
        $this->hasChanges = true;
        
        $presetName = ProductFieldSetting::PRESETS[$preset]['name'];
        $this->dispatch('notify', message: "Preset '{$presetName}' aplicado. Guarda para confirmar.", type: 'info');
    }

    public function saveSettings()
    {
        if (!auth()->user()->hasPermission('product_field_config.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        ProductFieldSetting::where('branch_id', $this->branchId)->delete();

        $displayOrder = 0;
        foreach ($this->fieldSettings as $fieldName => $settings) {
            ProductFieldSetting::create([
                'branch_id' => $this->branchId,
                'field_name' => $fieldName,
                'parent_visible' => $settings['parent_visible'],
                'parent_required' => $settings['parent_required'],
                'child_visible' => $settings['child_visible'],
                'child_required' => $settings['child_required'],
                'display_order' => $displayOrder++,
            ]);
        }

        $branchName = $this->branchId 
            ? Branch::find($this->branchId)?->name ?? 'Sucursal desconocida'
            : 'Global';
        
        ActivityLogService::log(
            'product_field_settings',
            'update',
            "Configuración de campos de producto actualizada para '{$branchName}'",
            null,
            null,
            $this->fieldSettings
        );

        $this->hasChanges = false;
        $this->dispatch('notify', message: 'Configuración guardada correctamente');
    }

    public function resetToDefaults()
    {
        if (!auth()->user()->hasPermission('product_field_config.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        foreach ($this->configurableFields as $fieldName => $config) {
            $this->fieldSettings[$fieldName] = [
                'parent_visible' => $config['parent_visible'],
                'parent_required' => $config['parent_required'],
                'child_visible' => $config['child_visible'],
                'child_required' => $config['child_required'],
            ];
        }

        $this->selectedPreset = null;
        $this->hasChanges = true;
        $this->dispatch('notify', message: 'Valores por defecto aplicados. Guarda para confirmar.', type: 'info');
    }
}

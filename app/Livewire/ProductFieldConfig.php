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
    // Current branch selection (null = global settings)
    public ?int $branchId = null;

    // Field settings array: [field_name => ['is_visible' => bool, 'is_required' => bool]]
    public array $fieldSettings = [];

    // Selected preset for quick configuration
    public ?string $selectedPreset = null;

    // Available presets from model
    public array $availablePresets = [];

    // Configurable fields with labels
    public array $configurableFields = [];

    // Track if settings have been modified
    public bool $hasChanges = false;

    public function mount()
    {
        // Load available presets
        $this->availablePresets = ProductFieldSetting::getAvailablePresets();
        
        // Load configurable fields
        $this->configurableFields = ProductFieldSetting::CONFIGURABLE_FIELDS;
        
        // Load current settings for the selected branch
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
                'is_visible' => is_object($setting) 
                    ? $setting->is_visible 
                    : ($setting['is_visible'] ?? $config['default_visible']),
                'is_required' => is_object($setting) 
                    ? $setting->is_required 
                    : ($setting['is_required'] ?? $config['default_required'] ?? false),
            ];
        }
    }

    public function toggleVisible(string $fieldName)
    {
        if (!auth()->user()->hasPermission('product_field_config.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        if (isset($this->fieldSettings[$fieldName])) {
            $this->fieldSettings[$fieldName]['is_visible'] = !$this->fieldSettings[$fieldName]['is_visible'];
            
            // If field becomes hidden, also set required to false
            if (!$this->fieldSettings[$fieldName]['is_visible']) {
                $this->fieldSettings[$fieldName]['is_required'] = false;
            }
            
            $this->hasChanges = true;
            $this->selectedPreset = null;
        }
    }

    public function toggleRequired(string $fieldName)
    {
        if (!auth()->user()->hasPermission('product_field_config.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        if (isset($this->fieldSettings[$fieldName])) {
            // Can only toggle required if field is visible
            if ($this->fieldSettings[$fieldName]['is_visible']) {
                $this->fieldSettings[$fieldName]['is_required'] = !$this->fieldSettings[$fieldName]['is_required'];
                $this->hasChanges = true;
                $this->selectedPreset = null;
            }
        }
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
                    'is_visible' => $presetConfig[$fieldName]['visible'],
                    'is_required' => $presetConfig[$fieldName]['required'],
                ];
            } else {
                // Reset to defaults if not in preset
                $this->fieldSettings[$fieldName] = [
                    'is_visible' => $config['default_visible'],
                    'is_required' => $config['default_required'] ?? false,
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

        // Delete existing settings for this branch
        ProductFieldSetting::where('branch_id', $this->branchId)->delete();

        // Create new settings
        $displayOrder = 0;
        foreach ($this->fieldSettings as $fieldName => $settings) {
            ProductFieldSetting::create([
                'branch_id' => $this->branchId,
                'field_name' => $fieldName,
                'is_visible' => $settings['is_visible'],
                'is_required' => $settings['is_required'],
                'display_order' => $displayOrder++,
            ]);
        }

        // Log activity
        $branchName = $this->branchId 
            ? Branch::find($this->branchId)?->name ?? 'Sucursal desconocida'
            : 'Global';
        
        // Use the base log method since we don't have a single model to reference
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
                'is_visible' => $config['default_visible'],
                'is_required' => $config['default_required'] ?? false,
            ];
        }

        $this->selectedPreset = null;
        $this->hasChanges = true;
        $this->dispatch('notify', message: 'Valores por defecto aplicados. Guarda para confirmar.', type: 'info');
    }
}

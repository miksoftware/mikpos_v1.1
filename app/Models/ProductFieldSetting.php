<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class ProductFieldSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'field_name',
        'is_visible',
        'is_required',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
            'is_required' => 'boolean',
        ];
    }

    /**
     * List of configurable fields with their default settings.
     */
    public const CONFIGURABLE_FIELDS = [
        'barcode' => ['label' => 'Código de Barras', 'default_visible' => true, 'default_required' => false],
        'presentation_id' => ['label' => 'Presentación', 'default_visible' => true, 'default_required' => false],
        'color_id' => ['label' => 'Color', 'default_visible' => false, 'default_required' => false],
        'product_model_id' => ['label' => 'Modelo', 'default_visible' => false, 'default_required' => false],
        'size' => ['label' => 'Talla', 'default_visible' => false, 'default_required' => false],
        'weight' => ['label' => 'Peso', 'default_visible' => false, 'default_required' => false],
        'imei' => ['label' => 'IMEI', 'default_visible' => false, 'default_required' => false],
        'min_stock' => ['label' => 'Stock Mínimo', 'default_visible' => true, 'default_required' => false],
        'max_stock' => ['label' => 'Stock Máximo', 'default_visible' => false, 'default_required' => false],
    ];

    /**
     * Business type presets with field configurations.
     */
    public const PRESETS = [
        'pharmacy' => [
            'name' => 'Farmacia',
            'description' => 'Configuración para farmacias y droguerías',
            'fields' => [
                'presentation_id' => ['visible' => true, 'required' => true],
                'barcode' => ['visible' => true, 'required' => false],
                'color_id' => ['visible' => false, 'required' => false],
                'product_model_id' => ['visible' => false, 'required' => false],
                'size' => ['visible' => false, 'required' => false],
                'weight' => ['visible' => false, 'required' => false],
                'imei' => ['visible' => false, 'required' => false],
                'min_stock' => ['visible' => true, 'required' => false],
                'max_stock' => ['visible' => true, 'required' => false],
            ],
        ],
        'cellphones' => [
            'name' => 'Celulares',
            'description' => 'Configuración para tiendas de celulares y electrónicos',
            'fields' => [
                'product_model_id' => ['visible' => true, 'required' => true],
                'color_id' => ['visible' => true, 'required' => true],
                'imei' => ['visible' => true, 'required' => false],
                'barcode' => ['visible' => true, 'required' => false],
                'presentation_id' => ['visible' => false, 'required' => false],
                'size' => ['visible' => false, 'required' => false],
                'weight' => ['visible' => false, 'required' => false],
                'min_stock' => ['visible' => true, 'required' => false],
                'max_stock' => ['visible' => false, 'required' => false],
            ],
        ],
        'clothing' => [
            'name' => 'Ropa',
            'description' => 'Configuración para tiendas de ropa y calzado',
            'fields' => [
                'color_id' => ['visible' => true, 'required' => true],
                'size' => ['visible' => true, 'required' => true],
                'barcode' => ['visible' => true, 'required' => false],
                'presentation_id' => ['visible' => false, 'required' => false],
                'product_model_id' => ['visible' => false, 'required' => false],
                'weight' => ['visible' => false, 'required' => false],
                'imei' => ['visible' => false, 'required' => false],
                'min_stock' => ['visible' => true, 'required' => false],
                'max_stock' => ['visible' => false, 'required' => false],
            ],
        ],
        'jewelry' => [
            'name' => 'Joyería',
            'description' => 'Configuración para joyerías y relojerías',
            'fields' => [
                'weight' => ['visible' => true, 'required' => true],
                'color_id' => ['visible' => true, 'required' => false],
                'barcode' => ['visible' => true, 'required' => false],
                'presentation_id' => ['visible' => false, 'required' => false],
                'product_model_id' => ['visible' => true, 'required' => false],
                'size' => ['visible' => true, 'required' => false],
                'imei' => ['visible' => false, 'required' => false],
                'min_stock' => ['visible' => true, 'required' => false],
                'max_stock' => ['visible' => false, 'required' => false],
            ],
        ],
        'general' => [
            'name' => 'General',
            'description' => 'Configuración general para cualquier tipo de negocio',
            'fields' => [
                'barcode' => ['visible' => true, 'required' => false],
                'presentation_id' => ['visible' => true, 'required' => false],
                'color_id' => ['visible' => true, 'required' => false],
                'product_model_id' => ['visible' => true, 'required' => false],
                'size' => ['visible' => true, 'required' => false],
                'weight' => ['visible' => true, 'required' => false],
                'imei' => ['visible' => false, 'required' => false],
                'min_stock' => ['visible' => true, 'required' => false],
                'max_stock' => ['visible' => true, 'required' => false],
            ],
        ],
    ];

    // Relationships

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    // Static Methods

    /**
     * Get field settings for a specific branch.
     * Falls back to global settings (branch_id = null) if no branch-specific settings exist.
     * Falls back to default settings if no settings exist at all.
     *
     * @param int|null $branchId The branch ID, or null for global settings
     * @return Collection Collection of field settings keyed by field_name
     */
    public static function getFieldsForBranch(?int $branchId = null): Collection
    {
        // Try to get branch-specific settings first
        $settings = collect();
        
        if ($branchId !== null) {
            $settings = static::where('branch_id', $branchId)
                ->orderBy('display_order')
                ->get()
                ->keyBy('field_name');
        }

        // If no branch-specific settings, try global settings
        if ($settings->isEmpty()) {
            $settings = static::whereNull('branch_id')
                ->orderBy('display_order')
                ->get()
                ->keyBy('field_name');
        }

        // If still no settings, return defaults
        if ($settings->isEmpty()) {
            return static::getDefaultFieldSettings();
        }

        // Merge with defaults to ensure all fields are present
        $defaults = static::getDefaultFieldSettings();
        foreach ($defaults as $fieldName => $default) {
            if (!$settings->has($fieldName)) {
                $settings->put($fieldName, $default);
            }
        }

        return $settings;
    }

    /**
     * Apply a preset configuration to a branch.
     *
     * @param string $preset The preset key (pharmacy, cellphones, clothing, jewelry, general)
     * @param int|null $branchId The branch ID, or null for global settings
     * @return bool True if successful, false if preset doesn't exist
     */
    public static function applyPreset(string $preset, ?int $branchId = null): bool
    {
        if (!isset(self::PRESETS[$preset])) {
            return false;
        }

        $presetConfig = self::PRESETS[$preset]['fields'];
        $displayOrder = 0;

        // Delete existing settings for this branch
        static::where('branch_id', $branchId)->delete();

        // Create new settings from preset
        foreach ($presetConfig as $fieldName => $config) {
            static::create([
                'branch_id' => $branchId,
                'field_name' => $fieldName,
                'is_visible' => $config['visible'],
                'is_required' => $config['required'],
                'display_order' => $displayOrder++,
            ]);
        }

        return true;
    }

    /**
     * Get default field settings based on CONFIGURABLE_FIELDS.
     *
     * @return Collection Collection of default settings
     */
    public static function getDefaultFieldSettings(): Collection
    {
        $defaults = collect();
        $displayOrder = 0;

        foreach (self::CONFIGURABLE_FIELDS as $fieldName => $config) {
            $defaults->put($fieldName, (object) [
                'field_name' => $fieldName,
                'label' => $config['label'],
                'is_visible' => $config['default_visible'],
                'is_required' => $config['default_required'],
                'display_order' => $displayOrder++,
            ]);
        }

        return $defaults;
    }

    /**
     * Get visible fields for a branch.
     *
     * @param int|null $branchId The branch ID
     * @return Collection Collection of visible field names
     */
    public static function getVisibleFieldsForBranch(?int $branchId = null): Collection
    {
        return static::getFieldsForBranch($branchId)
            ->filter(fn ($field) => $field->is_visible ?? $field['is_visible'] ?? true)
            ->keys();
    }

    /**
     * Get required fields for a branch.
     *
     * @param int|null $branchId The branch ID
     * @return Collection Collection of required field names
     */
    public static function getRequiredFieldsForBranch(?int $branchId = null): Collection
    {
        return static::getFieldsForBranch($branchId)
            ->filter(fn ($field) => ($field->is_visible ?? $field['is_visible'] ?? true) 
                && ($field->is_required ?? $field['is_required'] ?? false))
            ->keys();
    }

    /**
     * Get available presets.
     *
     * @return array Array of preset information
     */
    public static function getAvailablePresets(): array
    {
        $presets = [];
        foreach (self::PRESETS as $key => $preset) {
            $presets[$key] = [
                'name' => $preset['name'],
                'description' => $preset['description'],
            ];
        }
        return $presets;
    }
}

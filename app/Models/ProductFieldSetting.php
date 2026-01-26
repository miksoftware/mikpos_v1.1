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
        'parent_visible',
        'parent_required',
        'child_visible',
        'child_required',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'parent_visible' => 'boolean',
            'parent_required' => 'boolean',
            'child_visible' => 'boolean',
            'child_required' => 'boolean',
        ];
    }

    /**
     * List of configurable fields with their default settings.
     * These fields can be configured for both parent Product and child ProductChild (variants).
     */
    public const CONFIGURABLE_FIELDS = [
        'barcode' => [
            'label' => 'Código de Barras',
            'parent_visible' => true,
            'parent_required' => false,
            'child_visible' => true,
            'child_required' => false,
        ],
        'presentation_id' => [
            'label' => 'Presentación',
            'parent_visible' => false,
            'parent_required' => false,
            'child_visible' => true,
            'child_required' => false,
        ],
        'color_id' => [
            'label' => 'Color',
            'parent_visible' => false,
            'parent_required' => false,
            'child_visible' => false,
            'child_required' => false,
        ],
        'product_model_id' => [
            'label' => 'Modelo',
            'parent_visible' => false,
            'parent_required' => false,
            'child_visible' => false,
            'child_required' => false,
        ],
        'size' => [
            'label' => 'Talla',
            'parent_visible' => false,
            'parent_required' => false,
            'child_visible' => false,
            'child_required' => false,
        ],
        'weight' => [
            'label' => 'Peso',
            'parent_visible' => false,
            'parent_required' => false,
            'child_visible' => false,
            'child_required' => false,
        ],
        'imei' => [
            'label' => 'IMEI',
            'parent_visible' => false,
            'parent_required' => false,
            'child_visible' => false,
            'child_required' => false,
        ],
    ];

    /**
     * Business type presets with field configurations.
     */
    public const PRESETS = [
        'pharmacy' => [
            'name' => 'Farmacia',
            'description' => 'Configuración para farmacias y droguerías',
            'fields' => [
                'presentation_id' => ['parent_visible' => true, 'parent_required' => false, 'child_visible' => true, 'child_required' => true],
                'barcode' => ['parent_visible' => true, 'parent_required' => false, 'child_visible' => true, 'child_required' => false],
                'color_id' => ['parent_visible' => false, 'parent_required' => false, 'child_visible' => false, 'child_required' => false],
                'product_model_id' => ['parent_visible' => false, 'parent_required' => false, 'child_visible' => false, 'child_required' => false],
                'size' => ['parent_visible' => false, 'parent_required' => false, 'child_visible' => false, 'child_required' => false],
                'weight' => ['parent_visible' => false, 'parent_required' => false, 'child_visible' => false, 'child_required' => false],
                'imei' => ['parent_visible' => false, 'parent_required' => false, 'child_visible' => false, 'child_required' => false],
            ],
        ],
        'cellphones' => [
            'name' => 'Celulares',
            'description' => 'Configuración para tiendas de celulares y electrónicos',
            'fields' => [
                'product_model_id' => ['parent_visible' => true, 'parent_required' => true, 'child_visible' => true, 'child_required' => true],
                'color_id' => ['parent_visible' => true, 'parent_required' => false, 'child_visible' => true, 'child_required' => true],
                'imei' => ['parent_visible' => false, 'parent_required' => false, 'child_visible' => true, 'child_required' => false],
                'barcode' => ['parent_visible' => true, 'parent_required' => false, 'child_visible' => true, 'child_required' => false],
                'presentation_id' => ['parent_visible' => false, 'parent_required' => false, 'child_visible' => false, 'child_required' => false],
                'size' => ['parent_visible' => false, 'parent_required' => false, 'child_visible' => false, 'child_required' => false],
                'weight' => ['parent_visible' => false, 'parent_required' => false, 'child_visible' => false, 'child_required' => false],
            ],
        ],
        'clothing' => [
            'name' => 'Ropa',
            'description' => 'Configuración para tiendas de ropa y calzado',
            'fields' => [
                'color_id' => ['parent_visible' => true, 'parent_required' => false, 'child_visible' => true, 'child_required' => true],
                'size' => ['parent_visible' => true, 'parent_required' => false, 'child_visible' => true, 'child_required' => true],
                'barcode' => ['parent_visible' => true, 'parent_required' => false, 'child_visible' => true, 'child_required' => false],
                'presentation_id' => ['parent_visible' => false, 'parent_required' => false, 'child_visible' => false, 'child_required' => false],
                'product_model_id' => ['parent_visible' => false, 'parent_required' => false, 'child_visible' => false, 'child_required' => false],
                'weight' => ['parent_visible' => false, 'parent_required' => false, 'child_visible' => false, 'child_required' => false],
                'imei' => ['parent_visible' => false, 'parent_required' => false, 'child_visible' => false, 'child_required' => false],
            ],
        ],
        'jewelry' => [
            'name' => 'Joyería',
            'description' => 'Configuración para joyerías y relojerías',
            'fields' => [
                'weight' => ['parent_visible' => true, 'parent_required' => false, 'child_visible' => true, 'child_required' => true],
                'color_id' => ['parent_visible' => true, 'parent_required' => false, 'child_visible' => true, 'child_required' => false],
                'barcode' => ['parent_visible' => true, 'parent_required' => false, 'child_visible' => true, 'child_required' => false],
                'presentation_id' => ['parent_visible' => false, 'parent_required' => false, 'child_visible' => false, 'child_required' => false],
                'product_model_id' => ['parent_visible' => true, 'parent_required' => false, 'child_visible' => true, 'child_required' => false],
                'size' => ['parent_visible' => true, 'parent_required' => false, 'child_visible' => true, 'child_required' => false],
                'imei' => ['parent_visible' => false, 'parent_required' => false, 'child_visible' => false, 'child_required' => false],
            ],
        ],
        'general' => [
            'name' => 'General',
            'description' => 'Configuración general para cualquier tipo de negocio',
            'fields' => [
                'barcode' => ['parent_visible' => true, 'parent_required' => false, 'child_visible' => true, 'child_required' => false],
                'presentation_id' => ['parent_visible' => true, 'parent_required' => false, 'child_visible' => true, 'child_required' => false],
                'color_id' => ['parent_visible' => true, 'parent_required' => false, 'child_visible' => true, 'child_required' => false],
                'product_model_id' => ['parent_visible' => true, 'parent_required' => false, 'child_visible' => true, 'child_required' => false],
                'size' => ['parent_visible' => true, 'parent_required' => false, 'child_visible' => true, 'child_required' => false],
                'weight' => ['parent_visible' => true, 'parent_required' => false, 'child_visible' => true, 'child_required' => false],
                'imei' => ['parent_visible' => false, 'parent_required' => false, 'child_visible' => false, 'child_required' => false],
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
     */
    public static function getFieldsForBranch(?int $branchId = null): Collection
    {
        $settings = collect();
        
        if ($branchId !== null) {
            $settings = static::where('branch_id', $branchId)
                ->orderBy('display_order')
                ->get()
                ->keyBy('field_name');
        }

        if ($settings->isEmpty()) {
            $settings = static::whereNull('branch_id')
                ->orderBy('display_order')
                ->get()
                ->keyBy('field_name');
        }

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
     */
    public static function applyPreset(string $preset, ?int $branchId = null): bool
    {
        if (!isset(self::PRESETS[$preset])) {
            return false;
        }

        $presetConfig = self::PRESETS[$preset]['fields'];
        $displayOrder = 0;

        static::where('branch_id', $branchId)->delete();

        foreach ($presetConfig as $fieldName => $config) {
            static::create([
                'branch_id' => $branchId,
                'field_name' => $fieldName,
                'parent_visible' => $config['parent_visible'],
                'parent_required' => $config['parent_required'],
                'child_visible' => $config['child_visible'],
                'child_required' => $config['child_required'],
                'display_order' => $displayOrder++,
            ]);
        }

        return true;
    }

    /**
     * Get default field settings based on CONFIGURABLE_FIELDS.
     */
    public static function getDefaultFieldSettings(): Collection
    {
        $defaults = collect();
        $displayOrder = 0;

        foreach (self::CONFIGURABLE_FIELDS as $fieldName => $config) {
            $defaults->put($fieldName, (object) [
                'field_name' => $fieldName,
                'label' => $config['label'],
                'parent_visible' => $config['parent_visible'],
                'parent_required' => $config['parent_required'],
                'child_visible' => $config['child_visible'],
                'child_required' => $config['child_required'],
                'display_order' => $displayOrder++,
            ]);
        }

        return $defaults;
    }

    /**
     * Get available presets.
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

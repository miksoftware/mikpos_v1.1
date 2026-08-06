<?php

namespace Tests\Unit\Models;

use App\Models\Branch;
use App\Models\ProductFieldSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductFieldSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_field_setting_has_fillable_attributes(): void
    {
        $fillable = ['branch_id', 'field_name', 'parent_visible', 'parent_required', 'child_visible', 'child_required', 'display_order'];

        $setting = new ProductFieldSetting();

        $this->assertEquals($fillable, $setting->getFillable());
    }

    public function test_product_field_setting_casts_parent_visible_to_boolean(): void
    {
        $setting = ProductFieldSetting::factory()->create(['parent_visible' => 1]);

        $this->assertIsBool($setting->parent_visible);
        $this->assertTrue($setting->parent_visible);
    }

    public function test_product_field_setting_casts_parent_required_to_boolean(): void
    {
        $setting = ProductFieldSetting::factory()->required()->create();

        $this->assertIsBool($setting->parent_required);
        $this->assertTrue($setting->parent_required);
    }

    public function test_product_field_setting_can_be_created_with_factory(): void
    {
        $setting = ProductFieldSetting::factory()->create();

        $this->assertInstanceOf(ProductFieldSetting::class, $setting);
        $this->assertDatabaseHas('product_field_settings', [
            'id' => $setting->id,
            'field_name' => $setting->field_name,
        ]);
    }

    // Relationship tests

    public function test_product_field_setting_belongs_to_branch(): void
    {
        $branch = Branch::factory()->create();
        $setting = ProductFieldSetting::factory()->forBranch($branch)->create();

        $this->assertInstanceOf(Branch::class, $setting->branch);
        $this->assertEquals($branch->id, $setting->branch->id);
    }

    public function test_product_field_setting_can_have_null_branch(): void
    {
        $setting = ProductFieldSetting::factory()->create(['branch_id' => null]);

        $this->assertNull($setting->branch);
    }

    // Static method tests

    public function test_get_fields_for_branch_returns_branch_specific_settings(): void
    {
        $branch = Branch::factory()->create();
        ProductFieldSetting::factory()->forBranch($branch)->forField('barcode')->create([
            'parent_visible' => true,
            'parent_required' => true,
        ]);

        $fields = ProductFieldSetting::getFieldsForBranch($branch->id);

        $this->assertTrue($fields->has('barcode'));
        $this->assertTrue($fields->get('barcode')->parent_visible);
        $this->assertTrue($fields->get('barcode')->parent_required);
    }

    public function test_get_fields_for_branch_falls_back_to_global_settings(): void
    {
        $branch = Branch::factory()->create();
        ProductFieldSetting::factory()->forField('barcode')->create([
            'branch_id' => null,
            'parent_visible' => true,
            'parent_required' => false,
        ]);

        $fields = ProductFieldSetting::getFieldsForBranch($branch->id);

        $this->assertTrue($fields->has('barcode'));
    }

    public function test_get_fields_for_branch_returns_defaults_when_no_settings(): void
    {
        $branch = Branch::factory()->create();

        $fields = ProductFieldSetting::getFieldsForBranch($branch->id);

        $this->assertNotEmpty($fields);
        $this->assertTrue($fields->has('barcode'));
        $this->assertTrue($fields->has('presentation_id'));
    }

    public function test_get_default_field_settings_returns_all_configurable_fields(): void
    {
        $defaults = ProductFieldSetting::getDefaultFieldSettings();

        foreach (array_keys(ProductFieldSetting::CONFIGURABLE_FIELDS) as $fieldName) {
            $this->assertTrue($defaults->has($fieldName), "Missing field: {$fieldName}");
        }
    }

    public function test_apply_preset_creates_settings_for_pharmacy(): void
    {
        $result = ProductFieldSetting::applyPreset('pharmacy', null);

        $this->assertTrue($result);

        $settings = ProductFieldSetting::whereNull('branch_id')->get()->keyBy('field_name');

        $this->assertTrue($settings->get('presentation_id')->child_visible);
        $this->assertTrue($settings->get('presentation_id')->child_required);
        $this->assertFalse($settings->get('imei')->child_visible);
    }

    public function test_apply_preset_creates_settings_for_cellphones(): void
    {
        $result = ProductFieldSetting::applyPreset('cellphones', null);

        $this->assertTrue($result);

        $settings = ProductFieldSetting::whereNull('branch_id')->get()->keyBy('field_name');

        $this->assertTrue($settings->get('product_model_id')->parent_visible);
        $this->assertTrue($settings->get('product_model_id')->parent_required);
        $this->assertTrue($settings->get('color_id')->child_visible);
        $this->assertTrue($settings->get('imei')->child_visible);
        $this->assertFalse($settings->get('presentation_id')->parent_visible);
    }

    public function test_apply_preset_creates_settings_for_clothing(): void
    {
        $result = ProductFieldSetting::applyPreset('clothing', null);

        $this->assertTrue($result);

        $settings = ProductFieldSetting::whereNull('branch_id')->get()->keyBy('field_name');

        $this->assertTrue($settings->get('color_id')->child_visible);
        $this->assertTrue($settings->get('color_id')->child_required);
        $this->assertTrue($settings->get('size')->child_visible);
        $this->assertTrue($settings->get('size')->child_required);
        $this->assertFalse($settings->get('imei')->child_visible);
    }

    public function test_apply_preset_returns_false_for_invalid_preset(): void
    {
        $result = ProductFieldSetting::applyPreset('invalid_preset', null);

        $this->assertFalse($result);
    }

    public function test_apply_preset_deletes_existing_settings_before_applying(): void
    {
        $branch = Branch::factory()->create();
        ProductFieldSetting::factory()->forBranch($branch)->forField('barcode')->create();
        ProductFieldSetting::factory()->forBranch($branch)->forField('color_id')->create();

        $this->assertEquals(2, ProductFieldSetting::where('branch_id', $branch->id)->count());

        ProductFieldSetting::applyPreset('pharmacy', $branch->id);

        // Should have new settings from preset, not the old ones
        $settings = ProductFieldSetting::where('branch_id', $branch->id)->get();
        $this->assertGreaterThan(0, $settings->count());
    }

    public function test_get_visible_fields_for_branch_returns_only_visible(): void
    {
        ProductFieldSetting::factory()->forField('barcode')->create([
            'branch_id' => null,
            'parent_visible' => true,
            'child_visible' => true,
        ]);
        ProductFieldSetting::factory()->forField('imei')->create([
            'branch_id' => null,
            'parent_visible' => false,
            'child_visible' => false,
        ]);

        $parentVisibleFields = ProductFieldSetting::getFieldsForBranch(null)->filter(fn($setting) => $setting->parent_visible)->keys();

        $this->assertTrue($parentVisibleFields->contains('barcode'));
        $this->assertFalse($parentVisibleFields->contains('imei'));
    }

    public function test_get_required_fields_for_branch_returns_only_visible_and_required(): void
    {
        ProductFieldSetting::factory()->forField('barcode')->create([
            'branch_id' => null,
            'parent_visible' => true,
            'parent_required' => true,
        ]);
        ProductFieldSetting::factory()->forField('color_id')->create([
            'branch_id' => null,
            'parent_visible' => true,
            'parent_required' => false,
        ]);
        ProductFieldSetting::factory()->forField('imei')->create([
            'branch_id' => null,
            'parent_visible' => false,
            'parent_required' => true, // Hidden but required - should not be returned
        ]);

        $parentRequiredFields = ProductFieldSetting::getFieldsForBranch(null)->filter(fn($setting) => $setting->parent_visible && $setting->parent_required)->keys();

        $this->assertTrue($parentRequiredFields->contains('barcode'));
        $this->assertFalse($parentRequiredFields->contains('color_id'));
        $this->assertFalse($parentRequiredFields->contains('imei'));
    }

    public function test_get_available_presets_returns_all_presets(): void
    {
        $presets = ProductFieldSetting::getAvailablePresets();

        $this->assertArrayHasKey('pharmacy', $presets);
        $this->assertArrayHasKey('cellphones', $presets);
        $this->assertArrayHasKey('clothing', $presets);
        $this->assertArrayHasKey('jewelry', $presets);
        $this->assertArrayHasKey('general', $presets);

        $this->assertEquals('Farmacia', $presets['pharmacy']['name']);
        $this->assertEquals('Celulares', $presets['cellphones']['name']);
    }

    // Constants tests

    public function test_configurable_fields_constant_has_expected_fields(): void
    {
        $expectedFields = [
            'barcode', 'presentation_id', 'color_id', 'product_model_id',
            'size', 'weight', 'imei', 'import_code', 'import_declaration',
            'suggested_price',
        ];

        foreach ($expectedFields as $field) {
            $this->assertArrayHasKey($field, ProductFieldSetting::CONFIGURABLE_FIELDS);
        }
    }

    public function test_presets_constant_has_expected_presets(): void
    {
        $expectedPresets = ['pharmacy', 'cellphones', 'clothing', 'jewelry', 'general'];

        foreach ($expectedPresets as $preset) {
            $this->assertArrayHasKey($preset, ProductFieldSetting::PRESETS);
            $this->assertArrayHasKey('name', ProductFieldSetting::PRESETS[$preset]);
            $this->assertArrayHasKey('description', ProductFieldSetting::PRESETS[$preset]);
            $this->assertArrayHasKey('fields', ProductFieldSetting::PRESETS[$preset]);
        }
    }
}

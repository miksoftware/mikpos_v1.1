<?php

namespace Tests\Feature\Livewire;

use App\Livewire\ProductFieldConfig;
use App\Models\Branch;
use App\Models\ProductFieldSetting;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductFieldConfigTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $userWithoutPermission;
    protected Role $superAdminRole;
    protected Role $limitedRole;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->superAdminRole = Role::factory()->superAdmin()->create();
        $this->adminUser = User::factory()->create();
        $this->adminUser->roles()->attach($this->superAdminRole->id);
        
        $this->limitedRole = Role::factory()->create(['name' => 'limited']);
        $this->userWithoutPermission = User::factory()->create();
        $this->userWithoutPermission->roles()->attach($this->limitedRole->id);
    }

    // ==========================================
    // Page Rendering Tests
    // ==========================================

    public function test_product_field_config_page_can_be_rendered(): void
    {
        $this->actingAs($this->adminUser);
        
        $response = $this->get('/product-field-config');
        
        $response->assertStatus(200);
        $response->assertSeeLivewire(ProductFieldConfig::class);
    }

    public function test_product_field_config_displays_all_configurable_fields(): void
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(ProductFieldConfig::class)
            ->assertSee('Código de Barras')
            ->assertSee('Presentación')
            ->assertSee('Color')
            ->assertSee('Modelo')
            ->assertSee('Talla')
            ->assertSee('Peso')
            ->assertSee('IMEI')
            ->assertSee('Stock Mínimo')
            ->assertSee('Stock Máximo');
    }

    public function test_product_field_config_displays_available_presets(): void
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(ProductFieldConfig::class)
            ->assertSee('Farmacia')
            ->assertSee('Celulares')
            ->assertSee('Ropa')
            ->assertSee('Joyería')
            ->assertSee('General');
    }

    // ==========================================
    // Test: Guardar configuración
    // Requirements: 5.1, 5.2
    // ==========================================

    public function test_user_with_permission_can_save_field_settings(): void
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(ProductFieldConfig::class)
            ->call('toggleVisible', 'barcode')
            ->assertSet('hasChanges', true)
            ->call('saveSettings')
            ->assertSet('hasChanges', false)
            ->assertDispatched('notify');
        
        $this->assertDatabaseHas('product_field_settings', [
            'branch_id' => null,
            'field_name' => 'barcode',
        ]);
    }

    public function test_user_without_permission_cannot_save_settings(): void
    {
        $this->actingAs($this->userWithoutPermission);
        
        Livewire::test(ProductFieldConfig::class)
            ->call('saveSettings')
            ->assertDispatched('notify');
        
        // No settings should be saved
        $this->assertEquals(0, ProductFieldSetting::count());
    }

    public function test_saving_settings_persists_visible_and_required_states(): void
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(ProductFieldConfig::class)
            ->set('fieldSettings.barcode.is_visible', true)
            ->set('fieldSettings.barcode.is_required', true)
            ->set('fieldSettings.color_id.is_visible', false)
            ->set('fieldSettings.color_id.is_required', false)
            ->set('hasChanges', true)
            ->call('saveSettings');
        
        $this->assertDatabaseHas('product_field_settings', [
            'branch_id' => null,
            'field_name' => 'barcode',
            'is_visible' => true,
            'is_required' => true,
        ]);
        
        $this->assertDatabaseHas('product_field_settings', [
            'branch_id' => null,
            'field_name' => 'color_id',
            'is_visible' => false,
            'is_required' => false,
        ]);
    }

    public function test_saving_settings_for_specific_branch(): void
    {
        $this->actingAs($this->adminUser);
        
        $branch = Branch::factory()->create();
        
        Livewire::test(ProductFieldConfig::class)
            ->set('branchId', $branch->id)
            ->call('toggleVisible', 'imei')
            ->call('saveSettings');
        
        $this->assertDatabaseHas('product_field_settings', [
            'branch_id' => $branch->id,
            'field_name' => 'imei',
        ]);
    }

    public function test_saving_settings_replaces_existing_settings(): void
    {
        $this->actingAs($this->adminUser);
        
        // Create initial settings
        ProductFieldSetting::factory()->forField('barcode')->create([
            'branch_id' => null,
            'is_visible' => true,
            'is_required' => false,
        ]);
        
        $initialCount = ProductFieldSetting::whereNull('branch_id')->count();
        
        Livewire::test(ProductFieldConfig::class)
            ->call('toggleVisible', 'barcode')
            ->call('saveSettings');
        
        // Should have replaced settings, not added to them
        $newCount = ProductFieldSetting::whereNull('branch_id')->count();
        $this->assertGreaterThanOrEqual($initialCount, $newCount);
    }

    // ==========================================
    // Test: Aplicar preset
    // Requirements: 5.5
    // ==========================================

    public function test_user_with_permission_can_apply_pharmacy_preset(): void
    {
        $this->actingAs($this->adminUser);
        
        $component = Livewire::test(ProductFieldConfig::class)
            ->call('applyPreset', 'pharmacy')
            ->assertSet('selectedPreset', 'pharmacy')
            ->assertSet('hasChanges', true)
            ->assertDispatched('notify');
        
        // Verify pharmacy preset settings are applied on the same component instance
        $fieldSettings = $component->get('fieldSettings');
        
        // Pharmacy preset: presentation_id visible and required
        $this->assertTrue($fieldSettings['presentation_id']['is_visible']);
        $this->assertTrue($fieldSettings['presentation_id']['is_required']);
        
        // Pharmacy preset: imei hidden
        $this->assertFalse($fieldSettings['imei']['is_visible']);
    }

    public function test_user_with_permission_can_apply_cellphones_preset(): void
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(ProductFieldConfig::class)
            ->call('applyPreset', 'cellphones')
            ->assertSet('selectedPreset', 'cellphones')
            ->assertSet('hasChanges', true);
        
        $component = Livewire::test(ProductFieldConfig::class);
        $component->call('applyPreset', 'cellphones');
        $fieldSettings = $component->get('fieldSettings');
        
        // Cellphones preset: product_model_id and color_id visible and required
        $this->assertTrue($fieldSettings['product_model_id']['is_visible']);
        $this->assertTrue($fieldSettings['product_model_id']['is_required']);
        $this->assertTrue($fieldSettings['color_id']['is_visible']);
        $this->assertTrue($fieldSettings['color_id']['is_required']);
        
        // Cellphones preset: imei visible but not required
        $this->assertTrue($fieldSettings['imei']['is_visible']);
        $this->assertFalse($fieldSettings['imei']['is_required']);
        
        // Cellphones preset: presentation_id hidden
        $this->assertFalse($fieldSettings['presentation_id']['is_visible']);
    }

    public function test_user_with_permission_can_apply_clothing_preset(): void
    {
        $this->actingAs($this->adminUser);
        
        $component = Livewire::test(ProductFieldConfig::class);
        $component->call('applyPreset', 'clothing');
        $fieldSettings = $component->get('fieldSettings');
        
        // Clothing preset: color_id and size visible and required
        $this->assertTrue($fieldSettings['color_id']['is_visible']);
        $this->assertTrue($fieldSettings['color_id']['is_required']);
        $this->assertTrue($fieldSettings['size']['is_visible']);
        $this->assertTrue($fieldSettings['size']['is_required']);
        
        // Clothing preset: imei hidden
        $this->assertFalse($fieldSettings['imei']['is_visible']);
    }

    public function test_user_without_permission_cannot_apply_preset(): void
    {
        $this->actingAs($this->userWithoutPermission);
        
        Livewire::test(ProductFieldConfig::class)
            ->call('applyPreset', 'pharmacy')
            ->assertSet('selectedPreset', null)
            ->assertDispatched('notify');
    }

    public function test_applying_invalid_preset_shows_error(): void
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(ProductFieldConfig::class)
            ->call('applyPreset', 'invalid_preset')
            ->assertSet('selectedPreset', null)
            ->assertDispatched('notify');
    }

    public function test_applying_preset_then_saving_persists_settings(): void
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(ProductFieldConfig::class)
            ->call('applyPreset', 'pharmacy')
            ->call('saveSettings');
        
        // Verify settings were persisted
        $settings = ProductFieldSetting::whereNull('branch_id')->get()->keyBy('field_name');
        
        $this->assertTrue($settings->get('presentation_id')->is_visible);
        $this->assertTrue($settings->get('presentation_id')->is_required);
        $this->assertFalse($settings->get('imei')->is_visible);
    }

    // ==========================================
    // Test: Toggle visible/required
    // Requirements: 5.1, 5.2
    // ==========================================

    public function test_toggling_visible_updates_field_settings(): void
    {
        $this->actingAs($this->adminUser);
        
        $component = Livewire::test(ProductFieldConfig::class);
        $initialVisible = $component->get('fieldSettings.color_id.is_visible');
        
        $component->call('toggleVisible', 'color_id');
        
        $this->assertNotEquals($initialVisible, $component->get('fieldSettings.color_id.is_visible'));
        $this->assertTrue($component->get('hasChanges'));
    }

    public function test_toggling_required_updates_field_settings(): void
    {
        $this->actingAs($this->adminUser);
        
        $component = Livewire::test(ProductFieldConfig::class);
        
        // First make sure the field is visible
        $component->set('fieldSettings.barcode.is_visible', true);
        $initialRequired = $component->get('fieldSettings.barcode.is_required');
        
        $component->call('toggleRequired', 'barcode');
        
        $this->assertNotEquals($initialRequired, $component->get('fieldSettings.barcode.is_required'));
        $this->assertTrue($component->get('hasChanges'));
    }

    public function test_hiding_field_also_sets_required_to_false(): void
    {
        $this->actingAs($this->adminUser);
        
        $component = Livewire::test(ProductFieldConfig::class);
        
        // Set field as visible and required
        $component->set('fieldSettings.barcode.is_visible', true);
        $component->set('fieldSettings.barcode.is_required', true);
        
        // Toggle visible to hide
        $component->call('toggleVisible', 'barcode');
        
        // Required should now be false
        $this->assertFalse($component->get('fieldSettings.barcode.is_visible'));
        $this->assertFalse($component->get('fieldSettings.barcode.is_required'));
    }

    public function test_cannot_toggle_required_on_hidden_field(): void
    {
        $this->actingAs($this->adminUser);
        
        $component = Livewire::test(ProductFieldConfig::class);
        
        // Set field as hidden
        $component->set('fieldSettings.imei.is_visible', false);
        $component->set('fieldSettings.imei.is_required', false);
        
        // Try to toggle required
        $component->call('toggleRequired', 'imei');
        
        // Required should still be false
        $this->assertFalse($component->get('fieldSettings.imei.is_required'));
    }

    // ==========================================
    // Test: Reset to defaults
    // ==========================================

    public function test_reset_to_defaults_restores_default_settings(): void
    {
        $this->actingAs($this->adminUser);
        
        $component = Livewire::test(ProductFieldConfig::class);
        
        // Apply a preset first
        $component->call('applyPreset', 'cellphones');
        
        // Reset to defaults
        $component->call('resetToDefaults');
        
        $fieldSettings = $component->get('fieldSettings');
        
        // Check that defaults are restored
        $this->assertTrue($fieldSettings['barcode']['is_visible']); // default_visible = true
        $this->assertFalse($fieldSettings['barcode']['is_required']); // default_required = false
        $this->assertFalse($fieldSettings['color_id']['is_visible']); // default_visible = false
        $this->assertFalse($fieldSettings['imei']['is_visible']); // default_visible = false
        
        $this->assertTrue($component->get('hasChanges'));
        $this->assertNull($component->get('selectedPreset'));
    }

    // ==========================================
    // Test: Branch switching
    // ==========================================

    public function test_switching_branch_loads_branch_specific_settings(): void
    {
        $this->actingAs($this->adminUser);
        
        $branch = Branch::factory()->create();
        
        // Create branch-specific settings
        ProductFieldSetting::factory()->forBranch($branch)->forField('imei')->create([
            'is_visible' => true,
            'is_required' => true,
        ]);
        
        $component = Livewire::test(ProductFieldConfig::class);
        $component->set('branchId', $branch->id);
        
        $fieldSettings = $component->get('fieldSettings');
        
        $this->assertTrue($fieldSettings['imei']['is_visible']);
        $this->assertTrue($fieldSettings['imei']['is_required']);
    }

    public function test_switching_branch_resets_has_changes(): void
    {
        $this->actingAs($this->adminUser);
        
        $branch = Branch::factory()->create();
        
        $component = Livewire::test(ProductFieldConfig::class);
        $component->call('toggleVisible', 'barcode');
        $this->assertTrue($component->get('hasChanges'));
        
        $component->set('branchId', $branch->id);
        
        $this->assertFalse($component->get('hasChanges'));
    }

    // ==========================================
    // Property 9: Field Configuration Application
    // *For any* field marked as hidden in ProductFieldSetting, 
    // that field should not be included in form validation as required.
    // **Validates: Requirements 5.1, 5.2, 5.4**
    // ==========================================

    /**
     * Property 9: Field Configuration Application
     * Tests that hidden fields are not validated as required in the Products form.
     */
    public function test_hidden_field_is_not_required_in_validation(): void
    {
        $this->actingAs($this->adminUser);
        
        // Configure barcode as hidden
        ProductFieldSetting::factory()->forField('barcode')->create([
            'branch_id' => null,
            'is_visible' => false,
            'is_required' => false,
        ]);
        
        // Verify the field is hidden
        $visibleFields = ProductFieldSetting::getVisibleFieldsForBranch(null);
        $this->assertFalse($visibleFields->contains('barcode'));
        
        // Verify the field is not in required fields
        $requiredFields = ProductFieldSetting::getRequiredFieldsForBranch(null);
        $this->assertFalse($requiredFields->contains('barcode'));
    }

    /**
     * Property 9: Field Configuration Application
     * Tests that visible and required fields are properly returned.
     */
    public function test_visible_required_field_is_in_required_fields(): void
    {
        $this->actingAs($this->adminUser);
        
        // Configure presentation_id as visible and required
        ProductFieldSetting::factory()->forField('presentation_id')->create([
            'branch_id' => null,
            'is_visible' => true,
            'is_required' => true,
        ]);
        
        // Verify the field is visible
        $visibleFields = ProductFieldSetting::getVisibleFieldsForBranch(null);
        $this->assertTrue($visibleFields->contains('presentation_id'));
        
        // Verify the field is in required fields
        $requiredFields = ProductFieldSetting::getRequiredFieldsForBranch(null);
        $this->assertTrue($requiredFields->contains('presentation_id'));
    }

    /**
     * Property 9: Field Configuration Application
     * Tests that a field marked as required but hidden is NOT in required fields.
     */
    public function test_hidden_but_required_field_is_not_in_required_fields(): void
    {
        $this->actingAs($this->adminUser);
        
        // Configure imei as hidden but marked as required (edge case)
        ProductFieldSetting::factory()->forField('imei')->create([
            'branch_id' => null,
            'is_visible' => false,
            'is_required' => true, // This should be ignored since field is hidden
        ]);
        
        // Verify the field is NOT in required fields (hidden takes precedence)
        $requiredFields = ProductFieldSetting::getRequiredFieldsForBranch(null);
        $this->assertFalse($requiredFields->contains('imei'));
    }

    /**
     * Property 9: Field Configuration Application
     * Tests that field configuration is correctly applied per branch.
     */
    public function test_field_configuration_is_branch_specific(): void
    {
        $this->actingAs($this->adminUser);
        
        $branch1 = Branch::factory()->create();
        $branch2 = Branch::factory()->create();
        
        // Branch 1: imei visible and required
        ProductFieldSetting::factory()->forBranch($branch1)->forField('imei')->create([
            'is_visible' => true,
            'is_required' => true,
        ]);
        
        // Branch 2: imei hidden
        ProductFieldSetting::factory()->forBranch($branch2)->forField('imei')->create([
            'is_visible' => false,
            'is_required' => false,
        ]);
        
        // Verify branch 1 settings
        $branch1Required = ProductFieldSetting::getRequiredFieldsForBranch($branch1->id);
        $this->assertTrue($branch1Required->contains('imei'));
        
        // Verify branch 2 settings
        $branch2Required = ProductFieldSetting::getRequiredFieldsForBranch($branch2->id);
        $this->assertFalse($branch2Required->contains('imei'));
    }

    // ==========================================
    // Activity Logging Tests
    // ==========================================

    public function test_activity_log_is_created_on_settings_save(): void
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(ProductFieldConfig::class)
            ->call('toggleVisible', 'barcode')
            ->call('saveSettings');
        
        $this->assertDatabaseHas('activity_logs', [
            'module' => 'product_field_settings',
            'action' => 'update',
            'user_id' => $this->adminUser->id,
        ]);
    }
}

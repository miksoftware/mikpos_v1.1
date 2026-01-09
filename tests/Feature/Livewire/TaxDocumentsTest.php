<?php

namespace Tests\Feature\Livewire;

use App\Livewire\TaxDocuments;
use App\Models\Role;
use App\Models\TaxDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TaxDocumentsTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Role $superAdminRole;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->superAdminRole = Role::factory()->superAdmin()->create();
        $this->adminUser = User::factory()->create();
        $this->adminUser->roles()->attach($this->superAdminRole->id);
    }

    public function test_tax_documents_page_can_be_rendered(): void
    {
        $this->actingAs($this->adminUser);
        
        $response = $this->get('/tax-documents');
        
        $response->assertStatus(200);
        $response->assertSeeLivewire(TaxDocuments::class);
    }

    public function test_tax_documents_page_displays_tax_documents_list(): void
    {
        $this->actingAs($this->adminUser);
        
        $taxDocument = TaxDocument::factory()->create(['description' => 'Test Document']);
        
        Livewire::test(TaxDocuments::class)
            ->assertSee('Test Document');
    }

    public function test_tax_documents_can_be_searched(): void
    {
        $this->actingAs($this->adminUser);
        
        $taxDocument1 = TaxDocument::factory()->create(['description' => 'Factura de Venta']);
        $taxDocument2 = TaxDocument::factory()->create(['description' => 'Nota de Crédito']);
        
        Livewire::test(TaxDocuments::class)
            ->set('search', 'Factura')
            ->assertSee('Factura de Venta')
            ->assertDontSee('Nota de Crédito');
    }

    public function test_user_with_permission_can_create_tax_document(): void
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(TaxDocuments::class)
            ->call('create')
            ->assertSet('isModalOpen', true)
            ->set('dian_code', '01')
            ->set('description', 'Factura de Venta')
            ->set('abbreviation', 'FV')
            ->call('store')
            ->assertSet('isModalOpen', false);
        
        $this->assertDatabaseHas('tax_documents', [
            'dian_code' => '01',
            'description' => 'Factura de Venta',
            'abbreviation' => 'FV',
        ]);
    }

    public function test_user_without_permission_cannot_create_tax_document(): void
    {
        $userWithoutPermission = User::factory()->create();
        $limitedRole = Role::factory()->create(['name' => 'limited']);
        $userWithoutPermission->roles()->attach($limitedRole->id);
        
        $this->actingAs($userWithoutPermission);
        
        Livewire::test(TaxDocuments::class)
            ->call('create')
            ->assertSet('isModalOpen', false)
            ->assertDispatched('notify');
    }

    public function test_user_with_permission_can_edit_tax_document(): void
    {
        $this->actingAs($this->adminUser);
        
        $taxDocument = TaxDocument::factory()->create([
            'description' => 'Original Document',
        ]);
        
        Livewire::test(TaxDocuments::class)
            ->call('edit', $taxDocument->id)
            ->assertSet('isModalOpen', true)
            ->assertSet('itemId', $taxDocument->id)
            ->set('description', 'Updated Document')
            ->call('store')
            ->assertSet('isModalOpen', false);
        
        $this->assertDatabaseHas('tax_documents', [
            'id' => $taxDocument->id,
            'description' => 'Updated Document',
        ]);
    }

    public function test_user_with_permission_can_delete_tax_document(): void
    {
        $this->actingAs($this->adminUser);
        
        $taxDocument = TaxDocument::factory()->create(['description' => 'To Delete']);
        
        Livewire::test(TaxDocuments::class)
            ->call('confirmDelete', $taxDocument->id)
            ->assertSet('isDeleteModalOpen', true)
            ->call('delete')
            ->assertSet('isDeleteModalOpen', false);
        
        $this->assertDatabaseMissing('tax_documents', [
            'id' => $taxDocument->id,
        ]);
    }

    public function test_tax_document_status_can_be_toggled(): void
    {
        $this->actingAs($this->adminUser);
        
        $taxDocument = TaxDocument::factory()->create(['is_active' => true]);
        
        Livewire::test(TaxDocuments::class)
            ->call('toggleStatus', $taxDocument->id);
        
        $this->assertDatabaseHas('tax_documents', [
            'id' => $taxDocument->id,
            'is_active' => false,
        ]);
    }

    public function test_tax_document_dian_code_must_be_unique(): void
    {
        $this->actingAs($this->adminUser);
        
        TaxDocument::factory()->create(['dian_code' => '01']);
        
        Livewire::test(TaxDocuments::class)
            ->call('create')
            ->set('dian_code', '01')
            ->set('description', 'Another Document')
            ->set('abbreviation', 'AD')
            ->call('store')
            ->assertHasErrors(['dian_code']);
    }

    public function test_tax_document_description_is_required(): void
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(TaxDocuments::class)
            ->call('create')
            ->set('dian_code', '01')
            ->set('description', '')
            ->set('abbreviation', 'FV')
            ->call('store')
            ->assertHasErrors(['description']);
    }

    public function test_activity_log_is_created_on_tax_document_creation(): void
    {
        $this->actingAs($this->adminUser);
        
        Livewire::test(TaxDocuments::class)
            ->call('create')
            ->set('dian_code', '01')
            ->set('description', 'Logged Document')
            ->set('abbreviation', 'LD')
            ->call('store');
        
        $this->assertDatabaseHas('activity_logs', [
            'module' => 'tax_documents',
            'action' => 'create',
            'user_id' => $this->adminUser->id,
        ]);
    }

    public function test_activity_log_is_created_on_tax_document_update(): void
    {
        $this->actingAs($this->adminUser);
        
        $taxDocument = TaxDocument::factory()->create(['description' => 'Original']);
        
        Livewire::test(TaxDocuments::class)
            ->call('edit', $taxDocument->id)
            ->set('description', 'Updated')
            ->call('store');
        
        $this->assertDatabaseHas('activity_logs', [
            'module' => 'tax_documents',
            'action' => 'update',
            'user_id' => $this->adminUser->id,
        ]);
    }

    public function test_activity_log_is_created_on_tax_document_deletion(): void
    {
        $this->actingAs($this->adminUser);
        
        $taxDocument = TaxDocument::factory()->create(['description' => 'To Delete']);
        
        Livewire::test(TaxDocuments::class)
            ->call('confirmDelete', $taxDocument->id)
            ->call('delete');
        
        $this->assertDatabaseHas('activity_logs', [
            'module' => 'tax_documents',
            'action' => 'delete',
            'user_id' => $this->adminUser->id,
        ]);
    }
}
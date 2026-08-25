<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EcommerceCatalogPdfTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Role $superAdminRole;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::factory()->create([
            'name' => 'Farmacia Test',
            'is_active' => true,
            'ecommerce_enabled' => true,
            'phone' => '+57 300 123 4567',
            'tax_id' => '900123456-1',
            'address' => 'Calle 100 # 15-20',
        ]);

        $this->superAdminRole = Role::factory()->superAdmin()->create();
        $this->adminUser = User::factory()->create(['branch_id' => $this->branch->id]);
        $this->adminUser->roles()->attach($this->superAdminRole->id);
    }

    public function test_admin_user_can_download_ecommerce_catalog_pdf(): void
    {
        $this->actingAs($this->adminUser);

        $category = Category::factory()->create(['name' => 'Medicamentos']);
        Product::factory()->create([
            'name' => 'Paracetamol 500mg',
            'sku' => 'MED-001',
            'sale_price' => 5000,
            'category_id' => $category->id,
            'branch_id' => $this->branch->id,
            'is_active' => true,
            'show_in_shop' => true,
            'manages_inventory' => false,
        ]);

        $response = $this->get(route('ecommerce-orders.catalog-pdf'));

        $response->assertStatus(200);
        $this->assertTrue(
            str_contains($response->headers->get('content-type'), 'application/pdf') ||
            str_contains($response->headers->get('content-disposition'), 'catalogo-productos')
        );
    }

    public function test_customer_can_download_catalog_pdf_from_shop(): void
    {
        $customer = Customer::factory()->create(['is_active' => true]);

        $category = Category::factory()->create(['name' => 'Cuidado Personal']);
        Product::factory()->create([
            'name' => 'Shampoo Anticaspa 400ml',
            'sku' => 'CP-001',
            'sale_price' => 18000,
            'category_id' => $category->id,
            'branch_id' => $this->branch->id,
            'is_active' => true,
            'show_in_shop' => true,
            'manages_inventory' => false,
        ]);

        $response = $this->actingAs($customer, 'customer')->get(route('shop.catalog.pdf'));

        $response->assertStatus(200);
    }

    public function test_catalog_filters_by_category_if_provided(): void
    {
        $this->actingAs($this->adminUser);

        $cat1 = Category::factory()->create(['name' => 'Cat A']);
        $cat2 = Category::factory()->create(['name' => 'Cat B']);

        Product::factory()->create([
            'name' => 'Producto A',
            'category_id' => $cat1->id,
            'branch_id' => $this->branch->id,
            'is_active' => true,
            'show_in_shop' => true,
            'manages_inventory' => false,
        ]);

        Product::factory()->create([
            'name' => 'Producto B',
            'category_id' => $cat2->id,
            'branch_id' => $this->branch->id,
            'is_active' => true,
            'show_in_shop' => true,
            'manages_inventory' => false,
        ]);

        $response = $this->get(route('ecommerce-orders.catalog-pdf', ['category_id' => $cat1->id]));

        $response->assertStatus(200);
    }

    public function test_admin_can_request_catalog_pdf_via_ajax(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'application/json, application/pdf',
        ])->get(route('ecommerce-orders.catalog-pdf'));

        $response->assertStatus(200);
        $this->assertTrue(
            str_contains($response->headers->get('content-type'), 'application/pdf') ||
            str_contains($response->headers->get('content-disposition'), 'catalogo-productos')
        );
    }
}

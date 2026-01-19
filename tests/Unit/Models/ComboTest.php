<?php

namespace Tests\Unit\Models;

use App\Models\Combo;
use App\Models\ComboItem;
use App\Models\Product;
use App\Models\ProductChild;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComboTest extends TestCase
{
    use RefreshDatabase;

    public function test_combo_has_fillable_attributes(): void
    {
        $fillable = [
            'name', 'description', 'image', 'combo_price', 'original_price',
            'limit_type', 'start_date', 'end_date', 'max_sales', 'current_sales', 'is_active',
        ];

        $combo = new Combo();

        $this->assertEquals($fillable, $combo->getFillable());
    }

    public function test_combo_casts_is_active_to_boolean(): void
    {
        $combo = Combo::factory()->create(['is_active' => 1]);

        $this->assertIsBool($combo->is_active);
        $this->assertTrue($combo->is_active);
    }

    public function test_combo_casts_prices_to_decimal(): void
    {
        $combo = Combo::factory()->create([
            'combo_price' => 99.99,
            'original_price' => 149.99,
        ]);

        $this->assertEquals('99.99', $combo->combo_price);
        $this->assertEquals('149.99', $combo->original_price);
    }

    public function test_combo_can_be_created_with_factory(): void
    {
        $combo = Combo::factory()->create();

        $this->assertInstanceOf(Combo::class, $combo);
        $this->assertDatabaseHas('combos', [
            'id' => $combo->id,
            'name' => $combo->name,
        ]);
    }

    public function test_inactive_combo_factory_state(): void
    {
        $combo = Combo::factory()->inactive()->create();

        $this->assertFalse($combo->is_active);
    }

    public function test_combo_with_time_limit_factory_state(): void
    {
        $combo = Combo::factory()->withTimeLimit()->create();

        $this->assertEquals('time', $combo->limit_type);
        $this->assertNotNull($combo->start_date);
        $this->assertNotNull($combo->end_date);
    }

    public function test_combo_with_quantity_limit_factory_state(): void
    {
        $combo = Combo::factory()->withQuantityLimit(50)->create();

        $this->assertEquals('quantity', $combo->limit_type);
        $this->assertEquals(50, $combo->max_sales);
        $this->assertEquals(0, $combo->current_sales);
    }

    // Relationship tests

    public function test_combo_has_many_items(): void
    {
        $combo = Combo::factory()->create();
        $product = Product::factory()->create();
        
        ComboItem::factory()->create([
            'combo_id' => $combo->id,
            'product_id' => $product->id,
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $combo->items);
        $this->assertCount(1, $combo->items);
    }

    // Scope tests

    public function test_active_scope_filters_active_combos(): void
    {
        Combo::factory()->create(['is_active' => true]);
        Combo::factory()->create(['is_active' => false]);

        $activeCombos = Combo::active()->get();

        $this->assertCount(1, $activeCombos);
        $this->assertTrue($activeCombos->first()->is_active);
    }

    // Method tests

    public function test_get_savings_calculates_correctly(): void
    {
        $combo = Combo::factory()->create([
            'original_price' => 100,
            'combo_price' => 80,
        ]);

        $this->assertEquals(20, $combo->getSavings());
    }

    public function test_get_savings_returns_zero_when_no_discount(): void
    {
        $combo = Combo::factory()->create([
            'original_price' => 100,
            'combo_price' => 100,
        ]);

        $this->assertEquals(0, $combo->getSavings());
    }

    public function test_get_savings_percentage_calculates_correctly(): void
    {
        $combo = Combo::factory()->create([
            'original_price' => 100,
            'combo_price' => 80,
        ]);

        $this->assertEquals(20.0, $combo->getSavingsPercentage());
    }

    public function test_get_savings_percentage_returns_zero_when_original_price_is_zero(): void
    {
        $combo = Combo::factory()->create([
            'original_price' => 0,
            'combo_price' => 0,
        ]);

        $this->assertEquals(0, $combo->getSavingsPercentage());
    }

    public function test_is_available_returns_true_for_active_combo_without_limits(): void
    {
        $combo = Combo::factory()->create([
            'is_active' => true,
            'limit_type' => 'none',
        ]);

        $this->assertTrue($combo->isAvailable());
    }

    public function test_is_available_returns_false_for_inactive_combo(): void
    {
        $combo = Combo::factory()->inactive()->create();

        $this->assertFalse($combo->isAvailable());
    }

    public function test_is_available_returns_false_for_expired_combo(): void
    {
        $combo = Combo::factory()->expired()->create();

        $this->assertFalse($combo->isAvailable());
    }

    public function test_is_available_returns_false_for_sold_out_combo(): void
    {
        $combo = Combo::factory()->soldOut()->create();

        $this->assertFalse($combo->isAvailable());
    }

    public function test_is_available_returns_false_for_upcoming_combo(): void
    {
        $combo = Combo::factory()->upcoming()->create();

        $this->assertFalse($combo->isAvailable());
    }

    public function test_get_remaining_quantity_returns_correct_value(): void
    {
        $combo = Combo::factory()->create([
            'limit_type' => 'quantity',
            'max_sales' => 100,
            'current_sales' => 30,
        ]);

        $this->assertEquals(70, $combo->getRemainingQuantity());
    }

    public function test_get_remaining_quantity_returns_null_for_no_limit(): void
    {
        $combo = Combo::factory()->create(['limit_type' => 'none']);

        $this->assertNull($combo->getRemainingQuantity());
    }

    public function test_increment_sales_increases_current_sales(): void
    {
        $combo = Combo::factory()->create(['current_sales' => 5]);

        $combo->incrementSales(3);

        $this->assertEquals(8, $combo->fresh()->current_sales);
    }

    public function test_get_status_label_returns_disponible_for_available_combo(): void
    {
        $combo = Combo::factory()->create([
            'is_active' => true,
            'limit_type' => 'none',
        ]);

        $this->assertEquals('Disponible', $combo->getStatusLabel());
    }

    public function test_get_status_label_returns_inactivo_for_inactive_combo(): void
    {
        $combo = Combo::factory()->inactive()->create();

        $this->assertEquals('Inactivo', $combo->getStatusLabel());
    }

    public function test_get_status_label_returns_agotado_for_sold_out_combo(): void
    {
        $combo = Combo::factory()->soldOut()->create();

        $this->assertEquals('Agotado', $combo->getStatusLabel());
    }

    public function test_get_status_label_returns_expirado_for_expired_combo(): void
    {
        $combo = Combo::factory()->expired()->create();

        $this->assertEquals('Expirado', $combo->getStatusLabel());
    }

    public function test_get_status_label_returns_proximamente_for_upcoming_combo(): void
    {
        $combo = Combo::factory()->upcoming()->create();

        $this->assertEquals('Próximamente', $combo->getStatusLabel());
    }

    public function test_get_total_products_count_sums_quantities(): void
    {
        $combo = Combo::factory()->create();
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();
        
        ComboItem::factory()->create([
            'combo_id' => $combo->id,
            'product_id' => $product1->id,
            'quantity' => 2,
        ]);
        ComboItem::factory()->create([
            'combo_id' => $combo->id,
            'product_id' => $product2->id,
            'quantity' => 3,
        ]);

        $combo->load('items');
        $this->assertEquals(5, $combo->getTotalProductsCount());
    }
}

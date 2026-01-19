<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Subcategory;
use App\Models\Tax;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $purchasePrice = $this->faker->randomFloat(2, 10, 500);
        $salePrice = $purchasePrice * $this->faker->randomFloat(2, 1.1, 2.0);

        return [
            'sku' => strtoupper($this->faker->unique()->lexify('???')) . '-' . str_pad($this->faker->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'barcode' => $this->faker->optional(0.7)->ean13(),
            'name' => $this->faker->unique()->words(3, true),
            'description' => $this->faker->sentence(),
            'category_id' => Category::factory(),
            'subcategory_id' => null,
            'brand_id' => null,
            'unit_id' => Unit::factory(),
            'tax_id' => null,
            'image' => null,
            'purchase_price' => $purchasePrice,
            'sale_price' => round($salePrice, 2),
            'price_includes_tax' => false,
            'min_stock' => $this->faker->numberBetween(5, 20),
            'max_stock' => $this->faker->numberBetween(50, 200),
            'current_stock' => $this->faker->numberBetween(10, 100),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function withBrand(): static
    {
        return $this->state(fn (array $attributes) => [
            'brand_id' => Brand::factory(),
        ]);
    }

    public function withSubcategory(): static
    {
        return $this->state(function (array $attributes) {
            $category = Category::find($attributes['category_id']) ?? Category::factory()->create();
            return [
                'category_id' => $category->id,
                'subcategory_id' => Subcategory::factory()->create(['category_id' => $category->id])->id,
            ];
        });
    }

    public function withTax(): static
    {
        return $this->state(fn (array $attributes) => [
            'tax_id' => Tax::factory(),
        ]);
    }

    public function withImage(): static
    {
        return $this->state(fn (array $attributes) => [
            'image' => 'products/' . $this->faker->uuid() . '.jpg',
        ]);
    }

    public function withoutSku(): static
    {
        return $this->state(fn (array $attributes) => [
            'sku' => null,
        ]);
    }

    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'min_stock' => 10,
            'current_stock' => $this->faker->numberBetween(0, 10),
        ]);
    }

    public function negativeMargin(): static
    {
        return $this->state(function (array $attributes) {
            $purchasePrice = $this->faker->randomFloat(2, 100, 500);
            return [
                'purchase_price' => $purchasePrice,
                'sale_price' => $purchasePrice * 0.8, // 20% below purchase price
            ];
        });
    }

    public function zeroPurchasePrice(): static
    {
        return $this->state(fn (array $attributes) => [
            'purchase_price' => 0,
        ]);
    }
}

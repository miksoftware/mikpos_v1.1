<?php

namespace Database\Factories;

use App\Models\Color;
use App\Models\Presentation;
use App\Models\Product;
use App\Models\ProductChild;
use App\Models\ProductModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductChild>
 */
class ProductChildFactory extends Factory
{
    protected $model = ProductChild::class;

    public function definition(): array
    {
        $purchasePrice = $this->faker->randomFloat(2, 5, 200);
        $salePrice = $purchasePrice * $this->faker->randomFloat(2, 1.1, 2.0);

        return [
            'product_id' => Product::factory(),
            'sku' => strtoupper($this->faker->unique()->lexify('???')) . '-' . str_pad($this->faker->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'barcode' => $this->faker->unique()->ean13(),
            'name' => $this->faker->words(2, true),
            'presentation_id' => null,
            'color_id' => null,
            'product_model_id' => null,
            'size' => null,
            'weight' => null,
            'purchase_price' => $purchasePrice,
            'sale_price' => round($salePrice, 2),
            'price_includes_tax' => false,
            'min_stock' => $this->faker->numberBetween(5, 20),
            'max_stock' => $this->faker->numberBetween(50, 200),
            'current_stock' => $this->faker->numberBetween(10, 100),
            'image' => null,
            'imei' => null,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function withPresentation(): static
    {
        return $this->state(fn (array $attributes) => [
            'presentation_id' => Presentation::factory(),
        ]);
    }

    public function withColor(): static
    {
        return $this->state(fn (array $attributes) => [
            'color_id' => Color::factory(),
        ]);
    }

    public function withProductModel(): static
    {
        return $this->state(fn (array $attributes) => [
            'product_model_id' => ProductModel::factory(),
        ]);
    }

    public function withImage(): static
    {
        return $this->state(fn (array $attributes) => [
            'image' => 'products/variants/' . $this->faker->uuid() . '.jpg',
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
            $purchasePrice = $this->faker->randomFloat(2, 50, 200);
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

    public function withSize(): static
    {
        return $this->state(fn (array $attributes) => [
            'size' => $this->faker->randomElement(['XS', 'S', 'M', 'L', 'XL', 'XXL']),
        ]);
    }

    public function withWeight(): static
    {
        return $this->state(fn (array $attributes) => [
            'weight' => $this->faker->randomFloat(3, 0.1, 10),
        ]);
    }

    public function withImei(): static
    {
        return $this->state(fn (array $attributes) => [
            'imei' => $this->faker->numerify('###############'),
        ]);
    }
}

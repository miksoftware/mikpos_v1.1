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
        return [
            'product_id' => Product::factory(),
            'unit_quantity' => $this->faker->randomFloat(3, 1, 10),
            'sku' => strtoupper($this->faker->unique()->lexify('???')) . '-' . str_pad($this->faker->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'barcode' => $this->faker->unique()->ean13(),
            'name' => $this->faker->words(2, true),
            'presentation_id' => null,
            'color_id' => null,
            'product_model_id' => null,
            'size' => null,
            'weight' => null,
            'sale_price' => $this->faker->randomFloat(2, 10, 500),
            'price_includes_tax' => false,
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

    public function unitQuantity(float $quantity): static
    {
        return $this->state(fn (array $attributes) => [
            'unit_quantity' => $quantity,
        ]);
    }
}

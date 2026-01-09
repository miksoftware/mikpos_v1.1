<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\ProductModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductModel>
 */
class ProductModelFactory extends Factory
{
    protected $model = ProductModel::class;

    public function definition(): array
    {
        return [
            'brand_id' => Brand::factory(),
            'name' => $this->faker->unique()->words(2, true),
            'description' => $this->faker->sentence(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function withoutBrand(): static
    {
        return $this->state(fn (array $attributes) => [
            'brand_id' => null,
        ]);
    }
}
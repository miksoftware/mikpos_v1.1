<?php

namespace Database\Factories;

use App\Models\Combo;
use App\Models\ComboItem;
use App\Models\Product;
use App\Models\ProductChild;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ComboItem>
 */
class ComboItemFactory extends Factory
{
    protected $model = ComboItem::class;

    public function definition(): array
    {
        return [
            'combo_id' => Combo::factory(),
            'product_id' => Product::factory(),
            'product_child_id' => null,
            'quantity' => fake()->numberBetween(1, 5),
            'unit_price' => fake()->randomFloat(2, 10, 100),
        ];
    }

    /**
     * Indicate that the item is a product child.
     */
    public function forChild(): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => null,
            'product_child_id' => ProductChild::factory(),
        ]);
    }
}

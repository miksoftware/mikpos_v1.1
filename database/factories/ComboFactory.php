<?php

namespace Database\Factories;

use App\Models\Combo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Combo>
 */
class ComboFactory extends Factory
{
    protected $model = Combo::class;

    public function definition(): array
    {
        $originalPrice = fake()->randomFloat(2, 50, 500);
        $discount = fake()->randomFloat(2, 5, 30); // 5-30% discount
        $comboPrice = $originalPrice * (1 - $discount / 100);

        return [
            'name' => fake()->words(3, true) . ' Combo',
            'description' => fake()->optional()->sentence(),
            'image' => null,
            'combo_price' => round($comboPrice, 2),
            'original_price' => $originalPrice,
            'limit_type' => 'none',
            'start_date' => null,
            'end_date' => null,
            'max_sales' => null,
            'current_sales' => 0,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the combo is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the combo has a time limit.
     */
    public function withTimeLimit(): static
    {
        return $this->state(fn (array $attributes) => [
            'limit_type' => 'time',
            'start_date' => now()->subDay(),
            'end_date' => now()->addWeek(),
        ]);
    }

    /**
     * Indicate that the combo has a quantity limit.
     */
    public function withQuantityLimit(int $max = 100): static
    {
        return $this->state(fn (array $attributes) => [
            'limit_type' => 'quantity',
            'max_sales' => $max,
            'current_sales' => 0,
        ]);
    }

    /**
     * Indicate that the combo has both limits.
     */
    public function withBothLimits(int $max = 100): static
    {
        return $this->state(fn (array $attributes) => [
            'limit_type' => 'both',
            'start_date' => now()->subDay(),
            'end_date' => now()->addWeek(),
            'max_sales' => $max,
            'current_sales' => 0,
        ]);
    }

    /**
     * Indicate that the combo is expired (time).
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'limit_type' => 'time',
            'start_date' => now()->subMonth(),
            'end_date' => now()->subDay(),
        ]);
    }

    /**
     * Indicate that the combo is sold out (quantity).
     */
    public function soldOut(): static
    {
        return $this->state(fn (array $attributes) => [
            'limit_type' => 'quantity',
            'max_sales' => 100,
            'current_sales' => 100,
        ]);
    }

    /**
     * Indicate that the combo starts in the future.
     */
    public function upcoming(): static
    {
        return $this->state(fn (array $attributes) => [
            'limit_type' => 'time',
            'start_date' => now()->addWeek(),
            'end_date' => now()->addMonth(),
        ]);
    }
}

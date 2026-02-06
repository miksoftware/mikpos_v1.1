<?php

namespace Database\Factories;

use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Unit>
 */
class UnitFactory extends Factory
{
    protected $model = Unit::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(2, true),
            'abbreviation' => strtoupper($this->faker->unique()->lexify('???')),
            'is_active' => true,
            'is_weight_unit' => false,
        ];
    }

    /**
     * Indicate that the unit is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the unit is a weight unit.
     */
    public function weightUnit(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_weight_unit' => true,
        ]);
    }
}
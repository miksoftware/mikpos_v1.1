<?php

namespace Database\Factories;

use App\Models\Imei;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Imei>
 */
class ImeiFactory extends Factory
{
    protected $model = Imei::class;

    public function definition(): array
    {
        return [
            'imei' => $this->faker->unique()->numerify('###############'), // 15 digits
            'imei2' => $this->faker->optional(0.3)->numerify('###############'), // 30% chance of having imei2
            'status' => $this->faker->randomElement(['available', 'sold', 'reserved']),
            'notes' => $this->faker->optional(0.5)->sentence(),
        ];
    }

    public function available(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'available',
        ]);
    }

    public function sold(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sold',
        ]);
    }

    public function reserved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'reserved',
        ]);
    }

    public function withImei2(): static
    {
        return $this->state(fn (array $attributes) => [
            'imei2' => $this->faker->numerify('###############'),
        ]);
    }
}
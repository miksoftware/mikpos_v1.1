<?php

namespace Database\Factories;

use App\Models\Presentation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Presentation>
 */
class PresentationFactory extends Factory
{
    protected $model = Presentation::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->randomElement([
                'Caja x12', 'Blister x10', 'Individual', 'Pack x6', 'Botella 500ml',
                'Lata 350ml', 'Bolsa x5', 'Tubo x20', 'Frasco 250ml', 'Sobre x8'
            ]),
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
}
<?php

namespace Database\Factories;

use App\Models\Color;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Color>
 */
class ColorFactory extends Factory
{
    protected $model = Color::class;

    public function definition(): array
    {
        $colors = [
            ['name' => 'Rojo', 'hex' => '#FF0000'],
            ['name' => 'Azul', 'hex' => '#0000FF'],
            ['name' => 'Verde', 'hex' => '#00FF00'],
            ['name' => 'Negro', 'hex' => '#000000'],
            ['name' => 'Blanco', 'hex' => '#FFFFFF'],
            ['name' => 'Amarillo', 'hex' => '#FFFF00'],
            ['name' => 'Rosa', 'hex' => '#FFC0CB'],
            ['name' => 'Gris', 'hex' => '#808080'],
            ['name' => 'Naranja', 'hex' => '#FFA500'],
            ['name' => 'Morado', 'hex' => '#800080'],
        ];

        $color = $this->faker->randomElement($colors);

        return [
            'name' => $color['name'] . ' ' . $this->faker->unique()->numberBetween(1, 999),
            'hex_code' => $color['hex'],
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function withoutHexCode(): static
    {
        return $this->state(fn (array $attributes) => [
            'hex_code' => null,
        ]);
    }
}
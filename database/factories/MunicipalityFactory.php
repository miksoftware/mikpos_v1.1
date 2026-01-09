<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Municipality;
use Illuminate\Database\Eloquent\Factories\Factory;

class MunicipalityFactory extends Factory
{
    protected $model = Municipality::class;

    public function definition(): array
    {
        return [
            'department_id' => Department::factory(),
            'name' => fake()->city(),
            'dian_code' => fake()->unique()->numerify('####'),
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
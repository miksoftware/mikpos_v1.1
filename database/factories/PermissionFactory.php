<?php

namespace Database\Factories;

use App\Models\Module;
use App\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;

class PermissionFactory extends Factory
{
    protected $model = Permission::class;

    public function definition(): array
    {
        return [
            'module_id' => Module::factory(),
            'name' => fake()->unique()->slug(2) . '.' . fake()->randomElement(['view', 'create', 'edit', 'delete']),
            'display_name' => fake()->sentence(3),
            'description' => fake()->sentence(),
        ];
    }
}

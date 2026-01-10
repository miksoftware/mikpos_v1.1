<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Municipality;
use App\Models\Supplier;
use App\Models\TaxDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Supplier>
 */
class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition(): array
    {
        return [
            'tax_document_id' => TaxDocument::factory(),
            'document_number' => $this->faker->unique()->numerify('##########'),
            'name' => $this->faker->company(),
            'phone' => $this->faker->optional(0.7)->phoneNumber(),
            'email' => $this->faker->optional(0.6)->companyEmail(),
            'department_id' => function () {
                return Department::factory()->create()->id;
            },
            'municipality_id' => function (array $attributes) {
                return Municipality::factory()->create([
                    'department_id' => $attributes['department_id']
                ])->id;
            },
            'address' => $this->faker->address(),
            'salesperson_name' => $this->faker->optional(0.5)->name(),
            'salesperson_phone' => $this->faker->optional(0.4)->phoneNumber(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function withSalesperson(): static
    {
        return $this->state(fn (array $attributes) => [
            'salesperson_name' => $this->faker->name(),
            'salesperson_phone' => $this->faker->phoneNumber(),
        ]);
    }
}

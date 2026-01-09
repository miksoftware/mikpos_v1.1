<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Department;
use App\Models\Municipality;
use App\Models\TaxDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        $customerType = $this->faker->randomElement(['natural', 'juridico', 'exonerado']);
        $hasCredit = $this->faker->boolean(30); // 30% chance of having credit
        
        return [
            'customer_type' => $customerType,
            'tax_document_id' => TaxDocument::factory(),
            'document_number' => $this->faker->unique()->numerify('##########'),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'business_name' => $customerType === 'juridico' ? $this->faker->company() : null,
            'phone' => $this->faker->optional(0.7)->phoneNumber(),
            'email' => $this->faker->optional(0.6)->safeEmail(),
            'department_id' => function () {
                return Department::factory()->create()->id;
            },
            'municipality_id' => function (array $attributes) {
                return Municipality::factory()->create([
                    'department_id' => $attributes['department_id']
                ])->id;
            },
            'address' => $this->faker->address(),
            'has_credit' => $hasCredit,
            'credit_limit' => $hasCredit ? $this->faker->randomFloat(2, 100000, 5000000) : null,
            'is_active' => true,
            'is_default' => false,
        ];
    }

    public function natural(): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_type' => 'natural',
            'business_name' => null,
        ]);
    }

    public function juridico(): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_type' => 'juridico',
            'business_name' => $this->faker->company(),
        ]);
    }

    public function exonerado(): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_type' => 'exonerado',
            'business_name' => null,
        ]);
    }

    public function withCredit(): static
    {
        return $this->state(fn (array $attributes) => [
            'has_credit' => true,
            'credit_limit' => $this->faker->randomFloat(2, 100000, 5000000),
        ]);
    }

    public function withoutCredit(): static
    {
        return $this->state(fn (array $attributes) => [
            'has_credit' => false,
            'credit_limit' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }
}
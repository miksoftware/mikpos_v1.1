<?php

namespace Database\Factories;

use App\Models\TaxDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TaxDocument>
 */
class TaxDocumentFactory extends Factory
{
    protected $model = TaxDocument::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'dian_code' => $this->faker->unique()->numerify('##'),
            'description' => $this->faker->words(3, true),
            'abbreviation' => $this->faker->unique()->lexify('???'),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the tax document is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
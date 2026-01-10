<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\ProductFieldSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductFieldSetting>
 */
class ProductFieldSettingFactory extends Factory
{
    protected $model = ProductFieldSetting::class;

    public function definition(): array
    {
        $fieldNames = array_keys(ProductFieldSetting::CONFIGURABLE_FIELDS);

        return [
            'branch_id' => null,
            'field_name' => $this->faker->randomElement($fieldNames),
            'is_visible' => true,
            'is_required' => false,
            'display_order' => $this->faker->numberBetween(0, 10),
        ];
    }

    public function forBranch(Branch $branch): static
    {
        return $this->state(fn (array $attributes) => [
            'branch_id' => $branch->id,
        ]);
    }

    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_visible' => false,
        ]);
    }

    public function required(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_required' => true,
        ]);
    }

    public function forField(string $fieldName): static
    {
        return $this->state(fn (array $attributes) => [
            'field_name' => $fieldName,
        ]);
    }
}

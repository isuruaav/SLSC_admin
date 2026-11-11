<?php

namespace Database\Factories;

use App\Models\OrganizationCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<OrganizationCategory> */
final class OrganizationCategoryFactory extends Factory
{
    protected $model = OrganizationCategory::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['slug' => fake()->unique()->slug(), 'name_en' => fake()->words(2, true), 'sort_order' => 0, 'is_active' => true];
    }
}

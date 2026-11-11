<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Organization> */
final class OrganizationFactory extends Factory
{
    protected $model = Organization::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['slug' => fake()->unique()->slug(), 'short_name' => fake()->word(), 'status' => 'draft', 'sort_order' => 0, 'is_featured' => false];
    }
}

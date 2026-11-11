<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\OrganizationTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<OrganizationTranslation> */
final class OrganizationTranslationFactory extends Factory
{
    protected $model = OrganizationTranslation::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['organization_id' => Organization::factory(), 'locale' => 'en', 'title' => fake()->sentence(3)];
    }
}

<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\OrganizationContentBlock;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<OrganizationContentBlock> */
final class OrganizationContentBlockFactory extends Factory
{
    protected $model = OrganizationContentBlock::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['organization_id' => Organization::factory(), 'locale' => 'en', 'block_type' => 'paragraph', 'content' => fake()->paragraph(), 'sort_order' => 0];
    }
}

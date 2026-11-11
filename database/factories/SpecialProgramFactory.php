<?php

namespace Database\Factories;

use App\Models\SpecialProgram;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SpecialProgram> */
final class SpecialProgramFactory extends Factory
{
    protected $model = SpecialProgram::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['title_en' => fake()->sentence(3), 'url' => '/contact', 'category' => 'heritage', 'sort_order' => 0, 'is_active' => true];
    }
}

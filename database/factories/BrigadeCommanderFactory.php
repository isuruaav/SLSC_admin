<?php

namespace Database\Factories;

use App\Models\BrigadeCommander;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<BrigadeCommander> */
final class BrigadeCommanderFactory extends Factory
{
    protected $model = BrigadeCommander::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['name_en' => fake()->name(), 'sort_order' => 0, 'is_active' => true];
    }
}

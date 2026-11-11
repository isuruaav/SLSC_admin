<?php

namespace Database\Factories;

use App\Models\FirstAppointmentHolder;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FirstAppointmentHolder> */
final class FirstAppointmentHolderFactory extends Factory
{
    protected $model = FirstAppointmentHolder::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['appointment_en' => fake()->jobTitle(), 'name_en' => fake()->name(), 'sort_order' => 0, 'is_active' => true];
    }
}

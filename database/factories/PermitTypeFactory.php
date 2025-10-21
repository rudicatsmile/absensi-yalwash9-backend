<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PermitType>
 */
class PermitTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement([
                'Izin Sakit',
                'Izin Pribadi',
                'Izin Keluarga',
                'Izin Dinas',
                'Izin Melahirkan',
                'Izin Menikah',
                'Izin Ibadah',
                'Izin Pendidikan'
            ]),
            'quota_days' => $this->faker->numberBetween(1, 30),
            'is_paid' => $this->faker->boolean(70), // 70% chance of being paid
            'urut' => $this->faker->numberBetween(1, 100),
        ];
    }
}

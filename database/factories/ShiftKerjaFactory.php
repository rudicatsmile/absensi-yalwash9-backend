<?php

namespace Database\Factories;

use App\Models\ShiftKerja;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory untuk model ShiftKerja
 * 
 * Digunakan untuk membuat data testing shift kerja
 * dengan berbagai variasi waktu dan konfigurasi
 */
class ShiftKerjaFactory extends Factory
{
    protected $model = ShiftKerja::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $shifts = [
            [
                'name' => 'Shift Pagi',
                'start_time' => '08:00:00',
                'end_time' => '16:00:00',
                'is_cross_day' => false,
                'description' => 'Shift kerja pagi dari jam 8 pagi hingga 4 sore'
            ],
            [
                'name' => 'Shift Siang',
                'start_time' => '14:00:00',
                'end_time' => '22:00:00',
                'is_cross_day' => false,
                'description' => 'Shift kerja siang dari jam 2 siang hingga 10 malam'
            ],
            [
                'name' => 'Shift Malam',
                'start_time' => '22:00:00',
                'end_time' => '06:00:00',
                'is_cross_day' => true,
                'description' => 'Shift kerja malam dari jam 10 malam hingga 6 pagi'
            ],
            [
                'name' => 'Shift Fleksibel',
                'start_time' => '09:00:00',
                'end_time' => '17:00:00',
                'is_cross_day' => false,
                'description' => 'Shift kerja fleksibel dengan waktu yang dapat disesuaikan'
            ]
        ];

        $shift = $this->faker->randomElement($shifts);

        return [
            'name' => $shift['name'] . ' - ' . $this->faker->unique()->numberBetween(1, 100),
            'start_time' => $shift['start_time'],
            'end_time' => $shift['end_time'],
            'description' => $shift['description'],
            'is_cross_day' => $shift['is_cross_day'],
            'grace_period_minutes' => $this->faker->randomElement([5, 10, 15, 30]),
            'is_active' => $this->faker->boolean(90), // 90% kemungkinan aktif
        ];
    }

    /**
     * State untuk shift pagi
     */
    public function morning(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Shift Pagi',
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'is_cross_day' => false,
            'description' => 'Shift kerja pagi dari jam 8 pagi hingga 4 sore',
        ]);
    }

    /**
     * State untuk shift siang
     */
    public function afternoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Shift Siang',
            'start_time' => '14:00:00',
            'end_time' => '22:00:00',
            'is_cross_day' => false,
            'description' => 'Shift kerja siang dari jam 2 siang hingga 10 malam',
        ]);
    }

    /**
     * State untuk shift malam
     */
    public function night(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Shift Malam',
            'start_time' => '22:00:00',
            'end_time' => '06:00:00',
            'is_cross_day' => true,
            'description' => 'Shift kerja malam dari jam 10 malam hingga 6 pagi',
        ]);
    }

    /**
     * State untuk shift aktif
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * State untuk shift tidak aktif
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * State untuk shift dengan grace period pendek
     */
    public function shortGracePeriod(): static
    {
        return $this->state(fn (array $attributes) => [
            'grace_period_minutes' => 5,
        ]);
    }

    /**
     * State untuk shift dengan grace period panjang
     */
    public function longGracePeriod(): static
    {
        return $this->state(fn (array $attributes) => [
            'grace_period_minutes' => 30,
        ]);
    }
}
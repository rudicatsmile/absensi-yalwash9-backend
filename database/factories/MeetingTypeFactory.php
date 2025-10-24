<?php

namespace Database\Factories;

use App\Models\MeetingType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MeetingType>
 */
class MeetingTypeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = MeetingType::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement([
                'Team Meeting',
                'Project Review',
                'Client Presentation',
                'Training Session',
                'Board Meeting',
                'Standup Meeting',
                'Planning Meeting',
                'Performance Review',
                'Strategy Meeting',
                'All Hands Meeting'
            ]),
            'quota_days' => $this->faker->randomElement([0, 5, 10, 15, 20, 30]),
            'is_paid' => $this->faker->boolean(80), // 80% chance of being paid
        ];
    }

    /**
     * Indicate that the meeting type is paid.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_paid' => true,
        ]);
    }

    /**
     * Indicate that the meeting type is unpaid.
     */
    public function unpaid(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_paid' => false,
        ]);
    }

    /**
     * Indicate that the meeting type has unlimited quota.
     */
    public function unlimited(): static
    {
        return $this->state(fn (array $attributes) => [
            'quota_days' => 0,
        ]);
    }
}
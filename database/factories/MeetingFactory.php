<?php

namespace Database\Factories;

use App\Models\Meeting;
use App\Models\MeetingType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Meeting>
 */
class MeetingFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Meeting::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startTime = $this->faker->time('H:i:s', '18:00:00');
        $endTime = $this->faker->time('H:i:s', '22:00:00');

        return [
            'employee_id' => User::factory(),
            'meeting_type_id' => MeetingType::factory(),
            'date' => $this->faker->dateTimeBetween('-30 days', '+30 days')->format('Y-m-d'),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'reason' => $this->faker->randomElement([
                'Rapat koordinasi proyek baru dengan tim development',
                'Presentasi proposal kepada klien potensial',
                'Meeting evaluasi kinerja bulanan',
                'Diskusi strategi pemasaran produk terbaru',
                'Rapat review dan planning sprint berikutnya',
                'Meeting dengan vendor untuk negosiasi kontrak',
                'Presentasi hasil riset pasar kepada manajemen',
                'Rapat koordinasi antar departemen',
                'Meeting training dan development karyawan',
                'Diskusi implementasi sistem baru'
            ]),
            'document' => $this->faker->optional(0.3)->randomElement([
                'meetings/proposal_presentation.pdf',
                'meetings/meeting_agenda.docx',
                'meetings/project_report.pdf',
                'meetings/contract_draft.pdf'
            ]),
            'status' => $this->faker->randomElement(['pending', 'approved', 'rejected']),
            'notes' => $this->faker->optional(0.7)->sentence(),
            'approved_by' => $this->faker->optional(0.6)->randomElement(User::pluck('id')->toArray()),
            'approved_at' => $this->faker->optional(0.6)->dateTimeBetween('-7 days', 'now'),
        ];
    }

    /**
     * Indicate that the meeting is pending approval.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'approved_by' => null,
            'approved_at' => null,
            'notes' => null,
        ]);
    }

    /**
     * Indicate that the meeting is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'approved_by' => User::factory(),
            'approved_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
            'notes' => $this->faker->optional(0.8)->sentence(),
        ]);
    }

    /**
     * Indicate that the meeting is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'approved_by' => User::factory(),
            'approved_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
            'notes' => $this->faker->randomElement([
                'Tidak memenuhi kriteria meeting yang telah ditetapkan',
                'Jadwal bentrok dengan meeting lain yang lebih prioritas',
                'Perlu persiapan yang lebih matang sebelum meeting',
                'Budget tidak mencukupi untuk meeting ini',
                'Perlu approval dari level yang lebih tinggi'
            ]),
        ]);
    }
}
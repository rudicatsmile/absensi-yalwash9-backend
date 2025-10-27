<?php

namespace Database\Seeders;

use App\Models\WorkShift;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WorkShiftSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $workShifts = [
            [
                'name' => 'Shift Pagi',
                'start_time' => '08:00:00',
                'end_time' => '16:00:00',
                'is_cross_day' => false,
                'grace_period_minutes' => 15,
                'is_active' => true,
                'description' => 'Shift kerja pagi hari dari jam 8 sampai 4 sore',
            ],
            [
                'name' => 'Shift Siang',
                'start_time' => '16:00:00',
                'end_time' => '00:00:00',
                'is_cross_day' => true,
                'grace_period_minutes' => 15,
                'is_active' => true,
                'description' => 'Shift kerja siang hingga malam hari',
            ],
            [
                'name' => 'Shift Malam',
                'start_time' => '00:00:00',
                'end_time' => '08:00:00',
                'is_cross_day' => true,
                'grace_period_minutes' => 10,
                'is_active' => true,
                'description' => 'Shift kerja malam hingga pagi hari',
            ],
            [
                'name' => 'Shift Fleksibel',
                'start_time' => '09:00:00',
                'end_time' => '17:00:00',
                'is_cross_day' => false,
                'grace_period_minutes' => 30,
                'is_active' => true,
                'description' => 'Shift kerja dengan waktu fleksibel',
            ],
        ];

        foreach ($workShifts as $workShift) {
            WorkShift::firstOrCreate(
                ['name' => $workShift['name']],
                $workShift
            );
        }

        $this->command->info('Work Shift seeder completed successfully!');
    }
}

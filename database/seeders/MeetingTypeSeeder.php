<?php

namespace Database\Seeders;

use App\Models\MeetingType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MeetingTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $meetingTypes = [
            [
                'name' => 'Team Meeting',
                'quota_days' => 0, // Unlimited
                'is_paid' => true,
            ],
            [
                'name' => 'Project Review',
                'quota_days' => 12,
                'is_paid' => true,
            ],
            [
                'name' => 'Client Presentation',
                'quota_days' => 6,
                'is_paid' => true,
            ],
            [
                'name' => 'Training Session',
                'quota_days' => 10,
                'is_paid' => true,
            ],
            [
                'name' => 'Board Meeting',
                'quota_days' => 4,
                'is_paid' => true,
            ],
        ];

        foreach ($meetingTypes as $meetingType) {
            MeetingType::updateOrCreate(
                ['name' => $meetingType['name']],
                $meetingType
            );
        }
    }
}
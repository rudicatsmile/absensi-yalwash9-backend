<?php

namespace Database\Seeders;

use App\Models\Meeting;
use App\Models\MeetingType;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MeetingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get first user for consistent seeding
        $firstUser = User::first();
        
        if (!$firstUser) {
            $this->command->warn('No users found. Please run UserSeeder first.');
            return;
        }

        // Get meeting types
        $teamMeeting = MeetingType::where('name', 'Team Meeting')->first();
        $projectReview = MeetingType::where('name', 'Project Review')->first();
        $clientPresentation = MeetingType::where('name', 'Client Presentation')->first();

        if (!$teamMeeting || !$projectReview || !$clientPresentation) {
            $this->command->warn('Meeting types not found. Please run MeetingTypeSeeder first.');
            return;
        }

        $meetings = [
            [
                'employee_id' => $firstUser->id,
                'meeting_type_id' => $teamMeeting->id,
                'date' => '2024-01-15',
                'start_time' => '09:00:00',
                'end_time' => '11:00:00',
                'reason' => 'Rapat koordinasi proyek pengembangan aplikasi mobile dengan tim development untuk membahas timeline dan resource allocation',
                'status' => 'approved',
                'approved_by' => $firstUser->id,
                'approved_at' => '2024-01-14 16:30:00',
                'notes' => 'Meeting disetujui untuk koordinasi proyek penting',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'employee_id' => $firstUser->id,
                'meeting_type_id' => $projectReview->id,
                'date' => '2024-01-17',
                'start_time' => '14:00:00',
                'end_time' => '16:30:00',
                'reason' => 'Presentasi proposal sistem manajemen inventory kepada klien potensial PT. Maju Bersama',
                'status' => 'pending',
                'notes' => null,
                'approved_by' => null,
                'approved_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'employee_id' => $firstUser->id,
                'meeting_type_id' => $clientPresentation->id,
                'date' => '2024-01-18',
                'start_time' => '10:00:00',
                'end_time' => '12:00:00',
                'reason' => 'Meeting evaluasi kinerja bulanan dengan manajemen untuk review pencapaian target',
                'status' => 'rejected',
                'approved_by' => $firstUser->id,
                'approved_at' => '2024-01-17 09:15:00',
                'notes' => 'Jadwal bentrok dengan meeting board of directors yang lebih prioritas',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'employee_id' => $firstUser->id,
                'meeting_type_id' => $teamMeeting->id,
                'date' => '2024-01-19',
                'start_time' => '13:30:00',
                'end_time' => '15:00:00',
                'reason' => 'Diskusi strategi pemasaran produk terbaru dan planning campaign Q1 2024',
                'status' => 'approved',
                'approved_by' => $firstUser->id,
                'approved_at' => '2024-01-18 11:20:00',
                'notes' => 'Meeting strategis yang penting untuk pencapaian target Q1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'employee_id' => $firstUser->id,
                'meeting_type_id' => $projectReview->id,
                'date' => '2024-01-20',
                'start_time' => '16:00:00',
                'end_time' => '18:30:00',
                'reason' => 'Rapat review dan planning sprint berikutnya dengan tim agile development',
                'status' => 'approved',
                'approved_by' => $firstUser->id,
                'approved_at' => '2024-01-19 14:45:00',
                'notes' => 'Sprint planning meeting yang rutin dan diperlukan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($meetings as $meeting) {
            Meeting::create($meeting);
        }
    }
}
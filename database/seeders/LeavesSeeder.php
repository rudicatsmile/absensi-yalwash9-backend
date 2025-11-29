<?php

namespace Database\Seeders;

use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Database\Seeder;

class LeavesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $leaveTypes = LeaveType::all();

        if ($users->isEmpty() || $leaveTypes->isEmpty()) {
            $this->command->warn('No users or leave types found. Please run UserSeeder and LeaveTypeSeeder first.');
            return;
        }

        // Ambil beberapa user untuk approver
        $approvers = $users->whereIn('role', ['admin', 'kepala_lembaga', 'manager'])->take(3);
        if ($approvers->isEmpty()) {
            $approvers = $users->take(1);
        }

        $leavesData = [
            // 1. Pending - Employee dari departemen berbeda
            [
                'employee_id' => $users->where('role', 'employee')->first()?->id ?? $users->first()->id,
                'leave_type_id' => $leaveTypes->first()->id,
                'start_date' => now()->addDays(5),
                'end_date' => now()->addDays(7),
                'total_days' => 3,
                'reason' => 'Keperluan keluarga mendesak',
                'status' => 'pending',
            ],
            // 2. Pending - Manager
            [
                'employee_id' => $users->where('role', 'manager')->first()?->id ?? $users->skip(1)->first()->id,
                'leave_type_id' => $leaveTypes->skip(1)->first()?->id ?? $leaveTypes->first()->id,
                'start_date' => now()->addDays(10),
                'end_date' => now()->addDays(12),
                'total_days' => 3,
                'reason' => 'Liburan keluarga',
                'status' => 'pending',
            ],
            // 3. Approved - Employee
            [
                'employee_id' => $users->where('role', 'employee')->skip(1)->first()?->id ?? $users->skip(2)->first()->id,
                'leave_type_id' => $leaveTypes->first()->id,
                'start_date' => now()->subDays(5),
                'end_date' => now()->subDays(3),
                'total_days' => 3,
                'reason' => 'Sakit demam',
                'status' => 'approved',
                'approved_by' => $approvers->first()->id,
                'approved_at' => now()->subDays(6),
                'notes' => 'Disetujui dengan surat keterangan dokter',
            ],
            // 4. Pending - Kepala Sub Bagian
            [
                'employee_id' => $users->where('role', 'kepala_sub_bagian')->first()?->id ?? $users->skip(3)->first()->id,
                'leave_type_id' => $leaveTypes->first()->id,
                'start_date' => now()->addDays(15),
                'end_date' => now()->addDays(17),
                'total_days' => 3,
                'reason' => 'Acara keluarga',
                'status' => 'pending',
            ],
            // 5. Rejected - Employee
            [
                'employee_id' => $users->where('role', 'employee')->skip(2)->first()?->id ?? $users->skip(4)->first()->id,
                'leave_type_id' => $leaveTypes->skip(1)->first()?->id ?? $leaveTypes->first()->id,
                'start_date' => now()->addDays(20),
                'end_date' => now()->addDays(25),
                'total_days' => 6,
                'reason' => 'Liburan pribadi',
                'status' => 'rejected',
                'approved_by' => $approvers->skip(1)->first()?->id ?? $approvers->first()->id,
                'approved_at' => now()->subDays(2),
                'notes' => 'Periode terlalu sibuk, mohon reschedule',
            ],
            // 6. Pending - Employee
            [
                'employee_id' => $users->where('role', 'employee')->skip(3)->first()?->id ?? $users->skip(5)->first()->id,
                'leave_type_id' => $leaveTypes->first()->id,
                'start_date' => now()->addDays(8),
                'end_date' => now()->addDays(9),
                'total_days' => 2,
                'reason' => 'Urusan pribadi',
                'status' => 'pending',
            ],
            // 7. Approved - Manager
            [
                'employee_id' => $users->where('role', 'manager')->skip(1)->first()?->id ?? $users->skip(6)->first()->id,
                'leave_type_id' => $leaveTypes->skip(2)->first()?->id ?? $leaveTypes->first()->id,
                'start_date' => now()->subDays(10),
                'end_date' => now()->subDays(8),
                'total_days' => 3,
                'reason' => 'Cuti tahunan',
                'status' => 'approved',
                'approved_by' => $approvers->first()->id,
                'approved_at' => now()->subDays(12),
            ],
            // 8. Pending - Employee
            [
                'employee_id' => $users->where('role', 'employee')->skip(4)->first()?->id ?? $users->skip(7)->first()->id,
                'leave_type_id' => $leaveTypes->first()->id,
                'start_date' => now()->addDays(3),
                'end_date' => now()->addDays(4),
                'total_days' => 2,
                'reason' => 'Pemeriksaan kesehatan',
                'status' => 'pending',
            ],
            // 9. Rejected - Employee (Dibatalkan sendiri)
            [
                'employee_id' => $users->where('role', 'employee')->skip(5)->first()?->id ?? $users->skip(8)->first()->id,
                'leave_type_id' => $leaveTypes->skip(1)->first()?->id ?? $leaveTypes->first()->id,
                'start_date' => now()->addDays(30),
                'end_date' => now()->addDays(32),
                'total_days' => 3,
                'reason' => 'Rencana dibatalkan',
                'status' => 'rejected',
                'approved_by' => $approvers->first()->id,
                'approved_at' => now()->subDays(1),
                'notes' => 'Dibatalkan oleh karyawan sendiri',
            ],
            // 10. Pending - Employee
            [
                'employee_id' => $users->where('role', 'employee')->skip(6)->first()?->id ?? $users->skip(9)->first()->id,
                'leave_type_id' => $leaveTypes->first()->id,
                'start_date' => now()->addDays(12),
                'end_date' => now()->addDays(14),
                'total_days' => 3,
                'reason' => 'Menghadiri pernikahan saudara',
                'status' => 'pending',
            ],
            // 11. Approved - Employee
            [
                'employee_id' => $users->where('role', 'employee')->skip(7)->first()?->id ?? $users->skip(10)->first()->id,
                'leave_type_id' => $leaveTypes->skip(2)->first()?->id ?? $leaveTypes->first()->id,
                'start_date' => now()->subDays(15),
                'end_date' => now()->subDays(13),
                'total_days' => 3,
                'reason' => 'Cuti melahirkan',
                'status' => 'approved',
                'approved_by' => $approvers->skip(2)->first()?->id ?? $approvers->first()->id,
                'approved_at' => now()->subDays(20),
                'notes' => 'Selamat atas kelahiran putra/putri',
            ],
            // 12. Pending - Kepala Sub Bagian
            [
                'employee_id' => $users->where('role', 'kepala_sub_bagian')->skip(1)->first()?->id ?? $users->skip(11)->first()->id,
                'leave_type_id' => $leaveTypes->first()->id,
                'start_date' => now()->addDays(6),
                'end_date' => now()->addDays(7),
                'total_days' => 2,
                'reason' => 'Keperluan mendesak',
                'status' => 'pending',
            ],
            // 13. Approved - Employee
            [
                'employee_id' => $users->where('role', 'employee')->skip(8)->first()?->id ?? $users->skip(12)->first()->id,
                'leave_type_id' => $leaveTypes->first()->id,
                'start_date' => now()->subDays(7),
                'end_date' => now()->subDays(6),
                'total_days' => 2,
                'reason' => 'Sakit flu',
                'status' => 'approved',
                'approved_by' => $approvers->first()->id,
                'approved_at' => now()->subDays(8),
            ],
            // 14. Pending - Manager
            [
                'employee_id' => $users->where('role', 'manager')->skip(2)->first()?->id ?? $users->skip(13)->first()->id,
                'leave_type_id' => $leaveTypes->skip(1)->first()?->id ?? $leaveTypes->first()->id,
                'start_date' => now()->addDays(18),
                'end_date' => now()->addDays(20),
                'total_days' => 3,
                'reason' => 'Cuti bersama keluarga',
                'status' => 'pending',
            ],
            // 15. Pending - Employee
            [
                'employee_id' => $users->where('role', 'employee')->skip(9)->first()?->id ?? $users->skip(14)->first()->id,
                'leave_type_id' => $leaveTypes->first()->id,
                'start_date' => now()->addDays(25),
                'end_date' => now()->addDays(27),
                'total_days' => 3,
                'reason' => 'Mudik lebaran',
                'status' => 'pending',
            ],
        ];

        foreach ($leavesData as $data) {
            Leave::create($data);
        }

        $this->command->info('Successfully created 15 leave records.');
    }
}

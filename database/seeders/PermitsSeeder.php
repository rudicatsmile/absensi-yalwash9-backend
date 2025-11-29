<?php

namespace Database\Seeders;

use App\Models\Permit;
use App\Models\PermitType;
use App\Models\User;
use Illuminate\Database\Seeder;

class PermitsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $permitTypes = PermitType::all();

        if ($users->isEmpty() || $permitTypes->isEmpty()) {
            $this->command->warn('No users or permit types found. Please run UserSeeder and PermitTypeSeeder first.');
            return;
        }

        // Ambil beberapa user untuk approver
        $approvers = $users->whereIn('role', ['admin', 'kepala_lembaga', 'manager'])->take(3);
        if ($approvers->isEmpty()) {
            $approvers = $users->take(1);
        }

        $permitsData = [
            // 1. Pending - Employee
            [
                'employee_id' => $users->where('role', 'employee')->first()?->id ?? $users->first()->id,
                'permit_type_id' => $permitTypes->first()->id,
                'start_date' => now()->addDays(2),
                'end_date' => now()->addDays(2),
                'total_days' => 1,
                'reason' => 'Keperluan keluarga mendesak',
                'status' => 'pending',
            ],
            // 2. Pending - Manager
            [
                'employee_id' => $users->where('role', 'manager')->first()?->id ?? $users->skip(1)->first()->id,
                'permit_type_id' => $permitTypes->skip(1)->first()?->id ?? $permitTypes->first()->id,
                'start_date' => now()->addDays(5),
                'end_date' => now()->addDays(5),
                'total_days' => 1,
                'reason' => 'Mengurus dokumen penting',
                'status' => 'pending',
            ],
            // 3. Approved - Employee
            [
                'employee_id' => $users->where('role', 'employee')->skip(1)->first()?->id ?? $users->skip(2)->first()->id,
                'permit_type_id' => $permitTypes->first()->id,
                'start_date' => now()->subDays(3),
                'end_date' => now()->subDays(3),
                'total_days' => 1,
                'reason' => 'Berobat ke dokter',
                'status' => 'approved',
                'approved_by' => $approvers->first()->id,
                'approved_at' => now()->subDays(4),
                'notes' => 'Disetujui dengan surat keterangan dokter',
            ],
            // 4. Pending - Kepala Sub Bagian
            [
                'employee_id' => $users->where('role', 'kepala_sub_bagian')->first()?->id ?? $users->skip(3)->first()->id,
                'permit_type_id' => $permitTypes->first()->id,
                'start_date' => now()->addDays(7),
                'end_date' => now()->addDays(7),
                'total_days' => 1,
                'reason' => 'Menghadiri acara keluarga',
                'status' => 'pending',
            ],
            // 5. Rejected - Employee
            [
                'employee_id' => $users->where('role', 'employee')->skip(2)->first()?->id ?? $users->skip(4)->first()->id,
                'permit_type_id' => $permitTypes->skip(1)->first()?->id ?? $permitTypes->first()->id,
                'start_date' => now()->addDays(10),
                'end_date' => now()->addDays(10),
                'total_days' => 1,
                'reason' => 'Keperluan pribadi',
                'status' => 'rejected',
                'approved_by' => $approvers->skip(1)->first()?->id ?? $approvers->first()->id,
                'approved_at' => now()->subDays(1),
                'notes' => 'Mohon reschedule, periode sibuk',
            ],
            // 6. Pending - Employee
            [
                'employee_id' => $users->where('role', 'employee')->skip(3)->first()?->id ?? $users->skip(5)->first()->id,
                'permit_type_id' => $permitTypes->first()->id,
                'start_date' => now()->addDays(3),
                'end_date' => now()->addDays(3),
                'total_days' => 1,
                'reason' => 'Mengambil ijazah',
                'status' => 'pending',
            ],
            // 7. Approved - Manager
            [
                'employee_id' => $users->where('role', 'manager')->skip(1)->first()?->id ?? $users->skip(6)->first()->id,
                'permit_type_id' => $permitTypes->skip(2)->first()?->id ?? $permitTypes->first()->id,
                'start_date' => now()->subDays(5),
                'end_date' => now()->subDays(5),
                'total_days' => 1,
                'reason' => 'Rapat di kantor cabang',
                'status' => 'approved',
                'approved_by' => $approvers->first()->id,
                'approved_at' => now()->subDays(7),
            ],
            // 8. Pending - Employee
            [
                'employee_id' => $users->where('role', 'employee')->skip(4)->first()?->id ?? $users->skip(7)->first()->id,
                'permit_type_id' => $permitTypes->first()->id,
                'start_date' => now()->addDays(1),
                'end_date' => now()->addDays(1),
                'total_days' => 1,
                'reason' => 'Pemeriksaan kesehatan rutin',
                'status' => 'pending',
            ],
            // 9. Pending - Employee
            [
                'employee_id' => $users->where('role', 'employee')->skip(5)->first()?->id ?? $users->skip(8)->first()->id,
                'permit_type_id' => $permitTypes->skip(1)->first()?->id ?? $permitTypes->first()->id,
                'start_date' => now()->addDays(15),
                'end_date' => now()->addDays(15),
                'total_days' => 1,
                'reason' => 'Mengurus SIM',
                'status' => 'pending',
            ],
            // 10. Approved - Employee
            [
                'employee_id' => $users->where('role', 'employee')->skip(6)->first()?->id ?? $users->skip(9)->first()->id,
                'permit_type_id' => $permitTypes->first()->id,
                'start_date' => now()->subDays(8),
                'end_date' => now()->subDays(8),
                'total_days' => 1,
                'reason' => 'Menghadiri pemakaman keluarga',
                'status' => 'approved',
                'approved_by' => $approvers->skip(2)->first()?->id ?? $approvers->first()->id,
                'approved_at' => now()->subDays(10),
                'notes' => 'Turut berduka cita',
            ],
            // 11. Pending - Kepala Sub Bagian
            [
                'employee_id' => $users->where('role', 'kepala_sub_bagian')->skip(1)->first()?->id ?? $users->skip(10)->first()->id,
                'permit_type_id' => $permitTypes->first()->id,
                'start_date' => now()->addDays(4),
                'end_date' => now()->addDays(4),
                'total_days' => 1,
                'reason' => 'Keperluan bank',
                'status' => 'pending',
            ],
            // 12. Approved - Employee
            [
                'employee_id' => $users->where('role', 'employee')->skip(7)->first()?->id ?? $users->skip(11)->first()->id,
                'permit_type_id' => $permitTypes->first()->id,
                'start_date' => now()->subDays(2),
                'end_date' => now()->subDays(2),
                'total_days' => 1,
                'reason' => 'Datang terlambat karena ban bocor',
                'status' => 'approved',
                'approved_by' => $approvers->first()->id,
                'approved_at' => now()->subDays(2),
            ],
            // 13. Pending - Manager
            [
                'employee_id' => $users->where('role', 'manager')->skip(2)->first()?->id ?? $users->skip(12)->first()->id,
                'permit_type_id' => $permitTypes->skip(1)->first()?->id ?? $permitTypes->first()->id,
                'start_date' => now()->addDays(8),
                'end_date' => now()->addDays(8),
                'total_days' => 1,
                'reason' => 'Meeting dengan klien',
                'status' => 'pending',
            ],
            // 14. Pending - Employee
            [
                'employee_id' => $users->where('role', 'employee')->skip(8)->first()?->id ?? $users->skip(13)->first()->id,
                'permit_type_id' => $permitTypes->first()->id,
                'start_date' => now()->addDays(12),
                'end_date' => now()->addDays(12),
                'total_days' => 1,
                'reason' => 'Mengurus BPJS',
                'status' => 'pending',
            ],
            // 15. Pending - Employee
            [
                'employee_id' => $users->where('role', 'employee')->skip(9)->first()?->id ?? $users->skip(14)->first()->id,
                'permit_type_id' => $permitTypes->first()->id,
                'start_date' => now()->addDays(6),
                'end_date' => now()->addDays(6),
                'total_days' => 1,
                'reason' => 'Antar anak ke sekolah',
                'status' => 'pending',
            ],
        ];

        foreach ($permitsData as $data) {
            Permit::create($data);
        }

        $this->command->info('Successfully created 15 permit records.');
    }
}

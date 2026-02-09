<?php

namespace Tests\Feature;

use App\Models\EmployeeWorkTimeSchedule;
use App\Models\JamKerja;
use App\Models\ShiftKerja;
use App\Models\User;
use App\Models\Departemen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class WorkSchedulePdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_download_pdf_with_valid_data()
    {
        $admin = User::factory()->create();
        $dept = Departemen::create(['name' => 'IT Dept', 'code' => 'IT', 'urut' => 1]);
        $user = User::factory()->create(['departemen_id' => $dept->id]);
        $shift = ShiftKerja::factory()->create(['name' => 'Regular']);
        $jamKerja = JamKerja::create([
            'code' => 'JK01',
            'name' => 'Normal',
            'start_time' => '08:00',
            'end_time' => '17:00',
            'is_active' => true
        ]);

        EmployeeWorkTimeSchedule::create([
            'user_id' => $user->id,
            'shift_id' => $shift->id,
            'jam_kerja_id' => $jamKerja->id,
            'schedule_date' => '2025-01-01',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.users.work-schedule-pdf', [
            'user_id' => $user->id,
            'start_date' => '2025-01-01',
            'end_date' => '2025-01-05',
        ]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_cannot_download_if_unauthenticated()
    {
        $user = User::factory()->create();
        $response = $this->get(route('admin.users.work-schedule-pdf', [
            'user_id' => $user->id,
            'start_date' => '2025-01-01',
            'end_date' => '2025-01-05',
        ]));

        $response->assertRedirect();
    }

    public function test_validates_range_limit()
    {
        $admin = User::factory()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($admin)
            ->from('/admin/users')
            ->get(route('admin.users.work-schedule-pdf', [
                'user_id' => $user->id,
                'start_date' => '2025-01-01',
                'end_date' => '2025-02-15', // > 31 days
            ]));

        $response->assertSessionHas('error', 'Rentang maksimal 31 hari.');
    }
}

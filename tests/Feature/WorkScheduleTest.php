<?php

namespace Tests\Feature;

use App\Models\EmployeeWorkTimeSchedule;
use App\Models\JamKerja;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_save_work_schedule_with_user_id()
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(); // Acting as admin/user who can save
        
        $jamKerja = JamKerja::create([
            'name' => 'Shift Pagi',
            'start_time' => '08:00',
            'end_time' => '17:00',
            'is_active' => true,
        ]);

        $this->actingAs($admin);

        $response = $this->postJson(route('admin.ajax.save-work-schedule'), [
            'user_id' => $user->id,
            'day' => 10,
            'month' => 10,
            'year' => 2026,
            'jam_kerja_ids' => [$jamKerja->id],
        ]);

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);

        $this->assertDatabaseHas('employee_work_time_schedule', [
            'user_id' => $user->id,
            'schedule_date' => '2026-10-10',
            'jam_kerja_id' => $jamKerja->id,
        ]);
    }

    public function test_can_get_work_schedule()
    {
        $user = User::factory()->create();
        $admin = User::factory()->create();
        
        $jamKerja = JamKerja::create([
            'name' => 'Shift Pagi',
            'start_time' => '08:00',
            'end_time' => '17:00',
            'is_active' => true,
        ]);

        EmployeeWorkTimeSchedule::create([
            'user_id' => $user->id,
            'schedule_date' => '2026-10-10',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'jam_kerja_id' => $jamKerja->id,
        ]);

        $this->actingAs($admin);

        $response = $this->getJson(route('admin.ajax.get-work-schedule', [
            'user_id' => $user->id,
            'day' => 10,
            'month' => 10,
            'year' => 2026,
        ]));

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);
                 
        // Verify data contains the jam_kerja_id
        $data = $response->json('data');
        $this->assertContains($jamKerja->id, $data);
    }
}

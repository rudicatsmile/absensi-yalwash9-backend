<?php

namespace Tests\Feature;

use App\Models\EmployeeWorkTimeSchedule;
use App\Models\JamKerja;
use App\Models\ShiftKerja;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WorkScheduleStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_work_schedule_with_valid_shift_id()
    {
        // 1. Setup Data
        $user = User::factory()->create();
        $admin = User::factory()->create(); // Acting as admin
        
        $shift = ShiftKerja::factory()->create();
        
        // Attach user to shift (populate shift_kerja_user pivot)
        DB::table('shift_kerja_user')->insert([
            'user_id' => $user->id,
            'shift_kerja_id' => $shift->id,
        ]);

        $jamKerja = JamKerja::create([
            'code' => 'JK01',
            'name' => 'Pagi',
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'is_active' => true,
        ]);

        $payload = [
            'user_id' => $user->id,
            'shift_id' => $shift->id,
            'day' => 15,
            'month' => 10,
            'year' => 2025,
            'jam_kerja_ids' => [$jamKerja->id],
        ];

        // 2. Act
        $response = $this->actingAs($admin)
            ->postJson(route('admin.ajax.save-work-schedule'), $payload);

        // 3. Assert
        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('employee_work_time_schedule', [
            'user_id' => $user->id,
            'shift_id' => $shift->id,
            'jam_kerja_id' => $jamKerja->id,
            'schedule_date' => '2025-10-15',
        ]);
    }

    public function test_store_work_schedule_fails_if_shift_not_assigned_to_user()
    {
        // 1. Setup Data
        $user = User::factory()->create();
        $admin = User::factory()->create();
        
        $shift = ShiftKerja::factory()->create();
        // Do NOT attach user to shift

        $jamKerja = JamKerja::create([
            'code' => 'JK01',
            'name' => 'Pagi',
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'is_active' => true,
        ]);

        $payload = [
            'user_id' => $user->id,
            'shift_id' => $shift->id, // Shift exists but not assigned to user
            'day' => 15,
            'month' => 10,
            'year' => 2025,
            'jam_kerja_ids' => [$jamKerja->id],
        ];

        // 2. Act
        $response = $this->actingAs($admin)
            ->postJson(route('admin.ajax.save-work-schedule'), $payload);

        // 3. Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['shift_id']);
    }

    public function test_store_work_schedule_fails_if_shift_does_not_exist()
    {
        // 1. Setup Data
        $user = User::factory()->create();
        $admin = User::factory()->create();
        
        $jamKerja = JamKerja::create([
            'code' => 'JK01',
            'name' => 'Pagi',
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'is_active' => true,
        ]);

        $payload = [
            'user_id' => $user->id,
            'shift_id' => 99999, // Non-existent shift
            'day' => 15,
            'month' => 10,
            'year' => 2025,
            'jam_kerja_ids' => [$jamKerja->id],
        ];

        // 2. Act
        $response = $this->actingAs($admin)
            ->postJson(route('admin.ajax.save-work-schedule'), $payload);

        // 3. Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['shift_id']);
    }
}

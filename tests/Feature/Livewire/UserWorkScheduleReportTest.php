<?php

namespace Tests\Feature\Livewire;

use App\Livewire\UserWorkScheduleReport;
use App\Models\Departemen;
use App\Models\EmployeeWorkTimeSchedule;
use App\Models\JamKerja;
use App\Models\ShiftKerja;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserWorkScheduleReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_component_renders_correctly()
    {
        Livewire::test(UserWorkScheduleReport::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.user-work-schedule-report');
    }

    public function test_initial_state_sets_current_month()
    {
        Livewire::test(UserWorkScheduleReport::class)
            ->assertSet('startDate', Carbon::now()->startOfMonth()->format('Y-m-d'))
            ->assertSet('endDate', Carbon::now()->endOfMonth()->format('Y-m-d'));
    }

    public function test_can_filter_by_date()
    {
        $user = User::factory()->create();
        $shift = ShiftKerja::factory()->create(['name' => 'Shift A']);
        $jamKerja = JamKerja::create([
            'name' => 'Normal',
            'start_time' => '08:00',
            'end_time' => '17:00',
            'is_active' => true
        ]);

        // Create schedule within range
        EmployeeWorkTimeSchedule::create([
            'user_id' => $user->id,
            'shift_id' => $shift->id,
            'jam_kerja_id' => $jamKerja->id,
            'schedule_date' => Carbon::now()->format('Y-m-d'),
            'start_time' => '08:00',
            'end_time' => '17:00',
        ]);

        // Create schedule outside range
        EmployeeWorkTimeSchedule::create([
            'user_id' => $user->id,
            'shift_id' => $shift->id,
            'jam_kerja_id' => $jamKerja->id,
            'schedule_date' => Carbon::now()->subMonth()->format('Y-m-d'),
            'start_time' => '08:00',
            'end_time' => '17:00',
        ]);

        Livewire::test(UserWorkScheduleReport::class)
            ->call('loadData')
            ->set('startDate', Carbon::now()->startOfMonth()->format('Y-m-d'))
            ->set('endDate', Carbon::now()->endOfMonth()->format('Y-m-d'))
            ->assertSee($user->name)
            ->assertViewHas('schedules', function ($schedules) {
                return $schedules->count() === 1;
            });
    }

    public function test_can_filter_by_department()
    {
        $dept1 = Departemen::create(['name' => 'IT', 'urut' => 1]);
        $dept2 = Departemen::create(['name' => 'HR', 'urut' => 2]);

        $user1 = User::factory()->create(['departemen_id' => $dept1->id, 'name' => 'User IT']);
        $user2 = User::factory()->create(['departemen_id' => $dept2->id, 'name' => 'User HR']);

        $shift = ShiftKerja::factory()->create(['name' => 'Shift A']);
        $jamKerja = JamKerja::create([
            'name' => 'Normal',
            'start_time' => '08:00',
            'end_time' => '17:00',
            'is_active' => true
        ]);

        // Schedule for User IT
        EmployeeWorkTimeSchedule::create([
            'user_id' => $user1->id,
            'shift_id' => $shift->id,
            'jam_kerja_id' => $jamKerja->id,
            'schedule_date' => Carbon::now()->format('Y-m-d'),
            'start_time' => '08:00',
            'end_time' => '17:00',
        ]);

        // Schedule for User HR
        EmployeeWorkTimeSchedule::create([
            'user_id' => $user2->id,
            'shift_id' => $shift->id,
            'jam_kerja_id' => $jamKerja->id,
            'schedule_date' => Carbon::now()->format('Y-m-d'),
            'start_time' => '08:00',
            'end_time' => '17:00',
        ]);

        Livewire::test(UserWorkScheduleReport::class)
            ->call('loadData')
            ->set('departmentId', $dept1->id)
            ->assertSee('User IT')
            ->assertDontSee('User HR')
            ->assertViewHas('schedules', function ($schedules) {
                return $schedules->count() === 1;
            });
    }

    public function test_can_search_by_user_name()
    {
        $user1 = User::factory()->create(['name' => 'John Doe']);
        $user2 = User::factory()->create(['name' => 'Jane Smith']);

        $shift = ShiftKerja::factory()->create(['name' => 'Shift A']);
        $jamKerja = JamKerja::create([
            'name' => 'Normal',
            'start_time' => '08:00',
            'end_time' => '17:00',
            'is_active' => true
        ]);

        EmployeeWorkTimeSchedule::create([
            'user_id' => $user1->id,
            'shift_id' => $shift->id,
            'jam_kerja_id' => $jamKerja->id,
            'schedule_date' => Carbon::now()->format('Y-m-d'),
            'start_time' => '08:00',
            'end_time' => '17:00',
        ]);

        EmployeeWorkTimeSchedule::create([
            'user_id' => $user2->id,
            'shift_id' => $shift->id,
            'jam_kerja_id' => $jamKerja->id,
            'schedule_date' => Carbon::now()->format('Y-m-d'),
            'start_time' => '08:00',
            'end_time' => '17:00',
        ]);

        Livewire::test(UserWorkScheduleReport::class)
            ->call('loadData')
            ->set('search', 'John')
            ->assertSee('John Doe')
            ->assertDontSee('Jane Smith');
    }

    public function test_can_export_excel()
    {
        Livewire::test(UserWorkScheduleReport::class)
            ->call('exportExcel')
            ->assertFileDownloaded();
    }

    public function test_can_export_pdf()
    {
        Livewire::test(UserWorkScheduleReport::class)
            ->call('exportPdf')
            ->assertFileDownloaded();
    }
}

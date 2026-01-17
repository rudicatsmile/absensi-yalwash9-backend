<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\EmployeeWorkSchedule;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EmployeeWorkScheduleFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_schedule_for_leap_year_february(): void
    {
        Filament::setCurrentPanel('admin');

        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $employee = User::factory()->create([
            'email' => 'employee@example.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
        ]);

        $this->actingAs($admin);

        $month = 2;
        $year = 2024; // leap year
        $daysInMonth = \Carbon\Carbon::createFromDate($year, $month, 1)->daysInMonth;
        $allDays = array_map(fn ($d) => (string) $d, range(1, $daysInMonth));

        Livewire::test(ListUsers::class)
            ->callTableAction('ubah_jadwal', $employee, [
                'month' => $month,
                'year' => $year,
                'shift' => 'pagi',
                'allowed_days' => $allDays,
            ])
            ->assertNotified();

        $this->assertDatabaseHas('employee_work_schedule', [
            'employee_id' => $employee->id,
            'month' => $month,
            'year' => $year,
        ]);

        $schedule = EmployeeWorkSchedule::query()
            ->where('employee_id', $employee->id)
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        $this->assertNotNull($schedule);
        $this->assertEquals(true, $schedule->allowed_days['29']); // Feb 29 exists on leap year
    }
}

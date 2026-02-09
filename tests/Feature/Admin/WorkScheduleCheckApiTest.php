<?php

namespace Tests\Feature\Admin;

use App\Models\EmployeeWorkSchedule;
use App\Models\ShiftKerja;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkScheduleCheckApiTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $employee;
    protected $shift;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@example.com',
        ]);

        // Create employee
        $this->employee = User::factory()->create([
            'role' => 'staff',
        ]);

        // Create shift
        $this->shift = ShiftKerja::factory()->create([
            'name' => 'Morning Shift',
        ]);
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->getJson(route('admin.ajax.check-work-schedule'));

        // Depending on middleware, might be 401 or redirect
        // Assuming 'auth' middleware is applied to admin routes
        $this->assertTrue($response->status() === 401 || $response->status() === 302);
    }

    /** @test */
    public function it_validates_required_parameters()
    {
        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.ajax.check-work-schedule'));

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Validasi gagal',
            ]);
    }

    /** @test */
    public function it_returns_exists_true_when_schedule_exists()
    {
        $month = 10;
        $year = 2024;

        // Create schedule
        EmployeeWorkSchedule::create([
            'user_id' => $this->employee->id,
            'shift_id' => $this->shift->id,
            'month' => $month,
            'year' => $year,
            'jam_kerja_ids' => [], // JSON field
            'allowed_days' => [], // JSON field
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.ajax.check-work-schedule', [
                'user_id' => $this->employee->id,
                'shift_id' => $this->shift->id,
                'month' => $month,
                'year' => $year,
            ]));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'exists' => true,
            ]);
    }

    /** @test */
    public function it_returns_exists_false_when_schedule_does_not_exist()
    {
        $month = 10;
        $year = 2024;

        // Don't create schedule

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.ajax.check-work-schedule', [
                'user_id' => $this->employee->id,
                'shift_id' => $this->shift->id,
                'month' => $month,
                'year' => $year,
            ]));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'exists' => false,
            ]);
    }

    /** @test */
    public function it_returns_exists_false_when_parameters_do_not_match()
    {
        $month = 10;
        $year = 2024;

        // Create schedule for different shift
        $otherShift = ShiftKerja::factory()->create();
        EmployeeWorkSchedule::create([
            'user_id' => $this->employee->id,
            'shift_id' => $otherShift->id,
            'month' => $month,
            'year' => $year,
            'jam_kerja_ids' => [],
            'allowed_days' => [],
        ]);

        // Check for original shift
        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.ajax.check-work-schedule', [
                'user_id' => $this->employee->id,
                'shift_id' => $this->shift->id,
                'month' => $month,
                'year' => $year,
            ]));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'exists' => false,
            ]);
    }
}

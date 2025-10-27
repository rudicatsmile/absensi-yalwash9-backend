<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\WorkShift;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkShiftTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test WorkShift model creation
     */
    public function test_work_shift_can_be_created(): void
    {
        $workShift = WorkShift::create([
            'name' => 'Test Shift',
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'is_cross_day' => false,
            'grace_period_minutes' => 15,
            'is_active' => true,
            'description' => 'Test shift description',
        ]);

        $this->assertInstanceOf(WorkShift::class, $workShift);
        $this->assertEquals('Test Shift', $workShift->name);
        $this->assertEquals('08:00:00', $workShift->start_time);
        $this->assertEquals('16:00:00', $workShift->end_time);
        $this->assertFalse($workShift->is_cross_day);
        $this->assertEquals(15, $workShift->grace_period_minutes);
        $this->assertTrue($workShift->is_active);
        $this->assertEquals('Test shift description', $workShift->description);
    }

    /**
     * Test WorkShift model validation
     */
    public function test_work_shift_requires_name(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        WorkShift::create([
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
        ]);
    }

    /**
     * Test WorkShift model validation for start_time
     */
    public function test_work_shift_requires_start_time(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        WorkShift::create([
            'name' => 'Test Shift',
            'end_time' => '16:00:00',
        ]);
    }

    /**
     * Test WorkShift model validation for end_time
     */
    public function test_work_shift_requires_end_time(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        WorkShift::create([
            'name' => 'Test Shift',
            'start_time' => '08:00:00',
        ]);
    }

    /**
     * Test WorkShift default values
     */
    public function test_work_shift_default_values(): void
    {
        $workShift = WorkShift::create([
            'name' => 'Test Shift',
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
        ]);

        $this->assertFalse($workShift->is_cross_day);
        $this->assertEquals(0, $workShift->grace_period_minutes);
        $this->assertTrue($workShift->is_active);
        $this->assertNull($workShift->description);
    }

    /**
     * Test WorkShift users relationship
     */
    public function test_work_shift_has_users_relationship(): void
    {
        $workShift = WorkShift::create([
            'name' => 'Test Shift',
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
        ]);

        $user = User::factory()->create();
        $workShift->users()->attach($user->id);

        $this->assertTrue($workShift->users->contains($user));
        $this->assertEquals(1, $workShift->users->count());
    }

    /**
     * Test WorkShift duration calculation
     */
    public function test_work_shift_duration_calculation(): void
    {
        // Regular shift (same day)
        $workShift = WorkShift::create([
            'name' => 'Regular Shift',
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'is_cross_day' => false,
        ]);

        $this->assertEquals('8 jam', $workShift->duration_formatted);

        // Cross-day shift
        $crossDayShift = WorkShift::create([
            'name' => 'Night Shift',
            'start_time' => '22:00:00',
            'end_time' => '06:00:00',
            'is_cross_day' => true,
        ]);

        $this->assertEquals('8 jam', $crossDayShift->duration_formatted);
    }

    /**
     * Test WorkShift scope for active shifts
     */
    public function test_work_shift_active_scope(): void
    {
        WorkShift::create([
            'name' => 'Active Shift',
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'is_active' => true,
        ]);

        WorkShift::create([
            'name' => 'Inactive Shift',
            'start_time' => '16:00:00',
            'end_time' => '00:00:00',
            'is_active' => false,
        ]);

        $activeShifts = WorkShift::active()->get();
        $this->assertEquals(1, $activeShifts->count());
        $this->assertEquals('Active Shift', $activeShifts->first()->name);
    }

    /**
     * Test WorkShift can be soft deleted
     */
    public function test_work_shift_can_be_soft_deleted(): void
    {
        $workShift = WorkShift::create([
            'name' => 'Test Shift',
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
        ]);

        $workShift->delete();

        $this->assertSoftDeleted($workShift);
        $this->assertEquals(0, WorkShift::count());
        $this->assertEquals(1, WorkShift::withTrashed()->count());
    }
}

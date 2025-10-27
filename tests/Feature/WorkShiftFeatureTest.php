<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WorkShift;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class WorkShiftFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test User can have multiple work shifts
     */
    public function test_user_can_have_multiple_work_shifts(): void
    {
        $user = User::factory()->create();
        
        $workShift1 = WorkShift::create([
            'name' => 'Morning Shift',
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
        ]);

        $workShift2 = WorkShift::create([
            'name' => 'Evening Shift',
            'start_time' => '16:00:00',
            'end_time' => '00:00:00',
        ]);

        $user->workShifts()->attach([$workShift1->id, $workShift2->id]);

        $this->assertEquals(2, $user->workShifts->count());
        $this->assertTrue($user->workShifts->contains($workShift1));
        $this->assertTrue($user->workShifts->contains($workShift2));
    }

    /**
     * Test User syncWorkShifts method
     */
    public function test_user_sync_work_shifts_method(): void
    {
        $user = User::factory()->create();
        
        $workShift1 = WorkShift::create([
            'name' => 'Morning Shift',
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
        ]);

        $workShift2 = WorkShift::create([
            'name' => 'Evening Shift',
            'start_time' => '16:00:00',
            'end_time' => '00:00:00',
        ]);

        $workShift3 = WorkShift::create([
            'name' => 'Night Shift',
            'start_time' => '00:00:00',
            'end_time' => '08:00:00',
        ]);

        // Initial sync
        $user->syncWorkShifts([$workShift1->id, $workShift2->id]);
        $this->assertEquals(2, $user->workShifts->count());

        // Update sync (replace with new shifts)
        $user->syncWorkShifts([$workShift2->id, $workShift3->id]);
        $user->refresh();
        
        $this->assertEquals(2, $user->workShifts->count());
        $this->assertFalse($user->workShifts->contains($workShift1));
        $this->assertTrue($user->workShifts->contains($workShift2));
        $this->assertTrue($user->workShifts->contains($workShift3));
    }

    /**
     * Test User syncWorkShifts with invalid IDs
     */
    public function test_user_sync_work_shifts_with_invalid_ids(): void
    {
        $user = User::factory()->create();
        
        $workShift = WorkShift::create([
            'name' => 'Valid Shift',
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
        ]);

        // Try to sync with invalid ID (should throw validation exception)
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $user->syncWorkShifts([$workShift->id, 999, 'invalid']);
    }

    /**
     * Test User queueWorkShiftsForSync method
     */
    public function test_user_queue_work_shifts_for_sync(): void
    {
        $user = User::factory()->create();
        
        $workShift1 = WorkShift::create([
            'name' => 'Morning Shift',
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
        ]);

        $workShift2 = WorkShift::create([
            'name' => 'Evening Shift',
            'start_time' => '16:00:00',
            'end_time' => '00:00:00',
        ]);

        $user->queueWorkShiftsForSync([$workShift1->id, $workShift2->id]);
        
        // Use reflection to access the protected property
        $reflection = new \ReflectionClass($user);
        $property = $reflection->getProperty('queuedWorkShiftIds');
        $property->setAccessible(true);
        $queuedIds = $property->getValue($user);
        
        $this->assertEquals([$workShift1->id, $workShift2->id], $queuedIds);
    }

    /**
     * Test WorkShift pivot table relationship
     */
    public function test_work_shift_pivot_table_relationship(): void
    {
        $user = User::factory()->create();
        
        $workShift = WorkShift::create([
            'name' => 'Test Shift',
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
        ]);

        $user->workShifts()->attach($workShift->id);

        // Test from User side
        $this->assertTrue($user->workShifts->contains($workShift));
        
        // Test from WorkShift side
        $this->assertTrue($workShift->users->contains($user));
        
        // Test pivot table data
        $pivotData = $user->workShifts()->where('work_shift_id', $workShift->id)->first()->pivot;
        $this->assertEquals($user->id, $pivotData->user_id);
        $this->assertEquals($workShift->id, $pivotData->work_shift_id);
        $this->assertNotNull($pivotData->created_at);
        $this->assertNotNull($pivotData->updated_at);
    }

    /**
     * Test WorkShift users count
     */
    public function test_work_shift_users_count(): void
    {
        $workShift = WorkShift::create([
            'name' => 'Popular Shift',
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
        ]);

        $users = User::factory()->count(3)->create();
        
        foreach ($users as $user) {
            $user->workShifts()->attach($workShift->id);
        }

        $this->assertEquals(3, $workShift->users->count());
    }

    /**
     * Test WorkShift cascade delete
     */
    public function test_work_shift_cascade_delete(): void
    {
        $user = User::factory()->create();
        
        $workShift = WorkShift::create([
            'name' => 'Test Shift',
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
        ]);

        $user->workShifts()->attach($workShift->id);
        
        // Verify relationship exists
        $this->assertEquals(1, $user->workShifts->count());
        
        // Delete work shift
        $workShift->delete();
        
        // Verify pivot table entry is also deleted (cascade)
        $user->refresh();
        $this->assertEquals(0, $user->workShifts->count());
    }

    /**
     * Test User model booted method syncs queued work shifts
     */
    public function test_user_model_booted_syncs_queued_work_shifts(): void
    {
        $workShift1 = WorkShift::create([
            'name' => 'Morning Shift',
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
        ]);

        $workShift2 = WorkShift::create([
            'name' => 'Evening Shift',
            'start_time' => '16:00:00',
            'end_time' => '00:00:00',
        ]);

        // Create user with queued work shifts
        $user = new User([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
        
        $user->queueWorkShiftsForSync([$workShift1->id, $workShift2->id]);
        $user->save();

        // Verify work shifts were synced after save
        $user->refresh();
        $this->assertEquals(2, $user->workShifts->count());
        $this->assertTrue($user->workShifts->contains($workShift1));
        $this->assertTrue($user->workShifts->contains($workShift2));
        
        // Verify queue was cleared using reflection
        $reflection = new \ReflectionClass($user);
        $property = $reflection->getProperty('queuedWorkShiftIds');
        $property->setAccessible(true);
        $queuedIds = $property->getValue($user);
        
        $this->assertEmpty($queuedIds);
    }
}

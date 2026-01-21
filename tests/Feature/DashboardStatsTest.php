<?php

namespace Tests\Feature;

use App\Filament\Widgets\DashboardStatsWidget;
use App\Models\Leave;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardStatsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_dashboard_stats_shows_total_cuti_correctly(): void
    {
        // Create an admin user
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->actingAs($admin);

        // Get initial count of relevant leaves (overlapping today)
        // Note: The widget logic filters by date range.
        // We need to replicate the widget's logic to know the baseline?
        // Or simply count the widget's output before we add our data.

        // Let's create the widget first to get baseline?
        // Hard to extract value from widget HTML easily without complex parsing.

        // Instead, let's just create data that we KNOW will be picked up,
        // and rely on the fact that we are adding to it.

        // But the widget displays a number.
        // I can use regex to find the number in "Total Cuti" card.

        // Let's create users
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        // Create LeaveType
        $leaveType = \App\Models\LeaveType::firstOrCreate(
            ['name' => 'Test Annual Leave'],
            ['quota_days' => 12, 'is_paid' => true]
        );

        // Calculate expected increase
        $increase = 0;

        // Leave 1: Today is within range (Should be counted)
        Leave::create([
            'employee_id' => $user1->id,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
            'total_days' => 3,
            'leave_type_id' => $leaveType->id,
            'reason' => 'Test Leave 1',
            'status' => 'approved',
        ]);
        $increase++;

        // Leave 2: Today is exactly start date (Should NOT be counted because it is pending)
        Leave::create([
            'employee_id' => $user2->id,
            'start_date' => now(),
            'end_date' => now()->addDays(2),
            'total_days' => 3,
            'leave_type_id' => $leaveType->id,
            'reason' => 'Test Leave 2',
            'status' => 'pending',
        ]);
        // $increase++; // Pending should not be counted

        // Leave 3: In the past (Should NOT be counted)
        Leave::create([
            'employee_id' => $user3->id,
            'start_date' => now()->subDays(5),
            'end_date' => now()->subDays(2),
            'total_days' => 4,
            'leave_type_id' => $leaveType->id,
            'reason' => 'Past Leave',
            'status' => 'approved',
        ]);

        // We can't easily know the previous count without running the query ourselves.
        // So let's run the query logic ourselves to get the EXPECTED total.

        $expectedTotal = Leave::whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->where('status', 'approved')
            ->count();

        // Test the widget
        Livewire::test(DashboardStatsWidget::class)
            ->assertSee('Total Cuti')
            ->assertSee($expectedTotal);
    }
}

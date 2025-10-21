<?php

namespace Database\Seeders;

use App\Models\Permit;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Database\Seeder;

class PermitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $leaveTypes = LeaveType::all();

        if ($users->isEmpty() || $leaveTypes->isEmpty()) {
            $this->command->warn('No users or leave types found. Please run UserSeeder and LeaveTypeSeeder first.');
            return;
        }

        // Create some sample permit requests
        $sampleUsers = $users->take(5);
        foreach ($sampleUsers as $user) {
            $leaveType = $leaveTypes->random();

            // Pending permit
            Permit::create([
                'employee_id' => $user->id,
                'leave_type_id' => $leaveType->id,
                'start_date' => now()->addDays(rand(1, 30)),
                'end_date' => now()->addDays(rand(31, 40)),
                'total_days' => rand(1, 5),
                'reason' => 'Sample permit request for testing',
                'status' => 'pending',
                'notes' => 'Pending permit request',
            ]);

            // Approved permit
            Permit::create([
                'employee_id' => $user->id,
                'leave_type_id' => $leaveType->id,
                'start_date' => now()->subDays(rand(10, 20)),
                'end_date' => now()->subDays(rand(5, 9)),
                'total_days' => rand(1, 3),
                'reason' => 'Sample approved permit',
                'status' => 'approved',
                'approved_by' => $users->first()->id,
                'approved_at' => now()->subDays(rand(11, 21)),
                'notes' => 'Approved permit request',
            ]);

            // Rejected permit
            Permit::create([
                'employee_id' => $user->id,
                'leave_type_id' => $leaveType->id,
                'start_date' => now()->addDays(rand(5, 15)),
                'end_date' => now()->addDays(rand(16, 25)),
                'total_days' => rand(1, 4),
                'reason' => 'Sample rejected permit',
                'status' => 'rejected',
                'approved_by' => $users->first()->id,
                'approved_at' => now()->subDays(rand(1, 5)),
                'notes' => 'Rejected due to insufficient documentation',
            ]);
        }

        $this->command->info('Permit testing data created successfully.');
    }
}
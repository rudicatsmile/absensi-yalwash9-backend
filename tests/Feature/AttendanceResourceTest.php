<?php

namespace Tests\Feature;

use App\Filament\Resources\Attendances\Pages\EditAttendance;
use App\Filament\Resources\Attendances\Pages\ListAttendances;
use App\Models\Attendance;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AttendanceResourceTest extends TestCase
{
    // use RefreshDatabase; // Commneted out to avoid wiping db if not configured correctly, but usually safe in tests.
    // Ideally use RefreshDatabase, but for now I'll manually cleanup or just create records.
    // Given the environment, I'll use RefreshDatabase if I'm sure it uses sqlite or separate db.
    // The composer.json scripts suggest sqlite is used for testing or local?
    // "post-create-project-cmd": [ ... "touch('database/database.sqlite')" ]
    // But .env might be using mysql.
    // I will use RefreshDatabase.

    use RefreshDatabase;

    public function test_can_render_list_attendances_page()
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->actingAs($user);

        $this->get(ListAttendances::getUrl())
            ->assertSuccessful();
    }

    public function test_can_edit_attendance()
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        $company = \App\Models\Company::create([
            'name' => 'Test Company',
            'email' => 'test@company.com',
            'address' => 'Test Address',
            'latitude' => 0,
            'longitude' => 0,
            'radius_km' => 1,
            'attendance_type' => 'location_based_only',
        ]);

        $locationOld = \App\Models\CompanyLocation::create([
            'company_id' => $company->id,
            'name' => 'Old Location',
            'latitude' => -6.1,
            'longitude' => 106.1,
            'radius_km' => 100,
            'address' => 'Old Address',
            'attendance_type' => 'location_based_only',
        ]);

        $locationNew = \App\Models\CompanyLocation::create([
            'company_id' => $company->id,
            'name' => 'New Location',
            'latitude' => -6.2,
            'longitude' => 106.2,
            'radius_km' => 100,
            'address' => 'New Address',
            'attendance_type' => 'location_based_only',
        ]);

        $shiftOld = \App\Models\ShiftKerja::factory()->create(['is_active' => true, 'name' => 'Old Shift']);
        $shiftNew = \App\Models\ShiftKerja::factory()->create(['is_active' => true, 'name' => 'New Shift']);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'status' => 'on_time',
            'notes' => 'Old notes',
            'shift_id' => $shiftOld->id,
            'company_location_id' => $locationOld->id,
        ]);

        $this->actingAs($user);

        // Test that we can access the edit page
        $this->get(EditAttendance::getUrl(['record' => $attendance]))
            ->assertSuccessful();

        // Test saving changes via Livewire component
        Livewire::test(EditAttendance::class, ['record' => $attendance->getRouteKey()])
            ->assertFormSet(['shift_id' => $shiftOld->id])
            ->assertFormSet(['company_location_id' => $locationOld->id])
            ->fillForm([
                'status' => 'late',
                'notes' => 'Updated notes',
                'time_in' => '09:00',
                'time_out' => '18:00',
                'shift_id' => $shiftNew->id,
                'company_location_id' => $locationNew->id,
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertRedirect(ListAttendances::getUrl());

        $attendance->refresh();

        $this->assertEquals('late', $attendance->status);
        $this->assertEquals('Updated notes', $attendance->notes);
        $this->assertEquals($shiftNew->id, $attendance->shift_id);
        $this->assertEquals($locationNew->id, $attendance->company_location_id);
    }

    public function test_cannot_select_inactive_shift()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $shiftActive = \App\Models\ShiftKerja::factory()->create(['is_active' => true]);
        $shiftInactive = \App\Models\ShiftKerja::factory()->create(['is_active' => false]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'shift_id' => $shiftActive->id,
        ]);

        $this->actingAs($user);

        Livewire::test(EditAttendance::class, ['record' => $attendance->getRouteKey()])
            ->fillForm([
                'shift_id' => $shiftInactive->id,
            ])
            ->call('save')
            ->assertHasFormErrors(['shift_id']);
    }
}

<?php

namespace Tests\Feature;

use App\Filament\Resources\Permits\Pages\EditPermit;
use App\Filament\Resources\Permits\Pages\ListPermits;
use App\Models\Permit;
use App\Models\PermitType;
use App\Models\ShiftKerja;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PermitResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_render_list_permits_page_and_see_shift_and_date_range()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        $shift = ShiftKerja::factory()->create(['name' => 'Shift Pagi Test']);
        $permitType = PermitType::factory()->create(['name' => 'Sakit']);

        $permit = Permit::create([
            'employee_id' => $user->id,
            'permit_type_id' => $permitType->id,
            'shift_id' => $shift->id,
            'start_date' => now(),
            'end_date' => now()->addDays(2),
            'total_days' => 3,
            'reason' => 'Sakit',
            'status' => 'pending',
            'notes' => 'Test notes',
        ]);

        Livewire::test(ListPermits::class)
            ->assertSuccessful()
            ->assertSee('Shift Pagi Test')
            ->assertSee($permit->start_date->format('d/m/Y'))
            ->assertSee($permit->end_date->format('d/m/Y'))
            ->assertSee('Sakit');
    }

    public function test_can_edit_approved_permit()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        $shift = ShiftKerja::factory()->create(['name' => 'Shift Pagi Test']);
        $permitType = PermitType::factory()->create(['name' => 'Sakit']);

        $permit = Permit::create([
            'employee_id' => $user->id,
            'permit_type_id' => $permitType->id,
            'shift_id' => $shift->id,
            'start_date' => now(),
            'end_date' => now()->addDays(2),
            'total_days' => 3,
            'reason' => 'Sakit',
            'status' => 'approved',
            'notes' => 'Test notes',
        ]);

        Livewire::test(ListPermits::class)
            ->assertTableActionVisible('edit', $permit);
    }

    public function test_can_render_edit_permit_page_and_see_shift_select()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        $shift = ShiftKerja::factory()->create(['name' => 'Shift Pagi Test']);
        $permitType = PermitType::factory()->create(['name' => 'Sakit']);

        $permit = Permit::create([
            'employee_id' => $user->id,
            'permit_type_id' => $permitType->id,
            'shift_id' => $shift->id,
            'start_date' => now(),
            'end_date' => now()->addDays(2),
            'total_days' => 3,
            'reason' => 'Sakit',
            'status' => 'pending',
            'notes' => 'Test notes',
        ]);

        Livewire::test(EditPermit::class, ['record' => $permit->getRouteKey()])
            ->assertSuccessful()
            ->assertFormSet([
                'shift_id' => $shift->id,
            ]);
    }

    public function test_can_filter_by_shift()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        $shift1 = ShiftKerja::factory()->create(['name' => 'Shift Pagi Test']);
        $shift2 = ShiftKerja::factory()->create(['name' => 'Shift Malam Test']);
        $permitType = PermitType::factory()->create();

        $permit1 = Permit::create([
            'employee_id' => $user->id,
            'permit_type_id' => $permitType->id,
            'shift_id' => $shift1->id,
            'start_date' => now(),
            'end_date' => now(),
            'total_days' => 1,
            'reason' => 'Reason 1',
            'status' => 'pending',
            'notes' => 'Notes 1',
        ]);

        $permit2 = Permit::create([
            'employee_id' => $user->id,
            'permit_type_id' => $permitType->id,
            'shift_id' => $shift2->id,
            'start_date' => now(),
            'end_date' => now(),
            'total_days' => 1,
            'reason' => 'Reason 2',
            'status' => 'pending',
            'notes' => 'Notes 2',
        ]);

        Livewire::test(ListPermits::class)
            ->assertCanSeeTableRecords([$permit1, $permit2])
            ->filterTable('shift_id', $shift1->id)
            ->assertCanSeeTableRecords([$permit1])
            ->assertCanNotSeeTableRecords([$permit2]);
    }
}

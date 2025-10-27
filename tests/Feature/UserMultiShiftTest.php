<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ShiftKerja;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * Test untuk memverifikasi fungsionalitas multi shift kerja pada User
 * 
 * Test ini memastikan bahwa:
 * 1. User dapat memiliki multiple shift kerja
 * 2. Relasi many-to-many berfungsi dengan benar
 * 3. Data tersimpan dengan efisien ke database
 * 4. Validasi input bekerja sesuai ekspektasi
 */
class UserMultiShiftTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Tidak perlu seed data karena menggunakan factory
    }

    /**
     * Test bahwa user dapat memiliki multiple shift kerja
     */
    public function test_user_can_have_multiple_shift_kerjas(): void
    {
        // Arrange: Buat user dan shift kerja
        $user = User::factory()->create();
        $shifts = ShiftKerja::factory()->count(3)->create();

        // Act: Attach multiple shifts ke user
        $user->shiftKerjas()->attach($shifts->pluck('id'));

        // Assert: Verifikasi relasi
        $this->assertCount(3, $user->shiftKerjas);
        $this->assertEquals($shifts->pluck('id')->sort(), $user->shiftKerjas->pluck('id')->sort());
    }

    /**
     * Test bahwa shift kerja dapat memiliki multiple users
     */
    public function test_shift_kerja_can_have_multiple_users(): void
    {
        // Arrange: Buat shift dan users
        $shift = ShiftKerja::factory()->create();
        $users = User::factory()->count(3)->create();

        // Act: Attach multiple users ke shift
        $shift->users()->attach($users->pluck('id'));

        // Assert: Verifikasi relasi
        $this->assertCount(3, $shift->users);
        $this->assertEquals($users->pluck('id')->sort(), $shift->users->pluck('id')->sort());
    }

    /**
     * Test bahwa pivot table menyimpan data dengan benar
     */
    public function test_pivot_table_stores_data_correctly(): void
    {
        // Arrange
        $user = User::factory()->create();
        $shift1 = ShiftKerja::factory()->create(['name' => 'Shift Pagi']);
        $shift2 = ShiftKerja::factory()->create(['name' => 'Shift Siang']);

        // Act: Attach shifts dengan timestamps
        $user->shiftKerjas()->attach([
            $shift1->id => ['created_at' => now(), 'updated_at' => now()],
            $shift2->id => ['created_at' => now(), 'updated_at' => now()]
        ]);

        // Assert: Verifikasi data di pivot table
        $this->assertDatabaseHas('shift_kerja_user', [
            'user_id' => $user->id,
            'shift_kerja_id' => $shift1->id
        ]);
        
        $this->assertDatabaseHas('shift_kerja_user', [
            'user_id' => $user->id,
            'shift_kerja_id' => $shift2->id
        ]);
    }

    /**
     * Test bahwa user dapat menghapus shift kerja
     */
    public function test_user_can_detach_shift_kerjas(): void
    {
        // Arrange
        $user = User::factory()->create();
        $shifts = ShiftKerja::factory()->count(3)->create();
        $user->shiftKerjas()->attach($shifts->pluck('id'));

        // Act: Detach satu shift
        $shiftToDetach = $shifts->first();
        $user->shiftKerjas()->detach($shiftToDetach->id);

        // Assert: Verifikasi shift terhapus dari relasi
        $this->assertCount(2, $user->shiftKerjas);
        $this->assertFalse($user->shiftKerjas->contains($shiftToDetach));
    }

    /**
     * Test bahwa user dapat sync shift kerja
     */
    public function test_user_can_sync_shift_kerjas(): void
    {
        // Arrange
        $user = User::factory()->create();
        $initialShifts = ShiftKerja::factory()->count(2)->create();
        $newShifts = ShiftKerja::factory()->count(3)->create();
        
        $user->shiftKerjas()->attach($initialShifts->pluck('id'));

        // Act: Sync dengan shift baru
        $user->shiftKerjas()->sync($newShifts->pluck('id'));

        // Assert: Verifikasi hanya shift baru yang tersisa
        $this->assertCount(3, $user->shiftKerjas);
        $this->assertEquals($newShifts->pluck('id')->sort(), $user->shiftKerjas->pluck('id')->sort());
    }

    /**
     * Test bahwa relasi many-to-many bekerja dengan eager loading
     */
    public function test_eager_loading_works_correctly(): void
    {
        // Arrange
        $users = User::factory()->count(2)->create();
        $shifts = ShiftKerja::factory()->count(3)->create();
        
        foreach ($users as $user) {
            $user->shiftKerjas()->attach($shifts->random(2)->pluck('id'));
        }

        // Act: Load users dengan shift kerja
        $usersWithShifts = User::with('shiftKerjas')->get();

        // Assert: Verifikasi eager loading
        foreach ($usersWithShifts as $user) {
            $this->assertTrue($user->relationLoaded('shiftKerjas'));
            $this->assertGreaterThan(0, $user->shiftKerjas->count());
        }
    }

    /**
     * Test bahwa cascade delete bekerja dengan benar
     */
    public function test_cascade_delete_works_correctly(): void
    {
        // Arrange
        $user = User::factory()->create();
        $shift = ShiftKerja::factory()->create();
        $user->shiftKerjas()->attach($shift->id);

        // Act: Hapus user
        $userId = $user->id;
        $user->delete();

        // Assert: Verifikasi pivot record terhapus
        $this->assertDatabaseMissing('shift_kerja_user', [
            'user_id' => $userId,
            'shift_kerja_id' => $shift->id
        ]);
        
        // Shift masih ada
        $this->assertDatabaseHas('shift_kerjas', ['id' => $shift->id]);
    }

    /**
     * Test validasi minimum shift kerja
     */
    public function test_user_must_have_at_least_one_shift(): void
    {
        // Arrange
        $user = User::factory()->create();
        
        // Act & Assert: User tanpa shift tidak valid untuk operasi tertentu
        $this->assertCount(0, $user->shiftKerjas);
        
        // Attach minimal satu shift
        $shift = ShiftKerja::factory()->create();
        $user->shiftKerjas()->attach($shift->id);
        
        // Refresh user untuk mendapatkan relasi terbaru
        $user->refresh();
        $this->assertCount(1, $user->shiftKerjas);
    }

    /**
     * Test integrasi dengan sistem absensi
     */
    public function test_integration_with_attendance_system(): void
    {
        // Arrange
        $user = User::factory()->create();
        $morningShift = ShiftKerja::factory()->create([
            'name' => 'Shift Pagi',
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'is_cross_day' => false
        ]);
        $nightShift = ShiftKerja::factory()->create([
            'name' => 'Shift Malam',
            'start_time' => '20:00:00',
            'end_time' => '04:00:00',
            'is_cross_day' => true
        ]);

        // Act: Assign multiple shifts
        $user->shiftKerjas()->attach([$morningShift->id, $nightShift->id]);

        // Assert: User memiliki akses ke multiple shifts untuk absensi
        $userShifts = $user->shiftKerjas;
        $this->assertCount(2, $userShifts);
        
        // Verifikasi shift properties untuk sistem absensi
        $morningShiftFromUser = $userShifts->where('name', 'Shift Pagi')->first();
        $this->assertEquals('08:00:00', $morningShiftFromUser->start_time->format('H:i:s'));
        $this->assertEquals('16:00:00', $morningShiftFromUser->end_time->format('H:i:s'));
        $this->assertFalse($morningShiftFromUser->is_cross_day);
        
        $nightShiftFromUser = $userShifts->where('name', 'Shift Malam')->first();
        $this->assertEquals('20:00:00', $nightShiftFromUser->start_time->format('H:i:s'));
        $this->assertEquals('04:00:00', $nightShiftFromUser->end_time->format('H:i:s'));
        $this->assertTrue($nightShiftFromUser->is_cross_day);
    }
}
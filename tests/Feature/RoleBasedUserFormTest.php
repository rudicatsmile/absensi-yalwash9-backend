<?php

namespace Tests\Feature;

use App\Models\Departemen;
use App\Models\Jabatan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleBasedUserFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_edit_fields_restricted(): void
    {
        $dept = Departemen::create(['name' => 'Dept A']);
        $jab = Jabatan::create(['name' => 'Pegawai']);

        $employee = User::factory()->create([
            'role' => 'employee',
            'departemen_id' => $dept->id,
            'jabatan_id' => $jab->id,
            'name' => 'Emp',
            'email' => 'emp@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($employee);
        $res = $this->get('/admin/users/' . $employee->id . '/edit');
        $res->assertStatus(200);
        $res->assertSee('Email');
        $res->assertSee('Password');
        $res->assertSee('Unit Kerja');
        $res->assertSee('Lokasi');
        $res->assertSee('Shift Kerja');
        $res->assertSee('disabled');
    }

    public function test_manager_edit_shift_and_location_enabled(): void
    {
        $dept = Departemen::create(['name' => 'Dept A']);
        $manager = User::factory()->create([
            'role' => 'manager',
            'departemen_id' => $dept->id,
            'email' => 'mgr1@example.com',
            'password' => bcrypt('password'),
        ]);

        $employee = User::factory()->create([
            'role' => 'employee',
            'departemen_id' => $dept->id,
            'email' => 'emp2@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($manager);
        $res = $this->get('/admin/users/' . $employee->id . '/edit');
        $res->assertStatus(200);
        $res->assertSee('Lokasi');
        $res->assertSee('Shift Kerja');
    }

    public function test_kepala_sub_bagian_edit_shift_and_location_disabled(): void
    {
        $dept = Departemen::create(['name' => 'Dept A']);
        $ksb = User::factory()->create([
            'role' => 'kepala_sub_bagian',
            'departemen_id' => $dept->id,
            'email' => 'ksb1@example.com',
            'password' => bcrypt('password'),
        ]);

        $employee = User::factory()->create([
            'role' => 'employee',
            'departemen_id' => $dept->id,
            'email' => 'emp3@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($ksb);
        $res = $this->get('/admin/users/' . $employee->id . '/edit');
        $res->assertStatus(200);
        $res->assertSee('Lokasi');
        $res->assertSee('Shift Kerja');
        $res->assertSee('disabled');
    }

    public function test_manager_create_shows_limited_role_and_jabatan_options(): void
    {
        $dept = Departemen::create(['name' => 'Dept A']);
        Jabatan::create(['name' => 'Kasubag']);
        Jabatan::create(['name' => 'Pegawai']);
        Jabatan::create(['name' => 'Admin']);

        $manager = User::factory()->create([
            'role' => 'manager',
            'departemen_id' => $dept->id,
            'email' => 'mgr@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($manager);
        $res = $this->get('/admin/users/create');
        $res->assertStatus(200);
        $res->assertSee('Kepala Sub Bagian');
        $res->assertSee('Pegawai');
        $res->assertDontSee('Admin');
        $res->assertSee('Kasubag');
        $res->assertSee('Pegawai');
    }

    public function test_kepala_sub_bagian_cannot_access_create_and_sees_only_employee_option(): void
    {
        $dept = Departemen::create(['name' => 'Dept A']);
        $ksb = User::factory()->create([
            'role' => 'kepala_sub_bagian',
            'departemen_id' => $dept->id,
            'email' => 'ksb@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->actingAs($ksb);
        $res = $this->get('/admin/users/create');
        $res->assertStatus(403);
    }
}

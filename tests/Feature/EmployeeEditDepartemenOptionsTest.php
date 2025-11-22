<?php

namespace Tests\Feature;

use App\Models\Departemen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeEditDepartemenOptionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_edit_only_sees_own_departemen(): void
    {
        $deptA = Departemen::create(['name' => 'Dept A']);
        $deptB = Departemen::create(['name' => 'Dept B']);

        $employee = User::factory()->create([
            'role' => 'employee',
            'departemen_id' => $deptA->id,
            'name' => 'Employee A',
            'email' => 'empA@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($employee);
        $res = $this->get('/admin/users/'.$employee->id.'/edit');
        $res->assertStatus(200);
        $res->assertSee('Dept A');
        $res->assertDontSee('Dept B');
        $res->assertSee('Email');
    }

    public function test_employee_without_departemen_sees_clear_message(): void
    {
        Departemen::create(['name' => 'Dept A']);
        $employee = User::factory()->create([
            'role' => 'employee',
            'departemen_id' => null,
            'name' => 'Employee NoDept',
            'email' => 'empN@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($employee);
        $res = $this->get('/admin/users/'.$employee->id.'/edit');
        $res->assertStatus(200);
        $res->assertSee('Tidak ada departemen terkait');
    }
}


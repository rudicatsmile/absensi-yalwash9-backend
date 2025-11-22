<?php

namespace Tests\Feature;

use App\Models\Departemen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsersListingAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setupData(): array
    {
        $deptA = Departemen::create(['name' => 'Dept A']);
        $deptB = Departemen::create(['name' => 'Dept B']);

        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        $managerA = User::factory()->create([
            'role' => 'manager',
            'departemen_id' => $deptA->id,
            'email' => 'managerA@example.com',
            'password' => bcrypt('password'),
        ]);

        $employeeA1 = User::factory()->create([
            'role' => 'employee',
            'departemen_id' => $deptA->id,
            'name' => 'Employee A1',
            'email' => 'ea1@example.com',
            'password' => bcrypt('password'),
        ]);

        $employeeA2 = User::factory()->create([
            'role' => 'employee',
            'departemen_id' => $deptA->id,
            'name' => 'Employee A2',
            'email' => 'ea2@example.com',
            'password' => bcrypt('password'),
        ]);

        $employeeB1 = User::factory()->create([
            'role' => 'employee',
            'departemen_id' => $deptB->id,
            'name' => 'Employee B1',
            'email' => 'eb1@example.com',
            'password' => bcrypt('password'),
        ]);

        return compact('deptA', 'deptB', 'admin', 'managerA', 'employeeA1', 'employeeA2', 'employeeB1');
    }

    public function test_manager_sees_only_users_in_same_department(): void
    {
        $data = $this->setupData();
        $this->actingAs($data['managerA']);
        $res = $this->get('/admin/users');
        $res->assertStatus(200);
        $res->assertSee('Employee A1');
        $res->assertSee('Employee A2');
        $res->assertDontSee('Employee B1');
    }

    public function test_employee_sees_only_self(): void
    {
        $data = $this->setupData();
        $this->actingAs($data['employeeA1']);
        $res = $this->get('/admin/users');
        $res->assertStatus(200);
        $res->assertSee('Employee A1');
        $res->assertDontSee('Employee A2');
        $res->assertDontSee('Employee B1');
    }

    public function test_admin_unaffected_sees_all(): void
    {
        $data = $this->setupData();
        $this->actingAs($data['admin']);
        $res = $this->get('/admin/users');
        $res->assertStatus(200);
        $res->assertSee('Employee A1');
        $res->assertSee('Employee A2');
        $res->assertSee('Employee B1');
    }
}
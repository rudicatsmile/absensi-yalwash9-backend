<?php

namespace Tests\Feature;

use App\Models\Departemen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateUserFormDepartemenTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_sees_only_own_departemen_in_create_form(): void
    {
        $deptA = Departemen::create(['name' => 'Dept A']);
        $deptB = Departemen::create(['name' => 'Dept B']);

        $manager = User::factory()->create([
            'role' => 'manager',
            'departemen_id' => $deptA->id,
            'email' => 'managerc@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($manager);
        $res = $this->get('/admin/users/create');
        $res->assertStatus(200);
        $res->assertSee('Dept A');
        $res->assertDontSee('Dept B');
        $res->assertSee('disabled');
    }

    public function test_admin_sees_all_departemen_in_create_form(): void
    {
        $deptA = Departemen::create(['name' => 'Dept A']);
        $deptB = Departemen::create(['name' => 'Dept B']);

        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'adminc@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($admin);
        $res = $this->get('/admin/users/create');
        $res->assertStatus(200);
        $res->assertSee('Dept A');
        $res->assertSee('Dept B');
    }
}
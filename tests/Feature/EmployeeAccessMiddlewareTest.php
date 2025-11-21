<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeAccessMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function createEmployee(): User
    {
        return User::factory()->create([
            'role' => 'employee',
            'email' => 'employee@example.com',
            'password' => bcrypt('password'),
        ]);
    }

    public function test_employee_can_access_me_endpoint(): void
    {
        $user = $this->createEmployee();
        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/me');
        $response->assertStatus(200);
    }

    public function test_employee_denied_meetings_index(): void
    {
        $user = $this->createEmployee();
        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/meetings');
        $response->assertStatus(403);
    }

    public function test_employee_can_access_leaves_index(): void
    {
        $user = $this->createEmployee();
        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/leaves');
        $this->assertNotEquals(403, $response->status());
    }

    public function test_employee_cannot_access_other_user_profile(): void
    {
        $user = $this->createEmployee();
        $other = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/api-user/' . $other->id);
        $response->assertStatus(403);
    }
}
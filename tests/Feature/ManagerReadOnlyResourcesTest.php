<?php

namespace Tests\Feature;

use App\Models\Departemen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManagerReadOnlyResourcesTest extends TestCase
{
    use RefreshDatabase;

    protected function createManager(): User
    {
        $dept = Departemen::create(['name' => 'Dept A']);
        return User::factory()->create([
            'role' => 'manager',
            'departemen_id' => $dept->id,
            'email' => 'manager_ro@example.com',
            'password' => bcrypt('password'),
        ]);
    }

    public function test_manager_cannot_access_create_pages(): void
    {
        $user = $this->createManager();
        $this->actingAs($user);

        foreach ([
            '/admin/overtimes/create',
            '/admin/leaves/create',
            '/admin/permits/create',
        ] as $url) {
            $res = $this->get($url);
            $res->assertStatus(403);
        }
    }
}
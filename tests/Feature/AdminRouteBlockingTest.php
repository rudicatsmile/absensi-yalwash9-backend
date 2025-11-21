<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRouteBlockingTest extends TestCase
{
    use RefreshDatabase;

    protected function createEmployee(): User
    {
        return User::factory()->create([
            'role' => 'employee',
            'email' => 'employee2@example.com',
            'password' => bcrypt('password'),
        ]);
    }

    protected function createAdmin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'email' => 'admin2@example.com',
            'password' => bcrypt('password'),
        ]);
    }

    public function test_employee_blocked_from_master_data_routes(): void
    {
        $user = $this->createEmployee();
        $this->actingAs($user);

        foreach ([
            '/admin/work-shifts',
            '/admin/shift-kerjas',
            '/admin/departemens',
            '/admin/jabatans',
        ] as $url) {
            $response = $this->getJson($url);
            $response->assertStatus(403);
            $response->assertJson(['message' => 'Akses ditolak: Menu ini khusus admin']);
        }
    }

    public function test_employee_blocked_from_settings_routes(): void
    {
        $user = $this->createEmployee();
        $this->actingAs($user);

        foreach ([
            '/admin/company-settings',
            '/admin/public-holidays',
            '/admin/weekends',
        ] as $url) {
            $response = $this->getJson($url);
            $response->assertStatus(403);
            $response->assertJson(['message' => 'Akses ditolak: Menu ini khusus admin']);
        }
    }

    public function test_employee_blocked_from_management_rapat_routes(): void
    {
        $user = $this->createEmployee();
        $this->actingAs($user);

        foreach ([
            '/admin/meeting-types',
            '/admin/religious-study-events',
        ] as $url) {
            $response = $this->getJson($url);
            $response->assertStatus(403);
            $response->assertJson(['message' => 'Akses ditolak: Menu ini khusus admin']);
        }
    }

    public function test_admin_can_access_admin_routes(): void
    {
        $user = $this->createAdmin();
        $this->actingAs($user);

        Company::create([
            'name' => 'Test Co',
            'email' => 'test@example.com',
            'address' => 'Alamat',
            'latitude' => -6.2,
            'longitude' => 106.8,
            'radius_km' => 1.0,
            'attendance_type' => 'location',
        ]);

        foreach ([
            '/admin/work-shifts',
            '/admin/shift-kerjas',
            '/admin/departemens',
            '/admin/company-settings',
            '/admin/public-holidays',
            '/admin/weekends',
            '/admin/meeting-types',
            '/admin/religious-study-events',
        ] as $url) {
            $response = $this->get($url);
            $this->assertNotEquals(403, $response->status(), $url.' should be accessible');
        }
    }
}
<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Departemen;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ManagerSubBagianAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function createManager(): User
    {
        $dept = Departemen::create(['name' => 'Dept A']);
        return User::factory()->create([
            'role' => 'manager',
            'departemen_id' => $dept->id,
            'email' => 'manager@example.com',
            'password' => bcrypt('password'),
        ]);
    }

    protected function createKepalaSubBagian(): User
    {
        $dept = Departemen::create(['name' => 'Dept B']);
        return User::factory()->create([
            'role' => 'kepala_sub_bagian',
            'departemen_id' => $dept->id,
            'email' => 'ksbag@example.com',
            'password' => bcrypt('password'),
        ]);
    }

    public function test_manager_sidebar_hides_master_data_and_settings(): void
    {
        $user = $this->createManager();
        $this->actingAs($user);
        $response = $this->get('/admin');
        $response->assertStatus(200);
        $response->assertDontSee('/admin/work-shifts');
        $response->assertDontSee('/admin/company-settings');
    }

    public function test_ksbag_sidebar_hides_master_data_and_settings(): void
    {
        $user = $this->createKepalaSubBagian();
        $this->actingAs($user);
        $response = $this->get('/admin');
        $response->assertStatus(200);
        $response->assertDontSee('/admin/work-shifts');
        $response->assertDontSee('/admin/company-settings');
    }

    public function test_manager_blocked_routes_master_data_settings(): void
    {
        $user = $this->createManager();
        $this->actingAs($user);
        foreach ([
            '/admin/work-shifts',
            '/admin/shift-kerjas',
            '/admin/departemens',
            '/admin/jabatans',
            '/admin/company-settings',
            '/admin/public-holidays',
            '/admin/weekends',
        ] as $url) {
            $res = $this->get($url);
            $res->assertStatus(403);
        }
    }

    public function test_ksbag_blocked_routes_master_data_settings(): void
    {
        $user = $this->createKepalaSubBagian();
        $this->actingAs($user);
        foreach ([
            '/admin/work-shifts',
            '/admin/shift-kerjas',
            '/admin/departemens',
            '/admin/jabatans',
            '/admin/company-settings',
            '/admin/public-holidays',
            '/admin/weekends',
        ] as $url) {
            $res = $this->get($url);
            $res->assertStatus(403);
        }
    }
}
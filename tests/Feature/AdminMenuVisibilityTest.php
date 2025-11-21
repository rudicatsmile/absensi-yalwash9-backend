<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMenuVisibilityTest extends TestCase
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

    protected function createAdmin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);
    }

    public function test_sidebar_hides_admin_groups_for_employee(): void
    {
        $user = $this->createEmployee();
        $this->actingAs($user);

        $response = $this->get('/admin');
        $response->assertStatus(200);

        $response->assertDontSee('Master Data');
        $response->assertDontSee('Settings');
        $response->assertDontSee('Management Rapat');

        $response->assertDontSee('Work Shifts');
        $response->assertDontSee('Shif Kerja');
        $response->assertDontSee('Unit Kerja');
        $response->assertDontSee('Setting Yayasan');
        $response->assertDontSee('Libur bersama');
        $response->assertDontSee('Hari Libur');
        $response->assertDontSee('Tipe Rapat');
        $response->assertDontSee('Event Notifikasi');
    }

    public function test_sidebar_shows_admin_groups_for_admin(): void
    {
        $user = $this->createAdmin();
        $this->actingAs($user);

        $response = $this->get('/admin');
        $response->assertStatus(200);

        $response->assertSee('Master Data');
        $response->assertSee('Settings');
        $response->assertSee('Management Rapat');
    }
}
<?php

namespace Tests\Feature;

use App\Models\JamKerja;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class JamKerjaTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        // Create an admin user to access the API
        $this->user = User::factory()->create(['role' => 'admin']);
    }

    public function test_can_list_jam_kerjas()
    {
        JamKerja::create([
            'name' => 'Morning Shift',
            'start_time' => '08:00',
            'end_time' => '16:00',
            'is_cross_day' => false,
            'grace_period_minutes' => 10,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/jam-kerjas');

        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data'])
            ->assertJsonFragment(['name' => 'Morning Shift']);
    }

    public function test_can_create_jam_kerja()
    {
        $data = [
            'name' => 'Night Shift',
            'start_time' => '23:00',
            'end_time' => '07:00',
            'is_cross_day' => true,
            'grace_period_minutes' => 15,
            'is_active' => true,
            'description' => 'Night shift description',
        ];

        $response = $this->actingAs($this->user)->postJson('/api/jam-kerjas', $data);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'Night Shift']);

        $this->assertDatabaseHas('jam_kerjas', ['name' => 'Night Shift']);
    }

    public function test_can_update_jam_kerja()
    {
        $jamKerja = JamKerja::create([
            'name' => 'Old Name',
            'start_time' => '08:00',
            'end_time' => '16:00',
            'is_cross_day' => false,
            'grace_period_minutes' => 10,
            'is_active' => true,
        ]);

        $data = [
            'name' => 'New Name',
        ];

        $response = $this->actingAs($this->user)->putJson("/api/jam-kerjas/{$jamKerja->id}", $data);

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'New Name']);

        $this->assertDatabaseHas('jam_kerjas', ['id' => $jamKerja->id, 'name' => 'New Name']);
    }

    public function test_can_delete_jam_kerja()
    {
        $jamKerja = JamKerja::create([
            'name' => 'To Delete',
            'start_time' => '08:00',
            'end_time' => '16:00',
            'is_cross_day' => false,
            'grace_period_minutes' => 10,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->deleteJson("/api/jam-kerjas/{$jamKerja->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('jam_kerjas', ['id' => $jamKerja->id]);
    }

    public function test_validation_errors()
    {
        $response = $this->actingAs($this->user)->postJson('/api/jam-kerjas', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'start_time', 'end_time']);
    }

    public function test_unauthenticated_access()
    {
        $response = $this->getJson('/api/jam-kerjas');
        $response->assertStatus(401);
    }
}

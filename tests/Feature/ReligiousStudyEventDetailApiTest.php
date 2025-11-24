<?php

namespace Tests\Feature;

use App\Models\ReligiousStudyEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReligiousStudyEventDetailApiTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsSanctumUser(): User
    {
        $user = User::factory()->create([
            'email' => 'detail@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->actingAs($user, 'sanctum');
        return $user;
    }

    public function test_valid_request_returns_detail(): void
    {
        $this->actingAsSanctumUser();
        $event = ReligiousStudyEvent::create([
            'title' => 'Kajian Detail',
            'event_at' => now()->addDay(),
            'notify_at' => now()->addDay()->subHours(2),
            'location' => 'Aula',
            'theme' => 'Taqwa',
            'speaker' => 'Ustadz A',
            'message' => 'Datang tepat waktu',
            'cancelled' => false,
            'notified' => false,
        ]);

        $res = $this->postJson('/api/religious-study-events/detail', ['id' => $event->id]);
        $res->assertStatus(200);
        $json = $res->json();
        $this->assertEquals('success', $json['status']);
        $this->assertEquals($event->id, $json['data']['id']);
    }

    public function test_missing_id_returns_400(): void
    {
        $this->actingAsSanctumUser();
        $res = $this->postJson('/api/religious-study-events/detail', []);
        $res->assertStatus(400);
    }

    public function test_invalid_id_returns_400(): void
    {
        $this->actingAsSanctumUser();
        $res = $this->postJson('/api/religious-study-events/detail', ['id' => -5]);
        $res->assertStatus(400);
    }

    public function test_not_found_returns_404(): void
    {
        $this->actingAsSanctumUser();
        $res = $this->postJson('/api/religious-study-events/detail', ['id' => 999999]);
        $res->assertStatus(404);
    }
}


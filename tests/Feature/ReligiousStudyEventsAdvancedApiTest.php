<?php

namespace Tests\Feature;

use App\Models\ReligiousStudyEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReligiousStudyEventsAdvancedApiTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsSanctumUser(): User
    {
        $user = User::factory()->create([
            'email' => 'auth@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->actingAs($user, 'sanctum');
        return $user;
    }

    public function test_pagination_and_sorting(): void
    {
        $this->actingAsSanctumUser();
        ReligiousStudyEvent::create(['title' => 'A', 'event_at' => now()->addDays(3), 'cancelled' => 0]);
        ReligiousStudyEvent::create(['title' => 'B', 'event_at' => now()->addDay(), 'cancelled' => 0]);
        ReligiousStudyEvent::create(['title' => 'C', 'event_at' => now()->addDays(2), 'cancelled' => 0]);

        $res = $this->get('/api/religious-study-events?limit=2&sort=asc');
        $res->assertStatus(200);
        $json = $res->json();
        $this->assertCount(2, $json['data']);
        $this->assertEquals('B', $json['data'][0]['title']);
    }

    public function test_filter_date_range_and_search(): void
    {
        $this->actingAsSanctumUser();
        ReligiousStudyEvent::create(['title' => 'Kajian Ramadhan', 'event_at' => now()->addDays(10), 'cancelled' => 0]);
        ReligiousStudyEvent::create(['title' => 'Kajian Syawal', 'event_at' => now()->addDays(20), 'cancelled' => 0]);

        $start = now()->addDays(15)->format('Y-m-d');
        $end = now()->addDays(25)->format('Y-m-d');

        $res = $this->get('/api/religious-study-events?start_date=' . $start . '&end_date=' . $end . '&search=Syawal');
        $res->assertStatus(200);
        $json = $res->json();
        $this->assertCount(1, $json['data']);
        $this->assertEquals('Kajian Syawal', $json['data'][0]['title']);
    }

    public function test_cancelled_boolean_filter(): void
    {
        $this->actingAsSanctumUser();
        ReligiousStudyEvent::create(['title' => 'X', 'event_at' => now()->addDay(), 'cancelled' => 0]);
        ReligiousStudyEvent::create(['title' => 'Y', 'event_at' => now()->addDay(), 'cancelled' => 1]);

        $resFalse = $this->get('/api/religious-study-events?cancelled=false');
        $resFalse->assertStatus(200);
        $this->assertCount(1, $resFalse->json()['data']);

        $resTrue = $this->get('/api/religious-study-events?cancelled=true');
        $resTrue->assertStatus(200);
        $this->assertCount(1, $resTrue->json()['data']);
    }
}

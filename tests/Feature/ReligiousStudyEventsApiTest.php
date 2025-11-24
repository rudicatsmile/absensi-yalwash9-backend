<?php

namespace Tests\Feature;

use App\Models\ReligiousStudyEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReligiousStudyEventsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_not_cancelled_by_default(): void
    {
        ReligiousStudyEvent::create([
            'title' => 'Kajian A',
            'event_at' => now()->addDay(),
            'cancelled' => 0,
        ]);
        ReligiousStudyEvent::create([
            'title' => 'Kajian B',
            'event_at' => now()->addDays(2),
            'cancelled' => 1,
        ]);

        $res = $this->get('/api/religious-study-events');
        $res->assertStatus(200);
        $json = $res->json();
        $this->assertArrayHasKey('data', $json);
        $this->assertCount(1, $json['data']);
        $this->assertEquals('Kajian A', $json['data'][0]['title']);
    }

    public function test_lists_cancelled_when_param_set(): void
    {
        ReligiousStudyEvent::create([
            'title' => 'Kajian C',
            'event_at' => now()->addDay(),
            'cancelled' => 1,
        ]);
        ReligiousStudyEvent::create([
            'title' => 'Kajian D',
            'event_at' => now()->addDays(2),
            'cancelled' => 0,
        ]);

        $res = $this->get('/api/religious-study-events?cancelled=1');
        $res->assertStatus(200);
        $json = $res->json();
        $this->assertArrayHasKey('data', $json);
        $this->assertCount(1, $json['data']);
        $this->assertEquals('Kajian C', $json['data'][0]['title']);
    }
}


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

        $token = self::makeJwt(1);
        $res = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->get('/api/religious-study-events');
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

        $token = self::makeJwt(1);
        $res = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->get('/api/religious-study-events?cancelled=1');
        $res->assertStatus(200);
        $json = $res->json();
        $this->assertArrayHasKey('data', $json);
        $this->assertCount(1, $json['data']);
        $this->assertEquals('Kajian C', $json['data'][0]['title']);
    }
    private static function makeJwt(int $sub): string
    {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $payload = ['sub' => $sub, 'exp' => time() + 3600];
        $headB64 = rtrim(strtr(base64_encode(json_encode($header)), '+/', '-_'), '=');
        $payB64 = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
        $secret = config('app.key');
        $sig = hash_hmac('sha256', $headB64 . '.' . $payB64, $secret, true);
        $sigB64 = rtrim(strtr(base64_encode($sig), '+/', '-_'), '=');
        return $headB64 . '.' . $payB64 . '.' . $sigB64;
    }
}

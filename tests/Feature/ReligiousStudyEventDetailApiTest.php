<?php

namespace Tests\Feature;

use App\Models\ReligiousStudyEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReligiousStudyEventDetailApiTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_valid_request_returns_detail(): void
    {
        $token = self::makeJwt(1);
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

        $res = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->postJson('/api/religious-study-events/detail', ['id' => $event->id]);
        $res->assertStatus(200);
        $json = $res->json();
        $this->assertEquals('success', $json['status']);
        $this->assertEquals($event->id, $json['data']['id']);
    }

    public function test_missing_id_returns_400(): void
    {
        $token = self::makeJwt(1);
        $res = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->postJson('/api/religious-study-events/detail', []);
        $res->assertStatus(400);
    }

    public function test_invalid_id_returns_400(): void
    {
        $token = self::makeJwt(1);
        $res = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->postJson('/api/religious-study-events/detail', ['id' => -5]);
        $res->assertStatus(400);
    }

    public function test_not_found_returns_404(): void
    {
        $token = self::makeJwt(1);
        $res = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->postJson('/api/religious-study-events/detail', ['id' => 999999]);
        $res->assertStatus(404);
    }
}

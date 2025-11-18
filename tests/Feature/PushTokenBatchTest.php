<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PushTokenBatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_batch_upsert_tokens(): void
    {
        $user = User::factory()->create();
        $payload = [
            'tokens' => [
                ['fcm_token' => str_repeat('a', 64), 'device_info' => 'android-1'],
                ['fcm_token' => str_repeat('b', 64), 'device_info' => 'android-2'],
            ],
        ];

        $resp = $this->putJson('/api/users/'.$user->id.'/push-tokens', $payload);
        $resp->assertStatus(200);
        $resp->assertJsonStructure(['message','result' => ['stored','updated','reassigned','invalid']]);

        $this->assertDatabaseHas('user_push_tokens', [
            'user_id' => $user->id,
            'token_hash' => hash('sha256', str_repeat('a', 64)),
        ]);
        $this->assertDatabaseHas('user_push_tokens', [
            'user_id' => $user->id,
            'token_hash' => hash('sha256', str_repeat('b', 64)),
        ]);
    }
}
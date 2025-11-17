<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PushTokenLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_token_inserted_on_successful_login(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secret123'), 'email' => 'u@example.com']);

        $fcm = str_repeat('a', 64);

        $resp = $this->postJson('/api/login', [
            'email' => 'u@example.com',
            'password' => 'secret123',
            'fcm_token' => $fcm,
            'device_info' => 'android-debug',
        ]);

        $resp->assertStatus(200);

        $this->assertDatabaseHas('user_push_tokens', [
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $fcm),
            'device_info' => 'android-debug',
        ]);
    }

    public function test_invalid_token_format_is_ignored(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secret123'), 'email' => 'u2@example.com']);

        $resp = $this->postJson('/api/login', [
            'email' => 'u2@example.com',
            'password' => 'secret123',
            'fcm_token' => "\u0001bad", // non printable
        ]);

        $resp->assertStatus(200);

        $this->assertDatabaseMissing('user_push_tokens', [
            'user_id' => $user->id,
        ]);
    }
}
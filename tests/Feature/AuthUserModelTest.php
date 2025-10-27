<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthUserModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_model_supports_auth_guard()
    {
        $user = User::factory()->create([
            'password' => bcrypt('secret123'),
        ]);

        Auth::login($user);

        $this->assertEquals($user->id, Auth::id());
        $this->assertNotNull(Auth::user());
    }

    public function test_remember_me_does_not_throw_bad_method_call()
    {
        $user = User::factory()->create([
            'password' => bcrypt('secret123'),
        ]);

        // Login dengan remember
        Auth::login($user, true);

        // Simulasikan request berikutnya
        $this->get('/')->assertStatus(200);

        // Guard seharusnya bisa resolve user tanpa BadMethodCallException
        $this->assertEquals($user->id, Auth::id());
    }
}
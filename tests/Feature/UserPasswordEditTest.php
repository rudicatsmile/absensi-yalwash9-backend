<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserPasswordEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_unchanged_when_field_empty(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('OldPass123'),
            'email' => 'user@example.com',
        ]);

        $original = $user->password;

        $user->fill([
            'name' => 'Updated Name',
            // no password key
        ]);
        $user->save();

        $user->refresh();
        $this->assertSame($original, $user->password);
        $this->assertTrue(Hash::check('OldPass123', $user->password));
    }

    public function test_password_updated_when_field_filled(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('OldPass123'),
            'email' => 'user2@example.com',
        ]);

        $user->fill([
            'password' => 'NewPass123',
        ]);
        $user->save();

        $user->refresh();
        $this->assertTrue(Hash::check('NewPass123', $user->password));
        $this->assertFalse(Hash::check('OldPass123', $user->password));
    }
}


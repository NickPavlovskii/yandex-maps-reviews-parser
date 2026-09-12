<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_the_login_user(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('users', [
            'email' => 'admin@example.com',
            'name' => 'Admin',
        ]);

        $this->postJson('/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ])
            ->assertOk()
            ->assertJsonPath('data.email', 'admin@example.com')
            ->assertJsonPath('data.name', 'Admin');

        $this->assertAuthenticated();
    }

    public function test_user_can_login_and_fetch_profile(): void
    {
        $user = User::factory()->create([
            'email' => 'reviewer@example.com',
        ]);

        $this->postJson('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonMissingPath('data.password');

        $this->getJson('/user')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'reviewer@example.com',
        ]);

        $this->postJson('/login', [
            'email' => 'reviewer@example.com',
            'password' => 'wrong-password',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);

        $this->assertGuest();
    }

    public function test_login_is_rate_limited_after_too_many_attempts(): void
    {
        $maxAttempts = (int) config('auth.login.max_attempts');

        User::factory()->create([
            'email' => 'throttled@example.com',
        ]);

        foreach (range(1, $maxAttempts) as $attempt) {
            $this->postJson('/login', [
                'email' => 'throttled@example.com',
                'password' => 'wrong-password',
            ])->assertUnprocessable();
        }

        $this->postJson('/login', [
            'email' => 'throttled@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(429);
    }

    public function test_login_route_is_rate_limited_by_ip(): void
    {
        $routeMaxAttempts = (int) config('auth.login.route_max_attempts');

        config([
            'auth.login.max_attempts' => $routeMaxAttempts + 1,
        ]);

        User::factory()->create([
            'email' => 'flood@example.com',
        ]);

        foreach (range(1, $routeMaxAttempts) as $attempt) {
            $this->postJson('/login', [
                'email' => 'flood@example.com',
                'password' => 'wrong-password',
            ]);
        }

        $this->postJson('/login', [
            'email' => 'flood@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(429);
    }

    public function test_guest_cannot_fetch_profile_or_logout(): void
    {
        $this->getJson('/user')->assertUnauthorized();
        $this->postJson('/logout')->assertUnauthorized();
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/logout')
            ->assertNoContent();

        $this->assertGuest('web');
        $this->assertGuest('sanctum');
    }
}

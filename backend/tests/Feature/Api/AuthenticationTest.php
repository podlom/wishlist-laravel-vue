<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
        ]);

        $response->assertCreated()
            ->assertJsonPath('user.email', 'test@example.com')
            ->assertJsonMissingPath('user.password');

        $user = User::query()
            ->where('email', 'test@example.com')
            ->firstOrFail();

        $this->assertTrue(
            Hash::check('SecurePassword123!', $user->password)
        );

        $this->assertAuthenticatedAs($user, 'web');
    }

    public function test_registration_rejects_duplicate_email(): void
    {
        User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $this->postJson('/api/register', [
            'name' => 'Another User',
            'email' => 'existing@example.com',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_registration_requires_password_confirmation(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'WrongPassword123!',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_user_can_login(): void
    {
        $user = User::factory()->create([
            'email' => 'login@example.com',
            'password' => 'SecurePassword123!',
        ]);

        $this->postJson('/api/login', [
            'email' => 'login@example.com',
            'password' => 'SecurePassword123!',
        ])
            ->assertOk()
            ->assertJsonPath('user.id', $user->id);

        $this->assertAuthenticatedAs($user, 'web');
    }

    public function test_login_rejects_invalid_password(): void
    {
        User::factory()->create([
            'email' => 'login@example.com',
            'password' => 'SecurePassword123!',
        ]);

        $this->postJson('/api/login', [
            'email' => 'login@example.com',
            'password' => 'IncorrectPassword123!',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertGuest('web');
    }

    public function test_guest_cannot_access_current_user(): void
    {
        $this->getJson('/api/user')
            ->assertUnauthorized();
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'web');

        $this->postJson('/api/logout')
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Logged out successfully.'
            );

        $this->assertGuest('web');
    }

    public function test_logout_revokes_access_to_private_api(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'web');

        $this->getJson('/api/wishes')
            ->assertOk();

        $this->postJson('/api/logout')
            ->assertOk();

        $this->getJson('/api/user')
            ->assertUnauthorized();

        $this->getJson('/api/wishes')
            ->assertUnauthorized();
    }

    public function test_guest_cannot_logout(): void
    {
        $this->postJson('/api/logout')
            ->assertUnauthorized();
    }
}

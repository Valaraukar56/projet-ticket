<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'joueur']);
        Role::create(['name' => 'admin']);
    }

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email', 'balance', 'roles', 'casino_unlocked'],
            ])
            ->assertJson([
                'user' => [
                    'name' => 'Test User',
                    'email' => 'test@example.com',
                    'balance' => 100,
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);
    }

    public function test_register_fails_with_existing_email(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'existing@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_register_fails_with_invalid_data(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => '',
            'email' => 'not-an-email',
            'password' => '123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_user_can_login(): void
    {
        $user = User::factory()->create([
            'email' => 'login@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'login@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email', 'balance', 'roles', 'casino_unlocked'],
            ]);

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_wrong_credentials(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('correctpassword'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'user@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Déconnecté']);
    }

    public function test_me_returns_user_when_authenticated(): void
    {
        $user = User::factory()->create([
            'name' => 'Auth User',
            'email' => 'auth@example.com',
            'balance' => 500,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/me');

        $response->assertStatus(200)
            ->assertJson([
                'user' => [
                    'id' => $user->id,
                    'name' => 'Auth User',
                    'email' => 'auth@example.com',
                    'balance' => 500,
                ],
            ]);
    }

    public function test_me_returns_null_when_not_authenticated(): void
    {
        $response = $this->getJson('/api/me');

        $response->assertStatus(200)
            ->assertJson(['user' => null]);
    }

    public function test_user_can_update_balance(): void
    {
        $user = User::factory()->create(['balance' => 100]);

        $response = $this->actingAs($user)
            ->postJson('/api/balance', ['balance' => 250]);

        $response->assertStatus(200)
            ->assertJson(['balance' => 250]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'balance' => 250,
        ]);
    }

    public function test_update_balance_fails_when_not_authenticated(): void
    {
        $response = $this->postJson('/api/balance', ['balance' => 250]);

        $response->assertStatus(401);
    }

    public function test_user_can_delete_account(): void
    {
        $user = User::factory()->create(['name' => 'ToDelete', 'balance' => 100]);

        $response = $this->actingAs($user)
            ->deleteJson('/api/account');

        $response->assertStatus(200)
            ->assertJson([
                'deleted' => true,
            ]);

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }

    public function test_delete_account_fails_when_not_authenticated(): void
    {
        $response = $this->deleteJson('/api/account');

        $response->assertStatus(401);
    }
}

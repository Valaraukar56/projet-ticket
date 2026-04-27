<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TicketTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'joueur']);
        Role::create(['name' => 'admin']);
    }

    public function test_authenticated_user_can_get_open_tickets(): void
    {
        $user = User::factory()->create(['balance' => 100]);

        $response = $this->actingAs($user)
            ->getJson('/api/open-tickets');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => ['id', 'title', 'description', 'status', 'user_id']
            ]);
    }

    public function test_get_open_tickets_fails_when_not_authenticated(): void
    {
        $response = $this->getJson('/api/open-tickets');

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_get_closed_tickets(): void
    {
        $user = User::factory()->create(['balance' => 100]);

        $response = $this->actingAs($user)
            ->getJson('/api/closed-tickets');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => ['id', 'title', 'description', 'status', 'user_id']
            ]);
    }

    public function test_get_closed_tickets_fails_when_not_authenticated(): void
    {
        $response = $this->getJson('/api/closed-tickets');

        $response->assertStatus(401);
    }

    public function test_user_can_get_own_tickets_by_email(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        $response = $this->actingAs($user)
            ->getJson('/api/users/test@example.com/tickets');

        $response->assertStatus(200);
    }

    public function test_get_tickets_by_email_fails_when_not_authenticated(): void
    {
        $response = $this->getJson('/api/users/test@example.com/tickets');

        $response->assertStatus(401);
    }
}
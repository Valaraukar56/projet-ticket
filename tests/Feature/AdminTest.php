<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'joueur']);
        Role::create(['name' => 'admin']);
    }

    public function test_admin_can_get_users_list(): void
    {
        $admin = User::factory()->create(['balance' => 1000]);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/users');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => ['id', 'name', 'email', 'balance']
            ]);
    }

    public function test_joueur_cannot_access_admin_users(): void
    {
        $user = User::factory()->create(['balance' => 100]);
        $user->assignRole('joueur');

        $response = $this->actingAs($user)
            ->getJson('/api/admin/users');

        $response->assertStatus(403);
    }

    public function test_admin_can_get_stats(): void
    {
        $admin = User::factory()->create(['balance' => 1000]);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_users',
                'total_balance',
            ]);
    }

    public function test_joueur_cannot_access_admin_stats(): void
    {
        $user = User::factory()->create(['balance' => 100]);
        $user->assignRole('joueur');

        $response = $this->actingAs($user)
            ->getJson('/api/admin/stats');

        $response->assertStatus(403);
    }

    public function test_admin_can_delete_user(): void
    {
        $admin = User::factory()->create(['balance' => 1000]);
        $admin->assignRole('admin');

        $target = User::factory()->create(['balance' => 100]);

        $response = $this->actingAs($admin)
            ->deleteJson('/api/admin/users/' . $target->id);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('users', [
            'id' => $target->id,
        ]);
    }

    public function test_joueur_cannot_delete_user(): void
    {
        $user = User::factory()->create(['balance' => 100]);
        $user->assignRole('joueur');

        $target = User::factory()->create(['balance' => 100]);

        $response = $this->actingAs($user)
            ->deleteJson('/api/admin/users/' . $target->id);

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_access_admin(): void
    {
        $response = $this->getJson('/api/admin/users');

        $response->assertStatus(401);
    }
}
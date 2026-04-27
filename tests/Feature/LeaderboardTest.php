<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LeaderboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'joueur']);
        Role::create(['name' => 'admin']);
    }

    public function test_anyone_can_get_leaderboard(): void
    {
        $users = User::factory()->count(5)->create(['balance' => 100]);
        foreach ($users as $user) {
            $user->assignRole('joueur');
        }

        $response = $this->getJson('/api/leaderboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'leaderboard' => [
                    '*' => ['id', 'name', 'balance']
                ]
            ]);
    }

    public function test_leaderboard_is_sorted_by_balance_desc(): void
    {
        $riche = User::factory()->create(['name' => 'Riche', 'balance' => 9000]);
        $riche->assignRole('joueur');
        $pauvre = User::factory()->create(['name' => 'Pauvre', 'balance' => 10]);
        $pauvre->assignRole('joueur');

        $response = $this->getJson('/api/leaderboard');

        $data = $response->json('leaderboard');

        $this->assertGreaterThanOrEqual(
            $data[1]['balance'],
            $data[0]['balance']
        );
    }

    public function test_leaderboard_returns_max_50_users(): void
    {
        $users = User::factory()->count(60)->create(['balance' => 100]);
        foreach ($users as $user) {
            $user->assignRole('joueur');
        }

        $response = $this->getJson('/api/leaderboard');

        $response->assertStatus(200);
        $this->assertLessThanOrEqual(50, count($response->json('leaderboard')));
    }
}
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
        User::factory()->count(5)->create();

        $response = $this->getJson('/api/leaderboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => ['id', 'name', 'balance']
            ]);
    }

    public function test_leaderboard_is_sorted_by_balance_desc(): void
    {
        User::factory()->create(['name' => 'Riche', 'balance' => 9000]);
        User::factory()->create(['name' => 'Pauvre', 'balance' => 10]);

        $response = $this->getJson('/api/leaderboard');

        $data = $response->json();

        $this->assertGreaterThanOrEqual(
            $data[1]['balance'],
            $data[0]['balance']
        );
    }

    public function test_leaderboard_returns_max_50_users(): void
    {
        User::factory()->count(60)->create();

        $response = $this->getJson('/api/leaderboard');

        $response->assertStatus(200);
        $this->assertLessThanOrEqual(50, count($response->json()));
    }
}
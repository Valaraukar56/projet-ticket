<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'joueur']);
        Role::create(['name' => 'admin']);
    }

    private function makeTicket(array $attrs = []): Ticket
    {
        return Ticket::create(array_merge([
            'name'            => 'Ticket Test',
            'price'           => 100,
            'loss_percentage' => 50,
            'potential_gain'  => 300,
            'status'          => 'purchased',
        ], $attrs));
    }

    public function test_purchase_debits_balance_and_creates_payment(): void
    {
        $user   = User::factory()->create(['balance' => 500]);
        $ticket = $this->makeTicket(['price' => 100]);

        $response = $this->actingAs($user)
            ->postJson('/api/payments/purchase', ['ticket_id' => $ticket->id]);

        $response->assertStatus(201)
            ->assertJson([
                'balance'          => 400,
                'payment' => [
                    'type'      => 'purchase',
                    'amount'    => 100,
                    'user_id'   => $user->id,
                    'ticket_id' => $ticket->id,
                ],
            ]);

        $this->assertDatabaseHas('payments', [
            'user_id'   => $user->id,
            'ticket_id' => $ticket->id,
            'type'      => 'purchase',
            'amount'    => 100,
        ]);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'balance' => 400]);
    }

    public function test_purchase_fails_when_balance_insufficient(): void
    {
        $user   = User::factory()->create(['balance' => 50]);
        $ticket = $this->makeTicket(['price' => 100]);

        $response = $this->actingAs($user)
            ->postJson('/api/payments/purchase', ['ticket_id' => $ticket->id]);

        $response->assertStatus(422)
            ->assertJson(['error' => 'Solde insuffisant']);

        $this->assertDatabaseMissing('payments', ['user_id' => $user->id]);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'balance' => 50]);
    }

    public function test_purchase_requires_authentication(): void
    {
        $ticket = $this->makeTicket();

        $this->postJson('/api/payments/purchase', ['ticket_id' => $ticket->id])
            ->assertStatus(401);
    }

    public function test_purchase_fails_with_invalid_ticket_id(): void
    {
        $user = User::factory()->create(['balance' => 500]);

        $this->actingAs($user)
            ->postJson('/api/payments/purchase', ['ticket_id' => 9999])
            ->assertStatus(422);
    }

    public function test_win_credits_balance_and_creates_payment(): void
    {
        $user   = User::factory()->create(['balance' => 200]);
        $ticket = $this->makeTicket([
            'user_id'        => $user->id,
            'status'         => 'completed',
            'result'         => 'win',
            'potential_gain' => 300,
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/payments/win', ['ticket_id' => $ticket->id]);

        $response->assertStatus(201)
            ->assertJson([
                'balance'          => 500,
                'payment' => [
                    'type'      => 'win',
                    'amount'    => 300,
                    'user_id'   => $user->id,
                    'ticket_id' => $ticket->id,
                ],
            ]);

        $this->assertDatabaseHas('payments', [
            'user_id'   => $user->id,
            'ticket_id' => $ticket->id,
            'type'      => 'win',
            'amount'    => 300,
        ]);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'balance' => 500]);
    }

    public function test_win_fails_if_ticket_belongs_to_another_user(): void
    {
        $user  = User::factory()->create(['balance' => 200]);
        $other = User::factory()->create(['balance' => 0]);
        $ticket = $this->makeTicket([
            'user_id' => $other->id,
            'status'  => 'completed',
            'result'  => 'win',
        ]);

        $this->actingAs($user)
            ->postJson('/api/payments/win', ['ticket_id' => $ticket->id])
            ->assertStatus(404);
    }

    public function test_win_fails_if_ticket_result_is_lose(): void
    {
        $user   = User::factory()->create(['balance' => 200]);
        $ticket = $this->makeTicket([
            'user_id' => $user->id,
            'status'  => 'completed',
            'result'  => 'lose',
        ]);

        $this->actingAs($user)
            ->postJson('/api/payments/win', ['ticket_id' => $ticket->id])
            ->assertStatus(404);
    }

    public function test_win_requires_authentication(): void
    {
        $ticket = $this->makeTicket();

        $this->postJson('/api/payments/win', ['ticket_id' => $ticket->id])
            ->assertStatus(401);
    }

    public function test_history_returns_user_payments(): void
    {
        $user = User::factory()->create(['balance' => 500]);
        $ticket = $this->makeTicket(['user_id' => $user->id]);

        Payment::create([
            'user_id'   => $user->id,
            'ticket_id' => $ticket->id,
            'amount'    => 100,
            'type'      => 'purchase',
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/payments');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'payments')
            ->assertJsonPath('payments.0.type', 'purchase');
    }

    public function test_history_does_not_return_other_users_payments(): void
    {
        $user  = User::factory()->create(['balance' => 500]);
        $other = User::factory()->create(['balance' => 500]);
        $ticket = $this->makeTicket();

        Payment::create([
            'user_id'   => $other->id,
            'ticket_id' => $ticket->id,
            'amount'    => 100,
            'type'      => 'purchase',
        ]);

        $response = $this->actingAs($user)->getJson('/api/payments');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'payments');
    }

    public function test_history_requires_authentication(): void
    {
        $this->getJson('/api/payments')->assertStatus(401);
    }
}

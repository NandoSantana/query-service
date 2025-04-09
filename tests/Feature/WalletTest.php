<?php

namespace Tests\Feature\Query;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;

class WalletTest extends TestCase
{
    use RefreshDatabase;

    public function test_consulta_saldo()
    {
        $user = User::factory()->create();
        Wallet::factory()->create([
            'user_id' => $user->id,
            'balance' => 200.00,
        ]);

        $response = $this->getJson("http://localhost/api/wallet/{$user->id}/balance");

        $response->assertStatus(200)
                 ->assertJson([
                     'balance' => 200.00,
                 ]);
    }

    public function test_consulta_transacoes()
    {
        $user = User::factory()->create();
        Transaction::factory()->count(3)->create([
            'payer_id' => $user->id,
        ]);

        $response = $this->getJson("http://localhost/api/wallet/{$user->id}/transactions");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'transactions' => [
                         '*' => ['id', 'payer_id', 'payee_id', 'amount', 'created_at']
                     ]
                 ]);
    }
}
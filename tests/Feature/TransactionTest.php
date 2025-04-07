<?php

/**
 * A basic feature test example.
 */
namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        // Seed com usuários para teste
        $this->artisan('db:seed');
     
        $user = User::all();
        if(!$user)
            $this->artisan('db:seed', ['--class' => 'DatabaseSeeder']);
   

    }

    /** @test */
    public function usuario_comum_pode_fazer_deposito()
    {
        $user = User::where('type', 'common')->get()->first();
        // dump($user->id);
        $response = $this->postJson('http://localhost/api/command/deposit', [
            'user_id' => $user->id,
            'amount' => 100.00
        ]);
 
        $this->assertEquals(200, $response->getStatusCode() );
        $this->assertTrue($user->fresh()->wallet->balance >= 100 );
    }

    /** @test */
    public function usuario_comum_pode_transferir_para_lojista()
    {
        Http::fake([
            'https://util.devi.tools/api/v2/authorize' => Http::response(['message' => 'Autorizado'], 200),
            'https://util.devi.tools/api/v1/notify' => Http::response(['message' => 'Notificação enviada'], 200),
        ]);

        $payer = User::where('type', 'common')->get()->first();
        $payee = User::where('type', 'merchant')->get()->first();

        Wallet::create(['user_id' => $payer->id, 'balance' => 200.00]);
        Wallet::create(['user_id' => $payee->id, 'balance' => 0.00]);

        $response = $this->postJson('http://localhost/api/command/transfer', [
            'payer_id' => $payer->id,
            'payee_id' => $payee->id,
            'amount' => 50.00
        ]);

        $this->assertEquals(200, $response->getStatusCode() );
        $this->assertTrue($payer->fresh()->wallet->balance >= 100 );
        $this->assertTrue($payee->fresh()->wallet->balance >= 50.00 );
        
    }

    /** @test */
    public function lojista_nao_pode_transferir()
    {
        $merchant = User::where('type', 'merchant')->first();
        $user = User::where('type', 'common')->first();

        Wallet::create(['user_id' => $merchant->id, 'balance' => 200.00]);
        Wallet::create(['user_id' => $user->id, 'balance' => 0.00]);

        $response = $this->postJson('http://localhost/api/command/transfer', [
            'payer_id' => $merchant->id,
            'payee_id' => $user->id,
            'amount' => 50.00
        ]);

        $response->assertStatus(500);
        // Lojistas não podem realizar transferências
        
        $this->assertStringContainsString('Lojistas não podem realizar transferências', json_decode($response->getContent())->message);
    }
}

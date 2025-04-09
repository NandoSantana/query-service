<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Connection\AMQPStreamConnection;

// class ConsumeTransactionEvents extends Command
// {
//     protected $signature = 'consume:transactions';
//     protected $description = 'Consome eventos de transações do RabbitMQ';

//     public function handle()
//     {
//         $connection = new AMQPStreamConnection('rabbitmq', 5672, 'guest', 'guest');
//         $channel = $connection->channel();

        
//         $channel->queue_declare('users', false, true, false, false);       
//         $channel->basic_consume('users', '', false, true, false, false, function ($msgUser) {
//             echo "[users] Mensagem recebida\n";
//             $msgDecode = json_decode($msgUser->body, true);
//             // lógica para atualizar/replicar usuários

//             // Aqui você atualiza a base de leitura (query)
//             \Log::info('Evento recebido [users]', $msgDecode);
            
//             $wll = Wallet::all();
            
//             if(!$wll){
//                 // Transaction::truncate();
//                 // Wallet::truncate();
//                 // User::truncate();
//                 User::updateOrCreate($msgDecode);
//             }
            
//         });
//         $channel->basic_consume('wallets', '', false, true, false, false, function ($walletsMsg) {
//             echo "[Wallets] Mensagem recebida\n";
//             $wallets = json_decode($walletsMsg->body, true);
//             // lógica para atualizar/replicar usuários

//                // Aqui você atualiza a base de leitura (query)
//                \Log::info('Evento recebido [wallets]', $wallets);
//                // Exemplo:
//                 // $walletMoney = Wallet::all();
               
//                 // if(!$walletMoney)
//                     // Wallet::truncate();
//                    // Wallet::updateOrCreate($wallets);
//         });
        
        
        
//         $channel->queue_declare('transactions', false, true, false, false);

//         $callback = function ($msg) {
//             echo "[transaction] Mensagem recebida\n";
//             $data = json_decode($msg->body, true);

//             // Aqui você atualiza a base de leitura (query)
//             \Log::info('Evento recebido [transactions]', $data);
//             // Exemplo:
//             // $transactions = Transaction::all();
//             // $transactions->truncate();
//             // if(!$transactions)
//             // Transaction::truncate();
//             // Transaction::updateOrCreate($data);

//         };

//         $channel->basic_consume('transactions', '', false, true, false, false, $callback);




//         while ($channel->is_consuming()) {
//             $channel->wait();
//         }
//     }
// }

// namespace App\Services;

// use PhpAmqpLib\Connection\AMQPStreamConnection;
// use PhpAmqpLib\Message\AMQPMessage;
// use App\Models\Transaction;
// use App\Models\Wallet;

class ConsumeTransactionEvents extends Command
{
    protected $signature = 'consume:transactions';
    protected $description = 'Consome eventos de transações do RabbitMQ';

    public function handle()
    {
        $connection = new AMQPStreamConnection('rabbitmq', 5672, 'guest', 'guest');
        $channel = $connection->channel();

        $queues = ['users','wallets', 'transactions'];

        foreach ($queues as $queue) {
            $channel->queue_declare($queue, false, true, false, false);

            $channel->basic_consume($queue, '', false, true, false, false, function (AMQPMessage $message) use ($queue) {
                $data = json_decode($message->getBody(), true);
                if ($queue === 'users') {
                    // dd($data);
                        User::updateOrCreate(
                            ['id' => $data['id']],
                            [
                                // 'id' => $data['id'],
                                'name' => $data['name'],
                                'email' => $data['email'],
                                'cpf' => $data['cpf'] ?? null,
                                'password' => $data['password'] ?? null,
                                'cnpj' => $data['cnpj'] ?? null,
                                'type' => $data['type']
                            ]
                        );
                      
                    }

                if ($queue === 'wallets') {
                    // Verifica se o usuário existe antes de criar a carteira
                
                    $userExists = User::where('id', $data['user_id'])->exists();
           
                    if ($userExists) {
                        Wallet::updateOrCreate(
                            ['id' => $data['id']],
                            [
                                'user_id' => $data['user_id'],
                                'balance' => $data['balance']
                            ]
                        );
                    } else {
                        // Loga ou ignora, dependendo do caso
                        Log::warning("Usuário com ID {$data['user_id']} não encontrado. Ignorando criação da wallet.");
                    }
                }

                if ($queue === 'transactions') {
                    $payerExists = User::where('id', $data['payer_id'])->exists();
                    $payeeExists = User::where('id', $data['payee_id'])->exists();

                    if ($payerExists && $payeeExists) {
                        
                        Transaction::updateOrCreate(
                            ['id' => $data['id']],
                            [
                                'payer_id' => $data['payer_id'],
                                'payee_id' => $data['payee_id'],
                                'amount' => $data['amount'],
                            ]
                        );
                        // Transaction::create([
                        //     'payer_id' => $data['payer_id'],
                        //     'payee_id' => $data['payee_id'],
                        //     'amount' => $data['amount'],
                        // ]);
                    } else {
                        Log::warning("Transação ignorada: payer_id={$data['payer_id']} ou payee_id={$data['payee_id']} não existe.");
                    }
                }

                
            });
        }

        while ($channel->is_consuming()) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();
    }
}


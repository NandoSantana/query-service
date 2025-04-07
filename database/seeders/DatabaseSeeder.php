<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        //     'cpf' => '099.999.999-99'
        // ]);
        $common = User::create([
            'name' => 'Usuário Comum',
            'cpf' => '12345678901',
            'email' => 'comum@example.com',
            'password' => Hash::make('123456'),
            'type' => 'common',
        ]);
    
        $merchant = User::create([
            'name' => 'Lojista',
            'cpf' => '98765432100',
            'email' => 'lojista@example.com',
            'password' => Hash::make('123456'),
            'type' => 'merchant',
        ]);

        Wallet::create(['user_id' => $common->id, 'balance' => 100000]);
        Wallet::create(['user_id' => $merchant->id, 'balance' => 1900]);

        // dd(User::all(), Wallet::all());
    }
}

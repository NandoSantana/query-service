<?php 

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\Query\WalletController;


Route::get('/wallet/{user_id}/balance', [WalletController::class, 'getBalance']);
Route::get('/wallet/{user_id}/transactions', [WalletController::class, 'getTransactions']);
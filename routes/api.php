<?php 

use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;


Route::get('/wallet/{user_id}/balance', [WalletController::class, 'getBalance']);
Route::get('/wallet/{user_id}/transactions', [WalletController::class, 'getTransactions']);
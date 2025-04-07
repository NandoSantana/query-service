<?php

namespace App\Http\Controllers\Query;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class WalletController extends Controller
{
    public function getBalance($user_id): JsonResponse
    {
        $user = User::with('wallet')->findOrFail($user_id);

        return response()->json([
            'balance' => $user->wallet->balance ?? 0,
        ]);
    }

    public function getTransactions($user_id): JsonResponse
    {
        $user = User::findOrFail($user_id);

        // supondo que o model Transaction exista
        $transactions = $user->transactions()
            ->latest()
            ->take(50)
            ->get();

        return response()->json([
            'transactions' => $transactions,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Wallet;
use Illuminate\Http\Request;

class DashboardController
{
    public function __invoke(Request $request)
    {

        $transactions = $request->user()->walletTransactions();

        $balance = $request->user()->wallet?->balance ?? 0;

        $recurringTransfers = $request->user()->recurringTransfers()->orderByDesc('id')->get();

        return view('dashboard', compact('transactions', 'balance', 'recurringTransfers'));
    }
}

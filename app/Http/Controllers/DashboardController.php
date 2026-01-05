<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Wallet;
use Illuminate\Http\Request;

class DashboardController
{
    public function __invoke(Request $request)
    {
        if (!  $request->user()->wallet) {
            $wallet = new Wallet();
            $wallet->user()->associate($request->user());
            $wallet->save();
        }

        $transactions = $request->user()->wallet?->transactions()->with('transfer')->orderByDesc('id')->get();

        $balance = $request->user()->wallet?->balance || 0;

        return view('dashboard', compact('transactions', 'balance'));
    }
}

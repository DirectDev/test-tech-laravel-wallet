<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController
{
    public function __invoke(Request $request)
    {
        $wallet = $request->user()->wallet;
        $transactions = $request->user()->wallet?->transactions()->with('transfer')->orderByDesc('id')->get();
        if (! $wallet) {
            $transactions = [];
        }

        $balance = $request->user()->wallet?->balance || 0;

        return view('dashboard', compact('transactions', 'balance'));
    }
}

<?php

declare(strict_types=1);

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SendMoneyController;
use App\Http\Controllers\RecurringTransfertController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::post('/send-money', [SendMoneyController::class, '__invoke'])->name('send-money');
    Route::post('/recurring-transfer', [RecurringTransfertController::class, '__invoke'])->name('recurring-transfer');
});

require __DIR__.'/auth.php';

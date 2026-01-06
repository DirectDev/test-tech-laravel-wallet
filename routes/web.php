<?php

declare(strict_types=1);

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeleteRecurringTransferController;
use App\Http\Controllers\SendMoneyController;
use App\Http\Controllers\RecurringTransfertController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::post('/send-money', [SendMoneyController::class, '__invoke'])->name('send-money');
    Route::post('/recurring-transfer', [RecurringTransfertController::class, '__invoke'])->name('recurring-transfer');
    Route::delete('/recurring-transfer/{id}', [DeleteRecurringTransferController::class, '__invoke'])->name('recurring-transfer.delete');
});

require __DIR__.'/auth.php';

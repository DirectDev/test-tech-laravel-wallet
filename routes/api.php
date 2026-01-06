<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\CreateRecurringTransferController;
use App\Http\Controllers\Api\V1\DeleteRecurringTransferController;
use App\Http\Controllers\Api\V1\ListRecurringTransfersController;
use App\Http\Controllers\Api\V1\LoginController;
use App\Http\Controllers\Api\V1\SendMoneyController;
use App\Http\Controllers\Api\V1\ShowRecurringTransferController;
use Illuminate\Support\Facades\Route;

Route::post('/v1/login', LoginController::class)->middleware(['guest:sanctum', 'throttle:api.login']);

Route::middleware(['auth:sanctum', 'throttle:api'])->prefix('v1')->group(function () {
    Route::get('/account', AccountController::class);
    Route::post('/wallet/send-money', SendMoneyController::class);
    Route::get('/recurring-transfers', ListRecurringTransfersController::class);
    Route::post('/recurring-transfers', CreateRecurringTransferController::class);
    Route::get('/recurring-transfers/{id}', ShowRecurringTransferController::class);
    Route::delete('/recurring-transfers/{id}', DeleteRecurringTransferController::class);
});

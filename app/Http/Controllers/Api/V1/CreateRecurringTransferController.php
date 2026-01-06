<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\RegisterRecurringTransfer;
use App\Http\Requests\Api\V1\CreateRecurringTransferRequest;
use App\Http\Resources\RecurringTransferResource;
use Illuminate\Http\JsonResponse;

class CreateRecurringTransferController
{
    public function __invoke(
        CreateRecurringTransferRequest $request,
        RegisterRecurringTransfer $registerRecurringTransfer
    ): JsonResponse {
        $recurringTransfer = $registerRecurringTransfer->execute(
            user: $request->user(),
            startDate: $request->input('start_date'),
            stopDate: $request->input('stop_date'),
            frequency: $request->integer('frequency'),
            amount: $request->float('amount'),
            reason: $request->string('reason')->toString()
        );

        return response()->json(
            new RecurringTransferResource($recurringTransfer),
            201
        );
    }
}


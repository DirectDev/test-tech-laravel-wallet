<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\RecurringTransferResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ListRecurringTransfersController
{
    public function __invoke(Request $request): AnonymousResourceCollection
    {
        $recurringTransfers = $request->user()
            ->recurringTransfers()
            ->orderByDesc('id')
            ->get();

        RecurringTransferResource::withoutWrapping();

        return RecurringTransferResource::collection($recurringTransfers);
    }
}


<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\RecurringTransferResource;
use App\Models\RecurringTransfer;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShowRecurringTransferController
{
    public function __invoke(Request $request, string $id): RecurringTransferResource|JsonResponse
    {
        // Validate that $id is a valid integer
        if (! ctype_digit($id)) {
            return response()->json([
                'message' => 'Recurring transfer not found or unauthorized.',
            ], 404);
        }

        try {
            $transfer = RecurringTransfer::query()
                ->where('user_id', $request->user()->id)
                ->where('id', (int) $id)
                ->firstOrFail();

            return new RecurringTransferResource($transfer);
        } catch (ModelNotFoundException|AuthorizationException) {
            return response()->json([
                'message' => 'Recurring transfer not found or unauthorized.',
            ], 404);
        }
    }
}


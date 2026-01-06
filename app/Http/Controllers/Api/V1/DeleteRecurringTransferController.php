<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\DeleteRecurringTransfer;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DeleteRecurringTransferController
{
    public function __invoke(Request $request, DeleteRecurringTransfer $deleteRecurringTransfer, string $id): Response|JsonResponse
    {
        // Validate that $id is a valid integer
        if (! ctype_digit($id)) {
            return response()->json([
                'message' => 'Recurring transfer not found or unauthorized.',
            ], 404);
        }

        try {
            $deleteRecurringTransfer->execute($request->user(), (int) $id);
        } catch (ModelNotFoundException|AuthorizationException) {
            return response()->json([
                'message' => 'Recurring transfer not found or unauthorized.',
            ], 404);
        }

        return response()->noContent();
    }
}


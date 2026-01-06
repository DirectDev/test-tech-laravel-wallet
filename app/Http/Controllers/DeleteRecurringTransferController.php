<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\DeleteRecurringTransfer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DeleteRecurringTransferController
{
    public function __invoke(Request $request, DeleteRecurringTransfer $deleteRecurringTransfer, int $id): RedirectResponse
    {
        try {
            $deleteRecurringTransfer->execute(
                user: $request->user(),
                recurringTransferId: $id
            );

            return redirect()->route('dashboard')->with('recurring-transfer-delete-status', 'success');
        } catch (\Exception $e) {
            return redirect()->route('dashboard')->with('recurring-transfer-delete-status', 'error');
        }
    }
}


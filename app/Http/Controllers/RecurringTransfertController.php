<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\RegisterRecurringTransfer;
use App\Http\Requests\RecurringTranferRequest;
use App\Jobs\RecurringTransferJob;
use Illuminate\Http\RedirectResponse;

class RecurringTransfertController
{
    public function __invoke(RecurringTranferRequest $request, RegisterRecurringTransfer $registerRecurringTransfer): RedirectResponse
    {
        try {
            $transfer = $registerRecurringTransfer->execute(
                user: $request->user(),
                startDate: $request->input('start_date'),
                stopDate: $request->input('stop_date'),
                frequency: (int) $request->input('frequency'),
                amount: (float) $request->input('amount'),
                reason: $request->input('reason')
            );

            if ($transfer->id) {
                RecurringTransferJob::dispatch($transfer->id);
            }

            return redirect()->route('dashboard')->with('recurring-transfer-status', 'success');
        } catch (\Exception $e) {
            return redirect()->route('dashboard')->with('recurring-transfer-status', 'error');
        }
    }
}

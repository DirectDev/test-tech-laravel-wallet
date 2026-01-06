<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\RecurringTransfer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Queue\Queueable;

class RecurringTransferJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $activeTransfers = $this->getActiveRecurringTransfers();

        // Process each active recurring transfer
        foreach ($activeTransfers as $transfer) {

            if ($this->daysSinceLastTransfert($transfer) <= $transfer->frequency) {
                continue;
            }

            if ($transfer->user->wallet?->balance < $transfer->amount) {
                continue;
            }

            $this->performWalletTransfer($transfer);

        }
    }

    public function performWalletTransfer(RecurringTransfer $transfer): bool
    {
        return true;
    }

    public function daysSinceLastTransfert(RecurringTransfer $transfer): int
    {
        return 5; 
    }

    /**
     * Get all active recurring transfers.
     *
     * Active means:
     * - start_date is today or in the past
     * - stop_date is null or in the future
     * - not soft deleted
     *
     * @return Collection<int, RecurringTransfer>
     */
    public function getActiveRecurringTransfers(): Collection
    {
        return RecurringTransfer::query()
            ->where('start_date', '<=', now())
            ->where(function ($query) {
                $query->whereNull('stop_date')
                    ->orWhere('stop_date', '>', now());
            })
            ->get();
    }
}

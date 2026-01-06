<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\RecurringTransfer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Queue\Queueable;


/** 
 * C'est mieux de faire 2 jobs séparés, un pour tous les transferts une fois par jour
 * et un pour les transfert unique avec $id précisé
*/

class RecurringTransferJob implements ShouldQueue
{
    use Queueable;

    public ?int $id = null;

    /**
     * Create a new job instance.
     */
    public function __construct(?int $id = null)
    {
        $this->id = $id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $activeTransfers = $this->getActiveRecurringTransfers($this->id);

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
     * @param int|null $id Optional ID to filter a specific recurring transfer
     * @return Collection<int, RecurringTransfer>
     */
    public function getActiveRecurringTransfers(?int $id = null): Collection
    {
        $query = RecurringTransfer::query()
            ->where('start_date', '<=', now())
            ->where(function ($query) {
                $query->whereNull('stop_date')
                    ->orWhere('stop_date', '>', now());
            });

        if ($id !== null) {
            $query->where('id', $id);
        }

        return $query->get();
    }
}

<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\RecurringTransferJob;
use Illuminate\Console\Command;

class ProcessRecurringTransfers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recurring-transfers:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process all active recurring transfers';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->output) {
            $this->info('Processing active recurring transfers...');
        }

        // Dispatch the job to process all active recurring transfers
        RecurringTransferJob::dispatch();

        if ($this->output) {
            $this->info('Recurring transfer job dispatched successfully.');
        }

        return self::SUCCESS;
    }
}

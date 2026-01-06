<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\RecurringTransfer;
use App\Models\User;

readonly class RegisterRecurringTransfer
{
    public function __construct() {}

    public function execute(
        User $user,
        string $startDate,
        ?string $stopDate,
        int $frequency,
        float $amount,
        ?string $reason
    ): RecurringTransfer {
        return RecurringTransfer::create([
            'user_id' => $user->id,
            'start_date' => $startDate,
            'stop_date' => $stopDate,
            'frequency' => $frequency,
            'amount' => $amount,
            'reason' => $reason,
        ]);
    }
}

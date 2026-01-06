<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\RecurringTransfer;
use App\Models\User;

readonly class DeleteRecurringTransfer
{
    public function __construct() {}

    public function execute(User $user, int $recurringTransferId): bool
    {
        $recurringTransfer = RecurringTransfer::where('id', $recurringTransferId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        return $recurringTransfer->delete();
    }
}


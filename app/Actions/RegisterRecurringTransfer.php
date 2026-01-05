<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\WalletTransactionType;
use App\Exceptions\InsufficientBalance;
use App\Models\User;
use App\Models\WalletTransfer;
use Illuminate\Support\Facades\DB;

readonly class RegisterRecurringTransfer
{
    public function __construct() {}

    public function execute(User $sender, User $recipient, int $amount, string $reason): WalletTransfer
    {

    }
}

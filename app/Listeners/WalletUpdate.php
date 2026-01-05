<?php

namespace App\Listeners;

use App\Notifications\LowBalance;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class WalletUpdate
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        $wallet = $event->wallet();
        $user = $event->user;
        $user->notify(new LowBalance($wallet));
    }
}

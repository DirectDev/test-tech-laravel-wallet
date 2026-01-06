<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\RecurringTransfer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RecurringTransferInsufficientBalance extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public RecurringTransfer $recurringTransfer
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $currentBalance = $notifiable->wallet?->balance ?? 0;
        $requiredAmount = $this->recurringTransfer->amount;

        return (new MailMessage)
            ->subject('Recurring Transfer Failed - Insufficient Balance')
            ->line('Your scheduled recurring transfer could not be processed due to insufficient balance.')
            ->line("Current balance: €{$currentBalance}")
            ->line("Required amount: €{$requiredAmount}")
            ->line("Reason: {$this->recurringTransfer->reason}")
            ->action('Add Funds', url('/dashboard'))
            ->line('Please add funds to your wallet to resume automatic transfers.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'recurring_transfer_id' => $this->recurringTransfer->id,
            'required_amount' => $this->recurringTransfer->amount,
            'current_balance' => $notifiable->wallet?->balance ?? 0,
            'reason' => $this->recurringTransfer->reason,
        ];
    }
}


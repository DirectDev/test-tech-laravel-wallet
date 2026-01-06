<?php

declare(strict_types=1);

use App\Models\RecurringTransfer;
use App\Models\User;
use App\Models\Wallet;
use App\Notifications\RecurringTransferInsufficientBalance;
use Illuminate\Notifications\Messages\MailMessage;

test('notification can be instantiated with recurring transfer', function () {
    $user = User::factory()->create();
    $transfer = RecurringTransfer::factory()->for($user)->create();

    $notification = new RecurringTransferInsufficientBalance($transfer);

    expect($notification)->toBeInstanceOf(RecurringTransferInsufficientBalance::class);
    expect($notification->recurringTransfer)->toBe($transfer);
});

test('notification uses mail channel', function () {
    $user = User::factory()->create();
    $transfer = RecurringTransfer::factory()->for($user)->create();
    $notification = new RecurringTransferInsufficientBalance($transfer);

    $channels = $notification->via($user);

    expect($channels)->toBeArray();
    expect($channels)->toContain('mail');
});

test('notification generates correct mail message', function () {
    $user = User::factory()->create();
    Wallet::factory()->for($user)->create(['balance' => 50]);
    
    $transfer = RecurringTransfer::factory()->for($user)->create([
        'amount' => 100.00,
        'reason' => 'Monthly subscription',
    ]);

    $notification = new RecurringTransferInsufficientBalance($transfer);
    $mail = $notification->toMail($user);

    expect($mail)->toBeInstanceOf(MailMessage::class);
    expect($mail->subject)->toBe('Recurring Transfer Failed - Insufficient Balance');
});

test('notification mail includes current balance', function () {
    $user = User::factory()->create();
    Wallet::factory()->for($user)->create(['balance' => 50]);
    
    $transfer = RecurringTransfer::factory()->for($user)->create([
        'amount' => 100.00,
    ]);

    $notification = new RecurringTransferInsufficientBalance($transfer);
    $mail = $notification->toMail($user);

    expect($mail->introLines)->toContain('Current balance: €50');
});

test('notification mail includes required amount', function () {
    $user = User::factory()->create();
    Wallet::factory()->for($user)->create(['balance' => 50]);
    
    $transfer = RecurringTransfer::factory()->for($user)->create([
        'amount' => 100.00,
    ]);

    $notification = new RecurringTransferInsufficientBalance($transfer);
    $mail = $notification->toMail($user);

    expect($mail->introLines)->toContain('Required amount: €100');
});

test('notification mail includes reason', function () {
    $user = User::factory()->create();
    Wallet::factory()->for($user)->create(['balance' => 50]);
    
    $transfer = RecurringTransfer::factory()->for($user)->create([
        'amount' => 100.00,
        'reason' => 'Monthly rent payment',
    ]);

    $notification = new RecurringTransferInsufficientBalance($transfer);
    $mail = $notification->toMail($user);

    expect($mail->introLines)->toContain('Reason: Monthly rent payment');
});

test('notification mail includes action button', function () {
    $user = User::factory()->create();
    Wallet::factory()->for($user)->create(['balance' => 50]);
    
    $transfer = RecurringTransfer::factory()->for($user)->create([
        'amount' => 100.00,
    ]);

    $notification = new RecurringTransferInsufficientBalance($transfer);
    $mail = $notification->toMail($user);

    expect($mail->actionText)->toBe('Add Funds');
    expect($mail->actionUrl)->toBe(url('/dashboard'));
});

test('notification toArray includes transfer id', function () {
    $user = User::factory()->create();
    Wallet::factory()->for($user)->create(['balance' => 50]);
    
    $transfer = RecurringTransfer::factory()->for($user)->create([
        'amount' => 100.00,
    ]);

    $notification = new RecurringTransferInsufficientBalance($transfer);
    $array = $notification->toArray($user);

    expect($array)->toHaveKey('recurring_transfer_id');
    expect($array['recurring_transfer_id'])->toBe($transfer->id);
});

test('notification toArray includes required amount', function () {
    $user = User::factory()->create();
    Wallet::factory()->for($user)->create(['balance' => 50]);
    
    $transfer = RecurringTransfer::factory()->for($user)->create([
        'amount' => 100.00,
    ]);

    $notification = new RecurringTransferInsufficientBalance($transfer);
    $array = $notification->toArray($user);

    expect($array)->toHaveKey('required_amount');
    expect($array['required_amount'])->toBe(100.00);
});

test('notification toArray includes current balance', function () {
    $user = User::factory()->create();
    Wallet::factory()->for($user)->create(['balance' => 50]);
    
    $transfer = RecurringTransfer::factory()->for($user)->create([
        'amount' => 100.00,
    ]);

    $notification = new RecurringTransferInsufficientBalance($transfer);
    $array = $notification->toArray($user);

    expect($array)->toHaveKey('current_balance');
    expect($array['current_balance'])->toBe(50);
});

test('notification toArray includes reason', function () {
    $user = User::factory()->create();
    Wallet::factory()->for($user)->create(['balance' => 50]);
    
    $transfer = RecurringTransfer::factory()->for($user)->create([
        'amount' => 100.00,
        'reason' => 'Test reason',
    ]);

    $notification = new RecurringTransferInsufficientBalance($transfer);
    $array = $notification->toArray($user);

    expect($array)->toHaveKey('reason');
    expect($array['reason'])->toBe('Test reason');
});

test('notification handles user without wallet', function () {
    $user = User::factory()->create(); // No wallet
    
    $transfer = RecurringTransfer::factory()->for($user)->create([
        'amount' => 100.00,
    ]);

    $notification = new RecurringTransferInsufficientBalance($transfer);
    $array = $notification->toArray($user);

    expect($array['current_balance'])->toBe(0);
});

test('notification is queueable', function () {
    $user = User::factory()->create();
    $transfer = RecurringTransfer::factory()->for($user)->create();
    $notification = new RecurringTransferInsufficientBalance($transfer);

    expect($notification)->toHaveProperty('connection');
    expect($notification)->toHaveProperty('queue');
});

test('notification implements should queue', function () {
    $notification = new RecurringTransferInsufficientBalance(
        RecurringTransfer::factory()->make()
    );

    expect($notification)->toBeInstanceOf(Illuminate\Contracts\Queue\ShouldQueue::class);
});


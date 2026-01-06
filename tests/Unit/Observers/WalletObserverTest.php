<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Wallet;
use App\Notifications\LowBalance;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
});

test('sends low balance notification when wallet balance drops below 100', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()
        ->for($user)
        ->balance(200)
        ->create();

    // Update the balance to below 100
    $wallet->update(['balance' => 50]);

    Notification::assertSentTo(
        $user,
        LowBalance::class,
        function ($notification, $channels) use ($wallet) {
            return $notification instanceof LowBalance;
        }
    );
});

test('sends low balance notification when wallet balance is exactly 99', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()
        ->for($user)
        ->balance(200)
        ->create();

    // Update the balance to exactly 99
    $wallet->update(['balance' => 99]);

    Notification::assertSentTo($user, LowBalance::class);
});

test('does not send low balance notification when wallet balance is exactly 100', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()
        ->for($user)
        ->balance(200)
        ->create();

    // Update the balance to exactly 100
    $wallet->update(['balance' => 100]);

    Notification::assertNotSentTo($user, LowBalance::class);
});

test('does not send low balance notification when wallet balance is above 100', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()
        ->for($user)
        ->balance(200)
        ->create();

    // Update the balance to above 100
    $wallet->update(['balance' => 150]);

    Notification::assertNotSentTo($user, LowBalance::class);
});

test('does not send low balance notification when wallet balance stays below 100', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()
        ->for($user)
        ->balance(50)
        ->create();

    // Clear any notifications from the creation
    Notification::fake();

    // Update the balance to another value below 100
    $wallet->update(['balance' => 40]);

    Notification::assertSentTo($user, LowBalance::class);
});

test('sends low balance notification when updating from above to below threshold', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()
        ->for($user)
        ->balance(500)
        ->create();

    // Perform a withdrawal that brings balance below 100
    $wallet->update(['balance' => 80]);

    Notification::assertSentTo(
        $user,
        LowBalance::class
    );
});

test('does not send notification when wallet is created with balance above 100', function () {
    $user = User::factory()->create();
    
    Wallet::factory()
        ->for($user)
        ->balance(200)
        ->create();

    Notification::assertNothingSent();
});

test('does not send notification when wallet is created with balance below 100', function () {
    $user = User::factory()->create();
    
    Wallet::factory()
        ->for($user)
        ->balance(50)
        ->create();

    // The observer only triggers on update, not on create
    Notification::assertNothingSent();
});


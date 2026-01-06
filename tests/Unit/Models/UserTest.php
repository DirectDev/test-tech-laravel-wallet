<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Collection;

test('walletTransactions returns empty collection when user has no wallet', function () {
    $user = User::factory()->make();

    $transactions = $user->walletTransactions();

    expect($transactions)
        ->toBeInstanceOf(Collection::class)
        ->toBeEmpty();
});

test('walletTransactions returns empty collection when wallet has no transactions', function () {
    $user = User::factory()->create();
    Wallet::factory()->for($user)->create();

    $transactions = $user->walletTransactions();

    expect($transactions)
        ->toBeInstanceOf(Collection::class)
        ->toBeEmpty();
});

test('walletTransactions returns wallet transactions ordered by id descending', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->for($user)->create();

    $transaction1 = WalletTransaction::factory()
        ->for($wallet)
        ->credit()
        ->amount(100)
        ->create(['created_at' => now()->subMinutes(3)]);

    $transaction2 = WalletTransaction::factory()
        ->for($wallet)
        ->debit()
        ->amount(50)
        ->create(['created_at' => now()->subMinutes(2)]);

    $transaction3 = WalletTransaction::factory()
        ->for($wallet)
        ->credit()
        ->amount(75)
        ->create(['created_at' => now()->subMinutes(1)]);

    $transactions = $user->walletTransactions();

    expect($transactions)
        ->toBeInstanceOf(Collection::class)
        ->toHaveCount(3)
        ->sequence(
            fn ($transaction) => $transaction->id->toBe($transaction3->id),
            fn ($transaction) => $transaction->id->toBe($transaction2->id),
            fn ($transaction) => $transaction->id->toBe($transaction1->id),
        );
});

test('walletTransactions includes transfer relationship', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->for($user)->create();

    $transaction = WalletTransaction::factory()
        ->for($wallet)
        ->credit()
        ->amount(100)
        ->create();

    $transactions = $user->walletTransactions();

    expect($transactions->first()->relationLoaded('transfer'))->toBeTrue();
});

test('walletTransactions returns collection type', function () {
    $user = User::factory()->create();

    $transactions = $user->walletTransactions();

    expect($transactions)->toBeInstanceOf(Collection::class);
});

test('walletTransactions only returns transactions for user wallet', function () {
    $user1 = User::factory()->create();
    $wallet1 = Wallet::factory()->for($user1)->create();
    $transaction1 = WalletTransaction::factory()
        ->for($wallet1)
        ->credit()
        ->amount(100)
        ->create();

    $user2 = User::factory()->create();
    $wallet2 = Wallet::factory()->for($user2)->create();
    $transaction2 = WalletTransaction::factory()
        ->for($wallet2)
        ->credit()
        ->amount(200)
        ->create();

    $transactions = $user1->walletTransactions();

    expect($transactions)
        ->toHaveCount(1)
        ->first()->id->toBe($transaction1->id);
});


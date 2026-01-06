<?php

declare(strict_types=1);

use App\Actions\RegisterRecurringTransfer;
use App\Models\RecurringTransfer;
use App\Models\User;

test('register recurring transfer creates a new recurring transfer', function () {
    $user = User::factory()->create();
    $action = new RegisterRecurringTransfer();

    $recurringTransfer = $action->execute(
        user: $user,
        startDate: '2026-01-15',
        stopDate: '2026-12-31',
        frequency: 30,
        amount: 500.00,
        reason: 'Monthly subscription'
    );

    expect($recurringTransfer)
        ->toBeInstanceOf(RecurringTransfer::class)
        ->user_id->toBe($user->id)
        ->start_date->format('Y-m-d')->toBe('2026-01-15')
        ->stop_date->format('Y-m-d')->toBe('2026-12-31')
        ->frequency->toBe(30)
        ->amount->toBe(500.00)
        ->reason->toBe('Monthly subscription');

    expect(RecurringTransfer::where('user_id', $user->id)->count())->toBe(1);
    
    $dbTransfer = RecurringTransfer::where('user_id', $user->id)->first();
    expect($dbTransfer)
        ->user_id->toBe($user->id)
        ->frequency->toBe(30)
        ->amount->toBe(500.0)
        ->reason->toBe('Monthly subscription');
});

test('register recurring transfer can create transfer without stop date', function () {
    $user = User::factory()->create();
    $action = new RegisterRecurringTransfer();

    $recurringTransfer = $action->execute(
        user: $user,
        startDate: '2026-01-15',
        stopDate: null,
        frequency: 7,
        amount: 100.50,
        reason: 'Weekly payment'
    );

    expect($recurringTransfer)
        ->toBeInstanceOf(RecurringTransfer::class)
        ->stop_date->toBeNull()
        ->frequency->toBe(7)
        ->amount->toBe(100.50);
});

test('register recurring transfer can create transfer without reason', function () {
    $user = User::factory()->create();
    $action = new RegisterRecurringTransfer();

    $recurringTransfer = $action->execute(
        user: $user,
        startDate: '2026-01-15',
        stopDate: '2026-06-15',
        frequency: 14,
        amount: 250.00,
        reason: null
    );

    expect($recurringTransfer)
        ->toBeInstanceOf(RecurringTransfer::class)
        ->reason->toBeNull();

    expect(RecurringTransfer::where('user_id', $user->id)->whereNull('reason')->count())->toBe(1);
});

test('register recurring transfer associates with correct user', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $action = new RegisterRecurringTransfer();

    $transfer1 = $action->execute(
        user: $user1,
        startDate: '2026-01-15',
        stopDate: null,
        frequency: 30,
        amount: 100.00,
        reason: 'User 1 transfer'
    );

    $transfer2 = $action->execute(
        user: $user2,
        startDate: '2026-02-15',
        stopDate: null,
        frequency: 15,
        amount: 200.00,
        reason: 'User 2 transfer'
    );

    expect($transfer1->user_id)->toBe($user1->id);
    expect($transfer2->user_id)->toBe($user2->id);
});


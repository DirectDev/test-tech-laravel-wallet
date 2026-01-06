<?php

declare(strict_types=1);

use App\Models\RecurringTransfer;
use App\Models\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

test('recurring transfer can be created with factory', function () {
    $recurringTransfer = RecurringTransfer::factory()->create();

    expect($recurringTransfer)
        ->toBeInstanceOf(RecurringTransfer::class)
        ->id->not->toBeNull();
});

test('recurring transfer belongs to user', function () {
    $user = User::factory()->create();
    $recurringTransfer = RecurringTransfer::factory()->for($user)->create();

    expect($recurringTransfer->user)
        ->toBeInstanceOf(User::class)
        ->id->toBe($user->id);
});

test('user has many recurring transfers', function () {
    $user = User::factory()->create();
    
    RecurringTransfer::factory()->count(3)->for($user)->create();

    expect($user->recurringTransfers)
        ->toHaveCount(3)
        ->each->toBeInstanceOf(RecurringTransfer::class);
});

test('start_date is cast to date', function () {
    $recurringTransfer = RecurringTransfer::factory()->create([
        'start_date' => '2026-01-15',
    ]);

    expect($recurringTransfer->start_date)
        ->toBeInstanceOf(Carbon::class)
        ->format('Y-m-d')->toBe('2026-01-15');
});

test('stop_date is cast to date', function () {
    $recurringTransfer = RecurringTransfer::factory()->create([
        'stop_date' => '2026-06-15',
    ]);

    expect($recurringTransfer->stop_date)
        ->toBeInstanceOf(Carbon::class)
        ->format('Y-m-d')->toBe('2026-06-15');
});

test('stop_date can be null', function () {
    $recurringTransfer = RecurringTransfer::factory()->create([
        'stop_date' => null,
    ]);

    expect($recurringTransfer->stop_date)->toBeNull();
});

test('frequency is cast to integer', function () {
    $recurringTransfer = RecurringTransfer::factory()->create([
        'frequency' => '7',
    ]);

    expect($recurringTransfer->frequency)
        ->toBeInt()
        ->toBe(7);
});

test('amount is cast to float', function () {
    $recurringTransfer = RecurringTransfer::factory()->create([
        'amount' => '150.75',
    ]);

    expect($recurringTransfer->amount)
        ->toBeFloat()
        ->toBe(150.75);
});

test('recurring transfer uses soft deletes', function () {
    $recurringTransfer = RecurringTransfer::factory()->create();
    
    expect(in_array(SoftDeletes::class, class_uses(RecurringTransfer::class)))
        ->toBeTrue();
    
    $recurringTransfer->delete();

    expect($recurringTransfer->trashed())->toBeTrue();
});

test('soft deleted recurring transfer can be restored', function () {
    $recurringTransfer = RecurringTransfer::factory()->create();
    $id = $recurringTransfer->id;
    
    $recurringTransfer->delete();
    
    expect(RecurringTransfer::find($id))->toBeNull();
    expect(RecurringTransfer::withTrashed()->find($id))->not->toBeNull();
    
    $recurringTransfer->restore();
    
    expect(RecurringTransfer::find($id))
        ->not->toBeNull()
        ->trashed()->toBeFalse();
});

test('force delete permanently removes recurring transfer', function () {
    $recurringTransfer = RecurringTransfer::factory()->create();
    $id = $recurringTransfer->id;
    
    $recurringTransfer->forceDelete();
    
    expect(RecurringTransfer::withTrashed()->find($id))->toBeNull();
});

test('recurring transfer has correct fillable attributes', function () {
    $user = User::factory()->create();
    
    $recurringTransfer = RecurringTransfer::create([
        'user_id' => $user->id,
        'start_date' => '2026-01-01',
        'stop_date' => '2026-12-31',
        'frequency' => 30,
        'amount' => 500.00,
        'reason' => 'Monthly subscription',
    ]);

    expect($recurringTransfer)
        ->user_id->toBe($user->id)
        ->start_date->format('Y-m-d')->toBe('2026-01-01')
        ->stop_date->format('Y-m-d')->toBe('2026-12-31')
        ->frequency->toBe(30)
        ->amount->toBe(500.00)
        ->reason->toBe('Monthly subscription');
});

test('reason can be null', function () {
    $recurringTransfer = RecurringTransfer::factory()->create([
        'reason' => null,
    ]);

    expect($recurringTransfer->reason)->toBeNull();
});

test('recurring transfer has timestamps', function () {
    $recurringTransfer = RecurringTransfer::factory()->create();

    expect($recurringTransfer->created_at)
        ->toBeInstanceOf(Carbon::class)
        ->and($recurringTransfer->updated_at)
        ->toBeInstanceOf(Carbon::class);
});

test('user recurring transfers only return transfers for that user', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    
    $transfer1 = RecurringTransfer::factory()->for($user1)->create();
    $transfer2 = RecurringTransfer::factory()->for($user2)->create();

    expect($user1->recurringTransfers)
        ->toHaveCount(1)
        ->first()->id->toBe($transfer1->id);
    
    expect($user2->recurringTransfers)
        ->toHaveCount(1)
        ->first()->id->toBe($transfer2->id);
});

test('soft deleted recurring transfers are not included in default query', function () {
    $user = User::factory()->create();
    
    RecurringTransfer::factory()->count(2)->for($user)->create();
    $recurringTransfer = RecurringTransfer::factory()->for($user)->create();
    
    $recurringTransfer->delete();

    expect($user->recurringTransfers()->count())->toBe(2);
    expect($user->recurringTransfers()->withTrashed()->count())->toBe(3);
});


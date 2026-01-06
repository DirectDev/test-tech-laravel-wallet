<?php

declare(strict_types=1);

use App\Jobs\RecurringTransferJob;
use App\Models\RecurringTransfer;
use App\Models\User;
use App\Notifications\RecurringTransferInsufficientBalance;
use Illuminate\Support\Facades\Notification;

test('job can be instantiated', function () {
    $job = new RecurringTransferJob();

    expect($job)->toBeInstanceOf(RecurringTransferJob::class);
});

test('getActiveRecurringTransfers returns transfers that have started', function () {
    $user = User::factory()->create();
    
    // Transfer that started yesterday
    $activeTransfer = RecurringTransfer::factory()->for($user)->create([
        'start_date' => now()->subDay(),
        'stop_date' => null,
    ]);

    // Transfer that starts tomorrow
    $futureTransfer = RecurringTransfer::factory()->for($user)->create([
        'start_date' => now()->addDay(),
        'stop_date' => null,
    ]);

    $job = new RecurringTransferJob();
    $activeTransfers = $job->getActiveRecurringTransfers();

    expect($activeTransfers)
        ->toHaveCount(1)
        ->first()->id->toBe($activeTransfer->id);
});

test('getActiveRecurringTransfers returns transfers starting today', function () {
    $user = User::factory()->create();
    
    $transfer = RecurringTransfer::factory()->for($user)->create([
        'start_date' => now()->startOfDay(),
        'stop_date' => null,
    ]);

    $job = new RecurringTransferJob();
    $activeTransfers = $job->getActiveRecurringTransfers();

    expect($activeTransfers)
        ->toHaveCount(1)
        ->first()->id->toBe($transfer->id);
});

test('getActiveRecurringTransfers excludes transfers with past stop_date', function () {
    $user = User::factory()->create();
    
    // Active transfer
    $activeTransfer = RecurringTransfer::factory()->for($user)->create([
        'start_date' => now()->subWeek(),
        'stop_date' => now()->addWeek(),
    ]);

    // Ended transfer
    $endedTransfer = RecurringTransfer::factory()->for($user)->create([
        'start_date' => now()->subMonth(),
        'stop_date' => now()->subDay(),
    ]);

    $job = new RecurringTransferJob();
    $activeTransfers = $job->getActiveRecurringTransfers();

    expect($activeTransfers)
        ->toHaveCount(1)
        ->first()->id->toBe($activeTransfer->id);
});

test('getActiveRecurringTransfers includes transfers with null stop_date', function () {
    $user = User::factory()->create();
    
    $transfer1 = RecurringTransfer::factory()->for($user)->create([
        'start_date' => now()->subDay(),
        'stop_date' => null,
    ]);

    $transfer2 = RecurringTransfer::factory()->for($user)->create([
        'start_date' => now()->subWeek(),
        'stop_date' => null,
    ]);

    $job = new RecurringTransferJob();
    $activeTransfers = $job->getActiveRecurringTransfers();

    expect($activeTransfers)->toHaveCount(2);
});

test('getActiveRecurringTransfers includes transfers with future stop_date', function () {
    $user = User::factory()->create();
    
    $transfer = RecurringTransfer::factory()->for($user)->create([
        'start_date' => now()->subWeek(),
        'stop_date' => now()->addMonth(),
    ]);

    $job = new RecurringTransferJob();
    $activeTransfers = $job->getActiveRecurringTransfers();

    expect($activeTransfers)
        ->toHaveCount(1)
        ->first()->id->toBe($transfer->id);
});

test('getActiveRecurringTransfers excludes soft deleted transfers', function () {
    $user = User::factory()->create();
    
    $activeTransfer = RecurringTransfer::factory()->for($user)->create([
        'start_date' => now()->subDay(),
        'stop_date' => null,
    ]);

    $deletedTransfer = RecurringTransfer::factory()->for($user)->create([
        'start_date' => now()->subDay(),
        'stop_date' => null,
    ]);
    $deletedTransfer->delete();

    $job = new RecurringTransferJob();
    $activeTransfers = $job->getActiveRecurringTransfers();

    expect($activeTransfers)
        ->toHaveCount(1)
        ->first()->id->toBe($activeTransfer->id);
});

test('getActiveRecurringTransfers returns transfers from all users', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    
    $transfer1 = RecurringTransfer::factory()->for($user1)->create([
        'start_date' => now()->subDay(),
        'stop_date' => null,
    ]);

    $transfer2 = RecurringTransfer::factory()->for($user2)->create([
        'start_date' => now()->subDay(),
        'stop_date' => null,
    ]);

    $job = new RecurringTransferJob();
    $activeTransfers = $job->getActiveRecurringTransfers();

    expect($activeTransfers)->toHaveCount(2);
});

test('getActiveRecurringTransfers handles complex date scenarios', function () {
    $user = User::factory()->create();
    
    // Should be included: started yesterday, no end
    $transfer1 = RecurringTransfer::factory()->for($user)->create([
        'start_date' => now()->subDay(),
        'stop_date' => null,
    ]);

    // Should be included: started last week, ends next week
    $transfer2 = RecurringTransfer::factory()->for($user)->create([
        'start_date' => now()->subWeek(),
        'stop_date' => now()->addWeek(),
    ]);

    // Should NOT be included: starts tomorrow
    $transfer3 = RecurringTransfer::factory()->for($user)->create([
        'start_date' => now()->addDay(),
        'stop_date' => null,
    ]);

    // Should NOT be included: ended yesterday
    $transfer4 = RecurringTransfer::factory()->for($user)->create([
        'start_date' => now()->subMonth(),
        'stop_date' => now()->subDay(),
    ]);

    // Should be included: started today, ends in future
    $transfer5 = RecurringTransfer::factory()->for($user)->create([
        'start_date' => now()->startOfDay(),
        'stop_date' => now()->addMonth(),
    ]);

    $job = new RecurringTransferJob();
    $activeTransfers = $job->getActiveRecurringTransfers();

    expect($activeTransfers)->toHaveCount(3);
    
    $ids = $activeTransfers->pluck('id')->toArray();
    expect($ids)->toContain($transfer1->id);
    expect($ids)->toContain($transfer2->id);
    expect($ids)->toContain($transfer5->id);
    expect($ids)->not->toContain($transfer3->id);
    expect($ids)->not->toContain($transfer4->id);
});

test('getActiveRecurringTransfers returns empty collection when no active transfers', function () {
    $user = User::factory()->create();
    
    // All in the future
    RecurringTransfer::factory()->for($user)->create([
        'start_date' => now()->addWeek(),
        'stop_date' => null,
    ]);

    // All ended
    RecurringTransfer::factory()->for($user)->create([
        'start_date' => now()->subMonth(),
        'stop_date' => now()->subWeek(),
    ]);

    $job = new RecurringTransferJob();
    $activeTransfers = $job->getActiveRecurringTransfers();

    expect($activeTransfers)->toBeEmpty();
});

test('getActiveRecurringTransfers excludes transfers ending today', function () {
    $user = User::factory()->create();
    
    // Transfer that ends at start of today (should be excluded)
    $endedTransfer = RecurringTransfer::factory()->for($user)->create([
        'start_date' => now()->subMonth(),
        'stop_date' => now()->startOfDay(),
    ]);

    $job = new RecurringTransferJob();
    $activeTransfers = $job->getActiveRecurringTransfers();

    expect($activeTransfers)->toBeEmpty();
});

test('job can be dispatched', function () {
    $user = User::factory()->create();
    
    RecurringTransfer::factory()->for($user)->create([
        'start_date' => now()->subDay(),
        'stop_date' => null,
    ]);

    RecurringTransferJob::dispatch();

    expect(true)->toBeTrue();
});

test('job handle method executes without errors', function () {
    $user = User::factory()->create();
    
    RecurringTransfer::factory()->count(3)->for($user)->create([
        'start_date' => now()->subDay(),
        'stop_date' => null,
    ]);

    $job = new RecurringTransferJob();
    $job->handle();

    expect(true)->toBeTrue();
});

test('getActiveRecurringTransfers returns collection of RecurringTransfer models', function () {
    $user = User::factory()->create();
    
    RecurringTransfer::factory()->count(2)->for($user)->create([
        'start_date' => now()->subDay(),
        'stop_date' => null,
    ]);

    $job = new RecurringTransferJob();
    $activeTransfers = $job->getActiveRecurringTransfers();

    expect($activeTransfers)
        ->toHaveCount(2)
        ->each->toBeInstanceOf(RecurringTransfer::class);
});

test('getActiveRecurringTransfers maintains transfer attributes', function () {
    $user = User::factory()->create();
    
    $transfer = RecurringTransfer::factory()->for($user)->create([
        'start_date' => now()->subDay(),
        'stop_date' => now()->addMonth(),
        'frequency' => 7,
        'amount' => 100.50,
        'reason' => 'Weekly payment',
    ]);

    $job = new RecurringTransferJob();
    $activeTransfers = $job->getActiveRecurringTransfers();

    $retrieved = $activeTransfers->first();
    
    expect($retrieved)
        ->id->toBe($transfer->id)
        ->frequency->toBe(7)
        ->amount->toBe(100.5)
        ->reason->toBe('Weekly payment');
});

test('daysSinceLastTransfert returns integer', function () {
    $user = User::factory()->create();
    $transfer = RecurringTransfer::factory()->for($user)->create();

    $job = new RecurringTransferJob();
    $days = $job->daysSinceLastTransfert($transfer);

    expect($days)->toBeInt();
});

test('performWalletTransfer returns boolean', function () {
    $user = User::factory()->create();
    $transfer = RecurringTransfer::factory()->for($user)->create();

    $job = new RecurringTransferJob();
    $result = $job->performWalletTransfer($transfer);

    expect($result)->toBeBool();
});

test('performWalletTransfer can be called with recurring transfer', function () {
    $user = User::factory()->create();
    $transfer = RecurringTransfer::factory()->for($user)->create([
        'amount' => 100.00,
        'frequency' => 7,
    ]);

    $job = new RecurringTransferJob();
    $result = $job->performWalletTransfer($transfer);

    expect($result)->toBeTrue();
});

test('handle method calls performWalletTransfer for eligible transfers', function () {
    $user = User::factory()->create();
    \App\Models\Wallet::factory()->for($user)->create(['balance' => 100000]); // Sufficient balance
    
    // Create a mock job to track method calls
    $job = Mockery::mock(RecurringTransferJob::class)->makePartial();
    
    $transfer = RecurringTransfer::factory()->for($user)->create([
        'start_date' => now()->subDay(),
        'stop_date' => null,
        'frequency' => 1,
        'amount' => 10.00,
    ]);

    // Mock methods to allow transfer
    $job->shouldReceive('daysSinceLastTransfert')
        ->with(Mockery::type(RecurringTransfer::class))
        ->andReturn(10); // More than frequency
    
    $job->shouldReceive('performWalletTransfer')
        ->once()
        ->with(Mockery::type(RecurringTransfer::class))
        ->andReturn(true);

    $job->handle();

    expect(true)->toBeTrue();
});

test('handle method skips transfer when not enough days passed', function () {
    $user = User::factory()->create();
    \App\Models\Wallet::factory()->for($user)->create(['balance' => 100000]);
    
    $job = Mockery::mock(RecurringTransferJob::class)->makePartial();
    
    $transfer = RecurringTransfer::factory()->for($user)->create([
        'start_date' => now()->subDay(),
        'stop_date' => null,
        'frequency' => 7,
        'amount' => 10.00,
    ]);

    // Mock: only 3 days passed, but frequency is 7
    $job->shouldReceive('daysSinceLastTransfert')
        ->with(Mockery::type(RecurringTransfer::class))
        ->andReturn(3);
    
    $job->shouldReceive('performWalletTransfer')
        ->never();

    $job->handle();

    expect(true)->toBeTrue();
});

test('handle method skips transfer when insufficient balance', function () {
    $user = User::factory()->create();
    // Balance: 50 cents (numerically less than amount)
    \App\Models\Wallet::factory()->for($user)->create(['balance' => 50]);
    
    $job = Mockery::mock(RecurringTransferJob::class)->makePartial();
    
    // Amount: 100.00 EUR (numerically greater than balance)
    $transfer = RecurringTransfer::factory()->for($user)->create([
        'start_date' => now()->subDay(),
        'stop_date' => null,
        'frequency' => 7,
        'amount' => 100.00,
    ]);

    // Mock: enough days passed
    $job->shouldReceive('daysSinceLastTransfert')
        ->with(Mockery::type(RecurringTransfer::class))
        ->andReturn(10);
    
    // Should not be called because balance (50) < amount (100.00)
    $job->shouldReceive('performWalletTransfer')
        ->never();

    $job->handle();

    expect(true)->toBeTrue();
});

test('handle method processes transfer when all conditions met', function () {
    $user = User::factory()->create();
    \App\Models\Wallet::factory()->for($user)->create(['balance' => 100000]); // 1000.00 EUR
    
    $job = Mockery::mock(RecurringTransferJob::class)->makePartial();
    
    $transfer = RecurringTransfer::factory()->for($user)->create([
        'start_date' => now()->subDay(),
        'stop_date' => null,
        'frequency' => 7,
        'amount' => 50.00,
    ]);

    // Mock: enough days passed
    $job->shouldReceive('daysSinceLastTransfert')
        ->with(Mockery::type(RecurringTransfer::class))
        ->andReturn(10);
    
    // Should be called once
    $job->shouldReceive('performWalletTransfer')
        ->once()
        ->with(Mockery::type(RecurringTransfer::class))
        ->andReturn(true);

    $job->handle();

    expect(true)->toBeTrue();
});

test('handle method skips when days equal to frequency', function () {
    $user = User::factory()->create();
    \App\Models\Wallet::factory()->for($user)->create(['balance' => 100000]);
    
    $job = Mockery::mock(RecurringTransferJob::class)->makePartial();
    
    $transfer = RecurringTransfer::factory()->for($user)->create([
        'start_date' => now()->subDay(),
        'stop_date' => null,
        'frequency' => 7,
        'amount' => 10.00,
    ]);

    // Mock: exactly 7 days passed (equal to frequency)
    $job->shouldReceive('daysSinceLastTransfert')
        ->with(Mockery::type(RecurringTransfer::class))
        ->andReturn(7);
    
    // Should be skipped (condition is <= frequency)
    $job->shouldReceive('performWalletTransfer')
        ->never();

    $job->handle();

    expect(true)->toBeTrue();
});

test('handle method processes when days greater than frequency', function () {
    $user = User::factory()->create();
    \App\Models\Wallet::factory()->for($user)->create(['balance' => 100000]);
    
    $job = Mockery::mock(RecurringTransferJob::class)->makePartial();
    
    $transfer = RecurringTransfer::factory()->for($user)->create([
        'start_date' => now()->subDay(),
        'stop_date' => null,
        'frequency' => 7,
        'amount' => 10.00,
    ]);

    // Mock: 8 days passed (greater than frequency)
    $job->shouldReceive('daysSinceLastTransfert')
        ->with(Mockery::type(RecurringTransfer::class))
        ->andReturn(8);
    
    // Should be processed
    $job->shouldReceive('performWalletTransfer')
        ->once()
        ->andReturn(true);

    $job->handle();

    expect(true)->toBeTrue();
});

test('handle method skips transfer when user has no wallet', function () {
    $user = User::factory()->create();
    // No wallet created for user
    
    $job = Mockery::mock(RecurringTransferJob::class)->makePartial();
    
    $transfer = RecurringTransfer::factory()->for($user)->create([
        'start_date' => now()->subDay(),
        'stop_date' => null,
        'frequency' => 7,
        'amount' => 10.00,
    ]);

    $job->shouldReceive('daysSinceLastTransfert')
        ->with(Mockery::type(RecurringTransfer::class))
        ->andReturn(10);
    
    // Should not be called due to no wallet
    $job->shouldReceive('performWalletTransfer')
        ->never();

    $job->handle();

    expect(true)->toBeTrue();
});

test('handle method processes multiple eligible transfers', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    \App\Models\Wallet::factory()->for($user1)->create(['balance' => 100000]);
    \App\Models\Wallet::factory()->for($user2)->create(['balance' => 100000]);
    
    $job = Mockery::mock(RecurringTransferJob::class)->makePartial();
    
    $transfer1 = RecurringTransfer::factory()->for($user1)->create([
        'start_date' => now()->subDay(),
        'stop_date' => null,
        'frequency' => 7,
        'amount' => 10.00,
    ]);

    $transfer2 = RecurringTransfer::factory()->for($user2)->create([
        'start_date' => now()->subDay(),
        'stop_date' => null,
        'frequency' => 7,
        'amount' => 20.00,
    ]);

    $job->shouldReceive('daysSinceLastTransfert')
        ->with(Mockery::type(RecurringTransfer::class))
        ->andReturn(10);
    
    // Should be called twice (once for each transfer)
    $job->shouldReceive('performWalletTransfer')
        ->twice()
        ->andReturn(true);

    $job->handle();

    expect(true)->toBeTrue();
});

test('handle method skips some and processes others based on conditions', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $user3 = User::factory()->create();
    
    \App\Models\Wallet::factory()->for($user1)->create(['balance' => 100000]);
    \App\Models\Wallet::factory()->for($user2)->create(['balance' => 500]); // Low balance
    \App\Models\Wallet::factory()->for($user3)->create(['balance' => 100000]);
    
    $job = Mockery::mock(RecurringTransferJob::class)->makePartial();
    
    // Transfer 1: Should be processed (enough days, enough balance)
    $transfer1 = RecurringTransfer::factory()->for($user1)->create([
        'start_date' => now()->subDay(),
        'frequency' => 7,
        'amount' => 10.00,
    ]);

    // Transfer 2: Should be skipped (insufficient balance)
    $transfer2 = RecurringTransfer::factory()->for($user2)->create([
        'start_date' => now()->subDay(),
        'frequency' => 7,
        'amount' => 100.00,
    ]);

    // Transfer 3: Should be skipped (not enough days)
    $transfer3 = RecurringTransfer::factory()->for($user3)->create([
        'start_date' => now()->subDay(),
        'frequency' => 30,
        'amount' => 10.00,
    ]);

    $job->shouldReceive('daysSinceLastTransfert')
        ->andReturnUsing(function ($transfer) use ($transfer1, $transfer2, $transfer3) {
            if ($transfer->id === $transfer1->id) return 10; // Enough days
            if ($transfer->id === $transfer2->id) return 10; // Enough days
            if ($transfer->id === $transfer3->id) return 5;  // Not enough days
            return 0;
        });
    
    // Should only be called once (for transfer1)
    $job->shouldReceive('performWalletTransfer')
        ->once()
        ->with(Mockery::on(fn($t) => $t->id === $transfer1->id))
        ->andReturn(true);

    $job->handle();

    expect(true)->toBeTrue();
});

test('job can be instantiated with specific transfer id', function () {
    $transfer = RecurringTransfer::factory()->create();
    
    $job = new RecurringTransferJob($transfer->id);

    expect($job)->toBeInstanceOf(RecurringTransferJob::class);
    expect($job->id)->toBe($transfer->id);
});

test('job can be instantiated without id parameter', function () {
    $job = new RecurringTransferJob();

    expect($job)->toBeInstanceOf(RecurringTransferJob::class);
    expect($job->id)->toBeNull();
});

test('getActiveRecurringTransfers returns only specified transfer when id provided', function () {
    $user = User::factory()->create();
    
    $transfer1 = RecurringTransfer::factory()->for($user)->create([
        'start_date' => now()->subDay(),
        'stop_date' => null,
    ]);

    $transfer2 = RecurringTransfer::factory()->for($user)->create([
        'start_date' => now()->subDay(),
        'stop_date' => null,
    ]);

    $transfer3 = RecurringTransfer::factory()->for($user)->create([
        'start_date' => now()->subDay(),
        'stop_date' => null,
    ]);

    $job = new RecurringTransferJob($transfer2->id);
    $activeTransfers = $job->getActiveRecurringTransfers($transfer2->id);

    expect($activeTransfers)
        ->toHaveCount(1)
        ->first()->id->toBe($transfer2->id);
});

test('getActiveRecurringTransfers with id returns empty if transfer not active', function () {
    $user = User::factory()->create();
    
    // Transfer that hasn't started yet
    $transfer = RecurringTransfer::factory()->for($user)->create([
        'start_date' => now()->addWeek(),
        'stop_date' => null,
    ]);

    $job = new RecurringTransferJob($transfer->id);
    $activeTransfers = $job->getActiveRecurringTransfers($transfer->id);

    expect($activeTransfers)->toBeEmpty();
});

test('getActiveRecurringTransfers with id returns empty if transfer ended', function () {
    $user = User::factory()->create();
    
    // Transfer that has ended
    $transfer = RecurringTransfer::factory()->for($user)->create([
        'start_date' => now()->subMonth(),
        'stop_date' => now()->subWeek(),
    ]);

    $job = new RecurringTransferJob($transfer->id);
    $activeTransfers = $job->getActiveRecurringTransfers($transfer->id);

    expect($activeTransfers)->toBeEmpty();
});

test('getActiveRecurringTransfers with id returns empty if transfer soft deleted', function () {
    $user = User::factory()->create();
    
    $transfer = RecurringTransfer::factory()->for($user)->create([
        'start_date' => now()->subDay(),
        'stop_date' => null,
    ]);
    
    $transfer->delete();

    $job = new RecurringTransferJob($transfer->id);
    $activeTransfers = $job->getActiveRecurringTransfers($transfer->id);

    expect($activeTransfers)->toBeEmpty();
});

test('getActiveRecurringTransfers with non-existent id returns empty', function () {
    $job = new RecurringTransferJob(99999);
    $activeTransfers = $job->getActiveRecurringTransfers(99999);

    expect($activeTransfers)->toBeEmpty();
});

test('job with id only processes specified transfer', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    \App\Models\Wallet::factory()->for($user1)->create(['balance' => 100000]);
    \App\Models\Wallet::factory()->for($user2)->create(['balance' => 100000]);
    
    $transfer1 = RecurringTransfer::factory()->for($user1)->create([
        'start_date' => now()->subDay(),
        'stop_date' => null,
        'frequency' => 7,
        'amount' => 10.00,
    ]);

    $transfer2 = RecurringTransfer::factory()->for($user2)->create([
        'start_date' => now()->subDay(),
        'stop_date' => null,
        'frequency' => 7,
        'amount' => 10.00,
    ]);

    $job = Mockery::mock(RecurringTransferJob::class, [$transfer1->id])->makePartial();

    $job->shouldReceive('daysSinceLastTransfert')
        ->with(Mockery::on(fn($t) => $t->id === $transfer1->id))
        ->andReturn(10);
    
    // Should only be called once for transfer1
    $job->shouldReceive('performWalletTransfer')
        ->once()
        ->with(Mockery::on(fn($t) => $t->id === $transfer1->id))
        ->andReturn(true);

    $job->handle();

    expect(true)->toBeTrue();
});

test('job can be dispatched with specific transfer id', function () {
    $user = User::factory()->create();
    $transfer = RecurringTransfer::factory()->for($user)->create([
        'start_date' => now()->subDay(),
        'stop_date' => null,
    ]);

    RecurringTransferJob::dispatch($transfer->id);

    expect(true)->toBeTrue();
});

test('job dispatched with id processes only that transfer', function () {
    $user = User::factory()->create();
    \App\Models\Wallet::factory()->for($user)->create(['balance' => 100000]);
    
    $transfer1 = RecurringTransfer::factory()->for($user)->create([
        'start_date' => now()->subDay(),
        'frequency' => 7,
        'amount' => 10.00,
    ]);

    $transfer2 = RecurringTransfer::factory()->for($user)->create([
        'start_date' => now()->subDay(),
        'frequency' => 7,
        'amount' => 10.00,
    ]);

    $job = new RecurringTransferJob($transfer1->id);
    $activeTransfers = $job->getActiveRecurringTransfers($transfer1->id);

    expect($activeTransfers)
        ->toHaveCount(1)
        ->first()->id->toBe($transfer1->id);
});

test('getActiveRecurringTransfers respects id parameter over job property', function () {
    $user = User::factory()->create();
    
    $transfer1 = RecurringTransfer::factory()->for($user)->create([
        'start_date' => now()->subDay(),
        'stop_date' => null,
    ]);

    $transfer2 = RecurringTransfer::factory()->for($user)->create([
        'start_date' => now()->subDay(),
        'stop_date' => null,
    ]);

    // Job created with transfer1 id
    $job = new RecurringTransferJob($transfer1->id);
    
    // But call method with transfer2 id
    $activeTransfers = $job->getActiveRecurringTransfers($transfer2->id);

    expect($activeTransfers)
        ->toHaveCount(1)
        ->first()->id->toBe($transfer2->id);
});

test('job with id skips transfer if conditions not met', function () {
    $user = User::factory()->create();
    \App\Models\Wallet::factory()->for($user)->create(['balance' => 100000]);
    
    $transfer = RecurringTransfer::factory()->for($user)->create([
        'start_date' => now()->subDay(),
        'frequency' => 30,
        'amount' => 10.00,
    ]);

    $job = Mockery::mock(RecurringTransferJob::class, [$transfer->id])->makePartial();

    // Not enough days passed
    $job->shouldReceive('daysSinceLastTransfert')
        ->with(Mockery::type(RecurringTransfer::class))
        ->andReturn(5);
    
    // Should not be called
    $job->shouldReceive('performWalletTransfer')
        ->never();

    $job->handle();

    expect(true)->toBeTrue();
});


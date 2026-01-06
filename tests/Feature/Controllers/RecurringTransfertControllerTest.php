<?php

declare(strict_types=1);

use App\Jobs\RecurringTransferJob;
use App\Models\RecurringTransfer;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

test('authenticated user can create recurring transfer', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('recurring-transfer'), [
        'start_date' => '2026-01-15',
        'stop_date' => '2026-12-31',
        'frequency' => 30,
        'amount' => 500.00,
        'reason' => 'Monthly subscription',
    ]);

    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHas('recurring-transfer-status', 'success');

    $transfer = RecurringTransfer::where('user_id', $user->id)->first();
    expect($transfer)
        ->not->toBeNull()
        ->user_id->toBe($user->id)
        ->frequency->toBe(30)
        ->amount->toBe(500.0)
        ->reason->toBe('Monthly subscription');
});

test('can create recurring transfer without optional fields', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('recurring-transfer'), [
        'start_date' => '2026-01-15',
        'frequency' => 7,
        'amount' => 100.00,
    ]);

    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHas('recurring-transfer-status', 'success');

    $transfer = RecurringTransfer::where('user_id', $user->id)->first();
    expect($transfer)
        ->not->toBeNull()
        ->user_id->toBe($user->id)
        ->stop_date->toBeNull()
        ->frequency->toBe(7)
        ->amount->toBe(100.0)
        ->reason->toBeNull();
});

test('validation fails when start_date is missing', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('recurring-transfer'), [
        'frequency' => 30,
        'amount' => 500.00,
    ]);

    $response->assertSessionHasErrors('start_date');
});

test('validation fails when frequency is missing', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('recurring-transfer'), [
        'start_date' => '2026-01-15',
        'amount' => 500.00,
    ]);

    $response->assertSessionHasErrors('frequency');
});

test('validation fails when amount is missing', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('recurring-transfer'), [
        'start_date' => '2026-01-15',
        'frequency' => 30,
    ]);

    $response->assertSessionHasErrors('amount');
});

test('validation fails when amount is negative', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('recurring-transfer'), [
        'start_date' => '2026-01-15',
        'frequency' => 30,
        'amount' => -50.00,
    ]);

    $response->assertSessionHasErrors('amount');
});

test('validation fails when frequency is less than 1', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('recurring-transfer'), [
        'start_date' => '2026-01-15',
        'frequency' => 0,
        'amount' => 500.00,
    ]);

    $response->assertSessionHasErrors('frequency');
});

test('validation fails when stop_date is before start_date', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('recurring-transfer'), [
        'start_date' => '2026-01-15',
        'stop_date' => '2026-01-01',
        'frequency' => 30,
        'amount' => 500.00,
    ]);

    $response->assertSessionHasErrors('stop_date');
});

test('unauthenticated user cannot create recurring transfer', function () {
    $response = $this->post(route('recurring-transfer'), [
        'start_date' => '2026-01-15',
        'frequency' => 30,
        'amount' => 500.00,
    ]);

    $response->assertRedirect(route('login'));
    expect(RecurringTransfer::count())->toBe(0);
});

test('recurring transfer belongs to authenticated user', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $this->actingAs($user1)->post(route('recurring-transfer'), [
        'start_date' => '2026-01-15',
        'frequency' => 30,
        'amount' => 500.00,
    ]);

    $transfer = RecurringTransfer::first();

    expect($transfer->user_id)->toBe($user1->id);
    expect($transfer->user_id)->not->toBe($user2->id);
});

test('creating recurring transfer dispatches job with transfer id', function () {
    Queue::fake();
    
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('recurring-transfer'), [
        'start_date' => '2026-02-01',
        'frequency' => 30,
        'amount' => 100.00,
        'reason' => 'Test transfer',
    ]);

    $transfer = RecurringTransfer::first();

    Queue::assertPushed(RecurringTransferJob::class, function ($job) use ($transfer) {
        return $job->id === $transfer->id;
    });
});

test('job is dispatched exactly once per transfer creation', function () {
    Queue::fake();
    
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('recurring-transfer'), [
        'start_date' => '2026-02-01',
        'frequency' => 7,
        'amount' => 50.00,
    ]);

    Queue::assertPushed(RecurringTransferJob::class, 1);
});

test('each created transfer dispatches its own job', function () {
    Queue::fake();
    
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('recurring-transfer'), [
        'start_date' => '2026-02-01',
        'frequency' => 7,
        'amount' => 50.00,
    ]);

    $this->actingAs($user)->post(route('recurring-transfer'), [
        'start_date' => '2026-02-15',
        'frequency' => 14,
        'amount' => 100.00,
    ]);

    Queue::assertPushed(RecurringTransferJob::class, 2);
    
    $transfers = RecurringTransfer::all();
    
    foreach ($transfers as $transfer) {
        Queue::assertPushed(RecurringTransferJob::class, function ($job) use ($transfer) {
            return $job->id === $transfer->id;
        });
    }
});

test('job is not dispatched when transfer creation fails', function () {
    Queue::fake();
    
    $user = User::factory()->create();

    // Send invalid data to cause failure
    $this->actingAs($user)->post(route('recurring-transfer'), [
        'start_date' => 'invalid-date',
        'frequency' => 'not-a-number',
        'amount' => -100,
    ]);

    Queue::assertNothingPushed();
});

test('job receives correct transfer id', function () {
    Queue::fake();
    
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('recurring-transfer'), [
        'start_date' => '2026-03-01',
        'frequency' => 30,
        'amount' => 150.00,
        'reason' => 'Monthly payment',
    ]);

    $transfer = RecurringTransfer::where('amount', 150.0)->first();

    Queue::assertPushed(RecurringTransferJob::class, function ($job) use ($transfer) {
        return $job->id === $transfer->id;
    });
});

test('job is dispatched for transfer without optional fields', function () {
    Queue::fake();
    
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('recurring-transfer'), [
        'start_date' => '2026-02-01',
        'frequency' => 7,
        'amount' => 25.00,
        // No stop_date or reason
    ]);

    Queue::assertPushed(RecurringTransferJob::class, 1);
    
    $transfer = RecurringTransfer::first();
    
    Queue::assertPushed(RecurringTransferJob::class, function ($job) use ($transfer) {
        return $job->id === $transfer->id;
    });
});

test('different users transfers dispatch separate jobs', function () {
    Queue::fake();
    
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $this->actingAs($user1)->post(route('recurring-transfer'), [
        'start_date' => '2026-02-01',
        'frequency' => 30,
        'amount' => 100.00,
    ]);

    $this->actingAs($user2)->post(route('recurring-transfer'), [
        'start_date' => '2026-02-01',
        'frequency' => 15,
        'amount' => 200.00,
    ]);

    Queue::assertPushed(RecurringTransferJob::class, 2);
    
    $transfer1 = RecurringTransfer::where('user_id', $user1->id)->first();
    $transfer2 = RecurringTransfer::where('user_id', $user2->id)->first();

    Queue::assertPushed(RecurringTransferJob::class, function ($job) use ($transfer1) {
        return $job->id === $transfer1->id;
    });

    Queue::assertPushed(RecurringTransferJob::class, function ($job) use ($transfer2) {
        return $job->id === $transfer2->id;
    });
});

test('job dispatch happens after successful transfer creation', function () {
    Queue::fake();
    
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('recurring-transfer'), [
        'start_date' => '2026-02-01',
        'frequency' => 30,
        'amount' => 100.00,
    ]);

    $response->assertSessionHas('recurring-transfer-status', 'success');
    
    Queue::assertPushed(RecurringTransferJob::class);
});

test('job is dispatched on queue', function () {
    Queue::fake();
    
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('recurring-transfer'), [
        'start_date' => '2026-02-01',
        'frequency' => 7,
        'amount' => 50.00,
    ]);

    // Verify the job was pushed to the queue (not run synchronously)
    Queue::assertPushed(RecurringTransferJob::class);
    
    // Verify it's the correct job class
    Queue::assertPushed(function (RecurringTransferJob $job) {
        return $job instanceof RecurringTransferJob;
    });
});


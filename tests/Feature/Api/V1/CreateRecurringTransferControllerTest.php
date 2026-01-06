<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\CreateRecurringTransferController;
use App\Models\RecurringTransfer;
use App\Models\User;
use Carbon\Carbon;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;

test('authenticated user can create recurring transfer via api', function () {
    $user = User::factory()->create();

    actingAs($user);

    $response = postJson(action(CreateRecurringTransferController::class), [
        'start_date' => Carbon::tomorrow()->format('Y-m-d'),
        'stop_date' => Carbon::tomorrow()->addMonth()->format('Y-m-d'),
        'frequency' => 7,
        'amount' => 50.00,
        'reason' => 'Weekly savings',
    ])
        ->assertCreated()
        ->assertJsonStructure([
            'id',
            'user_id',
            'start_date',
            'stop_date',
            'frequency',
            'amount',
            'reason',
            'created_at',
            'updated_at',
        ]);

    assertDatabaseHas('recurring_transfers', [
        'user_id' => $user->id,
        'frequency' => 7,
        'amount' => 50.00,
        'reason' => 'Weekly savings',
    ]);

    expect($response->json('user_id'))->toBe($user->id);
    expect($response->json('frequency'))->toBe(7);
    expect($response->json('amount'))->toEqual(50.0);
});

test('api creates recurring transfer without optional fields', function () {
    $user = User::factory()->create();

    actingAs($user);

    $response = postJson(action(CreateRecurringTransferController::class), [
        'start_date' => Carbon::tomorrow()->format('Y-m-d'),
        'frequency' => 30,
        'amount' => 100.50,
    ])
        ->assertCreated();

    expect($response->json('stop_date'))->toBeNull();
    expect($response->json('reason'))->toBeNull();
    expect($response->json('frequency'))->toBe(30);
    expect($response->json('amount'))->toBe(100.50);
});

test('api validates required fields', function () {
    $user = User::factory()->create();

    actingAs($user);

    postJson(action(CreateRecurringTransferController::class), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['start_date', 'frequency', 'amount']);
});

test('api validates start_date must be today or future', function () {
    $user = User::factory()->create();

    actingAs($user);

    postJson(action(CreateRecurringTransferController::class), [
        'start_date' => Carbon::yesterday()->format('Y-m-d'),
        'frequency' => 7,
        'amount' => 50.00,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['start_date']);
});

test('api validates stop_date must be after start_date', function () {
    $user = User::factory()->create();

    actingAs($user);

    postJson(action(CreateRecurringTransferController::class), [
        'start_date' => Carbon::tomorrow()->format('Y-m-d'),
        'stop_date' => Carbon::today()->format('Y-m-d'),
        'frequency' => 7,
        'amount' => 50.00,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['stop_date']);
});

test('api validates frequency must be at least 1', function () {
    $user = User::factory()->create();

    actingAs($user);

    postJson(action(CreateRecurringTransferController::class), [
        'start_date' => Carbon::tomorrow()->format('Y-m-d'),
        'frequency' => 0,
        'amount' => 50.00,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['frequency']);
});

test('api validates amount must be at least 0.01', function () {
    $user = User::factory()->create();

    actingAs($user);

    postJson(action(CreateRecurringTransferController::class), [
        'start_date' => Carbon::tomorrow()->format('Y-m-d'),
        'frequency' => 7,
        'amount' => 0,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['amount']);
});

test('unauthenticated user cannot create recurring transfer via api', function () {
    postJson(action(CreateRecurringTransferController::class), [
        'start_date' => Carbon::tomorrow()->format('Y-m-d'),
        'frequency' => 7,
        'amount' => 50.00,
    ])
        ->assertUnauthorized();
});

test('api returns correct resource structure', function () {
    $user = User::factory()->create();

    actingAs($user);

    $startDate = Carbon::tomorrow();
    $stopDate = Carbon::tomorrow()->addMonth();

    $response = postJson(action(CreateRecurringTransferController::class), [
        'start_date' => $startDate->format('Y-m-d'),
        'stop_date' => $stopDate->format('Y-m-d'),
        'frequency' => 7,
        'amount' => 50.00,
        'reason' => 'Test reason',
    ]);

    expect($response->json('start_date'))->toBe($startDate->format('Y-m-d'));
    expect($response->json('stop_date'))->toBe($stopDate->format('Y-m-d'));
    expect($response->json('created_at'))->toBeString();
    expect($response->json('updated_at'))->toBeString();
});

test('api creates multiple recurring transfers for same user', function () {
    $user = User::factory()->create();

    actingAs($user);

    postJson(action(CreateRecurringTransferController::class), [
        'start_date' => Carbon::tomorrow()->format('Y-m-d'),
        'frequency' => 7,
        'amount' => 50.00,
    ])
        ->assertCreated();

    postJson(action(CreateRecurringTransferController::class), [
        'start_date' => Carbon::tomorrow()->format('Y-m-d'),
        'frequency' => 14,
        'amount' => 100.00,
    ])
        ->assertCreated();

    expect(RecurringTransfer::where('user_id', $user->id)->count())->toBe(2);
});

test('api recurring transfer belongs to authenticated user', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    actingAs($user1);

    $response = postJson(action(CreateRecurringTransferController::class), [
        'start_date' => Carbon::tomorrow()->format('Y-m-d'),
        'frequency' => 7,
        'amount' => 50.00,
    ]);

    expect($response->json('user_id'))->toBe($user1->id);
    expect($response->json('user_id'))->not->toBe($user2->id);
});

test('api validates reason max length', function () {
    $user = User::factory()->create();

    actingAs($user);

    postJson(action(CreateRecurringTransferController::class), [
        'start_date' => Carbon::tomorrow()->format('Y-m-d'),
        'frequency' => 7,
        'amount' => 50.00,
        'reason' => str_repeat('a', 256),
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['reason']);
});

test('api accepts decimal amounts', function () {
    $user = User::factory()->create();

    actingAs($user);

    $response = postJson(action(CreateRecurringTransferController::class), [
        'start_date' => Carbon::tomorrow()->format('Y-m-d'),
        'frequency' => 7,
        'amount' => 123.45,
    ])
        ->assertCreated();

    expect($response->json('amount'))->toBe(123.45);
});

test('api validates frequency must be integer', function () {
    $user = User::factory()->create();

    actingAs($user);

    postJson(action(CreateRecurringTransferController::class), [
        'start_date' => Carbon::tomorrow()->format('Y-m-d'),
        'frequency' => 'not-an-integer',
        'amount' => 50.00,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['frequency']);
});

test('api validates start_date must be valid date', function () {
    $user = User::factory()->create();

    actingAs($user);

    postJson(action(CreateRecurringTransferController::class), [
        'start_date' => 'not-a-date',
        'frequency' => 7,
        'amount' => 50.00,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['start_date']);
});

test('api accepts start_date as today', function () {
    $user = User::factory()->create();

    actingAs($user);

    postJson(action(CreateRecurringTransferController::class), [
        'start_date' => Carbon::today()->format('Y-m-d'),
        'frequency' => 7,
        'amount' => 50.00,
    ])
        ->assertCreated();
});

test('api returns 201 status code on successful creation', function () {
    $user = User::factory()->create();

    actingAs($user);

    postJson(action(CreateRecurringTransferController::class), [
        'start_date' => Carbon::tomorrow()->format('Y-m-d'),
        'frequency' => 7,
        'amount' => 50.00,
    ])
        ->assertStatus(201);
});


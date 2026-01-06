<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\ShowRecurringTransferController;
use App\Models\RecurringTransfer;
use App\Models\User;
use Carbon\Carbon;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

test('authenticated user can get their specific recurring transfer via api', function () {
    $user = User::factory()->create();
    
    $transfer = RecurringTransfer::factory()->for($user)->create([
        'start_date' => Carbon::tomorrow(),
        'stop_date' => null,
        'frequency' => 7,
        'amount' => 50.00,
        'reason' => 'Test transfer',
    ]);

    actingAs($user);

    $response = getJson(action(ShowRecurringTransferController::class, ['id' => $transfer->id]))
        ->assertOk()
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

    expect($response->json('id'))->toBe($transfer->id);
    expect($response->json('amount'))->toEqual(50.0);
    expect($response->json('frequency'))->toBe(7);
});

test('unauthenticated user cannot get recurring transfer via api', function () {
    $user = User::factory()->create();
    $transfer = RecurringTransfer::factory()->for($user)->create();

    getJson(action(ShowRecurringTransferController::class, ['id' => $transfer->id]))
        ->assertUnauthorized();
});

test('user cannot get another users recurring transfer via api', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    
    $transfer = RecurringTransfer::factory()->for($user1)->create();

    actingAs($user2);

    getJson(action(ShowRecurringTransferController::class, ['id' => $transfer->id]))
        ->assertNotFound()
        ->assertJson([
            'message' => 'Recurring transfer not found or unauthorized.',
        ]);
});

test('api returns 404 for non-existent recurring transfer', function () {
    $user = User::factory()->create();

    actingAs($user);

    getJson(action(ShowRecurringTransferController::class, ['id' => 99999]))
        ->assertNotFound()
        ->assertJson([
            'message' => 'Recurring transfer not found or unauthorized.',
        ]);
});

test('api returns 404 for soft deleted recurring transfer', function () {
    $user = User::factory()->create();
    $transfer = RecurringTransfer::factory()->for($user)->create();
    
    $transfer->delete();

    actingAs($user);

    getJson(action(ShowRecurringTransferController::class, ['id' => $transfer->id]))
        ->assertNotFound();
});

test('api returns correct data types for single transfer', function () {
    $user = User::factory()->create();

    $transfer = RecurringTransfer::factory()->for($user)->create([
        'stop_date' => null,
        'frequency' => 7,
        'amount' => 123.45,
    ]);

    actingAs($user);

    $response = getJson(action(ShowRecurringTransferController::class, ['id' => $transfer->id]));

    expect($response->json('id'))->toBeInt();
    expect($response->json('user_id'))->toBeInt();
    expect($response->json('frequency'))->toBeInt();
    expect($response->json('amount'))->toBeFloat();
});

test('api handles null optional fields correctly for single transfer', function () {
    $user = User::factory()->create();

    $transfer = RecurringTransfer::factory()->for($user)->create([
        'start_date' => Carbon::tomorrow(),
        'stop_date' => null,
        'frequency' => 7,
        'amount' => 50.00,
        'reason' => null,
    ]);

    actingAs($user);

    $response = getJson(action(ShowRecurringTransferController::class, ['id' => $transfer->id]));

    expect($response->json('stop_date'))->toBeNull();
    expect($response->json('reason'))->toBeNull();
});

test('api returns formatted dates for single transfer', function () {
    $user = User::factory()->create();

    $startDate = Carbon::tomorrow();
    $stopDate = Carbon::tomorrow()->addMonth();

    $transfer = RecurringTransfer::factory()->for($user)->create([
        'start_date' => $startDate,
        'stop_date' => $stopDate,
    ]);

    actingAs($user);

    $response = getJson(action(ShowRecurringTransferController::class, ['id' => $transfer->id]));

    expect($response->json('start_date'))->toBe($startDate->format('Y-m-d'));
    expect($response->json('stop_date'))->toBe($stopDate->format('Y-m-d'));
});

test('api validates id parameter is integer for show', function () {
    $user = User::factory()->create();

    actingAs($user);

    getJson(action(ShowRecurringTransferController::class, ['id' => 'invalid']))
        ->assertNotFound()
        ->assertJson([
            'message' => 'Recurring transfer not found or unauthorized.',
        ]);
});

test('api returns 200 status code for successful get', function () {
    $user = User::factory()->create();
    $transfer = RecurringTransfer::factory()->for($user)->create(['stop_date' => null]);

    actingAs($user);

    getJson(action(ShowRecurringTransferController::class, ['id' => $transfer->id]))
        ->assertStatus(200);
});

test('api returns complete transfer data including timestamps', function () {
    $user = User::factory()->create();
    $transfer = RecurringTransfer::factory()->for($user)->create(['stop_date' => null]);

    actingAs($user);

    $response = getJson(action(ShowRecurringTransferController::class, ['id' => $transfer->id]));

    expect($response->json('created_at'))->toBeString();
    expect($response->json('updated_at'))->toBeString();
    expect($response->json('created_at'))->toContain('T'); // ISO8601 format
});

test('api get returns same data as in list', function () {
    $user = User::factory()->create();
    
    $transfer = RecurringTransfer::factory()->for($user)->create([
        'start_date' => Carbon::tomorrow(),
        'stop_date' => Carbon::tomorrow()->addMonth(),
        'frequency' => 7,
        'amount' => 50.00,
        'reason' => 'Test',
    ]);

    actingAs($user);

    $showResponse = getJson(action(ShowRecurringTransferController::class, ['id' => $transfer->id]));

    expect($showResponse->json('id'))->toBe($transfer->id);
    expect($showResponse->json('user_id'))->toBe($user->id);
    expect($showResponse->json('frequency'))->toBe(7);
    expect($showResponse->json('amount'))->toEqual(50.0);
});

test('api get works with string id parameter', function () {
    $user = User::factory()->create();
    $transfer = RecurringTransfer::factory()->for($user)->create(['stop_date' => null]);

    actingAs($user);

    getJson(action(ShowRecurringTransferController::class, ['id' => (string) $transfer->id]))
        ->assertOk();
});

test('api returns user_id matching authenticated user', function () {
    $user = User::factory()->create();
    $transfer = RecurringTransfer::factory()->for($user)->create(['stop_date' => null]);

    actingAs($user);

    $response = getJson(action(ShowRecurringTransferController::class, ['id' => $transfer->id]));

    expect($response->json('user_id'))->toBe($user->id);
});

test('api get includes all required fields', function () {
    $user = User::factory()->create();
    $transfer = RecurringTransfer::factory()->for($user)->create(['stop_date' => null]);

    actingAs($user);

    $response = getJson(action(ShowRecurringTransferController::class, ['id' => $transfer->id]));

    expect($response->json())->toHaveKeys([
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
});


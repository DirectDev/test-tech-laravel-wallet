<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\ListRecurringTransfersController;
use App\Models\RecurringTransfer;
use App\Models\User;
use Carbon\Carbon;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

test('authenticated user can list their recurring transfers via api', function () {
    $user = User::factory()->create();
    
    $transfer1 = RecurringTransfer::factory()->for($user)->create([
        'start_date' => Carbon::tomorrow(),
        'stop_date' => null,
        'frequency' => 7,
        'amount' => 50.00,
    ]);

    $transfer2 = RecurringTransfer::factory()->for($user)->create([
        'start_date' => Carbon::tomorrow(),
        'stop_date' => null,
        'frequency' => 14,
        'amount' => 100.00,
    ]);

    actingAs($user);

    $response = getJson(action(ListRecurringTransfersController::class))
        ->assertOk()
        ->assertJsonCount(2);

    expect($response->json())->toHaveCount(2);
});

test('api returns empty array when user has no recurring transfers', function () {
    $user = User::factory()->create();

    actingAs($user);

    getJson(action(ListRecurringTransfersController::class))
        ->assertOk()
        ->assertJsonCount(0);
});

test('unauthenticated user cannot list recurring transfers via api', function () {
    getJson(action(ListRecurringTransfersController::class))
        ->assertUnauthorized();
});

test('api returns only authenticated users recurring transfers', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    RecurringTransfer::factory()->for($user1)->create(['stop_date' => null]);
    RecurringTransfer::factory()->for($user1)->create(['stop_date' => null]);
    RecurringTransfer::factory()->for($user2)->create(['stop_date' => null]);

    actingAs($user1);

    getJson(action(ListRecurringTransfersController::class))
        ->assertOk()
        ->assertJsonCount(2);
});

test('api returns recurring transfers in descending order by id', function () {
    $user = User::factory()->create();

    $transfer1 = RecurringTransfer::factory()->for($user)->create(['amount' => 10.00, 'stop_date' => null]);
    $transfer2 = RecurringTransfer::factory()->for($user)->create(['amount' => 20.00, 'stop_date' => null]);
    $transfer3 = RecurringTransfer::factory()->for($user)->create(['amount' => 30.00, 'stop_date' => null]);

    actingAs($user);

    $response = getJson(action(ListRecurringTransfersController::class));

    $data = $response->json();
    expect($data[0]['id'])->toBe($transfer3->id);
    expect($data[1]['id'])->toBe($transfer2->id);
    expect($data[2]['id'])->toBe($transfer1->id);
});

test('api returns correct resource structure for recurring transfers list', function () {
    $user = User::factory()->create();

    RecurringTransfer::factory()->for($user)->create([
        'start_date' => Carbon::tomorrow(),
        'stop_date' => Carbon::tomorrow()->addMonth(),
        'frequency' => 7,
        'amount' => 50.00,
        'reason' => 'Test reason',
    ]);

    actingAs($user);

    getJson(action(ListRecurringTransfersController::class))
        ->assertOk()
        ->assertJsonStructure([
            '*' => [
                'id',
                'user_id',
                'start_date',
                'stop_date',
                'frequency',
                'amount',
                'reason',
                'created_at',
                'updated_at',
            ],
        ]);
});

test('api list includes soft deleted transfers', function () {
    $user = User::factory()->create();

    $transfer1 = RecurringTransfer::factory()->for($user)->create();
    $transfer2 = RecurringTransfer::factory()->for($user)->create();
    
    $transfer1->delete();

    actingAs($user);

    getJson(action(ListRecurringTransfersController::class))
        ->assertOk()
        ->assertJsonCount(1);
});

test('api list returns correct data types', function () {
    $user = User::factory()->create();

    RecurringTransfer::factory()->for($user)->create([
        'stop_date' => null,
        'frequency' => 7,
        'amount' => 50.50,
    ]);

    actingAs($user);

    $response = getJson(action(ListRecurringTransfersController::class));

    $data = $response->json()[0];
    expect($data['id'])->toBeInt();
    expect($data['user_id'])->toBeInt();
    expect($data['frequency'])->toBeInt();
    expect($data['amount'])->toBeFloat();
});

test('api list handles null optional fields correctly', function () {
    $user = User::factory()->create();

    RecurringTransfer::factory()->for($user)->create([
        'start_date' => Carbon::tomorrow(),
        'stop_date' => null,
        'frequency' => 7,
        'amount' => 50.00,
        'reason' => null,
    ]);

    actingAs($user);

    $response = getJson(action(ListRecurringTransfersController::class));

    $data = $response->json()[0];
    expect($data['stop_date'])->toBeNull();
    expect($data['reason'])->toBeNull();
});

test('api list returns formatted dates', function () {
    $user = User::factory()->create();

    $startDate = Carbon::tomorrow();
    $stopDate = Carbon::tomorrow()->addMonth();

    RecurringTransfer::factory()->for($user)->create([
        'start_date' => $startDate,
        'stop_date' => $stopDate,
    ]);

    actingAs($user);

    $response = getJson(action(ListRecurringTransfersController::class));

    $data = $response->json()[0];
    expect($data['start_date'])->toBe($startDate->format('Y-m-d'));
    expect($data['stop_date'])->toBe($stopDate->format('Y-m-d'));
});

test('api list works with large number of transfers', function () {
    $user = User::factory()->create();

    RecurringTransfer::factory()->for($user)->count(50)->create(['stop_date' => null]);

    actingAs($user);

    getJson(action(ListRecurringTransfersController::class))
        ->assertOk()
        ->assertJsonCount(50);
});

test('api list returns 200 status code', function () {
    $user = User::factory()->create();

    actingAs($user);

    getJson(action(ListRecurringTransfersController::class))
        ->assertStatus(200);
});

test('api list maintains data isolation between users', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $transfer1 = RecurringTransfer::factory()->for($user1)->create(['amount' => 100.00, 'stop_date' => null]);
    $transfer2 = RecurringTransfer::factory()->for($user2)->create(['amount' => 200.00, 'stop_date' => null]);

    actingAs($user1);

    $response = getJson(action(ListRecurringTransfersController::class));

    expect($response->json())->toHaveCount(1);
    expect($response->json()[0]['amount'])->toEqual(100.0);
    expect($response->json()[0]['id'])->toBe($transfer1->id);
});


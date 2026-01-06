<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\DeleteRecurringTransferController;
use App\Models\RecurringTransfer;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\deleteJson;

test('authenticated user can delete their recurring transfer via api', function () {
    $user = User::factory()->create();
    $transfer = RecurringTransfer::factory()->for($user)->create();

    actingAs($user);

    deleteJson(action(DeleteRecurringTransferController::class, ['id' => $transfer->id]))
        ->assertNoContent();

    expect(RecurringTransfer::find($transfer->id))->toBeNull();
    expect(RecurringTransfer::withTrashed()->find($transfer->id))->not->toBeNull();
});

test('unauthenticated user cannot delete recurring transfer via api', function () {
    $user = User::factory()->create();
    $transfer = RecurringTransfer::factory()->for($user)->create();

    deleteJson(action(DeleteRecurringTransferController::class, ['id' => $transfer->id]))
        ->assertUnauthorized();

    expect(RecurringTransfer::find($transfer->id))->not->toBeNull();
});

test('user cannot delete another users recurring transfer via api', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $transfer = RecurringTransfer::factory()->for($user1)->create();

    actingAs($user2);

    deleteJson(action(DeleteRecurringTransferController::class, ['id' => $transfer->id]))
        ->assertNotFound()
        ->assertJson([
            'message' => 'Recurring transfer not found or unauthorized.',
        ]);

    expect(RecurringTransfer::find($transfer->id))->not->toBeNull();
});

test('api returns 404 for non-existent recurring transfer', function () {
    $user = User::factory()->create();

    actingAs($user);

    deleteJson(action(DeleteRecurringTransferController::class, ['id' => 99999]))
        ->assertNotFound()
        ->assertJson([
            'message' => 'Recurring transfer not found or unauthorized.',
        ]);
});

test('api soft deletes recurring transfer', function () {
    $user = User::factory()->create();
    $transfer = RecurringTransfer::factory()->for($user)->create();

    actingAs($user);

    deleteJson(action(DeleteRecurringTransferController::class, ['id' => $transfer->id]))
        ->assertNoContent();

    expect(RecurringTransfer::withTrashed()->find($transfer->id)->deleted_at)->not->toBeNull();
});

test('api can delete multiple recurring transfers sequentially', function () {
    $user = User::factory()->create();
    $transfer1 = RecurringTransfer::factory()->for($user)->create();
    $transfer2 = RecurringTransfer::factory()->for($user)->create();

    actingAs($user);

    deleteJson(action(DeleteRecurringTransferController::class, ['id' => $transfer1->id]))
        ->assertNoContent();

    deleteJson(action(DeleteRecurringTransferController::class, ['id' => $transfer2->id]))
        ->assertNoContent();

    expect(RecurringTransfer::count())->toBe(0);
    expect(RecurringTransfer::withTrashed()->count())->toBe(2);
});

test('api deleting already deleted transfer returns 404', function () {
    $user = User::factory()->create();
    $transfer = RecurringTransfer::factory()->for($user)->create();

    actingAs($user);

    deleteJson(action(DeleteRecurringTransferController::class, ['id' => $transfer->id]))
        ->assertNoContent();

    deleteJson(action(DeleteRecurringTransferController::class, ['id' => $transfer->id]))
        ->assertNotFound();
});

test('api returns 204 status code on successful deletion', function () {
    $user = User::factory()->create();
    $transfer = RecurringTransfer::factory()->for($user)->create();

    actingAs($user);

    deleteJson(action(DeleteRecurringTransferController::class, ['id' => $transfer->id]))
        ->assertStatus(204);
});

test('api deletion maintains data integrity for other users', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $transfer1 = RecurringTransfer::factory()->for($user1)->create();
    $transfer2 = RecurringTransfer::factory()->for($user2)->create();

    actingAs($user1);

    deleteJson(action(DeleteRecurringTransferController::class, ['id' => $transfer1->id]))
        ->assertNoContent();

    expect(RecurringTransfer::find($transfer2->id))->not->toBeNull();
    expect(RecurringTransfer::count())->toBe(1);
});

test('api validates id parameter is integer', function () {
    $user = User::factory()->create();

    actingAs($user);

    deleteJson(action(DeleteRecurringTransferController::class, ['id' => 'invalid']))
        ->assertNotFound();
});

test('api deletion preserves soft deleted data', function () {
    $user = User::factory()->create();
    $transfer = RecurringTransfer::factory()->for($user)->create([
        'amount' => 123.45,
        'frequency' => 7,
        'reason' => 'Test reason',
    ]);

    actingAs($user);

    deleteJson(action(DeleteRecurringTransferController::class, ['id' => $transfer->id]))
        ->assertNoContent();

    $deletedTransfer = RecurringTransfer::withTrashed()->find($transfer->id);
    expect($deletedTransfer->amount)->toBe(123.45);
    expect($deletedTransfer->frequency)->toBe(7);
    expect($deletedTransfer->reason)->toBe('Test reason');
});

test('api user can only delete their own transfers', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    
    $transfer1 = RecurringTransfer::factory()->for($user1)->create();
    $transfer2 = RecurringTransfer::factory()->for($user1)->create();
    $transfer3 = RecurringTransfer::factory()->for($user2)->create();

    actingAs($user1);

    // Can delete own transfers
    deleteJson(action(DeleteRecurringTransferController::class, ['id' => $transfer1->id]))
        ->assertNoContent();
    
    deleteJson(action(DeleteRecurringTransferController::class, ['id' => $transfer2->id]))
        ->assertNoContent();

    // Cannot delete other user's transfer
    deleteJson(action(DeleteRecurringTransferController::class, ['id' => $transfer3->id]))
        ->assertNotFound();

    expect(RecurringTransfer::withTrashed()->where('user_id', $user1->id)->count())->toBe(2);
    expect(RecurringTransfer::where('user_id', $user2->id)->count())->toBe(1);
});


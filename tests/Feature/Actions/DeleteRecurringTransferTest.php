<?php

declare(strict_types=1);

use App\Actions\DeleteRecurringTransfer;
use App\Models\RecurringTransfer;
use App\Models\User;

test('delete recurring transfer soft deletes the transfer', function () {
    $user = User::factory()->create();
    $transfer = RecurringTransfer::factory()->for($user)->create();
    $action = new DeleteRecurringTransfer();

    $result = $action->execute(
        user: $user,
        recurringTransferId: $transfer->id
    );

    expect($result)->toBeTrue();
    expect(RecurringTransfer::find($transfer->id))->toBeNull();
    expect(RecurringTransfer::withTrashed()->find($transfer->id))->not->toBeNull();
});

test('delete recurring transfer only deletes transfers owned by the user', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    
    $transfer1 = RecurringTransfer::factory()->for($user1)->create();
    $transfer2 = RecurringTransfer::factory()->for($user2)->create();
    
    $action = new DeleteRecurringTransfer();

    try {
        $action->execute(
            user: $user1,
            recurringTransferId: $transfer2->id
        );
        $this->fail('Expected exception was not thrown');
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        expect(true)->toBeTrue();
    }

    expect(RecurringTransfer::find($transfer2->id))->not->toBeNull();
});

test('delete recurring transfer throws exception for non-existent transfer', function () {
    $user = User::factory()->create();
    $action = new DeleteRecurringTransfer();

    $action->execute(
        user: $user,
        recurringTransferId: 99999
    );
})->throws(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

test('delete recurring transfer returns true on successful deletion', function () {
    $user = User::factory()->create();
    $transfer = RecurringTransfer::factory()->for($user)->create();
    $action = new DeleteRecurringTransfer();

    $result = $action->execute(
        user: $user,
        recurringTransferId: $transfer->id
    );

    expect($result)->toBeTrue();
});

test('deleted recurring transfer can be queried with trashed', function () {
    $user = User::factory()->create();
    $transfer = RecurringTransfer::factory()->for($user)->create([
        'amount' => 100.00,
        'frequency' => 30,
    ]);
    $action = new DeleteRecurringTransfer();

    $action->execute(
        user: $user,
        recurringTransferId: $transfer->id
    );

    $deletedTransfer = RecurringTransfer::withTrashed()->find($transfer->id);
    
    expect($deletedTransfer)
        ->not->toBeNull()
        ->trashed()->toBeTrue()
        ->amount->toBe(100.0)
        ->frequency->toBe(30);
});

test('user can delete multiple recurring transfers', function () {
    $user = User::factory()->create();
    $transfer1 = RecurringTransfer::factory()->for($user)->create();
    $transfer2 = RecurringTransfer::factory()->for($user)->create();
    $transfer3 = RecurringTransfer::factory()->for($user)->create();
    
    $action = new DeleteRecurringTransfer();

    $action->execute($user, $transfer1->id);
    $action->execute($user, $transfer3->id);

    expect(RecurringTransfer::where('user_id', $user->id)->count())->toBe(1);
    expect(RecurringTransfer::where('user_id', $user->id)->first()->id)->toBe($transfer2->id);
    expect(RecurringTransfer::withTrashed()->where('user_id', $user->id)->count())->toBe(3);
});


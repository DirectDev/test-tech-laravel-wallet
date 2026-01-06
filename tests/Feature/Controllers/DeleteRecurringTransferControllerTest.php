<?php

declare(strict_types=1);

use App\Models\RecurringTransfer;
use App\Models\User;

test('authenticated user can delete their recurring transfer', function () {
    $user = User::factory()->create();
    $transfer = RecurringTransfer::factory()->for($user)->create();

    $response = $this->actingAs($user)->delete(route('recurring-transfer.delete', $transfer->id));

    $response
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('recurring-transfer-delete-status', 'success');

    expect(RecurringTransfer::find($transfer->id))->toBeNull();
    expect(RecurringTransfer::withTrashed()->find($transfer->id))->not->toBeNull();
});

test('unauthenticated user cannot delete recurring transfer', function () {
    $user = User::factory()->create();
    $transfer = RecurringTransfer::factory()->for($user)->create();

    $response = $this->delete(route('recurring-transfer.delete', $transfer->id));

    $response->assertRedirect(route('login'));
    expect(RecurringTransfer::find($transfer->id))->not->toBeNull();
});

test('user cannot delete another users recurring transfer', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    
    $transfer = RecurringTransfer::factory()->for($user2)->create();

    $response = $this->actingAs($user1)->delete(route('recurring-transfer.delete', $transfer->id));

    $response
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('recurring-transfer-delete-status', 'error');

    expect(RecurringTransfer::find($transfer->id))->not->toBeNull();
});

test('deleting non-existent recurring transfer returns error', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->delete(route('recurring-transfer.delete', 99999));

    $response
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('recurring-transfer-delete-status', 'error');
});

test('deleted recurring transfer does not appear in default listing', function () {
    $user = User::factory()->create();
    
    $transfer1 = RecurringTransfer::factory()->for($user)->create([
        'amount' => 100.00,
    ]);
    
    $transfer2 = RecurringTransfer::factory()->for($user)->create([
        'amount' => 200.00,
    ]);

    $this->actingAs($user)->delete(route('recurring-transfer.delete', $transfer1->id));

    $transfers = $user->recurringTransfers;
    
    expect($transfers)
        ->toHaveCount(1)
        ->first()->id->toBe($transfer2->id);
});

test('dashboard displays success message after deletion', function () {
    $user = User::factory()->create();
    $transfer = RecurringTransfer::factory()->for($user)->create();

    $this->actingAs($user)->delete(route('recurring-transfer.delete', $transfer->id));

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response
        ->assertOk()
        ->assertSeeText('Recurring transfer deleted successfully!');
});

test('dashboard displays error message when deletion fails', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->withSession(['recurring-transfer-delete-status' => 'error'])
        ->get(route('dashboard'));

    $response
        ->assertOk()
        ->assertSeeText('Failed to delete recurring transfer.');
});

test('delete button is present for each recurring transfer', function () {
    $user = User::factory()->create();
    
    RecurringTransfer::factory()->count(3)->for($user)->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    
    // Should see 3 delete buttons (one for each transfer)
    // The @method('DELETE') directive compiles to: <input type="hidden" name="_method" value="DELETE">
    expect(substr_count($response->content(), 'name="_method"'))->toBeGreaterThanOrEqual(3);
    expect(substr_count($response->content(), 'value="DELETE"'))->toBeGreaterThanOrEqual(3);
});

test('delete uses DELETE HTTP method', function () {
    $user = User::factory()->create();
    $transfer = RecurringTransfer::factory()->for($user)->create();

    // Try with POST instead of DELETE
    $response = $this->actingAs($user)->post(route('recurring-transfer.delete', $transfer->id));

    $response->assertStatus(405); // Method Not Allowed
});

test('deleted recurring transfer maintains its data in soft delete', function () {
    $user = User::factory()->create();
    $transfer = RecurringTransfer::factory()->for($user)->create([
        'start_date' => '2026-01-15',
        'stop_date' => '2026-12-31',
        'frequency' => 30,
        'amount' => 150.75,
        'reason' => 'Monthly payment',
    ]);

    $this->actingAs($user)->delete(route('recurring-transfer.delete', $transfer->id));

    $deletedTransfer = RecurringTransfer::withTrashed()->find($transfer->id);

    expect($deletedTransfer)
        ->not->toBeNull()
        ->start_date->format('Y-m-d')->toBe('2026-01-15')
        ->stop_date->format('Y-m-d')->toBe('2026-12-31')
        ->frequency->toBe(30)
        ->amount->toBe(150.75)
        ->reason->toBe('Monthly payment');
});


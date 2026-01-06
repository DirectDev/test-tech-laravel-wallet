<?php

declare(strict_types=1);

use App\Models\RecurringTransfer;
use App\Models\User;

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


<?php

declare(strict_types=1);

use App\Models\RecurringTransfer;
use App\Models\User;
use App\Models\Wallet;

use function Pest\Laravel\actingAs;

test('dashboard page is displayed', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->richChillGuy()->for($user)->create();

    $response = actingAs($user)->get('/');

    $response
        ->assertOk()
        ->assertSeeTextInOrder([
            __('Balance'),
            Number::currencyCents($wallet->balance),
            'Transactions history',
            'Just a rich chill guy',
        ]);
});

test('send money to a friend', function () {
    $user = User::factory()->create();
    Wallet::factory()->richChillGuy()->for($user)->create();
    $recipient = User::factory()->create();
    Wallet::factory()->for($recipient)->create();

    $response = actingAs($user)->post('/send-money', [
        'recipient_email' => $recipient->email,
        'amount' => 10, // In euros, not cents
        'reason' => 'Just a chill guy gift',
    ]);

    $response
        ->assertRedirect('/')
        ->assertSessionHas('money-sent-status', 'success')
        ->assertSessionHas('money-sent-recipient-name', $recipient->name)
        ->assertSessionHas('money-sent-amount', 10_00);

    actingAs($user)->get('/')
        ->assertSeeTextInOrder([
            __('Balance'),
            Number::currencyCents(1_000_000 - 10_00),
            'Transactions history',
            'Just a chill guy gift',
            Number::currencyCents(-10_00),
            'Just a rich chill guy',
            Number::currencyCents(1_000_000),
        ]);
});

test('cannot send money to a friend with insufficient balance', function () {
    $user = User::factory()->create();
    Wallet::factory()->for($user)->create();
    $recipient = User::factory()->create();
    Wallet::factory()->for($recipient)->create();

    $response = actingAs($user)->post('/send-money', [
        'recipient_email' => $recipient->email,
        'amount' => 10, // In euros, not cents
        'reason' => 'Just a chill guy gift',
    ]);

    $response
        ->assertRedirect('/')
        ->assertSessionHas('money-sent-status', 'insufficient-balance')
        ->assertSessionHas('money-sent-recipient-name', $recipient->name)
        ->assertSessionHas('money-sent-amount', 10_00);
});

test('dashboard displays recurring transfers when they exist', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->for($user)->create();

    RecurringTransfer::factory()->for($user)->create([
        'start_date' => '2026-01-15',
        'stop_date' => '2026-12-31',
        'frequency' => 30,
        'amount' => 100.50,
        'reason' => 'Monthly subscription',
    ]);

    RecurringTransfer::factory()->for($user)->create([
        'start_date' => '2026-02-01',
        'stop_date' => null,
        'frequency' => 7,
        'amount' => 25.00,
        'reason' => null,
    ]);

    $response = actingAs($user)->get('/');

    $response
        ->assertOk()
        ->assertSeeTextInOrder([
            'My Recurring Transfers',
            '2026-02-01',
            '7',
            '€25.00',
            '2026-01-15',
            '2026-12-31',
            '30',
            '€100.50',
            'Monthly subscription',
        ]);
});

test('dashboard shows no recurring transfers message when user has none', function () {
    $user = User::factory()->create();
    Wallet::factory()->for($user)->create();

    $response = actingAs($user)->get('/');

    $response
        ->assertOk()
        ->assertSeeText('My Recurring Transfers')
        ->assertSeeText('No recurring transfers yet.');
});

test('dashboard displays recurring transfer status badges correctly', function () {
    $user = User::factory()->create();
    Wallet::factory()->for($user)->create();

    // Active transfer (started, no end date)
    RecurringTransfer::factory()->for($user)->create([
        'start_date' => now()->subDay(),
        'stop_date' => null,
        'frequency' => 30,
        'amount' => 100.00,
    ]);

    // Scheduled transfer (future start date)
    RecurringTransfer::factory()->for($user)->create([
        'start_date' => now()->addWeek(),
        'stop_date' => null,
        'frequency' => 7,
        'amount' => 50.00,
    ]);

    $response = actingAs($user)->get('/');

    $response
        ->assertOk()
        ->assertSeeText('Active')
        ->assertSeeText('Scheduled');
});

test('recurring transfers are ordered by id descending', function () {
    $user = User::factory()->create();
    Wallet::factory()->for($user)->create();

    $transfer1 = RecurringTransfer::factory()->for($user)->create([
        'amount' => 100.00,
        'frequency' => 30,
    ]);

    $transfer2 = RecurringTransfer::factory()->for($user)->create([
        'amount' => 200.00,
        'frequency' => 15,
    ]);

    $transfer3 = RecurringTransfer::factory()->for($user)->create([
        'amount' => 300.00,
        'frequency' => 7,
    ]);

    $response = actingAs($user)->get('/');

    $response
        ->assertOk()
        ->assertSeeTextInOrder([
            '€300.00',
            '€200.00',
            '€100.00',
        ]);
});

test('user only sees their own recurring transfers', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    Wallet::factory()->for($user1)->create();
    Wallet::factory()->for($user2)->create();

    RecurringTransfer::factory()->for($user1)->create([
        'amount' => 100.00,
        'reason' => 'User 1 transfer',
    ]);

    RecurringTransfer::factory()->for($user2)->create([
        'amount' => 200.00,
        'reason' => 'User 2 transfer',
    ]);

    $response = actingAs($user1)->get('/');

    $response
        ->assertOk()
        ->assertSeeText('User 1 transfer')
        ->assertDontSeeText('User 2 transfer')
        ->assertSeeText('€100.00')
        ->assertDontSeeText('€200.00');
});

test('dashboard displays recurring transfer form', function () {
    $user = User::factory()->create();
    Wallet::factory()->for($user)->create();

    $response = actingAs($user)->get('/');

    $response
        ->assertOk()
        ->assertSeeText('Recurring Transfers')
        ->assertSee('name="start_date"', false)
        ->assertSee('name="stop_date"', false)
        ->assertSee('name="frequency"', false)
        ->assertSee('name="amount"', false)
        ->assertSee('name="reason"', false)
        ->assertSee('action="' . route('recurring-transfer') . '"', false);
});

test('can create recurring transfer from dashboard', function () {
    $user = User::factory()->create();
    Wallet::factory()->for($user)->create();

    $response = actingAs($user)->post('/recurring-transfer', [
        'start_date' => '2026-01-15',
        'stop_date' => '2026-12-31',
        'frequency' => 30,
        'amount' => 100.50,
        'reason' => 'Monthly rent',
    ]);

    $response
        ->assertRedirect('/')
        ->assertSessionHas('recurring-transfer-status', 'success');

    expect(RecurringTransfer::where('user_id', $user->id)->count())->toBe(1);

    actingAs($user)->get('/')
        ->assertOk()
        ->assertSeeText('Transfer ok!')
        ->assertSeeText('2026-01-15')
        ->assertSeeText('2026-12-31')
        ->assertSeeText('Monthly rent');
});

test('dashboard displays success message after creating recurring transfer', function () {
    $user = User::factory()->create();
    Wallet::factory()->for($user)->create();

    actingAs($user)->post('/recurring-transfer', [
        'start_date' => now()->addDay()->format('Y-m-d'),
        'frequency' => 7,
        'amount' => 50.00,
    ]);

    $response = actingAs($user)->get('/');

    $response
        ->assertOk()
        ->assertSeeText('Transfer ok!');
});

test('dashboard displays error message when recurring transfer creation fails', function () {
    $user = User::factory()->create();
    Wallet::factory()->for($user)->create();

    $response = actingAs($user)
        ->withSession(['recurring-transfer-status' => 'error'])
        ->get('/');

    $response
        ->assertOk()
        ->assertSeeText('Transfer ko');
});

test('dashboard shows validation errors for recurring transfer form', function () {
    $user = User::factory()->create();
    Wallet::factory()->for($user)->create();

    $response = actingAs($user)->post('/recurring-transfer', [
        'start_date' => 'invalid-date',
        'frequency' => 'not-a-number',
        'amount' => -100,
    ]);

    $response
        ->assertSessionHasErrors(['start_date', 'frequency', 'amount']);
});

test('recurring transfer form validates required fields', function () {
    $user = User::factory()->create();
    Wallet::factory()->for($user)->create();

    $response = actingAs($user)->post('/recurring-transfer', []);

    $response
        ->assertSessionHasErrors(['start_date', 'frequency', 'amount']);
});

test('recurring transfer form allows optional fields to be empty', function () {
    $user = User::factory()->create();
    Wallet::factory()->for($user)->create();

    $response = actingAs($user)->post('/recurring-transfer', [
        'start_date' => now()->addDay()->format('Y-m-d'),
        'frequency' => 30,
        'amount' => 100.00,
        // stop_date and reason are optional
    ]);

    $response
        ->assertRedirect('/')
        ->assertSessionHas('recurring-transfer-status', 'success')
        ->assertSessionDoesntHaveErrors();

    $transfer = RecurringTransfer::where('user_id', $user->id)->first();
    expect($transfer)
        ->stop_date->toBeNull()
        ->reason->toBeNull();
});

test('newly created recurring transfer appears in listing', function () {
    $user = User::factory()->create();
    Wallet::factory()->for($user)->create();

    actingAs($user)->post('/recurring-transfer', [
        'start_date' => '2026-03-01',
        'stop_date' => '2026-06-30',
        'frequency' => 15,
        'amount' => 75.25,
        'reason' => 'Bi-weekly payment',
    ]);

    $response = actingAs($user)->get('/');

    $response
        ->assertOk()
        ->assertSeeText('2026-03-01')
        ->assertSeeText('2026-06-30')
        ->assertSeeText('15')
        ->assertSeeText('€75.25')
        ->assertSeeText('Bi-weekly payment');
});

test('dashboard displays empty dash for null optional fields', function () {
    $user = User::factory()->create();
    Wallet::factory()->for($user)->create();

    RecurringTransfer::factory()->for($user)->create([
        'stop_date' => null,
        'reason' => null,
    ]);

    $response = actingAs($user)->get('/');

    $response
        ->assertOk()
        ->assertSee('—'); // Em dash for empty values
});

test('recurring transfer with past stop date shows ended status', function () {
    $user = User::factory()->create();
    Wallet::factory()->for($user)->create();

    RecurringTransfer::factory()->for($user)->create([
        'start_date' => now()->subMonth(),
        'stop_date' => now()->subDay(),
        'frequency' => 7,
        'amount' => 100.00,
    ]);

    $response = actingAs($user)->get('/');

    $response
        ->assertOk()
        ->assertSeeText('Ended');
});

test('multiple recurring transfers are all displayed', function () {
    $user = User::factory()->create();
    Wallet::factory()->for($user)->create();

    RecurringTransfer::factory()->count(5)->for($user)->create();

    $response = actingAs($user)->get('/');

    $response->assertOk();
    
    $recurringTransfers = $user->recurringTransfers;
    expect($recurringTransfers)->toHaveCount(5);
    
    foreach ($recurringTransfers as $transfer) {
        $response->assertSeeText((string) $transfer->frequency);
    }
});

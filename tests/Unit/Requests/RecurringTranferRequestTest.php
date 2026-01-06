<?php

declare(strict_types=1);

use App\Http\Requests\RecurringTranferRequest;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('valid recurring transfer request passes validation', function () {
    $request = new RecurringTranferRequest();
    $data = [
        'start_date' => now()->addDay()->format('Y-m-d'),
        'stop_date' => now()->addMonth()->format('Y-m-d'),
        'frequency' => 7,
        'amount' => 100.50,
        'reason' => 'Weekly payment',
    ];

    $validator = Validator::make($data, $request->rules());

    expect($validator->passes())->toBeTrue();
});

test('request passes validation without optional stop_date', function () {
    $request = new RecurringTranferRequest();
    $data = [
        'start_date' => now()->addDay()->format('Y-m-d'),
        'frequency' => 7,
        'amount' => 100.50,
        'reason' => 'Weekly payment',
    ];

    $validator = Validator::make($data, $request->rules());

    expect($validator->passes())->toBeTrue();
});

test('request passes validation without optional reason', function () {
    $request = new RecurringTranferRequest();
    $data = [
        'start_date' => now()->addDay()->format('Y-m-d'),
        'stop_date' => now()->addMonth()->format('Y-m-d'),
        'frequency' => 7,
        'amount' => 100.50,
    ];

    $validator = Validator::make($data, $request->rules());

    expect($validator->passes())->toBeTrue();
});

test('request passes validation without both optional fields', function () {
    $request = new RecurringTranferRequest();
    $data = [
        'start_date' => now()->addDay()->format('Y-m-d'),
        'frequency' => 7,
        'amount' => 100.50,
    ];

    $validator = Validator::make($data, $request->rules());

    expect($validator->passes())->toBeTrue();
});

test('validation fails when start_date is missing', function () {
    $request = new RecurringTranferRequest();
    $data = [
        'frequency' => 7,
        'amount' => 100.50,
    ];

    $validator = Validator::make($data, $request->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('start_date'))->toBeTrue();
});

test('validation fails when start_date is not a valid date', function () {
    $request = new RecurringTranferRequest();
    $data = [
        'start_date' => 'not-a-date',
        'frequency' => 7,
        'amount' => 100.50,
    ];

    $validator = Validator::make($data, $request->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('start_date'))->toBeTrue();
});

test('validation fails when start_date is in the past', function () {
    $request = new RecurringTranferRequest();
    $data = [
        'start_date' => now()->subDay()->format('Y-m-d'),
        'frequency' => 7,
        'amount' => 100.50,
    ];

    $validator = Validator::make($data, $request->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('start_date'))->toBeTrue();
});

test('validation passes when start_date is today', function () {
    $request = new RecurringTranferRequest();
    $data = [
        'start_date' => now()->format('Y-m-d'),
        'frequency' => 7,
        'amount' => 100.50,
    ];

    $validator = Validator::make($data, $request->rules());

    expect($validator->passes())->toBeTrue();
});

test('validation fails when stop_date is not a valid date', function () {
    $request = new RecurringTranferRequest();
    $data = [
        'start_date' => now()->addDay()->format('Y-m-d'),
        'stop_date' => 'invalid-date',
        'frequency' => 7,
        'amount' => 100.50,
    ];

    $validator = Validator::make($data, $request->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('stop_date'))->toBeTrue();
});

test('validation fails when stop_date is before start_date', function () {
    $request = new RecurringTranferRequest();
    $data = [
        'start_date' => now()->addWeek()->format('Y-m-d'),
        'stop_date' => now()->addDay()->format('Y-m-d'),
        'frequency' => 7,
        'amount' => 100.50,
    ];

    $validator = Validator::make($data, $request->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('stop_date'))->toBeTrue();
});

test('validation fails when stop_date equals start_date', function () {
    $request = new RecurringTranferRequest();
    $date = now()->addDay()->format('Y-m-d');
    $data = [
        'start_date' => $date,
        'stop_date' => $date,
        'frequency' => 7,
        'amount' => 100.50,
    ];

    $validator = Validator::make($data, $request->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('stop_date'))->toBeTrue();
});

test('validation fails when frequency is missing', function () {
    $request = new RecurringTranferRequest();
    $data = [
        'start_date' => now()->addDay()->format('Y-m-d'),
        'amount' => 100.50,
    ];

    $validator = Validator::make($data, $request->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('frequency'))->toBeTrue();
});

test('validation fails when frequency is not an integer', function () {
    $request = new RecurringTranferRequest();
    $data = [
        'start_date' => now()->addDay()->format('Y-m-d'),
        'frequency' => 'not-a-number',
        'amount' => 100.50,
    ];

    $validator = Validator::make($data, $request->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('frequency'))->toBeTrue();
});

test('validation fails when frequency is zero', function () {
    $request = new RecurringTranferRequest();
    $data = [
        'start_date' => now()->addDay()->format('Y-m-d'),
        'frequency' => 0,
        'amount' => 100.50,
    ];

    $validator = Validator::make($data, $request->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('frequency'))->toBeTrue();
});

test('validation fails when frequency is negative', function () {
    $request = new RecurringTranferRequest();
    $data = [
        'start_date' => now()->addDay()->format('Y-m-d'),
        'frequency' => -5,
        'amount' => 100.50,
    ];

    $validator = Validator::make($data, $request->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('frequency'))->toBeTrue();
});

test('validation passes when frequency is 1', function () {
    $request = new RecurringTranferRequest();
    $data = [
        'start_date' => now()->addDay()->format('Y-m-d'),
        'frequency' => 1,
        'amount' => 100.50,
    ];

    $validator = Validator::make($data, $request->rules());

    expect($validator->passes())->toBeTrue();
});

test('validation fails when amount is missing', function () {
    $request = new RecurringTranferRequest();
    $data = [
        'start_date' => now()->addDay()->format('Y-m-d'),
        'frequency' => 7,
    ];

    $validator = Validator::make($data, $request->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('amount'))->toBeTrue();
});

test('validation fails when amount is not numeric', function () {
    $request = new RecurringTranferRequest();
    $data = [
        'start_date' => now()->addDay()->format('Y-m-d'),
        'frequency' => 7,
        'amount' => 'not-a-number',
    ];

    $validator = Validator::make($data, $request->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('amount'))->toBeTrue();
});

test('validation fails when amount is zero', function () {
    $request = new RecurringTranferRequest();
    $data = [
        'start_date' => now()->addDay()->format('Y-m-d'),
        'frequency' => 7,
        'amount' => 0,
    ];

    $validator = Validator::make($data, $request->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('amount'))->toBeTrue();
});

test('validation fails when amount is negative', function () {
    $request = new RecurringTranferRequest();
    $data = [
        'start_date' => now()->addDay()->format('Y-m-d'),
        'frequency' => 7,
        'amount' => -50.00,
    ];

    $validator = Validator::make($data, $request->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('amount'))->toBeTrue();
});

test('validation passes when amount is 0.01', function () {
    $request = new RecurringTranferRequest();
    $data = [
        'start_date' => now()->addDay()->format('Y-m-d'),
        'frequency' => 7,
        'amount' => 0.01,
    ];

    $validator = Validator::make($data, $request->rules());

    expect($validator->passes())->toBeTrue();
});

test('validation fails when amount is less than 0.01', function () {
    $request = new RecurringTranferRequest();
    $data = [
        'start_date' => now()->addDay()->format('Y-m-d'),
        'frequency' => 7,
        'amount' => 0.001,
    ];

    $validator = Validator::make($data, $request->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('amount'))->toBeTrue();
});

test('validation fails when reason exceeds 255 characters', function () {
    $request = new RecurringTranferRequest();
    $data = [
        'start_date' => now()->addDay()->format('Y-m-d'),
        'frequency' => 7,
        'amount' => 100.50,
        'reason' => str_repeat('a', 256),
    ];

    $validator = Validator::make($data, $request->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('reason'))->toBeTrue();
});

test('validation passes when reason is exactly 255 characters', function () {
    $request = new RecurringTranferRequest();
    $data = [
        'start_date' => now()->addDay()->format('Y-m-d'),
        'frequency' => 7,
        'amount' => 100.50,
        'reason' => str_repeat('a', 255),
    ];

    $validator = Validator::make($data, $request->rules());

    expect($validator->passes())->toBeTrue();
});

test('validation fails when reason is not a string', function () {
    $request = new RecurringTranferRequest();
    $data = [
        'start_date' => now()->addDay()->format('Y-m-d'),
        'frequency' => 7,
        'amount' => 100.50,
        'reason' => ['not', 'a', 'string'],
    ];

    $validator = Validator::make($data, $request->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('reason'))->toBeTrue();
});

test('getAmountInCents returns correct value for whole number', function () {
    $request = RecurringTranferRequest::create(
        route('recurring-transfer'),
        'POST',
        ['amount' => 100.00]
    );

    expect($request->getAmountInCents())->toBe(10000);
});

test('getAmountInCents returns correct value for decimal amount', function () {
    $request = RecurringTranferRequest::create(
        route('recurring-transfer'),
        'POST',
        ['amount' => 50.75]
    );

    expect($request->getAmountInCents())->toBe(5075);
});

test('getAmountInCents rounds up for fractional cents', function () {
    $request = RecurringTranferRequest::create(
        route('recurring-transfer'),
        'POST',
        ['amount' => 10.555]
    );

    expect($request->getAmountInCents())->toBe(1056);
});

test('getAmountInCents returns correct value for small amount', function () {
    $request = RecurringTranferRequest::create(
        route('recurring-transfer'),
        'POST',
        ['amount' => 0.01]
    );

    expect($request->getAmountInCents())->toBe(1);
});

test('authorize always returns true', function () {
    $request = new RecurringTranferRequest();

    expect($request->authorize())->toBeTrue();
});


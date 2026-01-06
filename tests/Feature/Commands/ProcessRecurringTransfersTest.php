<?php

declare(strict_types=1);

use App\Console\Commands\ProcessRecurringTransfers;
use App\Jobs\RecurringTransferJob;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;

test('command class exists', function () {
    expect(class_exists(ProcessRecurringTransfers::class))->toBeTrue();
});

test('command has correct signature', function () {
    $command = new ProcessRecurringTransfers();
    
    expect($command)->toBeInstanceOf(ProcessRecurringTransfers::class);
});

test('command handle method dispatches job', function () {
    Queue::fake();
    
    $command = new ProcessRecurringTransfers();
    $exitCode = $command->handle();
    
    expect($exitCode)->toBe(0);
    Queue::assertPushed(RecurringTransferJob::class);
});

test('command dispatches job without id parameter', function () {
    Queue::fake();
    
    $command = new ProcessRecurringTransfers();
    $command->handle();
    
    Queue::assertPushed(RecurringTransferJob::class, function ($job) {
        return $job->id === null;
    });
});

test('command dispatches job exactly once per execution', function () {
    Queue::fake();
    
    $command = new ProcessRecurringTransfers();
    $command->handle();
    
    Queue::assertPushed(RecurringTransferJob::class, 1);
});

test('command can be executed multiple times', function () {
    Queue::fake();
    
    $command1 = new ProcessRecurringTransfers();
    $command1->handle();
    
    $command2 = new ProcessRecurringTransfers();
    $command2->handle();
    
    Queue::assertPushed(RecurringTransferJob::class, 2);
});

test('command returns success exit code', function () {
    $command = new ProcessRecurringTransfers();
    $exitCode = $command->handle();
    
    expect($exitCode)->toBe(0);
});

test('command is registered in artisan', function () {
    $commands = Artisan::all();
    
    expect($commands)->toHaveKey('recurring-transfers:process');
});

test('command has correct description', function () {
    $command = new ProcessRecurringTransfers();
    
    expect($command->getDescription())->toBe('Process all active recurring transfers');
});

test('command signature is correct', function () {
    $command = new ProcessRecurringTransfers();
    
    expect($command->getName())->toBe('recurring-transfers:process');
});


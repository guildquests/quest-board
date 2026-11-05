<?php

declare(strict_types=1);

use App\Jobs\PingJob;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

test('quests:ping pushes a PingJob onto the queue', function () {
    Queue::fake();

    $this->artisan('quests:ping')
        ->expectsOutput('PingJob dispatched')
        ->assertSuccessful();

    Queue::assertPushed(PingJob::class);
});

test('PingJob logs when it runs', function () {
    Log::spy();

    (new PingJob)->handle();

    Log::shouldHaveReceived('info')
        ->once()
        ->withArgs(fn (string $message) => str_starts_with($message, 'PingJob ran at '));
});

test('quests:heartbeat logs a heartbeat line', function () {
    Log::spy();

    $this->artisan('quests:heartbeat')->assertSuccessful();

    Log::shouldHaveReceived('info')
        ->once()
        ->withArgs(fn (string $message) => str_starts_with($message, 'Heartbeat '));
});

test('quests:heartbeat is scheduled every minute', function () {
    $events = collect(app(Schedule::class)->events())
        ->filter(fn (Event $event) => str_contains((string) $event->command, 'quests:heartbeat'));

    expect($events)->toHaveCount(1)
        ->and($events->first()->expression)->toBe('* * * * *');
});

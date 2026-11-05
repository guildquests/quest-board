<?php

declare(strict_types=1);

use App\Enums\ActivityEvent;
use App\Enums\QuestStatus;
use App\Models\Quest;
use Illuminate\Console\Scheduling\Schedule;

test('the command expires overdue in-progress quests only', function () {
    $overdue = Quest::factory()->inProgress()->create(['due_at' => now()->subMinute()]);
    $current = Quest::factory()->inProgress()->create(['due_at' => now()->addDay()]);
    $review = Quest::factory()->inReview()->create(['due_at' => now()->subWeek()]);

    $this->artisan('quests:expire-claims')
        ->expectsOutput('Expired 1 claim(s).')
        ->assertSuccessful();

    $event = $overdue->events()->firstOrFail();

    expect($overdue->fresh()->status)->toBe(QuestStatus::Open)
        ->and($overdue->fresh()->adventurer_id)->toBeNull()
        ->and($event->event)->toBe(ActivityEvent::Expired)
        ->and($event->user_id)->toBeNull()
        ->and($current->fresh()->status)->toBe(QuestStatus::InProgress)
        ->and($review->fresh()->status)->toBe(QuestStatus::InReview);
});

test('the command reports zero when nothing is overdue', function () {
    $this->artisan('quests:expire-claims')
        ->expectsOutput('Expired 0 claim(s).')
        ->assertSuccessful();
});

test('the command is scheduled daily', function () {
    $event = collect(app(Schedule::class)->events())
        ->first(fn ($event) => str_contains($event->command, 'quests:expire-claims'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('0 0 * * *');
});

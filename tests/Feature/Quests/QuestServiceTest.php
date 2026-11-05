<?php

declare(strict_types=1);

use App\Enums\ActivityEvent;
use App\Enums\Category;
use App\Enums\Difficulty;
use App\Enums\QuestStatus;
use App\Exceptions\QuestActionException;
use App\Models\Quest;
use App\Models\QuestEvent;
use App\Models\User;
use App\Services\QuestService;

beforeEach(function () {
    $this->travelTo(now()->startOfSecond());
    $this->service = new QuestService;
});

function lastEvent(Quest $quest): QuestEvent
{
    return $quest->events()->latest('id')->firstOrFail();
}

test('post creates an open quest and logs it', function () {
    $poster = User::factory()->create();

    $quest = $this->service->post($poster, [
        'title' => 'Map the clash rules',
        'summary' => 'Fewer clashes',
        'category' => Category::Research,
        'difficulty' => Difficulty::Medium,
        'brief' => 'Brief',
        'objectives' => ['One'],
        'proof' => 'A doc',
    ]);

    expect($quest->exists)->toBeTrue()
        ->and($quest->fresh()->status)->toBe(QuestStatus::Open)
        ->and($quest->poster_id)->toBe($poster->id)
        ->and(lastEvent($quest)->event)->toBe(ActivityEvent::Posted)
        ->and(lastEvent($quest)->user_id)->toBe($poster->id);
});

test('update changes content while open and fails once claimed', function () {
    $quest = Quest::factory()->create();

    $this->service->update($quest, ['title' => 'New title']);
    expect($quest->fresh()->title)->toBe('New title');

    $claimed = Quest::factory()->inProgress()->create();
    expect(fn () => $this->service->update($claimed, ['title' => 'Nope']))
        ->toThrow(QuestActionException::class, 'This quest is no longer open.');
});

test('delete removes an open quest and fails once claimed', function () {
    $quest = Quest::factory()->create();
    $this->service->delete($quest);
    expect(Quest::find($quest->id))->toBeNull();

    $claimed = Quest::factory()->inProgress()->create();
    expect(fn () => $this->service->delete($claimed))->toThrow(QuestActionException::class);
    expect(Quest::find($claimed->id))->not->toBeNull();
});

test('accept claims the quest, sets the deadline, and logs it', function () {
    $user = User::factory()->create();
    $quest = Quest::factory()->create();

    $this->service->accept($quest, $user);

    expect($quest->status)->toBe(QuestStatus::InProgress)
        ->and($quest->adventurer_id)->toBe($user->id)
        ->and($quest->claimed_at->equalTo(now()))->toBeTrue()
        ->and($quest->due_at->equalTo(now()->addDays(config('quests.claim_days'))))->toBeTrue()
        ->and(lastEvent($quest)->event)->toBe(ActivityEvent::Accepted);
});

test('accept fails for the poster and for users with an active quest', function (string $activeState) {
    $user = User::factory()->create();
    $own = Quest::factory()->create(['poster_id' => $user->id]);

    expect(fn () => $this->service->accept($own, $user))->toThrow(QuestActionException::class);

    Quest::factory()->{$activeState}()->create(['adventurer_id' => $user->id]);
    $other = Quest::factory()->create();

    expect(fn () => $this->service->accept($other, $user))
        ->toThrow(QuestActionException::class, 'You already have an active quest.');
    expect($other->fresh()->status)->toBe(QuestStatus::Open);
})->with(['in progress' => 'inProgress', 'in review' => 'inReview']);

test('a second accept by someone else says the quest was taken', function () {
    [$first, $second] = User::factory()->count(2)->create();
    $quest = Quest::factory()->create();
    $stale = Quest::find($quest->id);

    $this->service->accept($quest, $first);

    expect(fn () => $this->service->accept($stale, $second))
        ->toThrow(QuestActionException::class, 'Someone else just took this quest.');
});

test('drop reopens the quest and clears claim and turn-in fields', function () {
    $quest = Quest::factory()->inProgress()->create();
    $adventurer = $quest->adventurer;

    $this->service->drop($quest, $adventurer);

    expect($quest->status)->toBe(QuestStatus::Open)
        ->and($quest->adventurer_id)->toBeNull()
        ->and($quest->claimed_at)->toBeNull()
        ->and($quest->due_at)->toBeNull()
        ->and(lastEvent($quest)->event)->toBe(ActivityEvent::Dropped)
        ->and(lastEvent($quest)->user_id)->toBe($adventurer->id);
});

test('drop and turn in fail when stale or for someone else', function () {
    $open = Quest::factory()->create();
    $user = User::factory()->create();
    expect(fn () => $this->service->turnIn($open, $user, 'https://example.com', null))
        ->toThrow(QuestActionException::class, 'This quest is no longer in progress.');
    expect(fn () => $this->service->drop($open, $user))->toThrow(QuestActionException::class);

    $claimed = Quest::factory()->inProgress()->create();
    expect(fn () => $this->service->drop($claimed, $user))
        ->toThrow(QuestActionException::class, 'This quest is not yours.');
});

test('turn in stores the result and moves to review', function () {
    $quest = Quest::factory()->inProgress()->create();

    $this->service->turnIn($quest, $quest->adventurer, 'https://example.com/r', 'Done it');

    expect($quest->status)->toBe(QuestStatus::InReview)
        ->and($quest->result_url)->toBe('https://example.com/r')
        ->and($quest->result_note)->toBe('Done it')
        ->and($quest->turned_in_at->equalTo(now()))->toBeTrue()
        ->and(lastEvent($quest)->event)->toBe(ActivityEvent::TurnedIn);
});

test('send back returns to in progress, restarts the deadline, and keeps the result', function () {
    $quest = Quest::factory()->inReview()->create(['due_at' => now()->subDay()]);
    $lead = User::factory()->lead()->create();
    $url = $quest->result_url;

    $this->service->sendBack($quest, $lead, 'Needs sources');

    expect($quest->status)->toBe(QuestStatus::InProgress)
        ->and($quest->due_at->equalTo(now()->addDays(config('quests.claim_days'))))->toBeTrue()
        ->and($quest->result_url)->toBe($url)
        ->and(lastEvent($quest)->event)->toBe(ActivityEvent::SentBack)
        ->and(lastEvent($quest)->note)->toBe('Needs sources');

    expect(fn () => $this->service->sendBack($quest, $lead, 'Again'))
        ->toThrow(QuestActionException::class, 'This quest is no longer in review.');
});

test('send back refuses the adventurer own turn-in', function () {
    $lead = User::factory()->lead()->create();
    $quest = Quest::factory()->inReview()->create(['adventurer_id' => $lead->id]);

    expect(fn () => $this->service->sendBack($quest, $lead, 'Needs sources'))
        ->toThrow(QuestActionException::class, "You can't review your own quest.");
});

test('approve stores xp from the final difficulty and poster xp from config', function () {
    $quest = Quest::factory()->inReview()->create(['difficulty' => Difficulty::Easy]);
    $lead = User::factory()->lead()->create();

    $this->service->approve($quest, $lead, Difficulty::Hard);

    expect($quest->status)->toBe(QuestStatus::Done)
        ->and($quest->difficulty)->toBe(Difficulty::Hard)
        ->and($quest->xp)->toBe(Difficulty::Hard->xp())
        ->and($quest->poster_xp)->toBe(config('quests.poster_xp'))
        ->and($quest->approved_by)->toBe($lead->id)
        ->and($quest->approved_at->equalTo(now()))->toBeTrue()
        ->and(lastEvent($quest)->event)->toBe(ActivityEvent::Approved);
});

test('approve keeps the difficulty when none is given and refuses the adventurer', function () {
    $quest = Quest::factory()->inReview()->create(['difficulty' => Difficulty::Medium]);
    $lead = User::factory()->lead()->create();

    $this->service->approve($quest, $lead, null);
    expect($quest->difficulty)->toBe(Difficulty::Medium)
        ->and($quest->xp)->toBe(Difficulty::Medium->xp());

    $own = User::factory()->lead()->create();
    $mine = Quest::factory()->inReview()->create(['adventurer_id' => $own->id]);
    expect(fn () => $this->service->approve($mine, $own, null))
        ->toThrow(QuestActionException::class, "You can't review your own quest.");
});

test('expire reopens overdue in-progress quests only', function () {
    $overdue = Quest::factory()->inProgress()->create(['due_at' => now()->subMinute()]);
    $current = Quest::factory()->inProgress()->create(['due_at' => now()->addDay()]);
    $review = Quest::factory()->inReview()->create(['due_at' => now()->subWeek()]);

    $count = $this->service->expireOverdueClaims();

    expect($count)->toBe(1)
        ->and($overdue->fresh()->status)->toBe(QuestStatus::Open)
        ->and($overdue->fresh()->adventurer_id)->toBeNull()
        ->and(lastEvent($overdue)->event)->toBe(ActivityEvent::Expired)
        ->and(lastEvent($overdue)->user_id)->toBeNull()
        ->and($current->fresh()->status)->toBe(QuestStatus::InProgress)
        ->and($review->fresh()->status)->toBe(QuestStatus::InReview);
});

test('release reopens the active quest and logs a system event', function (string $state) {
    $quest = Quest::factory()->{$state}()->create();
    $other = Quest::factory()->inProgress()->create();

    $this->service->releaseFor($quest->adventurer);

    expect($quest->fresh()->status)->toBe(QuestStatus::Open)
        ->and($quest->fresh()->adventurer_id)->toBeNull()
        ->and($quest->fresh()->result_url)->toBeNull()
        ->and(lastEvent($quest)->event)->toBe(ActivityEvent::Released)
        ->and(lastEvent($quest)->user_id)->toBeNull()
        ->and($other->fresh()->status)->toBe(QuestStatus::InProgress);
})->with(['in progress' => 'inProgress', 'in review' => 'inReview']);

test('release does nothing for a user without an active quest', function () {
    $user = User::factory()->create();

    $this->service->releaseFor($user);

    expect(Quest::count())->toBe(0);
});

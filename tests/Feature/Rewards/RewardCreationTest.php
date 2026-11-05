<?php

declare(strict_types=1);

use App\Enums\Difficulty;
use App\Enums\RewardKind;
use App\Exceptions\QuestActionException;
use App\Models\Quest;
use App\Models\Reward;
use App\Models\User;
use App\Services\QuestService;

beforeEach(function () {
    $this->travelTo(now()->startOfSecond());
    $this->service = new QuestService;
});

function rewardApprovalQuest(User $poster, User $adventurer, Difficulty $difficulty = Difficulty::Medium): Quest
{
    return Quest::factory()->inReview()->create([
        'poster_id' => $poster->id,
        'adventurer_id' => $adventurer->id,
        'difficulty' => $difficulty,
    ]);
}

test('approval creates adventurer and poster rewards with lifetime xp before approval', function () {
    $lead = User::factory()->lead()->create();
    $poster = User::factory()->create();
    $adventurer = User::factory()->create();

    Quest::factory()->done()->create([
        'poster_id' => User::factory()->create()->id,
        'adventurer_id' => $adventurer->id,
        'xp' => 30,
    ]);
    Quest::factory()->done()->create([
        'poster_id' => $poster->id,
        'adventurer_id' => User::factory()->create()->id,
        'poster_xp' => 5,
    ]);

    $quest = rewardApprovalQuest($poster, $adventurer, Difficulty::Hard);
    $this->service->approve($quest, $lead, null);

    expect(Reward::query()->count())->toBe(2);

    $adventurerReward = Reward::query()
        ->where('user_id', $adventurer->id)
        ->where('kind', RewardKind::Adventurer)
        ->firstOrFail();
    $posterReward = Reward::query()
        ->where('user_id', $poster->id)
        ->where('kind', RewardKind::Poster)
        ->firstOrFail();

    expect($adventurerReward->xp)->toBe(60)
        ->and($adventurerReward->xp_before)->toBe(30)
        ->and($posterReward->xp)->toBe(5)
        ->and($posterReward->xp_before)->toBe(5)
        ->and($adventurerReward->seen_at)->toBeNull()
        ->and($posterReward->seen_at)->toBeNull();
});

test('approval creates no poster reward when the lead posted the quest', function () {
    $lead = User::factory()->lead()->create();
    $adventurer = User::factory()->create();
    $quest = rewardApprovalQuest($lead, $adventurer);

    $this->service->approve($quest, $lead, null);

    expect(Reward::query()->count())->toBe(1)
        ->and(Reward::query()->firstOrFail()->kind)->toBe(RewardKind::Adventurer);
});

test('send back, drop, and expiry create no rewards', function () {
    $lead = User::factory()->lead()->create();
    $poster = User::factory()->create();
    $adventurer = User::factory()->create();

    $sentBack = rewardApprovalQuest($poster, $adventurer);
    $this->service->sendBack($sentBack, $lead, 'Needs more detail');

    $dropped = Quest::factory()->inProgress()->create([
        'poster_id' => $poster->id,
        'adventurer_id' => $adventurer->id,
    ]);
    $this->service->drop($dropped, $adventurer);

    Quest::factory()->inProgress()->create([
        'poster_id' => $poster->id,
        'due_at' => now()->subMinute(),
    ]);
    $this->service->expireOverdueClaims();

    expect(Reward::query()->count())->toBe(0);
});

test('a failed approval creates no rewards', function () {
    $lead = User::factory()->lead()->create();
    $quest = Quest::factory()->create();

    expect(fn () => $this->service->approve($quest, $lead, null))
        ->toThrow(QuestActionException::class);
    expect(Reward::query()->count())->toBe(0);
});

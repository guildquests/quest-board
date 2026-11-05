<?php

declare(strict_types=1);

use App\Enums\Badge;
use App\Enums\Category;
use App\Enums\RewardKind;
use App\Models\Quest;
use App\Models\Reward;
use App\Models\User;
use App\Models\UserBadge;
use App\Support\RewardQueue;
use Illuminate\Support\Facades\DB;

function queueReward(User $user, RewardKind $kind, int $xpBefore, int $xp, int $createdOffset = 0): Reward
{
    $lead = User::factory()->lead()->create();
    $adventurer = $kind === RewardKind::Adventurer ? $user : User::factory()->create();
    $poster = $kind === RewardKind::Poster ? $user : User::factory()->create();
    $quest = Quest::factory()->done()->create([
        'poster_id' => $poster->id,
        'adventurer_id' => $adventurer->id,
        'approved_by' => $lead->id,
        'category' => Category::Docs,
        'xp' => $xp,
        'poster_xp' => 5,
    ]);

    return Reward::factory()->create([
        'user_id' => $user->id,
        'quest_id' => $quest->id,
        'kind' => $kind,
        'xp' => $xp,
        'xp_before' => $xpBefore,
        'created_at' => now()->addSeconds($createdOffset),
        'updated_at' => now()->addSeconds($createdOffset),
    ]);
}

function rewardQueueQueryCount(User $user, int $count): int
{
    foreach (range(1, $count) as $index) {
        queueReward($user, RewardKind::Adventurer, ($index - 1) * 10, 10, $index);
    }

    DB::enableQueryLog();
    DB::flushQueryLog();
    RewardQueue::for($user);

    return count(DB::getQueryLog());
}

test('unseen rewards are oldest first and one to three use single mode', function () {
    $user = User::factory()->create();
    $oldest = queueReward($user, RewardKind::Adventurer, 0, 10, 1);
    $middle = queueReward($user, RewardKind::Poster, 10, 5, 2);
    $newest = queueReward($user, RewardKind::Adventurer, 15, 30, 3);

    $queue = RewardQueue::for($user)->toArray();

    expect($queue['mode'])->toBe('single')
        ->and($queue['summary'])->toBeNull()
        ->and(array_column($queue['items'], 'id'))->toBe([$oldest->id, $middle->id, $newest->id]);
});

test('four unseen rewards use summary mode and include all ids', function () {
    $user = User::factory()->create();
    $rewards = [];
    foreach (range(1, 4) as $index) {
        $rewards[] = queueReward($user, RewardKind::Adventurer, ($index - 1) * 10, 10, $index);
    }

    $queue = RewardQueue::for($user)->toArray();

    expect($queue['mode'])->toBe('summary')
        ->and($queue['items'])->toBe(array_map(fn (Reward $reward): int => $reward->id, $rewards))
        ->and($queue['summary']['count'])->toBe(4)
        ->and($queue['summary']['total_xp'])->toBe(40)
        ->and($queue['summary']['from_xp'])->toBe(0)
        ->and($queue['summary']['to_xp'])->toBe(40);
});

test('a mid-queue level crossing fills the bar and only the last item carries level up', function () {
    $user = User::factory()->create();
    queueReward($user, RewardKind::Adventurer, 0, 5, 1);
    $crossing = queueReward($user, RewardKind::Adventurer, 5, 30, 2);
    queueReward($user, RewardKind::Adventurer, 35, 5, 3);

    $items = RewardQueue::for($user)->toArray()['items'];

    expect($items[1]['id'])->toBe($crossing->id)
        ->and($items[1]['to_xp'])->toBe(35)
        ->and($items[1]['level_from'])->toBe(1)
        ->and($items[1]['level_to'])->toBe(2)
        ->and($items[1]['floor'])->toBe(0)
        ->and($items[1]['next'])->toBe(30)
        ->and(array_key_exists('level_up', $items[1]))->toBeFalse()
        ->and($items[2]['level_up'])->toBe(['level' => 2, 'title' => 'Squire']);
});

test('several levels crossed give one final level up and Legend has no next level', function () {
    $user = User::factory()->create();
    queueReward($user, RewardKind::Adventurer, 0, 400);

    $item = RewardQueue::for($user)->toArray()['items'][0];

    expect($item['level_from'])->toBe(1)
        ->and($item['level_to'])->toBe(6)
        ->and($item['level_up'])->toBe(['level' => 6, 'title' => 'Legend'])
        ->and($item['next'])->toBeNull();
});

test('badges are matched to the reward quest', function () {
    $user = User::factory()->create();
    $reward = queueReward($user, RewardKind::Adventurer, 0, 10);

    UserBadge::query()->create([
        'user_id' => $user->id,
        'quest_id' => $reward->quest_id,
        'badge' => Badge::FirstQuest,
        'earned_at' => now(),
    ]);

    $item = RewardQueue::for($user)->toArray()['items'][0];

    expect($item['badges'])->toBe([['key' => 'first_quest', 'name' => 'First Quest']]);
});

test('a user with no unseen rewards gets none mode', function () {
    $user = User::factory()->create();

    expect(RewardQueue::for($user)->toArray())->toBe([
        'mode' => 'none',
        'items' => [],
        'summary' => null,
    ]);
});

test('queue query count does not grow with the number of unseen rewards', function () {
    $two = User::factory()->create();
    $six = User::factory()->create();

    expect(rewardQueueQueryCount($two, 2))->toBe(rewardQueueQueryCount($six, 6));
});

test('a start level decides whether the last item carries level up', function () {
    $user = User::factory()->create();
    queueReward($user, RewardKind::Adventurer, 25, 10);

    $withLowStart = RewardQueue::for($user, 1)->toArray()['items'][0];
    $withHighStart = RewardQueue::for($user, 2)->toArray()['items'][0];

    expect($withLowStart['level_up'])->toBe(['level' => 2, 'title' => 'Squire'])
        ->and($withHighStart['level_up'])->toBeNull();
});

test('summary mode uses the start level for level up', function () {
    $user = User::factory()->create();
    foreach (range(1, 4) as $index) {
        queueReward($user, RewardKind::Adventurer, 30 + ($index - 1) * 10, 10, $index);
    }

    expect(RewardQueue::for($user)->toArray()['summary']['level_up'])->toBeNull()
        ->and(RewardQueue::for($user, 1)->toArray()['summary']['level_up'])
        ->toBe(['level' => 2, 'title' => 'Squire']);
});

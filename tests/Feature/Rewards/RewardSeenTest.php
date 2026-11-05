<?php

declare(strict_types=1);

use App\Enums\RewardKind;
use App\Models\Quest;
use App\Models\Reward;
use App\Models\User;
use App\Support\RewardQueue;
use Inertia\Testing\AssertableInertia;

function seenTestReward(User $user, ?int $seenAt = null): Reward
{
    $lead = User::factory()->lead()->create();
    $quest = Quest::factory()->done()->create([
        'poster_id' => $user->id,
        'adventurer_id' => User::factory()->create()->id,
        'approved_by' => $lead->id,
    ]);

    return Reward::factory()->create([
        'user_id' => $user->id,
        'quest_id' => $quest->id,
        'kind' => RewardKind::Poster,
        'seen_at' => $seenAt === null ? null : now()->subMinute(),
    ]);
}

test('marks the signed in users unseen rewards seen', function () {
    $user = User::factory()->create();
    $first = seenTestReward($user);
    $second = seenTestReward($user);

    $this->actingAs($user)
        ->post(route('rewards.seen'), ['ids' => [$first->id, $second->id]])
        ->assertRedirect();

    expect($first->fresh()->seen_at)->not->toBeNull()
        ->and($second->fresh()->seen_at)->not->toBeNull();
});

test('ignores another users reward ids and already seen rewards', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $own = seenTestReward($user);
    $otherReward = seenTestReward($other);
    $seen = seenTestReward($user, 1);

    $this->actingAs($user)
        ->post(route('rewards.seen'), ['ids' => [$own->id, $otherReward->id, $seen->id]])
        ->assertRedirect();

    expect($own->fresh()->seen_at)->not->toBeNull()
        ->and($otherReward->fresh()->seen_at)->toBeNull()
        ->and($seen->fresh()->seen_at->equalTo($seen->seen_at))->toBeTrue();
});

test('guests are redirected and ids must be an array of integers', function () {
    $this->post(route('rewards.seen'), ['ids' => ['not-an-id']])
        ->assertRedirect(route('login'));

    $user = User::factory()->create();
    $this->actingAs($user)
        ->post(route('rewards.seen'), ['ids' => ['not-an-id']])
        ->assertSessionHasErrors('ids.0');
});

test('the board omits a reward after it is seen', function () {
    $user = User::factory()->create();
    $reward = seenTestReward($user);

    $this->actingAs($user)
        ->post(route('rewards.seen'), ['ids' => [$reward->id]])
        ->assertRedirect();

    $this->actingAs($user)
        ->get(route('board'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('rewards.mode', 'none')
            ->where('rewards.items', [])
            ->where('rewards.summary', null));
});

function seenLevelReward(User $user, int $xpBefore, int $xp, int $offset): Reward
{
    $reward = seenTestReward($user);
    $reward->update([
        'xp' => $xp,
        'xp_before' => $xpBefore,
        'created_at' => now()->addSeconds($offset),
    ]);

    return $reward;
}

function seenBoard(User $user): array
{
    $page = test()->actingAs($user)->get(route('board'))->viewData('page');

    return $page['props']['rewards'];
}

function closeReward(User $user, Reward $reward): void
{
    test()->actingAs($user)
        ->post(route('rewards.seen'), ['ids' => [$reward->id]])
        ->assertRedirect();
}

test('the level up stays on the last reward after the first pop-up closes', function () {
    $user = User::factory()->create();
    $first = seenLevelReward($user, 25, 10, 1);
    seenLevelReward($user, 35, 5, 2);

    expect(seenBoard($user)['items'][0])->not->toHaveKey('level_up');

    closeReward($user, $first);

    $rewards = seenBoard($user);
    expect($rewards['items'])->toHaveCount(1)
        ->and($rewards['items'][0]['level_up'])->toBe(['level' => 2, 'title' => 'Squire']);

    closeReward($user, Reward::query()->where('user_id', $user->id)->whereNull('seen_at')->firstOrFail());

    $this->get(route('board'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('rewards.mode', 'none'));
    expect(session()->has(RewardQueue::startLevelKey($user)))->toBeFalse();
});

test('the level up survives two closes with three rewards', function () {
    $user = User::factory()->create();
    $first = seenLevelReward($user, 25, 10, 1);
    $second = seenLevelReward($user, 35, 5, 2);
    seenLevelReward($user, 40, 5, 3);

    closeReward($user, $first);
    $afterFirst = seenBoard($user);
    expect($afterFirst['items'])->toHaveCount(2)
        ->and($afterFirst['items'][0])->not->toHaveKey('level_up');

    closeReward($user, $second);
    $afterSecond = seenBoard($user);
    expect($afterSecond['items'])->toHaveCount(1)
        ->and($afterSecond['items'][0]['level_up'])->toBe(['level' => 2, 'title' => 'Squire']);
});

test('no level up appears when no reward crosses a level', function () {
    $user = User::factory()->create();
    $first = seenLevelReward($user, 0, 5, 1);
    seenLevelReward($user, 5, 5, 2);

    closeReward($user, $first);

    $rewards = seenBoard($user);
    expect($rewards['items'])->toHaveCount(1)
        ->and($rewards['items'][0]['level_up'])->toBeNull();
});

test('the summary keeps the level up after a crossing reward is closed', function () {
    $user = User::factory()->create();
    $first = seenLevelReward($user, 25, 10, 1);
    seenLevelReward($user, 35, 5, 2);

    closeReward($user, $first);

    foreach (range(1, 4) as $index) {
        seenLevelReward($user, 40 + $index, 1, 10 + $index);
    }

    $rewards = seenBoard($user);
    expect($rewards['mode'])->toBe('summary')
        ->and($rewards['summary']['level_up'])->toBe(['level' => 2, 'title' => 'Squire']);
});

test('another users reward ids do not change the next pop-up', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    seenLevelReward($user, 25, 10, 1);
    seenLevelReward($user, 35, 5, 2);
    $otherReward = seenLevelReward($other, 0, 5, 1);

    closeReward($user, $otherReward);

    $rewards = seenBoard($user);
    expect($rewards['items'])->toHaveCount(2)
        ->and($rewards['items'][1]['level_up'])->toBe(['level' => 2, 'title' => 'Squire']);
    expect($otherReward->fresh()->seen_at)->toBeNull();
});

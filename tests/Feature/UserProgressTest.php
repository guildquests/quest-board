<?php

declare(strict_types=1);

use App\Enums\Difficulty;
use App\Models\Quest;
use App\Models\User;

function progressOf(User $user): User
{
    return User::query()->withProgress()->findOrFail($user->id);
}

test('lifetime xp adds adventurer xp and poster xp across done quests', function () {
    $user = User::factory()->create();

    Quest::factory()->done()->create(['adventurer_id' => $user->id, 'difficulty' => Difficulty::Hard]);
    Quest::factory()->done()->create(['adventurer_id' => $user->id, 'difficulty' => Difficulty::Easy]);
    Quest::factory()->done()->create(['poster_id' => $user->id]);
    Quest::factory()->done()->create(['poster_id' => $user->id]);

    $user = progressOf($user);

    expect($user->lifetimeXp())->toBe(60 + 10 + 5 + 5)
        ->and($user->questsDone())->toBe(2)
        ->and($user->level()->number)->toBe(3);
});

test('quests that are not done add nothing', function () {
    $user = User::factory()->create();

    Quest::factory()->create(['poster_id' => $user->id, 'poster_xp' => 5]);
    Quest::factory()->inProgress()->create(['adventurer_id' => $user->id, 'xp' => 30]);
    Quest::factory()->inReview()->create(['adventurer_id' => $user->id, 'xp' => 30]);

    $user = progressOf($user);

    expect($user->lifetimeXp())->toBe(0)
        ->and($user->questsDone())->toBe(0);
});

test('a new user has 0 xp, level 1, and 0 quests done', function () {
    $user = progressOf(User::factory()->create());

    expect($user->lifetimeXp())->toBe(0)
        ->and($user->questsDone())->toBe(0)
        ->and($user->level()->number)->toBe(1);
});

test('deleting an open quest leaves totals unchanged', function () {
    $user = User::factory()->create();

    Quest::factory()->done()->create(['adventurer_id' => $user->id, 'difficulty' => Difficulty::Medium]);
    $open = Quest::factory()->create(['poster_id' => $user->id]);

    $before = progressOf($user)->lifetimeXp();
    $open->delete();

    expect(progressOf($user)->lifetimeXp())->toBe($before)->toBe(30);
});

test('quests done counts only done quests as adventurer', function () {
    $user = User::factory()->create();

    Quest::factory()->done()->create(['adventurer_id' => $user->id]);
    Quest::factory()->done()->create(['poster_id' => $user->id]);
    Quest::factory()->inProgress()->create(['adventurer_id' => $user->id]);

    expect(progressOf($user)->questsDone())->toBe(1);
});

test('withProgress and loadProgress give the same numbers', function () {
    $user = User::factory()->create();

    Quest::factory()->done()->create(['adventurer_id' => $user->id]);
    Quest::factory()->done()->create(['poster_id' => $user->id]);

    $scoped = progressOf($user);
    $loaded = User::query()->findOrFail($user->id)->loadProgress();

    expect($loaded->lifetimeXp())->toBe($scoped->lifetimeXp())
        ->and($loaded->questsDone())->toBe($scoped->questsDone());
});

test('reading progress without the aggregates throws', function () {
    $user = User::factory()->create();

    expect(fn () => $user->lifetimeXp())->toThrow(LogicException::class)
        ->and(fn () => $user->questsDone())->toThrow(LogicException::class)
        ->and(fn () => $user->level())->toThrow(LogicException::class);
});

test('a deactivated user keeps their totals', function () {
    $user = User::factory()->create();

    Quest::factory()->done()->create(['adventurer_id' => $user->id, 'difficulty' => Difficulty::Hard]);
    $user->forceFill(['deactivated_at' => now()])->save();

    expect(progressOf($user)->lifetimeXp())->toBe(60);
});

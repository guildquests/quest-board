<?php

declare(strict_types=1);

use App\Enums\Badge;
use App\Enums\Category;
use App\Models\Quest;
use App\Models\User;
use App\Models\UserBadge;
use App\Support\BadgeProgress;
use Illuminate\Database\QueryException;

/**
 * @return array<string, array{current: int, target: int, earned: bool}>
 */
function badgeRows(User $user): array
{
    $rows = [];
    foreach (BadgeProgress::for($user)->toArray() as $row) {
        $rows[$row['key']] = $row;
    }

    return $rows;
}

function doneAs(User $user, Category $category, int $times = 1): void
{
    Quest::factory()->done()->count($times)->create([
        'adventurer_id' => $user->id,
        'category' => $category,
    ]);
}

test('a new user has zero progress and no badges', function () {
    $rows = badgeRows(User::factory()->create());

    expect($rows)->toHaveCount(5);
    foreach (Badge::cases() as $badge) {
        expect($rows[$badge->value]['current'])->toBe(0)
            ->and($rows[$badge->value]['target'])->toBe($badge->target())
            ->and($rows[$badge->value]['earned'])->toBeFalse();
    }
});

test('rows come in enum order', function () {
    $keys = array_column(BadgeProgress::for(User::factory()->create())->toArray(), 'key');

    expect($keys)->toBe(array_map(fn (Badge $badge): string => $badge->value, Badge::cases()));
});

test('research and tooling quests count toward scout and toolsmith', function () {
    $user = User::factory()->create();
    doneAs($user, Category::Research, 2);
    doneAs($user, Category::Tooling);

    $rows = badgeRows($user);

    expect($rows['scout']['current'])->toBe(2)
        ->and($rows['toolsmith']['current'])->toBe(1)
        ->and($rows['first_quest']['current'])->toBe(3);
});

test('explorer counts distinct categories', function () {
    $user = User::factory()->create();
    doneAs($user, Category::Research, 2);
    doneAs($user, Category::Docs);

    expect(badgeRows($user)['explorer']['current'])->toBe(2);
});

test('quest giver counts only done quests the user posted', function () {
    $user = User::factory()->create();
    Quest::factory()->done()->count(2)->create(['poster_id' => $user->id]);
    Quest::factory()->count(2)->create(['poster_id' => $user->id]);
    Quest::factory()->inProgress()->create(['poster_id' => $user->id]);
    Quest::factory()->inReview()->create(['poster_id' => $user->id]);

    expect(badgeRows($user)['quest_giver']['current'])->toBe(2);
});

test('quests that are not done count zero', function () {
    $user = User::factory()->create();
    Quest::factory()->inProgress()->create(['adventurer_id' => $user->id, 'category' => Category::Research]);
    Quest::factory()->inReview()->create(['adventurer_id' => $user->id, 'category' => Category::Tooling]);

    foreach (badgeRows($user) as $row) {
        expect($row['current'])->toBe(0);
    }
});

test('met targets list every badge at or over its target', function () {
    $user = User::factory()->create();
    doneAs($user, Category::Research, 3);

    expect(BadgeProgress::for($user)->metTargets())->toBe([Badge::FirstQuest, Badge::Scout]);
});

test('a stored badge row makes it earned', function () {
    $user = User::factory()->create();
    UserBadge::query()->create([
        'user_id' => $user->id,
        'badge' => Badge::FirstQuest,
        'earned_at' => now(),
    ]);

    $progress = BadgeProgress::for($user);

    expect($progress->earned(Badge::FirstQuest))->toBeTrue()
        ->and($progress->earned(Badge::Scout))->toBeFalse();
});

test('an earned badge stays earned with current capped at a raised target', function () {
    $user = User::factory()->create();
    doneAs($user, Category::Research, 3);
    UserBadge::query()->create([
        'user_id' => $user->id,
        'badge' => Badge::Scout,
        'earned_at' => now(),
    ]);

    config(['quests.badges.scout' => 5]);
    $row = badgeRows($user)['scout'];

    expect($row['earned'])->toBeTrue()
        ->and($row['target'])->toBe(5)
        ->and($row['current'])->toBe(3);

    config(['quests.badges.scout' => 2]);

    expect(badgeRows($user)['scout']['current'])->toBe(2);
});

test('the same badge cannot be stored twice for a user', function () {
    $user = User::factory()->create();
    $attributes = ['user_id' => $user->id, 'badge' => Badge::Scout, 'earned_at' => now()];

    UserBadge::query()->create($attributes);

    expect(fn () => UserBadge::query()->create($attributes))->toThrow(QueryException::class);
});

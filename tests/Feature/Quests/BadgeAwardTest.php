<?php

declare(strict_types=1);

use App\Enums\Badge;
use App\Enums\Category;
use App\Models\Quest;
use App\Models\User;
use App\Models\UserBadge;
use App\Services\QuestService;

beforeEach(function () {
    $this->travelTo(now()->startOfSecond());
    $this->service = new QuestService;
    $this->lead = User::factory()->lead()->create();
});

function approveQuest(User $lead, User $poster, User $adventurer, Category $category = Category::Docs): Quest
{
    $quest = Quest::factory()->inReview()->create([
        'poster_id' => $poster->id,
        'adventurer_id' => $adventurer->id,
        'category' => $category,
    ]);

    (new QuestService)->approve($quest, $lead, null);

    return $quest;
}

function badgeKeys(User $user): array
{
    return $user->badges()->get()->map(fn (UserBadge $row): string => $row->badge->value)->sort()->values()->all();
}

test('each badge is awarded on the approval that reaches its target', function (Badge $badge, bool $forPoster, array $categories) {
    $user = User::factory()->create();
    $last = count($categories) - 1;

    foreach ($categories as $i => $category) {
        $quest = $forPoster
            ? approveQuest($this->lead, $user, User::factory()->create(), $category)
            : approveQuest($this->lead, User::factory()->create(), $user, $category);

        $row = $user->badges()->where('badge', $badge->value)->first();

        if ($i < $last) {
            expect($row)->toBeNull();

            continue;
        }

        expect($row)->not->toBeNull()
            ->and($row->quest_id)->toBe($quest->id)
            ->and($row->earned_at->equalTo(now()))->toBeTrue();
    }
})->with([
    'first quest' => [Badge::FirstQuest, false, [Category::Docs]],
    'scout' => [Badge::Scout, false, [Category::Research, Category::Research, Category::Research]],
    'toolsmith' => [Badge::Toolsmith, false, [Category::Tooling, Category::Tooling, Category::Tooling]],
    'quest giver' => [Badge::QuestGiver, true, [Category::Docs, Category::Docs, Category::Docs, Category::Docs, Category::Docs]],
    'explorer' => [Badge::Explorer, false, [Category::Docs, Category::Research, Category::Experiment]],
]);

test('one approval awards the adventurer and the poster', function () {
    $poster = User::factory()->create();
    foreach (range(1, 4) as $_) {
        Quest::factory()->done()->create(['poster_id' => $poster->id]);
    }
    $adventurer = User::factory()->create();

    $quest = approveQuest($this->lead, $poster, $adventurer);

    expect(badgeKeys($adventurer))->toBe(['first_quest'])
        ->and(badgeKeys($poster))->toBe(['quest_giver'])
        ->and($poster->badges()->first()->quest_id)->toBe($quest->id);
});

test('one approval can award several badges to one user', function () {
    config(['quests.badges.scout' => 1]);
    $adventurer = User::factory()->create();

    approveQuest($this->lead, User::factory()->create(), $adventurer, Category::Research);

    expect(badgeKeys($adventurer))->toBe(['first_quest', 'scout']);
});

test('later approvals create no duplicate rows', function () {
    $adventurer = User::factory()->create();

    approveQuest($this->lead, User::factory()->create(), $adventurer);
    approveQuest($this->lead, User::factory()->create(), $adventurer);

    expect(UserBadge::query()->where('user_id', $adventurer->id)->count())->toBe(1);
});

test('sending back or dropping awards nothing', function () {
    $adventurer = User::factory()->create();
    $poster = User::factory()->create();

    $sentBack = Quest::factory()->inReview()->create(['poster_id' => $poster->id, 'adventurer_id' => $adventurer->id]);
    $this->service->sendBack($sentBack, $this->lead, 'Needs more detail');

    $dropped = Quest::factory()->inProgress()->create(['poster_id' => $poster->id, 'adventurer_id' => User::factory()]);
    $this->service->drop($dropped, $dropped->adventurer);

    expect(UserBadge::query()->count())->toBe(0);
});

test('a deactivated user keeps their badges', function () {
    $adventurer = User::factory()->create();
    approveQuest($this->lead, User::factory()->create(), $adventurer);

    $adventurer->forceFill(['deactivated_at' => now()])->save();
    approveQuest($this->lead, User::factory()->create(), User::factory()->create());

    expect(badgeKeys($adventurer))->toBe(['first_quest']);
});

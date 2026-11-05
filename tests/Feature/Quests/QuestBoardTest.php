<?php

declare(strict_types=1);

use App\Enums\Category;
use App\Enums\Difficulty;
use App\Enums\QuestStatus;
use App\Models\Quest;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to login', function () {
    $this->get(route('board'))->assertRedirect(route('login'));
});

test('the board lists every quest newest first', function () {
    $oldest = Quest::factory()->create(['created_at' => now()->subDays(3)]);
    $newest = Quest::factory()->inReview()->create(['created_at' => now()->subHour()]);
    $middle = Quest::factory()->done()->create(['created_at' => now()->subDay()]);

    $this->actingAs(User::factory()->create())
        ->get(route('board'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('quest-board')
            ->has('quests', 3)
            ->where('quests.0.id', $newest->id)
            ->where('quests.1.id', $middle->id)
            ->where('quests.2.id', $oldest->id)
            ->where('filters', ['status' => null, 'category' => null])
            ->has('statuses', 4)
            ->has('categories', 4));
});

test('cards carry the poster, adventurer, effort and xp', function () {
    $open = Quest::factory()->create(['difficulty' => Difficulty::Medium, 'created_at' => now()->subDay()]);
    $done = Quest::factory()->done()->create(['difficulty' => Difficulty::Hard]);
    $done->forceFill(['xp' => 45, 'created_at' => now()])->save();

    $this->actingAs(User::factory()->create())
        ->get(route('board'))
        ->assertInertia(fn (Assert $page) => $page
            // Done: the stored XP, not the difficulty's.
            ->where('quests.0.id', $done->id)
            ->where('quests.0.xp', 45)
            ->where('quests.0.adventurer.id', $done->adventurer_id)
            ->where('quests.0.poster.id', $done->poster_id)
            // Not done: the difficulty's XP and effort, no adventurer.
            ->where('quests.1.id', $open->id)
            ->where('quests.1.xp', 30)
            ->where('quests.1.effort', '~half day')
            ->where('quests.1.adventurer', null)
            ->where('quests.1.status', 'open'));
});

test('the status filter narrows the list', function () {
    Quest::factory()->create();
    $review = Quest::factory()->inReview()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('board', ['status' => 'in_review']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('quests', 1)
            ->where('quests.0.id', $review->id)
            ->where('filters.status', 'in_review')
            ->where('filters.category', null));
});

test('the category filter narrows the list', function () {
    Quest::factory()->create(['category' => Category::Docs]);
    $tooling = Quest::factory()->create(['category' => Category::Tooling]);

    $this->actingAs(User::factory()->create())
        ->get(route('board', ['category' => 'tooling']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('quests', 1)
            ->where('quests.0.id', $tooling->id)
            ->where('filters.category', 'tooling'));
});

test('status and category filters combine', function () {
    Quest::factory()->create(['category' => Category::Docs]);
    Quest::factory()->inReview()->create(['category' => Category::Tooling]);
    $match = Quest::factory()->inReview()->create(['category' => Category::Docs]);

    $this->actingAs(User::factory()->create())
        ->get(route('board', ['status' => 'in_review', 'category' => 'docs']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('quests', 1)
            ->where('quests.0.id', $match->id));
});

test('a filter with no matches returns an empty list', function () {
    Quest::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('board', ['status' => 'done']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('quests', 0));
});

test('invalid filter values are ignored', function (array $query) {
    Quest::factory()->count(2)->create();

    $this->actingAs(User::factory()->create())
        ->get(route('board', $query))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('quests', 2)
            ->where('filters', ['status' => null, 'category' => null]));
})->with([
    'unknown status' => [['status' => 'nope']],
    'unknown category' => [['category' => 'cooking']],
    'array values' => [['status' => ['open'], 'category' => ['docs']]],
    'empty values' => [['status' => '', 'category' => '']],
]);

test('latestCompletion is the most recently approved quest', function () {
    $older = Quest::factory()->done()->create(['title' => 'Older win']);
    $older->forceFill(['approved_at' => now()->subDays(3), 'created_at' => now()])->save();
    $newer = Quest::factory()->done()->create(['title' => 'Newer win', 'created_at' => now()->subDays(9)]);
    $newer->forceFill(['approved_at' => now()->subDay(), 'xp' => 60])->save();
    Quest::factory()->inReview()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('board'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('latestCompletion.id', $newer->id)
            ->where('latestCompletion.title', 'Newer win')
            ->where('latestCompletion.xp', 60)
            ->where('latestCompletion.adventurer.id', $newer->adventurer_id));
});

test('latestCompletion ignores filters and is null without done quests', function () {
    $this->actingAs(User::factory()->create());

    Quest::factory()->create();
    $this->get(route('board'))
        ->assertInertia(fn (Assert $page) => $page->where('latestCompletion', null));

    $done = Quest::factory()->done()->create();
    $this->get(route('board', ['status' => 'open']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('quests', 1)
            ->where('latestCompletion.id', $done->id));

    expect(Quest::status(QuestStatus::Done)->count())->toBe(1);
});

<?php

declare(strict_types=1);

use App\Enums\Badge;
use App\Enums\Category;
use App\Enums\Difficulty;
use App\Models\Quest;
use App\Models\User;
use App\Models\UserBadge;
use App\Support\ProfilePage;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

function profileUtc(string $localDate): CarbonImmutable
{
    return CarbonImmutable::parse($localDate, 'Asia/Yangon')->setTimezone('UTC');
}

test('guests are redirected and signed-in users see the profile component', function () {
    $this->get(route('me'))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->create())
        ->get(route('me'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('me'));
});

test('profile stats include all posts, done quests, total xp, and earned badges', function () {
    $user = User::factory()->create(['name' => 'Profile User']);
    $other = User::factory()->deactivated()->create();
    $approver = User::factory()->deactivated()->create();

    Quest::factory()->done()->create([
        'poster_id' => $other->id,
        'adventurer_id' => $user->id,
        'approved_by' => $approver->id,
        'difficulty' => Difficulty::Hard,
        'xp' => 60,
    ]);
    Quest::factory()->done()->create([
        'poster_id' => $other->id,
        'adventurer_id' => $user->id,
        'approved_by' => $approver->id,
        'difficulty' => Difficulty::Medium,
        'xp' => 30,
    ]);

    Quest::factory()->create(['poster_id' => $user->id]);
    Quest::factory()->inProgress()->create(['poster_id' => $user->id]);
    Quest::factory()->inReview()->create(['poster_id' => $user->id]);
    Quest::factory()->done()->create([
        'poster_id' => $user->id,
        'adventurer_id' => $other->id,
        'poster_xp' => 5,
    ]);

    UserBadge::query()->create(['user_id' => $user->id, 'badge' => Badge::FirstQuest, 'earned_at' => now()]);
    UserBadge::query()->create(['user_id' => $user->id, 'badge' => Badge::Scout, 'earned_at' => now()]);

    $this->actingAs($user)
        ->get(route('me'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('profile.name', 'Profile User')
            ->where('profile.role_label', 'Member')
            ->where('level.xp', 95)
            ->where('level.level', 3)
            ->where('level.floor', 80)
            ->where('level.next', 150)
            ->where('level.next_level', ['level' => 4, 'title' => 'Knight'])
            ->where('stats.quests_done', 2)
            ->where('stats.quests_posted', 4)
            ->where('stats.total_xp', 95)
            ->where('stats.badges_earned', 2)
            ->where('stats.badges_total', 5));
});

test('profile lists contain the right quests, order, wording, and xp', function () {
    $this->travelTo('2026-10-27 05:30:00');
    $user = User::factory()->create();
    $other = User::factory()->deactivated()->create(['name' => 'Other Adventurer']);
    $approver = User::factory()->deactivated()->create();

    Quest::factory()->inProgress()->create([
        'poster_id' => $other->id,
        'adventurer_id' => $user->id,
        'claimed_at' => profileUtc('2026-10-27 08:00:00'),
        'due_at' => profileUtc('2026-11-02 08:00:00'),
        'category' => Category::Research,
        'difficulty' => Difficulty::Hard,
        'title' => 'Active quest',
    ]);
    Quest::factory()->inReview()->create([
        'poster_id' => $other->id,
        'adventurer_id' => $user->id,
        'claimed_at' => profileUtc('2026-10-26 08:00:00'),
        'turned_in_at' => profileUtc('2026-10-26 23:00:00'),
        'category' => Category::Tooling,
        'difficulty' => Difficulty::Medium,
        'title' => 'Review quest',
    ]);

    Quest::factory()->done()->create([
        'poster_id' => $other->id,
        'adventurer_id' => $user->id,
        'approved_by' => $approver->id,
        'approved_at' => profileUtc('2026-10-25 08:00:00'),
        'category' => Category::Docs,
        'difficulty' => Difficulty::Easy,
        'xp' => 17,
        'title' => 'Older completed',
    ]);
    Quest::factory()->done()->create([
        'poster_id' => $other->id,
        'adventurer_id' => $user->id,
        'approved_by' => $approver->id,
        'approved_at' => profileUtc('2026-10-26 08:00:00'),
        'category' => Category::Experiment,
        'difficulty' => Difficulty::Hard,
        'xp' => 55,
        'title' => 'Newest completed',
    ]);

    Quest::factory()->create([
        'poster_id' => $user->id,
        'created_at' => profileUtc('2026-10-27 08:00:00'),
        'category' => Category::Research,
        'difficulty' => Difficulty::Easy,
        'title' => 'Open post',
    ]);
    Quest::factory()->done()->create([
        'poster_id' => $user->id,
        'adventurer_id' => $other->id,
        'approved_by' => $approver->id,
        'created_at' => profileUtc('2026-10-25 08:00:00'),
        'category' => Category::Research,
        'difficulty' => Difficulty::Easy,
        'title' => 'Done post',
        'poster_xp' => 5,
    ]);

    $this->actingAs($user)
        ->get(route('me'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('quests.active', 2)
            ->where('quests.active.0.title', 'Active quest')
            ->where('quests.active.0.detail', 'Research · Hard · due 2 Nov')
            ->where('quests.active.0.xp', 60)
            ->where('quests.active.1.detail', 'Tooling · Medium · turned in yesterday')
            ->has('quests.completed', 2)
            ->where('quests.completed.0.title', 'Newest completed')
            ->where('quests.completed.0.detail', 'Experiment · Hard · Yesterday')
            ->where('quests.completed.0.xp', 55)
            ->where('quests.completed.1.xp', 17)
            ->has('quests.posted', 2)
            ->where('quests.posted.0.title', 'Open post')
            ->where('quests.posted.0.detail', 'Research · Easy · posted today')
            ->where('quests.posted.0.xp', null)
            ->where('quests.posted.1.title', 'Done post')
            ->where('quests.posted.1.detail', 'Research · Easy · done by Other Adventurer')
            ->where('quests.posted.1.xp', 5));
});

test('profile excludes another users quests from every list', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    Quest::factory()->inProgress()->create(['adventurer_id' => $other->id]);
    Quest::factory()->done()->create(['adventurer_id' => $other->id]);
    Quest::factory()->create(['poster_id' => $other->id]);

    $this->actingAs($user)
        ->get(route('me'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('quests.active', 0)
            ->has('quests.completed', 0)
            ->has('quests.posted', 0));
});

test('profile uses the configured next level and handles the top level', function () {
    $user = User::factory()->create();
    $other = User::factory()->deactivated()->create();

    Quest::factory()->done()->create([
        'poster_id' => $other->id,
        'adventurer_id' => $user->id,
        'xp' => 400,
    ]);

    $this->actingAs($user)
        ->get(route('me'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('level.level', 6)
            ->where('level.title', 'Legend')
            ->where('level.next', null)
            ->where('level.next_level', null));
});

test('a new user gets empty lists and five locked badges', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('me'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('level.xp', 0)
            ->where('level.level', 1)
            ->where('stats.quests_done', 0)
            ->where('stats.quests_posted', 0)
            ->where('stats.total_xp', 0)
            ->where('stats.badges_earned', 0)
            ->where('stats.badges_total', 5)
            ->where('poster_xp', config('quests.poster_xp'))
            ->has('quests.active', 0)
            ->has('quests.completed', 0)
            ->has('quests.posted', 0)
            ->has('badges', 5)
            ->where('badges.0.current', 0)
            ->where('badges.0.earned', false)
            ->where('badges.4.current', 0)
            ->where('badges.4.earned', false));
});

test('profile query count stays fixed as each list grows', function () {
    $user = User::factory()->create();

    $countQueries = function () use ($user): int {
        DB::flushQueryLog();
        DB::enableQueryLog();

        ProfilePage::for($user);

        return count(DB::getQueryLog());
    };

    Quest::factory()->inProgress()->create(['adventurer_id' => $user->id]);
    Quest::factory()->done()->create(['adventurer_id' => $user->id]);
    Quest::factory()->create(['poster_id' => $user->id]);
    $smallCount = $countQueries();

    Quest::factory()->inProgress()->count(5)->create(['adventurer_id' => $user->id]);
    Quest::factory()->done()->count(5)->create(['adventurer_id' => $user->id]);
    Quest::factory()->count(5)->create(['poster_id' => $user->id]);
    $largeCount = $countQueries();

    expect($largeCount)->toBe($smallCount);
});

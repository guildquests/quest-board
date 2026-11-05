<?php

declare(strict_types=1);

use App\Models\Quest;
use App\Models\User;
use App\Support\HallOfFame;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * @param  array<string, mixed>  $attributes
 */
function pageDoneQuest(
    User $poster,
    User $adventurer,
    User $approver,
    CarbonImmutable $approvedAt,
    array $attributes = [],
): Quest {
    return Quest::factory()->done()->create([
        'poster_id' => $poster->id,
        'adventurer_id' => $adventurer->id,
        'approved_by' => $approver->id,
        'approved_at' => $approvedAt,
        ...$attributes,
    ]);
}

function pageUtc(string $localDate): CarbonImmutable
{
    return CarbonImmutable::parse($localDate, 'Asia/Yangon')->setTimezone('UTC');
}

function pageHallOfFameQueryCount(): int
{
    DB::flushQueryLog();
    DB::enableQueryLog();

    $hallOfFame = new HallOfFame(CarbonImmutable::parse('2026-10-15 12:00:00', 'Asia/Yangon'));
    $hallOfFame->mostCompleted();
    $hallOfFame->topGiver();
    $hallOfFame->newest();
    $hallOfFame->topAdventurers();
    $hallOfFame->recentCompletions();

    return count(DB::getQueryLog());
}

test('guests are redirected and signed-in users see the Hall of Fame component', function () {
    $this->get(route('hall-of-fame'))->assertRedirect(route('login'));

    foreach (['member', 'lead', 'admin'] as $role) {
        $this->actingAs(User::factory()->state(['role' => $role])->create())
            ->get(route('hall-of-fame'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('hall-of-fame'));
    }
});

test('top adventurers are ordered by xp, quests done, latest approval, and id', function () {
    $poster = User::factory()->deactivated()->create();
    $approver = User::factory()->deactivated()->create();
    $xpLeader = User::factory()->create(['name' => 'XP leader']);
    $questCountTie = User::factory()->create(['name' => 'More quests']);
    $singleQuestTie = User::factory()->create(['name' => 'Single quest']);
    $earlyApproval = User::factory()->create(['name' => 'Early approval']);
    $lateApproval = User::factory()->create(['name' => 'Late approval']);
    $zeroXp = User::factory()->create(['name' => 'Zero XP']);
    $deactivated = User::factory()->deactivated()->create(['name' => 'Deactivated']);

    pageDoneQuest($poster, $xpLeader, $approver, pageUtc('2026-10-08 12:00:00'), ['xp' => 60]);
    pageDoneQuest($poster, $questCountTie, $approver, pageUtc('2026-10-03 12:00:00'), ['xp' => 15]);
    pageDoneQuest($poster, $questCountTie, $approver, pageUtc('2026-10-04 12:00:00'), ['xp' => 15]);
    pageDoneQuest($poster, $singleQuestTie, $approver, pageUtc('2026-10-01 12:00:00'), ['xp' => 30]);
    pageDoneQuest($poster, $earlyApproval, $approver, pageUtc('2026-10-01 12:00:00'), ['xp' => 10]);
    pageDoneQuest($poster, $lateApproval, $approver, pageUtc('2026-10-02 12:00:00'), ['xp' => 10]);
    pageDoneQuest($poster, $deactivated, $approver, pageUtc('2026-10-09 12:00:00'), ['xp' => 100]);

    $this->actingAs($zeroXp)
        ->get(route('hall-of-fame'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('topAdventurers', 5)
            ->where('topAdventurers.0.person.name', 'XP leader')
            ->where('topAdventurers.0.xp', 60)
            ->where('topAdventurers.1.person.name', 'More quests')
            ->where('topAdventurers.1.quests_done', 2)
            ->where('topAdventurers.2.person.name', 'Single quest')
            ->where('topAdventurers.3.person.name', 'Early approval')
            ->where('topAdventurers.4.person.name', 'Late approval'));
});

test('poster xp contributes to lifetime xp and fewer than five users produce fewer rows', function () {
    $poster = User::factory()->create(['name' => 'Quest giver']);
    $adventurer = User::factory()->deactivated()->create();
    $approver = User::factory()->deactivated()->create();
    pageDoneQuest($poster, $adventurer, $approver, pageUtc('2026-10-08 12:00:00'), [
        'xp' => 30,
        'poster_xp' => 5,
    ]);

    $this->actingAs($poster)
        ->get(route('hall-of-fame'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('topAdventurers', 1)
            ->where('topAdventurers.0.person.name', 'Quest giver')
            ->where('topAdventurers.0.xp', 5));
});

test('recent completions are newest first, limited to five, and include deactivated adventurers', function () {
    $poster = User::factory()->deactivated()->create();
    $approver = User::factory()->deactivated()->create();
    $adventurer = User::factory()->deactivated()->create(['name' => 'Former member']);
    $viewer = User::factory()->create();

    pageDoneQuest($poster, $adventurer, $approver, pageUtc('2026-10-01 12:00:00'), ['title' => 'Oldest']);
    pageDoneQuest($poster, $adventurer, $approver, pageUtc('2026-10-03 12:00:00'), [
        'title' => 'Newest',
        'xp' => 45,
        'poster_xp' => 5,
    ]);
    Quest::factory()->inReview()->create([
        'poster_id' => $poster->id,
        'adventurer_id' => $adventurer->id,
        'approved_at' => pageUtc('2026-10-04 12:00:00'),
        'title' => 'Not done',
    ]);

    foreach (range(1, 5) as $number) {
        pageDoneQuest($poster, $adventurer, $approver, pageUtc("2026-10-0{$number} 13:00:00"), [
            'title' => "Completion {$number}",
            'xp' => 10,
        ]);
    }

    $this->actingAs($viewer)
        ->get(route('hall-of-fame'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('recentCompletions', 5)
            ->where('recentCompletions.0.title', 'Completion 5')
            ->where('recentCompletions.0.adventurer.id', $adventurer->id)
            ->where('recentCompletions.0.xp', 10)
            ->where('recentCompletions.1.title', 'Completion 4')
            ->where('recentCompletions.2.title', 'Completion 3')
            ->where('recentCompletions.3.title', 'Newest')
            ->where('recentCompletions.3.xp', 45)
            ->where('recentCompletions.4.title', 'Completion 2'));
});

test('feed times use Yangon calendar labels', function () {
    $this->travelTo('2026-10-27 05:30:00');
    $poster = User::factory()->deactivated()->create();
    $approver = User::factory()->deactivated()->create();
    $adventurer = User::factory()->deactivated()->create();
    $viewer = User::factory()->create();

    pageDoneQuest($poster, $adventurer, $approver, pageUtc('2026-10-27 00:05:00'), ['title' => 'Today']);
    pageDoneQuest($poster, $adventurer, $approver, pageUtc('2026-10-26 12:00:00'), ['title' => 'Yesterday']);
    pageDoneQuest($poster, $adventurer, $approver, pageUtc('2026-10-24 12:00:00'), ['title' => 'Three days']);
    pageDoneQuest($poster, $adventurer, $approver, pageUtc('2026-10-21 12:00:00'), ['title' => 'Six days']);
    pageDoneQuest($poster, $adventurer, $approver, pageUtc('2026-10-20 12:00:00'), ['title' => 'Seven days']);

    $this->actingAs($viewer)
        ->get(route('hall-of-fame'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('recentCompletions.0.when', 'Today')
            ->where('recentCompletions.1.when', 'Yesterday')
            ->where('recentCompletions.2.when', '3 days ago')
            ->where('recentCompletions.3.when', '6 days ago')
            ->where('recentCompletions.4.when', '20 Oct'));
});

test('feed times include the year for another calendar year', function () {
    $this->travelTo('2026-10-27 05:30:00');
    $poster = User::factory()->deactivated()->create();
    $approver = User::factory()->deactivated()->create();
    $adventurer = User::factory()->deactivated()->create();
    $viewer = User::factory()->create();
    pageDoneQuest($poster, $adventurer, $approver, pageUtc('2025-10-27 12:00:00'), ['title' => 'Last year']);

    $this->actingAs($viewer)
        ->get(route('hall-of-fame'))
        ->assertInertia(fn (Assert $page) => $page->where('recentCompletions.0.when', '27 Oct 2025'));
});

test('highlight props include levels and empty states when there are no completions', function () {
    $this->travelTo('2026-10-27 05:30:00');
    $viewer = User::factory()->create();

    $this->actingAs($viewer)
        ->get(route('hall-of-fame'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('highlights.mostCompleted', null)
            ->where('highlights.topGiver', null)
            ->where('highlights.newest', null)
            ->has('topAdventurers', 0)
            ->has('recentCompletions', 0));

    $poster = User::factory()->create(['name' => 'Quest giver']);
    $adventurer = User::factory()->create(['name' => 'Adventurer']);
    $approver = User::factory()->deactivated()->create();
    pageDoneQuest($poster, $adventurer, $approver, pageUtc('2026-10-08 12:00:00'), [
        'xp' => 10,
        'poster_xp' => 0,
    ]);

    $this->get(route('hall-of-fame'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('highlights.mostCompleted.person.name', 'Adventurer')
            ->where('highlights.mostCompleted.person.level', 1)
            ->where('highlights.mostCompleted.person.level_title', 'Apprentice')
            ->where('highlights.mostCompleted.count', 1)
            ->where('highlights.topGiver.person.name', 'Quest giver')
            ->where('highlights.newest.person.name', 'Adventurer')
            ->where('highlights.newest.first_on', '8 Oct'));
});

test('Hall of Fame query count stays fixed as users and quests grow', function () {
    $users = User::factory()->count(3)->create();
    $poster = $users[0];
    $adventurer = $users[1];
    $approver = $users[2];

    foreach (range(1, 3) as $number) {
        pageDoneQuest($poster, $adventurer, $approver, pageUtc("2026-10-0{$number} 12:00:00"));
    }

    $smallCount = pageHallOfFameQueryCount();

    $moreUsers = User::factory()->count(9)->create();
    $allUsers = $users->concat($moreUsers)->values();
    foreach (range(4, 30) as $number) {
        pageDoneQuest(
            $allUsers[$number % $allUsers->count()],
            $allUsers[($number + 1) % $allUsers->count()],
            $approver,
            pageUtc('2026-10-10 12:00:00'),
        );
    }

    $largeCount = pageHallOfFameQueryCount();

    expect($largeCount)->toBe($smallCount);
});

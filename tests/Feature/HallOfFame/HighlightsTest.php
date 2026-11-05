<?php

declare(strict_types=1);

use App\Models\Quest;
use App\Models\User;
use App\Support\HallOfFame;
use Carbon\CarbonImmutable;

/**
 * @param  array<string, mixed>  $attributes
 */
function hallOfFameDoneQuest(
    User $poster,
    User $adventurer,
    CarbonImmutable $approvedAt,
    array $attributes = [],
): Quest {
    return Quest::factory()->done()->create([
        'poster_id' => $poster->id,
        'adventurer_id' => $adventurer->id,
        'approved_at' => $approvedAt,
        ...$attributes,
    ]);
}

function hallOfFame(string $now): HallOfFame
{
    return new HallOfFame(CarbonImmutable::parse($now, 'Asia/Yangon'));
}

test('most completed counts only done quests approved this month', function () {
    $poster = User::factory()->create();
    $winner = User::factory()->create(['name' => 'Winner']);
    $other = User::factory()->create(['name' => 'Other']);

    hallOfFameDoneQuest($poster, $winner, CarbonImmutable::parse('2026-10-03 12:00:00', 'Asia/Yangon'));
    hallOfFameDoneQuest($poster, $winner, CarbonImmutable::parse('2026-10-04 12:00:00', 'Asia/Yangon'));
    hallOfFameDoneQuest($poster, $other, CarbonImmutable::parse('2026-10-05 12:00:00', 'Asia/Yangon'));
    hallOfFameDoneQuest($poster, $winner, CarbonImmutable::parse('2026-09-30 12:00:00', 'Asia/Yangon'));
    Quest::factory()->inReview()->create([
        'poster_id' => $poster->id,
        'adventurer_id' => $winner->id,
        'created_at' => CarbonImmutable::parse('2026-10-06 12:00:00', 'Asia/Yangon'),
    ]);

    $result = hallOfFame('2026-10-15 12:00:00')->mostCompleted();

    expect($result)
        ->not->toBeNull()
        ->and($result['user']->id)->toBe($winner->id)
        ->and($result['count'])->toBe(2);
});

test('most completed breaks ties by the earliest last approval and then lower id', function () {
    $poster = User::factory()->create();
    $earlier = User::factory()->create();
    $later = User::factory()->create();

    hallOfFameDoneQuest($poster, $earlier, CarbonImmutable::parse('2026-10-01 12:00:00', 'Asia/Yangon'));
    hallOfFameDoneQuest($poster, $earlier, CarbonImmutable::parse('2026-10-05 12:00:00', 'Asia/Yangon'));
    hallOfFameDoneQuest($poster, $later, CarbonImmutable::parse('2026-10-02 12:00:00', 'Asia/Yangon'));
    hallOfFameDoneQuest($poster, $later, CarbonImmutable::parse('2026-10-06 12:00:00', 'Asia/Yangon'));

    $result = hallOfFame('2026-10-15 12:00:00')->mostCompleted();

    expect($result['user']->id)->toBe($earlier->id);

    $sameTimeA = User::factory()->create();
    $sameTimeB = User::factory()->create();
    $sameTime = CarbonImmutable::parse('2026-10-10 12:00:00', 'Asia/Yangon');
    hallOfFameDoneQuest($poster, $sameTimeA, $sameTime);
    hallOfFameDoneQuest($poster, $sameTimeB, $sameTime);

    $tieResult = hallOfFame('2026-10-15 12:00:00')->mostCompleted();

    expect($tieResult['user']->id)->toBe($earlier->id);
});

test('top giver counts only their done quests approved this month', function () {
    $giver = User::factory()->create(['name' => 'Giver']);
    $otherGiver = User::factory()->create(['name' => 'Other giver']);
    $adventurer = User::factory()->create();

    hallOfFameDoneQuest($giver, $adventurer, CarbonImmutable::parse('2026-10-03 12:00:00', 'Asia/Yangon'));
    hallOfFameDoneQuest($giver, $adventurer, CarbonImmutable::parse('2026-10-04 12:00:00', 'Asia/Yangon'));
    hallOfFameDoneQuest($giver, $adventurer, CarbonImmutable::parse('2026-09-30 12:00:00', 'Asia/Yangon'));
    Quest::factory()->inReview()->create([
        'poster_id' => $giver->id,
        'adventurer_id' => $adventurer->id,
    ]);
    hallOfFameDoneQuest($otherGiver, $adventurer, CarbonImmutable::parse('2026-10-05 12:00:00', 'Asia/Yangon'));

    $result = hallOfFame('2026-10-15 12:00:00')->topGiver();

    expect($result['user']->id)->toBe($giver->id)
        ->and($result['count'])->toBe(2);
});

test('newest picks the most recent first approval in this month', function () {
    $poster = User::factory()->create();
    $oldAdventurer = User::factory()->create();
    $early = User::factory()->create();
    $newest = User::factory()->create();

    hallOfFameDoneQuest($poster, $oldAdventurer, CarbonImmutable::parse('2026-09-30 12:00:00', 'Asia/Yangon'));
    hallOfFameDoneQuest($poster, $oldAdventurer, CarbonImmutable::parse('2026-10-12 12:00:00', 'Asia/Yangon'));
    hallOfFameDoneQuest($poster, $early, CarbonImmutable::parse('2026-10-03 12:00:00', 'Asia/Yangon'));
    hallOfFameDoneQuest($poster, $newest, CarbonImmutable::parse('2026-10-08 12:00:00', 'Asia/Yangon'));

    $result = hallOfFame('2026-10-15 12:00:00')->newest();

    expect($result['user']->id)->toBe($newest->id)
        ->and($result['firstAt']->setTimezone('Asia/Yangon')->toDateString())->toBe('2026-10-08');
});

test('monthly windows follow Yangon calendar boundaries', function () {
    $poster = User::factory()->create();
    $adventurer = User::factory()->create();
    $novemberFirst = CarbonImmutable::parse('2026-11-01 00:30:00', 'Asia/Yangon');
    $octoberLast = CarbonImmutable::parse('2026-10-31 23:30:00', 'Asia/Yangon');

    hallOfFameDoneQuest($poster, $adventurer, $novemberFirst->setTimezone('UTC'));
    hallOfFameDoneQuest($poster, $adventurer, $octoberLast->setTimezone('UTC'));

    $november = hallOfFame('2026-11-15 12:00:00')->mostCompleted();
    $october = hallOfFame('2026-10-15 12:00:00')->mostCompleted();

    expect($november['count'])->toBe(1)
        ->and($october['count'])->toBe(1)
        ->and(hallOfFame('2026-11-01 00:30:00')->monthName())->toBe('November')
        ->and(hallOfFame('2026-10-31 23:30:00')->monthName())->toBe('October');
});

test('all monthly highlights are empty without done quests this month', function () {
    $poster = User::factory()->create();
    $adventurer = User::factory()->create();

    Quest::factory()->inReview()->create([
        'poster_id' => $poster->id,
        'adventurer_id' => $adventurer->id,
    ]);
    hallOfFameDoneQuest($poster, $adventurer, CarbonImmutable::parse('2026-09-30 12:00:00', 'Asia/Yangon'));

    $hallOfFame = hallOfFame('2026-10-15 12:00:00');

    expect($hallOfFame->mostCompleted())->toBeNull()
        ->and($hallOfFame->topGiver())->toBeNull()
        ->and($hallOfFame->newest())->toBeNull();
});

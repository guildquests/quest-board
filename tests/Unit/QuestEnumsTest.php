<?php

declare(strict_types=1);

use App\Enums\ActivityEvent;
use App\Enums\Badge;
use App\Enums\Category;
use App\Enums\Difficulty;
use App\Enums\QuestStatus;

test('difficulty xp and effort follow config', function (Difficulty $difficulty, int $xp, string $effort) {
    expect($difficulty->xp())->toBe($xp)
        ->and($difficulty->effort())->toBe($effort);
})->with([
    'easy' => [Difficulty::Easy, 10, '~1 hr'],
    'medium' => [Difficulty::Medium, 30, '~half day'],
    'hard' => [Difficulty::Hard, 60, '1–2 days'],
]);

test('changing the difficulty config changes xp and effort', function () {
    config(['quests.difficulties.easy' => ['xp' => 99, 'effort' => '~5 min']]);

    expect(Difficulty::Easy->xp())->toBe(99)
        ->and(Difficulty::Easy->effort())->toBe('~5 min');
});

test('only claimed statuses are active', function (QuestStatus $status, bool $isActive) {
    expect($status->isActive())->toBe($isActive);
})->with([
    'open' => [QuestStatus::Open, false],
    'in progress' => [QuestStatus::InProgress, true],
    'in review' => [QuestStatus::InReview, true],
    'done' => [QuestStatus::Done, false],
]);

test('every badge has a label, a description, and a positive target from config', function () {
    foreach (Badge::cases() as $badge) {
        expect($badge->label())->not->toBeEmpty()
            ->and($badge->description())->not->toBeEmpty()
            ->and($badge->target())->toBeGreaterThan(0);
    }
});

test('changing the badge config changes the target', function () {
    config(['quests.badges.scout' => 5]);

    expect(Badge::Scout->target())->toBe(5);
});

test('every enum case has a label', function () {
    $cases = [
        ...QuestStatus::cases(),
        ...Category::cases(),
        ...Difficulty::cases(),
        ...ActivityEvent::cases(),
    ];

    foreach ($cases as $case) {
        expect($case->label())->not->toBeEmpty();
    }
});

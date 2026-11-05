<?php

declare(strict_types=1);

use App\Support\Level;

test('xp maps to the level in the config table', function (int $xp, int $number, string $title, int $floor, ?int $next) {
    $level = Level::fromXp($xp);

    expect($level->number)->toBe($number)
        ->and($level->title)->toBe($title)
        ->and($level->floor)->toBe($floor)
        ->and($level->next)->toBe($next)
        ->and($level->toArray())->toBe(['level' => $number, 'title' => $title, 'floor' => $floor, 'next' => $next]);
})->with([
    '0' => [0, 1, 'Apprentice', 0, 30],
    '29' => [29, 1, 'Apprentice', 0, 30],
    '30' => [30, 2, 'Squire', 30, 80],
    '149' => [149, 3, 'Adventurer', 80, 150],
    '150' => [150, 4, 'Knight', 150, 250],
    '400' => [400, 6, 'Legend', 400, null],
    '1000' => [1000, 6, 'Legend', 400, null],
]);

test('negative xp is level 1', function () {
    expect(Level::fromXp(-5)->number)->toBe(1);
});

test('levels follow the config', function () {
    config(['quests.levels' => [
        ['threshold' => 0, 'title' => 'Newbie'],
        ['threshold' => 10, 'title' => 'Pro'],
    ]]);

    expect(Level::fromXp(9)->toArray())->toBe(['level' => 1, 'title' => 'Newbie', 'floor' => 0, 'next' => 10])
        ->and(Level::fromXp(10)->toArray())->toBe(['level' => 2, 'title' => 'Pro', 'floor' => 10, 'next' => null]);
});

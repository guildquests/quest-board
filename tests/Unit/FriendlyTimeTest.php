<?php

declare(strict_types=1);

use App\Support\FriendlyTime;
use Carbon\CarbonImmutable;

test('ago uses the shared Yangon wording', function (string $date, string $expected) {
    $this->travelTo('2026-10-27 05:30:00');

    expect(FriendlyTime::ago(CarbonImmutable::parse($date, 'UTC')))->toBe($expected);
})->with([
    'today' => ['2026-10-27 00:05:00', 'Today'],
    'yesterday' => ['2026-10-26 12:00:00', 'Yesterday'],
    'two days ago' => ['2026-10-25 12:00:00', '2 days ago'],
    'six days ago' => ['2026-10-21 12:00:00', '6 days ago'],
    'seven days ago' => ['2026-10-20 12:00:00', '20 Oct'],
    'last year' => ['2025-10-27 12:00:00', '27 Oct 2025'],
]);

test('ago compares Yangon calendar days at the local boundary', function () {
    $this->travelTo('2026-10-26 23:30:00');
    $yesterdayAtYangonBoundary = CarbonImmutable::parse('2026-10-26 23:30:00', 'Asia/Yangon');

    expect($yesterdayAtYangonBoundary->utc()->toDateString())->toBe('2026-10-26')
        ->and(FriendlyTime::ago($yesterdayAtYangonBoundary))->toBe('Yesterday');
});

test('date uses the display timezone', function () {
    $date = CarbonImmutable::parse('2026-11-01 17:45:00', 'UTC');

    expect(FriendlyTime::date($date))->toBe('2 Nov');
});

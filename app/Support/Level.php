<?php

declare(strict_types=1);

namespace App\Support;

/**
 * A level derived from lifetime XP. Thresholds and titles come only from
 * config('quests.levels').
 */
final readonly class Level
{
    public function __construct(
        public int $number,
        public string $title,
        public int $floor,
        public ?int $next,
    ) {}

    /**
     * The highest level whose threshold is at or below the XP (negative XP counts as 0).
     */
    public static function fromXp(int $xp): self
    {
        /** @var list<array{threshold: int, title: string}> $levels */
        $levels = array_values(config('quests.levels'));
        $xp = max($xp, 0);

        $index = 0;
        foreach ($levels as $i => $level) {
            if ($level['threshold'] <= $xp) {
                $index = $i;
            }
        }

        return new self(
            number: $index + 1,
            title: $levels[$index]['title'],
            floor: $levels[$index]['threshold'],
            next: $levels[$index + 1]['threshold'] ?? null,
        );
    }

    /**
     * @return array{level: int, title: string, floor: int, next: int|null}
     */
    public function toArray(): array
    {
        return [
            'level' => $this->number,
            'title' => $this->title,
            'floor' => $this->floor,
            'next' => $this->next,
        ];
    }

    /**
     * @return array{level: int, title: string}|null
     */
    public function nextLevel(): ?array
    {
        /** @var list<array{threshold: int, title: string}> $levels */
        $levels = array_values(config('quests.levels'));
        $next = $levels[$this->number] ?? null;

        return $next === null ? null : [
            'level' => $this->number + 1,
            'title' => $next['title'],
        ];
    }
}

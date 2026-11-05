<?php

declare(strict_types=1);

namespace App\Enums;

enum Difficulty: string
{
    case Easy = 'easy';
    case Medium = 'medium';
    case Hard = 'hard';

    public function label(): string
    {
        return match ($this) {
            self::Easy => 'Easy',
            self::Medium => 'Medium',
            self::Hard => 'Hard',
        };
    }

    /**
     * XP the adventurer earns, from config('quests.difficulties').
     */
    public function xp(): int
    {
        return (int) config("quests.difficulties.{$this->value}.xp");
    }

    /**
     * The effort hint shown beside the difficulty, from config('quests.difficulties').
     */
    public function effort(): string
    {
        return (string) config("quests.difficulties.{$this->value}.effort");
    }
}

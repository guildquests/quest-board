<?php

declare(strict_types=1);

namespace App\Enums;

enum QuestStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case InReview = 'in_review';
    case Done = 'done';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::InProgress => 'In progress',
            self::InReview => 'In review',
            self::Done => 'Done',
        };
    }

    /**
     * Whether the quest is claimed and still counts as its adventurer's one active quest.
     */
    public function isActive(): bool
    {
        return match ($this) {
            self::InProgress, self::InReview => true,
            self::Open, self::Done => false,
        };
    }
}

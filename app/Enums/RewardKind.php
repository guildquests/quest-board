<?php

declare(strict_types=1);

namespace App\Enums;

enum RewardKind: string
{
    case Adventurer = 'adventurer';
    case Poster = 'poster';

    public function label(): string
    {
        return match ($this) {
            self::Adventurer => 'Adventurer',
            self::Poster => 'Poster',
        };
    }
}

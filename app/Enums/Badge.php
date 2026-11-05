<?php

declare(strict_types=1);

namespace App\Enums;

enum Badge: string
{
    case FirstQuest = 'first_quest';
    case Scout = 'scout';
    case Toolsmith = 'toolsmith';
    case QuestGiver = 'quest_giver';
    case Explorer = 'explorer';

    public function label(): string
    {
        return match ($this) {
            self::FirstQuest => 'First Quest',
            self::Scout => 'Scout',
            self::Toolsmith => 'Toolsmith',
            self::QuestGiver => 'Quest Giver',
            self::Explorer => 'Explorer',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::FirstQuest => 'Finish 1 quest',
            self::Scout => 'Finish 3 Research quests',
            self::Toolsmith => 'Finish 3 Tooling quests',
            self::QuestGiver => '5 of your posted quests completed',
            self::Explorer => 'Quests in 3 categories',
        };
    }

    /**
     * Count needed to earn the badge, from config('quests.badges').
     */
    public function target(): int
    {
        return (int) config("quests.badges.{$this->value}");
    }
}

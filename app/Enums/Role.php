<?php

declare(strict_types=1);

namespace App\Enums;

enum Role: string
{
    case Member = 'member';
    case Lead = 'lead';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Member => 'Member',
            self::Lead => 'Lead',
            self::Admin => 'Admin',
        };
    }

    /**
     * The line shown in the role guide under the Members table.
     */
    public function description(): string
    {
        return match ($this) {
            self::Member => 'Take quests, post quests, earn XP.',
            self::Lead => 'Everything members do, plus approve or send back quests.',
            self::Admin => 'Everything leads do, plus manage members.',
        };
    }

    /**
     * Whether this role can approve or send back turned-in quests.
     */
    public function canReview(): bool
    {
        return match ($this) {
            self::Member => false,
            self::Lead, self::Admin => true,
        };
    }
}

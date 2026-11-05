<?php

declare(strict_types=1);

namespace App\Enums;

enum ActivityEvent: string
{
    case Posted = 'posted';
    case Accepted = 'accepted';
    case Dropped = 'dropped';
    case TurnedIn = 'turned_in';
    case SentBack = 'sent_back';
    case Approved = 'approved';
    case Expired = 'expired';
    case Released = 'released';

    /**
     * The phrase shown in a quest's activity log.
     */
    public function label(): string
    {
        return match ($this) {
            self::Posted => 'posted this quest',
            self::Accepted => 'accepted this quest',
            self::Dropped => 'dropped this quest',
            self::TurnedIn => 'turned in the quest',
            self::SentBack => 'sent the quest back',
            self::Approved => 'approved the quest',
            self::Expired => 'Claim expired. The quest is open again.',
            self::Released => "Released when the adventurer's account was deactivated",
        };
    }
}

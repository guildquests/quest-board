<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\Badge;
use App\Models\User;
use App\Models\UserBadge;
use LogicException;

final readonly class PublicProfile
{
    /**
     * @param  array{name: string, portrait_url: string|null, level: int, level_title: string, xp: int, quests_done: int, badges: list<array{key: string, label: string, description: string}>, joined_year: int}  $member
     */
    private function __construct(private array $member) {}

    public static function for(User $user): self
    {
        $user->loadProgress();
        $level = $user->level();
        $earned = $user->badges()
            ->get()
            ->map(fn (UserBadge $userBadge): string => $userBadge->badge->value)
            ->all();

        $badges = array_values(array_map(
            static fn (Badge $badge): array => [
                'key' => $badge->value,
                'label' => $badge->label(),
                'description' => $badge->description(),
            ],
            array_filter(
                Badge::cases(),
                static fn (Badge $badge): bool => in_array($badge->value, $earned, true),
            ),
        ));

        if ($user->created_at === null) {
            throw new LogicException('Public profiles need a creation date.');
        }

        return new self([
            'name' => $user->name,
            'portrait_url' => $user->portrait_url,
            'level' => $level->number,
            'level_title' => $level->title,
            'xp' => $user->lifetimeXp(),
            'quests_done' => $user->questsDone(),
            'badges' => $badges,
            'joined_year' => (int) $user->created_at
                ->setTimezone((string) config('app.display_timezone'))
                ->format('Y'),
        ]);
    }

    /**
     * @return array{name: string, portrait_url: string|null, level: int, level_title: string, xp: int, quests_done: int, badges: list<array{key: string, label: string, description: string}>, joined_year: int}
     */
    public function toArray(): array
    {
        return $this->member;
    }
}

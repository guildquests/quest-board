<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\Badge;
use App\Enums\Category;
use App\Enums\QuestStatus;
use App\Models\Quest;
use App\Models\User;
use App\Models\UserBadge;

/**
 * Progress toward each badge, computed from Done quests (three queries).
 * Targets come only from config('quests.badges').
 */
final readonly class BadgeProgress
{
    /**
     * @param  array<string, int>  $doneByCategory  Done quests as adventurer, keyed by category value
     * @param  list<string>  $earned  Stored badge values
     */
    private function __construct(
        private array $doneByCategory,
        private int $donePosted,
        private array $earned,
    ) {}

    public static function for(User $user): self
    {
        /** @var array<string, int> $doneByCategory */
        $doneByCategory = Quest::query()
            ->where('adventurer_id', $user->id)
            ->where('status', QuestStatus::Done)
            ->selectRaw('category, count(*) as total')
            ->groupBy('category')
            ->pluck('total', 'category')
            ->map(fn (mixed $total): int => (int) $total)
            ->all();

        $donePosted = Quest::query()
            ->where('poster_id', $user->id)
            ->where('status', QuestStatus::Done)
            ->count();

        /** @var list<string> $earned */
        $earned = UserBadge::query()
            ->where('user_id', $user->id)
            ->pluck('badge')
            ->map(fn (mixed $badge): string => $badge instanceof Badge ? $badge->value : (string) $badge)
            ->values()
            ->all();

        return new self($doneByCategory, $donePosted, $earned);
    }

    public function current(Badge $badge): int
    {
        return match ($badge) {
            Badge::FirstQuest => array_sum($this->doneByCategory),
            Badge::Scout => $this->doneByCategory[Category::Research->value] ?? 0,
            Badge::Toolsmith => $this->doneByCategory[Category::Tooling->value] ?? 0,
            Badge::QuestGiver => $this->donePosted,
            Badge::Explorer => count($this->doneByCategory),
        };
    }

    public function earned(Badge $badge): bool
    {
        return in_array($badge->value, $this->earned, true);
    }

    /**
     * Every badge whose target is met, earned or not.
     *
     * @return list<Badge>
     */
    public function metTargets(): array
    {
        return array_values(array_filter(
            Badge::cases(),
            fn (Badge $badge): bool => $this->current($badge) >= $badge->target(),
        ));
    }

    /**
     * @return list<array{key: string, label: string, description: string, current: int, target: int, earned: bool}>
     */
    public function toArray(): array
    {
        return array_map(function (Badge $badge): array {
            $earned = $this->earned($badge);
            $target = $badge->target();
            $current = $this->current($badge);

            return [
                'key' => $badge->value,
                'label' => $badge->label(),
                'description' => $badge->description(),
                'current' => $earned ? min($current, $target) : $current,
                'target' => $target,
                'earned' => $earned,
            ];
        }, Badge::cases());
    }
}

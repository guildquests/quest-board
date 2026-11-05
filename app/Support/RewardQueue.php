<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\RewardKind;
use App\Models\Quest;
use App\Models\Reward;
use App\Models\User;
use App\Models\UserBadge;
use Illuminate\Database\Eloquent\Collection;
use LogicException;

final readonly class RewardQueue
{
    /**
     * @param  list<array<string, mixed>>|list<int>  $items
     * @param  array<string, mixed>|null  $summary
     */
    private function __construct(
        private string $mode,
        private array $items,
        private ?array $summary,
    ) {}

    public static function startLevelKey(User $user): string
    {
        return 'rewards.start_level.'.$user->id;
    }

    public static function for(User $user, ?int $startLevel = null): self
    {
        /** @var Collection<int, Reward> $rewards */
        $rewards = Reward::query()
            ->where('user_id', $user->id)
            ->whereNull('seen_at')
            ->with(['quest.adventurer', 'quest.approver'])
            ->oldest('created_at')
            ->oldest('id')
            ->get();

        if ($rewards->isEmpty()) {
            return new self('none', [], null);
        }

        $firstReward = $rewards->first();
        $lastReward = $rewards->last();

        $badgesByQuest = self::badgesByQuest($user, $rewards);
        $items = [];

        foreach ($rewards as $index => $reward) {
            $items[] = self::item(
                $reward,
                $badgesByQuest[(int) $reward->quest_id] ?? [],
                $index === $rewards->count() - 1,
                $firstReward,
                $lastReward,
                $startLevel,
            );
        }

        $summaryAfter = (int) config('quests.reward_summary_after');

        if ($rewards->count() > $summaryAfter) {
            return new self(
                'summary',
                array_values($rewards->pluck('id')->map(fn (mixed $id): int => (int) $id)->all()),
                self::summary($rewards, $badgesByQuest, $startLevel),
            );
        }

        return new self('single', $items, null);
    }

    /**
     * @return array{mode: string, items: list<array<string, mixed>>|list<int>, summary: array<string, mixed>|null}
     */
    public function toArray(): array
    {
        return [
            'mode' => $this->mode,
            'items' => $this->items,
            'summary' => $this->summary,
        ];
    }

    /**
     * @param  Collection<int, Reward>  $rewards
     * @return array<int, list<array{key: string, name: string}>>
     */
    private static function badgesByQuest(User $user, Collection $rewards): array
    {
        $questIds = $rewards->pluck('quest_id')->map(fn (mixed $id): int => (int) $id)->all();
        $badges = UserBadge::query()
            ->where('user_id', $user->id)
            ->whereIn('quest_id', $questIds)
            ->oldest('id')
            ->get();

        $badgesByQuest = [];
        foreach ($badges as $badge) {
            if ($badge->quest_id === null) {
                continue;
            }

            $badgesByQuest[(int) $badge->quest_id][] = [
                'key' => $badge->badge->value,
                'name' => $badge->badge->label(),
            ];
        }

        return $badgesByQuest;
    }

    /**
     * @param  list<array{key: string, name: string}>  $badges
     * @return array<string, mixed>
     */
    private static function item(
        Reward $reward,
        array $badges,
        bool $isLast,
        Reward $firstReward,
        Reward $lastReward,
        ?int $startLevel,
    ): array {
        $fromXp = (int) $reward->xp_before;
        $toXp = $fromXp + (int) $reward->xp;
        $levelFrom = Level::fromXp($fromXp);
        $levelTo = Level::fromXp($toXp);
        $barLevel = ! $isLast && $levelTo->number > $levelFrom->number
            ? $levelFrom
            : $levelTo;

        $item = [
            'id' => $reward->id,
            'kind' => $reward->kind->value,
            'quest' => self::quest($reward->quest),
            'by_label' => self::byLabel($reward),
            'xp' => (int) $reward->xp,
            'from_xp' => $fromXp,
            'to_xp' => $toXp,
            'level_from' => $levelFrom->number,
            'level_to' => $levelTo->number,
            'floor' => $barLevel->floor,
            'next' => $barLevel->next,
            'badges' => $badges,
        ];

        if ($isLast) {
            $firstLevelNumber = $startLevel ?? Level::fromXp((int) $firstReward->xp_before)->number;
            $finalXp = (int) $lastReward->xp_before + (int) $lastReward->xp;
            $finalLevel = Level::fromXp($finalXp);
            $item['level_up'] = $finalLevel->number > $firstLevelNumber
                ? ['level' => $finalLevel->number, 'title' => $finalLevel->title]
                : null;
        }

        return $item;
    }

    /**
     * @param  Collection<int, Reward>  $rewards
     * @param  array<int, list<array{key: string, name: string}>>  $badgesByQuest
     * @return array<string, mixed>
     */
    private static function summary(Collection $rewards, array $badgesByQuest, ?int $startLevel): array
    {
        $firstReward = $rewards->first();
        $lastReward = $rewards->last();

        if ($firstReward === null || $lastReward === null) {
            throw new LogicException('A reward summary needs at least one reward.');
        }

        $fromXp = (int) $firstReward->xp_before;
        $toXp = (int) $lastReward->xp_before + (int) $lastReward->xp;
        $firstLevel = Level::fromXp($fromXp);
        $finalLevel = Level::fromXp($toXp);
        $badges = [];
        $seenBadges = [];

        foreach ($badgesByQuest as $questBadges) {
            foreach ($questBadges as $badge) {
                if (isset($seenBadges[$badge['key']])) {
                    continue;
                }

                $seenBadges[$badge['key']] = true;
                $badges[] = $badge;
            }
        }

        return [
            'count' => $rewards->count(),
            'total_xp' => (int) $rewards->sum('xp'),
            'from_xp' => $fromXp,
            'to_xp' => $toXp,
            'level_from' => $firstLevel->number,
            'level_up' => $finalLevel->number > ($startLevel ?? $firstLevel->number)
                ? ['level' => $finalLevel->number, 'title' => $finalLevel->title]
                : null,
            'floor' => $finalLevel->floor,
            'next' => $finalLevel->next,
            'badges' => $badges,
        ];
    }

    /**
     * @return array{id: int, title: string, category: string, category_label: string}
     */
    private static function quest(Quest $quest): array
    {
        return [
            'id' => $quest->id,
            'title' => $quest->title,
            'category' => $quest->category->value,
            'category_label' => $quest->category->label(),
        ];
    }

    private static function byLabel(Reward $reward): string
    {
        $quest = $reward->quest;

        return match ($reward->kind) {
            RewardKind::Adventurer => 'Approved by '.self::personName($quest->approver),
            RewardKind::Poster => 'Completed by '.self::personName($quest->adventurer),
        };
    }

    private static function personName(?User $user): string
    {
        if ($user === null) {
            throw new LogicException('A reward quest needs both people loaded.');
        }

        return $user->name;
    }
}

<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\QuestStatus;
use App\Models\Quest;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

final class HallOfFame
{
    public function __construct(private readonly CarbonImmutable $now) {}

    public static function current(): self
    {
        return new self(CarbonImmutable::instance(now()));
    }

    public function monthName(): string
    {
        return $this->localNow()->format('F');
    }

    /**
     * @return array{user: User, count: int}|null
     */
    public function mostCompleted(): ?array
    {
        return $this->monthlyWinner('adventurer_id');
    }

    /**
     * @return array{user: User, count: int}|null
     */
    public function topGiver(): ?array
    {
        return $this->monthlyWinner('poster_id');
    }

    /**
     * @return array{user: User, firstAt: CarbonImmutable}|null
     */
    public function newest(): ?array
    {
        [$start, $end] = $this->monthWindowUtc();

        $firstApprovals = DB::table('quests')
            ->select('adventurer_id')
            ->selectRaw('MIN(approved_at) AS first_approved_at')
            ->where('status', QuestStatus::Done->value)
            ->whereNotNull('adventurer_id')
            ->groupBy('adventurer_id');

        $row = DB::table('quests')
            ->fromSub($firstApprovals, 'first_approvals')
            ->join('quests', function (JoinClause $join): void {
                $join->on('quests.adventurer_id', '=', 'first_approvals.adventurer_id')
                    ->on('quests.approved_at', '=', 'first_approvals.first_approved_at');
            })
            ->where('first_approvals.first_approved_at', '>=', $start)
            ->where('first_approvals.first_approved_at', '<', $end)
            ->select([
                'first_approvals.adventurer_id',
                'first_approvals.first_approved_at',
                'quests.id AS first_quest_id',
            ])
            ->orderByDesc('first_approvals.first_approved_at')
            ->orderByDesc('quests.id')
            ->first();

        if ($row === null) {
            return null;
        }

        $user = User::query()
            ->withProgress()
            ->find((int) $row->adventurer_id);

        if ($user === null) {
            return null;
        }

        return [
            'user' => $user,
            'firstAt' => CarbonImmutable::parse($row->first_approved_at, 'UTC'),
        ];
    }

    /**
     * @return Collection<int, User>
     */
    public function topAdventurers(int $limit = 5): Collection
    {
        $done = function (Builder $query): void {
            /** @var Builder<Quest> $query */
            $query->status(QuestStatus::Done);
        };

        $users = User::query()
            ->whereNull('deactivated_at')
            ->withProgress()
            ->withMax(['adventuredQuests as latest_adventurer_approval' => $done], 'approved_at')
            ->withMax(['postedQuests as latest_poster_approval' => $done], 'approved_at')
            ->get()
            ->filter(fn (User $user): bool => $user->lifetimeXp() > 0)
            ->sort(function (User $left, User $right): int {
                $xp = $right->lifetimeXp() <=> $left->lifetimeXp();
                if ($xp !== 0) {
                    return $xp;
                }

                $questsDone = $right->questsDone() <=> $left->questsDone();
                if ($questsDone !== 0) {
                    return $questsDone;
                }

                $approval = $this->compareApprovalDates($left, $right);
                if ($approval !== 0) {
                    return $approval;
                }

                return $left->id <=> $right->id;
            });

        return $users->values()->take($limit)->values();
    }

    /**
     * @return Collection<int, Quest>
     */
    public function recentCompletions(int $limit = 5): Collection
    {
        return Quest::query()
            ->status(QuestStatus::Done)
            ->whereNotNull('adventurer_id')
            ->whereNotNull('approved_at')
            ->with('adventurer')
            ->orderByDesc('approved_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    /**
     * @return array{user: User, count: int}|null
     */
    private function monthlyWinner(string $column): ?array
    {
        [$start, $end] = $this->monthWindowUtc();

        $row = DB::table('quests')
            ->select($column)
            ->selectRaw('COUNT(*) AS quest_count, MAX(approved_at) AS last_approved_at')
            ->where('status', QuestStatus::Done->value)
            ->whereNotNull($column)
            ->where('approved_at', '>=', $start)
            ->where('approved_at', '<', $end)
            ->groupBy($column)
            ->orderByDesc('quest_count')
            ->orderBy('last_approved_at')
            ->orderBy($column)
            ->first();

        if ($row === null) {
            return null;
        }

        $user = User::query()
            ->withProgress()
            ->find((int) $row->{$column});

        if ($user === null) {
            return null;
        }

        return [
            'user' => $user,
            'count' => (int) $row->quest_count,
        ];
    }

    private function compareApprovalDates(User $left, User $right): int
    {
        $leftDate = $this->latestApproval($left);
        $rightDate = $this->latestApproval($right);

        if ($leftDate === null && $rightDate === null) {
            return 0;
        }

        if ($leftDate === null) {
            return 1;
        }

        if ($rightDate === null) {
            return -1;
        }

        return $leftDate->getTimestamp() <=> $rightDate->getTimestamp();
    }

    private function latestApproval(User $user): ?CarbonImmutable
    {
        $latest = null;

        foreach (['latest_adventurer_approval', 'latest_poster_approval'] as $attribute) {
            $value = $user->getAttribute($attribute);
            if ($value === null) {
                continue;
            }

            $candidate = $value instanceof CarbonImmutable
                ? $value
                : CarbonImmutable::parse((string) $value, 'UTC');

            if ($latest === null || $candidate->greaterThan($latest)) {
                $latest = $candidate;
            }
        }

        return $latest;
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function monthWindowUtc(): array
    {
        $localStart = $this->localNow()->startOfMonth();

        return [
            $localStart->setTimezone('UTC'),
            $localStart->addMonthNoOverflow()->setTimezone('UTC'),
        ];
    }

    private function localNow(): CarbonImmutable
    {
        return $this->now->setTimezone((string) config('app.display_timezone'));
    }
}

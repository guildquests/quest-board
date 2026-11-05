<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ActivityEvent;
use App\Enums\Difficulty;
use App\Enums\QuestStatus;
use App\Enums\RewardKind;
use App\Exceptions\QuestActionException;
use App\Models\Quest;
use App\Models\Reward;
use App\Models\User;
use App\Models\UserBadge;
use App\Support\BadgeProgress;
use Closure;
use Illuminate\Support\Facades\DB;

/**
 * Every quest transition and its activity log entry. Each method reloads the
 * quest under a row lock and re-checks its status, so a stale or simultaneous
 * action fails with a QuestActionException instead of corrupting the quest.
 */
class QuestService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function post(User $poster, array $data): Quest
    {
        return DB::transaction(function () use ($poster, $data): Quest {
            $quest = new Quest($data);
            // Set here, not only in the creating hook: the seeder runs without model events.
            $quest->public_id = Quest::newPublicId();
            $quest->poster_id = $poster->id;
            $quest->status = QuestStatus::Open;
            $quest->save();

            $this->log($quest, ActivityEvent::Posted, $poster);

            return $quest;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Quest $quest, array $data): Quest
    {
        $this->transition($quest, function (Quest $locked) use ($data): void {
            $this->requireStatus($locked, QuestStatus::Open, 'This quest is no longer open.');

            $locked->fill($data)->save();
        });

        return $quest;
    }

    public function delete(Quest $quest): void
    {
        DB::transaction(function () use ($quest): void {
            $locked = $this->lock($quest);
            $this->requireStatus($locked, QuestStatus::Open, 'This quest is no longer open.');

            $locked->delete();
        });
    }

    public function accept(Quest $quest, User $user): void
    {
        $this->transition($quest, function (Quest $locked) use ($user): void {
            if ($locked->status !== QuestStatus::Open) {
                throw new QuestActionException(
                    $locked->adventurer_id !== null && $locked->adventurer_id !== $user->id
                        ? 'Someone else just took this quest.'
                        : 'This quest is no longer open.',
                );
            }

            if ($locked->poster_id === $user->id) {
                throw new QuestActionException("You can't take a quest you posted.");
            }

            // Lock the user too, so two quests accepted at once can't both pass the check.
            User::query()->whereKey($user->id)->lockForUpdate()->first();

            if ($user->activeQuest() !== null) {
                throw new QuestActionException('You already have an active quest.');
            }

            $locked->status = QuestStatus::InProgress;
            $locked->adventurer_id = $user->id;
            $locked->claimed_at = now();
            $locked->due_at = now()->addDays((int) config('quests.claim_days'));
            $locked->save();

            $this->log($locked, ActivityEvent::Accepted, $user);
        });
    }

    public function drop(Quest $quest, User $user): void
    {
        $this->transition($quest, function (Quest $locked) use ($user): void {
            $this->requireAdventurer($locked, $user);

            $this->clearClaim($locked);
            $locked->status = QuestStatus::Open;
            $locked->save();

            $this->log($locked, ActivityEvent::Dropped, $user);
        });
    }

    public function turnIn(Quest $quest, User $user, ?string $url, ?string $note): void
    {
        $this->transition($quest, function (Quest $locked) use ($user, $url, $note): void {
            $this->requireAdventurer($locked, $user);

            $locked->status = QuestStatus::InReview;
            $locked->result_url = $url;
            $locked->result_note = $note;
            $locked->turned_in_at = now();
            $locked->save();

            $this->log($locked, ActivityEvent::TurnedIn, $user);
        });
    }

    public function sendBack(Quest $quest, User $lead, string $note): void
    {
        $this->transition($quest, function (Quest $locked) use ($lead, $note): void {
            $this->requireStatus($locked, QuestStatus::InReview, 'This quest is no longer in review.');

            if ($locked->adventurer_id === $lead->id) {
                throw new QuestActionException("You can't review your own quest.");
            }

            $locked->status = QuestStatus::InProgress;
            $locked->due_at = now()->addDays((int) config('quests.claim_days'));
            $locked->turned_in_at = null;
            $locked->save();

            $this->log($locked, ActivityEvent::SentBack, $lead, $note);
        });
    }

    public function approve(Quest $quest, User $lead, ?Difficulty $difficulty): void
    {
        $this->transition($quest, function (Quest $locked) use ($lead, $difficulty): void {
            $this->requireStatus($locked, QuestStatus::InReview, 'This quest is no longer in review.');

            if ($locked->adventurer_id === $lead->id) {
                throw new QuestActionException("You can't review your own quest.");
            }

            $adventurer = $locked->adventurer()->firstOrFail();
            $poster = $locked->poster()->firstOrFail();
            $adventurer->loadProgress();
            $adventurerXpBefore = $adventurer->lifetimeXp();
            $poster->loadProgress();
            $posterXpBefore = $poster->lifetimeXp();

            $locked->difficulty = $difficulty ?? $locked->difficulty;
            $locked->xp = $locked->difficulty->xp();
            $locked->poster_xp = (int) config('quests.poster_xp');
            $locked->approved_by = $lead->id;
            $locked->approved_at = now();
            $locked->status = QuestStatus::Done;
            $locked->save();

            $this->log($locked, ActivityEvent::Approved, $lead);

            $this->awardBadges($adventurer, $locked);
            $this->awardBadges($poster, $locked);

            Reward::query()->create([
                'user_id' => $adventurer->id,
                'quest_id' => $locked->id,
                'kind' => RewardKind::Adventurer,
                'xp' => $locked->xp,
                'xp_before' => $adventurerXpBefore,
            ]);

            if ($lead->id !== $locked->poster_id) {
                Reward::query()->create([
                    'user_id' => $poster->id,
                    'quest_id' => $locked->id,
                    'kind' => RewardKind::Poster,
                    'xp' => $locked->poster_xp,
                    'xp_before' => $posterXpBefore,
                ]);
            }
        });
    }

    /**
     * Send every overdue In progress quest back to Open.
     *
     * @return int How many quests expired
     */
    public function expireOverdueClaims(): int
    {
        $ids = Quest::query()
            ->status(QuestStatus::InProgress)
            ->where('due_at', '<', now())
            ->pluck('id');

        $expired = 0;

        foreach ($ids as $id) {
            DB::transaction(function () use ($id, &$expired): void {
                $locked = Quest::query()->whereKey($id)->lockForUpdate()->first();

                // Turned in, dropped, or sent back with a new deadline since the query.
                if ($locked === null
                    || $locked->status !== QuestStatus::InProgress
                    || $locked->due_at === null
                    || $locked->due_at->isFuture()) {
                    return;
                }

                $this->reopen($locked, ActivityEvent::Expired);
                $expired++;
            });
        }

        return $expired;
    }

    /**
     * Put the user's active quest back on the board, for when they are deactivated.
     */
    public function releaseFor(User $user): void
    {
        $quest = $user->activeQuest();

        if ($quest === null) {
            return;
        }

        DB::transaction(function () use ($quest, $user): void {
            $locked = $this->lock($quest);

            if (! $locked->status->isActive() || $locked->adventurer_id !== $user->id) {
                return;
            }

            $this->reopen($locked, ActivityEvent::Released);
        });
    }

    /**
     * Run a change on the quest under a row lock, then refresh the caller's copy.
     *
     * @param  Closure(Quest): void  $change
     */
    private function transition(Quest $quest, Closure $change): void
    {
        DB::transaction(fn () => $change($this->lock($quest)));

        $quest->refresh();
    }

    private function lock(Quest $quest): Quest
    {
        return Quest::query()->lockForUpdate()->findOrFail($quest->id);
    }

    private function requireStatus(Quest $quest, QuestStatus $status, string $message): void
    {
        if ($quest->status !== $status) {
            throw new QuestActionException($message);
        }
    }

    private function requireAdventurer(Quest $quest, User $user): void
    {
        $this->requireStatus($quest, QuestStatus::InProgress, 'This quest is no longer in progress.');

        if ($quest->adventurer_id !== $user->id) {
            throw new QuestActionException('This quest is not yours.');
        }
    }

    private function reopen(Quest $quest, ActivityEvent $event): void
    {
        $this->clearClaim($quest);
        $quest->status = QuestStatus::Open;
        $quest->save();

        $this->log($quest, $event, null);
    }

    /**
     * Clear the claim and turn-in fields shared by drop, expire, and release.
     */
    private function clearClaim(Quest $quest): void
    {
        $quest->adventurer_id = null;
        $quest->claimed_at = null;
        $quest->due_at = null;
        $quest->result_url = null;
        $quest->result_note = null;
        $quest->turned_in_at = null;
    }

    /**
     * Store every badge the user now qualifies for and has not earned yet.
     * insertOrIgnore leans on the unique (user_id, badge) index, so a repeat
     * or simultaneous check never duplicates or fails.
     */
    private function awardBadges(User $user, Quest $quest): void
    {
        $progress = BadgeProgress::for($user);
        $now = now();

        $rows = [];
        foreach ($progress->metTargets() as $badge) {
            if ($progress->earned($badge)) {
                continue;
            }

            $rows[] = [
                'user_id' => $user->id,
                'badge' => $badge->value,
                'quest_id' => $quest->id,
                'earned_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows !== []) {
            UserBadge::query()->insertOrIgnore($rows);
        }
    }

    private function log(Quest $quest, ActivityEvent $event, ?User $user, ?string $note = null): void
    {
        $quest->events()->create([
            'user_id' => $user?->id,
            'event' => $event,
            'note' => $note,
        ]);
    }
}

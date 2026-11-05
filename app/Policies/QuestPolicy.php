<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\QuestStatus;
use App\Models\Quest;
use App\Models\User;

/**
 * Who may do what to a quest, by its status. QuestService re-checks the status
 * under a lock; this gate decides who is allowed to try.
 */
class QuestPolicy
{
    public function update(User $user, Quest $quest): bool
    {
        return $this->canManage($user, $quest) && $quest->status === QuestStatus::Open;
    }

    public function delete(User $user, Quest $quest): bool
    {
        return $this->update($user, $quest);
    }

    public function accept(User $user, Quest $quest): bool
    {
        return $quest->status === QuestStatus::Open
            && $quest->poster_id !== $user->id
            && $user->activeQuest() === null;
    }

    public function drop(User $user, Quest $quest): bool
    {
        return $this->isActiveAdventurer($user, $quest);
    }

    public function turnIn(User $user, Quest $quest): bool
    {
        return $this->isActiveAdventurer($user, $quest);
    }

    public function sendBack(User $user, Quest $quest): bool
    {
        return $user->canReview()
            && $quest->status === QuestStatus::InReview
            && $quest->adventurer_id !== $user->id;
    }

    public function approve(User $user, Quest $quest): bool
    {
        return $this->sendBack($user, $quest) && $quest->adventurer_id !== $user->id;
    }

    private function canManage(User $user, Quest $quest): bool
    {
        return $quest->poster_id === $user->id || $user->isAdmin();
    }

    private function isActiveAdventurer(User $user, Quest $quest): bool
    {
        return $quest->adventurer_id === $user->id && $quest->status === QuestStatus::InProgress;
    }
}

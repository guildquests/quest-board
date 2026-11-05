<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Difficulty;
use App\Enums\QuestStatus;
use App\Exceptions\QuestActionException;
use App\Http\Requests\Quests\ApproveQuestRequest;
use App\Http\Requests\Quests\SendBackQuestRequest;
use App\Http\Requests\Quests\TurnInQuestRequest;
use App\Models\Quest;
use App\Services\QuestService;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Quest status changes. A stale request (the quest is no longer in the status
 * the action needs) gets the service's message as an error toast instead of a
 * 403, so a user who clicked a moment too late sees why.
 */
class QuestActionController extends Controller
{
    public function __construct(private readonly QuestService $quests) {}

    public function accept(Request $request, Quest $quest): RedirectResponse
    {
        $this->authorizeWhile($request, 'accept', $quest, QuestStatus::Open);

        return $this->run(function () use ($quest, $request): void {
            $this->quests->accept($quest, $request->user());

            $claimDays = (int) config('quests.claim_days');
            Inertia::flash([
                'toast' => [
                    'type' => 'success',
                    'message' => "Mission accepted! Turn it in within {$claimDays} days.",
                ],
                'mission_accepted' => true,
            ]);
        });
    }

    public function drop(Request $request, Quest $quest): RedirectResponse
    {
        $this->authorizeWhile($request, 'drop', $quest, QuestStatus::InProgress);

        return $this->run(fn () => $this->quests->drop($quest, $request->user()));
    }

    public function turnIn(TurnInQuestRequest $request, Quest $quest): RedirectResponse
    {
        $this->authorizeWhile($request, 'turnIn', $quest, QuestStatus::InProgress);

        return $this->run(fn () => $this->quests->turnIn(
            $quest,
            $request->user(),
            $request->validated('result_url'),
            $request->validated('result_note'),
        ));
    }

    public function sendBack(SendBackQuestRequest $request, Quest $quest): RedirectResponse
    {
        abort_unless($request->user()->canReview(), 403);
        $this->authorizeWhile($request, 'sendBack', $quest, QuestStatus::InReview);

        return $this->run(function () use ($quest, $request): void {
            $this->quests->sendBack(
                $quest,
                $request->user(),
                $request->validated('note'),
            );

            $quest->load('adventurer');
            Inertia::flash('toast', [
                'type' => 'success',
                'message' => "Sent back to {$quest->adventurer->name}.",
            ]);
        });
    }

    public function approve(ApproveQuestRequest $request, Quest $quest): RedirectResponse
    {
        abort_unless($request->user()->canReview(), 403);
        $this->authorizeWhile($request, 'approve', $quest, QuestStatus::InReview);

        return $this->run(function () use ($quest, $request): void {
            $this->quests->approve(
                $quest,
                $request->user(),
                Difficulty::tryFrom((string) $request->validated('difficulty')),
            );

            $quest->load(['adventurer', 'poster']);
            Inertia::flash('toast', [
                'type' => 'success',
                'message' => "{$quest->adventurer->name} earned {$quest->xp} XP for '{$quest->title}'. "
                    ."{$quest->poster->name} got +{$quest->poster_xp} XP for posting.",
            ]);
        });
    }

    /**
     * Check the policy only while the quest is in the status the action needs.
     * Otherwise the service throws the stale-action message.
     */
    private function authorizeWhile(Request $request, string $ability, Quest $quest, QuestStatus $status): void
    {
        if ($quest->status === $status) {
            Gate::forUser($request->user())->authorize($ability, $quest);
        }
    }

    /**
     * @param  Closure(): void  $action
     */
    private function run(Closure $action): RedirectResponse
    {
        try {
            $action();
        } catch (QuestActionException $exception) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $exception->getMessage()]);
        }

        return back();
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Category;
use App\Enums\QuestStatus;
use App\Models\Quest;
use App\Models\User;
use App\Support\Impersonation;
use App\Support\RewardQueue;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QuestBoardController extends Controller
{
    /**
     * The board: every quest, newest first, optionally narrowed by status and category.
     * Unknown filter values are ignored, so an old or edited link still shows the board.
     */
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(401);
        }

        $status = $this->enumFromQuery($request, 'status', QuestStatus::class);
        $category = $this->enumFromQuery($request, 'category', Category::class);

        $quests = Quest::query()
            ->with(['poster', 'adventurer'])
            ->when($status, fn ($query, QuestStatus $status) => $query->status($status))
            ->when($category, fn ($query, Category $category) => $query->category($category))
            ->latest()
            ->latest('id')
            ->get();

        $latest = Quest::query()
            ->with('adventurer')
            ->status(QuestStatus::Done)
            ->latest('approved_at')
            ->latest('id')
            ->first();

        return Inertia::render('quest-board', [
            'quests' => $quests->map(fn (Quest $quest): array => [
                'id' => $quest->id,
                'title' => $quest->title,
                'summary' => $quest->summary,
                'category' => $quest->category->value,
                'difficulty' => $quest->difficulty->value,
                'effort' => $quest->difficulty->effort(),
                // The stored amount once approved, so later config changes don't rewrite history.
                'xp' => $quest->xp ?? $quest->difficulty->xp(),
                'status' => $quest->status->value,
                'poster' => $this->person($quest->poster),
                'adventurer' => $quest->adventurer === null ? null : $this->person($quest->adventurer),
            ])->all(),
            'filters' => [
                'status' => $status?->value,
                'category' => $category?->value,
            ],
            'statuses' => array_map(
                fn (QuestStatus $status): array => ['value' => $status->value, 'label' => $status->label()],
                QuestStatus::cases(),
            ),
            'categories' => array_map(
                fn (Category $category): array => ['value' => $category->value, 'label' => $category->label()],
                Category::cases(),
            ),
            'latestCompletion' => $latest === null || $latest->adventurer === null ? null : [
                'id' => $latest->id,
                'title' => $latest->title,
                'xp' => $latest->xp,
                'adventurer' => $this->person($latest->adventurer),
            ],
            'rewards' => Impersonation::isActive($request) ? [] : RewardQueue::for(
                $user,
                $request->session()->has(RewardQueue::startLevelKey($user))
                    ? (int) $request->session()->get(RewardQueue::startLevelKey($user))
                    : null,
            )->toArray(),
        ]);
    }

    /**
     * @template T of \BackedEnum
     *
     * @param  class-string<T>  $enum
     * @return T|null
     */
    private function enumFromQuery(Request $request, string $key, string $enum): mixed
    {
        $value = $request->query($key);

        return is_string($value) ? $enum::tryFrom($value) : null;
    }

    /**
     * @return array{id: int, name: string, portrait_url: string|null}
     */
    private function person(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'portrait_url' => $user->portrait_url,
        ];
    }
}

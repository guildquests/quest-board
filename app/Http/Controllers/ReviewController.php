<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ActivityEvent;
use App\Enums\Difficulty;
use App\Enums\QuestStatus;
use App\Models\Quest;
use App\Models\QuestEvent;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReviewController extends Controller
{
    public function __invoke(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $quests = Quest::query()
            ->status(QuestStatus::InReview)
            ->with(['poster', 'adventurer' => fn ($query) => $query->withProgress()])
            ->orderBy('turned_in_at')
            ->orderBy('id')
            ->get();

        $weekStart = now((string) config('app.display_timezone'))
            ->startOfWeek()
            ->setTimezone('UTC');

        $recent = QuestEvent::query()
            ->whereIn('event', [ActivityEvent::Approved, ActivityEvent::SentBack])
            ->with(['quest.adventurer', 'user'])
            ->latest('created_at')
            ->latest('id')
            ->limit(5)
            ->get();

        // The adventurer to show is the one who turned the quest in, because a drop,
        // expiry or release clears the quest's current adventurer.
        $recentTurnIns = QuestEvent::query()
            ->whereIn('quest_id', $recent->pluck('quest_id'))
            ->where('event', ActivityEvent::TurnedIn)
            ->with('user')
            ->orderByDesc('id')
            ->get()
            ->groupBy('quest_id');

        return Inertia::render('reviews', [
            'quests' => $quests->map(fn (Quest $quest): array => [
                'id' => $quest->id,
                'title' => $quest->title,
                'category' => $quest->category->value,
                'category_label' => $quest->category->label(),
                'difficulty' => $quest->difficulty->value,
                'objectives' => $quest->objectives,
                'result_url' => $quest->result_url,
                'result_display' => $this->resultDisplay($quest->result_url),
                'result_note' => $quest->result_note,
                'submitted' => $this->relative($quest->turned_in_at),
                'adventurer' => [
                    ...$this->person($quest->adventurer),
                    'level' => $quest->adventurer->level()->number,
                    'level_title' => $quest->adventurer->level()->title,
                ],
                'poster' => $this->person($quest->poster),
                'own' => $quest->adventurer_id === $user->id,
            ])->all(),
            'difficulties' => array_map(
                fn (Difficulty $difficulty): array => [
                    'value' => $difficulty->value,
                    'label' => $difficulty->label(),
                    'effort' => $difficulty->effort(),
                    'xp' => $difficulty->xp(),
                ],
                Difficulty::cases(),
            ),
            'posterXp' => (int) config('quests.poster_xp'),
            'counts' => [
                'waiting' => $quests->count(),
                'approvedThisWeek' => Quest::query()
                    ->status(QuestStatus::Done)
                    ->where('approved_at', '>=', $weekStart)
                    ->count(),
            ],
            'recent' => $recent->map(function (QuestEvent $event) use ($recentTurnIns): array {
                $turnIn = $recentTurnIns->get($event->quest_id)?->first(
                    fn (QuestEvent $candidate): bool => $candidate->id < $event->id,
                );
                $adventurer = $event->quest->adventurer;

                if ($turnIn !== null && $turnIn->user !== null) {
                    $adventurer = $turnIn->user;
                }

                return [
                    'id' => $event->id,
                    'event' => $event->event->value,
                    'quest' => [
                        'id' => $event->quest->id,
                        'title' => $event->quest->title,
                        'xp' => $event->quest->xp,
                    ],
                    'adventurer' => $adventurer === null ? null : $this->person($adventurer),
                    'reviewer' => $event->user === null ? null : $this->person($event->user),
                    'note' => $event->note,
                    'when' => $this->relative($event->created_at),
                ];
            })->all(),
        ]);
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

    private function resultDisplay(?string $url): ?string
    {
        return $url === null ? null : preg_replace('/^https?:\/\//i', '', $url);
    }

    private function relative(?CarbonInterface $date): ?string
    {
        return $date?->setTimezone((string) config('app.display_timezone'))
            ->diffForHumans(now((string) config('app.display_timezone')));
    }
}

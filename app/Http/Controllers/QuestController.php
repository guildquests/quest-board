<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Category;
use App\Enums\Difficulty;
use App\Enums\QuestStatus;
use App\Exceptions\QuestActionException;
use App\Http\Requests\Quests\StoreQuestRequest;
use App\Models\Quest;
use App\Models\QuestEvent;
use App\Models\User;
use App\Services\QuestService;
use App\Support\QrCode;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class QuestController extends Controller
{
    public function __construct(private readonly QuestService $quests) {}

    public function create(): Response
    {
        return Inertia::render('quests/form', [
            'quest' => null,
            ...$this->options(),
        ]);
    }

    public function edit(Quest $quest): Response
    {
        Gate::authorize('update', $quest);

        return Inertia::render('quests/form', [
            'quest' => [
                'id' => $quest->id,
                'title' => $quest->title,
                'summary' => $quest->summary,
                'category' => $quest->category->value,
                'difficulty' => $quest->difficulty->value,
                'brief' => $quest->brief,
                'objectives' => $quest->objectives,
                'proof' => $quest->proof,
            ],
            ...$this->options(),
        ]);
    }

    public function store(StoreQuestRequest $request): RedirectResponse
    {
        $quest = $this->quests->post($request->user(), $request->validated());

        return to_route('quests.show', $quest);
    }

    public function update(StoreQuestRequest $request, Quest $quest): RedirectResponse
    {
        Gate::authorize('update', $quest);

        try {
            $this->quests->update($quest, $request->validated());
        } catch (QuestActionException $exception) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $exception->getMessage()]);
        }

        return to_route('quests.show', $quest);
    }

    public function destroy(Quest $quest): RedirectResponse
    {
        Gate::authorize('delete', $quest);

        try {
            $this->quests->delete($quest);
        } catch (QuestActionException $exception) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $exception->getMessage()]);

            return to_route('quests.show', $quest);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Quest deleted.']);

        return to_route('board');
    }

    public function show(Request $request, Quest $quest): Response
    {
        $user = $request->user();
        $quest->load([
            'poster' => fn ($query) => $query->withProgress(),
            'adventurer' => fn ($query) => $query->withProgress(),
            'approver',
            'events.user',
        ]);

        $canAccept = $user->can('accept', $quest);
        $publicUrl = route('quests.public', ['publicId' => $quest->public_id]);

        return Inertia::render('quests/show', [
            'quest' => [
                'id' => $quest->id,
                'title' => $quest->title,
                'summary' => $quest->summary,
                'category' => $quest->category->value,
                'category_label' => $quest->category->label(),
                'difficulty' => $quest->difficulty->value,
                'difficulty_label' => $quest->difficulty->label(),
                'effort' => $quest->difficulty->effort(),
                'brief' => $quest->brief,
                'objectives' => $quest->objectives,
                'proof' => $quest->proof,
                'status' => $quest->status->value,
                'status_label' => $quest->status->label(),
                'poster' => $this->leveledPerson($quest->poster),
                'adventurer' => $quest->adventurer === null ? null : $this->leveledPerson($quest->adventurer),
                'claimed_at' => $this->date($quest->claimed_at),
                'due_at' => $this->date($quest->due_at),
                'result_url' => $quest->result_url,
                'result_note' => $quest->result_note,
                'turned_in_at' => $this->date($quest->turned_in_at),
                // XP the adventurer earns: the stored amount once approved, the current difficulty's before.
                'reward_xp' => $quest->xp ?? $quest->difficulty->xp(),
                'xp' => $quest->xp,
                'approved_at' => $this->date($quest->approved_at),
            ],
            'claimDays' => (int) config('quests.claim_days'),
            'share' => [
                'public_url' => $publicUrl,
                'qr' => QrCode::svgDataUri($publicUrl),
            ],
            'events' => $quest->events->map(fn (QuestEvent $event): array => [
                'id' => $event->id,
                'user' => $event->user === null ? null : $this->person($event->user),
                'event' => $event->event->value,
                'phrase' => $event->event->label(),
                'note' => $event->note,
                'at' => $this->date($event->created_at, 'j M, H:i'),
            ])->all(),
            'can' => [
                'update' => $user->can('update', $quest),
                'delete' => $user->can('delete', $quest),
                'accept' => $canAccept,
                'drop' => $user->can('drop', $quest),
                'turnIn' => $user->can('turnIn', $quest),
                'review' => $user->can('sendBack', $quest),
            ],
            'acceptBlockedReason' => $quest->status === QuestStatus::Open && ! $canAccept
                ? "You can have one active quest at a time, and you can't take quests you posted."
                : null,
        ]);
    }

    /**
     * @return array{categories: list<array<string, string>>, difficulties: list<array<string, mixed>>}
     */
    private function options(): array
    {
        return [
            'categories' => array_map(
                fn (Category $category): array => ['value' => $category->value, 'label' => $category->label()],
                Category::cases(),
            ),
            'difficulties' => array_map(
                fn (Difficulty $difficulty): array => [
                    'value' => $difficulty->value,
                    'label' => $difficulty->label(),
                    'xp' => $difficulty->xp(),
                    'effort' => $difficulty->effort(),
                ],
                Difficulty::cases(),
            ),
        ];
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

    /**
     * @return array{id: int, name: string, portrait_url: string|null, level: int, level_title: string}
     */
    private function leveledPerson(User $user): array
    {
        $level = $user->level();

        return [
            ...$this->person($user),
            'level' => $level->number,
            'level_title' => $level->title,
        ];
    }

    private function date(?CarbonInterface $date, string $format = 'D, j M'): ?string
    {
        return $date?->setTimezone(config('app.display_timezone'))->format($format);
    }
}

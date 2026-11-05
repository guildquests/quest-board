<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Quest;
use App\Models\User;
use App\Support\FriendlyTime;
use App\Support\HallOfFame;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use LogicException;

class HallOfFameController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $hallOfFame = HallOfFame::current();

        return Inertia::render('hall-of-fame', [
            'month' => $hallOfFame->monthName(),
            'highlights' => [
                'mostCompleted' => $this->highlight($hallOfFame->mostCompleted()),
                'topGiver' => $this->highlight($hallOfFame->topGiver()),
                'newest' => $this->newestHighlight($hallOfFame->newest()),
            ],
            'topAdventurers' => $hallOfFame->topAdventurers()
                ->map(fn (User $user, int $index): array => [
                    'rank' => $index + 1,
                    'person' => $this->leveledPerson($user),
                    'quests_done' => $user->questsDone(),
                    'xp' => $user->lifetimeXp(),
                ])
                ->all(),
            'recentCompletions' => $hallOfFame->recentCompletions()
                ->map(fn (Quest $quest): array => $this->completion($quest))
                ->all(),
        ]);
    }

    /**
     * @param  array{user: User, count: int}|null  $highlight
     * @return array{person: array{id: int, name: string, portrait_url: string|null, level: int, level_title: string}, count: int}|null
     */
    private function highlight(?array $highlight): ?array
    {
        if ($highlight === null) {
            return null;
        }

        return [
            'person' => $this->leveledPerson($highlight['user']),
            'count' => $highlight['count'],
        ];
    }

    /**
     * @param  array{user: User, firstAt: CarbonInterface}|null  $highlight
     * @return array{person: array{id: int, name: string, portrait_url: string|null, level: int, level_title: string}, first_on: string}|null
     */
    private function newestHighlight(?array $highlight): ?array
    {
        if ($highlight === null) {
            return null;
        }

        return [
            'person' => $this->leveledPerson($highlight['user']),
            'first_on' => $highlight['firstAt']
                ->setTimezone((string) config('app.display_timezone'))
                ->format('j M'),
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
     * @return array{id: int, title: string, xp: int, approved_at: string, when: string, adventurer: array{id: int, name: string, portrait_url: string|null}}
     */
    private function completion(Quest $quest): array
    {
        $approvedAt = $quest->approved_at;
        $adventurer = $quest->adventurer;

        if ($approvedAt === null || $adventurer === null) {
            throw new LogicException('Done quests in the Hall of Fame need an approval date and adventurer.');
        }

        return [
            'id' => $quest->id,
            'title' => $quest->title,
            'xp' => (int) $quest->xp,
            'approved_at' => $approvedAt->toIso8601String(),
            'when' => FriendlyTime::ago($approvedAt),
            'adventurer' => $this->person($adventurer),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\QuestStatus;
use App\Models\Quest;
use App\Models\User;

final readonly class PublicQuest
{
    /**
     * @param  array{
     *     id: int,
     *     title: string,
     *     summary: string,
     *     category: string,
     *     category_label: string,
     *     difficulty: string,
     *     difficulty_label: string,
     *     effort: string,
     *     xp: int,
     *     brief: string,
     *     objectives: array<int, string>,
     *     proof: string,
     *     status: string,
     *     status_label: string,
     *     poster: array{name: string, portrait_url: string|null, level: int, level_title: string},
     *     completed_by: array{name: string, portrait_url: string|null, level: int, level_title: string}|null
     * }  $quest
     */
    private function __construct(private array $quest) {}

    public static function for(Quest $quest): self
    {
        $quest->load([
            'poster' => fn ($query) => $query->withProgress(),
            'adventurer' => fn ($query) => $query->withProgress(),
        ]);

        return new self([
            'id' => $quest->id,
            'title' => $quest->title,
            'summary' => $quest->summary,
            'category' => $quest->category->value,
            'category_label' => $quest->category->label(),
            'difficulty' => $quest->difficulty->value,
            'difficulty_label' => $quest->difficulty->label(),
            'effort' => $quest->difficulty->effort(),
            'xp' => $quest->xp ?? $quest->difficulty->xp(),
            'brief' => $quest->brief,
            'objectives' => $quest->objectives,
            'proof' => $quest->proof,
            'status' => $quest->status->value,
            'status_label' => $quest->status->label(),
            'poster' => self::person($quest->poster),
            'completed_by' => $quest->status === QuestStatus::Done && $quest->adventurer !== null
                ? self::person($quest->adventurer)
                : null,
        ]);
    }

    /**
     * @return array{
     *     id: int,
     *     title: string,
     *     summary: string,
     *     category: string,
     *     category_label: string,
     *     difficulty: string,
     *     difficulty_label: string,
     *     effort: string,
     *     xp: int,
     *     brief: string,
     *     objectives: array<int, string>,
     *     proof: string,
     *     status: string,
     *     status_label: string,
     *     poster: array{name: string, portrait_url: string|null, level: int, level_title: string},
     *     completed_by: array{name: string, portrait_url: string|null, level: int, level_title: string}|null
     * }
     */
    public function toArray(): array
    {
        return $this->quest;
    }

    /**
     * @return array{name: string, portrait_url: string|null, level: int, level_title: string}
     */
    private static function person(User $user): array
    {
        $level = $user->level();

        return [
            'name' => $user->name,
            'portrait_url' => $user->portrait_url,
            'level' => $level->number,
            'level_title' => $level->title,
        ];
    }
}

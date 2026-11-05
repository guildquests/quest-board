<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\Badge;
use App\Enums\QuestStatus;
use App\Models\Quest;
use App\Models\User;
use Carbon\CarbonInterface;
use LogicException;

final readonly class ProfilePage
{
    /**
     * @param  array<string, mixed>  $profile
     * @param  array<string, mixed>  $level
     * @param  array<string, mixed>  $stats
     * @param  array<string, mixed>  $quests
     * @param  list<array<string, mixed>>  $badges
     * @param  array{member: array<string, mixed>, public_url: string, qr: string|null, sharing_enabled: bool}  $card
     */
    private function __construct(
        private array $profile,
        private array $level,
        private array $stats,
        private array $quests,
        private array $badges,
        private array $card,
    ) {}

    public static function for(User $user): self
    {
        $user->loadProgress();
        $level = $user->level();

        $active = Quest::query()
            ->where('adventurer_id', $user->id)
            ->whereIn('status', [QuestStatus::InProgress, QuestStatus::InReview])
            ->orderByDesc('claimed_at')
            ->orderByDesc('id')
            ->get();

        $completed = Quest::query()
            ->where('adventurer_id', $user->id)
            ->status(QuestStatus::Done)
            ->orderByDesc('approved_at')
            ->orderByDesc('id')
            ->get();

        $posted = Quest::query()
            ->where('poster_id', $user->id)
            ->with('adventurer')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $badges = BadgeProgress::for($user)->toArray();
        $member = PublicProfile::for($user)->toArray();

        if ($user->public_id === null) {
            throw new LogicException('Profile users need a public id.');
        }

        $publicUrl = route('guild.member', ['publicId' => $user->public_id]);
        $sharingEnabled = (bool) $user->public_profile_enabled;

        return new self(
            profile: [
                'id' => $user->id,
                'name' => $user->name,
                'portrait_url' => $user->portrait_url,
                'role_label' => $user->role->label(),
                'member_since' => self::memberSince($user->created_at),
            ],
            level: [
                ...$level->toArray(),
                'xp' => $user->lifetimeXp(),
                'next_level' => $level->nextLevel(),
            ],
            stats: [
                'quests_done' => $user->questsDone(),
                'quests_posted' => $posted->count(),
                'total_xp' => $user->lifetimeXp(),
                'badges_earned' => count(array_filter(
                    $badges,
                    fn (array $badge): bool => $badge['earned'] === true,
                )),
                'badges_total' => count(Badge::cases()),
            ],
            quests: [
                'active' => $active->map(fn (Quest $quest): array => self::activeRow($quest))->all(),
                'completed' => $completed->map(fn (Quest $quest): array => self::completedRow($quest))->all(),
                'posted' => $posted->map(fn (Quest $quest): array => self::postedRow($quest))->all(),
            ],
            badges: $badges,
            card: [
                'member' => $member,
                'public_url' => $publicUrl,
                'qr' => $sharingEnabled ? QrCode::svgDataUri($publicUrl) : null,
                'sharing_enabled' => $sharingEnabled,
            ],
        );
    }

    /**
     * @return array{profile: array<string, mixed>, level: array<string, mixed>, stats: array<string, mixed>, quests: array<string, mixed>, badges: list<array<string, mixed>>, card: array{member: array<string, mixed>, public_url: string, qr: string|null, sharing_enabled: bool}, poster_xp: int}
     */
    public function toArray(): array
    {
        return [
            'profile' => $this->profile,
            'level' => $this->level,
            'stats' => $this->stats,
            'quests' => $this->quests,
            'badges' => $this->badges,
            'card' => $this->card,
            'poster_xp' => (int) config('quests.poster_xp'),
        ];
    }

    /**
     * @return array{id: int, title: string, category: string, category_label: string, difficulty: string, difficulty_label: string, status: string, detail: string, xp: int|null}
     */
    private static function activeRow(Quest $quest): array
    {
        $detail = match ($quest->status) {
            QuestStatus::InProgress => self::prefix($quest).' · due '.FriendlyTime::date(
                self::requiredDate($quest->due_at, 'In-progress quests need a due date.'),
            ),
            QuestStatus::InReview => self::prefix($quest).' · turned in '.self::sentenceTime(
                FriendlyTime::ago(self::requiredDate($quest->turned_in_at, 'In-review quests need a turn-in date.')),
            ),
            default => throw new LogicException('Unexpected active quest status.'),
        };

        return self::row($quest, $detail, $quest->difficulty->xp());
    }

    /**
     * @return array{id: int, title: string, category: string, category_label: string, difficulty: string, difficulty_label: string, status: string, detail: string, xp: int|null}
     */
    private static function completedRow(Quest $quest): array
    {
        $detail = self::prefix($quest).' · '.FriendlyTime::ago(
            self::requiredDate($quest->approved_at, 'Done quests need an approval date.'),
        );

        return self::row($quest, $detail, self::storedXp($quest));
    }

    /**
     * @return array{id: int, title: string, category: string, category_label: string, difficulty: string, difficulty_label: string, status: string, detail: string, xp: int|null}
     */
    private static function postedRow(Quest $quest): array
    {
        if ($quest->status === QuestStatus::Done) {
            if ($quest->adventurer === null) {
                throw new LogicException('Done posted quests need an adventurer.');
            }

            $detail = self::prefix($quest).' · done by '.$quest->adventurer->name;
            $xp = $quest->poster_xp === null ? null : (int) $quest->poster_xp;
        } else {
            $detail = self::prefix($quest).' · posted '.self::sentenceTime(FriendlyTime::ago(
                self::requiredDate($quest->created_at, 'Posted quests need a creation date.'),
            ));
            $xp = null;
        }

        return self::row($quest, $detail, $xp);
    }

    /**
     * @return array{id: int, title: string, category: string, category_label: string, difficulty: string, difficulty_label: string, status: string, detail: string, xp: int|null}
     */
    private static function row(Quest $quest, string $detail, ?int $xp): array
    {
        return [
            'id' => $quest->id,
            'title' => $quest->title,
            'category' => $quest->category->value,
            'category_label' => $quest->category->label(),
            'difficulty' => $quest->difficulty->value,
            'difficulty_label' => $quest->difficulty->label(),
            'status' => $quest->status->value,
            'detail' => $detail,
            'xp' => $xp,
        ];
    }

    private static function prefix(Quest $quest): string
    {
        return $quest->category->label().' · '.$quest->difficulty->label();
    }

    private static function sentenceTime(string $time): string
    {
        return match ($time) {
            'Today' => 'today',
            'Yesterday' => 'yesterday',
            default => $time,
        };
    }

    private static function storedXp(Quest $quest): ?int
    {
        return $quest->xp === null ? null : (int) $quest->xp;
    }

    private static function memberSince(?CarbonInterface $createdAt): string
    {
        if ($createdAt === null) {
            throw new LogicException('Profile users need a creation date.');
        }

        return $createdAt
            ->setTimezone((string) config('app.display_timezone'))
            ->format('M Y');
    }

    private static function requiredDate(?CarbonInterface $date, string $message): CarbonInterface
    {
        if ($date === null) {
            throw new LogicException($message);
        }

        return $date;
    }
}

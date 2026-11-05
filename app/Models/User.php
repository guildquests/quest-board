<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\QuestStatus;
use App\Enums\Role;
use App\Support\Level;
use Carbon\CarbonImmutable;
use Closure;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $public_id
 * @property bool $public_profile_enabled
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property Role $role
 * @property string|null $portrait
 * @property CarbonImmutable|null $deactivated_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read int|string|null $adventurer_xp
 * @property-read int|string|null $poster_xp_total
 * @property-read int|string|null $quests_done
 */
#[Fillable(['name', 'email', 'password', 'role', 'portrait'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            if ($user->public_id === null || $user->public_id === '') {
                $user->public_id = self::newPublicId();
            }

            if (! array_key_exists('public_profile_enabled', $user->getAttributes())) {
                $user->public_profile_enabled = false;
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'deactivated_at' => 'datetime',
            'public_profile_enabled' => 'boolean',
        ];
    }

    public function regeneratePublicId(): void
    {
        $this->forceFill(['public_id' => self::newPublicId()])->save();
    }

    private static function newPublicId(): string
    {
        do {
            $publicId = bin2hex(random_bytes(12));
        } while (self::query()->where('public_id', $publicId)->exists());

        return $publicId;
    }

    /**
     * Emails are stored lowercase so uniqueness and login ignore case.
     *
     * @return Attribute<string, string>
     */
    protected function email(): Attribute
    {
        return Attribute::make(
            set: fn (string $value): string => mb_strtolower($value),
        );
    }

    /**
     * Public path of the user's portrait, or null for the initials fallback.
     *
     * @return Attribute<string|null, never>
     */
    protected function portraitUrl(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->portrait === null
                ? null
                : config("quests.portraits.{$this->portrait}"),
        );
    }

    /**
     * @return HasMany<Quest, $this>
     */
    public function postedQuests(): HasMany
    {
        return $this->hasMany(Quest::class, 'poster_id');
    }

    /**
     * @return HasMany<Quest, $this>
     */
    public function adventuredQuests(): HasMany
    {
        return $this->hasMany(Quest::class, 'adventurer_id');
    }

    /**
     * @return HasMany<UserBadge, $this>
     */
    public function badges(): HasMany
    {
        return $this->hasMany(UserBadge::class);
    }

    /**
     * @return HasMany<Reward, $this>
     */
    public function rewards(): HasMany
    {
        return $this->hasMany(Reward::class);
    }

    /**
     * The user's quest that is In progress or In review, or null.
     */
    public function activeQuest(): ?Quest
    {
        return $this->adventuredQuests()
            ->whereIn('status', array_filter(
                QuestStatus::cases(),
                fn (QuestStatus $status): bool => $status->isActive(),
            ))
            ->first();
    }

    /**
     * Add lifetime XP parts and the quests-done count (Done quests only).
     *
     * @param  Builder<User>  $query
     */
    protected function scopeWithProgress(Builder $query): void
    {
        foreach (self::progressAggregates() as [$kind, $relations, $column]) {
            if ($kind === 'sum') {
                $query->withSum($relations, $column);
            } else {
                $query->withCount($relations);
            }
        }
    }

    /**
     * Load the same aggregates as withProgress() onto this model.
     */
    public function loadProgress(): static
    {
        foreach (self::progressAggregates() as [$kind, $relations, $column]) {
            if ($kind === 'sum') {
                $this->loadSum($relations, $column);
            } else {
                $this->loadCount($relations);
            }
        }

        return $this;
    }

    /**
     * The single definition of the progress aggregates, shared by the scope and the loader.
     *
     * @return list<array{0: 'sum'|'count', 1: array<string, Closure(Builder<Quest>): void>, 2: string|null}>
     */
    private static function progressAggregates(): array
    {
        $done = function (Builder $query): void {
            /** @var Builder<Quest> $query */
            $query->status(QuestStatus::Done);
        };

        return [
            ['sum', ['adventuredQuests as adventurer_xp' => $done], 'xp'],
            ['sum', ['postedQuests as poster_xp_total' => $done], 'poster_xp'],
            ['count', ['adventuredQuests as quests_done' => $done], null],
        ];
    }

    /**
     * Lifetime XP: XP of Done quests as adventurer plus poster XP of Done quests as poster.
     */
    public function lifetimeXp(): int
    {
        $this->assertProgressLoaded();

        return (int) $this->adventurer_xp + (int) $this->poster_xp_total;
    }

    public function questsDone(): int
    {
        $this->assertProgressLoaded();

        return (int) $this->quests_done;
    }

    public function level(): Level
    {
        return Level::fromXp($this->lifetimeXp());
    }

    private function assertProgressLoaded(): void
    {
        $attributes = $this->getAttributes();

        if (! array_key_exists('adventurer_xp', $attributes)
            || ! array_key_exists('poster_xp_total', $attributes)
            || ! array_key_exists('quests_done', $attributes)) {
            throw new LogicException('Progress aggregates are not loaded; use withProgress() or loadProgress().');
        }
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function canReview(): bool
    {
        return $this->role->canReview();
    }

    public function isActive(): bool
    {
        return $this->deactivated_at === null;
    }
}

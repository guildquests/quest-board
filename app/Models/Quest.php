<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Category;
use App\Enums\Difficulty;
use App\Enums\QuestStatus;
use Carbon\CarbonImmutable;
use Database\Factories\QuestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $public_id
 * @property int $poster_id
 * @property string $title
 * @property string $summary
 * @property Category $category
 * @property Difficulty $difficulty
 * @property string $brief
 * @property array<int, string> $objectives
 * @property string $proof
 * @property QuestStatus $status
 * @property int|null $adventurer_id
 * @property CarbonImmutable|null $claimed_at
 * @property CarbonImmutable|null $due_at
 * @property string|null $result_url
 * @property string|null $result_note
 * @property CarbonImmutable|null $turned_in_at
 * @property int|null $xp
 * @property int|null $poster_xp
 * @property int|null $approved_by
 * @property CarbonImmutable|null $approved_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['title', 'summary', 'category', 'difficulty', 'brief', 'objectives', 'proof'])]
class Quest extends Model
{
    /** @use HasFactory<QuestFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (Quest $quest): void {
            if (blank($quest->public_id)) {
                $quest->public_id = self::newPublicId();
            }
        });
    }

    public static function newPublicId(): string
    {
        do {
            $publicId = bin2hex(random_bytes(12));
        } while (self::query()->where('public_id', $publicId)->exists());

        return $publicId;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => Category::class,
            'difficulty' => Difficulty::class,
            'status' => QuestStatus::class,
            'objectives' => 'array',
            'claimed_at' => 'datetime',
            'due_at' => 'datetime',
            'turned_in_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'poster_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function adventurer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adventurer_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Activity log, oldest first.
     *
     * @return HasMany<QuestEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(QuestEvent::class)->oldest()->oldest('id');
    }

    /**
     * @param  Builder<Quest>  $query
     */
    protected function scopeStatus(Builder $query, QuestStatus $status): void
    {
        $query->where('status', $status);
    }

    /**
     * @param  Builder<Quest>  $query
     */
    protected function scopeCategory(Builder $query, Category $category): void
    {
        $query->where('category', $category);
    }
}

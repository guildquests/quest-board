<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ActivityEvent;
use Carbon\CarbonImmutable;
use Database\Factories\QuestEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $quest_id
 * @property int|null $user_id
 * @property ActivityEvent $event
 * @property string|null $note
 * @property CarbonImmutable|null $created_at
 */
class QuestEvent extends Model
{
    /** @use HasFactory<QuestEventFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $guarded = [];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event' => ActivityEvent::class,
        ];
    }

    /**
     * @return BelongsTo<Quest, $this>
     */
    public function quest(): BelongsTo
    {
        return $this->belongsTo(Quest::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

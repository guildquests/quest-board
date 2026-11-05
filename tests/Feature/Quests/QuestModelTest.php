<?php

declare(strict_types=1);

use App\Enums\ActivityEvent;
use App\Enums\Category;
use App\Enums\Difficulty;
use App\Enums\QuestStatus;
use App\Models\Quest;
use App\Models\QuestEvent;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('quest attributes are cast and round-trip', function () {
    $quest = Quest::factory()->create([
        'category' => Category::Docs,
        'difficulty' => Difficulty::Hard,
        'objectives' => ['One', 'Two'],
    ])->fresh();

    expect($quest->category)->toBe(Category::Docs)
        ->and($quest->difficulty)->toBe(Difficulty::Hard)
        ->and($quest->status)->toBe(QuestStatus::Open)
        ->and($quest->objectives)->toBe(['One', 'Two']);

    $done = Quest::factory()->done()->create()->fresh();

    expect($done->claimed_at)->toBeInstanceOf(DateTimeInterface::class)
        ->and($done->due_at)->toBeInstanceOf(DateTimeInterface::class)
        ->and($done->turned_in_at)->toBeInstanceOf(DateTimeInterface::class)
        ->and($done->approved_at)->toBeInstanceOf(DateTimeInterface::class);
});

test('quest relations resolve', function () {
    $quest = Quest::factory()->done()->create();

    expect($quest->poster)->toBeInstanceOf(User::class)
        ->and($quest->adventurer)->toBeInstanceOf(User::class)
        ->and($quest->approver)->toBeInstanceOf(User::class)
        ->and($quest->poster->postedQuests->pluck('id')->all())->toBe([$quest->id])
        ->and($quest->adventurer->adventuredQuests->pluck('id')->all())->toBe([$quest->id]);
});

test('events are ordered oldest first', function () {
    $quest = Quest::factory()->create();
    QuestEvent::factory()->for($quest)->create([
        'event' => ActivityEvent::Accepted,
        'created_at' => now(),
    ]);
    QuestEvent::factory()->for($quest)->create([
        'event' => ActivityEvent::Posted,
        'created_at' => now()->subHour(),
    ]);

    expect($quest->events->pluck('event')->all())
        ->toBe([ActivityEvent::Posted, ActivityEvent::Accepted])
        ->and($quest->events->first()->user)->toBeInstanceOf(User::class)
        ->and($quest->events->first()->quest->is($quest))->toBeTrue();
});

test('scopes filter by status and category', function () {
    Quest::factory()->create(['category' => Category::Docs]);
    $review = Quest::factory()->inReview()->create(['category' => Category::Tooling]);

    expect(Quest::status(QuestStatus::InReview)->pluck('id')->all())->toBe([$review->id])
        ->and(Quest::category(Category::Tooling)->pluck('id')->all())->toBe([$review->id])
        ->and(Quest::category(Category::Research)->count())->toBe(0);
});

test('activeQuest returns in progress and in review quests only', function (Closure $make, bool $expectsQuest) {
    $user = User::factory()->create();
    $quest = $make($user);

    expect($user->activeQuest()?->is($quest) ?? false)->toBe($expectsQuest);
})->with([
    'open' => [fn (User $user) => Quest::factory()->create(), false],
    'in progress' => [fn (User $user) => Quest::factory()->inProgress()->create(['adventurer_id' => $user->id]), true],
    'in review' => [fn (User $user) => Quest::factory()->inReview()->create(['adventurer_id' => $user->id]), true],
    'done' => [fn (User $user) => Quest::factory()->done()->create(['adventurer_id' => $user->id]), false],
]);

test('activeQuest is null without quests', function () {
    expect(User::factory()->create()->activeQuest())->toBeNull();
});

test('deleting a quest deletes its events', function () {
    $quest = Quest::factory()->create();
    QuestEvent::factory()->for($quest)->count(2)->create();

    $quest->delete();

    expect(QuestEvent::count())->toBe(0);
});

test('factory states produce consistent rows', function () {
    $inProgress = Quest::factory()->inProgress()->create();
    $inReview = Quest::factory()->inReview()->create();
    $done = Quest::factory()->done()->create();

    expect($inProgress->status)->toBe(QuestStatus::InProgress)
        ->and($inProgress->adventurer_id)->not->toBeNull()
        ->and($inProgress->due_at->greaterThan($inProgress->claimed_at))->toBeTrue()
        ->and($inProgress->turned_in_at)->toBeNull()
        ->and($inReview->status)->toBe(QuestStatus::InReview)
        ->and($inReview->turned_in_at)->not->toBeNull()
        ->and($inReview->xp)->toBeNull()
        ->and($done->status)->toBe(QuestStatus::Done)
        ->and($done->xp)->toBe($done->difficulty->xp())
        ->and($done->poster_xp)->toBe(config('quests.poster_xp'))
        ->and($done->approved_by)->not->toBeNull()
        ->and($done->approved_at)->not->toBeNull();
});

test('new quests receive unique public ids', function () {
    $first = Quest::factory()->create();
    $second = Quest::factory()->create();

    expect($first->public_id)
        ->toMatch('/^[0-9a-f]{24}$/')
        ->not->toBe($second->public_id);
});

test('public ids cannot be mass assigned', function () {
    $quest = Quest::factory()->create();
    $original = $quest->public_id;

    $quest->fill(['public_id' => str_repeat('a', 24)]);

    expect($quest->public_id)->toBe($original);
});

test('the public id migration backfills existing quests', function () {
    $first = Quest::factory()->create();
    $second = Quest::factory()->create();
    /** @var Migration $migration */
    $migration = require database_path('migrations/2026_10_27_140000_add_public_id_to_quests_table.php');

    Schema::table('quests', function (Blueprint $table): void {
        $table->dropUnique(['public_id']);
        $table->dropColumn('public_id');
    });

    $migration->up();

    $ids = DB::table('quests')->pluck('public_id')->all();

    expect($ids)->toHaveCount(Quest::count());

    foreach ($ids as $id) {
        expect($id)->toMatch('/^[0-9a-f]{24}$/');
    }

    expect($first->fresh()->public_id)
        ->not->toBeNull()
        ->not->toBe($second->fresh()->public_id);
});

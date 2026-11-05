<?php

declare(strict_types=1);

use App\Enums\ActivityEvent;
use App\Enums\Badge;
use App\Enums\QuestStatus;
use App\Enums\Role;
use App\Models\Quest;
use App\Models\QuestEvent;
use App\Models\User;
use App\Support\BadgeProgress;
use Database\Seeders\DatabaseSeeder;

test('the local seeder creates an admin, a lead, and three members', function () {
    $this->app['env'] = 'local';

    $this->seed(DatabaseSeeder::class);

    expect(User::count())->toBe(5)
        ->and(User::where('email', 'admin@example.com')->first()->role)->toBe(Role::Admin)
        ->and(User::where('email', 'grace.marlow@example.com')->first()->role)->toBe(Role::Lead)
        ->and(User::where('email', 'kit.thornton@example.com')->first()->role)->toBe(Role::Member)
        ->and(User::where('email', 'kit.thornton@example.com')->first()->portrait_url)->not->toBeNull()
        ->and(User::where('email', 'alan.brooks@example.com')->first()->portrait_url)->not->toBeNull()
        ->and(User::where('email', 'lin.torres@example.com')->first()->portrait_url)->toBeNull();
});

test('seeded users have public ids and a member can view their profile', function () {
    $this->app['env'] = 'local';

    $this->seed(DatabaseSeeder::class);

    $member = User::where('role', Role::Member)->firstOrFail();

    expect(User::whereNull('public_id')->orWhere('public_id', '')->exists())->toBeFalse();

    $this->actingAs($member)
        ->get(route('me'))
        ->assertOk();
});

test('the local seeder creates quests in every status and category with events', function () {
    $this->app['env'] = 'local';

    $this->seed(DatabaseSeeder::class);

    expect(Quest::count())->toBe(8)
        ->and(Quest::pluck('status')->unique()->map->value->sort()->values()->all())
        ->toBe(['done', 'in_progress', 'in_review', 'open'])
        ->and(Quest::pluck('category')->unique()->map->value->sort()->values()->all())
        ->toBe(['docs', 'experiment', 'research', 'tooling'])
        ->and(Quest::status(QuestStatus::Open)->count())->toBe(4)
        ->and(Quest::status(QuestStatus::Done)->count())->toBe(2)
        ->and(Quest::doesntHave('events')->count())->toBe(0)
        ->and(QuestEvent::where('event', ActivityEvent::SentBack)->count())->toBe(1)
        ->and(QuestEvent::where('event', ActivityEvent::Expired)->count())->toBe(1)
        ->and(Quest::status(QuestStatus::Done)->orderByDesc('approved_at')->first()->approved_by)
        ->toBe(User::where('email', 'grace.marlow@example.com')->first()->id);
});

test('each seeded user has at most one active quest', function () {
    $this->app['env'] = 'local';

    $this->seed(DatabaseSeeder::class);

    foreach (User::all() as $user) {
        $active = $user->adventuredQuests()
            ->whereIn('status', [QuestStatus::InProgress, QuestStatus::InReview])
            ->count();

        expect($active)->toBeLessThanOrEqual(1);
    }
});

test('seeded events are in order and the newest done quest feeds the banner', function () {
    $this->app['env'] = 'local';

    $this->seed(DatabaseSeeder::class);

    foreach (Quest::with('events')->get() as $quest) {
        $times = $quest->events->pluck('created_at')->all();
        $sorted = $times;
        usort($sorted, fn ($a, $b) => $a <=> $b);

        expect($times)->toEqual($sorted)
            ->and($quest->created_at->equalTo($quest->events->first()->created_at))->toBeTrue();
    }

    $latest = Quest::status(QuestStatus::Done)->orderByDesc('approved_at')->first();

    expect($latest->title)->toBe('Script the weekly database backup check');
});

test('seeded users hold the badges their done quests earn', function () {
    $this->app['env'] = 'local';

    $this->seed(DatabaseSeeder::class);

    foreach (Quest::status(QuestStatus::Done)->get() as $quest) {
        expect($quest->adventurer->badges()->where('badge', Badge::FirstQuest->value)->exists())->toBeTrue();
    }

    foreach (User::all() as $user) {
        $stored = $user->badges()->pluck('badge')->map(fn (Badge $badge): string => $badge->value)->sort()->values()->all();
        $met = collect(BadgeProgress::for($user)->metTargets())->map(fn (Badge $badge): string => $badge->value)->sort()->values()->all();

        expect($stored)->toBe($met);
    }
});

test('the seeder does nothing outside the local environment', function () {
    $this->app['env'] = 'production';

    // Called directly: db:seed would ask for confirmation in production.
    (new DatabaseSeeder)->run();

    expect(User::count())->toBe(0);
});

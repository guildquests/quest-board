<?php

declare(strict_types=1);

use App\Enums\ActivityEvent;
use App\Enums\Category;
use App\Enums\Difficulty;
use App\Models\Quest;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function questPayload(array $overrides = []): array
{
    return [
        'title' => 'Map the clash rules',
        'summary' => 'Fewer clashes in review',
        'category' => Category::Research->value,
        'difficulty' => Difficulty::Medium->value,
        'brief' => 'Find the rules the team keeps tripping on.',
        'objectives' => ['List the rules', 'Rank them'],
        'proof' => 'A shared doc',
        ...$overrides,
    ];
}

test('guests are redirected to login on every quest route', function (string $method, string $uri) {
    $quest = Quest::factory()->create();

    $this->{$method}(str_replace('{quest}', (string) $quest->id, $uri))
        ->assertRedirect(route('login'));
})->with([
    ['get', '/quests/create'],
    ['post', '/quests'],
    ['get', '/quests/{quest}'],
    ['get', '/quests/{quest}/edit'],
    ['put', '/quests/{quest}'],
    ['delete', '/quests/{quest}'],
    ['post', '/quests/{quest}/accept'],
    ['post', '/quests/{quest}/drop'],
    ['post', '/quests/{quest}/turn-in'],
    ['post', '/quests/{quest}/send-back'],
    ['post', '/quests/{quest}/approve'],
]);

test('the post form lists categories and difficulties', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('quests.create'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('quests/form')
            ->where('quest', null)
            ->has('categories', 4)
            ->where('categories.0', ['value' => 'research', 'label' => 'Research'])
            ->has('difficulties', 3)
            ->where('difficulties.2', [
                'value' => 'hard',
                'label' => 'Hard',
                'xp' => 60,
                'effort' => '1–2 days',
            ]));
});

test('store creates an open quest and opens its page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('quests.store'), questPayload());

    $quest = Quest::firstOrFail();
    $response->assertRedirect(route('quests.show', $quest));

    expect($quest->poster_id)->toBe($user->id)
        ->and($quest->events()->firstOrFail()->event)->toBe(ActivityEvent::Posted);
});

test('store drops blank objective rows', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('quests.store'), questPayload(['objectives' => ['One', '', '  ', 'Two']]))
        ->assertSessionHasNoErrors();

    expect(Quest::firstOrFail()->objectives)->toBe(['One', 'Two']);
});

test('store validates every limit', function (array $overrides, string $field) {
    $this->actingAs(User::factory()->create())
        ->post(route('quests.store'), questPayload($overrides))
        ->assertSessionHasErrors($field);

    expect(Quest::count())->toBe(0);
})->with([
    'title too long' => [['title' => str_repeat('a', 61)], 'title'],
    'summary too long' => [['summary' => str_repeat('a', 91)], 'summary'],
    'bad category' => [['category' => 'cooking'], 'category'],
    'bad difficulty' => [['difficulty' => 'brutal'], 'difficulty'],
    'brief missing' => [['brief' => ''], 'brief'],
    'brief too long' => [['brief' => str_repeat('a', 2001)], 'brief'],
    'no objectives' => [['objectives' => ['', ' ']], 'objectives'],
    'too many objectives' => [['objectives' => array_fill(0, 9, 'x')], 'objectives'],
    'objective too long' => [['objectives' => [str_repeat('a', 121)]], 'objectives.0'],
    'proof too long' => [['proof' => str_repeat('a', 501)], 'proof'],
]);

test('an empty title gets the friendly message', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('quests.store'), questPayload(['title' => '']))
        ->assertSessionHasErrors(['title' => 'Give your quest a title first.']);
});

test('the poster and an admin can edit and update an open quest', function (Closure $actor) {
    $quest = Quest::factory()->create();
    $user = $actor($quest);

    $this->actingAs($user)->get(route('quests.edit', $quest))
        ->assertInertia(fn (Assert $page) => $page
            ->component('quests/form')
            ->where('quest.id', $quest->id)
            ->where('quest.title', $quest->title));

    $this->actingAs($user)
        ->put(route('quests.update', $quest), questPayload(['title' => 'Renamed']))
        ->assertRedirect(route('quests.show', $quest));

    expect($quest->fresh()->title)->toBe('Renamed');
})->with([
    'poster' => [fn (Quest $quest) => $quest->poster],
    'admin' => [fn (Quest $quest) => User::factory()->admin()->create()],
]);

test('others and claimed quests cannot be edited or deleted', function () {
    $open = Quest::factory()->create();
    $claimed = Quest::factory()->inProgress()->create();
    $stranger = User::factory()->lead()->create();

    $this->actingAs($stranger);
    $this->get(route('quests.edit', $open))->assertForbidden();
    $this->put(route('quests.update', $open), questPayload())->assertForbidden();
    $this->delete(route('quests.destroy', $open))->assertForbidden();

    $this->actingAs($claimed->poster);
    $this->get(route('quests.edit', $claimed))->assertForbidden();
    $this->put(route('quests.update', $claimed), questPayload())->assertForbidden();
    $this->delete(route('quests.destroy', $claimed))->assertForbidden();

    expect($open->fresh()->title)->toBe($open->title)
        ->and(Quest::count())->toBe(2);
});

test('the poster and an admin can delete an open quest', function (Closure $actor) {
    $quest = Quest::factory()->create();

    $this->actingAs($actor($quest))
        ->delete(route('quests.destroy', $quest))
        ->assertRedirect(route('board'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Quest deleted.']);

    expect(Quest::count())->toBe(0);
})->with([
    'poster' => [fn (Quest $quest) => $quest->poster],
    'admin' => [fn (Quest $quest) => User::factory()->admin()->create()],
]);

test('show gives an open quest to a member who can accept it', function () {
    $quest = Quest::factory()->create(['difficulty' => Difficulty::Easy]);
    $quest->events()->create(['user_id' => $quest->poster_id, 'event' => ActivityEvent::Posted]);

    $this->actingAs(User::factory()->create())
        ->get(route('quests.show', $quest))
        ->assertInertia(fn (Assert $page) => $page
            ->component('quests/show')
            ->where('quest.id', $quest->id)
            ->where('quest.status', 'open')
            ->where('quest.difficulty', 'easy')
            ->where('quest.effort', '~1 hr')
            ->where('quest.poster.id', $quest->poster_id)
            ->where('quest.adventurer', null)
            ->where('events.0.phrase', 'posted this quest')
            ->where('can', [
                'update' => false,
                'delete' => false,
                'accept' => true,
                'drop' => false,
                'turnIn' => false,
                'review' => false,
            ])
            ->where('acceptBlockedReason', null));
});

test('show explains why accept is blocked', function () {
    $quest = Quest::factory()->create();

    $this->actingAs($quest->poster)
        ->get(route('quests.show', $quest))
        ->assertInertia(fn (Assert $page) => $page
            ->where('can.accept', false)
            ->where('can.update', true)
            ->where('can.delete', true)
            ->where('acceptBlockedReason', "You can have one active quest at a time, and you can't take quests you posted."));
});

test('show gives the adventurer the in-progress actions with dates in the display timezone', function () {
    $this->travelTo('2026-10-25 20:00:00');
    $quest = Quest::factory()->inProgress()->create([
        'due_at' => '2026-10-31 20:00:00',
    ]);

    $this->actingAs($quest->adventurer)
        ->get(route('quests.show', $quest))
        ->assertInertia(fn (Assert $page) => $page
            ->where('quest.status', 'in_progress')
            ->where('quest.adventurer.id', $quest->adventurer_id)
            // 20:00 UTC on 31 Oct is 02:30 on 1 Nov in Asia/Yangon.
            ->where('quest.due_at', 'Sun, 1 Nov')
            ->where('can.drop', true)
            ->where('can.turnIn', true)
            ->where('can.accept', false)
            ->where('can.review', false)
            ->where('acceptBlockedReason', null));
});

test('show gives leads the review flag only while in review', function () {
    $review = Quest::factory()->inReview()->create();
    $progress = Quest::factory()->inProgress()->create();
    $lead = User::factory()->lead()->create();

    $this->actingAs($lead);
    $this->get(route('quests.show', $review))
        ->assertInertia(fn (Assert $page) => $page->where('can.review', true));
    $this->get(route('quests.show', $progress))
        ->assertInertia(fn (Assert $page) => $page->where('can.review', false));

    $this->actingAs(User::factory()->create())
        ->get(route('quests.show', $review))
        ->assertInertia(fn (Assert $page) => $page->where('can.review', false));

    $own = Quest::factory()->inReview()->create(['adventurer_id' => $lead->id]);
    $this->actingAs($lead)
        ->get(route('quests.show', $own))
        ->assertInertia(fn (Assert $page) => $page->where('can.review', false));
});

test('show sends the claim days and the reward xp', function () {
    config(['quests.claim_days' => 5]);
    $open = Quest::factory()->create(['difficulty' => Difficulty::Hard]);
    $done = Quest::factory()->done()->create(['difficulty' => Difficulty::Easy]);
    // xp is not mass assignable: it is only ever set by approval.
    $done->forceFill(['xp' => 25])->save();

    $this->actingAs(User::factory()->create());

    $this->get(route('quests.show', $open))
        ->assertInertia(fn (Assert $page) => $page
            ->where('claimDays', 5)
            ->where('quest.reward_xp', 60)
            ->where('quest.xp', null));

    // Once approved, the stored amount wins over the current difficulty.
    $this->get(route('quests.show', $done))
        ->assertInertia(fn (Assert $page) => $page
            ->where('quest.reward_xp', 25)
            ->where('quest.xp', 25));
});

test('show lists events oldest first with formatted times and notes', function () {
    $quest = Quest::factory()->inProgress()->create();
    $lead = User::factory()->lead()->create();

    // Created out of order on purpose: the page must sort by time, not by id.
    $quest->events()->create([
        'user_id' => $lead->id,
        'event' => ActivityEvent::SentBack,
        'note' => 'Add sources',
        'created_at' => '2026-10-26 18:00:00',
    ]);
    $quest->events()->create([
        'user_id' => $quest->poster_id,
        'event' => ActivityEvent::Posted,
        'created_at' => '2026-10-25 02:00:00',
    ]);
    $quest->events()->create([
        'user_id' => null,
        'event' => ActivityEvent::Expired,
        'created_at' => '2026-10-27 00:00:00',
    ]);

    $this->actingAs(User::factory()->create())
        ->get(route('quests.show', $quest))
        ->assertInertia(fn (Assert $page) => $page
            ->has('events', 3)
            ->where('events.0.event', 'posted')
            ->where('events.0.phrase', 'posted this quest')
            // UTC to Asia/Yangon (+06:30).
            ->where('events.0.at', '25 Oct, 08:30')
            ->where('events.1.event', 'sent_back')
            ->where('events.1.note', 'Add sources')
            ->where('events.1.user.id', $lead->id)
            ->where('events.1.at', '27 Oct, 00:30')
            ->where('events.2.event', 'expired')
            ->where('events.2.user', null)
            ->where('events.2.at', '27 Oct, 06:30'));
});

test('show gives the poster and adventurer a level but not the activity log', function () {
    $quest = Quest::factory()->done()->create(['difficulty' => Difficulty::Hard]);
    Quest::factory()->done()->create(['poster_id' => $quest->poster_id]);
    $quest->events()->create(['user_id' => $quest->adventurer_id, 'event' => ActivityEvent::Accepted]);

    $this->actingAs(User::factory()->create())
        ->get(route('quests.show', $quest))
        ->assertInertia(fn (Assert $page) => $page
            ->where('quest.poster.level', 1)
            ->where('quest.poster.level_title', 'Apprentice')
            ->where('quest.adventurer.level', 2)
            ->where('quest.adventurer.level_title', 'Squire')
            ->missing('events.0.user.level')
            ->missing('events.0.user.level_title'));
});

test('show gives a done quest its xp and approver', function () {
    $quest = Quest::factory()->done()->create(['difficulty' => Difficulty::Hard]);

    $this->actingAs(User::factory()->create())
        ->get(route('quests.show', $quest))
        ->assertInertia(fn (Assert $page) => $page
            ->where('quest.status', 'done')
            ->where('quest.xp', 60)
            ->where('can.accept', false)
            ->where('acceptBlockedReason', null));
});

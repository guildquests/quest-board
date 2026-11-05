<?php

declare(strict_types=1);

use App\Enums\ActivityEvent;
use App\Enums\Difficulty;
use App\Models\Quest;
use App\Models\QuestEvent;
use App\Models\User;
use App\Services\QuestService;
use Inertia\Testing\AssertableInertia as Assert;

test('members cannot open the review queue and reviewers can', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('reviews'))
        ->assertForbidden();

    foreach (['lead', 'admin'] as $role) {
        $this->actingAs(User::factory()->{$role}()->create())
            ->get(route('reviews'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('reviews'));
    }
});

test('the queue contains only in-review quests oldest first with review props', function () {
    $reviewer = User::factory()->lead()->create();
    $oldest = Quest::factory()->inReview()->create([
        'title' => 'Oldest review',
        'difficulty' => Difficulty::Medium,
        'result_url' => 'https://example.com/old',
        'result_note' => 'Please check this.',
        'turned_in_at' => now()->subHour(),
    ]);
    $newer = Quest::factory()->inReview()->create([
        'title' => 'Newer review',
        'result_url' => null,
        'result_note' => null,
        'turned_in_at' => now(),
    ]);
    Quest::factory()->create(['title' => 'Open quest']);
    Quest::factory()->done()->create(['title' => 'Done quest']);

    $this->actingAs($reviewer)
        ->get(route('reviews'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('quests', 2)
            ->where('quests.0.id', $oldest->id)
            ->where('quests.0.category', $oldest->category->value)
            ->where('quests.0.category_label', $oldest->category->label())
            ->where('quests.0.difficulty', 'medium')
            ->where('quests.0.objectives', $oldest->objectives)
            ->where('quests.0.result_url', 'https://example.com/old')
            ->where('quests.0.result_display', 'example.com/old')
            ->where('quests.0.result_note', 'Please check this.')
            ->where('quests.0.adventurer.id', $oldest->adventurer_id)
            ->where('quests.0.poster.id', $oldest->poster_id)
            ->where('quests.0.own', false)
            ->where('quests.1.id', $newer->id)
            ->where('quests.1.result_display', null)
            ->where('quests.1.result_note', null)
            ->where('quests.1.own', false)
            ->where('counts.waiting', 2));
});

test('the adventurer on each card carries a level and title', function () {
    $adventurer = User::factory()->create();
    Quest::factory()->done()->create(['adventurer_id' => $adventurer->id, 'difficulty' => Difficulty::Hard]);
    Quest::factory()->inReview()->create(['adventurer_id' => $adventurer->id]);

    $this->actingAs(User::factory()->lead()->create())
        ->get(route('reviews'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('quests.0.adventurer.level', 2)
            ->where('quests.0.adventurer.level_title', 'Squire')
            ->missing('quests.0.poster.level'));
});

test('the difficulty options and poster xp come from config', function () {
    $this->actingAs(User::factory()->lead()->create())
        ->get(route('reviews'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('difficulties', [
                ['value' => 'easy', 'label' => 'Easy', 'effort' => '~1 hr', 'xp' => 10],
                ['value' => 'medium', 'label' => 'Medium', 'effort' => '~half day', 'xp' => 30],
                ['value' => 'hard', 'label' => 'Hard', 'effort' => '1–2 days', 'xp' => 60],
            ])
            ->where('posterXp', config('quests.poster_xp')));
});

test('own turn-ins are flagged for the current reviewer', function () {
    $lead = User::factory()->lead()->create();
    $own = Quest::factory()->inReview()->create(['adventurer_id' => $lead->id]);
    $other = Quest::factory()->inReview()->create();

    $this->actingAs($lead)
        ->get(route('reviews'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('quests.0.id', $own->id)
            ->where('quests.0.own', true)
            ->where('quests.1.id', $other->id)
            ->where('quests.1.own', false));
});

test('approved this week starts at monday in the display timezone', function () {
    // Monday 00:30 in Asia/Yangon is Sunday 18:00 UTC.
    $this->travelTo('2026-10-25 18:00:00');
    $reviewer = User::factory()->lead()->create();
    $monday = Quest::factory()->done()->create();
    $monday->forceFill(['approved_at' => now()])->save();
    $sunday = Quest::factory()->done()->create();
    $sunday->forceFill(['approved_at' => now()->subMinutes(60)->setTimezone('UTC')])->save();

    $this->actingAs($reviewer)
        ->get(route('reviews'))
        ->assertInertia(fn (Assert $page) => $page->where('counts.approvedThisWeek', 1));
});

test('recent review events are newest first and limited to five', function () {
    $reviewer = User::factory()->lead()->create(['name' => 'Reviewer']);
    $quests = Quest::factory()->count(6)->done()->create();

    foreach ($quests as $index => $quest) {
        QuestEvent::factory()->create([
            'quest_id' => $quest->id,
            'user_id' => $reviewer->id,
            'event' => $index === 0 ? ActivityEvent::SentBack : ActivityEvent::Approved,
            'note' => $index === 0 ? 'Add more detail.' : null,
            'created_at' => now()->subMinutes($index),
        ]);
    }

    $this->actingAs($reviewer)
        ->get(route('reviews'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('recent', 5)
            ->where('recent.0.event', 'sent_back')
            ->where('recent.0.quest.id', $quests[0]->id)
            ->where('recent.0.adventurer.id', $quests[0]->adventurer_id)
            ->where('recent.0.reviewer.name', 'Reviewer')
            ->where('recent.0.note', 'Add more detail.')
            ->where('recent.1.event', 'approved')
            ->where('recent.1.quest.xp', $quests[1]->xp));
});

test('a sent back row shows the adventurer from the turn-in after the claim is dropped', function () {
    $poster = User::factory()->create();
    $lead = User::factory()->lead()->create();
    $adventurer = User::factory()->create();
    $quest = Quest::factory()->create(['poster_id' => $poster->id]);

    $service = app(QuestService::class);
    $service->accept($quest, $adventurer);
    $service->turnIn($quest, $adventurer, 'https://example.com/proof', 'The result.');
    $service->sendBack($quest, $lead, 'Add a screenshot.');
    $service->drop($quest, $adventurer);

    $this->actingAs($lead)
        ->get(route('reviews'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('recent', 1)
            ->where('recent.0.event', 'sent_back')
            ->where('recent.0.adventurer.id', $adventurer->id)
            ->where('recent.0.adventurer.name', $adventurer->name));
});

test('a sent back row keeps the original adventurer after a second person claims', function () {
    $poster = User::factory()->create();
    $lead = User::factory()->lead()->create();
    $adventurer = User::factory()->create();
    $second = User::factory()->create();
    $quest = Quest::factory()->create(['poster_id' => $poster->id]);

    $service = app(QuestService::class);
    $service->accept($quest, $adventurer);
    $service->turnIn($quest, $adventurer, 'https://example.com/proof', 'The result.');
    $service->sendBack($quest, $lead, 'Add a screenshot.');
    $service->drop($quest, $adventurer);
    $service->accept($quest, $second);

    $this->actingAs($lead)
        ->get(route('reviews'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('recent', 1)
            ->where('recent.0.adventurer.id', $adventurer->id)
            ->where('recent.0.adventurer.name', $adventurer->name));
});

test('a review row shows no adventurer when there is no claim and no turn-in', function () {
    $lead = User::factory()->lead()->create();
    $quest = Quest::factory()->create(['adventurer_id' => null]);
    QuestEvent::factory()->create([
        'quest_id' => $quest->id,
        'user_id' => $lead->id,
        'event' => ActivityEvent::SentBack,
        'note' => 'Returned to the board.',
    ]);

    $this->actingAs($lead)
        ->get(route('reviews'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('recent', 1)
            ->where('recent.0.adventurer', null));
});

test('review count is shared for reviewers and null for members', function () {
    Quest::factory()->inReview()->count(2)->create();
    $lead = User::factory()->lead()->create();
    $member = User::factory()->create();

    $this->actingAs($lead)
        ->get(route('board'))
        ->assertInertia(fn (Assert $page) => $page->where('reviewCount', 2));

    $this->actingAs($member)
        ->get(route('board'))
        ->assertInertia(fn (Assert $page) => $page->where('reviewCount', null));
});

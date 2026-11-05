<?php

declare(strict_types=1);

use App\Enums\Difficulty;
use App\Models\Quest;
use App\Models\Setting;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests see an open quest with exactly the public summary', function () {
    $quest = Quest::factory()->create([
        'title' => 'Map the hidden rules',
        'summary' => 'Make the rules easier to understand.',
        'difficulty' => Difficulty::Medium,
    ]);

    $this->get(route('quests.public', ['publicId' => $quest->public_id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/quest')
            ->where('quest', function ($publicQuest) use ($quest): bool {
                $publicQuest = json_decode(json_encode($publicQuest), true);

                return array_keys($publicQuest) === [
                    'id',
                    'title',
                    'summary',
                    'category',
                    'category_label',
                    'difficulty',
                    'difficulty_label',
                    'effort',
                    'xp',
                    'brief',
                    'objectives',
                    'proof',
                    'status',
                    'status_label',
                    'poster',
                    'completed_by',
                ]
                    && $publicQuest['id'] === $quest->id
                    && $publicQuest['title'] === 'Map the hidden rules'
                    && $publicQuest['poster']['name'] === $quest->poster->name
                    && $publicQuest['completed_by'] === null;
            }));
});

test('public summaries do not expose active adventurers or private quest details', function (string $state) {
    $quest = Quest::factory()->{$state}()->create([
        'result_url' => 'https://private.example/result',
        'result_note' => 'Private turn-in note',
    ]);
    $adventurerName = $quest->adventurer->name;

    $this->get(route('quests.public', ['publicId' => $quest->public_id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/quest')
            ->missing('quest.adventurer')
            ->missing('quest.result_url')
            ->missing('quest.result_note')
            ->missing('events')
            ->where('quest.completed_by', null))
        ->assertDontSee($adventurerName)
        ->assertDontSee('private.example/result')
        ->assertDontSee('Private turn-in note');
})->with(['in progress' => 'inProgress', 'in review' => 'inReview']);

test('done public quests show the completed adventurer', function () {
    $quest = Quest::factory()->done()->create();
    $level = $quest->adventurer->loadProgress()->level();

    $this->get(route('quests.public', ['publicId' => $quest->public_id]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/quest')
            ->where('quest.completed_by.name', $quest->adventurer->name)
            ->where('quest.completed_by.level', $level->number)
            ->where('quest.completed_by.level_title', $level->title));
});

test('unknown, malformed, and deleted public ids are unavailable', function (string $publicId) {
    $this->get('/q/'.$publicId)
        ->assertNotFound()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/quest-unavailable'));
})->with([
    'unknown' => '0123456789abcdef01234567',
    'malformed' => 'abc',
    'uppercase' => '0123456789ABCDEF01234567',
    'too long' => '0123456789abcdef012345678',
]);

test('deleted quests are unavailable', function () {
    $quest = Quest::factory()->create();
    $publicId = $quest->public_id;
    $quest->delete();

    $this->get(route('quests.public', ['publicId' => $publicId]))
        ->assertNotFound()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/quest-unavailable'));
});

test('logged-in visitors are redirected to the private quest page', function () {
    $quest = Quest::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('quests.public', ['publicId' => $quest->public_id]))
        ->assertRedirect(route('quests.show', $quest));
});

test('following the accept link preserves the quest as the login intent', function () {
    $quest = Quest::factory()->create();
    $user = User::factory()->create();

    $this->get(route('quests.show', $quest))
        ->assertRedirect(route('login'));

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('quests.show', $quest));
});

test('public quest pages have escaped open graph tags and other pages do not', function () {
    $quest = Quest::factory()->create([
        'title' => '<Quest> "Map" & Learn',
        'summary' => 'A quest for <everyone> & friends.',
    ]);
    $header = url(Setting::current()->headerUrl());

    $this->get(route('quests.public', ['publicId' => $quest->public_id]))
        ->assertOk()
        ->assertSee('property="og:title" content="&lt;Quest&gt; &quot;Map&quot; &amp; Learn"', false)
        ->assertSee('property="og:url" content="'.route('quests.public', ['publicId' => $quest->public_id]).'"', false)
        ->assertSee('property="og:image" content="'.$header.'"', false)
        ->assertSee('name="twitter:card" content="summary_large_image"', false);

    $this->get('/q/0123456789abcdef01234567')
        ->assertNotFound()
        ->assertDontSee('property="og:title"', false);

    $this->get(route('login'))
        ->assertOk()
        ->assertDontSee('property="og:title"', false);
});

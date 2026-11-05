<?php

declare(strict_types=1);

use App\Enums\Difficulty;
use App\Models\Quest;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('the shared auth user has exactly the fields the app needs', function () {
    $user = User::factory()->lead()->withPortrait('portrait-02')->create();

    $this->actingAs($user)
        ->get(route('board'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.user', [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => 'lead',
                'portrait_url' => '/assets/ui/portraits/portrait-02.webp',
                'can_review' => true,
            ]));
});

test('the repository url is shared, and null when the config is empty', function () {
    $this->get(route('login'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('repositoryUrl', 'https://github.com/guildquests/quest-board'));

    config(['app.repository_url' => '']);

    $this->get(route('login'))
        ->assertInertia(fn (Assert $page) => $page->where('repositoryUrl', null));
});

test('progress is level 1 for a new user and null for guests', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('board'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('progress', ['xp' => 0, 'level' => 1, 'title' => 'Apprentice', 'floor' => 0, 'next' => 30]));

    auth()->logout();

    $this->get(route('login'))
        ->assertInertia(fn (Assert $page) => $page->where('progress', null));
});

test('progress adds adventurer xp and poster xp', function () {
    $user = User::factory()->create();
    Quest::factory()->done()->create(['adventurer_id' => $user->id, 'difficulty' => Difficulty::Hard]);
    Quest::factory()->done()->create(['poster_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('board'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('progress', ['xp' => 65, 'level' => 2, 'title' => 'Squire', 'floor' => 30, 'next' => 80])
            ->where('auth.user.id', $user->id));
});

test('members get can_review false and no portrait url without a portrait', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('board'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.user.role', 'member')
            ->where('auth.user.portrait_url', null)
            ->where('auth.user.can_review', false));
});
